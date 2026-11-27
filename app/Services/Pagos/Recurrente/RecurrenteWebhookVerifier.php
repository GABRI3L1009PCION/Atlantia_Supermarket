<?php

namespace App\Services\Pagos\Recurrente;

/**
 * Verifica firmas de webhooks de Recurrente (esquema Svix).
 *
 * signed_content = "{svix-id}.{svix-timestamp}.{cuerpo crudo}"
 * firma = base64(HMAC-SHA256(base64_decode(secreto sin "whsec_"), signed_content))
 * La cabecera svix-signature puede traer varias firmas "v1,<firma>" separadas por espacio.
 */
class RecurrenteWebhookVerifier
{
    public const TOLERANCE_SECONDS = 300;

    public function verify(string $rawBody, string $id, string $timestamp, string $signatureHeader, string $secret, ?int $now = null): bool
    {
        if ($id === '' || $signatureHeader === '' || $secret === '' || ! ctype_digit($timestamp)) {
            return false;
        }

        $now ??= time();

        if (abs($now - (int) $timestamp) > self::TOLERANCE_SECONDS) {
            return false;
        }

        $key = base64_decode(str_starts_with($secret, 'whsec_') ? substr($secret, 6) : $secret, true);

        if ($key === false || $key === '') {
            return false;
        }

        $expected = base64_encode(hash_hmac('sha256', $id.'.'.$timestamp.'.'.$rawBody, $key, true));

        foreach (preg_split('/\s+/', trim($signatureHeader)) ?: [] as $candidate) {
            [$version, $signature] = array_pad(explode(',', $candidate, 2), 2, '');

            if ($version === 'v1' && $signature !== '' && hash_equals($expected, $signature)) {
                return true;
            }
        }

        return false;
    }
}
