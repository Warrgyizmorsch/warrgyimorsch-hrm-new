<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

/**
 * A damage or loss case for one asset, with its cost and the amount recovered from the employee.
 */
class AssetIncident extends Model
{
    public const TYPE_DAMAGE = 'damage';
    public const TYPE_LOST = 'lost';
    public const TYPES = [self::TYPE_DAMAGE => 'Damage', self::TYPE_LOST => 'Lost'];

    public const STATUS_OPEN = 'open';
    public const STATUS_IN_REPAIR = 'in_repair';
    public const STATUS_CLOSED = 'closed';
    public const OPEN_STATUSES = [self::STATUS_OPEN, self::STATUS_IN_REPAIR];
    public const STATUSES = [self::STATUS_OPEN => 'Open', self::STATUS_IN_REPAIR => 'In repair', self::STATUS_CLOSED => 'Closed'];

    /** How each type can be closed. */
    public const RESOLUTIONS = [
        self::TYPE_DAMAGE => [
            'repaired' => 'Repaired - back in use',
            'no_action' => 'Minor - still usable, no repair',
            'written_off' => 'Beyond repair - write off',
        ],
        self::TYPE_LOST => [
            'found' => 'Found - back in stock',
            'written_off' => 'Not found - write off',
        ],
    ];

    public const RECOVERY_STATUSES = [
        'none' => 'No recovery',
        'pending' => 'Recovery pending',
        'recovered' => 'Recovered',
        'waived' => 'Waived',
    ];

    public const ATTACHMENT_DISK = 'public';

    protected $fillable = [
        'asset_id', 'asset_request_id', 'user_id', 'user_name', 'reported_by', 'type', 'incident_date',
        'description', 'attachment_path', 'status', 'resolution', 'cost', 'recovery_amount',
        'recovery_status', 'recovered_amount', 'recovered_at', 'resolution_notes', 'resolved_by', 'resolved_at',
    ];

    protected $casts = [
        'incident_date' => 'date',
        'recovered_at' => 'date',
        'resolved_at' => 'datetime',
        'cost' => 'decimal:2',
        'recovery_amount' => 'decimal:2',
        'recovered_amount' => 'decimal:2',
    ];

    public function asset()
    {
        return $this->belongsTo(Asset::class)->withTrashed();
    }

    public function allocation()
    {
        return $this->belongsTo(AssetRequest::class, 'asset_request_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function reporter()
    {
        return $this->belongsTo(User::class, 'reported_by');
    }

    public function resolver()
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }

    public function isOpen(): bool
    {
        return in_array($this->status, self::OPEN_STATUSES, true);
    }

    public function scopeOpen($query)
    {
        return $query->whereIn('status', self::OPEN_STATUSES);
    }

    public function getReferenceAttribute(): string
    {
        return 'INC-' . str_pad((string) $this->id, 4, '0', STR_PAD_LEFT);
    }

    public function getHolderNameAttribute(): ?string
    {
        return $this->user->name ?? $this->user_name;
    }

    public function getOutstandingAmountAttribute(): float
    {
        if ($this->recovery_status !== 'pending') {
            return 0.0;
        }

        return max(0, (float) $this->recovery_amount - (float) $this->recovered_amount);
    }

    public function getResolutionLabelAttribute(): ?string
    {
        return $this->resolution ? (self::RESOLUTIONS[$this->type][$this->resolution] ?? ucfirst(str_replace('_', ' ', $this->resolution))) : null;
    }

    public function getAttachmentUrlAttribute(): ?string
    {
        return $this->attachment_path ? Storage::disk(self::ATTACHMENT_DISK)->url($this->attachment_path) : null;
    }
}
