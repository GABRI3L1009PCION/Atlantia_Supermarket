<?php

namespace App\Livewire\Cliente;

use App\Support\WishlistStore;
use Illuminate\Contracts\View\View;
use Livewire\Component;

/**
 * Boton de toggle para wishlist del cliente.
 */
class WishlistButton extends Component
{
    /**
     * Producto objetivo.
     */
    public int $productoId;

    /**
     * Estado visual actual.
     */
    public bool $guardado = false;

    /**
     * Inicializa estado.
     */
    public function mount(int $productoId): void
    {
        $this->productoId = $productoId;
        $this->guardado = $this->resolverGuardado();
    }

    /**
     * Alterna el producto en wishlist.
     */
    public function toggle(): void
    {
        $this->guardado = WishlistStore::toggle(request(), $this->productoId, auth()->user());
        $this->dispatch(
            'toast',
            type: $this->guardado ? 'success' : 'info',
            message: $this->guardado
                ? 'Producto agregado a tus favoritos.'
                : 'Producto retirado de tus favoritos.'
        );
    }

    /**
     * Renderiza el boton.
     */
    public function render(): View
    {
        return view('livewire.cliente.wishlist-button');
    }

    /**
     * Resuelve si el producto ya esta guardado.
     */
    private function resolverGuardado(): bool
    {
        return WishlistStore::contains(request(), $this->productoId, auth()->user());
    }
}
