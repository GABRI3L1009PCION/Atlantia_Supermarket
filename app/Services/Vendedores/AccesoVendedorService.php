<?php

namespace App\Services\Vendedores;

use App\Models\User;
use App\Models\Vendor;
use App\Notifications\SolicitudVendedorRechazadaNotification;
use App\Notifications\VendedorAprobadoNotification;
use Illuminate\Support\Facades\URL;
use Throwable;

/**
 * Da acceso al vendedor aprobado sin enviarle nunca una contraseña por correo.
 *
 * Se envía un enlace firmado para que el vendedor cree su propia contraseña.
 * El enlace vence a las 72 horas y queda inservible en cuanto la contraseña
 * cambia, porque incluye una huella del hash de la contraseña actual.
 */
class AccesoVendedorService
{
    public const HORAS_VIGENCIA = 72;

    /**
     * Enlace de un solo uso para crear la contraseña.
     */
    public function enlaceCrearContrasena(User $user): string
    {
        return URL::temporarySignedRoute(
            'vendedor.acceso.crear',
            now()->addHours(self::HORAS_VIGENCIA),
            ['usuario' => $user->uuid, 'huella' => $this->huella($user)],
        );
    }

    /**
     * Huella corta de la contraseña actual: cambia al crear una nueva.
     */
    public function huella(User $user): string
    {
        return substr(hash_hmac('sha256', (string) $user->password, (string) config('app.key')), 0, 20);
    }

    public function huellaValida(User $user, string $huella): bool
    {
        return hash_equals($this->huella($user), $huella);
    }

    /**
     * Correo de aprobación con el enlace. Devuelve false si no se pudo enviar.
     */
    public function enviarBienvenida(Vendor $vendor): bool
    {
        $user = $vendor->user;

        if ($user === null) {
            return false;
        }

        try {
            $user->notify(new VendedorAprobadoNotification($vendor, $this->enlaceCrearContrasena($user)));

            return true;
        } catch (Throwable $exception) {
            report($exception);

            return false;
        }
    }

    /**
     * Correo de rechazo con el motivo. Devuelve false si no se pudo enviar.
     */
    public function enviarRechazo(Vendor $vendor): bool
    {
        try {
            $vendor->user?->notify(new SolicitudVendedorRechazadaNotification($vendor));

            return $vendor->user !== null;
        } catch (Throwable $exception) {
            report($exception);

            return false;
        }
    }

    /**
     * Correo enmascarado para mostrar en páginas públicas (lu*****98@gmail.com).
     */
    public static function correoEnmascarado(?string $correo): string
    {
        if ($correo === null || ! str_contains($correo, '@')) {
            return 'tu correo';
        }

        [$usuario, $dominio] = explode('@', $correo, 2);
        $visible = mb_strlen($usuario) <= 4 ? mb_substr($usuario, 0, 1) : mb_substr($usuario, 0, 2);
        $final = mb_strlen($usuario) > 4 ? mb_substr($usuario, -2) : '';

        return $visible.str_repeat('*', max(3, mb_strlen($usuario) - mb_strlen($visible) - mb_strlen($final))).$final.'@'.$dominio;
    }
}
