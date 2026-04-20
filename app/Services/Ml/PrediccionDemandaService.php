<?php

namespace App\Services\Ml;

use App\Exceptions\MlServiceUnavailableException;
use App\Models\Ml\SalesPrediction;
use App\Models\Producto;
use App\Models\User;
use App\Services\Ml\Fallback\FallbackPrediccionService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

/**
 * Servicio de prediccion de demanda por producto.
 */
class PrediccionDemandaService
{
    /**
     * Crea una instancia del servicio.
     */
    public function __construct(
        private readonly MlServiceClient $mlClient,
        private readonly FallbackPrediccionService $fallbackPrediccionService
    ) {
    }

    /**
     * Lista predicciones del vendedor autenticado.
     *
     * @param User $user
     * @return LengthAwarePaginator
     */
    public function forVendor(User $user): LengthAwarePaginator
    {
        return SalesPrediction::query()
            ->with(['producto', 'modeloVersion'])
            ->where('vendor_id', $user->vendor?->id)
            ->latest()
            ->paginate(50);
    }

    /**
     * Obtiene o genera prediccion para un producto.
     *
     * @param Producto $producto
     * @param array<string, mixed> $data
     * @return SalesPrediction
     */
    public function forProduct(Producto $producto, array $data = []): SalesPrediction
    {
        $horizonte = (int) ($data['horizonte_dias'] ?? 7);
        $fecha = now()->toDateString();

        $existente = SalesPrediction::query()
            ->where('producto_id', $producto->id)
            ->whereDate('fecha_prediccion', $fecha)
            ->where('horizonte_dias', $horizonte)
            ->latest()
            ->first();

        return $existente ?? $this->generar($producto, $horizonte);
    }

    /**
     * Genera prediccion usando microservicio ML o fallback local.
     *
     * @param Producto $producto
     * @param int $horizonteDias
     * @return SalesPrediction
     */
    public function generar(Producto $producto, int $horizonteDias): SalesPrediction
    {
        $producto->loadMissing(['vendor', 'inventario']);

        try {
            $resultado = $this->mlClient->post('/forecast/demand', [
                'producto_id' => $producto->id,
                'vendor_id' => $producto->vendor_id,
                'horizonte_dias' => $horizonteDias,
                'stock_actual' => $producto->inventario?->stock_actual,
            ]);
        } catch (MlServiceUnavailableException) {
            $resultado = $this->fallbackPrediccionService->predecir($producto, $horizonteDias);
        }

        return DB::transaction(fn () => SalesPrediction::query()->create([
            'producto_id' => $producto->id,
            'vendor_id' => $producto->vendor_id,
            'fecha_prediccion' => now()->toDateString(),
            'horizonte_dias' => $horizonteDias,
            'valor_predicho' => $resultado['valor_predicho'],
            'intervalo_inferior' => $resultado['intervalo_inferior'] ?? null,
            'intervalo_superior' => $resultado['intervalo_superior'] ?? null,
            'modelo_version_id' => $resultado['modelo_version_id'] ?? null,
        ]));
    }
}
