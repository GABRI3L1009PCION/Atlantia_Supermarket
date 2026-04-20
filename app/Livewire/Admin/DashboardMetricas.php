<?php

namespace App\Livewire\Admin;

use Illuminate\Contracts\View\View;
use Livewire\Component;

class DashboardMetricas extends Component
{
    public function render(): View
    {
        return view('livewire.admin.dashboard-metricas', [
            'metrics' => app(\App\Services\Admin\DashboardService::class)->metrics(auth()->user()),
        ]);
    }
}
