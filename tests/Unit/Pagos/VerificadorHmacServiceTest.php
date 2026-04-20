<?php

namespace Tests\Unit\Pagos;

use App\Services\Pagos\VerificadorHmacService;
use PHPUnit\Framework\TestCase;

/**
 * Pruebas unitarias de firmas HMAC para webhooks.
 */
class VerificadorHmacServiceTest extends TestCase
{
    /**
     * Valida firmas generadas con prefijo sha256.
     */
    public function testAcceptsValidPrefixedSignature(): void
    {
        $service = new VerificadorHmacService();
        $payload = '{"pedido":"ATL-20260418-0001","estado":"pagado"}';
        $secret = 'clave-compartida-atlantia';

        $signature = $service->sign($payload, $secret);

        $this->assertTrue($service->verify($payload, $signature, $secret));
    }

    /**
     * Rechaza firmas modificadas.
     */
    public function testRejectsTamperedPayload(): void
    {
        $service = new VerificadorHmacService();
        $secret = 'clave-compartida-atlantia';
        $signature = $service->sign('{"monto":125.50}', $secret);

        $this->assertFalse($service->verify('{"monto":925.50}', $signature, $secret));
    }

    /**
     * Rechaza entradas vacias.
     */
    public function testRejectsEmptyInputs(): void
    {
        $service = new VerificadorHmacService();

        $this->assertFalse($service->verify('', 'sha256=abc', 'secret'));
        $this->assertFalse($service->verify('payload', '', 'secret'));
        $this->assertFalse($service->verify('payload', 'sha256=abc', ''));
    }
}
