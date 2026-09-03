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

    'webhook_url' => env('BIOMETRIC_WEBHOOK_URL', 'https://rs9n.itmsu.com/api/attendance'),

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
    | The rs9n device assigns its own internal enrollment ID to every person
    | it enrolls, unrelated to employees.employee_code. This used to be
    | hardcoded here; it now lives on employees.rs9n_device_id (set via the
    | "Biometric Device Mapping" field on the employee Add/Edit form) so it
    | can be maintained through the UI instead of a code deploy — see
    | ZKTController::syncAttendance() and App\Imports\AttendanceImport.
    |
    | The 'zk' machine is unaffected — its codes already equal employee_code.
    |
    */

];
