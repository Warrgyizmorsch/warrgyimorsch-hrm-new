<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Sop extends Model
{
    protected $fillable = [
        'department_id',
        'role',
        'title',
        'content',
        'version',
        'status',
    ];

    protected $casts = [
        'status' => 'boolean',
    ];

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function acknowledgements()
    {
        return $this->hasMany(SopAcknowledgement::class);
    }

    public function versions()
    {
        return $this->hasMany(SopVersion::class)->orderByDesc('version');
    }

    public function scopeActive($query)
    {
        return $query->where('status', true);
    }

    // Applies to an employee if it's company-wide/role-open, or matches their department/role.
    public function scopeApplicableTo($query, Employee $employee)
    {
        $role = str_replace(' ', '_', strtolower(trim((string) ($employee->role ?? ''))));

        return $query->where(function ($q) use ($employee) {
            $q->whereNull('department_id')->orWhere('department_id', $employee->department_id);
        })->where(function ($q) use ($role) {
            $q->whereNull('role')->orWhereRaw("LOWER(REPLACE(role, ' ', '_')) = ?", [$role]);
        });
    }

    public function isAcknowledgedBy(int $userId): bool
    {
        return $this->acknowledgements->contains(fn ($ack) => $ack->user_id === $userId && $ack->version === $this->version);
    }
}
