<?php

namespace App\Services\Ml;

use App\Contracts\MlServiceContract;
use App\Exceptions\MlServiceUnavailableException;
use App\Models\Ml\SalesPrediction;
use App\Models\PedidoItem;
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
    ) {}

    /**
     * Lista predicciones del vendedor autenticado.
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
     * @param  Collection<int, SalesPrediction>  $predictions
     */
    private function stockRiskCount(Collection $predictions): int
    {
        return $predictions->filter(function (SalesPrediction $prediction): bool {
            $stock = (int) ($prediction->producto?->inventario?->stock_actual ?? 0);

            return $stock > 0 && (float) $prediction->valor_predicho >= $stock;
        })->count();
    }

    /** Horizontes que el vendedor puede elegir (días). */
    public const HORIZONTES = [7, 14, 30];

    /**
     * Proyección honesta para el vendedor: parte de sus ventas reales de los últimos 30 días
     * y usa la predicción del modelo cuando existe. Nunca inventa demanda para productos sin ventas.
     *
     * @return array{horizonte: int, filas: Collection<int, array<string, mixed>>, resumen: array<string, int>}
     */
    public function proyeccionVendedor(User $user, array $filters = []): array
    {
        $pedido = (int) ($filters['horizonte'] ?? 7);
        $horizonte = in_array($pedido, self::HORIZONTES, true) ? $pedido : 7;
        $vendorId = $user->vendor?->id;
        $termino = trim((string) ($filters['q'] ?? ''));

        $productos = Producto::query()
            ->with(['inventario', 'imagenPrincipal', 'media'])
            ->where('vendor_id', $vendorId)
            ->where('is_active', true)
            ->when($termino !== '', fn ($q) => $q->where(fn ($w) => $w->where('nombre', 'like', "%{$termino}%")->orWhere('sku', 'like', "%{$termino}%")))
            ->get();

        $vendidas = PedidoItem::query()
            ->join('pedidos', 'pedidos.id', '=', 'pedido_items.pedido_id')
            ->whereIn('pedido_items.producto_id', $productos->pluck('id'))
            ->whereNotIn('pedidos.estado', ['cancelado', 'rechazado'])
            ->where('pedidos.created_at', '>=', now()->subDays(30))
            ->groupBy('pedido_items.producto_id')
            ->selectRaw('pedido_items.producto_id, SUM(pedido_items.cantidad) as unidades')
            ->pluck('unidades', 'pedido_items.producto_id');

        $modelo = SalesPrediction::query()
            ->whereIn('producto_id', $productos->pluck('id'))
            ->where('horizonte_dias', $horizonte)
            ->whereNotNull('modelo_version_id')
            ->where('fecha_prediccion', '>=', now()->subDays(2)->toDateString())
            ->latest('fecha_prediccion')
            ->get()
            ->unique('producto_id')
            ->keyBy('producto_id');

        $prioridad = ['agotado' => 0, 'pedir' => 1, 'atento' => 2, 'bien' => 3, 'sin_ventas' => 4];

        $filas = $productos->map(function (Producto $producto) use ($vendidas, $modelo, $horizonte): array {
            $vendidas30 = (int) ($vendidas[$producto->id] ?? 0);
            $ritmo = $vendidas30 / 30;
            $prediccion = $modelo->get($producto->id);
            $esperado = $prediccion !== null ? (float) $prediccion->valor_predicho : $ritmo * $horizonte;
            $disponible = max(0, (int) ($producto->inventario?->stock_actual ?? 0) - (int) ($producto->inventario?->stock_reservado ?? 0));
            $ritmoUsado = $esperado > 0 ? $esperado / $horizonte : 0;
            $alcanza = $ritmoUsado > 0 ? (int) floor($disponible / $ritmoUsado) : null;

            $estado = match (true) {
                $esperado <= 0 => 'sin_ventas',
                $disponible <= 0 => 'agotado',
                $esperado > $disponible => 'pedir',
                $alcanza !== null && $alcanza < $horizonte * 2 => 'atento',
                default => 'bien',
            };

            return [
                'producto' => $producto,
                'vendidas_30' => $vendidas30,
                'esperado' => (int) ceil($esperado),
                'disponible' => $disponible,
                'alcanza_dias' => $alcanza,
                'faltan' => max(0, (int) ceil($esperado) - $disponible),
                'estado' => $estado,
                'fuente' => $prediccion !== null ? 'modelo' : 'ventas',
            ];
        })->sortBy([fn ($a, $b) => $prioridad[$a['estado']] <=> $prioridad[$b['estado']], fn ($a, $b) => $b['esperado'] <=> $a['esperado']])->values();

        return [
            'horizonte' => $horizonte,
            'filas' => $filas,
            'resumen' => [
                'productos' => $filas->count(),
                'con_ventas' => $filas->where('estado', '!=', 'sin_ventas')->count(),
                'esperado' => (int) $filas->sum('esperado'),
                'pedir' => $filas->whereIn('estado', ['agotado', 'pedir'])->count(),
                'sin_ventas' => $filas->where('estado', 'sin_ventas')->count(),
                'con_modelo' => $filas->where('fuente', 'modelo')->count(),
            ],
        ];
    }

    /**
     * Obtiene o genera prediccion para un producto.
     *
     * @param  array<string, mixed>  $data
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
                'historial' => $this->historialVentas($producto),
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

    /**
     * Construye una serie diaria de ventas reciente compatible con FastAPI.
     *
     * @return array<int, array{fecha: string, unidades: float}>
     */
    private function historialVentas(Producto $producto): array
    {
        return PedidoItem::query()
            ->selectRaw('DATE(created_at) as fecha, SUM(cantidad) as unidades')
            ->where('producto_id', $producto->id)
            ->where('created_at', '>=', now()->subDays(60))
            ->groupByRaw('DATE(created_at)')
            ->orderBy('fecha')
            ->get()
            ->map(fn ($row): array => [
                'fecha' => (string) $row->fecha,
                'unidades' => (float) $row->unidades,
            ])
            ->values()
            ->all();
    }
}
