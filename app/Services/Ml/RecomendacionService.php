<?php

namespace App\Services\Ml;

use App\Contracts\MlServiceContract;
use App\Exceptions\MlServiceUnavailableException;
use App\Models\Ml\ProductRecommendation;
use App\Models\Producto;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Facades\DB;

/**
 * Servicio de recomendaciones personalizadas.
 */
class RecomendacionService
{
    /**
     * Crea una instancia del servicio.
     */
    public function __construct(private readonly MlServiceContract $mlClient)
    {
    }

    /**
     * Devuelve recomendaciones para un cliente.
     *
     * @param User $user
     * @param array<string, mixed> $data
     * @return EloquentCollection<int, ProductRecommendation>
     */
    public function forCustomer(User $user, array $data = []): EloquentCollection
    {
        $limit = min(30, max(6, (int) ($data['limit'] ?? 12)));
        $existentes = ProductRecommendation::query()
            ->with(['producto.imagenPrincipal', 'producto.vendor'])
            ->where('cliente_id', $user->id)
            ->ordenadas()
            ->limit($limit)
            ->get();

        if ($existentes->count() >= $limit) {
            return $existentes;
        }

        $this->generarParaCliente($user, $limit);

        return ProductRecommendation::query()
            ->with(['producto.imagenPrincipal', 'producto.vendor'])
            ->where('cliente_id', $user->id)
            ->ordenadas()
            ->limit($limit)
            ->get();
    }

    /**
     * Genera recomendaciones personalizadas.
     *
     * @param User $user
     * @param int $limit
     * @return int
     */
    public function generarParaCliente(User $user, int $limit = 12): int
    {
        try {
            $resultado = $this->mlClient->recomendar([
                'cliente_id' => $user->id,
                'limit' => $limit,
            ]);
            $items = $resultado['items'] ?? [];
        } catch (MlServiceUnavailableException) {
            $items = $this->fallbackItems($limit);
        }

        return DB::transaction(function () use ($user, $items): int {
            $posicion = 1;

            foreach ($items as $item) {
                ProductRecommendation::query()->updateOrCreate(
                    [
                        'cliente_id' => $user->id,
                        'producto_id' => $item['producto_id'],
                        'algoritmo' => $item['algoritmo'] ?? 'fallback_popularidad',
                    ],
                    [
                        'score' => $item['score'] ?? max(0.1, 1 / $posicion),
                        'posicion' => $posicion,
                        'modelo_version_id' => $item['modelo_version_id'] ?? null,
                    ]
                );
                $posicion++;
            }

            return $posicion - 1;
        });
    }

    /**
     * Fallback con productos publicados populares por compras.
     *
     * @param int $limit
     * @return array<int, array<string, mixed>>
     */
    private function fallbackItems(int $limit): array
    {
        return Producto::query()
            ->publicados()
            ->withCount('pedidoItems')
            ->orderByDesc('pedido_items_count')
            ->limit($limit)
            ->get()
            ->values()
            ->map(fn (Producto $producto, int $index) => [
                'producto_id' => $producto->id,
                'score' => round(1 - ($index * 0.03), 6),
                'algoritmo' => 'fallback_popularidad',
            ])
            ->all();
    }
}
