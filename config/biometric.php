<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Biometric / ZKT Attendance Sync
    |--------------------------------------------------------------------------
    |
    | Punches are pulled from the biometric device via the ZKTeco Attendance
    | webhook API (https://webhook.gjnwm.dpdns.org/docs), which reaches the
    | device on the LAN on our behalf. Override these in .env per
    | server/environment.
    |
    */

    'webhook_url' => env('BIOMETRIC_WEBHOOK_URL', 'https://webhook.gjnwm.dpdns.org/api/attendance'),

    'device_host' => env('BIOMETRIC_DEVICE_HOST', '192.168.29.150'),

    'device_port' => (int) env('BIOMETRIC_DEVICE_PORT', 4370),

    'device_password' => (int) env('BIOMETRIC_DEVICE_PASSWORD', 0),

    'sync_days' => (int) env('BIOMETRIC_SYNC_DAYS', 1),

    'timeout' => (int) env('BIOMETRIC_TIMEOUT', 60),

];
