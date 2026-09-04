<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Project extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'start_date',
        'end_date',
        'status',
        'description',
        'technology',
        // 'type',
        // 'manage',
        'leaders',
        'members',
        'documents',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'leaders' => 'array',
        'members' => 'array',
        'documents' => 'array',
    ];

    public function getRouteKeyName()
    {
        return 'slug';
    }

    public function tasks()
    {
        return $this->hasMany(DailyTask::class);
    }

    // Departments this project is tagged with (a project can belong to several).
    public function departments()
    {
        return $this->belongsToMany(Department::class, 'department_project');
    }

    public function checklistTemplates()
    {
        return $this->hasMany(ProjectChecklistTemplate::class)->orderBy('sort_order');
    }

    public function getNormalizedStatusAttribute(): string
    {
        $status = $this->status ?? 'Pending';

        return match ($status) {
            'Not Started' => 'Pending',
            'In Progress' => 'In Process',
            'Finished' => 'Completed',
            default => $status,
        };
    }

    public function getTaskProgressAttribute(): int
    {
        if (in_array(strtolower($this->normalized_status), ['completed', 'finished'])) {
            return 100;
        }

        $totalTasks = (int) ($this->tasks_count ?? $this->tasks()->count());
        if ($totalTasks === 0) {
            return (int) $this->progress;
        }

        $completedTasks = (int) ($this->completed_tasks_count ?? $this->tasks()
            ->whereIn('status', ['Completed', 'Done', 'completed', 'done'])
            ->count());

        return min(100, (int) round(($completedTasks / $totalTasks) * 100));
    }

    public function getDisplayProgressAttribute(): int
    {
        return $this->task_progress;
    }

    public function getProgressAttribute()
    {
        // If explicitly set to completed status, it's 100%
        if (in_array(strtolower($this->status), ['completed', 'finished'])) {
            return 100;
        }

        $startDate = $this->start_date;
        $endDate = $this->end_date;
        $now = now();

        if ($startDate) {
            if ($now < $startDate) {
                return 0;
            }

            if ($endDate) {
                $totalDuration = $startDate->diffInSeconds($endDate);
                if ($totalDuration <= 0) return 100; // Edge case
                
                $elapsed = $startDate->diffInSeconds($now);
                $progress = ($elapsed / $totalDuration) * 100;
                return min(100, round($progress));
            } else {
                $daysPassed = $startDate->diffInDays($now);
                return min(100, round($daysPassed));
            }
        }

        return 0;
    }

    public function getIsOverdueAttribute(): bool
    {
        if (!$this->end_date || in_array(strtolower($this->normalized_status), ['completed', 'finished'])) {
            return false;
        }

        return $this->end_date->isPast();
    }

    public function getStatusToneAttribute(): string
    {
        return match ($this->normalized_status) {
            'Pending' => 'pending',
            'In Process' => 'process',
            'Completed' => 'completed',
            'On Hold' => 'hold',
            'Review' => 'review',
            'Rework' => 'rework',
            default => 'default',
        };
    }

    public function getStatusColorAttribute(): string
    {
        return match ($this->normalized_status) {
            'Pending' => 'bg-soft-pending text-pending',
            'In Process' => 'bg-soft-process text-process',
            'Completed' => 'bg-soft-completed text-completed',
            'On Hold' => 'bg-soft-hold text-hold',
            'Review' => 'bg-soft-review text-review',
            'Rework' => 'bg-soft-rework text-rework',
            default => 'bg-soft-secondary text-secondary',
        };
    }
}

