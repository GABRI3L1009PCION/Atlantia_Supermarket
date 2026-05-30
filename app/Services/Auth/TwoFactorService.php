<?php

namespace App\Services\Auth;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

/**
 * Servicio de autenticacion de dos factores.
 */
class TwoFactorService
{
    private const MAX_FAILED_CHALLENGES = 5;
    private const LOCK_MINUTES = 15;

    /**
     * Crea una instancia del servicio.
     */
    public function __construct(
        private readonly LoginService $loginService,
        private readonly TotpService $totpService
    ) {
    }

    /**
     * Devuelve los datos de la pantalla 2FA actual.
     *
     * @return array<string, mixed>
     */
    public function challengeData(Request $request): array
    {
        $user = $this->pendingUser($request);

        if ($user === null) {
            return [
                'active' => false,
                'setup_required' => false,
            ];
        }

        $authentication = $this->ensureAuthentication($user);
        $setupRequired = $this->requiresSetup($user, $authentication);
        $secret = decrypt((string) $authentication->secret);

        return [
            'active' => true,
            'setup_required' => $setupRequired,
            'manual_key' => $this->totpService->humanizeSecret($secret),
            'otp_uri' => $this->totpService->provisioningUri($this->issuer(), $user->email, $secret),
            'account_label' => $user->email,
            'locked_until' => $authentication->locked_until,
        ];
    }

    /**
     * Verifica un desafio 2FA y devuelve la ruta destino.
     *
     * @param array<string, mixed> $data
     */
    public function verifyChallenge(array $data, Request $request): string
    {
        $remember = (bool) $request->session()->get('auth.2fa_remember', false);
        $guestSessionId = $request->session()->get('auth.2fa_guest_session_id');
        $user = $this->pendingUser($request);

        if ($user === null) {
            throw new RuntimeException('No existe un desafio 2FA activo.');
        }

        $authentication = $this->ensureAuthentication($user);

        if ($this->isLocked($authentication)) {
            throw new RuntimeException('Tu segundo factor esta bloqueado temporalmente.');
        }

        $setupRequired = $this->requiresSetup($user, $authentication);
        $secret = decrypt((string) $authentication->secret);
        $code = (string) ($data['code'] ?? '');

        if (! $this->totpService->verify($secret, $code)) {
            $this->registerFailedChallenge($user->id);
            throw new RuntimeException('Codigo 2FA invalido.');
        }

        $this->registerSuccessfulChallenge($user->id, $request, $setupRequired);
        $request->session()->forget('auth.2fa_user_id');
        $request->session()->forget('auth.2fa_remember');
        $request->session()->forget('auth.2fa_guest_session_id');
        $request->session()->regenerate();
        $this->audit($user, 'auth.two_factor_verified');

        return $this->loginService->completeAuthenticatedSession(
            $user->fresh(),
            $request,
            $remember,
            is_string($guestSessionId) ? $guestSessionId : null
        );
    }

    /**
     * Activa 2FA para un usuario.
     *
     * @return array<string, mixed>
     */
    public function enable(User $user): array
    {
        return DB::transaction(function () use ($user): array {
            $secret = $this->totpService->generateSecret();
            $recoveryCodes = $this->freshRecoveryCodes();

            $user->forceFill([
                'two_factor_enabled' => true,
                'two_factor_confirmed_at' => null,
            ])->save();

            DB::table('two_factor_authentications')->updateOrInsert(
                ['user_id' => $user->id],
                [
                    'secret' => encrypt($secret),
                    'recovery_codes' => encrypt(json_encode($recoveryCodes, JSON_THROW_ON_ERROR)),
                    'confirmed_at' => null,
                    'failed_challenges' => 0,
                    'locked_until' => null,
                    'last_used_at' => null,
                    'last_used_ip' => null,
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );

            $this->audit($user, 'auth.two_factor_enabled');

            return [
                'secret' => $secret,
                'manual_key' => $this->totpService->humanizeSecret($secret),
                'otp_uri' => $this->totpService->provisioningUri($this->issuer(), $user->email, $secret),
                'recovery_codes' => $recoveryCodes,
            ];
        });
    }

    /**
     * Desactiva 2FA para un usuario.
     */
    public function disable(User $user): void
    {
        DB::transaction(function () use ($user): void {
            $user->forceFill([
                'two_factor_enabled' => false,
                'two_factor_confirmed_at' => null,
            ])->save();

            DB::table('two_factor_authentications')->where('user_id', $user->id)->delete();
            $this->audit($user, 'auth.two_factor_disabled');
        });
    }

    /**
     * Obtiene el usuario pendiente de verificar.
     */
    private function pendingUser(Request $request): ?User
    {
        $userId = $request->session()->get('auth.2fa_user_id');

        if ($userId === null) {
            return null;
        }

        /** @var User|null $user */
        $user = User::query()->find($userId);

        return $user;
    }

    /**
     * Garantiza que el usuario tenga registro 2FA persistido.
     */
    private function ensureAuthentication(User $user): object
    {
        $authentication = $this->authenticationRow($user->id);

        if ($authentication !== null && is_string($authentication->secret) && $authentication->secret !== '') {
            try {
                $secret = decrypt($authentication->secret);

                if (is_string($secret) && preg_match('/^[A-Z2-7]{16,}$/', strtoupper($secret)) === 1) {
                    return $authentication;
                }
            } catch (Throwable) {
                // Regenera el secreto si el registro previo no es compatible con TOTP real.
            }
        }

        $secret = $this->totpService->generateSecret();
        $recoveryCodes = $this->freshRecoveryCodes();

        DB::table('two_factor_authentications')->updateOrInsert(
            ['user_id' => $user->id],
            [
                'secret' => encrypt($secret),
                'recovery_codes' => encrypt(json_encode($recoveryCodes, JSON_THROW_ON_ERROR)),
                'confirmed_at' => null,
                'failed_challenges' => 0,
                'locked_until' => null,
                'last_used_at' => null,
                'last_used_ip' => null,
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );

        $user->forceFill([
            'two_factor_enabled' => true,
            'two_factor_confirmed_at' => null,
        ])->save();

        return $this->authenticationRow($user->id)
            ?? throw new RuntimeException('No fue posible inicializar el segundo factor.');
    }

    /**
     * Determina si la cuenta aun debe completar enrolamiento.
     */
    private function requiresSetup(User $user, object $authentication): bool
    {
        return $user->two_factor_confirmed_at === null || $authentication->confirmed_at === null;
    }

    /**
     * Determina si el usuario esta bloqueado temporalmente.
     */
    private function isLocked(object $authentication): bool
    {
        return $authentication->locked_until !== null && now()->lt($authentication->locked_until);
    }

    /**
     * Registra fallo en el challenge y aplica bloqueo si corresponde.
     */
    private function registerFailedChallenge(int $userId): void
    {
        $authentication = $this->authenticationRow($userId);

        if ($authentication === null) {
            return;
        }

        $failed = ((int) $authentication->failed_challenges) + 1;
        $lockUntil = $failed >= self::MAX_FAILED_CHALLENGES ? now()->addMinutes(self::LOCK_MINUTES) : null;

        DB::table('two_factor_authentications')
            ->where('user_id', $userId)
            ->update([
                'failed_challenges' => $failed,
                'locked_until' => $lockUntil,
                'updated_at' => now(),
            ]);
    }

    /**
     * Marca un challenge como exitoso y confirma el secreto si estaba pendiente.
     */
    private function registerSuccessfulChallenge(int $userId, Request $request, bool $setupRequired): void
    {
        $payload = [
            'failed_challenges' => 0,
            'locked_until' => null,
            'last_used_at' => now(),
            'last_used_ip' => (string) $request->ip(),
            'updated_at' => now(),
        ];

        if ($setupRequired) {
            $payload['confirmed_at'] = now();
        }

        DB::table('two_factor_authentications')
            ->where('user_id', $userId)
            ->update($payload);

        if ($setupRequired) {
            User::query()->whereKey($userId)->update([
                'two_factor_enabled' => true,
                'two_factor_confirmed_at' => now(),
            ]);
        }
    }

    /**
     * Obtiene la fila de autenticacion 2FA del usuario.
     */
    private function authenticationRow(int $userId): ?object
    {
        return DB::table('two_factor_authentications')->where('user_id', $userId)->first();
    }

    /**
     * Genera nuevos codigos de recuperacion.
     *
     * @return array<int, string>
     */
    private function freshRecoveryCodes(): array
    {
        return collect(range(1, 8))
            ->map(fn (): string => Str::upper(Str::random(10)))
            ->all();
    }

    /**
     * Nombre emisor visible en la app autenticadora.
     */
    private function issuer(): string
    {
        return (string) (config('app.name') ?: 'Atlantia Supermarket');
    }

    /**
     * Registra auditoria de 2FA.
     */
    private function audit(User $user, string $event): void
    {
        AuditLog::query()->create([
            'uuid' => (string) Str::uuid(),
            'user_id' => $user->id,
            'event' => $event,
            'auditable_type' => User::class,
            'auditable_id' => $user->id,
            'metadata' => ['security' => '2fa'],
            'method' => 'SERVICE',
        ]);
    }
}
