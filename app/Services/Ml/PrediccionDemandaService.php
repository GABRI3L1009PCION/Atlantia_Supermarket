<?php

namespace App\Services\Ml;

use App\Contracts\MlServiceContract;
use App\Exceptions\MlServiceUnavailableException;
use App\Models\Ml\SalesPrediction;
use App\Models\Producto;
use App\Models\User;
use App\Services\Ml\Fallback\FallbackPrediccionService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
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
        private readonly MlServiceContract $mlClient,
        private readonly FallbackPrediccionService $fallbackPrediccionService
    ) {
    }

    /**
     * Lista predicciones del vendedor autenticado.
     *
     * @param User $user
     * @return LengthAwarePaginator
     */
    public function forVendor(User $user, array $filters = []): LengthAwarePaginator
    {
        return SalesPrediction::query()
            ->with(['producto.categoria', 'producto.inventario', 'producto.imagenPrincipal', 'modeloVersion'])
            ->where('vendor_id', $user->vendor?->id)
            ->when($filters['q'] ?? null, function ($query, string $term): void {
                $query->whereHas('producto', fn ($query) => $query->where('nombre', 'like', "%{$term}%")->orWhere('sku', 'like', "%{$term}%"));
            })
            ->when($filters['horizonte'] ?? null, fn ($query, string $horizonte) => $query->where('horizonte_dias', (int) $horizonte))
            ->when(($filters['orden'] ?? 'mayor_demanda') === 'menor_demanda', fn ($query) => $query->orderBy('valor_predicho'))
            ->when(($filters['orden'] ?? 'mayor_demanda') === 'recientes', fn ($query) => $query->latest('fecha_prediccion'))
            ->when(($filters['orden'] ?? 'mayor_demanda') === 'mayor_demanda', fn ($query) => $query->orderByDesc('valor_predicho'))
            ->paginate(8)
            ->withQueryString();
    }

    /**
     * Resumen para el panel del vendedor.
     *
     * @return array<string, mixed>
     */
    public function dashboard(User $user, array $filters = []): array
    {
        $predictions = SalesPrediction::query()
            ->with(['producto.inventario'])
            ->where('vendor_id', $user->vendor?->id)
            ->when($filters['horizonte'] ?? null, fn ($query, string $horizonte) => $query->where('horizonte_dias', (int) $horizonte))
            ->get();

        $total = (float) $predictions->sum('valor_predicho');
        $average = $predictions->count() ? round($total / max(1, $predictions->count()), 1) : 0.0;
        $highDemand = $predictions->filter(fn (SalesPrediction $prediction): bool => (float) $prediction->valor_predicho >= $average && $average > 0)->count();
        $stockRisk = $this->stockRiskCount($predictions);

        return [
            'total_predicho' => round($total, 0),
            'productos_evaluados' => $predictions->pluck('producto_id')->unique()->count(),
            'demanda_alta' => $highDemand,
            'riesgo_stock' => $stockRisk,
            'promedio' => $average,
            'horizonte_activo' => (int) ($filters['horizonte'] ?? ($predictions->first()?->horizonte_dias ?? 7)),
            'max_prediccion' => max(1, (float) $predictions->max('valor_predicho')),
        ];
    }

    /**
     * @param Collection<int, SalesPrediction> $predictions
     */
    private function stockRiskCount(Collection $predictions): int
    {
        return $predictions->filter(function (SalesPrediction $prediction): bool {
            $stock = (int) ($prediction->producto?->inventario?->stock_actual ?? 0);

            return $stock > 0 && (float) $prediction->valor_predicho >= $stock;
        })->count();
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
            $resultado = $this->mlClient->predecirDemanda([
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
