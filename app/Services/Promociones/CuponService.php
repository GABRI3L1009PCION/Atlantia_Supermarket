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
    public function resolver(?User $user, ?string $codigo, float $subtotal, array $subtotalPorVendor = []): array
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

        /* Cupón de una tienda: solo cuenta lo que el cliente lleva de esa tienda. */
        if ($cupon->vendor_id !== null && $subtotalPorVendor !== []) {
            $subtotal = (float) ($subtotalPorVendor[(int) $cupon->vendor_id] ?? 0);

            if ($subtotal <= 0) {
                return [
                    'valido' => false,
                    'mensaje' => 'Este cupón es de '.($cupon->vendor?->business_name ?? 'otra tienda').' y tu carrito no tiene productos de esa tienda.',
                    'descuento' => 0.0,
                    'cupon' => $cupon,
                ];
            }
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

        /* Cupón de una tienda: "primera compra" significa primera compra en esa tienda. */
        $yaCompro = $user !== null && $cupon->solo_primera_compra && Pedido::query()
            ->where('cliente_id', $user->id)
            ->whereNotIn('estado', ['cancelado', 'rechazado'])
            ->when($cupon->vendor_id !== null, fn ($q) => $q->where('vendor_id', $cupon->vendor_id), fn ($q) => $q->whereNull('pedido_padre_id'))
            ->exists();

        if ($yaCompro) {
            return [
                'valido' => false,
                'mensaje' => $cupon->vendor_id !== null ? 'Este cupón es solo para tu primera compra en esta tienda.' : 'Este cupon solo aplica para tu primera compra.',
                'descuento' => 0.0,
                'cupon' => $cupon,
            ];
        }

        if (
            $user !== null
            && ($cupon->solo_primera_compra || $cupon->un_uso_por_cliente)
            && CuponUso::query()->where('cupon_id', $cupon->id)->where('user_id', $user->id)->exists()
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
            'mensaje' => $cupon->vendor_id !== null && $subtotalPorVendor !== []
                ? "Cupón {$cupon->codigo} aplicado a tus productos de ".($cupon->vendor?->business_name ?? 'la tienda').'.'
                : "Cupón {$cupon->codigo} aplicado correctamente.",
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
