<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

/**
 * Bloquea temporalmente autenticacion con demasiados intentos fallidos.
 */
class BloquearPorIntentos
{
    /**
     * Cantidad maxima de intentos fallidos permitidos en la ventana.
     */
    private const MAX_FAILED_ATTEMPTS = 5;

    /**
     * Ventana de revision en minutos.
     */
    private const WINDOW_MINUTES = 15;

    /**
     * Verifica intentos fallidos recientes por email e IP.
     *
     * @param Request $request
     * @param Closure(Request): Response $next
     * @return Response
     */
    public function handle(Request $request, Closure $next): Response
    {
        $email = mb_strtolower(trim((string) $request->input('email')));
        $ipAddress = (string) $request->ip();

        if ($email === '') {
            return $next($request);
        }

        $failedAttempts = DB::table('login_attempts')
            ->where('email', $email)
            ->where('ip_address', $ipAddress)
            ->where('successful', false)
            ->where('attempted_at', '>=', now()->subMinutes(self::WINDOW_MINUTES))
            ->count();

        if ($failedAttempts >= self::MAX_FAILED_ATTEMPTS) {
            abort(429, 'Demasiados intentos fallidos. Intenta nuevamente en unos minutos.');
        }

        return $next($request);
    }
}
