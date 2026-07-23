<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class LoginActivity extends Model
{
    protected $fillable = [
        'user_id',
        'employee_id',
        'session_id',
        'ip_address',
        'user_agent',
        'city',
        'region',
        'country',
        'login_at',
        'last_seen_at',
        'logout_at',
        'duration_seconds',
        'is_active',
    ];

    protected $casts = [
        'login_at' => 'datetime',
        'last_seen_at' => 'datetime',
        'logout_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function touchHeartbeat(): void
    {
        $now = now();

        $this->last_seen_at = $now;
        $this->duration_seconds = $this->login_at->diffInSeconds($now);
        $this->save();
    }

    public function closeSession(?Carbon $endedAt = null): void
    {
        $endedAt ??= now();

        // Never let logout land before the last known heartbeat.
        if ($this->last_seen_at && $endedAt->lessThan($this->last_seen_at)) {
            $endedAt = $this->last_seen_at->copy();
        }

        $this->logout_at = $endedAt;
        $this->last_seen_at = $endedAt;
        $this->duration_seconds = $this->login_at->diffInSeconds($endedAt);
        $this->is_active = false;
        $this->save();
    }

    public function getFormattedDurationAttribute(): string
    {
        $seconds = $this->duration_seconds ?? 0;

        if ($seconds <= 0) {
            return '--';
        }

        $hours = intdiv($seconds, 3600);
        $minutes = intdiv($seconds % 3600, 60);

        return $hours > 0 ? sprintf('%dh %02dm', $hours, $minutes) : sprintf('%dm', $minutes);
    }

    public function getLocationLabelAttribute(): string
    {
        $parts = array_filter([$this->city, $this->region, $this->country]);

        return $parts ? implode(', ', $parts) : 'Unknown';
    }
}
