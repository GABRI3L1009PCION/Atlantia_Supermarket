<?php

namespace App\Livewire\Pedidos;

use App\Models\MarketCourierStatus;
use App\Models\Pedido;
use Illuminate\Contracts\View\View;
use Livewire\Component;

/**
 * Rastreo en vivo del repartidor asignado.
 */
class RastreoEnVivo extends Component
{
    public Pedido $pedido;

    public function mount(Pedido $pedido): void
    {
        $this->pedido = $pedido->load('deliveryRoute');
    }

    public function render(): View
    {
        $ubicacion = $this->pedido->deliveryRoute?->repartidor_id
            ? MarketCourierStatus::query()
                ->where('pedido_id', $this->pedido->id)
                ->where('repartidor_id', $this->pedido->deliveryRoute->repartidor_id)
                ->latest('timestamp_gps')
                ->first()
            : null;

        return view('livewire.pedidos.rastreo-en-vivo', ['ubicacion' => $ubicacion]);
    }
}

