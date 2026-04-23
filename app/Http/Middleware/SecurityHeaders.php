<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

/**
 * Agrega cabeceras HTTP de seguridad a respuestas web y API.
 */
class SecurityHeaders
{
    /**
     * Procesa la solicitud y agrega cabeceras seguras.
     *
     * @param Request $request
     * @param Closure(Request): Response $next
     * @return Response
     */
    public function handle(Request $request, Closure $next): Response
    {
        $nonce = Str::random(32);
        $request->attributes->set('csp_nonce', $nonce);
        Vite::useCspNonce($nonce);

        $response = $next($request);

        $response->headers->set('X-Frame-Options', 'DENY');
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('X-Permitted-Cross-Domain-Policies', 'none');
        $response->headers->set(
            'Permissions-Policy',
            'geolocation=(self), camera=(), microphone=()'
        );
        $response->headers->set('Content-Security-Policy', $this->contentSecurityPolicy($nonce));

        if (app()->environment('production') && $request->secure()) {
            $response->headers->set(
                'Strict-Transport-Security',
                'max-age=31536000; includeSubDomains'
            );
        }

        return $response;
    }

    /**
     * Define una politica CSP compatible con Blade, Livewire, Mapbox y Vite.
     */
    private function contentSecurityPolicy(string $nonce): string
    {
        $scriptSrc = [
            "'self'",
            "'nonce-{$nonce}'",
            'https://api.mapbox.com',
        ];
        $connectSrc = [
            "'self'",
            'https://api.mapbox.com',
            'https://events.mapbox.com',
        ];

        if (app()->environment('local')) {
            array_push($scriptSrc, 'http://127.0.0.1:5173', 'http://localhost:5173');
            array_push(
                $connectSrc,
                'http://127.0.0.1:5173',
                'ws://127.0.0.1:5173',
                'http://localhost:5173',
                'ws://localhost:5173'
            );
        }

        return implode('; ', [
            "default-src 'self'",
            "base-uri 'self'",
            "frame-ancestors 'none'",
            "object-src 'none'",
            "form-action 'self'",
            "img-src 'self' data: blob: https:",
            "font-src 'self' data:",
            "style-src 'self' 'nonce-{$nonce}' https://api.mapbox.com",
            'script-src ' . implode(' ', $scriptSrc),
            'connect-src ' . implode(' ', $connectSrc),
            "worker-src 'self' blob:",
            "manifest-src 'self'",
        ]);
    }
}
