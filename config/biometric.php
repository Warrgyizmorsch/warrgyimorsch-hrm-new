<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Biometric / ZKT Attendance Sync
    |--------------------------------------------------------------------------
    |
    | Configure the Python interpreter and script used to pull punches from
    | the biometric device. Override these in .env per server/environment.
    |
    */

    'python_path' => env('BIOMETRIC_PYTHON_PATH', 'python'),

    'script_path' => env(
        'BIOMETRIC_SCRIPT_PATH',
        'C:\\xampp new\\htdocs\\python\\zk_attendance.py'
    ),

    'timeout' => (int) env('BIOMETRIC_TIMEOUT', 120),

];
