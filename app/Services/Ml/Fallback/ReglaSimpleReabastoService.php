<?php

namespace App\Services\Ml\Fallback;

use App\Models\Inventario;

/**
 * Regla simple de reabastecimiento cuando ML no responde.
 */
class ReglaSimpleReabastoService
{
    /**
     * Calcula sugerencia usando minimo/maximo configurado.
     *
     * @param Inventario $inventario
     * @return array<string, mixed>
     */
    public function calcular(Inventario $inventario): array
    {
        $disponible = max(0, $inventario->stock_actual - $inventario->stock_reservado);
        $objetivo = $inventario->stock_maximo ?? max($inventario->stock_minimo * 2, $inventario->stock_minimo + 10);

        return [
            'stock_sugerido' => max(0, $objetivo - $disponible),
            'dias_hasta_quiebre' => $disponible <= 0 ? 0 : 7,
            'urgencia' => $disponible <= 0 ? 'critica' : ($disponible <= $inventario->stock_minimo ? 'alta' : 'media'),
            'modelo_version_id' => null,
            'algoritmo' => 'regla_minimo_maximo',
        ];
    }
}
