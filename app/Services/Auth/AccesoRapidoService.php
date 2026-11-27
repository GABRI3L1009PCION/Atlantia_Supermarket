<?php

namespace App\Services\Auth;

use App\Jobs\EnviarWhatsApp;
use App\Models\User;
use App\Services\Notificaciones\WhatsAppService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Acceso de clientes sin contrasena: codigo por WhatsApp o cuenta de Google.
 *
 * Solo aplica a cuentas de cliente. Las cuentas con roles internos (admin, vendedor,
 * repartidor, empleados) o con verificacion en dos pasos deben entrar con contrasena.
 */
class AccesoRapidoService
{
    private const MINUTOS_CODIGO = 10;

    private const INTENTOS_CODIGO = 5;

    /**
     * Envia un codigo de 6 digitos al WhatsApp del telefono.
     */
    public function enviarCodigo(string $telefono, string $ip): string
    {
        $numero = WhatsAppService::normalizarTelefono($telefono)
            ?? throw new RuntimeException('Ingresa un número de Guatemala válido, por ejemplo 5555 1234.');

        foreach (["acceso-tel:{$numero}" => 3, "acceso-ip:{$ip}" => 10] as $clave => $maximo) {
            if (RateLimiter::tooManyAttempts($clave, $maximo)) {
                throw new RuntimeException('Pediste varios códigos seguidos. Espera unos minutos e inténtalo de nuevo.');
            }
            RateLimiter::hit($clave, 600);
        }

        $usuario = $this->usuarioPorTelefono($numero);
        if ($usuario !== null && $this->requiereContrasena($usuario)) {
            throw new RuntimeException('Esta cuenta debe entrar con correo y contraseña.');
        }

        $codigo = (string) random_int(100000, 999999);
        Cache::put($this->claveCodigo($numero), ['hash' => Hash::make($codigo), 'intentos' => 0], now()->addMinutes(self::MINUTOS_CODIGO));

        EnviarWhatsApp::dispatch(
            $numero,
            'codigo_acceso',
            [$codigo],
            "Tu código de acceso a Atlantia Store es {$codigo}. Vence en ".self::MINUTOS_CODIGO.' minutos. No lo compartas con nadie.'
        );

        return $numero;
    }

    /**
     * Valida el codigo y devuelve el usuario (lo crea si es nuevo).
     */
    public function verificarCodigo(string $numero, string $codigo, ?string $nombre): User
    {
        $clave = $this->claveCodigo($numero);
        $registro = Cache::get($clave);

        if (! is_array($registro)) {
            throw new RuntimeException('El código venció. Pide uno nuevo.');
        }

        if ($registro['intentos'] >= self::INTENTOS_CODIGO) {
            Cache::forget($clave);
            throw new RuntimeException('Demasiados intentos. Pide un código nuevo.');
        }

        if (! Hash::check(preg_replace('/\D/', '', $codigo) ?? '', $registro['hash'])) {
            Cache::put($clave, ['hash' => $registro['hash'], 'intentos' => $registro['intentos'] + 1], now()->addMinutes(self::MINUTOS_CODIGO));
            throw new RuntimeException('El código no es correcto.');
        }

        Cache::forget($clave);

        $usuario = $this->usuarioPorTelefono($numero);
        if ($usuario !== null) {
            if ($this->requiereContrasena($usuario)) {
                throw new RuntimeException('Esta cuenta debe entrar con correo y contraseña.');
            }

            return $usuario;
        }

        $nombre = trim((string) $nombre);
        if ($nombre === '') {
            throw new RuntimeException('Escribe tu nombre para crear tu cuenta.');
        }

        return $this->crearCliente($nombre, "tel-{$numero}@clientes.atlantia.local", ['phone' => substr($numero, 3)]);
    }

    /**
     * Inicia sesion o registra con los datos que devuelve Google.
     */
    public function usuarioDeGoogle(string $googleId, ?string $email, ?string $nombre): User
    {
        $usuario = User::query()->where('google_id', $googleId)->first();

        if ($usuario === null && $email !== null) {
            $usuario = User::query()->where('email', Str::lower($email))->first();
        }

        if ($usuario !== null) {
            if ($this->requiereContrasena($usuario)) {
                throw new RuntimeException('Esta cuenta debe entrar con correo y contraseña.');
            }
            $usuario->forceFill(['google_id' => $googleId, 'email_verified_at' => $usuario->email_verified_at ?? now()])->save();

            return $usuario;
        }

        if ($email === null) {
            throw new RuntimeException('Google no compartió tu correo. Intenta con otra opción.');
        }

        return $this->crearCliente($nombre ?: Str::before($email, '@'), Str::lower($email), ['google_id' => $googleId]);
    }

    public function requiereContrasena(User $usuario): bool
    {
        return $usuario->two_factor_enabled
            || $usuario->roles()->where('name', '!=', 'cliente')->exists()
            || $usuario->status !== 'active';
    }

    private function usuarioPorTelefono(string $numero): ?User
    {
        $local = substr($numero, 3);

        return User::query()
            ->where('phone', 'like', '%'.substr($local, -4))
            ->where('email', 'not like', '%@invitados.atlantia.local')
            ->get()
            ->first(fn (User $user): bool => WhatsAppService::normalizarTelefono($user->phone) === $numero);
    }

    /**
     * @param  array<string, mixed>  $extra
     */
    private function crearCliente(string $nombre, string $email, array $extra): User
    {
        $usuario = User::query()->create([
            'uuid' => (string) Str::uuid(),
            'name' => mb_substr($nombre, 0, 160),
            'email' => $email,
            'password' => Hash::make(Str::random(40)),
            'status' => 'active',
        ]);
        $usuario->forceFill([...$extra, 'email_verified_at' => now()])->save();
        $usuario->assignRole('cliente');

        return $usuario;
    }

    private function claveCodigo(string $numero): string
    {
        return "acceso-codigo:{$numero}";
    }
}
