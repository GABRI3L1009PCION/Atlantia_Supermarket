<?php

namespace App\Services\Clientes;

use App\Models\User;
use Illuminate\Support\Carbon;

/**
 * Membresia de envio gratis (pago mensual por suscripcion de Recurrente).
 */
class MembresiaService
{
    /**
     * La membresia se ofrece y se puede contratar (ATLANTIA_MEMBRESIA_ACTIVA).
     */
    public static function habilitada(): bool
    {
        return (bool) config('atlantia.membresia.activa', false);
    }

    public static function precio(): float
    {
        return (float) config('atlantia.membresia.precio', 49);
    }

    public static function minimoEnvioGratis(): float
    {
        return (float) config('atlantia.membresia.minimo_envio_gratis', 100);
    }

    public function activa(?User $user): bool
    {
        return $user?->membresia_hasta !== null && Carbon::parse($user->membresia_hasta)->isFuture();
    }

    /**
     * El envio sale gratis para miembros con compras desde el minimo.
     */
    public function envioGratis(?User $user, float $subtotal): bool
    {
        return $this->activa($user) && $subtotal >= self::minimoEnvioGratis();
    }

    /**
     * Extiende la membresia un mes desde hoy o desde su vencimiento, lo que sea mayor.
     */
    public function extenderUnMes(User $user, ?string $suscripcionId = null): void
    {
        $desde = $this->activa($user) ? Carbon::parse($user->membresia_hasta) : now();

        $user->forceFill([
            'membresia_hasta' => $desde->copy()->addMonthNoOverflow(),
            'membresia_suscripcion_id' => $suscripcionId ?? $user->membresia_suscripcion_id,
        ])->save();
    }
}
