<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

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

    /** False until `php artisan migrate` has created the table — leave screens keep working meanwhile. */
    public static function available(): bool
    {
        static $available = null;

        return $available ??= Schema::hasTable('leave_application_logs');
    }

    /** Append one step to an application's timeline. */
    public static function record(LeaveApplication $leave, string $action, string $level, ?string $from, ?string $to, ?string $remarks = null, ?User $actor = null): ?self
    {
        if (!self::available()) {
            return null;
        }

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
