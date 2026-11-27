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
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Las peticiones de Livewire son AJAX puras — no renderizan HTML ni
        // ejecutan scripts propios, así que no necesitan (ni deben recibir)
        // un nuevo nonce CSP que sobrescriba el de la página cargada.
        $isLivewireUpdate = $request->is('livewire/update')
            || $request->is('livewire/*')
            || $request->headers->get('X-Livewire') === 'true';

        $nonce = Str::random(32);
        $request->attributes->set('csp_nonce', $nonce);
        Vite::useCspNonce($nonce);

        $response = $next($request);

        if ($isLivewireUpdate) {
            return $response;
        }

        if ($request->is('api/*') || $request->user() !== null || $request->routeIs(
            'home',
            'catalogo.*',
            'productos.show',
            'login',
            'login.*',
            'register',
            'register.*',
            'password.*',
            'verification.*',
            'two-factor.*'
        )) {
            $response->headers->set('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
            $response->headers->set('Pragma', 'no-cache');
            $response->headers->set('Expires', 'Sat, 01 Jan 2000 00:00:00 GMT');
        }

        /*
         * Los documentos de vendedores se muestran en el visor del panel (un iframe
         * del mismo sitio). Solo esa ruta puede enmarcarse, y solo desde este sitio.
         */
        $enmarcable = $request->routeIs('admin.vendedores.documents.show') && $request->boolean('ver');

        $response->headers->set('X-Frame-Options', $enmarcable ? 'SAMEORIGIN' : 'DENY');
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('X-Permitted-Cross-Domain-Policies', 'none');
        if ($request->is('api/*') && app()->environment('local')) {
            $response->headers->set('Access-Control-Allow-Private-Network', 'true');
        }
        /* En desarrollo con ngrok se amplía la política de geolocalización */
        $geoPolicy = app()->environment('local')
            ? 'geolocation=*, camera=(), microphone=()'
            : 'geolocation=(self), camera=(), microphone=()';
        $response->headers->set('Permissions-Policy', $geoPolicy);

        $politica = $this->contentSecurityPolicy($nonce);

        if ($enmarcable) {
            $politica = str_replace("frame-ancestors 'none'", "frame-ancestors 'self'", $politica);
        }

        $response->headers->set('Content-Security-Policy', $politica);

        if (app()->environment('production') && $request->secure()) {
            $response->headers->set(
                'Strict-Transport-Security',
                'max-age=31536000; includeSubDomains'
            );
        }

        return $response;
    }

    /**
     * Define una politica CSP compatible con Blade, Livewire, mapas y Vite.
     */
    private function contentSecurityPolicy(string $nonce): string
    {
        $scriptSrc = [
            "'self'",
            "'nonce-{$nonce}'",
            /*
             * Livewire 3 incluye Alpine, que evalua las expresiones de x-data, @click y wire:
             * con new Function(). Sin 'unsafe-eval' toda la interactividad del sitio se cae en
             * produccion. El nonce sigue impidiendo que se ejecuten scripts inyectados.
             */
            "'unsafe-eval'",
            'https://api.mapbox.com',
            'https://maps.googleapis.com',
            'https://maps.gstatic.com',
        ];
        $connectSrc = [
            "'self'",
            'https://api.mapbox.com',
            'https://events.mapbox.com',
            'https://maps.googleapis.com',
            'https://maps.gstatic.com',
        ];
        $frameSrc = [
            "'self'",
        ];

        /* Calculo de rutas (OSRM) para el seguimiento en vivo del pedido. */
        $routing = parse_url((string) config('services.mapas.routing_url'));
        if (isset($routing['scheme'], $routing['host']) && in_array($routing['scheme'], ['https', 'http'], true)) {
            $connectSrc[] = $routing['scheme'].'://'.$routing['host'].(isset($routing['port']) ? ':'.$routing['port'] : '');
        }

        if (app()->environment('local')) {
            array_push($scriptSrc, 'http://127.0.0.1:5173', 'http://localhost:5173', 'https://*.ngrok-free.dev', 'https://*.ngrok-free.app', 'https://*.ngrok.io');
            array_push(
                $connectSrc,
                'http://127.0.0.1:5173',
                'ws://127.0.0.1:5173',
                'http://localhost:5173',
                'ws://localhost:5173',
                'https://*.ngrok-free.dev',
                'https://*.ngrok-free.app',
                'https://*.ngrok.io'
            );
        }

        /* En local con ngrok permitir cualquier origen para forms y frames */
        $ngrokDomains = "'self' https://*.ngrok-free.dev https://*.ngrok-free.app https://*.ngrok.io https://*.ngrok.app";
        $selfOrAny = app()->environment('local') ? $ngrokDomains : "'self'";

        /* El checkout redirige a la pagina alojada de Recurrente: form-action debe permitirla. */
        $paymentDomains = collect(config('services.recurrente.checkout_domains', []))
            ->filter(fn (mixed $domain): bool => is_string($domain) && str_starts_with($domain, 'https://'))
            ->implode(' ');
        $formAction = trim($selfOrAny.' '.$paymentDomains);
        /* Checkout embebido de Recurrente dentro de la pagina de pago. */
        $frameSrc = array_merge($frameSrc, array_filter(explode(' ', $paymentDomains)));

        return implode('; ', [
            "default-src 'self'",
            "base-uri 'self'",
            "frame-ancestors 'none'",
            "object-src 'none'",
            "form-action {$formAction}",
            "img-src 'self' data: blob: https:",
            "font-src 'self' data: https://fonts.gstatic.com",
            "style-src 'self' 'unsafe-inline' https://api.mapbox.com https://fonts.googleapis.com https://fonts.gstatic.com",
            'script-src '.implode(' ', $scriptSrc),
            'connect-src '.implode(' ', $connectSrc),
            'frame-src '.implode(' ', $frameSrc),
            "worker-src 'self' blob:",
            "manifest-src 'self'",
        ]);
    }
}
