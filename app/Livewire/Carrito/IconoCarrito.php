<?php

namespace App\Livewire\Carrito;

use App\Models\Carrito;
use App\Models\CarritoItem;
use App\Models\Producto;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Livewire\Attributes\On;
use Livewire\Component;

/**
 * Icono reactivo del carrito con conteo persistido.
 */
class IconoCarrito extends Component
{
    /**
     * Cantidad total de unidades en el carrito.
     */
    public int $cantidad = 0;

    /**
     * Total monetario actual del carrito.
     */
    public float $total = 0.0;

    /**
     * Inicializa el conteo del carrito.
     *
     * @return void
     */
    public function mount(): void
    {
        $this->actualizarConteo();
    }

    /**
     * Agrega un producto solicitado desde catalogo.
     *
     * @param int $productoId
     * @return void
     */
    #[On('carrito.agregar-producto')]
    public function agregarProducto(int $productoId): void
    {
        DB::transaction(function () use ($productoId): void {
            $producto = Producto::query()
                ->with('inventario')
                ->publicados()
                ->lockForUpdate()
                ->findOrFail($productoId);

            $carrito = $this->carritoActual();
            $item = CarritoItem::query()
                ->where('carrito_id', $carrito->id)
                ->where('producto_id', $producto->id)
                ->lockForUpdate()
                ->first();

            $cantidadActual = $item?->cantidad ?? 0;
            $cantidadNueva = $cantidadActual + 1;

            if ($cantidadNueva > $this->stockDisponible($producto)) {
                $this->dispatch('notificacion', message: 'No hay stock suficiente para agregar mas unidades.');

                return;
            }

            CarritoItem::query()->updateOrCreate(
                [
                    'carrito_id' => $carrito->id,
                    'producto_id' => $producto->id,
                ],
                [
                    'cantidad' => $cantidadNueva,
                    'precio_unitario_snapshot' => $this->precioActual($producto),
                ]
            );
        });

        $this->actualizarConteo();
        $this->dispatch('carrito.actualizado');
    }

    /**
     * Actualiza conteo y total visible del carrito.
     *
     * @return void
     */
    #[On('carrito.actualizado')]
    public function actualizarConteo(): void
    {
        $carrito = $this->buscarCarritoActivo();

        if (! $carrito) {
            $this->cantidad = 0;
            $this->total = 0.0;

            return;
        }

        $items = $carrito->items()->get(['cantidad', 'precio_unitario_snapshot']);

        $this->cantidad = (int) $items->sum('cantidad');
        $this->total = (float) $items->sum(
            fn (CarritoItem $item) => $item->cantidad * (float) $item->precio_unitario_snapshot
        );
    }

    /**
     * Renderiza el icono del carrito.
     *
     * @return View
     */
    public function render(): View
    {
        return view('livewire.carrito.icono-carrito');
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
        $query = Carrito::query()->active()->with('items');
        $user = auth()->user();

        return $user
            ? $query->where('user_id', $user->id)->first()
            : $query->where('session_id', session()->getId())->first();
    }

    /**
     * Calcula stock disponible para compra.
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
     * Obtiene el precio vigente validado en servidor.
     *
     * @param Producto $producto
     * @return float
     */
    private function precioActual(Producto $producto): float
    {
        return (float) ($producto->precio_oferta ?? $producto->precio_base);
    }
}
