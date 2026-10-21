<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** One salary increment (see App\Services\IncrementService). */
class EmployeeIncrement extends Model
{
    protected $fillable = [
        'employee_id',
        'effective_date',
        'previous_gross',
        'increment_amount',
        'new_gross',
        'increment_percent',
        'previous_components',
        'new_components',
        'remarks',
        'applied_at',
        'created_by',
    ];

    protected $casts = [
        'effective_date' => 'date',
        'previous_gross' => 'float',
        'increment_amount' => 'float',
        'new_gross' => 'float',
        'increment_percent' => 'float',
        'previous_components' => 'array',
        'new_components' => 'array',
        'applied_at' => 'datetime',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function isApplied(): bool
    {
        return $this->applied_at !== null;
    }
}
