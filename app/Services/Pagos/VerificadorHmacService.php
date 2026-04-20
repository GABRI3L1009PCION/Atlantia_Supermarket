<?php

namespace App\Services\Pagos;

/**
 * Servicio de verificacion HMAC para webhooks.
 */
class VerificadorHmacService
{
    /**
     * Verifica una firma HMAC SHA-256.
     *
     * @param string $payload
     * @param string $signature
     * @param string $secret
     * @return bool
     */
    public function verify(string $payload, string $signature, string $secret): bool
    {
        if ($payload === '' || $signature === '' || $secret === '') {
            return false;
        }

        $expected = hash_hmac('sha256', $payload, $secret);
        $normalized = str_starts_with($signature, 'sha256=')
            ? substr($signature, 7)
            : $signature;

        return hash_equals($expected, $normalized);
    }

    /**
     * Genera una firma HMAC SHA-256 para pruebas controladas.
     *
     * @param string $payload
     * @param string $secret
     * @return string
     */
    public function sign(string $payload, string $secret): string
    {
        return 'sha256=' . hash_hmac('sha256', $payload, $secret);
    }
}
