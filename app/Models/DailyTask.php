<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DailyTask extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_id',
        'task_title',
        'start_date',
        'end_date',
        'priority',
        'status',
        'employee_id',
        'assigned_by',
        'description',
        'photo',
        'status_changed_at',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'status_changed_at' => 'datetime',
    ];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }

    public function followUps()
    {
        return $this->hasMany(TaskFollowUp::class, 'daily_task_id');
    }

    public function getTotalTimeAttribute()
    {
        return $this->followUps->reduce(function ($total, $fu) {
            // Extract numeric part from strings like "5 hours", "2.5 days" etc.
            if (preg_match('/[+-]?([0-9]*[.])?[0-9]+/', $fu->time_taken, $matches)) {
                return $total + (float) $matches[0];
            }
            return $total;
        }, 0);
    }

    public function getFormattedTotalTimeAttribute()
    {
        $totalHours = $this->total_time;
        $h = floor($totalHours);
        $m = round(($totalHours - $h) * 60);

        $display = [];
        if ($h > 0) $display[] = $h . 'h';
        if ($m > 0) $display[] = $m . 'm';

        return count($display) > 0 ? implode(' ', $display) : '0m';
    }

    public function getStatusColorAttribute()
    {
        return match ($this->status) {
            'Pending' => 'bg-soft-pending text-pending',
            'In Process' => 'bg-soft-process text-process',
            'Completed' => 'bg-soft-completed text-completed',
            'On Hold' => 'bg-soft-hold text-hold',
            'Review' => 'bg-soft-review text-review',
            'Rework' => 'bg-soft-rework text-rework',
            default => 'bg-soft-secondary text-secondary',
        };
    }

    public function statusHistory()
    {
        return $this->hasMany(
            TaskStatusHistory::class,
            'task_id'
        )->latest();
    }

    public function latestStatusHistory()
    {
        return $this->hasOne(TaskStatusHistory::class, 'task_id')->latestOfMany();
    }
}
