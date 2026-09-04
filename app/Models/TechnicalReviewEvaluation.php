<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TechnicalReviewEvaluation extends Model
{
    protected $fillable = [
        'department_id',
        'criteria_name',
        'max_point',
        'sort_order',
        'status',
    ];

    public function department()
    {
        return $this->belongsTo(Department::class);
    }
}
