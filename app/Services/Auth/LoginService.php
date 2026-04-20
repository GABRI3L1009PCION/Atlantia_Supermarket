<?php

namespace App\Services\Auth;

use App\Models\AuditLog;
use App\Models\LoginAttempt;
use App\Models\User;
use App\Services\Carrito\CarritoService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Servicio de autenticacion de usuarios.
 */
class LoginService
{
    /**
     * Maximo de intentos de login permitidos.
     */
    private const MAX_ATTEMPTS = 5;

    /**
     * Crea una instancia del servicio.
     */
    public function __construct(private readonly CarritoService $carritoService)
    {
    }

    /**
     * Autentica al usuario y devuelve la ruta destino.
     *
     * @param array<string, mixed> $credentials
     * @param Request $request
     * @return string
     */
    public function authenticate(array $credentials, Request $request): string
    {
        $key = $this->throttleKey($credentials['email'] ?? '', $request);
        $guestSessionId = $request->session()->getId();

        if (RateLimiter::tooManyAttempts($key, self::MAX_ATTEMPTS)) {
            $this->recordAttempt($credentials['email'] ?? '', $request, false, 'rate_limited');
            throw new RuntimeException('Demasiados intentos de inicio de sesion.');
        }

        if (! Auth::attempt($this->onlyCredentials($credentials), (bool) ($credentials['remember'] ?? false))) {
            RateLimiter::hit($key, 900);
            $this->recordAttempt($credentials['email'] ?? '', $request, false, 'invalid_credentials');
            throw new RuntimeException('Credenciales invalidas.');
        }

        RateLimiter::clear($key);
        $request->session()->regenerate();
        /** @var User $user */
        $user = $request->user();
        $this->carritoService->mergeGuestCartIntoUser($guestSessionId, $user);
        $this->registerSuccessfulLogin($user, $request);

        if ($user->two_factor_enabled) {
            $request->session()->put('auth.2fa_user_id', $user->id);

            return 'two-factor.challenge';
        }

        return $this->redirectRouteFor($user);
    }

    /**
     * Cierra la sesion actual.
     *
     * @param Request $request
     */
    public function logout(Request $request): void
    {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
    }

    /**
     * Extrae credenciales validas para Auth::attempt.
     *
     * @param array<string, mixed> $credentials
     * @return array<string, mixed>
     */
    private function onlyCredentials(array $credentials): array
    {
        return [
            'email' => $credentials['email'] ?? '',
            'password' => $credentials['password'] ?? '',
            'status' => 'active',
        ];
    }

    /**
     * Registra un login exitoso.
     *
     * @param User $user
     * @param Request $request
     */
    private function registerSuccessfulLogin(User $user, Request $request): void
    {
        $user->forceFill([
            'last_login_at' => now(),
            'last_login_ip' => $request->ip(),
        ])->save();

        $this->recordAttempt($user->email, $request, true, null, $user);
        $this->audit($user, 'auth.login_success', $request);
    }

    /**
     * Registra un intento de login cuando la tabla existe en el dominio.
     *
     * @param string $email
     * @param Request $request
     * @param bool $successful
     * @param string|null $failureReason
     * @param User|null $user
     */
    private function recordAttempt(
        string $email,
        Request $request,
        bool $successful,
        ?string $failureReason,
        ?User $user = null
    ): void {
        if (! class_exists(LoginAttempt::class)) {
            return;
        }

        LoginAttempt::query()->create([
            'user_id' => $user?->id,
            'email' => $email,
            'ip_address' => (string) $request->ip(),
            'user_agent' => $request->userAgent(),
            'successful' => $successful,
            'failure_reason' => $failureReason,
            'attempted_at' => now(),
        ]);
    }

    /**
     * Genera la llave de throttling.
     *
     * @param string $email
     * @param Request $request
     * @return string
     */
    private function throttleKey(string $email, Request $request): string
    {
        return Str::lower($email) . '|' . $request->ip();
    }

    /**
     * Determina la ruta de destino segun el rol.
     *
     * @param User $user
     * @return string
     */
    private function redirectRouteFor(User $user): string
    {
        return match (true) {
            $user->hasRole('admin') => 'admin.dashboard',
            $user->hasRole('vendedor') => 'vendedor.dashboard',
            $user->hasRole('repartidor') => 'repartidor.dashboard',
            $user->hasRole('empleado') => 'empleado.dashboard',
            default => 'catalogo.index',
        };
    }

    /**
     * Registra auditoria de autenticacion.
     *
     * @param User $user
     * @param string $event
     * @param Request $request
     */
    private function audit(User $user, string $event, Request $request): void
    {
        AuditLog::query()->create([
            'uuid' => (string) Str::uuid(),
            'user_id' => $user->id,
            'event' => $event,
            'auditable_type' => User::class,
            'auditable_id' => $user->id,
            'metadata' => ['ip' => $request->ip()],
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'request_id' => $request->headers->get('X-Request-Id'),
            'url' => $request->fullUrl(),
            'method' => $request->method(),
        ]);
    }
}
