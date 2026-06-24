<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Asset extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'serial_number',
        'type',
        'system_configuration',
        'extra_assets',
        'status',
    ];

    public function requests()
    {
        return $this->hasMany(AssetRequest::class, 'asset_id');
    }
}
