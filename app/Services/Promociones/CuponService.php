<?php

namespace App\Services\Promociones;

use App\Models\Cupon;
use App\Models\CuponUso;
use App\Models\Pedido;
use App\Models\User;

/**
 * Servicio de validacion y aplicacion de cupones.
 */
class CuponService
{
    /**
     * Valida un cupon y calcula el descuento aplicable.
     *
     * @return array<string, mixed>
     */
    public function resolver(?User $user, ?string $codigo, float $subtotal): array
    {
        $codigo = strtoupper(trim((string) $codigo));

        if ($codigo === '') {
            return [
                'valido' => false,
                'mensaje' => 'Ingresa un codigo para validar el descuento.',
                'descuento' => 0.0,
                'cupon' => null,
            ];
        }

        $cupon = Cupon::query()
            ->vigentes()
            ->where('codigo', $codigo)
            ->first();

        if ($cupon === null) {
            return [
                'valido' => false,
                'mensaje' => 'El cupon no existe o ya no esta disponible.',
                'descuento' => 0.0,
                'cupon' => null,
            ];
        }

        if ($subtotal < (float) $cupon->minimo_compra) {
            return [
                'valido' => false,
                'mensaje' => 'Tu compra aun no alcanza el minimo requerido para este cupon.',
                'descuento' => 0.0,
                'cupon' => $cupon,
            ];
        }

        if ($cupon->usos_maximos !== null && $cupon->usos_actuales >= $cupon->usos_maximos) {
            return [
                'valido' => false,
                'mensaje' => 'Este cupon ya alcanzo su limite de uso.',
                'descuento' => 0.0,
                'cupon' => $cupon,
            ];
        }

        if (
            $user !== null
            && $cupon->solo_primera_compra
            && Pedido::query()->where('cliente_id', $user->id)->whereNull('pedido_padre_id')->exists()
        ) {
            return [
                'valido' => false,
                'mensaje' => 'Este cupon solo aplica para tu primera compra.',
                'descuento' => 0.0,
                'cupon' => $cupon,
            ];
        }

        if (
            $user !== null
            && CuponUso::query()->where('cupon_id', $cupon->id)->where('user_id', $user->id)->exists()
            && $cupon->solo_primera_compra
        ) {
            return [
                'valido' => false,
                'mensaje' => 'Este cupon ya fue utilizado en tu cuenta.',
                'descuento' => 0.0,
                'cupon' => $cupon,
            ];
        }

        $descuento = $cupon->tipo === 'porcentaje'
            ? round($subtotal * (((float) $cupon->valor) / 100), 2)
            : round((float) $cupon->valor, 2);

        if ($cupon->maximo_descuento !== null) {
            $descuento = min($descuento, (float) $cupon->maximo_descuento);
        }

        $descuento = min($descuento, $subtotal);

        return [
            'valido' => true,
            'mensaje' => "Cupon {$cupon->codigo} aplicado correctamente.",
            'descuento' => $descuento,
            'cupon' => $cupon,
        ];
    }

    /**
     * Registra el uso definitivo del cupon.
     */
    public function registrarUso(Cupon $cupon, User $user, Pedido $pedido): void
    {
        CuponUso::query()->create([
            'cupon_id' => $cupon->id,
            'user_id' => $user->id,
            'pedido_id' => $pedido->id,
        ]);

        $cupon->increment('usos_actuales');
    }
}
