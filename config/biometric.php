<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Biometric / ZKT Attendance Sync
    |--------------------------------------------------------------------------
    |
    | Punches are pulled from the unified Attendance API
    | (https://rs9n.gjnwm.dpdns.org/docs), which fronts both biometric
    | machines on our behalf. The API is server-side pre-configured per
    | machine, so we only pass a `machine` key ('zk' or 'rs9n') and a day
    | range — no host/port/password per request. Override these in .env
    | per server/environment.
    |
    */

    'webhook_url' => env('BIOMETRIC_WEBHOOK_URL', 'https://rs9n.gjnwm.dpdns.org/api/attendance'),

    // Comma-separated list of machine keys to sync, e.g. "zk,rs9n".
    'machines' => array_filter(array_map('trim', explode(',', env('BIOMETRIC_MACHINES', 'zk,rs9n')))),

    'sync_days' => (int) env('BIOMETRIC_SYNC_DAYS', 2),

    'timeout' => (int) env('BIOMETRIC_TIMEOUT', 60),

    'api_secret_token' => env('API_SECRET_TOKEN'),

    /*
    |--------------------------------------------------------------------------
    | 'rs9n' machine employee ID map
    |--------------------------------------------------------------------------
    |
    | The 28-member group moved to the 'rs9n' device were re-enrolled there
    | with the device's own sequential IDs (1-28), unrelated to their real
    | employees.employee_code. Verified against mobile_number on 2026-08-25.
    |
    | The 'zk' machine is unaffected — its codes already equal employee_code.
    |
    */
    'rs9n_employee_map' => [
        1 => 34, 2 => 46, 3 => 11, 4 => 55, 5 => 5, 6 => 37, 7 => 6, 8 => 57,
        9 => 49, 10 => 9, 11 => 40, 12 => 45, 13 => 13, 14 => 54, 15 => 32,
        16 => 8, 17 => 2, 18 => 39, 19 => 15, 20 => 36, 21 => 17, 22 => 50,
        23 => 59, 24 => 56, 25 => 25, 26 => 21, 27 => 3, 28 => 16,
    ],

];
