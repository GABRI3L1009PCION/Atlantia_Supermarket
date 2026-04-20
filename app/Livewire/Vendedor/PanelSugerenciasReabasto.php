<?php

namespace App\Livewire\Vendedor;

use App\Models\Ml\RestockSuggestion;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class PanelSugerenciasReabasto extends Component
{
    public function render(): View
    {
        return view('livewire.vendedor.panel-sugerencias-reabasto', [
            'sugerencias' => RestockSuggestion::query()
                ->with('producto')
                ->where('vendor_id', auth()->user()?->vendor?->id)
                ->where('aceptada', false)
                ->latest()
                ->get(),
        ]);
    }
}

