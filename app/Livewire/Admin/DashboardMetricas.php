<?php

namespace App\Livewire\Admin;

use App\Services\Admin\DashboardService;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class DashboardMetricas extends Component
{
    /**
     * Marca visual de la ultima actualizacion del widget.
     */
    public string $lastRefreshed;

    /**
     * Inicializa el componente con la marca de refresco.
     */
    public function mount(): void
    {
        $this->refreshMetrics();
    }

    /**
     * Punto usado por wire:poll para refrescar las metricas.
     */
    public function refreshMetrics(): void
    {
        $this->lastRefreshed = now()->format('d/m/Y H:i');
    }

    /**
     * Renderiza metricas administrativas reutilizando el servicio consolidado.
     */
    public function render(): View
    {
        $user = auth()->user();

        return view('livewire.admin.dashboard-metricas', [
            'metrics' => $user
                ? app(DashboardService::class)->metrics($user)
                : [],
        ]);
    }
}
