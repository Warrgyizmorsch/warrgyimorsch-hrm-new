<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmployeeReview extends Model
{
    protected $fillable=[
        'employee_id',
        'month',
        'period',
        'self_total',
        'author_total',
        'admin_total',
        'author_note',
        'admin_note'
    ];

    public function details() {
        return $this->hasMany(EmployeeReviewDetail::class, 'review_id');
    }

    public function employee() {
        return $this->belongsTo(Employee::class, 'employee_id');
    }
}
