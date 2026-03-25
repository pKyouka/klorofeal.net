<?php

return [
    'allow_in_production' => (bool) env('DEMO_MODE_ALLOW_IN_PRODUCTION', false),
    'enabled' => (bool) env('DEMO_MODE_ENABLED', true)
        && (strtolower((string) env('APP_ENV', 'production')) !== 'production' || (bool) env('DEMO_MODE_ALLOW_IN_PRODUCTION', false)),
    'account' => [
        'name' => env('DEMO_ACCOUNT_NAME', 'Demo Klorofeal'),
        'email' => env('DEMO_ACCOUNT_EMAIL', 'demo@klorofeal.test'),
        'password' => env('DEMO_ACCOUNT_PASSWORD', 'password'),
    ],
];
