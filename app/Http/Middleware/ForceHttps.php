<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Fuerza HTTPS en produccion respetando proxies y balanceadores.
 */
class ForceHttps
{
    /**
     * Redirige a HTTPS cuando la aplicacion corre en produccion.
     *
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (app()->environment('production')) {
            $host = parse_url((string) config('app.url'), PHP_URL_HOST);
            abort_unless(is_string($host) && strcasecmp($request->getHost(), $host) === 0, 400, 'Host no permitido.');
        }

        if ($this->shouldForceHttps($request)) {
            return redirect()->secure($request->getRequestUri(), 301);
        }

        return $next($request);
    }

    /**
     * Determina si la solicitud debe redirigirse a HTTPS.
     */
    private function shouldForceHttps(Request $request): bool
    {
        if (! app()->environment('production')) {
            return false;
        }

        if ($request->secure()) {
            return false;
        }

        return ! $request->is('health') && ! $request->is('up');
    }
}
