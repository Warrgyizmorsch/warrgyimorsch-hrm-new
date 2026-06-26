<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Payroll extends Model
{
    protected $fillable = [
        'employee_id',
        'month',
        'payable_days',
        'unpaid_days',
        'paid_leave_days',
        'unpaid_leave_days',
        'leave_balance_before_payroll',
        'overtime_hours',
        'overtime_days',
        'gross_salary',
        'basic_salary',
        'hra',
        'conveyance_allowance',
        'medical_allowance',
        'other_allowance',
        'deductions',
        'pf_deduction',
        'esi_deduction',
        'other_deduction',
        'net_salary',
        'salary_loss',
        'status',
        'payment_date',
        'remarks',
    ];

    protected $casts = [
        'payment_date' => 'date',
        'payable_days' => 'decimal:2',
        'unpaid_days' => 'decimal:2',
        'paid_leave_days' => 'decimal:2',
        'unpaid_leave_days' => 'decimal:2',
        'leave_balance_before_payroll' => 'decimal:2',
        'overtime_hours' => 'decimal:2',
        'overtime_days' => 'decimal:2',
        'salary_loss' => 'decimal:2',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }
}
