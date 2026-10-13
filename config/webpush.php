<?php

return [
    /*
    | Browser push notifications (work even when the HRM tab is closed).
    | Generate keys once with:  php artisan webpush:vapid
    | and paste the printed lines into .env.
    */
    'enabled' => env('WEBPUSH_ENABLED', true),

    // VAPID key pair, base64url (public = 65-byte uncompressed P-256 point, private = 32 bytes).
    'public_key' => env('VAPID_PUBLIC_KEY'),
    'private_key' => env('VAPID_PRIVATE_KEY'),

    // Contact the push services can reach if something goes wrong (mailto: or https: URL).
    'subject' => env('VAPID_SUBJECT', 'mailto:' . env('MAIL_FROM_ADDRESS', 'admin@example.com')),

    // How long a push service keeps an undelivered push (seconds) — e.g. while a laptop is asleep.
    'ttl' => (int) env('WEBPUSH_TTL', 86400),

    'timeout' => (int) env('WEBPUSH_TIMEOUT', 10),
];
