<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class Asset extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'asset_tag',
        'name',
        'serial_number',
        'type',
        'brand',
        'model',
        'system_configuration',
        'extra_assets',
        'status',
        'condition',
        'location',
        'purchase_date',
        'purchase_cost',
        'vendor',
        'invoice_number',
        'warranty_expiry',
        'notes',
        'image_path',
    ];

    protected $casts = [
        'purchase_date' => 'date',
        'warranty_expiry' => 'date',
        'purchase_cost' => 'decimal:2',
    ];

    public const IMAGE_DISK = 'public';

    public const TAG_PREFIX = 'WM';

    /** Full lifecycle. Lost / Retired are only set through their own actions (see AssetService). */
    public const STATUSES = ['Available', 'Allocated', 'Maintenance', 'Faulty', 'Lost', 'Retired'];

    /** Bootstrap colour for each status badge (bg-soft-{colour} text-{colour}). */
    public const STATUS_COLORS = [
        'Available' => 'success', 'Allocated' => 'primary', 'Maintenance' => 'warning',
        'Faulty' => 'danger', 'Lost' => 'danger', 'Retired' => 'secondary',
    ];

    public const CONDITIONS = ['New', 'Good', 'Fair', 'Damaged'];

    public const TYPES = ['PC', 'Laptop', 'Monitor', 'Keyboard', 'Mouse', 'Charger', 'Headphone', 'Earphone', 'Pendrive/Hard disk', 'Mobile', 'Other'];

    public const TYPE_CODES = [
        'pc' => 'PC', 'laptop' => 'LT', 'keyboard' => 'KB', 'mouse' => 'MS', 'monitor' => 'MN',
        'charger' => 'CH', 'headphone' => 'HP', 'earphone' => 'EP', 'pendrive/hard disk' => 'SD',
        'mobile' => 'MB', 'other' => 'OT',
    ];

    /** Fields whose edits are written to the asset history. */
    public const TRACKED_FIELDS = [
        'name', 'type', 'serial_number', 'brand', 'model', 'system_configuration', 'condition', 'location',
        'purchase_date', 'purchase_cost', 'vendor', 'invoice_number', 'warranty_expiry', 'notes', 'image_path',
    ];

    protected $appends = ['image_url'];

    protected static function booted(): void
    {
        static::creating(function (Asset $asset) {
            if (blank($asset->asset_tag)) {
                $asset->asset_tag = static::nextTag($asset->type);
            }
            $asset->condition ??= 'Good';
        });

        static::created(function (Asset $asset) {
            AssetEvent::record($asset, 'created', [
                'to_status' => $asset->status,
                'notes' => trim(($asset->asset_tag ? $asset->asset_tag . ' - ' : '') . $asset->name),
            ]);
        });

        // Detail edits (not status/holder - AssetService logs those with their context).
        static::updated(function (Asset $asset) {
            $changed = array_intersect(array_keys($asset->getChanges()), self::TRACKED_FIELDS);
            if (!$changed) {
                return;
            }

            $changes = [];
            foreach ($changed as $field) {
                $old = $asset->getOriginal($field);
                $new = $asset->getAttribute($field);
                $changes[$field] = [
                    'from' => $old instanceof \DateTimeInterface ? $old->format('Y-m-d') : $old,
                    'to' => $new instanceof \DateTimeInterface ? $new->format('Y-m-d') : $new,
                ];
            }

            AssetEvent::record($asset, 'details_updated', [
                'notes' => 'Updated ' . implode(', ', array_map(fn ($f) => str_replace('_', ' ', $f), $changed)),
                'meta' => ['changes' => $changes],
            ]);
        });
    }

    public static function typeCode(?string $type): string
    {
        $type = strtolower(trim((string) $type));

        return self::TYPE_CODES[$type]
            ?? (strtoupper(substr(preg_replace('/[^A-Za-z]/', '', $type), 0, 2)) ?: 'OT');
    }

    /** Next free tag for this type, e.g. WM-LT-0007. Counts deleted assets too so tags never repeat. */
    public static function nextTag(?string $type): string
    {
        $prefix = self::TAG_PREFIX . '-' . self::typeCode($type) . '-';

        $max = static::withTrashed()
            ->where('asset_tag', 'like', $prefix . '%')
            ->pluck('asset_tag')
            ->map(fn ($tag) => (int) substr($tag, strlen($prefix)))
            ->max() ?? 0;

        return $prefix . str_pad((string) ($max + 1), 4, '0', STR_PAD_LEFT);
    }

    /** Public URL of the asset photo, or null when none was uploaded. */
    public function getImageUrlAttribute(): ?string
    {
        return $this->image_path
            ? Storage::disk(self::IMAGE_DISK)->url($this->image_path)
            : null;
    }

    public function getDisplayNameAttribute(): string
    {
        return trim(($this->asset_tag ? $this->asset_tag . ' · ' : '') . $this->name);
    }

    /** 'none' | 'active' | 'expiring' (within 60 days) | 'expired' */
    public function getWarrantyStateAttribute(): string
    {
        if (!$this->warranty_expiry) {
            return 'none';
        }
        if ($this->warranty_expiry->isPast() && !$this->warranty_expiry->isToday()) {
            return 'expired';
        }

        return $this->warranty_expiry->lte(now()->addDays(60)) ? 'expiring' : 'active';
    }

    public function isClosed(): bool
    {
        return in_array($this->status, ['Lost', 'Retired'], true);
    }

    public function requests()
    {
        return $this->hasMany(AssetRequest::class, 'asset_id');
    }

    /** Every handover row (current and past), newest first. */
    public function allocations()
    {
        return $this->hasMany(AssetRequest::class, 'asset_id')
            ->whereIn('status', ['Allocated', 'Returned', 'Lost'])
            ->orderByDesc('allocated_at')
            ->orderByDesc('id');
    }

    public function activeAllocation()
    {
        return $this->hasOne(AssetRequest::class, 'asset_id')
            ->where('status', 'Allocated')
            ->latestOfMany();
    }

    public function events()
    {
        return $this->hasMany(AssetEvent::class)->orderByDesc('created_at')->orderByDesc('id');
    }

    public function incidents()
    {
        return $this->hasMany(AssetIncident::class)->latest();
    }

    public function openIncidents()
    {
        return $this->hasMany(AssetIncident::class)->whereIn('status', AssetIncident::OPEN_STATUSES);
    }

    public function scopeSearch($query, ?string $term)
    {
        $term = trim((string) $term);
        if ($term === '') {
            return $query;
        }

        $like = '%' . str_replace(['%', '_'], ['\%', '\_'], $term) . '%';

        return $query->where(function ($q) use ($like) {
            $q->where('asset_tag', 'like', $like)
                ->orWhere('name', 'like', $like)
                ->orWhere('serial_number', 'like', $like)
                ->orWhere('brand', 'like', $like)
                ->orWhere('model', 'like', $like)
                ->orWhere('location', 'like', $like)
                ->orWhereHas('activeAllocation.user', fn ($u) => $u->where('name', 'like', $like));
        });
    }
}
