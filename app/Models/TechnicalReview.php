<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TechnicalReview extends Model
{
    protected $fillable = [
        'employee_id',
        'month',
        'self_total',
        'author_total',
        'admin_total',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function details()
    {
        return $this->hasMany(
            TechnicalReviewDetail::class,
            'review_id'
        );
    }
}