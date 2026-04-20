<?php

namespace App\Livewire\Vendedor;

use App\Models\Inventario;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class AlertasStockInteligente extends Component
{
    public function render(): View
    {
        return view('livewire.vendedor.alertas-stock-inteligente', [
            'inventarios' => Inventario::query()
                ->with('producto')
                ->whereHas('producto', fn ($query) => $query->where('vendor_id', auth()->user()?->vendor?->id))
                ->bajoMinimo()
                ->get(),
        ]);
    }
}

