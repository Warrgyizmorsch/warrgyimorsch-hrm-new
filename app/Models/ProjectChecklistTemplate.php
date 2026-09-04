<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProjectChecklistTemplate extends Model
{
    protected $fillable = [
        'project_id',
        'label',
        'assigned_to',
        'due_weekday',
        'sort_order',
        'created_by',
    ];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function completions()
    {
        return $this->hasMany(ProjectChecklistCompletion::class, 'template_id');
    }

    public function assignee()
    {
        return $this->belongsTo(Employee::class, 'assigned_to');
    }
}
