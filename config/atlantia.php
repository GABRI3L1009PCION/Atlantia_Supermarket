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

];
