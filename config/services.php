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
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'mapbox' => [
        'token' => env('ATLANTIA_MAPBOX_TOKEN', env('MAPBOX_TOKEN')),
        'base_url' => env('MAPBOX_BASE_URL', 'https://api.mapbox.com'),
    ],

    'infile' => [
        'base_url' => env('INFILE_BASE_URL'),
        'username' => env('INFILE_USERNAME'),
        'password' => env('INFILE_PASSWORD'),
        'webhook_secret' => env('INFILE_WEBHOOK_SECRET'),
    ],

    'payment_gateway' => [
        'base_url' => env('PAYMENT_GATEWAY_BASE_URL'),
        'merchant_id' => env('PAYMENT_GATEWAY_MERCHANT_ID'),
        'secret' => env('PAYMENT_GATEWAY_SECRET'),
        'webhook_secret' => env('PAYMENT_GATEWAY_WEBHOOK_SECRET'),
    ],

    'ml' => [
        'base_url' => env('ML_SERVICE_URL', 'http://ml-api:8000'),
        'service_token' => env('ML_SERVICE_TOKEN'),
        'webhook_secret' => env('ML_WEBHOOK_SECRET'),
        'timeout_seconds' => (int) env('ML_TIMEOUT_SECONDS', 10),
    ],

    'courier' => [
        'webhook_secret' => env('COURIER_WEBHOOK_SECRET'),
    ],

    'recaptcha' => [
        'site_key' => env('RECAPTCHA_SITE_KEY'),
        'secret_key' => env('RECAPTCHA_SECRET_KEY'),
        'minimum_score' => (float) env('RECAPTCHA_MINIMUM_SCORE', 0.6),
    ],

];
