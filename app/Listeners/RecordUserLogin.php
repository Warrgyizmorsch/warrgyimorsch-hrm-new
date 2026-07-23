<?php

namespace App\Listeners;

use App\Models\LoginActivity;
use App\Services\GeoLocationService;
use Illuminate\Auth\Events\Login;

class RecordUserLogin
{
    public function __construct(private GeoLocationService $geoLocationService)
    {
    }

    public function handle(Login $event): void
    {
        $request = request();
        $ip = $request->ip();
        $location = $this->geoLocationService->resolve($ip);
        $now = now();

        $activity = LoginActivity::create([
            'user_id' => $event->user->id,
            'employee_id' => $event->user->employee_id,
            'session_id' => $request->session()->getId(),
            'ip_address' => $ip,
            'user_agent' => $request->userAgent(),
            'city' => $location['city'],
            'region' => $location['region'],
            'country' => $location['country'],
            'login_at' => $now,
            'last_seen_at' => $now,
            'is_active' => true,
        ]);

        $request->session()->put('login_activity_id', $activity->id);
    }
}
