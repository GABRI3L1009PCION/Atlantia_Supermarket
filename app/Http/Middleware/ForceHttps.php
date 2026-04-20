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
     * @param Request $request
     * @param Closure(Request): Response $next
     * @return Response
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($this->shouldForceHttps($request)) {
            return redirect()->secure($request->getRequestUri(), 301);
        }

        return $next($request);
    }

    /**
     * Determina si la solicitud debe redirigirse a HTTPS.
     *
     * @param Request $request
     * @return bool
     */
    private function shouldForceHttps(Request $request): bool
    {
        if (! app()->environment('production')) {
            return false;
        }

        if ($request->secure()) {
            return false;
        }

        if ($request->headers->get('X-Forwarded-Proto') === 'https') {
            return false;
        }

        return ! $request->is('health') && ! $request->is('up');
    }
}
