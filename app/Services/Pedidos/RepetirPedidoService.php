<?php

namespace App\Services\Pedidos;

use App\DTOs\CarritoItemDTO;
use App\Models\Pedido;
use App\Services\Carrito\CarritoService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Throwable;

/**
 * Pone de nuevo en el carrito los productos de un pedido anterior.
 */
class RepetirPedidoService
{
    public function __construct(private readonly CarritoService $carritoService) {}

    /**
     * @return array{agregados: int, omitidos: array<int, string>}
     */
    public function alCarrito(Request $request, Pedido $pedido): array
    {
        $pedido->loadMissing(['items.producto', 'pedidosHijos.items.producto']);
        $items = $pedido->pedidosHijos->isNotEmpty()
            ? $pedido->pedidosHijos->flatMap->items
            : $pedido->items;

        $resultado = ['agregados' => 0, 'omitidos' => []];

        foreach ($items->groupBy('producto_id') as $productoId => $grupo) {
            $nombre = (string) ($grupo->first()->producto?->nombre ?? $grupo->first()->producto_nombre_snapshot ?? 'Producto');

            try {
                $this->carritoService->addItem($request, new CarritoItemDTO((int) $productoId, (int) $grupo->sum('cantidad')));
                $resultado['agregados']++;
            } catch (ModelNotFoundException) {
                $resultado['omitidos'][] = $nombre;
            } catch (Throwable $exception) {
                report($exception);
                $resultado['omitidos'][] = $nombre;
            }
        }

        return $resultado;
    }

    /**
     * Mensaje para el cliente segun el resultado.
     *
     * @param  array{agregados: int, omitidos: array<int, string>}  $resultado
     */
    public static function mensaje(array $resultado): string
    {
        if ($resultado['agregados'] === 0) {
            return 'Los productos de ese pedido ya no están disponibles.';
        }

        $mensaje = $resultado['agregados'] === 1 ? 'Agregamos 1 producto a tu carrito.' : "Agregamos {$resultado['agregados']} productos a tu carrito.";

        if ($resultado['omitidos'] !== []) {
            $mensaje .= ' Sin existencia por ahora: '.implode(', ', array_slice($resultado['omitidos'], 0, 5)).'.';
        }

        return $mensaje;
    }
}
