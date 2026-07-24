<?php

namespace App\Services\Notificaciones;

use App\Models\CourierDevice;
use App\Models\User;
use Firebase\JWT\JWT;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FirebasePushService
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function sendToCourierDevices(User $user, string $type, array $data): void
    {
        if (! $this->isConfigured()) {
            return;
        }

        $devices = CourierDevice::query()
            ->where('user_id', $user->id)
            ->where('push_provider', 'fcm')
            ->where('notifications_enabled', true)
            ->whereNotNull('push_token')
            ->get()
            ->filter(fn (CourierDevice $device): bool => filled($device->push_token))
            ->unique(fn (CourierDevice $device): string => (string) $device->push_token)
            ->values();

        if ($devices->isEmpty()) {
            return;
        }

        $accessToken = $this->accessToken();
        if ($accessToken === null) {
            return;
        }

        $payload = $this->normalizePayload($type, $data);
        $url = sprintf(
            'https://fcm.googleapis.com/v1/projects/%s/messages:send',
            config('services.firebase.project_id')
        );

        foreach ($devices as $device) {
            $token = (string) $device->push_token;
            $response = Http::withToken($accessToken)
                ->acceptJson()
                ->post($url, [
                    'message' => [
                        'token' => $token,
                        'data' => $payload,
                        'android' => [
                            'priority' => 'HIGH',
                        ],
                    ],
                ]);

            if ($response->successful()) {
                continue;
            }

            if ($this->isInvalidTokenResponse($response->json())) {
                $device->forceFill([
                    'notifications_enabled' => false,
                    'push_token' => null,
                ])->save();
            }

            Log::warning('No se pudo enviar push FCM al repartidor.', [
                'user_uuid' => $user->uuid,
                'notification_type' => $type,
                'status' => $response->status(),
                'response' => $response->json(),
            ]);
        }
    }

    private function accessToken(): ?string
    {
        return Cache::remember('firebase.fcm.access_token', now()->addMinutes(50), function (): ?string {
            $email = (string) config('services.firebase.service_account_email');
            $privateKey = str_replace('\n', "\n", (string) config('services.firebase.private_key'));
            $tokenUri = (string) config('services.firebase.token_uri');

            if ($email === '' || $privateKey === '' || $tokenUri === '') {
                return null;
            }

            $now = time();
            $assertion = JWT::encode([
                'iss' => $email,
                'sub' => $email,
                'aud' => $tokenUri,
                'scope' => 'https://www.googleapis.com/auth/firebase.messaging',
                'iat' => $now,
                'exp' => $now + 3600,
            ], $privateKey, 'RS256');

            $response = Http::asForm()->acceptJson()->post($tokenUri, [
                'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                'assertion' => $assertion,
            ]);

            if (! $response->successful()) {
                Log::warning('No se pudo obtener el access token de Firebase.', [
                    'status' => $response->status(),
                    'response' => $response->json(),
                ]);

                return null;
            }

            return $response->json('access_token');
        });
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, string>
     */
    private function normalizePayload(string $type, array $data): array
    {
        $title = (string) ($data['title'] ?? $data['titulo'] ?? 'Atlantia Repartidor');
        $message = (string) ($data['message'] ?? $data['mensaje'] ?? 'Tienes una actualizacion nueva.');

        $normalized = [
            'type' => $type,
            'title' => $title,
            'body' => $message,
        ];

        foreach ($data as $key => $value) {
            if ($value === null || is_array($value) || is_object($value)) {
                continue;
            }

            $normalized[(string) $key] = (string) $value;
        }

        return $normalized;
    }

    /**
     * @param  array<string, mixed>|null  $response
     */
    private function isInvalidTokenResponse(?array $response): bool
    {
        $errorCode = data_get($response, 'error.details.0.errorCode');
        $status = data_get($response, 'error.status');

        return in_array($errorCode, ['UNREGISTERED', 'INVALID_ARGUMENT'], true)
            || in_array($status, ['NOT_FOUND', 'INVALID_ARGUMENT'], true);
    }

    private function isConfigured(): bool
    {
        return (bool) config('services.firebase.enabled')
            && filled(config('services.firebase.project_id'))
            && filled(config('services.firebase.service_account_email'))
            && filled(config('services.firebase.private_key'));
    }
}
