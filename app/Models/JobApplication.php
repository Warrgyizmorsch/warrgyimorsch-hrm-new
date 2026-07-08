<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class JobApplication extends Model
{
    protected $table = 'job_applications';

    protected $fillable = [
        'candidate_id',
        'job_requirement_id',
        'name',
        'email',
        'phone',
        'department_id',
        'designation',
        'qualification',
        'experience',
        'interview_date',
        'interview_time',
        'interviewer_id',
        'interview_details',
        'status',
        'resume',
        'remarks',
        'hired_employee_id',
    ];

    /**
     * Recruitment pipeline stages — single source of truth for status everywhere
     * (list filters/counters, status dropdown, badge colors).
     */
    public const STAGES = [
        'applied' => 'Applied',
        'shortlisted' => 'Shortlisted',
        'interview_scheduled' => 'Interview Scheduled',
        'interviewed' => 'Interviewed',
        'offered' => 'Offered',
        'hired' => 'Hired',
        'rejected' => 'Rejected',
    ];

    public static function stageLabel(?string $stage): string
    {
        return self::STAGES[$stage] ?? ucfirst(str_replace('_', ' ', (string) $stage));
    }

    public function candidate()
    {
        return $this->belongsTo(Candidate::class);
    }

    public function requirement()
    {
        return $this->belongsTo(JobRequirement::class, 'job_requirement_id');
    }

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function interviewer()
    {
        return $this->belongsTo(Employee::class, 'interviewer_id');
    }

    public function hiredEmployee()
    {
        return $this->belongsTo(Employee::class, 'hired_employee_id');
    }
}
