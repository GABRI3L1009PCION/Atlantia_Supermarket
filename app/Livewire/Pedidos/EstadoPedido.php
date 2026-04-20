<?php

namespace App\Livewire\Pedidos;

use App\Models\Pedido;
use Illuminate\Contracts\View\View;
use Livewire\Component;

/**
 * Muestra el estado actual e historial de un pedido.
 */
class EstadoPedido extends Component
{
    public Pedido $pedido;

    public function mount(Pedido $pedido): void
    {
        $this->pedido = $pedido->load(['estados.usuario']);
    }

    public function render(): View
    {
        return view('livewire.pedidos.estado-pedido');
    }
}

