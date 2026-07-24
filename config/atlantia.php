<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Super Administrador
    |--------------------------------------------------------------------------
    |
    | Rol reservado para activaciones de plataforma, soporte de emergencia y
    | funciones escalables que no deben exponerse al administrador operativo.
    |
    */

    'super_admin' => [
        'enabled' => (bool) env('ATLANTIA_SUPER_ADMIN_ENABLED', true),
        'email' => env('ATLANTIA_SUPER_ADMIN_EMAIL'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Funciones escalables
    |--------------------------------------------------------------------------
    |
    | Estas banderas dejan preparada la activacion controlada desde super admin.
    |
    */

    'features' => [
        'multi_region' => (bool) env('ATLANTIA_FEATURE_MULTI_REGION', false),
        'advanced_ml_automation' => (bool) env('ATLANTIA_FEATURE_ADVANCED_ML_AUTOMATION', false),
        'vendor_subscription_billing' => (bool) env('ATLANTIA_FEATURE_VENDOR_SUBSCRIPTION_BILLING', false),
        'external_courier_network' => (bool) env('ATLANTIA_FEATURE_EXTERNAL_COURIER_NETWORK', false),
        'fel_contingency_mode' => (bool) env('ATLANTIA_FEATURE_FEL_CONTINGENCY_MODE', true),
    ],

    'couriers' => [
        'minimum_withdrawal' => (float) env('ATLANTIA_COURIER_MINIMUM_WITHDRAWAL', 25),
    ],

    'marketplace' => [
        'municipios' => array_values(array_filter(array_map(
            static fn (string $municipio): string => trim($municipio),
            explode(',', (string) env('ATLANTIA_MARKETPLACE_MUNICIPIOS', 'Puerto Barrios,Santo Tomas'))
        ))),
        'default_municipio' => env('ATLANTIA_MARKETPLACE_DEFAULT_MUNICIPIO', 'Puerto Barrios'),
    ],

    'auth' => [
        'enforce_2fa_roles' => array_values(array_filter(array_map(
            static fn (string $role): string => trim($role),
            explode(',', (string) env(
                'ATLANTIA_ENFORCE_2FA_ROLES',
                'super_admin,admin,empleado,soporte,contabilidad_finanzas,supervisor_logistica'
            ))
        ))),
        'passport' => [
            'access_token_ttl_minutes' => (int) env('ATLANTIA_ACCESS_TOKEN_TTL_MINUTES', 10080),
            'refresh_token_ttl_days' => (int) env('ATLANTIA_REFRESH_TOKEN_TTL_DAYS', 30),
            'personal_access_token_ttl_days' => (int) env('ATLANTIA_PERSONAL_ACCESS_TOKEN_TTL_DAYS', 30),
        ],
    ],

    'support' => [
        'phone' => env('ATLANTIA_SUPPORT_PHONE', '+502 5555-0101'),
        'emergency_phone' => env('ATLANTIA_SUPPORT_EMERGENCY_PHONE', '+502 5555-0191'),
        'whatsapp' => env('ATLANTIA_SUPPORT_WHATSAPP'),
        'average_response_minutes' => (int) env('ATLANTIA_SUPPORT_AVERAGE_RESPONSE_MINUTES', 2),
        'hours' => env('ATLANTIA_SUPPORT_HOURS', 'Lunes a domingo, 6:00 a. m. - 10:00 p. m.'),
        'channels' => array_values(array_filter(array_map(
            static fn (string $channel): string => trim($channel),
            explode(',', (string) env('ATLANTIA_SUPPORT_CHANNELS', 'app,whatsapp,phone,emergency'))
        ))),
        'sla_minutes' => [
            'low' => (int) env('ATLANTIA_SUPPORT_SLA_LOW_MINUTES', 30),
            'normal' => (int) env('ATLANTIA_SUPPORT_SLA_NORMAL_MINUTES', 10),
            'high' => (int) env('ATLANTIA_SUPPORT_SLA_HIGH_MINUTES', 5),
            'critical' => (int) env('ATLANTIA_SUPPORT_SLA_CRITICAL_MINUTES', 2),
        ],
    ],

    'payments' => [
        'cash' => [
            'max_change_bill' => (float) env('ATLANTIA_CASH_MAX_CHANGE_BILL', 500),
        ],
        'pos' => [
            'enabled' => (bool) env('ATLANTIA_POS_ENABLED', true),
            'provider' => env('ATLANTIA_POS_PROVIDER', 'POS bancario'),
            'support_phone' => env('ATLANTIA_POS_SUPPORT_PHONE'),
        ],
        'transfer' => [
            'bank_name' => env('ATLANTIA_TRANSFER_BANK_NAME'),
            'account_name' => env('ATLANTIA_TRANSFER_ACCOUNT_NAME'),
            'account_number' => env('ATLANTIA_TRANSFER_ACCOUNT_NUMBER'),
            'instructions' => env(
                'ATLANTIA_TRANSFER_INSTRUCTIONS',
                'Realiza la transferencia cuando llegue el repartidor y comparte el comprobante.'
            ),
        ],
    ],

    'backups' => [
        'directory' => env('ATLANTIA_BACKUP_DIR', storage_path('app/backups')),
        'retention_days' => (int) env('ATLANTIA_BACKUP_RETENTION_DAYS', 14),
        's3_prefix' => env('ATLANTIA_BACKUP_S3_PREFIX', 'backups/marketplace'),
    ],

];
