<?php

return [
    /*
    | Leave e-mail notifications (two-level approval).
    | Set LEAVE_MAIL_ENABLED=false in .env to switch every leave e-mail off.
    */
    'mail_enabled' => env('LEAVE_MAIL_ENABLED', true),

    // Shown in the e-mail header and footer.
    'mail_brand' => env('LEAVE_MAIL_BRAND', 'Warrgyizmorsch HRM'),

    /*
    | Level 2 approvers who receive "action required" e-mails.
    | LEAVE_MANAGER_EMAILS="manager@company.com,hr@company.com" sends to exactly these addresses.
    | Left empty, it e-mails every active user whose role is in manager_roles.
    | (A manager's own leave always goes to HR / Super Admin instead. HR is CC'd on everything.)
    */
    'manager_emails' => array_values(array_filter(array_map('trim', explode(',', (string) env('LEAVE_MANAGER_EMAILS', ''))))),
    'manager_roles' => array_values(array_filter(array_map('trim', explode(',', (string) env('LEAVE_MANAGER_ROLES', 'manager'))))),

    // HR inbox put in CC on every leave / WFH e-mail. Set LEAVE_MAIL_HR_CC= (empty) in .env to turn it off.
    'hr_cc' => env('LEAVE_MAIL_HR_CC', 'warrgyizmorsch.hr@gmail.com'),
];
