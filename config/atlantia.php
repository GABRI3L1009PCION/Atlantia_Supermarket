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
                'super_admin,admin,empleado,soporte,contabilidad_finanzas,supervisor_logistica,vendedor'
            ))
        ))),
        /* Rechaza contraseñas que aparecen en filtraciones (Have I Been Pwned, k-anonimato). */
        'verificar_contrasenas_filtradas' => (bool) env('ATLANTIA_VERIFICAR_CONTRASENAS_FILTRADAS', true),
        'passport' => [
            'access_token_ttl_minutes' => (int) env('ATLANTIA_ACCESS_TOKEN_TTL_MINUTES', 10080),
            'refresh_token_ttl_days' => (int) env('ATLANTIA_REFRESH_TOKEN_TTL_DAYS', 30),
            'personal_access_token_ttl_days' => (int) env('ATLANTIA_PERSONAL_ACCESS_TOKEN_TTL_DAYS', 30),
        ],
    ],

    /*
    | Cobertura propia de Atlantia por colonia/barrio.
    | - cubiertas: se sugieren al cliente al escribir su colonia.
    | - excluidas: Atlantia no entrega ahi (se muestra un aviso amable). Los comercios
    |   emprendedores manejan sus propias zonas de entrega.
    */
    'cobertura_colonias' => [
        'Santo Tomas' => [
            'cubiertas' => [
                'Banvi I', 'Banvi II', 'Colonia 15 de Abril', 'Colonia Coviemport', 'Colonia El Manantial',
                'Colonia La Repegua', 'Colonia María Luisa I, II y III', 'Colonia Portuaria',
                'Colonia Quebrada Seca', 'Colonia San Agustín', 'Colonia San Andrés', 'Colonia San Manuel I y II',
            ],
            'excluidas' => [
                'El Bordo', 'El Mirador', 'La Bendición', 'Piedras Negras', 'Las Colinas',
                'Las Palmeras', 'Salubrista', 'San Francisco', 'El Pueblito',
            ],
        ],
        'Puerto Barrios' => [
            'cubiertas' => [
                'Barrio El Centro', 'Barrio El Estrecho', 'Barrio El Rastro', 'Barrio La Catorce', 'Barrio La Cuevita',
                'Barrio La Esperanza', 'Barrio La Trece', 'Barrio La Veinte', 'Barrio La Veintidós', 'Barrio Las Champas',
                'Barrio Loma Linda', 'Colonia Bethania', 'Colonia El Mitch', 'Colonia Ex-UFCO', 'Colonia La Amistad',
                'Colonia La Revolución', 'Colonia Las Brisas', 'Colonia Las Flores', 'Colonia Militar',
            ],
            'excluidas' => [
                'Los Tanques', 'San José', 'Las Palmeras', 'Los Ángeles',
            ],
        ],
    ],

    /*
    | Datos del titular que aparecen en Terminos y Condiciones y Politica de Privacidad.
    | Completar con los datos reales de la empresa antes de publicar.
    */
    'legal' => [
        'nombre_comercial' => env('ATLANTIA_LEGAL_NOMBRE_COMERCIAL', 'Atlantia Store'),
        'razon_social' => env('ATLANTIA_LEGAL_RAZON_SOCIAL'),
        'nit' => env('ATLANTIA_LEGAL_NIT'),
        'domicilio' => env('ATLANTIA_LEGAL_DOMICILIO', 'Puerto Barrios, Izabal, Guatemala'),
        'email' => env('ATLANTIA_LEGAL_EMAIL', env('ATLANTIA_SUPPORT_EMAIL')),
        'email_privacidad' => env('ATLANTIA_LEGAL_EMAIL_PRIVACIDAD', env('ATLANTIA_LEGAL_EMAIL', env('ATLANTIA_SUPPORT_EMAIL'))),
        'vigencia' => env('ATLANTIA_LEGAL_VIGENCIA', '2026-11-11'),
    ],

    /* Tienda propia de Atlantia (sus productos siguen las reglas de cobertura propias). */
    'vendor_propio_slug' => env('ATLANTIA_VENDOR_PROPIO_SLUG', 'atlantia-supermarket'),

    /* Programa "Invita a un amigo": saldo para ambos cuando el invitado recibe su primer pedido. */
    'referidos' => [
        'recompensa' => (float) env('ATLANTIA_REFERIDOS_RECOMPENSA', 15),
    ],

    /* Recordatorio de carrito abandonado: se envia una vez, entre estas horas sin actividad. */
    'carrito_abandonado' => [
        'horas' => (int) env('ATLANTIA_CARRITO_RECORDATORIO_HORAS', 3),
        'horas_max' => 48,
    ],

    /*
     * Descuento automatico en la primera compra de una cuenta registrada (sin cupon).
     * Apagado (0) por defecto: solo se activa si el dueño lo autoriza poniendo un porcentaje en el .env.
     */
    'primera_compra' => [
        'porcentaje' => (float) env('ATLANTIA_PRIMERA_COMPRA_PORCENTAJE', 0),
        'tope' => (float) env('ATLANTIA_PRIMERA_COMPRA_TOPE', 25),
        'minimo' => (float) env('ATLANTIA_PRIMERA_COMPRA_MINIMO', 100),
    ],

    /*
    | Membresia mensual de envio gratis (cobro recurrente en Recurrente).
    | Apagada: no se ofrece ni se puede contratar. Quien ya la hubiera pagado conserva
    | el envio gratis hasta su fecha de vencimiento.
    */
    'membresia' => [
        'activa' => (bool) env('ATLANTIA_MEMBRESIA_ACTIVA', false),
        'precio' => (float) env('ATLANTIA_MEMBRESIA_PRECIO', 49),
        'minimo_envio_gratis' => (float) env('ATLANTIA_MEMBRESIA_MINIMO', 100),
    ],

    /* Montos sugeridos de propina para el repartidor. */
    'propinas_sugeridas' => [5, 10, 15],

    /* Horario general de atencion; cada tienda puede definir el suyo. */
    /*
    | Tiendas: aprobación automática y protecciones.
    | - auto_aprobar: la solicitud que cumple los requisitos y no tiene señales de riesgo se aprueba sola.
    | - Tiendas nuevas (primeros dias_tienda_nueva): el dinero de cada venta entregada se libera dias_retencion_nueva después.
    | - Denuncias: con denuncias_para_pausa clientes distintos en dias_denuncias, la tienda se pausa y se retienen sus pagos.
    */
    'vendedores' => [
        'auto_aprobar' => (bool) env('ATLANTIA_AUTO_APROBAR_VENDEDORES', true),
        'dias_tienda_nueva' => (int) env('ATLANTIA_DIAS_TIENDA_NUEVA', 14),
        'dias_retencion_nueva' => (int) env('ATLANTIA_DIAS_RETENCION_TIENDA_NUEVA', 7),
        'denuncias_para_pausa' => (int) env('ATLANTIA_DENUNCIAS_PARA_PAUSA', 3),
        'dias_denuncias' => (int) env('ATLANTIA_DIAS_DENUNCIAS', 7),
        /* Horas para transferir cuando la tienda eligió cobrar al instante. */
        'horas_pago_instantaneo' => (int) env('ATLANTIA_HORAS_PAGO_INSTANTANEO', 2),
    ],

    'horario' => [
        'apertura' => env('ATLANTIA_HORARIO_APERTURA', '07:00'),
        'cierre' => env('ATLANTIA_HORARIO_CIERRE', '21:00'),
    ],

    'support' => [
        'email' => env('ATLANTIA_SUPPORT_EMAIL'),
        'phone' => env('ATLANTIA_SUPPORT_PHONE'),
        'emergency_phone' => env('ATLANTIA_SUPPORT_EMERGENCY_PHONE', env('ATLANTIA_SUPPORT_PHONE')),
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
        /*
         * Modo de cobro con tarjeta:
         * - recurrente: pago en linea con el checkout alojado de Recurrente.
         * - pos: cobro con terminal POS al entregar.
         */
        'card' => [
            'mode' => env('ATLANTIA_CARD_PAYMENT_MODE', 'pos'),
        ],
        'cash' => [
            'max_change_bill' => (float) env('ATLANTIA_CASH_MAX_CHANGE_BILL', 500),
        ],
        'pos' => [
            'enabled' => (bool) env('ATLANTIA_POS_ENABLED', true),
            'provider' => env('ATLANTIA_POS_PROVIDER', 'POS bancario'),
            'support_phone' => env('ATLANTIA_POS_SUPPORT_PHONE'),
            'merchant_id' => env('ATLANTIA_POS_MERCHANT_ID'),
            'terminal_ids' => array_values(array_filter(array_map(
                static fn (string $terminalId): string => trim($terminalId),
                explode(',', (string) env('ATLANTIA_POS_TERMINAL_IDS', ''))
            ))),
            'currency' => env('ATLANTIA_POS_CURRENCY', 'GTQ'),
            'receipt_required' => (bool) env('ATLANTIA_POS_RECEIPT_REQUIRED', true),
        ],
        'transfer' => [
            'enabled' => (bool) env('ATLANTIA_TRANSFER_ENABLED', false),
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
