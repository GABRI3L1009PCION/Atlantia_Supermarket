<?php

namespace App\Http\Middleware;

use App\Services\Pagos\VerificadorHmacService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

/**
 * Verifica firmas HMAC de webhooks externos.
 */
class VerificarWebhookHmac
{
    private const TIMESTAMP_TOLERANCE_SECONDS = 300;

    private const REPLAY_TTL_SECONDS = 600;

    /**
     * Crea una instancia del middleware.
     */
    public function __construct(private readonly VerificadorHmacService $verificadorHmacService) {}

    /**
     * Maneja la solicitud entrante.
     *
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $secret = $this->secretFor($request);
        $signature = $this->signatureFrom($request);
        $timestamp = $this->timestampFrom($request);
        $eventId = $this->eventIdFrom($request);

        if ($secret === '' || $signature === '' || $timestamp === '' || $eventId === '') {
            return response()->json(['message' => 'Firma HMAC requerida.'], Response::HTTP_UNAUTHORIZED);
        }

        if (! $this->timestampIsFresh($timestamp)) {
            return response()->json(['message' => 'Timestamp de webhook invalido.'], Response::HTTP_UNAUTHORIZED);
        }

        if (! $this->verificadorHmacService->verify($request->getContent(), $signature, $secret, $timestamp)) {
            return response()->json(['message' => 'Firma HMAC invalida.'], Response::HTTP_UNAUTHORIZED);
        }

        if (! Cache::add($this->replayCacheKey($request, $eventId), true, now()->addSeconds(self::REPLAY_TTL_SECONDS))) {
            return response()->json(['message' => 'Webhook ya procesado.'], Response::HTTP_CONFLICT);
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

    /**
     * Busca timestamp Unix enviado por la integracion.
     */
    private function timestampFrom(Request $request): string
    {
        foreach ([
            'X-Atlantia-Timestamp',
            'X-Webhook-Timestamp',
            'X-INFILE-Timestamp',
            'X-Courier-Timestamp',
            'X-ML-Timestamp',
        ] as $header) {
            $timestamp = (string) $request->header($header);

            if ($timestamp !== '') {
                return $timestamp;
            }
        }

        return '';
    }

    /**
     * Obtiene un identificador idempotente del evento para prevenir replay.
     */
    private function eventIdFrom(Request $request): string
    {
        foreach ([
            'X-Atlantia-Event-Id',
            'X-Webhook-Id',
            'X-INFILE-Event-Id',
            'X-Courier-Event-Id',
            'X-ML-Event-Id',
            'X-Request-Id',
        ] as $header) {
            $eventId = trim((string) $request->header($header));

            if ($eventId !== '') {
                return substr($eventId, 0, 160);
            }
        }

        return '';
    }

    /**
     * Acepta timestamps Unix en segundos o milisegundos dentro de una ventana corta.
     */
    private function timestampIsFresh(string $timestamp): bool
    {
        if (! ctype_digit($timestamp)) {
            return false;
        }

        $sentAt = (int) $timestamp;

        if ($sentAt > 9999999999) {
            $sentAt = intdiv($sentAt, 1000);
        }

        return abs(now()->timestamp - $sentAt) <= self::TIMESTAMP_TOLERANCE_SECONDS;
    }

    /**
     * Llave estable por endpoint y evento.
     */
    private function replayCacheKey(Request $request, string $eventId): string
    {
        return 'webhook-replay:'.sha1($request->path().':'.$eventId);
    }
}
