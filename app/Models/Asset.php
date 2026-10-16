<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Asset extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'serial_number',
        'type',
        'system_configuration',
        'extra_assets',
        'status',
        'image_path',
    ];

    public const IMAGE_DISK = 'public';

    protected $appends = ['image_url'];

    /** Public URL of the asset photo, or null when none was uploaded. */
    public function getImageUrlAttribute(): ?string
    {
        return $this->image_path
            ? \Illuminate\Support\Facades\Storage::disk(self::IMAGE_DISK)->url($this->image_path)
            : null;
    }

    public function requests()
    {
        return $this->hasMany(AssetRequest::class, 'asset_id');
    }

    public function activeAllocation()
    {
        return $this->hasOne(AssetRequest::class, 'asset_id')
            ->where('status', 'Allocated')
            ->latestOfMany();
    }
}
