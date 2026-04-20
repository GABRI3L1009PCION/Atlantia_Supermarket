<?php

namespace App\Services\Inventario;

use App\Models\Inventario;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

/**
 * Servicio para detectar productos con riesgo de quiebre de stock.
 */
class AlertaStockService
{
    /**
     * Lista alertas de stock para el vendedor autenticado.
     *
     * @param User $user
     * @return LengthAwarePaginator
     */
    public function forVendor(User $user): LengthAwarePaginator
    {
        return $this->queryBase()
            ->whereHas('producto', fn ($query) => $query->where('vendor_id', $user->vendor?->id))
            ->paginate(25);
    }

    /**
     * Lista alertas globales para administracion.
     *
     * @return LengthAwarePaginator
     */
    public function global(): LengthAwarePaginator
    {
        return $this->queryBase()->paginate(50);
    }

    /**
     * Calcula datos de alerta para un inventario especifico.
     *
     * @param Inventario $inventario
     * @return array<string, mixed>
     */
    public function evaluate(Inventario $inventario): array
    {
        $disponible = max(0, $inventario->stock_actual - $inventario->stock_reservado);
        $deficit = max(0, $inventario->stock_minimo - $disponible);

        return [
            'inventario_id' => $inventario->id,
            'producto_id' => $inventario->producto_id,
            'stock_actual' => $inventario->stock_actual,
            'stock_reservado' => $inventario->stock_reservado,
            'stock_disponible' => $disponible,
            'stock_minimo' => $inventario->stock_minimo,
            'deficit' => $deficit,
            'urgencia' => $this->urgencia($inventario, $disponible),
            'requiere_reabasto' => $disponible <= $inventario->stock_minimo,
        ];
    }

    /**
     * Cuenta productos bajo minimo por vendedor.
     *
     * @param Vendor $vendor
     * @return int
     */
    public function countForVendor(Vendor $vendor): int
    {
        return $this->queryBase()
            ->whereHas('producto', fn ($query) => $query->where('vendor_id', $vendor->id))
            ->count();
    }

    /**
     * Query base para inventarios en alerta.
     *
     * @return Builder<Inventario>
     */
    private function queryBase(): Builder
    {
        return Inventario::query()
            ->with(['producto.vendor', 'producto.categoria'])
            ->whereRaw('(stock_actual - stock_reservado) <= stock_minimo')
            ->orderBy('stock_actual')
            ->orderByDesc('ultima_actualizacion');
    }

    /**
     * Determina urgencia operativa segun disponibilidad.
     *
     * @param Inventario $inventario
     * @param int $disponible
     * @return string
     */
    private function urgencia(Inventario $inventario, int $disponible): string
    {
        if ($disponible <= 0) {
            return 'critica';
        }

        if ($inventario->stock_minimo > 0 && $disponible <= (int) ceil($inventario->stock_minimo * 0.5)) {
            return 'alta';
        }

        return 'media';
    }
}
