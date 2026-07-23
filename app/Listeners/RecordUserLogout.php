<?php

namespace App\Listeners;

use App\Models\LoginActivity;
use Illuminate\Auth\Events\Logout;

class RecordUserLogout
{
    public function handle(Logout $event): void
    {
        $activityId = request()->session()->get('login_activity_id');

        if (!$activityId) {
            return;
        }

        $activity = LoginActivity::find($activityId);

        if ($activity && $activity->is_active) {
            $activity->closeSession();
        }
    }
}
