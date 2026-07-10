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
    public function test_accepts_valid_prefixed_signature(): void
    {
        $service = new VerificadorHmacService;
        $payload = '{"pedido":"ATL-20260418-0001","estado":"pagado"}';
        $secret = 'clave-compartida-atlantia';
        $timestamp = '1760000000';

        $signature = $service->sign($payload, $secret, $timestamp);

        $this->assertTrue($service->verify($payload, $signature, $secret, $timestamp));
    }

    /**
     * Rechaza firmas modificadas.
     */
    public function test_rejects_tampered_payload(): void
    {
        $service = new VerificadorHmacService;
        $secret = 'clave-compartida-atlantia';
        $timestamp = '1760000000';
        $signature = $service->sign('{"monto":125.50}', $secret, $timestamp);

        $this->assertFalse($service->verify('{"monto":925.50}', $signature, $secret, $timestamp));
        $this->assertFalse($service->verify('{"monto":125.50}', $signature, $secret, '1760000001'));
    }

    /**
     * Rechaza entradas vacias.
     */
    public function test_rejects_empty_inputs(): void
    {
        $service = new VerificadorHmacService;

        $this->assertFalse($service->verify('', 'sha256=abc', 'secret'));
        $this->assertFalse($service->verify('payload', '', 'secret'));
        $this->assertFalse($service->verify('payload', 'sha256=abc', ''));
    }
}
