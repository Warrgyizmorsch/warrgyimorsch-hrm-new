<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class KraAssignmentItem extends Model
{
    protected $fillable = [
        'kra_assignment_id',
        'technical_review_evaluation_id',
        'criteria_name',
        'max_point',
    ];

    public function assignment()
    {
        return $this->belongsTo(KraAssignment::class, 'kra_assignment_id');
    }

    public function criteria()
    {
        return $this->belongsTo(TechnicalReviewEvaluation::class, 'technical_review_evaluation_id');
    }
}
