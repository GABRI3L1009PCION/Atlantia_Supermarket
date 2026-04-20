<?php

namespace App\Livewire\Catalogo;

use App\Models\Producto;
use App\Services\Ml\RecomendacionService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Livewire\Component;

/**
 * Productos recomendados para clientes autenticados o fallback publico.
 */
class ProductosRecomendados extends Component
{
    /**
     * Cantidad maxima de productos recomendados.
     */
    public int $limit = 8;

    /**
     * Solicita agregar un producto recomendado al carrito.
     *
     * @param int $productoId
     * @return void
     */
    public function agregarAlCarrito(int $productoId): void
    {
        $producto = Producto::query()->publicados()->findOrFail($productoId);

        $this->dispatch('carrito.agregar-producto', productoId: $producto->id);
        $this->dispatch('notificacion', message: "{$producto->nombre} agregado al carrito.");
    }

    /**
     * Renderiza recomendaciones del cliente o productos populares.
     *
     * @return View
     */
    public function render(): View
    {
        return view('livewire.catalogo.productos-recomendados', [
            'productos' => $this->productos(app(RecomendacionService::class)),
        ]);
    }

    /**
     * Obtiene productos recomendados con fallback seguro.
     *
     * @param RecomendacionService $recomendacionService
     * @return Collection<int, Producto>
     */
    private function productos(RecomendacionService $recomendacionService): Collection
    {
        $user = auth()->user();

        if ($user && $user->hasRole('cliente')) {
            return $recomendacionService
                ->forCustomer($user, ['limit' => $this->limit])
                ->pluck('producto')
                ->filter()
                ->values();
        }

        return Producto::query()
            ->publicados()
            ->with(['imagenPrincipal', 'vendor', 'inventario'])
            ->withCount('pedidoItems')
            ->orderByDesc('pedido_items_count')
            ->limit($this->limit)
            ->get();
    }
}
