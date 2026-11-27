<?php

namespace App\Services\Promociones;

use App\Models\Pedido;
use App\Models\Producto;
use App\Models\Promocion;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * Calcula los descuentos automaticos: promociones (2x1, 3x2, porcentaje) y primera compra.
 */
class PromocionService
{
    /** @var Collection<int, Promocion>|null */
    private ?Collection $vigentes = null;

    /**
     * Descuento por promociones del carrito, total y por comercio.
     *
     * @param  iterable<int, mixed>  $items  Items con ->producto y ->cantidad.
     * @return array{total: float, por_vendor: array<int|string, float>, detalle: array<int, array{nombre: string, etiqueta: string, descuento: float}>}
     */
    public function descuentoCarrito(iterable $items): array
    {
        $resultado = ['total' => 0.0, 'por_vendor' => [], 'detalle' => []];

        foreach ($items as $item) {
            $producto = $item->producto;
            if ($producto === null) {
                continue;
            }

            [$promocion, $descuento] = $this->mejorPromocion($producto, (int) $item->cantidad);
            if ($promocion === null || $descuento <= 0) {
                continue;
            }

            $vendor = $producto->vendor_id ?? 0;
            $resultado['total'] += $descuento;
            $resultado['por_vendor'][$vendor] = round(($resultado['por_vendor'][$vendor] ?? 0) + $descuento, 2);
            $resultado['detalle'][] = [
                'nombre' => $promocion->nombre.' · '.$producto->nombre,
                'etiqueta' => $promocion->etiqueta(),
                'descuento' => $descuento,
            ];
        }

        $resultado['total'] = round($resultado['total'], 2);

        return $resultado;
    }

    /**
     * Promocion vigente con mejor descuento para un producto (sin cantidad: para mostrar la etiqueta).
     */
    public function promocionPara(Producto $producto): ?Promocion
    {
        return $this->candidatas($producto)->sortByDesc(
            fn (Promocion $p): float => $p->tipo === 'nxm' ? (1 - $p->paga / max(1, $p->lleva)) * 100 : (float) $p->porcentaje
        )->first();
    }

    /**
     * Descuento automatico de primera compra para cuentas registradas.
     */
    public function descuentoPrimeraCompra(?User $user, float $base): float
    {
        $config = config('atlantia.primera_compra', []);
        $porcentaje = (float) ($config['porcentaje'] ?? 0);

        if ($user === null || $porcentaje <= 0 || $base < (float) ($config['minimo'] ?? 0)
            || str_ends_with((string) $user->email, '@invitados.atlantia.local')) {
            return 0.0;
        }

        $tieneCompras = Pedido::query()
            ->where('cliente_id', $user->id)
            ->whereNull('pedido_padre_id')
            ->whereNotIn('estado', ['cancelado', 'rechazado'])
            ->exists();

        return $tieneCompras ? 0.0 : round(min($base * $porcentaje / 100, (float) ($config['tope'] ?? $base)), 2);
    }

    /**
     * @return array{0: ?Promocion, 1: float}
     */
    private function mejorPromocion(Producto $producto, int $cantidad): array
    {
        $precio = (float) ($producto->precio_oferta ?? $producto->precio_base);
        $mejor = [null, 0.0];

        foreach ($this->candidatas($producto) as $promocion) {
            $descuento = $promocion->tipo === 'nxm'
                ? $this->descuentoNxM($promocion, $cantidad, $precio)
                : round($precio * $cantidad * ((float) $promocion->porcentaje / 100), 2);

            if ($descuento > $mejor[1]) {
                $mejor = [$promocion, $descuento];
            }
        }

        return $mejor;
    }

    private function descuentoNxM(Promocion $promocion, int $cantidad, float $precio): float
    {
        $lleva = max(2, (int) $promocion->lleva);
        $paga = max(1, min($lleva - 1, (int) $promocion->paga));

        return round(intdiv($cantidad, $lleva) * ($lleva - $paga) * $precio, 2);
    }

    /**
     * @return Collection<int, Promocion>
     */
    private function candidatas(Producto $producto): Collection
    {
        $this->vigentes ??= Promocion::query()->vigentes()->get();

        return $this->vigentes->filter(function (Promocion $promocion) use ($producto): bool {
            if ($promocion->vendor_id !== null && (int) $promocion->vendor_id !== (int) $producto->vendor_id) {
                return false;
            }

            if ($promocion->producto_id !== null) {
                return (int) $promocion->producto_id === (int) $producto->id;
            }

            if ($promocion->categoria_id !== null) {
                return (int) $promocion->categoria_id === (int) $producto->categoria_id;
            }

            /* Sin producto ni categoria: aplica a toda la tienda del comercio (o a todo, si es de plataforma). */
            return true;
        })->values();
    }
}
