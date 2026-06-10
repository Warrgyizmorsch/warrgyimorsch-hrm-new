<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TechnicalReviewDetail extends Model
{
    protected $fillable = [
        'review_id',
        'criteria_name',
        'criteria_point',
        'self_review',
        'author_review',
        'admin_review',
    ];

    public function review()
    {
        return $this->belongsTo(
            TechnicalReview::class,
            'review_id'
        );
    }
}