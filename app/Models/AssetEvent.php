<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * One line of an asset's history. Append-only: written through AssetEvent::record().
 */
class AssetEvent extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = [
        'asset_id', 'event', 'from_user_id', 'to_user_id', 'from_status', 'to_status',
        'asset_request_id', 'asset_incident_id', 'performed_by', 'notes', 'meta', 'created_at',
    ];

    protected $casts = [
        'meta' => 'array',
        'created_at' => 'datetime',
    ];

    /** Label + icon + colour used by the history timeline. */
    public const LABELS = [
        'created' => ['Added to inventory', 'feather-plus-circle', 'primary'],
        'details_updated' => ['Details updated', 'feather-edit-3', 'secondary'],
        'allocated' => ['Allocated', 'feather-user-plus', 'success'],
        'transferred' => ['Transferred', 'feather-repeat', 'info'],
        'returned' => ['Returned to stock', 'feather-corner-down-left', 'secondary'],
        'acknowledged' => ['Receipt confirmed', 'feather-check-circle', 'success'],
        'disputed' => ['Receipt disputed', 'feather-alert-circle', 'warning'],
        'status_changed' => ['Status changed', 'feather-toggle-right', 'secondary'],
        'damage_reported' => ['Damage reported', 'feather-alert-triangle', 'warning'],
        'lost_reported' => ['Reported lost', 'feather-x-octagon', 'danger'],
        'sent_to_repair' => ['Sent for repair', 'feather-tool', 'warning'],
        'repaired' => ['Repaired', 'feather-check-square', 'success'],
        'found' => ['Found', 'feather-search', 'success'],
        'written_off' => ['Written off', 'feather-slash', 'danger'],
        'incident_closed' => ['Incident closed', 'feather-check', 'secondary'],
        'recovery_updated' => ['Recovery updated', 'feather-dollar-sign', 'info'],
        'retired' => ['Retired', 'feather-archive', 'dark'],
        'reactivated' => ['Back in service', 'feather-rotate-ccw', 'success'],
        'audited' => ['Audit check', 'feather-clipboard', 'info'],
        'deleted' => ['Deleted', 'feather-trash-2', 'danger'],
    ];

    /**
     * Write a history line. $data: from_user_id, to_user_id, from_status, to_status,
     * asset_request_id, asset_incident_id, notes, meta, performed_by (defaults to the logged-in user).
     */
    public static function record(Asset $asset, string $event, array $data = []): self
    {
        $meta = $data['meta'] ?? [];

        // Keep names readable even if the user rows are purged later.
        foreach (['from_user_id' => 'from_name', 'to_user_id' => 'to_name'] as $idKey => $nameKey) {
            if (!empty($data[$idKey]) && !isset($meta[$nameKey])) {
                $meta[$nameKey] = User::whereKey($data[$idKey])->value('name');
            }
        }

        return static::create([
            'asset_id' => $asset->id,
            'event' => $event,
            'from_user_id' => $data['from_user_id'] ?? null,
            'to_user_id' => $data['to_user_id'] ?? null,
            'from_status' => $data['from_status'] ?? null,
            'to_status' => $data['to_status'] ?? null,
            'asset_request_id' => $data['asset_request_id'] ?? null,
            'asset_incident_id' => $data['asset_incident_id'] ?? null,
            'performed_by' => array_key_exists('performed_by', $data) ? $data['performed_by'] : auth()->id(),
            'notes' => $data['notes'] ?? null,
            'meta' => $meta ?: null,
            'created_at' => now(),
        ]);
    }

    public function asset()
    {
        return $this->belongsTo(Asset::class)->withTrashed();
    }

    public function fromUser()
    {
        return $this->belongsTo(User::class, 'from_user_id');
    }

    public function toUser()
    {
        return $this->belongsTo(User::class, 'to_user_id');
    }

    public function performer()
    {
        return $this->belongsTo(User::class, 'performed_by');
    }

    public function incident()
    {
        return $this->belongsTo(AssetIncident::class, 'asset_incident_id');
    }

    public function getLabelAttribute(): string
    {
        return self::LABELS[$this->event][0] ?? ucfirst(str_replace('_', ' ', $this->event));
    }

    public function getIconAttribute(): string
    {
        return self::LABELS[$this->event][1] ?? 'feather-circle';
    }

    public function getColorAttribute(): string
    {
        return self::LABELS[$this->event][2] ?? 'secondary';
    }

    public function getFromNameAttribute(): ?string
    {
        return $this->fromUser->name ?? ($this->meta['from_name'] ?? null);
    }

    public function getToNameAttribute(): ?string
    {
        return $this->toUser->name ?? ($this->meta['to_name'] ?? null);
    }
}
