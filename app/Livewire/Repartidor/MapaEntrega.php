<?php

namespace App\Livewire\Repartidor;

use App\Models\DeliveryRoute;
use Illuminate\Contracts\View\View;
use Livewire\Component;

/**
 * Mapa de entrega para una ruta asignada.
 */
class MapaEntrega extends Component
{
    public DeliveryRoute $route;

    public function mount(DeliveryRoute $route): void
    {
        $this->route = $route->load(['pedido.direccion']);
    }

    public function render(): View
    {
        return view('livewire.repartidor.mapa-entrega', [
            'mapboxToken' => config('services.mapbox.token'),
        ]);
    }
}

