<?php

namespace App\Services\Antifraude;

use Illuminate\Support\Facades\Http;

/**
 * Servicio de verificacion reCAPTCHA v3.
 */
class ReCaptchaService
{
    /**
     * Verifica token reCAPTCHA v3 contra Google o modo local.
     *
     * @param string|null $token
     * @param string $action
     * @param string|null $ip
     * @return array<string, mixed>
     */
    public function verify(?string $token, string $action, ?string $ip = null): array
    {
        $secret = config('services.recaptcha.secret') ?: env('RECAPTCHA_SECRET');

        if (empty($secret) || app()->environment(['local', 'testing'])) {
            return [
                'success' => true,
                'score' => 0.9,
                'action' => $action,
                'source' => 'mock',
            ];
        }

        if (empty($token)) {
            return [
                'success' => false,
                'score' => 0.0,
                'action' => $action,
                'source' => 'google',
                'error_codes' => ['missing-input-response'],
            ];
        }

        $response = Http::asForm()->timeout(8)->post('https://www.google.com/recaptcha/api/siteverify', [
            'secret' => $secret,
            'response' => $token,
            'remoteip' => $ip,
        ]);

        $payload = $response->json() ?? [];

        return [
            'success' => (bool) ($payload['success'] ?? false),
            'score' => (float) ($payload['score'] ?? 0.0),
            'action' => $payload['action'] ?? $action,
            'source' => 'google',
            'error_codes' => $payload['error-codes'] ?? [],
        ];
    }

    /**
     * Indica si el resultado supera el umbral minimo.
     *
     * @param array<string, mixed> $resultado
     * @param float $threshold
     * @return bool
     */
    public function passes(array $resultado, float $threshold = 0.5): bool
    {
        return (bool) ($resultado['success'] ?? false)
            && (float) ($resultado['score'] ?? 0.0) >= $threshold;
    }
}
