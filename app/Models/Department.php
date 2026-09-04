<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Department extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'short_name', 'is_active'];

    // Employees whose primary department is this one (via employees.department_id).
    public function employees()
    {
        return $this->hasMany(Employee::class, 'department_id');
    }

    // Employees who additionally lead this department as Team Leader (multi-department leads).
    public function ledByEmployees()
    {
        return $this->belongsToMany(Employee::class, 'department_employee_led');
    }

    // Projects tagged with this department.
    public function projects()
    {
        return $this->belongsToMany(Project::class, 'department_project');
    }

    /**
     * The one remaining place the "Business Development" name literal lives — every
     * consumer that special-cases that department (salary structure, payroll display)
     * compares against this resolved ID instead of hard-coding the name string.
     */
    public static function businessDevelopmentId(): ?int
    {
        static $id = false;

        if ($id === false) {
            $id = static::where('name', 'Business Development')->value('id');
        }

        return $id;
    }
}
