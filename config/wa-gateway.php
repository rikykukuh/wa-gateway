<?php

return [
    'api_key' => env('WA_GATEWAY_API_KEY'),
    'engine_url' => env('WA_ENGINE_URL', 'http://127.0.0.1:3100'),
    'engine_secret' => env('WA_ENGINE_SECRET'),
    'request_timeout' => (int) env('WA_ENGINE_TIMEOUT', 30),
    'admin_email' => env('WA_ADMIN_EMAIL', 'admin@example.com'),
    'admin_password_hash' => env('WA_ADMIN_PASSWORD_HASH'),
];
