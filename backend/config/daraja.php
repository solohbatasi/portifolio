<?php

return [
    'environment' => env('DARAJA_ENVIRONMENT', 'sandbox'),
    'base_url' => rtrim(env('DARAJA_BASE_URL', 'https://sandbox.safaricom.co.ke'), '/'),
    'consumer_key' => env('DARAJA_CONSUMER_KEY'),
    'consumer_secret' => env('DARAJA_CONSUMER_SECRET'),
    'shortcode' => env('DARAJA_SHORTCODE'),
    'passkey' => env('DARAJA_PASSKEY'),
    'transaction_type' => env('DARAJA_TRANSACTION_TYPE', 'CustomerPayBillOnline'),
    'callback_url' => env('DARAJA_CALLBACK_URL'),
    'timezone' => env('APP_TIMEZONE', 'Africa/Nairobi'),
    'connect_timeout' => (int) env('DARAJA_CONNECT_TIMEOUT', 5),
    'timeout' => (int) env('DARAJA_TIMEOUT', 20),
    'coffee' => [
        'minimum' => (int) env('COFFEE_MIN_AMOUNT', 50),
        'maximum' => (int) env('COFFEE_MAX_AMOUNT', 10000),
        'presets' => array_map('intval', explode(',', env('COFFEE_PRESET_AMOUNTS', '100,250,500,1000'))),
        'account_reference' => env('COFFEE_ACCOUNT_REFERENCE', 'SOLOMON-PORTFOLIO'),
        'description' => env('COFFEE_TRANSACTION_DESCRIPTION', 'Support Solomon Batasi'),
        'frontend_url' => rtrim(env('PORTFOLIO_FRONTEND_URL', 'http://localhost:5173'), '/'),
        'query_after_seconds' => (int) env('COFFEE_QUERY_AFTER_SECONDS', 30),
        'query_interval_seconds' => (int) env('COFFEE_QUERY_INTERVAL_SECONDS', 30),
    ],
];
