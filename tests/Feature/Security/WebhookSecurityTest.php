<?php

namespace Tests\Feature\Security;

use App\Services\Pagos\VerificadorHmacService;
use Tests\TestCase;

/**
 * Pruebas de seguridad para webhooks.
 */
class WebhookSecurityTest extends TestCase
{
    /**
     * Documenta y valida el formato de firma aceptado por la pasarela.
     */
    public function testPaymentGatewaySignatureCanBeGeneratedAndVerified(): void
    {
        $payload = json_encode([
            'numero_pedido' => 'ATL-20260418-0007',
            'estado' => 'pagado',
            'monto' => 245.75,
        ], JSON_THROW_ON_ERROR);
        $secret = 'secret-webhook-pasarela';
        $service = app(VerificadorHmacService::class);

        $signature = $service->sign($payload, $secret);

        $this->assertStringStartsWith('sha256=', $signature);
        $this->assertTrue($service->verify($payload, $signature, $secret));
    }
}
