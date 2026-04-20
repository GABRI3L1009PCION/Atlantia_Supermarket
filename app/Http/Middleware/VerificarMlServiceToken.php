<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Verifica autenticidad de solicitudes del microservicio ML.
 */
class VerificarMlServiceToken
{
    /**
     * Valida token bearer o firma HMAC del servicio ML.
     *
     * @param Request $request
     * @param Closure(Request): Response $next
     * @return Response
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! $this->hasValidBearerToken($request) && ! $this->hasValidSignature($request)) {
            abort(401, 'Solicitud ML no autorizada.');
        }

        return $next($request);
    }

    /**
     * Verifica token bearer compartido.
     *
     * @param Request $request
     * @return bool
     */
    private function hasValidBearerToken(Request $request): bool
    {
        $expectedToken = (string) config('services.ml.token', env('ML_SERVICE_TOKEN', ''));

        if ($expectedToken === '') {
            return false;
        }

        return hash_equals($expectedToken, (string) $request->bearerToken());
    }

    /**
     * Verifica firma HMAC del body recibido.
     *
     * @param Request $request
     * @return bool
     */
    private function hasValidSignature(Request $request): bool
    {
        $secret = (string) config('services.ml.webhook_secret', env('ML_WEBHOOK_SECRET', ''));
        $signature = (string) $request->header('X-ML-Signature', '');

        if ($secret === '' || $signature === '') {
            return false;
        }

        $expected = hash_hmac('sha256', $request->getContent(), $secret);
        $normalized = str_starts_with($signature, 'sha256=')
            ? substr($signature, 7)
            : $signature;

        return hash_equals($expected, $normalized);
    }
}
