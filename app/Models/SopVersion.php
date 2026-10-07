<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SopVersion extends Model
{
    protected $fillable = [
        'sop_id',
        'version',
        'title',
        'content',
        'attachment_path',
        'attachment_name',
        'changed_by',
    ];

    public function sop()
    {
        return $this->belongsTo(Sop::class);
    }

    public function changedBy()
    {
        return $this->belongsTo(User::class, 'changed_by');
    }
}
