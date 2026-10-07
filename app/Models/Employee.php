<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Employee extends Model
{
    // A deleted former employee is a soft-deleted shell kept only for payroll history
    // (see EmployeePurgeService) — hidden from every normal Employee query.
    use SoftDeletes;

    protected $fillable = [
        'name',
        'employee_code',
        'email',
        'mobile_number',
        'role',
        'department_id',
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
        'dearness_allowance',
        'hra',
        'conveyance_allowance',
        'medical_allowance',
        'other_allowance',
        'working_mode',
        'employment_status',
        'profile_status',
    ];

    /**
     * Self-service onboarding (see EmployeeProfileRequest). NULL = fully active. While in one
     * of these states the employee can only use the "Complete your profile" page.
     */
    public const PROFILE_PENDING = 'pending_profile';   // HR created the account, employee hasn't submitted
    public const PROFILE_SUBMITTED = 'submitted';       // waiting for HR approval
    public const PROFILE_RETURNED = 'returned';         // HR sent it back with a note

    public function isProfileLocked(): bool
    {
        return in_array($this->profile_status, [self::PROFILE_PENDING, self::PROFILE_SUBMITTED, self::PROFILE_RETURNED], true);
    }

    public function profileRequests()
    {
        return $this->hasMany(EmployeeProfileRequest::class)->latest();
    }

    /** The request currently open (waiting for HR, or sent back to the employee), if any. */
    public function openProfileRequest(): ?EmployeeProfileRequest
    {
        return $this->profileRequests()
            ->whereIn('status', [EmployeeProfileRequest::STATUS_PENDING, EmployeeProfileRequest::STATUS_RETURNED])
            ->first();
    }

    public const EMPLOYMENT_STATUSES = [
        'working' => 'Working',
        'probation' => 'Probation',
        'notice_period' => 'Notice Period',
        'pip' => 'PIP',
        'internship' => 'Internship',
    ];

    // Pseudo-status for employees with under one month of service; not stored,
    // derived from date_of_joining, but has its own allotment rule.
    public const NEW_JOINER = 'new_joiner';

    public const LEAVE_ELIGIBILITY_SETTING = 'leave_allotment.eligible_statuses';
    public const DEFAULT_LEAVE_ELIGIBLE_STATUSES = ['working', 'pip'];

    public static function leaveEligibleStatuses(): array
    {
        return (array) AppSetting::getValue(self::LEAVE_ELIGIBILITY_SETTING, self::DEFAULT_LEAVE_ELIGIBLE_STATUSES);
    }

    public function getEmploymentStatusLabelAttribute(): string
    {
        return self::EMPLOYMENT_STATUSES[$this->employment_status ?: 'working'] ?? 'Working';
    }

    /** This employee's user ID on each biometric machine (see BiometricEnrollment). */
    public function biometricEnrollments()
    {
        return $this->hasMany(BiometricEnrollment::class)->orderBy('machine');
    }

    // Department IDs this employee has visibility/edit rights over as Team Leader
    // (their own department plus any additionally assigned ones)
    public function ledDepartmentIds(): array
    {
        return array_values(array_unique(array_filter(
            array_merge([$this->department_id], $this->ledDepartmentRefs->pluck('id')->all())
        )));
    }

    public function departmentRef()
    {
        return $this->belongsTo(Department::class, 'department_id');
    }

    // Departments this employee additionally leads as Team Leader, on top of their own.
    public function ledDepartmentRefs()
    {
        return $this->belongsToMany(Department::class, 'department_employee_led');
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
    public function documents()
    {
        return $this->hasMany(EmployeeDocument::class);
    }
    public function letters()
    {
        return $this->hasMany(EmployeeLetter::class);
    }
    public function kraAssignments()
    {
        return $this->hasMany(KraAssignment::class);
    }
    public function kpiAssignments()
    {
        return $this->hasMany(KpiAssignment::class);
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
