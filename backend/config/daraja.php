<?php

return [
    'environment' => env('DARAJA_ENVIRONMENT', 'sandbox'),
    'base_url' => rtrim(env('DARAJA_BASE_URL', 'https://sandbox.safaricom.co.ke'), '/'),
    'consumer_key' => env('DARAJA_CONSUMER_KEY'),
    'consumer_secret' => env('DARAJA_CONSUMER_SECRET'),
    'shortcode' => env('DARAJA_SHORTCODE'),
    // Buy Goods may use a store/Till identifier distinct from the business
    // shortcode used with the passkey to generate the STK password.
    'party_b' => env('DARAJA_PARTY_B', env('DARAJA_SHORTCODE')),
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
        'account_reference' => env('COFFEE_ACCOUNT_REFERENCE', 'SBATASI'),
        'description' => env('COFFEE_TRANSACTION_DESCRIPTION', 'Support'),
        'frontend_url' => rtrim(env('PORTFOLIO_FRONTEND_URL', 'http://localhost:5173'), '/'),
        'query_after_seconds' => (int) env('COFFEE_QUERY_AFTER_SECONDS', 30),
        'query_interval_seconds' => (int) env('COFFEE_QUERY_INTERVAL_SECONDS', 30),
    ],
    'organization' => [
        'initiator_name' => env('DARAJA_INITIATOR_NAME'),
        'security_credential' => env('DARAJA_SECURITY_CREDENTIAL'),
    ],
    'balance' => [
        'enabled' => filter_var(env('DARAJA_BALANCE_ENABLED', false), FILTER_VALIDATE_BOOL),
        'identifier_type' => (int) env('DARAJA_BALANCE_IDENTIFIER_TYPE', 4),
        'path' => env('DARAJA_BALANCE_PATH', '/mpesa/accountbalance/v1/query'),
        'result_url' => env('DARAJA_BALANCE_RESULT_URL'),
        'timeout_url' => env('DARAJA_BALANCE_TIMEOUT_URL'),
    ],
    'b2c' => [
        'enabled' => filter_var(env('DARAJA_B2C_ENABLED', false), FILTER_VALIDATE_BOOL),
        'shortcode' => env('DARAJA_B2C_SHORTCODE'),
        'path' => env('DARAJA_B2C_PATH', '/mpesa/b2c/v3/paymentrequest'),
        'result_url' => env('DARAJA_B2C_RESULT_URL'),
        'timeout_url' => env('DARAJA_B2C_TIMEOUT_URL'),
        'command_id' => env('DARAJA_B2C_COMMAND_ID', 'BusinessPayment'),
        'minimum' => (int) env('PAYOUT_MIN_AMOUNT', 10),
        'maximum' => env('PAYOUT_MAX_AMOUNT') !== null && env('PAYOUT_MAX_AMOUNT') !== '' ? (int) env('PAYOUT_MAX_AMOUNT') : null,
        'daily_limit' => env('PAYOUT_DAILY_LIMIT') !== null && env('PAYOUT_DAILY_LIMIT') !== '' ? (int) env('PAYOUT_DAILY_LIMIT') : null,
        'require_balance_check' => filter_var(env('PAYOUT_REQUIRE_BALANCE_CHECK', true), FILTER_VALIDATE_BOOL),
    ],
];
