<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Auth\SessionGuard;
use Illuminate\Http\Request;
use Illuminate\Session\Middleware\AuthenticateSession;
use Symfony\Component\HttpFoundation\Response;

/**
 * Cierra la sesión en este dispositivo si la contraseña cambió en otro
 * (necesario para "cerrar sesión en los demás dispositivos").
 * Solo actúa con el guard de sesión web; los tokens de la API se ignoran.
 */
class AutenticarSesion extends AuthenticateSession
{
    public function handle($request, Closure $next): Response
    {
        if (! $this->guard() instanceof SessionGuard) {
            return $next($request);
        }

        return parent::handle($request, $next);
    }
}
