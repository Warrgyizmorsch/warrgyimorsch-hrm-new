<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class KraAssignment extends Model
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
        return $this->hasMany(KraAssignmentItem::class);
    }

    public function getTotalMaxPointsAttribute(): float
    {
        return (float) $this->items->sum('max_point');
    }
}
