<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProjectChecklistCompletion extends Model
{
    protected $fillable = [
        'template_id',
        'week_start',
        'is_done',
        'note',
        'completed_by',
        'completed_at',
    ];

    protected $casts = [
        'week_start' => 'date',
        'is_done' => 'boolean',
        'completed_at' => 'datetime',
    ];

    public function template()
    {
        return $this->belongsTo(ProjectChecklistTemplate::class, 'template_id');
    }
}
