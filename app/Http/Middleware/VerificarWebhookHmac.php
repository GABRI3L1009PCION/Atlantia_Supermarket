<?php

namespace App\Http\Middleware;

use App\Services\Pagos\VerificadorHmacService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Verifica firmas HMAC de webhooks externos.
 */
class VerificarWebhookHmac
{
    /**
     * Crea una instancia del middleware.
     */
    public function __construct(private readonly VerificadorHmacService $verificadorHmacService)
    {
    }

    /**
     * Maneja la solicitud entrante.
     *
     * @param Closure(Request): Response $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $secret = $this->secretFor($request);
        $signature = $this->signatureFrom($request);

        if ($secret === '' || $signature === '') {
            return response()->json(['message' => 'Firma HMAC requerida.'], Response::HTTP_UNAUTHORIZED);
        }

        if (! $this->verificadorHmacService->verify($request->getContent(), $signature, $secret)) {
            return response()->json(['message' => 'Firma HMAC invalida.'], Response::HTTP_UNAUTHORIZED);
        }

        return $next($request);
    }

    /**
     * Obtiene el secreto esperado segun el origen del webhook.
     */
    private function secretFor(Request $request): string
    {
        $path = $request->path();

        return match (true) {
            str_contains($path, 'pasarela-pago') => (string) config('services.payment_gateway.webhook_secret'),
            str_contains($path, 'certificador-fel') => (string) config('services.infile.webhook_secret'),
            str_contains($path, 'courier-externo') => (string) config('services.courier.webhook_secret'),
            str_contains($path, 'ml-service') => (string) config('services.ml.webhook_secret'),
            default => '',
        };
    }

    /**
     * Busca firma en los headers admitidos por integraciones externas.
     */
    private function signatureFrom(Request $request): string
    {
        foreach ([
            'X-Atlantia-Signature',
            'X-INFILE-Signature',
            'X-Courier-Signature',
            'X-ML-Signature',
            'X-Signature',
        ] as $header) {
            $signature = (string) $request->header($header);

            if ($signature !== '') {
                return $signature;
            }
        }

        return '';
    }
}

