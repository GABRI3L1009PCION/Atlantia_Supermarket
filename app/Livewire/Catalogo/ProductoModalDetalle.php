<?php

namespace App\Livewire\Catalogo;

use App\Models\Producto;
use Livewire\Attributes\On;
use Livewire\Component;

class ProductoModalDetalle extends Component
{
    public ?Producto $producto = null;

    public bool $abierto = false;

    #[On('open-product-modal')]
    public function openModal(int $productId): void
    {
        $this->producto = Producto::query()
            ->with(['vendor', 'imagenes', 'imagenPrincipal', 'categoria', 'inventario', 'media'])
            ->findOrFail($productId);

        $this->abierto = true;
    }

    public function closeModal(): void
    {
        $this->abierto = false;
        $this->producto = null;
    }

    public function render()
    {
        return view('livewire.catalogo.producto-modal-detalle');
    }
}
