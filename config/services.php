<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
    ],

    'cloudflare' => [
        'tunnel_token' => env('TUNNEL_TOKEN'),
    ],

    'google' => [
        'client_id' => env('GOOGLE_CLIENT_ID'),
        'client_secret' => env('GOOGLE_CLIENT_SECRET'),
        'redirect' => env('GOOGLE_REDIRECT_URI'),
        'admin_emails' => env('GOOGLE_ADMIN_EMAILS'),
        'allowed_domains' => env('GOOGLE_ALLOWED_DOMAINS'),
    ],

    'helcim' => [
        'api_token' => env('HELCIM_API_TOKEN'),
        'api_url' => env('HELCIM_API_URL', 'https://api.helcim.com/v2'),
        'account_id' => env('HELCIM_ACCOUNT_ID'),
        'webhook_secret' => env('HELCIM_WEBHOOK_SECRET'),
        'timeout' => env('HELCIM_TIMEOUT', 30),
        
        // Convenience fee settings
        'convenience_fee_enabled' => env('HELCIM_CONVENIENCE_FEE_ENABLED', false),
        'convenience_fee_percent' => env('HELCIM_CONVENIENCE_FEE_PERCENT', 2.9),
        'convenience_fee_flat' => env('HELCIM_CONVENIENCE_FEE_FLAT', 0.30),
    ],

    /*
    |--------------------------------------------------------------------------
    | Rate Limiting & Circuit Breaker
    |--------------------------------------------------------------------------
    */

    'rate_limiter' => [
        'driver' => env('RATE_LIMITER_DRIVER', 'redis'),
        'google_api' => [
            'max_attempts' => env('GOOGLE_API_RATE_LIMIT', 100),
            'decay_seconds' => 3600, // 1 hour
        ],
        'action1_api' => [
            'max_attempts' => env('ACTION1_API_RATE_LIMIT', 60),
            'decay_seconds' => 3600,
        ],
    ],

    'circuit_breaker' => [
        'threshold' => env('CIRCUIT_BREAKER_THRESHOLD', 5),
        'timeout' => env('CIRCUIT_BREAKER_TIMEOUT', 60), // seconds
    ],

];
