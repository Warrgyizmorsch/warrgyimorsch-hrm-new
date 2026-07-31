<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Broadcast extends Model
{
    use HasFactory;

    protected $table = 'broadcasts';

    protected $fillable = [
        'department',
        'message',
        'documents',
    ];

    protected $casts = [
        'documents' => 'array',
    ];

    public function readByUsers()
    {
        return $this->belongsToMany(User::class)->withPivot('read_at');
    }
}
