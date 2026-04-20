<?php

namespace App\Livewire\Admin;

use App\Models\Ml\FraudAlert;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class PanelAntifraude extends Component
{
    public function render(): View
    {
        return view('livewire.admin.panel-antifraude', [
            'alerts' => FraudAlert::query()->where('resuelta', false)->latest()->limit(10)->get(),
        ]);
    }
}

