<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Agrega cabeceras HTTP de seguridad a respuestas web y API.
 */
class SecurityHeaders
{
    /**
     * Procesa la solicitud y agrega cabeceras seguras.
     *
     * @param  Request  $request
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $response->headers->set('X-Frame-Options', 'DENY');
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('X-Permitted-Cross-Domain-Policies', 'none');
        $response->headers->set(
            'Permissions-Policy',
            'camera=(), microphone=(), geolocation=(self), payment=(self)'
        );
        $response->headers->set('Content-Security-Policy', $this->contentSecurityPolicy());

        if (app()->environment('production') && $request->secure()) {
            $response->headers->set(
                'Strict-Transport-Security',
                'max-age=31536000; includeSubDomains; preload'
            );
        }

        return $response;
    }

    /**
     * Define una política CSP compatible con Blade, Livewire, Mapbox y Vite.
     */
    private function contentSecurityPolicy(): string
    {
        return implode('; ', [
            "default-src 'self'",
            "base-uri 'self'",
            "frame-ancestors 'none'",
            "object-src 'none'",
            "form-action 'self'",
            "img-src 'self' data: blob: https:",
            "font-src 'self' data:",
            "style-src 'self' 'unsafe-inline' https://api.mapbox.com",
            "script-src 'self' 'unsafe-inline' 'unsafe-eval' http://127.0.0.1:5173 http://localhost:5173 https://api.mapbox.com",
            "connect-src 'self' http://127.0.0.1:5173 ws://127.0.0.1:5173 http://localhost:5173 ws://localhost:5173 https://api.mapbox.com https://events.mapbox.com",
            "worker-src 'self' blob:",
            "manifest-src 'self'",
        ]);
    }
}
