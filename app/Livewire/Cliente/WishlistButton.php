<?php

namespace App\Livewire\Cliente;

use App\Models\Wishlist;
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
        if (! auth()->check()) {
            $this->dispatch('toast', type: 'info', message: 'Inicia sesion para guardar productos en tu lista.');

            return;
        }

        $registro = Wishlist::query()->where('user_id', auth()->id())->where('producto_id', $this->productoId)->first();

        if ($registro) {
            $registro->delete();
            $this->guardado = false;
            $this->dispatch('toast', type: 'info', message: 'Producto retirado de tu lista de deseos.');

            return;
        }

        Wishlist::query()->create([
            'user_id' => auth()->id(),
            'producto_id' => $this->productoId,
        ]);

        $this->guardado = true;
        $this->dispatch('toast', type: 'success', message: 'Producto agregado a tu lista de deseos.');
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
        if (! auth()->check()) {
            return false;
        }

        return Wishlist::query()
            ->where('user_id', auth()->id())
            ->where('producto_id', $this->productoId)
            ->exists();
    }
}
