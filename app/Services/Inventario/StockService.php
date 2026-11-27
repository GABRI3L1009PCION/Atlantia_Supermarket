<?php

namespace App\Services\Inventario;

use App\Exceptions\StockInsuficienteException;
use App\Exceptions\TransaccionFallidaException;
use App\Models\AuditLog;
use App\Models\Inventario;
use App\Models\Pedido;
use App\Models\PedidoItem;
use App\Models\Producto;
use App\Models\User;
use App\Notifications\StockBajoNotification;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Throwable;

/**
 * Servicio de operaciones atomicas de stock.
 */
class StockService
{
    /**
     * Lista el inventario de productos pertenecientes al vendedor autenticado.
     */
    public function forVendor(User $user): LengthAwarePaginator
    {
        return Inventario::query()
            ->with(['producto.categoria', 'producto.imagenPrincipal', 'producto.media'])
            ->whereHas('producto', fn ($query) => $query->where('vendor_id', $user->vendor?->id))
            ->orderByDesc('ultima_actualizacion')
            ->paginate(50)
            ->withQueryString();
    }

    /**
     * Resume el estado de inventario para el panel del vendedor (solo datos reales).
     *
     * @return array<string, mixed>
     */
    public function dashboardForVendor(User $user): array
    {
        $items = Inventario::query()
            ->with('producto:id,nombre,vendor_id')
            ->whereHas('producto', fn ($query) => $query->where('vendor_id', $user->vendor?->id))
            ->get();

        $disponible = fn (Inventario $inventario): int => max(0, (int) $inventario->stock_actual - (int) $inventario->stock_reservado);

        return [
            'summary' => [
                'productos' => $items->count(),
                'disponibles' => (int) $items->sum($disponible),
                'apartadas' => (int) $items->sum('stock_reservado'),
                'pocas' => $items->filter(fn (Inventario $i): bool => $disponible($i) > 0 && $disponible($i) <= (int) $i->stock_minimo)->count(),
                'agotados' => $items->filter(fn (Inventario $i): bool => $disponible($i) <= 0)->count(),
            ],
            'movements' => $this->recentMovements($items),
            'last_update' => $items->max('ultima_actualizacion'),
        ];
    }

    /**
     * Últimos movimientos reales: ajustes hechos a mano (bitácora) y unidades vendidas en pedidos.
     *
     * @param  Collection<int, Inventario>  $items
     * @return Collection<int, array<string, mixed>>
     */
    private function recentMovements(Collection $items): Collection
    {
        if ($items->isEmpty()) {
            return collect();
        }

        $nombres = $items->mapWithKeys(fn (Inventario $i): array => [$i->id => $i->producto?->nombre ?? 'Producto']);

        $ajustes = AuditLog::query()
            ->where('event', 'inventario.actualizado')
            ->where('auditable_type', Inventario::class)
            ->whereIn('auditable_id', $items->pluck('id'))
            ->latest('id')
            ->limit(20)
            ->get()
            ->map(function (AuditLog $log) use ($nombres): ?array {
                $antes = (int) ($log->old_values['stock_actual'] ?? 0);
                $despues = (int) ($log->new_values['stock_actual'] ?? 0);
                if ($antes === $despues) {
                    return null;
                }

                return [
                    'type' => $despues > $antes ? 'Agregaste existencias' : 'Quitaste existencias',
                    'product' => $nombres[$log->auditable_id] ?? 'Producto',
                    'date' => $log->created_at,
                    'delta' => $despues - $antes,
                ];
            })
            ->filter();

        $ventas = PedidoItem::query()
            ->join('pedidos', 'pedidos.id', '=', 'pedido_items.pedido_id')
            ->whereIn('pedido_items.producto_id', $items->pluck('producto_id'))
            ->whereNotIn('pedidos.estado', ['cancelado', 'rechazado'])
            ->latest('pedidos.created_at')
            ->limit(20)
            ->get(['pedido_items.producto_nombre_snapshot', 'pedido_items.cantidad', 'pedidos.numero_pedido', 'pedidos.created_at as fecha'])
            ->map(fn ($venta): array => [
                'type' => 'Vendido · pedido #'.$venta->numero_pedido,
                'product' => $venta->producto_nombre_snapshot,
                'date' => Carbon::parse($venta->fecha),
                'delta' => -1 * (int) $venta->cantidad,
            ]);

        return $ajustes->concat($ventas)->sortByDesc('date')->take(6)->values();
    }

    /**
     * Devuelve disponibilidad segura para catalogo, carrito o checkout.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function availability(Producto $producto, array $data = []): array
    {
        $producto->loadMissing('inventario');
        $cantidad = max(1, (int) ($data['cantidad'] ?? 1));
        $inventario = $producto->inventario;
        $disponible = $inventario === null
            ? 0
            : max(0, $inventario->stock_actual - $inventario->stock_reservado);

        return [
            'producto_id' => $producto->id,
            'producto_uuid' => $producto->uuid,
            'stock_disponible' => $disponible,
            'cantidad_solicitada' => $cantidad,
            'disponible' => $producto->is_active && $producto->visible_catalogo && $disponible >= $cantidad,
            'bajo_minimo' => $inventario !== null && $inventario->stock_actual <= $inventario->stock_minimo,
        ];
    }

    /**
     * Verifica stock disponible para una lista de items de carrito o pedido.
     *
     * @param  iterable<int, mixed>  $items
     *
     * @throws StockInsuficienteException
     */
    public function assertAvailableForItems(iterable $items): void
    {
        foreach ($items as $item) {
            $this->assertAvailable($item->producto, (int) $item->cantidad);
        }
    }

    /**
     * Verifica stock disponible para un producto.
     *
     *
     * @throws StockInsuficienteException
     */
    public function assertAvailable(Producto $producto, int $cantidad): void
    {
        $inventario = $producto->inventario()->lockForUpdate()->first();
        $disponible = $inventario === null
            ? 0
            : max(0, $inventario->stock_actual - $inventario->stock_reservado);

        if (! $producto->is_active || ! $producto->visible_catalogo || $disponible < $cantidad) {
            throw new StockInsuficienteException(
                "Stock insuficiente para el producto {$producto->nombre}."
            );
        }
    }

    /**
     * Reserva stock para una lista de items dentro de una transaccion activa.
     *
     * @param  iterable<int, mixed>  $items
     *
     * @throws StockInsuficienteException
     */
    public function reserveForItems(iterable $items): void
    {
        foreach ($items as $item) {
            $this->reserve($item->producto, (int) $item->cantidad);
        }
    }

    /**
     * Reserva stock de un producto.
     *
     *
     * @throws StockInsuficienteException
     */
    public function reserve(Producto $producto, int $cantidad): Inventario
    {
        return DB::transaction(function () use ($producto, $cantidad): Inventario {
            $inventario = $this->lockedInventario($producto);
            $disponible = max(0, $inventario->stock_actual - $inventario->stock_reservado);

            if ($disponible < $cantidad) {
                throw new StockInsuficienteException(
                    "Stock insuficiente para reservar {$producto->nombre}."
                );
            }

            $inventario->update([
                'stock_reservado' => $inventario->stock_reservado + $cantidad,
                'ultima_actualizacion' => now(),
            ]);

            return $inventario->refresh();
        });
    }

    /**
     * Libera stock reservado por un pedido cancelado o rechazado.
     */
    public function releaseForPedido(Pedido $pedido): void
    {
        $pedido->loadMissing(['items.producto', 'pedidosHijos.items.producto']);

        foreach ($this->itemsFromPedidoTree($pedido) as $item) {
            $this->release($item->producto, (int) $item->cantidad);
        }
    }

    /**
     * Libera una cantidad previamente reservada.
     */
    public function release(Producto $producto, int $cantidad): Inventario
    {
        return DB::transaction(function () use ($producto, $cantidad): Inventario {
            $inventario = $this->lockedInventario($producto);

            $inventario->update([
                'stock_reservado' => max(0, $inventario->stock_reservado - $cantidad),
                'ultima_actualizacion' => now(),
            ]);

            return $inventario->refresh();
        });
    }

    /**
     * Descuenta stock fisico y reservado cuando el pedido se confirma como entregado.
     */
    public function consumeReservedForPedido(Pedido $pedido): void
    {
        $pedido->loadMissing(['items.producto', 'pedidosHijos.items.producto']);

        foreach ($this->itemsFromPedidoTree($pedido) as $item) {
            $this->consumeReserved($item->producto, (int) $item->cantidad);
        }
    }

    /**
     * Restaura stock fisico de un pedido devuelto.
     */
    public function restoreForPedido(Pedido $pedido): void
    {
        $pedido->loadMissing(['items.producto', 'pedidosHijos.items.producto']);

        foreach ($this->itemsFromPedidoTree($pedido) as $item) {
            $this->restore($item->producto, (int) $item->cantidad);
        }
    }

    /**
     * Restaura stock fisico de un producto.
     */
    public function restore(Producto $producto, int $cantidad): Inventario
    {
        return DB::transaction(function () use ($producto, $cantidad): Inventario {
            $inventario = $this->lockedInventario($producto);

            $inventario->update([
                'stock_actual' => $inventario->stock_actual + $cantidad,
                'ultima_actualizacion' => now(),
            ]);

            return $inventario->refresh();
        });
    }

    /**
     * Descuenta stock fisico consumiendo primero la reserva.
     */
    public function consumeReserved(Producto $producto, int $cantidad): Inventario
    {
        return DB::transaction(function () use ($producto, $cantidad): Inventario {
            $inventario = $this->lockedInventario($producto);

            $inventario->update([
                'stock_actual' => max(0, $inventario->stock_actual - $cantidad),
                'stock_reservado' => max(0, $inventario->stock_reservado - $cantidad),
                'ultima_actualizacion' => now(),
            ]);

            $inventario = $inventario->refresh();
            $this->notificarStockBajo($producto, $inventario);

            return $inventario;
        });
    }

    /**
     * Actualiza inventario de un producto propio desde el panel de vendedor.
     *
     * @param  array<string, mixed>  $data
     *
     * @throws TransaccionFallidaException
     */
    public function updateForProduct(Producto $producto, array $data, User $user): Inventario
    {
        try {
            return DB::transaction(function () use ($producto, $data, $user): Inventario {
                $inventario = $this->lockedInventario($producto);
                $oldValues = $inventario->only(['stock_actual', 'stock_minimo', 'stock_maximo']);
                $stockActual = (int) ($data['stock_actual'] ?? $inventario->stock_actual);

                if ($stockActual < $inventario->stock_reservado) {
                    throw new TransaccionFallidaException(
                        'El stock actual no puede ser menor que el stock reservado.'
                    );
                }

                $inventario->update([
                    'stock_actual' => $stockActual,
                    'stock_minimo' => (int) ($data['stock_minimo'] ?? $inventario->stock_minimo),
                    'stock_maximo' => $data['stock_maximo'] ?? $inventario->stock_maximo,
                    'ultima_actualizacion' => now(),
                ]);

                $this->audit($inventario, $user, 'inventario.actualizado', $oldValues, $inventario->fresh()->toArray());
                $this->notificarStockBajo($producto, $inventario->refresh());

                return $inventario->refresh();
            });
        } catch (TransaccionFallidaException $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            throw new TransaccionFallidaException('No fue posible actualizar el inventario.', previous: $exception);
        }
    }

    /**
     * Obtiene o crea inventario con bloqueo pesimista para operaciones criticas.
     */
    private function lockedInventario(Producto $producto): Inventario
    {
        $inventario = Inventario::query()
            ->where('producto_id', $producto->id)
            ->lockForUpdate()
            ->first();

        if ($inventario !== null) {
            return $inventario;
        }

        return Inventario::query()->create([
            'producto_id' => $producto->id,
            'stock_actual' => 0,
            'stock_reservado' => 0,
            'stock_minimo' => 0,
            'stock_maximo' => null,
            'ultima_actualizacion' => now(),
        ]);
    }

    /**
     * Obtiene items de un pedido padre o hijo sin duplicar lineas.
     *
     * @return Collection<int, mixed>
     */
    private function itemsFromPedidoTree(Pedido $pedido): Collection
    {
        if ($pedido->pedidosHijos->isNotEmpty()) {
            return $pedido->pedidosHijos->flatMap(fn (Pedido $pedidoHijo) => $pedidoHijo->items);
        }

        return $pedido->items;
    }

    /**
     * Registra auditoria append-only del cambio de inventario.
     *
     * @param  array<string, mixed>  $oldValues
     * @param  array<string, mixed>  $newValues
     */
    private function audit(
        Inventario $inventario,
        User $user,
        string $event,
        array $oldValues,
        array $newValues
    ): void {
        AuditLog::query()->create([
            'uuid' => (string) Str::uuid(),
            'user_id' => $user->id,
            'event' => $event,
            'auditable_type' => Inventario::class,
            'auditable_id' => $inventario->id,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'metadata' => ['producto_id' => $inventario->producto_id],
        ]);
    }

    /**
     * Envia alerta al vendedor cuando el stock cae al minimo configurado.
     */
    private function notificarStockBajo(Producto $producto, Inventario $inventario): void
    {
        if ($inventario->stock_actual > $inventario->stock_minimo) {
            return;
        }

        $producto->loadMissing('vendor.user');

        if ($producto->vendor?->user === null) {
            return;
        }

        $producto->vendor->user->notify(new StockBajoNotification($producto, (int) $inventario->stock_actual));
    }
}
