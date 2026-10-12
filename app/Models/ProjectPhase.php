<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProjectPhase extends Model
{
    public const STATUSES = ['Planned', 'In Progress', 'Completed', 'On Hold'];

    public const DONE_TASK_STATUSES = ['completed', 'done'];

    protected $fillable = [
        'project_id',
        'name',
        'description',
        'documents',
        'start_date',
        'due_date',
        'status',
        'owner_id',
        'sort_order',
        'created_by',
    ];

    protected $casts = [
        'start_date' => 'date',
        'due_date' => 'date',
        'documents' => 'array',
    ];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function tasks()
    {
        return $this->hasMany(DailyTask::class, 'phase_id');
    }

    public function owner()
    {
        return $this->belongsTo(Employee::class, 'owner_id');
    }

    /**
     * Percentage of this phase's tasks that are done. A phase marked Completed is always 100.
     * Expects the `tasks` relation to be loaded to avoid extra queries.
     */
    public function getProgressAttribute(): int
    {
        if ($this->status === 'Completed') {
            return 100;
        }

        $tasks = $this->tasks;
        if ($tasks->isEmpty()) {
            return 0;
        }

        $done = $tasks->filter(fn ($t) => in_array(strtolower($t->status ?? ''), self::DONE_TASK_STATUSES, true))->count();

        return (int) round($done / $tasks->count() * 100);
    }

    public function getIsOverdueAttribute(): bool
    {
        return $this->due_date
            && $this->status !== 'Completed'
            && $this->due_date->copy()->endOfDay()->isPast();
    }
}
