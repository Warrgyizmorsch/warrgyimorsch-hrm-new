<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LeaveApplicationLog extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = [
        'leave_application_id',
        'action',
        'level',
        'from_status',
        'to_status',
        'remarks',
        'actor_user_id',
        'actor_name',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    public function leaveApplication()
    {
        return $this->belongsTo(LeaveApplication::class);
    }

    /** Append one step to an application's timeline. */
    public static function record(LeaveApplication $leave, string $action, string $level, ?string $from, ?string $to, ?string $remarks = null, ?User $actor = null): self
    {
        $actor ??= auth()->user();

        return self::create([
            'leave_application_id' => $leave->id,
            'action' => $action,
            'level' => $level,
            'from_status' => $from,
            'to_status' => $to,
            'remarks' => $remarks,
            'actor_user_id' => $actor?->id,
            'actor_name' => $actor?->name,
        ]);
    }
}
