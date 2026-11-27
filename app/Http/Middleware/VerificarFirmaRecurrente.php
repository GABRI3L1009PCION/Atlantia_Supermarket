<?php

namespace App\Http\Middleware;

use App\Services\Pagos\Recurrente\RecurrenteWebhookVerifier;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

/**
 * Verifica la firma Svix de los webhooks de Recurrente y evita reprocesar
 * entregas repetidas del mismo mensaje (svix-id).
 */
class VerificarFirmaRecurrente
{
    /** Svix reintenta hasta ~8.5 horas; se guarda el id procesado por mas tiempo. */
    private const PROCESSED_TTL_SECONDS = 172800;

    private const LOCK_SECONDS = 120;

    public function __construct(private readonly RecurrenteWebhookVerifier $verifier) {}

    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $id = (string) $request->header('svix-id', $request->header('webhook-id', ''));
        $timestamp = (string) $request->header('svix-timestamp', $request->header('webhook-timestamp', ''));
        $signature = (string) $request->header('svix-signature', $request->header('webhook-signature', ''));
        $secret = trim((string) config('services.recurrente.webhook_secret'));

        if ($secret === '' || ! $this->verifier->verify($request->getContent(), $id, $timestamp, $signature, $secret)) {
            return response()->json(['message' => 'Firma de webhook invalida.'], Response::HTTP_UNAUTHORIZED);
        }

        $key = 'webhooks:recurrente:'.hash('sha256', $id);
        $lock = Cache::lock($key.':processing', self::LOCK_SECONDS);

        if (! $lock->get()) {
            /* Otra entrega del mismo mensaje se esta procesando; Svix reintentara. */
            return response()->json(['message' => 'Webhook en proceso.'], Response::HTTP_CONFLICT);
        }

        try {
            if (Cache::has($key)) {
                /* Ya procesado: se responde 200 para que Svix no siga reintentando. */
                return response()->json(['message' => 'Webhook ya procesado.']);
            }

            $response = $next($request);

            if ($response->isSuccessful()) {
                Cache::put($key, true, now()->addSeconds(self::PROCESSED_TTL_SECONDS));
            }

            return $response;
        } finally {
            $lock->release();
        }
    }
}
