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

    'google_maps' => [
        'api_key' => env('GOOGLE_MAPS_API_KEY'),
        'map_id' => env('GOOGLE_MAPS_MAP_ID'),
        'default_lat' => (float) env('GOOGLE_MAPS_DEFAULT_LAT', 15.7309),
        'default_lng' => (float) env('GOOGLE_MAPS_DEFAULT_LNG', -88.5944),
        'default_zoom' => (int) env('GOOGLE_MAPS_DEFAULT_ZOOM', 13),
    ],

    'infile' => [
        'base_url' => env('INFILE_BASE_URL'),
        'username' => env('INFILE_USERNAME'),
        'password' => env('INFILE_PASSWORD'),
        'webhook_secret' => env('INFILE_WEBHOOK_SECRET'),
        'mock' => (bool) env(
            'INFILE_MOCK',
            in_array(env('APP_ENV', 'production'), ['local', 'testing'], true)
        ),
    ],

    'payment_gateway' => [
        'base_url' => env('PAYMENT_GATEWAY_BASE_URL'),
        'merchant_id' => env('PAYMENT_GATEWAY_MERCHANT_ID'),
        'secret' => env('PAYMENT_GATEWAY_SECRET'),
        'webhook_secret' => env('PAYMENT_GATEWAY_WEBHOOK_SECRET'),
    ],

    'stripe' => [
        'publishable_key' => env('STRIPE_PUBLISHABLE_KEY'),
        'secret_key' => env('STRIPE_SECRET_KEY'),
        'webhook_secret' => env('STRIPE_WEBHOOK_SECRET'),
        'currency' => env('STRIPE_CURRENCY', 'gtq'),
    ],

    'ml' => [
        'base_url' => env('ML_SERVICE_URL', 'http://ml-api:8000/api/v1'),
        'service_token' => env('ML_SERVICE_TOKEN'),
        'webhook_secret' => env('ML_WEBHOOK_SECRET'),
        'timeout_seconds' => (int) env('ML_TIMEOUT_SECONDS', 10),
    ],

    'courier' => [
        'webhook_secret' => env('COURIER_WEBHOOK_SECRET'),
    ],

    'firebase' => [
        'enabled' => (bool) env('FIREBASE_ENABLED', false),
        'project_id' => env('FIREBASE_PROJECT_ID'),
        'service_account_email' => env('FIREBASE_SERVICE_ACCOUNT_EMAIL'),
        'private_key' => env('FIREBASE_PRIVATE_KEY'),
        'token_uri' => env('FIREBASE_TOKEN_URI', 'https://oauth2.googleapis.com/token'),
    ],

    'recaptcha' => [
        'site_key' => env('RECAPTCHA_SITE_KEY'),
        'secret_key' => env('RECAPTCHA_SECRET_KEY'),
        'minimum_score' => (float) env('RECAPTCHA_MINIMUM_SCORE', 0.6),
    ],

];
