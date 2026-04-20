<?php

namespace App\Services\Ml\Fallback;

use App\Models\PedidoItem;
use App\Models\Producto;

/**
 * Fallback estadistico simple para prediccion de demanda.
 */
class FallbackPrediccionService
{
    /**
     * Predice demanda con promedio diario historico.
     *
     * @param Producto $producto
     * @param int $horizonteDias
     * @return array<string, mixed>
     */
    public function predecir(Producto $producto, int $horizonteDias): array
    {
        $ventas = PedidoItem::query()
            ->where('producto_id', $producto->id)
            ->where('created_at', '>=', now()->subDays(30))
            ->sum('cantidad');
        $promedioDiario = max(0.2, ((float) $ventas) / 30);
        $valor = round($promedioDiario * $horizonteDias, 2);

        return [
            'valor_predicho' => $valor,
            'intervalo_inferior' => max(0, round($valor * 0.75, 2)),
            'intervalo_superior' => round($valor * 1.35, 2),
            'modelo_version_id' => null,
            'algoritmo' => 'fallback_promedio_30d',
        ];
    }
}
