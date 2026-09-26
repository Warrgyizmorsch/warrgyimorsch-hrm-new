<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class KpiAssignment extends Model
{
    protected $fillable = [
        'employee_id',
        'month',
        'assigned_by',
        'acknowledged_at',
    ];

    protected $casts = [
        'acknowledged_at' => 'datetime',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function assignedBy()
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }

    public function items()
    {
        return $this->hasMany(KpiAssignmentItem::class);
    }

    // Weighted overall score across items that have an actual value recorded yet.
    public function getOverallScoreAttribute(): ?float
    {
        $scored = $this->items->filter(fn ($item) => $item->actual_value !== null);
        if ($scored->isEmpty()) {
            return null;
        }

        $totalWeight = $scored->sum('weightage');
        if ($totalWeight <= 0) {
            return round($scored->avg(fn ($item) => $item->percentage), 2);
        }

        $weightedSum = $scored->sum(fn ($item) => $item->percentage * $item->weightage);
        return round($weightedSum / $totalWeight, 2);
    }
}
