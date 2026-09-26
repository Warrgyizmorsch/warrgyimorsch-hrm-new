<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class KpiAssignmentItem extends Model
{
    protected $fillable = [
        'kpi_assignment_id',
        'kpi_id',
        'title',
        'unit',
        'target_value',
        'weightage',
        'auto_metric',
        'actual_value',
    ];

    public function assignment()
    {
        return $this->belongsTo(KpiAssignment::class, 'kpi_assignment_id');
    }

    public function kpi()
    {
        return $this->belongsTo(Kpi::class);
    }

    public function getPercentageAttribute(): ?float
    {
        if ($this->actual_value === null || (float) $this->target_value <= 0) {
            return null;
        }

        return round(((float) $this->actual_value / (float) $this->target_value) * 100, 2);
    }
}
