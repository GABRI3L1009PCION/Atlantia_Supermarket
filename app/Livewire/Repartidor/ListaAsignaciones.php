<?php

namespace App\Livewire\Repartidor;

use App\Models\DeliveryRoute;
use Illuminate\Contracts\View\View;
use Livewire\Component;

/**
 * Lista de rutas asignadas al repartidor autenticado.
 */
class ListaAsignaciones extends Component
{
    public function render(): View
    {
        return view('livewire.repartidor.lista-asignaciones', [
            'rutas' => DeliveryRoute::query()
                ->with('pedido.direccion')
                ->where('repartidor_id', auth()->id())
                ->activas()
                ->latest()
                ->get(),
        ]);
    }
}

