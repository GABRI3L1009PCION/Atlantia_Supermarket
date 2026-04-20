<?php

namespace App\Services\Carrito;

use App\Exceptions\StockInsuficienteException;
use App\Models\Carrito;
use App\Models\CarritoItem;
use App\Models\Producto;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Servicio de carrito persistido para clientes y visitantes.
 */
class CarritoService
{
    /**
     * Obtiene el carrito activo de la solicitud actual.
     *
     * @param Request $request
     * @return Carrito
     */
    public function current(Request $request): Carrito
    {
        return $this->activeCart($request)->load([
            'items.producto.imagenPrincipal',
            'items.producto.inventario',
            'items.producto.vendor',
        ]);
    }

    /**
     * Agrega un producto al carrito con precio validado por servidor.
     *
     * @param Request $request
     * @param array<string, mixed> $data
     * @return CarritoItem
     */
    public function addItem(Request $request, array $data): CarritoItem
    {
        return DB::transaction(function () use ($request, $data): CarritoItem {
            $carrito = $this->activeCart($request);
            $producto = Producto::query()
                ->with('inventario')
                ->publicados()
                ->lockForUpdate()
                ->findOrFail((int) $data['producto_id']);

            $cantidad = (int) $data['cantidad'];
            $this->assertStockDisponible($producto, $cantidad);

            $item = CarritoItem::query()->firstOrNew([
                'carrito_id' => $carrito->id,
                'producto_id' => $producto->id,
            ]);

            $nuevaCantidad = (int) $item->cantidad + $cantidad;
            $this->assertStockDisponible($producto, $nuevaCantidad);

            $item->fill([
                'cantidad' => $nuevaCantidad,
                'precio_unitario_snapshot' => $this->precioVigente($producto),
            ]);
            $item->save();

            return $item->refresh();
        });
    }

    /**
     * Actualiza cantidad de un item del carrito.
     *
     * @param CarritoItem $item
     * @param array<string, mixed> $data
     * @return CarritoItem
     */
    public function updateItem(CarritoItem $item, array $data): CarritoItem
    {
        return DB::transaction(function () use ($item, $data): CarritoItem {
            $item->loadMissing('producto.inventario');
            $cantidad = (int) $data['cantidad'];

            $this->assertStockDisponible($item->producto, $cantidad);

            $item->update([
                'cantidad' => $cantidad,
                'precio_unitario_snapshot' => $this->precioVigente($item->producto),
            ]);

            return $item->refresh();
        });
    }

    /**
     * Remueve un item del carrito.
     *
     * @param CarritoItem $item
     * @param User|null $user
     * @return void
     */
    public function removeItem(CarritoItem $item, ?User $user): void
    {
        $item->delete();
    }

    /**
     * Verifica si el item pertenece al usuario o sesion actual.
     *
     * @param Request $request
     * @param CarritoItem $item
     * @return bool
     */
    public function ownsItem(Request $request, CarritoItem $item): bool
    {
        $item->loadMissing('carrito');
        $user = $request->user();

        if ($user instanceof User) {
            return (int) $item->carrito?->user_id === (int) $user->id;
        }

        return hash_equals((string) $request->session()->getId(), (string) $item->carrito?->session_id);
    }

    /**
     * Traslada el carrito de visitante a la cuenta del cliente.
     *
     * @param string $sessionId
     * @param User $user
     * @return void
     */
    public function mergeGuestCartIntoUser(string $sessionId, User $user): void
    {
        DB::transaction(function () use ($sessionId, $user): void {
            $guestCart = Carrito::query()->with('items')->where([
                'session_id' => $sessionId,
                'estado' => 'activo',
            ])->first();

            if ($guestCart === null || $guestCart->items->isEmpty()) {
                return;
            }

            $userCart = Carrito::query()->firstOrCreate(['user_id' => $user->id, 'estado' => 'activo'], [
                'uuid' => (string) Str::uuid(),
                'session_id' => null,
                'expira_at' => now()->addDays(14),
            ]);

            $this->mergeItems($guestCart, $userCart);
            $guestCart->items()->delete();
            $guestCart->delete();
        });
    }

    /**
     * Sincroniza el carrito desde API para clientes autenticados.
     *
     * @param Request $request
     * @param array<string, mixed> $data
     * @return Carrito
     */
    public function sync(Request $request, array $data): Carrito
    {
        return DB::transaction(function () use ($request, $data): Carrito {
            $carrito = $this->activeCart($request);
            $items = collect($data['items'] ?? []);

            $carrito->items()->delete();
            $this->createItemsFromCollection($request, $items);

            return $this->current($request);
        });
    }

    /**
     * Crea items desde una coleccion validada.
     *
     * @param Request $request
     * @param Collection<int, mixed> $items
     * @return void
     */
    private function createItemsFromCollection(Request $request, Collection $items): void
    {
        foreach ($items as $item) {
            $this->addItem($request, [
                'producto_id' => (int) $item['producto_id'],
                'cantidad' => (int) $item['cantidad'],
            ]);
        }
    }

    /**
     * Fusiona productos repetidos entre carritos.
     *
     * @param Carrito $guestCart
     * @param Carrito $userCart
     * @return void
     */
    private function mergeItems(Carrito $guestCart, Carrito $userCart): void
    {
        foreach ($guestCart->items as $guestItem) {
            $userItem = CarritoItem::query()->firstOrNew([
                'carrito_id' => $userCart->id,
                'producto_id' => $guestItem->producto_id,
            ]);

            $userItem->fill([
                'cantidad' => min(99, (int) $userItem->cantidad + (int) $guestItem->cantidad),
                'precio_unitario_snapshot' => $guestItem->precio_unitario_snapshot,
            ]);
            $userItem->save();
        }
    }

    /**
     * Obtiene o crea el carrito activo.
     */
    private function activeCart(Request $request): Carrito
    {
        $user = $request->user();
        $sessionId = $request->session()->getId();

        $attributes = $user instanceof User
            ? ['user_id' => $user->id, 'estado' => 'activo']
            : ['session_id' => $sessionId, 'estado' => 'activo'];

        return Carrito::query()->firstOrCreate($attributes, [
            'uuid' => (string) Str::uuid(),
            'user_id' => $user?->id,
            'session_id' => $user instanceof User ? null : $sessionId,
            'expira_at' => now()->addDays(14),
        ]);
    }

    /**
     * Verifica disponibilidad real de inventario.
     */
    private function assertStockDisponible(Producto $producto, int $cantidad): void
    {
        $inventario = $producto->inventario;
        $disponible = $inventario === null
            ? 0
            : max(0, (int) $inventario->stock_actual - (int) $inventario->stock_reservado);

        if ($cantidad < 1 || $cantidad > $disponible) {
            throw new StockInsuficienteException('Stock insuficiente para el producto seleccionado.');
        }
    }

    /**
     * Devuelve precio vigente sin confiar en el cliente.
     */
    private function precioVigente(Producto $producto): float
    {
        return (float) ($producto->precio_oferta ?? $producto->precio_base);
    }
}
