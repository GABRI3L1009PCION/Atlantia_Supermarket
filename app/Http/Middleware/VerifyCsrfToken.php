<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as Middleware;

/**
 * Proteccion CSRF para formularios web de Atlantia.
 */
class VerifyCsrfToken extends Middleware
{
    /**
     * URIs excluidas de verificacion CSRF.
     *
     * Los webhooks externos usan HMAC/token dedicado y no dependen de sesion.
     *
     * @var array<int, string>
     */
    protected $except = [
        'webhooks/pasarela-pago',
        'webhooks/certificador-fel',
        'webhooks/courier-externo',
        'webhooks/ml-service',
    ];
}
