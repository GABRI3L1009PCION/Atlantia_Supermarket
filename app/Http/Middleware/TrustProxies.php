<?php

namespace App\Http\Middleware;

use Illuminate\Http\Middleware\TrustProxies as Middleware;
use Illuminate\Http\Request;

/**
 * Configura proxies confiables para Cloudflare, Nginx y balanceadores.
 */
class TrustProxies extends Middleware
{
    /**
     * Proxies confiables.
     *
     * En produccion se opera detras de Cloudflare/Nginx. El valor wildcard
     * permite resolver correctamente IP y esquema desde cabeceras forward.
     *
     * @var array<int, string>|string|null
     */
    protected $proxies = '*';

    /**
     * Cabeceras usadas para detectar cliente, host y protocolo originales.
     *
     * @var int
     */
    protected $headers =
        Request::HEADER_X_FORWARDED_FOR |
        Request::HEADER_X_FORWARDED_HOST |
        Request::HEADER_X_FORWARDED_PORT |
        Request::HEADER_X_FORWARDED_PROTO |
        Request::HEADER_X_FORWARDED_AWS_ELB;
}
