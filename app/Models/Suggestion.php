<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Suggestion extends Model
{
    public const CATEGORIES = [
        'Work Culture',
        'Management & Leadership',
        'Process & Productivity',
        'Facilities & Resources',
        'Compensation & Benefits',
        'Other',
    ];

    public const STATUSES = [
        'Open',
        'In Progress',
        'Resolved',
        'Closed',
    ];

    protected $fillable = [
        'user_id',
        'category',
        'message',
        'status',
        'manager_reply',
        'appreciated',
        'replied_by',
        'replied_at',
    ];

    protected function casts(): array
    {
        return [
            'appreciated' => 'boolean',
            'replied_at' => 'datetime',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function repliedBy()
    {
        return $this->belongsTo(User::class, 'replied_by');
    }
}
