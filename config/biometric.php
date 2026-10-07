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
    | Machine labels / employee ID mapping
    |--------------------------------------------------------------------------
    |
    | Every machine numbers its own users, so the same number can be different
    | people on different machines. Each employee's ID per machine is stored in
    | biometric_enrollments (the "Biometric IDs" list on the employee form) and
    | every punch is matched by (machine, device user ID) — never by
    | employee_code, which is a pure HR/payroll number.
    |
    | To add a machine: add its key to BIOMETRIC_MACHINES (so it's synced) and
    | a label here. It then appears in the employee form's machine dropdown.
    |
    */

    'machine_labels' => [
        'zk' => 'zk (old machine)',
        'rs9n' => 'rs9n (new machine)',
    ],

];
