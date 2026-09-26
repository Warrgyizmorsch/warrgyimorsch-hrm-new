<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Kpi extends Model
{
    // Built-in computations available for 'auto_metric'. Add new keys here as more get wired up.
    public const AUTO_METRICS = [
        'tasks_completed_on_time' => 'Tasks Completed On Time (from Task Management)',
    ];

    protected $fillable = [
        'department_id',
        'title',
        'unit',
        'target_value',
        'weightage',
        'auto_metric',
        'sort_order',
        'status',
    ];

    protected $casts = [
        'status' => 'boolean',
    ];

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function scopeActive($query)
    {
        return $query->where('status', true);
    }
}
