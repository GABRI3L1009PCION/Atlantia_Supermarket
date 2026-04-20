<?php

namespace App\Livewire\Carrito;

use App\Models\Carrito;
use App\Models\CarritoItem;
use App\Models\Producto;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Livewire\Attributes\On;
use Livewire\Component;

/**
 * Panel interactivo para administrar items del carrito.
 */
class PanelCarrito extends Component
{
    /**
     * Indica si el panel lateral esta abierto.
     */
    public bool $abierto = false;

    /**
     * Abre el panel cuando el carrito cambia.
     *
     * @return void
     */
    #[On('carrito.actualizado')]
    public function refrescar(): void
    {
        $this->abierto = true;
    }

    /**
     * Abre el panel del carrito.
     *
     * @return void
     */
    public function abrir(): void
    {
        $this->abierto = true;
    }

    /**
     * Cierra el panel del carrito.
     *
     * @return void
     */
    public function cerrar(): void
    {
        $this->abierto = false;
    }

    /**
     * Incrementa una unidad de un item.
     *
     * @param int $itemId
     * @return void
     */
    public function incrementar(int $itemId): void
    {
        $item = $this->itemDelCarrito($itemId);

        $this->actualizarCantidad($item->id, $item->cantidad + 1);
    }

    /**
     * Disminuye una unidad de un item.
     *
     * @param int $itemId
     * @return void
     */
    public function disminuir(int $itemId): void
    {
        $item = $this->itemDelCarrito($itemId);
        $cantidad = $item->cantidad - 1;

        if ($cantidad <= 0) {
            $this->eliminarItem($item->id);

            return;
        }

        $this->actualizarCantidad($item->id, $cantidad);
    }

    /**
     * Actualiza la cantidad validando stock disponible.
     *
     * @param int $itemId
     * @param int $cantidad
     * @return void
     */
    public function actualizarCantidad(int $itemId, int $cantidad): void
    {
        $cantidad = max(1, min(99, $cantidad));

        DB::transaction(function () use ($itemId, $cantidad): void {
            $item = $this->itemDelCarrito($itemId, true);
            $producto = Producto::query()
                ->with('inventario')
                ->publicados()
                ->lockForUpdate()
                ->findOrFail($item->producto_id);

            if ($cantidad > $this->stockDisponible($producto)) {
                $this->dispatch('notificacion', message: 'No hay stock suficiente para esa cantidad.');

                return;
            }

            $item->update([
                'cantidad' => $cantidad,
                'precio_unitario_snapshot' => $this->precioActual($producto),
            ]);
        });

        $this->dispatch('carrito.actualizado');
    }

    /**
     * Elimina un item del carrito.
     *
     * @param int $itemId
     * @return void
     */
    public function eliminarItem(int $itemId): void
    {
        $this->itemDelCarrito($itemId)->delete();

        $this->dispatch('carrito.actualizado');
    }

    /**
     * Elimina todos los items del carrito activo.
     *
     * @return void
     */
    public function vaciarCarrito(): void
    {
        $carrito = $this->buscarCarritoActivo();

        if ($carrito) {
            $carrito->items()->delete();
        }

        $this->dispatch('carrito.actualizado');
    }

    /**
     * Renderiza el panel del carrito.
     *
     * @return View
     */
    public function render(): View
    {
        $items = $this->items();

        return view('livewire.carrito.panel-carrito', [
            'items' => $items,
            'subtotal' => $this->subtotal($items),
            'cantidadTotal' => (int) $items->sum('cantidad'),
        ]);
    }

    /**
     * Obtiene items del carrito activo.
     *
     * @return Collection<int, CarritoItem>
     */
    private function items(): Collection
    {
        return $this->buscarCarritoActivo()?->items()
            ->with(['producto.imagenPrincipal', 'producto.inventario', 'producto.vendor'])
            ->orderByDesc('created_at')
            ->get() ?? collect();
    }

    /**
     * Obtiene un item asegurando ownership del carrito actual.
     *
     * @param int $itemId
     * @param bool $lock
     * @return CarritoItem
     */
    private function itemDelCarrito(int $itemId, bool $lock = false): CarritoItem
    {
        $carrito = $this->carritoActual();
        $query = CarritoItem::query()
            ->where('carrito_id', $carrito->id)
            ->whereKey($itemId);

        if ($lock) {
            $query->lockForUpdate();
        }

        return $query->firstOrFail();
    }

    /**
     * Obtiene o crea el carrito activo del usuario o visitante.
     *
     * @return Carrito
     */
    private function carritoActual(): Carrito
    {
        $user = auth()->user();

        return Carrito::query()->firstOrCreate(
            $user ? ['user_id' => $user->id, 'estado' => 'activo'] : [
                'session_id' => session()->getId(),
                'estado' => 'activo',
            ],
            [
                'uuid' => (string) Str::uuid(),
                'expira_at' => now()->addDays(7),
            ]
        );
    }

    /**
     * Busca el carrito activo sin crearlo.
     *
     * @return Carrito|null
     */
    private function buscarCarritoActivo(): ?Carrito
    {
        $query = Carrito::query()->active();
        $user = auth()->user();

        return $user
            ? $query->where('user_id', $user->id)->first()
            : $query->where('session_id', session()->getId())->first();
    }

    /**
     * Calcula subtotal del carrito.
     *
     * @param Collection<int, CarritoItem> $items
     * @return float
     */
    private function subtotal(Collection $items): float
    {
        return (float) $items->sum(
            fn (CarritoItem $item) => $item->cantidad * (float) $item->precio_unitario_snapshot
        );
    }

    /**
     * Calcula stock disponible del producto.
     *
     * @param Producto $producto
     * @return int
     */
    private function stockDisponible(Producto $producto): int
    {
        $inventario = $producto->inventario;

        if (! $inventario) {
            return 0;
        }

        return max(0, $inventario->stock_actual - $inventario->stock_reservado);
    }

    /**
     * Obtiene precio actual validado en servidor.
     *
     * @param Producto $producto
     * @return float
     */
    private function precioActual(Producto $producto): float
    {
        return (float) ($producto->precio_oferta ?? $producto->precio_base);
    }
}
