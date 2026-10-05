<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LeaveApplication extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_id',
        'leave_type',
        'leave_category',
        'start_date',
        'end_date',
        'reason',
        'message',
        'status',
        'total_days',
        'start_time',
        'end_time',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'start_time' => 'datetime:H:i',
        'end_time' => 'datetime:H:i',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function isWfh(): bool
    {
        return str_contains(strtolower($this->leave_category ?? ''), 'wfh')
            || str_contains(strtolower($this->leave_type ?? ''), 'wfh');
    }

    // Applications whose range covers $date (single-day ones may have a null end_date).
    public function scopeCoveringDate($query, string $date)
    {
        return $query->whereDate('start_date', '<=', $date)
            ->where(function ($q) use ($date) {
                $q->whereDate('end_date', '>=', $date)
                    ->orWhere(function ($q2) use ($date) {
                        $q2->whereNull('end_date')->whereDate('start_date', $date);
                    });
            });
    }

    // When a long WFH range overlaps an actual leave, the leave wins for that day —
    // order real leaves first so ->first() picks them over WFH.
    public function scopePreferActualLeave($query)
    {
        return $query->orderByRaw(
            "CASE WHEN LOWER(COALESCE(leave_category, '')) LIKE '%wfh%' OR LOWER(COALESCE(leave_type, '')) LIKE '%wfh%' THEN 1 ELSE 0 END"
        )->orderByDesc('id');
    }
}
