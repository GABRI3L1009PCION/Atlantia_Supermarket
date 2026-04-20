<?php

namespace App\Services\Ml;

use App\Exceptions\MlServiceUnavailableException;
use App\Models\Ml\RestockSuggestion;
use App\Models\Producto;
use App\Models\Vendor;
use App\Services\Ml\Fallback\ReglaSimpleReabastoService;
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
    ) {
    }

    /**
     * Genera sugerencias para productos de un vendedor.
     *
     * @param Vendor $vendor
     * @return EloquentCollection<int, RestockSuggestion>
     */
    public function generarParaVendor(Vendor $vendor): EloquentCollection
    {
        $resultados = new EloquentCollection();

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
     *
     * @param Producto $producto
     * @return RestockSuggestion|null
     */
    public function generarParaProducto(Producto $producto): ?RestockSuggestion
    {
        $producto->loadMissing('inventario');

        if ($producto->inventario === null) {
            return null;
        }

        try {
            $resultado = $this->mlClient->post('/forecast/restock', [
                'producto_id' => $producto->id,
                'vendor_id' => $producto->vendor_id,
                'stock_actual' => $producto->inventario->stock_actual,
                'stock_reservado' => $producto->inventario->stock_reservado,
            ]);
        } catch (MlServiceUnavailableException) {
            $resultado = $this->reglaSimpleReabastoService->calcular($producto->inventario);
        }

        if ((int) ($resultado['stock_sugerido'] ?? 0) <= 0) {
            return null;
        }

        return RestockSuggestion::query()->updateOrCreate(
            ['producto_id' => $producto->id, 'vendor_id' => $producto->vendor_id, 'aceptada' => false],
            [
                'stock_actual' => $producto->inventario->stock_actual,
                'stock_sugerido' => $resultado['stock_sugerido'],
                'dias_hasta_quiebre' => $resultado['dias_hasta_quiebre'] ?? null,
                'urgencia' => $resultado['urgencia'] ?? 'media',
                'modelo_version_id' => $resultado['modelo_version_id'] ?? null,
            ]
        );
    }
}
