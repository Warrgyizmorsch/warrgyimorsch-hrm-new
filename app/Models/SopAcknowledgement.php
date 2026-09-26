<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SopAcknowledgement extends Model
{
    protected $fillable = [
        'sop_id',
        'user_id',
        'version',
        'acknowledged_at',
    ];

    protected $casts = [
        'acknowledged_at' => 'datetime',
    ];

    public function sop()
    {
        return $this->belongsTo(Sop::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
