<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AssetAuditItem extends Model
{
    public const RESULTS = [
        'pending' => 'Not checked',
        'found' => 'Found - OK',
        'wrong_holder' => 'Found with someone else',
        'damaged' => 'Found damaged',
        'missing' => 'Missing',
    ];

    protected $fillable = [
        'asset_audit_id', 'asset_id', 'expected_user_id', 'expected_user_name', 'expected_status',
        'result', 'notes', 'checked_by', 'checked_at', 'asset_incident_id',
    ];

    protected $casts = ['checked_at' => 'datetime'];

    public function audit()
    {
        return $this->belongsTo(AssetAudit::class, 'asset_audit_id');
    }

    public function asset()
    {
        return $this->belongsTo(Asset::class)->withTrashed();
    }

    public function expectedUser()
    {
        return $this->belongsTo(User::class, 'expected_user_id');
    }

    public function checker()
    {
        return $this->belongsTo(User::class, 'checked_by');
    }

    public function incident()
    {
        return $this->belongsTo(AssetIncident::class, 'asset_incident_id');
    }
}
