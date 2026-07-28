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
        'additional_led_departments',
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

    public function getAdditionalLedDepartmentsAttribute($value)
    {
        if (empty($value)) {
            return [];
        }
        $decoded = json_decode($value, true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
            return $decoded;
        }
        return array_map('trim', explode(',', $value));
    }

    public function setAdditionalLedDepartmentsAttribute($value)
    {
        if (is_array($value)) {
            $this->attributes['additional_led_departments'] = json_encode(array_values(array_filter($value)));
        } elseif (is_string($value) && $value !== '') {
            $this->attributes['additional_led_departments'] = json_encode(array_map('trim', explode(',', $value)));
        } else {
            $this->attributes['additional_led_departments'] = null;
        }
    }

    // Departments this employee has visibility/edit rights over as Team Leader
    // (their own department plus any additionally assigned ones)
    public function ledDepartments(): array
    {
        return array_values(array_unique(array_filter(
            array_merge([$this->department], (array) $this->additional_led_departments)
        )));
    }

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
