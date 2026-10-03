<?php

namespace App\Services\Ml;

use App\Exceptions\MlServiceUnavailableException;
use App\Models\Ml\RestockSuggestion;
use App\Models\PedidoItem;
use App\Models\Producto;
use App\Models\Vendor;
use App\Services\Ml\Fallback\ReglaSimpleReabastoService;
use App\Services\Notificaciones\NotificadorSugerenciaMlService;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;

/**
 * Servicio ML de sugerencias de reabastecimiento.
 */
class SugerenciaReabastoService
{
    /**
     * Crea una instancia del servicio.
     */
    public function __construct(
        private readonly MlServiceClient $mlClient,
        private readonly ReglaSimpleReabastoService $reglaSimpleReabastoService
    ) {}

    /**
     * Genera sugerencias para productos de un vendedor.
     *
     * @return EloquentCollection<int, RestockSuggestion>
     */
    public function generarParaVendor(Vendor $vendor): EloquentCollection
    {
        $resultados = new EloquentCollection;

        $vendor->productos()->with('inventario')->active()->chunkById(100, function ($productos) use ($resultados): void {
            foreach ($productos as $producto) {
                $suggestion = $this->generarParaProducto($producto);

                if ($suggestion !== null) {
                    $resultados->push($suggestion);
                }
            }
        });

        return $resultados;
    }

    /**
     * Genera una sugerencia para un producto.
     */
    public function generarParaProducto(Producto $producto): ?RestockSuggestion
    {
        $producto->loadMissing('inventario');

        if ($producto->inventario === null) {
            return null;
        }

        try {
            $resultado = $this->mlClient->post('/restock/suggest', [
                'producto_id' => $producto->id,
                'vendor_id' => $producto->vendor_id,
                'stock_actual' => $producto->inventario->stock_actual,
                'stock_minimo' => $producto->inventario->stock_minimo,
                'ventas_promedio_diarias' => $this->ventasPromedioDiarias($producto),
                'lead_time_dias' => 3,
            ]);
        } catch (MlServiceUnavailableException) {
            $resultado = $this->reglaSimpleReabastoService->calcular($producto->inventario);
        }

        if ((int) ($resultado['stock_sugerido'] ?? 0) <= 0) {
            return null;
        }

        $suggestion = RestockSuggestion::query()->updateOrCreate(
            ['producto_id' => $producto->id, 'vendor_id' => $producto->vendor_id, 'aceptada' => false],
            [
                'stock_actual' => $producto->inventario->stock_actual,
                'stock_sugerido' => $resultado['stock_sugerido'],
                'dias_hasta_quiebre' => $resultado['dias_hasta_quiebre'] ?? null,
                'urgencia' => $resultado['urgencia'] ?? 'media',
                'modelo_version_id' => $resultado['modelo_version_id'] ?? null,
            ]
        );

        if (in_array($suggestion->urgencia, ['alta', 'critica'], true)) {
            app(NotificadorSugerenciaMlService::class)->sugerenciaReabasto($suggestion);
        }

        return $suggestion;
    }

    /**
     * Promedio diario de unidades vendidas en los ultimos 30 dias.
     */
    private function ventasPromedioDiarias(Producto $producto): float
    {
        $ventas = PedidoItem::query()
            ->where('producto_id', $producto->id)
            ->where('created_at', '>=', now()->subDays(30))
            ->sum('cantidad');

        return round(((float) $ventas) / 30, 2);
    }
}
