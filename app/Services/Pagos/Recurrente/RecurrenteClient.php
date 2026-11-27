<?php

namespace App\Services\Pagos\Recurrente;

use App\Exceptions\PagoRechazadoException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Cliente HTTP de la API de Recurrente (https://docs.recurrente.com).
 *
 * Autenticacion por cabecera X-SECRET-KEY. La llave (sk_test_ / sk_live_)
 * determina si se opera en Sandbox o produccion; la URL base es la misma.
 */
class RecurrenteClient
{
    /**
     * Indica si el cobro con tarjeta debe pasar por Recurrente.
     */
    public static function habilitado(): bool
    {
        return config('atlantia.payments.card.mode') === 'recurrente';
    }

    /**
     * Crea un checkout alojado de pago unico.
     *
     * @param  array<int, array<string, mixed>>  $items
     * @param  array<string, string|int|float|bool>  $metadata
     * @return array<string, mixed>
     *
     * @throws PagoRechazadoException
     */
    public function crearCheckout(
        array $items,
        string $successUrl,
        string $cancelUrl,
        array $metadata,
        ?\DateTimeInterface $expiresAt,
        string $idempotencyKey
    ): array {
        $body = array_filter([
            'items' => $items,
            'success_url' => $successUrl,
            'cancel_url' => $cancelUrl,
            'metadata' => $metadata,
            'expires_at' => $expiresAt?->format(DATE_ATOM),
        ], static fn (mixed $value): bool => $value !== null);

        $response = $this->send(
            fn (PendingRequest $request): Response => $request
                ->withHeaders(['Idempotency-Key' => $idempotencyKey])
                ->post('/checkouts', $body),
            'crear el checkout de pago'
        );

        $data = (array) $response->json();

        if (! is_string($data['id'] ?? null) || ! is_string($data['checkout_url'] ?? null)) {
            Log::error('Recurrente devolvio un checkout sin id o checkout_url.', ['status' => $response->status()]);

            throw new PagoRechazadoException('La pasarela de pago no devolvio un enlace de pago valido.');
        }

        return $data;
    }

    /**
     * Consulta un checkout por id para verificar su estado del lado del servidor.
     *
     * @return array<string, mixed>
     *
     * @throws PagoRechazadoException
     */
    public function obtenerCheckout(string $checkoutId): array
    {
        $response = $this->send(
            fn (PendingRequest $request): Response => $request->get('/checkouts/'.rawurlencode($checkoutId)),
            'consultar el checkout de pago'
        );

        return (array) $response->json();
    }

    /**
     * Solicita un reembolso de un intent (in_...). Sin monto se reembolsa el saldo completo.
     *
     * @return array<string, mixed>
     *
     * @throws PagoRechazadoException
     */
    public function reembolsar(string $intentId, ?int $amountInCents, string $idempotencyKey): array
    {
        $body = ['intent_id' => $intentId];

        if ($amountInCents !== null) {
            $body['amount_in_cents'] = $amountInCents;
        }

        $response = $this->send(
            fn (PendingRequest $request): Response => $request
                ->withHeaders(['Idempotency-Key' => $idempotencyKey])
                ->post('/refunds', $body),
            'solicitar el reembolso'
        );

        return (array) $response->json();
    }

    /**
     * Crea un cliente en Recurrente (necesario para guardar tarjetas).
     *
     * @return array<string, mixed>
     *
     * @throws PagoRechazadoException
     */
    public function crearCliente(string $email, string $nombre): array
    {
        return (array) $this->send(
            fn (PendingRequest $request): Response => $request->post('/customers', ['email' => $email, 'full_name' => $nombre]),
            'registrar al cliente'
        )->json();
    }

    /**
     * Checkout en modo "setup": guarda la tarjeta sin cobrarla.
     *
     * @return array<string, mixed>
     *
     * @throws PagoRechazadoException
     */
    public function crearCheckoutGuardarTarjeta(string $customerId, string $successUrl, string $cancelUrl, string $idempotencyKey): array
    {
        return (array) $this->send(
            fn (PendingRequest $request): Response => $request
                ->withHeaders(['Idempotency-Key' => $idempotencyKey])
                ->post('/checkouts', [
                    'mode' => 'setup',
                    'customer_id' => $customerId,
                    'success_url' => $successUrl,
                    'cancel_url' => $cancelUrl,
                ]),
            'preparar el guardado de la tarjeta'
        )->json();
    }

    /**
     * Cobra directamente un metodo de pago guardado (POST /one_time_payments).
     *
     * @param  array<int, array<string, mixed>>  $items
     * @return array<string, mixed>
     *
     * @throws PagoRechazadoException
     */
    public function cobrarTarjetaGuardada(string $customerId, string $metodoPagoId, array $items, string $idempotencyKey): array
    {
        return (array) $this->send(
            fn (PendingRequest $request): Response => $request
                ->withHeaders(['Idempotency-Key' => $idempotencyKey])
                ->post('/one_time_payments', [
                    'customer_id' => $customerId,
                    'payment_method_id' => $metodoPagoId,
                    'items' => $items,
                ]),
            'cobrar la tarjeta guardada'
        )->json();
    }

    /**
     * Cancela los cobros futuros de una suscripcion (no reembolsa pagos anteriores).
     *
     * @throws PagoRechazadoException
     */
    public function cancelarSuscripcion(string $subscriptionId): void
    {
        $this->send(
            fn (PendingRequest $request): Response => $request->delete('/subscriptions/'.rawurlencode($subscriptionId)),
            'cancelar la suscripcion'
        );
    }

    /**
     * Ejecuta la peticion y normaliza errores de red o de la API.
     *
     * @param  callable(PendingRequest): Response  $callback
     *
     * @throws PagoRechazadoException
     */
    private function send(callable $callback, string $accion): Response
    {
        $secret = trim((string) config('services.recurrente.secret_key'));

        if ($secret === '') {
            throw new PagoRechazadoException('El pago en linea no esta configurado. Elige otro metodo de pago.');
        }

        try {
            $response = $callback(
                Http::baseUrl((string) config('services.recurrente.base_url'))
                    ->withHeaders(['X-SECRET-KEY' => $secret])
                    ->acceptJson()
                    ->asJson()
                    ->timeout(max(5, (int) config('services.recurrente.timeout_seconds', 20)))
            );
        } catch (ConnectionException $exception) {
            Log::warning('No fue posible conectar con Recurrente.', ['accion' => $accion, 'error' => $exception->getMessage()]);

            throw new PagoRechazadoException('No fue posible conectar con la pasarela de pago. Intenta de nuevo.', previous: $exception);
        }

        if (! $response->successful()) {
            Log::warning('Recurrente rechazo la solicitud.', [
                'accion' => $accion,
                'status' => $response->status(),
                'code' => $response->json('code'),
                'message' => $response->json('message') ?? $response->json('error'),
            ]);

            throw new PagoRechazadoException(match (true) {
                $response->json('code') === 'intent_not_refundable' => 'El pago todavia no se puede reembolsar en la pasarela.',
                $response->json('code') === 'amount_exceeds_unverified_limit' => 'La cuenta de cobro aun no esta verificada para este monto.',
                default => 'La pasarela de pago no pudo '.$accion.'.',
            });
        }

        return $response;
    }
}
