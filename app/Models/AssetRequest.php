<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AssetRequest extends Model
{
    use HasFactory;

    public const SOURCE_REQUEST = 'request';
    public const SOURCE_DECLARATION = 'declaration';

    protected $fillable = [
        'user_id',
        'source',
        'asset_type',
        'reason',
        'status',
        'asset_id',
        'allocated_at',
        'returned_at',
        'declared_name',
        'declared_serial_number',
        'declared_configuration',
        'declared_image_path',
        'created_by',
        'verified_by',
        'verified_at',
        'rejection_reason',
    ];

    protected $casts = [
        'allocated_at' => 'datetime',
        'returned_at' => 'datetime',
        'verified_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function asset()
    {
        // withTrashed: history rows still show the device after it's (soft) deleted.
        return $this->belongsTo(Asset::class, 'asset_id')->withTrashed();
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function verifier()
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public function isDeclaration(): bool
    {
        return $this->source === self::SOURCE_DECLARATION;
    }

    public function getDeclaredImageUrlAttribute(): ?string
    {
        return $this->declared_image_path ? asset('storage/' . ltrim($this->declared_image_path, '/')) : null;
    }
}
