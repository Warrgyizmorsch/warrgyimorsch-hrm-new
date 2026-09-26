<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LetterTemplate extends Model
{
    // type => label
    public const TYPES = [
        'offer_letter' => 'Offer Letter',
        'appointment_letter' => 'Appointment Letter',
        'appraisal_letter' => 'Appraisal Letter',
        'experience_letter' => 'Experience Letter',
        'relieving_letter' => 'Relieving Letter',
        'certificate' => 'Certificate',
    ];

    // placeholder => description shown to the admin while editing a template
    public const PLACEHOLDERS = [
        '{{employee_name}}' => "Employee's full name",
        '{{employee_code}}' => 'Employee code',
        '{{designation}}' => 'Designation',
        '{{department}}' => 'Department name',
        '{{date_of_joining}}' => 'Date of joining (e.g. 15 Jan 2026)',
        '{{gross_salary}}' => 'Monthly gross salary (e.g. ₹35,000.00)',
        '{{gross_salary_words}}' => 'Monthly gross salary in words',
        '{{today}}' => "Today's date (e.g. 26 Sep 2026)",
        '{{company_name}}' => 'Company name',
    ];

    protected $fillable = [
        'type',
        'title',
        'content',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function getLabelAttribute(): string
    {
        return self::TYPES[$this->type] ?? ucwords(str_replace('_', ' ', $this->type));
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function letters()
    {
        return $this->hasMany(EmployeeLetter::class);
    }
}
