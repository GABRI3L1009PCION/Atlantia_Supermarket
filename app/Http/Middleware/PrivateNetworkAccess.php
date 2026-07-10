<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Permite que apps instaladas/WebView accedan a la API local en redes privadas.
 */
class PrivateNetworkAccess
{
    /**
     * Responde preflights de Private Network Access antes del CORS de Laravel.
     *
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->is('api/*') && $request->isMethod('OPTIONS')) {
            return response('', 204)->withHeaders($this->headers($request));
        }

        $response = $next($request);

        if ($request->is('api/*')) {
            foreach ($this->headers($request) as $header => $value) {
                $response->headers->set($header, $value);
            }
        }

        return $response;
    }

    /**
     * Cabeceras CORS/PNA para llamadas desde Capacitor Android.
     *
     * @return array<string, string>
     */
    private function headers(Request $request): array
    {
        return [
            'Access-Control-Allow-Origin' => $request->headers->get('Origin', '*'),
            'Access-Control-Allow-Methods' => 'GET, POST, PUT, PATCH, DELETE, OPTIONS',
            'Access-Control-Allow-Headers' => 'Content-Type, Authorization, X-Requested-With, Accept',
            'Access-Control-Allow-Private-Network' => 'true',
        ];
    }
}
