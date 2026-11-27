<?php

namespace App\Support;

use App\Models\Producto;

/**
 * Margen de un producto propio de Atlantia Store sobre su precio de venta (ambos con IVA).
 * El margen sugerido cubre la tarifa de la tarjeta en un pedido minimo y deja para gastos.
 */
final class MargenProducto
{
    /**
     * @return array{porcentaje: float, ganancia: float, sugerido: float, suficiente: bool}|null
     */
    public static function calcular(Producto $producto): ?array
    {
        $costo = $producto->costo_compra !== null ? (float) $producto->costo_compra : null;
        $precio = (float) ($producto->precio_oferta ?? $producto->precio_base);

        if ($costo === null || $precio <= 0) {
            return null;
        }

        $ganancia = round($precio - $costo, 2);
        $porcentaje = round(($ganancia / $precio) * 100, 1);
        $sugerido = self::sugerido();

        return [
            'porcentaje' => $porcentaje,
            'ganancia' => $ganancia,
            'sugerido' => $sugerido,
            'suficiente' => $porcentaje >= $sugerido,
        ];
    }

    /**
     * Margen sugerido sobre el precio de venta (porcentaje).
     */
    public static function sugerido(): float
    {
        return (float) config('monetizacion.margen_sugerido', 15);
    }
}
