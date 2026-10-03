<?php

namespace App\Http\Controllers\Api\Repartidor;

use App\Http\Controllers\Controller;
use App\Models\CourierDevice;
use App\Models\User;
use App\Services\Repartidores\MobileRepartidorPayloadService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Autenticacion API para la app movil del repartidor.
 */
class AuthController extends Controller
{
    public function __construct(private readonly MobileRepartidorPayloadService $payload) {}

    public function login(Request $request): JsonResponse
    {
        $data = $request->validate([
            'email' => ['required', 'email:rfc', 'max:190'],
            'password' => ['required', 'string', 'max:255'],
            'device_name' => ['nullable', 'string', 'max:120'],
        ]);

        $key = Str::lower((string) $data['email']).'|mobile|'.$request->ip();

        if (RateLimiter::tooManyAttempts($key, 5)) {
            throw ValidationException::withMessages([
                'email' => 'Demasiados intentos. Intenta nuevamente en unos minutos.',
            ]);
        }

        /** @var User|null $user */
        $user = User::query()->where('email', $data['email'])->first();

        if ($user === null || ! Hash::check((string) $data['password'], (string) $user->password)) {
            RateLimiter::hit($key, 900);
            throw ValidationException::withMessages([
                'email' => 'Credenciales invalidas.',
            ]);
        }

        if ($user->status !== 'active') {
            return response()->json(['message' => 'La cuenta no esta activa.'], 403);
        }

        if (! $user->hasRole('repartidor')) {
            return response()->json(['message' => 'Esta app es solo para repartidores.'], 403);
        }

        if (! $user->hasVerifiedEmail()) {
            return response()->json(['message' => 'Debes verificar tu correo antes de usar la app.'], 403);
        }

        RateLimiter::clear($key);
        $user->forceFill([
            'last_login_at' => now(),
            'last_login_ip' => $request->ip(),
        ])->save();

        $deviceName = $data['device_name'] ?? 'Atlantia Repartidor Android';

        $user->tokens()
            ->where('name', $deviceName)
            ->where('revoked', false)
            ->update([
                'revoked' => true,
            ]);

        $token = $user->createToken($deviceName)->accessToken;

        return response()->json([
            'message' => 'Sesion iniciada.',
            'data' => [
                'token_type' => 'Bearer',
                'access_token' => $token,
                'user' => $this->payload->me($user),
            ],
        ]);
    }

    public function me(Request $request): JsonResponse
    {
        return response()->json([
            'message' => 'Perfil obtenido.',
            'data' => $this->payload->me($request->user()),
        ]);
    }

    public function registerDevice(Request $request): JsonResponse
    {
        $data = $request->validate([
            'platform' => ['required', 'in:android,ios,web'],
            'device_uuid' => ['required', 'string', 'max:120'],
            'device_name' => ['nullable', 'string', 'max:120'],
            'app_version' => ['nullable', 'string', 'max:40'],
            'push_provider' => ['nullable', 'in:fcm,none'],
            'push_token' => ['nullable', 'string', 'max:4096'],
            'notifications_enabled' => ['nullable', 'boolean'],
        ]);

        $device = CourierDevice::query()->firstOrNew([
            'user_id' => $request->user()->id,
            'device_uuid' => $data['device_uuid'],
        ]);
        $device->fill([
            'uuid' => $device->uuid ?? (string) Str::uuid(),
            'platform' => $data['platform'],
            'device_name' => $data['device_name'] ?? null,
            'app_version' => $data['app_version'] ?? null,
            'push_provider' => $data['push_provider'] ?? null,
            'push_token' => $data['push_token'] ?? null,
            'notifications_enabled' => (bool) ($data['notifications_enabled'] ?? false),
            'last_seen_at' => now(),
        ])->save();

        return response()->json([
            'message' => 'Dispositivo registrado.',
            'data' => [
                'id' => $device->uuid,
                'platform' => $device->platform,
                'device_uuid' => $device->device_uuid,
                'notifications_enabled' => (bool) $device->notifications_enabled,
                'last_seen_at' => $device->last_seen_at?->toIso8601String(),
            ],
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->token()?->revoke();

        return response()->json(['message' => 'Sesion cerrada.']);
    }
}
