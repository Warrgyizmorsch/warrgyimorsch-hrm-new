<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class JobRequirement extends Model
{
    use HasFactory;

    protected $fillable = [
        'role_id',
        'department_id',
        'created_by',
        'priority',
        'date',
        'candidate_type',
        'minimum_experience',
        'positions_count',
        'skills',
        'status',
    ];

    protected $casts = [
        'skills' => 'array',
    ];

    public function designation()
    {
        return $this->belongsTo(Designation::class, 'role_id');
    }

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function creator()
    {
        return $this->belongsTo(Employee::class, 'created_by');
    }

    public function applications()
    {
        return $this->hasMany(JobApplication::class, 'job_requirement_id');
    }

    public function hiredApplicationsCount(): int
    {
        return $this->applications()->where('status', 'hired')->count();
    }
}
