<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TechnicalReviewEvaluation extends Model
{
    protected $fillable = [
        'department',
        'criteria_name',
        'max_point',
        'sort_order',
        'status',
    ];
}
