<?php

namespace App\Services\Inventario;

use App\Exceptions\StockInsuficienteException;
use App\Exceptions\TransaccionFallidaException;
use App\Models\AuditLog;
use App\Models\Inventario;
use App\Models\Pedido;
use App\Models\Producto;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
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
     *
     * @param User $user
     * @return LengthAwarePaginator
     */
    public function forVendor(User $user): LengthAwarePaginator
    {
        return Inventario::query()
            ->with(['producto.categoria'])
            ->whereHas('producto', fn ($query) => $query->where('vendor_id', $user->vendor?->id))
            ->orderByDesc('ultima_actualizacion')
            ->paginate(25);
    }

    /**
     * Devuelve disponibilidad segura para catalogo, carrito o checkout.
     *
     * @param Producto $producto
     * @param array<string, mixed> $data
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
     * @param iterable<int, mixed> $items
     * @return void
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
     * @param Producto $producto
     * @param int $cantidad
     * @return void
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
     * @param iterable<int, mixed> $items
     * @return void
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
     * @param Producto $producto
     * @param int $cantidad
     * @return Inventario
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
     *
     * @param Pedido $pedido
     * @return void
     */
    public function releaseForPedido(Pedido $pedido): void
    {
        $pedido->loadMissing('items.producto');

        foreach ($pedido->items as $item) {
            $this->release($item->producto, (int) $item->cantidad);
        }
    }

    /**
     * Libera una cantidad previamente reservada.
     *
     * @param Producto $producto
     * @param int $cantidad
     * @return Inventario
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
     *
     * @param Pedido $pedido
     * @return void
     */
    public function consumeReservedForPedido(Pedido $pedido): void
    {
        $pedido->loadMissing('items.producto');

        foreach ($pedido->items as $item) {
            $this->consumeReserved($item->producto, (int) $item->cantidad);
        }
    }

    /**
     * Descuenta stock fisico consumiendo primero la reserva.
     *
     * @param Producto $producto
     * @param int $cantidad
     * @return Inventario
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

            return $inventario->refresh();
        });
    }

    /**
     * Actualiza inventario de un producto propio desde el panel de vendedor.
     *
     * @param Producto $producto
     * @param array<string, mixed> $data
     * @param User $user
     * @return Inventario
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
     *
     * @param Producto $producto
     * @return Inventario
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
     * Registra auditoria append-only del cambio de inventario.
     *
     * @param Inventario $inventario
     * @param User $user
     * @param string $event
     * @param array<string, mixed> $oldValues
     * @param array<string, mixed> $newValues
     * @return void
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
}
