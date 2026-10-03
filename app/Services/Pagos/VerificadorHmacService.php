<?php

namespace App\Services\Pagos;

/**
 * Servicio de verificacion HMAC para webhooks.
 */
class VerificadorHmacService
{
    /**
     * Verifica una firma HMAC SHA-256.
     */
    public function verify(string $payload, string $signature, string $secret, ?string $timestamp = null): bool
    {
        if ($payload === '' || $signature === '' || $secret === '') {
            return false;
        }

        $signedPayload = $timestamp !== null && $timestamp !== ''
            ? $timestamp.'.'.$payload
            : $payload;
        $expected = hash_hmac('sha256', $signedPayload, $secret);
        $normalized = str_starts_with($signature, 'sha256=')
            ? substr($signature, 7)
            : $signature;

        return hash_equals($expected, $normalized);
    }

    /**
     * Genera una firma HMAC SHA-256 para pruebas controladas.
     */
    public function sign(string $payload, string $secret, ?string $timestamp = null): string
    {
        $signedPayload = $timestamp !== null && $timestamp !== ''
            ? $timestamp.'.'.$payload
            : $payload;

        return 'sha256='.hash_hmac('sha256', $signedPayload, $secret);
    }
}
