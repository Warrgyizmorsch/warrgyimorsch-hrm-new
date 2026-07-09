<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Employee extends Model
{
    protected $fillable = [
        'name',
        'employee_code',
        'email',
        'mobile_number',
        'role',
        'department',
        'designation',
        'date_of_joining',
        'date_of_birth',
        'gender',
        'password',
        'aadhaar_number',
        'pan_number',
        'address',
        'time_in',
        'time_out',
        'sunday_time_in',
        'sunday_time_out',
        'leave',
        'photo',
        'pf',
        'pf_number',
        'esi',
        'esi_number',
        'insurance',
        'insurance_provider',
        'insurance_policy_number',
        'bank_name',
        'account_number',
        'ifsc_code',
        'basic_salary',
        'hra',
        'conveyance_allowance',
        'medical_allowance',
        'other_allowance',
        'working_mode',
    ];

    public function leaveAllotments()
    {
        return $this->hasMany(LeaveAllotment::class);
    }

    public function tasks()
    {
        return $this->hasMany(DailyTask::class);
    }
    public function attendances()
    {
        return $this->hasMany(Attendance::class);
    }
    public function user()
    {
        return $this->hasOne(User::class, 'employee_id');
    }
    public function scopeActive($query)
    {
        return $query->whereHas('user', function ($q) {
            $q->where('account_status', 'active');
        });
    }
}
