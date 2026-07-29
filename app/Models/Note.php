<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Note extends Model
{
    public const TYPES = [
        'priority',
        'meeting',
    ];

    protected $fillable = [
        'user_id',
        'type',
        'title',
        'remind_at',
        'is_completed',
    ];

    protected function casts(): array
    {
        return [
            'remind_at' => 'datetime',
            'is_completed' => 'boolean',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
