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

    /*
     * Mapas con OpenStreetMap. Los valores por defecto son los servidores publicos de prueba:
     * en produccion usa un proveedor de teselas OSM y un servidor OSRM propio.
     */
    'mapas' => [
        'tiles_url' => env('MAP_TILES_URL', 'https://tile.openstreetmap.org/{z}/{x}/{y}.png'),
        'tiles_attribution' => env('MAP_TILES_ATTRIBUTION', '&copy; OpenStreetMap'),
        'routing_url' => rtrim((string) env('MAP_ROUTING_URL', 'https://router.project-osrm.org'), '/'),
    ],

    /*
     * Notificaciones push (Web Push). Genera las claves una vez con: php artisan push:claves
     */
    'webpush' => [
        'public_key' => env('WEBPUSH_PUBLIC_KEY'),
        'private_key' => env('WEBPUSH_PRIVATE_KEY'),
        'subject' => env('WEBPUSH_SUBJECT', 'mailto:soporte@atlantia.store'),
    ],

    /*
     * Avisos por WhatsApp. Driver "log" solo los escribe en el log; "meta" usa la
     * WhatsApp Cloud API con plantillas aprobadas en Meta Business; "null" los apaga.
     */
    'whatsapp' => [
        'driver' => env('WHATSAPP_DRIVER', 'null'),
        'token' => env('WHATSAPP_TOKEN'),
        'phone_number_id' => env('WHATSAPP_PHONE_NUMBER_ID'),
        'api_version' => env('WHATSAPP_API_VERSION', 'v21.0'),
        'idioma' => env('WHATSAPP_IDIOMA', 'es'),
        'plantillas' => [
            'pedido_confirmado' => env('WHATSAPP_PLANTILLA_CONFIRMADO', 'pedido_confirmado'),
            'pedido_en_camino' => env('WHATSAPP_PLANTILLA_EN_CAMINO', 'pedido_en_camino'),
            'repartidor_llego' => env('WHATSAPP_PLANTILLA_LLEGO', 'repartidor_llego'),
            'pedido_entregado' => env('WHATSAPP_PLANTILLA_ENTREGADO', 'pedido_entregado'),
            'codigo_acceso' => env('WHATSAPP_PLANTILLA_CODIGO', 'codigo_acceso'),
            'carrito_abandonado' => env('WHATSAPP_PLANTILLA_CARRITO', 'carrito_abandonado'),
            'pedido_recurrente' => env('WHATSAPP_PLANTILLA_RECURRENTE', 'pedido_recurrente'),
        ],
    ],

    /* "Continuar con Google" (Laravel Socialite). El boton se oculta sin credenciales. */
    'google' => [
        'client_id' => env('GOOGLE_CLIENT_ID'),
        'client_secret' => env('GOOGLE_CLIENT_SECRET'),
        'redirect' => env('GOOGLE_REDIRECT_URI', '/entrar/google/callback'),
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

    'recurrente' => [
        'base_url' => rtrim((string) env('RECURRENTE_BASE_URL', 'https://app.recurrente.com/api'), '/'),
        'secret_key' => env('RECURRENTE_SECRET_KEY'),
        'webhook_secret' => env('RECURRENTE_WEBHOOK_SECRET'),
        'currency' => env('RECURRENTE_CURRENCY', 'GTQ'),
        'checkout_expires_minutes' => (int) env('RECURRENTE_CHECKOUT_EXPIRES_MINUTES', 30),
        /* Pago con tarjeta dentro de Atlantia (iframe). Requiere https o localhost. */
        'embebido' => (bool) env('RECURRENTE_EMBEDDED', true),
        'timeout_seconds' => (int) env('RECURRENTE_TIMEOUT_SECONDS', 20),
        'checkout_domains' => array_values(array_filter(array_map(
            static fn (string $domain): string => trim($domain),
            explode(',', (string) env('RECURRENTE_CHECKOUT_DOMAINS', 'https://app.recurrente.com'))
        ))),
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
