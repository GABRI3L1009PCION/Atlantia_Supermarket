<?php

namespace App\Support;

use App\Models\Vendor;
use Illuminate\Support\Collection;

/**
 * Pedido minimo por tienda. Cada tienda es una entrega aparte, asi que el minimo se mide
 * con lo que el cliente lleva de esa tienda. Aplica igual con cualquier forma de pago.
 */
final class PedidoMinimo
{
    /**
     * Tiendas del carrito que aun no llegan a su minimo.
     *
     * @param  Collection<int, mixed>  $items  items del carrito (con producto.vendor)
     * @return Collection<int, array{vendor: Vendor|null, minimo: float, subtotal: float, falta: float}>
     */
    public static function pendientes(Collection $items): Collection
    {
        return $items
            ->groupBy(fn ($item) => (int) ($item->producto?->vendor_id ?? 0))
            ->map(function (Collection $grupo): array {
                $vendor = $grupo->first()?->producto?->vendor;
                $minimo = round((float) ($vendor?->pedido_minimo ?? 0), 2);
                $subtotal = round((float) $grupo->sum(fn ($item) => (float) $item->precio_unitario_snapshot * (int) $item->cantidad), 2);

                return [
                    'vendor' => $vendor,
                    'minimo' => $minimo,
                    'subtotal' => $subtotal,
                    'falta' => round(max(0, $minimo - $subtotal), 2),
                ];
            })
            ->filter(fn (array $grupo): bool => $grupo['falta'] > 0)
            ->values();
    }

    /**
     * Mensaje amable para el cliente.
     *
     * @param  array{vendor: Vendor|null, minimo: float, subtotal: float, falta: float}  $grupo
     */
    public static function mensaje(array $grupo): string
    {
        $tienda = $grupo['vendor']?->business_name ?? 'Atlantia Store';

        return 'Agrega Q '.number_format($grupo['falta'], 2)." más de {$tienda} para completar su pedido mínimo de Q ".number_format($grupo['minimo'], 2).'.';
    }
}
