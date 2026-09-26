<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmployeeLetter extends Model
{
    // Generated letters contain salary/personal details — kept on the private "local" disk,
    // same as EmployeeDocument, and served only through EmployeeLetterController::download.
    public const DISK = 'local';

    protected $fillable = [
        'employee_id',
        'letter_template_id',
        'type',
        'title',
        'file_path',
        'generated_by',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function template()
    {
        return $this->belongsTo(LetterTemplate::class, 'letter_template_id');
    }

    public function generatedBy()
    {
        return $this->belongsTo(User::class, 'generated_by');
    }
}
