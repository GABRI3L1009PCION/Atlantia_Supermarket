<?php

namespace App\Livewire\Vendedor;

use App\Models\Ml\SalesPrediction;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class GraficoPrediccionDemanda extends Component
{
    public function render(): View
    {
        return view('livewire.vendedor.grafico-prediccion-demanda', [
            'predicciones' => SalesPrediction::query()
                ->with('producto')
                ->where('vendor_id', auth()->user()?->vendor?->id)
                ->latest('fecha_prediccion')
                ->limit(12)
                ->get(),
        ]);
    }
}

