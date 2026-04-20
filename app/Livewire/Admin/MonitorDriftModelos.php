<?php

namespace App\Livewire\Admin;

use Illuminate\Contracts\View\View;
use Livewire\Component;

class MonitorDriftModelos extends Component
{
    public function render(): View
    {
        return view('livewire.admin.monitor-drift-modelos', [
            'monitor' => app(\App\Services\Ml\MonitorDriftService::class)->dashboard(),
        ]);
    }
}
