<?php

namespace Tests\Feature\Security;

use App\Enums\EstadoPago;
use App\Models\Payment;
use App\Models\Pedido;
use App\Services\Pagos\VerificadorHmacService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Pruebas de seguridad para webhooks.
 */
class WebhookSecurityTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Documenta y valida el formato de firma aceptado por la pasarela.
     */
    public function test_payment_gateway_signature_can_be_generated_and_verified(): void
    {
        $payload = json_encode([
            'numero_pedido' => 'ATL-20260418-0007',
            'estado' => 'pagado',
            'monto' => 245.75,
        ], JSON_THROW_ON_ERROR);
        $secret = 'secret-webhook-pasarela';
        $service = app(VerificadorHmacService::class);
        $timestamp = (string) now()->timestamp;

        $signature = $service->sign($payload, $secret, $timestamp);

        $this->assertStringStartsWith('sha256=', $signature);
        $this->assertTrue($service->verify($payload, $signature, $secret, $timestamp));
    }

    /**
     * Un webhook firmado puede confirmar pagos por transaction_id sin recalcular HMAC con JSON reconstruido.
     */
    public function test_payment_gateway_webhook_updates_payment_state(): void
    {
        config(['services.payment_gateway.webhook_secret' => 'secret-webhook-pasarela']);

        $pedido = Pedido::factory()->create([
            'estado' => 'confirmado',
            'estado_pago' => EstadoPago::Validando->value,
        ]);
        $payment = Payment::factory()->create([
            'pedido_id' => $pedido->id,
            'estado' => EstadoPago::Validando->value,
            'transaccion_id_pasarela' => 'pi_123',
        ]);
        $payload = [
            'transaction_id' => 'pi_123',
            'status' => 'succeeded',
        ];
        $body = json_encode($payload, JSON_THROW_ON_ERROR);
        $timestamp = (string) now()->timestamp;
        $signature = app(VerificadorHmacService::class)->sign($body, 'secret-webhook-pasarela', $timestamp);

        $this->call('POST', route('webhooks.pasarela-pago'), [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_X_ATLANTIA_SIGNATURE' => $signature,
            'HTTP_X_ATLANTIA_TIMESTAMP' => $timestamp,
            'HTTP_X_ATLANTIA_EVENT_ID' => 'evt-payment-succeeded-123',
        ], $body)->assertOk();

        $this->assertSame(EstadoPago::Aprobado, $payment->refresh()->estado);
        $this->assertSame(EstadoPago::Pagado, $pedido->refresh()->estado_pago);
    }

    /**
     * Rechaza webhooks de pasarela sin firma valida.
     */
    public function test_payment_gateway_webhook_rejects_invalid_signature(): void
    {
        config(['services.payment_gateway.webhook_secret' => 'secret-webhook-pasarela']);

        $payload = ['transaction_id' => 'pi_123', 'status' => 'succeeded'];
        $timestamp = (string) now()->timestamp;

        $this->postJson(route('webhooks.pasarela-pago'), $payload, [
            'X-Atlantia-Signature' => 'sha256=invalid',
            'X-Atlantia-Timestamp' => $timestamp,
            'X-Atlantia-Event-Id' => 'evt-invalid-signature-123',
        ])->assertUnauthorized();
    }

    /**
     * Rechaza reintentos del mismo evento firmado.
     */
    public function test_payment_gateway_webhook_rejects_replay(): void
    {
        config(['services.payment_gateway.webhook_secret' => 'secret-webhook-pasarela']);

        $pedido = Pedido::factory()->create([
            'estado' => 'confirmado',
            'estado_pago' => EstadoPago::Validando->value,
        ]);
        Payment::factory()->create([
            'pedido_id' => $pedido->id,
            'estado' => EstadoPago::Validando->value,
            'transaccion_id_pasarela' => 'pi_replay',
        ]);
        $payload = ['transaction_id' => 'pi_replay', 'status' => 'succeeded'];
        $body = json_encode($payload, JSON_THROW_ON_ERROR);
        $timestamp = (string) now()->timestamp;
        $signature = app(VerificadorHmacService::class)->sign($body, 'secret-webhook-pasarela', $timestamp);
        $headers = [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_X_ATLANTIA_SIGNATURE' => $signature,
            'HTTP_X_ATLANTIA_TIMESTAMP' => $timestamp,
            'HTTP_X_ATLANTIA_EVENT_ID' => 'evt-replay-123',
        ];

        $this->call('POST', route('webhooks.pasarela-pago'), [], [], [], $headers, $body)->assertOk();
        $this->call('POST', route('webhooks.pasarela-pago'), [], [], [], $headers, $body)->assertConflict();
    }

    /**
     * Rechaza firmas validas fuera de la ventana de frescura.
     */
    public function test_payment_gateway_webhook_rejects_stale_timestamp(): void
    {
        config(['services.payment_gateway.webhook_secret' => 'secret-webhook-pasarela']);

        $payload = ['transaction_id' => 'pi_stale', 'status' => 'succeeded'];
        $body = json_encode($payload, JSON_THROW_ON_ERROR);
        $timestamp = (string) now()->subMinutes(10)->timestamp;
        $signature = app(VerificadorHmacService::class)->sign($body, 'secret-webhook-pasarela', $timestamp);

        $this->call('POST', route('webhooks.pasarela-pago'), [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_X_ATLANTIA_SIGNATURE' => $signature,
            'HTTP_X_ATLANTIA_TIMESTAMP' => $timestamp,
            'HTTP_X_ATLANTIA_EVENT_ID' => 'evt-stale-123',
        ], $body)->assertUnauthorized();
    }
}
