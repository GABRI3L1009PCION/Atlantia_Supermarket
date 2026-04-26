<?php

namespace App\Livewire\Admin;

use App\Models\Ml\FraudAlert;
use App\Services\Antifraude\DeteccionPatronesService;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Component;

class PanelAntifraude extends Component
{
    public string $estado = 'abiertas';

    public string $riesgo = 'todos';

    public string $tipo = '';

    public string $lastRefreshed;

    public ?string $notice = null;

    public function mount(): void
    {
        $this->lastRefreshed = now()->format('d/m/Y H:i');
    }

    public function refreshPanel(): void
    {
        $this->lastRefreshed = now()->format('d/m/Y H:i');
    }

    public function updatedEstado(): void
    {
        $this->refreshPanel();
    }

    public function updatedRiesgo(): void
    {
        $this->refreshPanel();
    }

    public function updatedTipo(): void
    {
        $this->refreshPanel();
    }

    public function resolver(string $uuid): void
    {
        $alert = FraudAlert::query()->where('uuid', $uuid)->firstOrFail();

        app(DeteccionPatronesService::class)->resolve($alert, [
            'resuelta' => true,
            'accion' => 'revision_panel_admin',
            'notas' => 'Resuelta desde panel antifraude.',
        ], auth()->user());

        $this->notice = 'Alerta marcada como resuelta.';
        $this->refreshPanel();
    }

    public function render(): View
    {
        $dashboard = app(DeteccionPatronesService::class)->dashboard();
        $alerts = $this->alerts();

        return view('livewire.admin.panel-antifraude', [
            'dashboard' => $dashboard,
            'alerts' => $alerts,
        ]);
    }

    /**
     * Alertas filtradas con relaciones listas para evitar N+1 en la vista.
     *
     * @return Collection<int, FraudAlert>
     */
    private function alerts(): Collection
    {
        return FraudAlert::query()
            ->with(['pedido.cliente', 'user', 'revisadaPor', 'modeloVersion'])
            ->when($this->estado === 'abiertas', fn ($query) => $query->where('resuelta', false))
            ->when($this->estado === 'resueltas', fn ($query) => $query->where('resuelta', true))
            ->when($this->estado === 'pendientes', fn ($query) => $query->where('revisada', false))
            ->when($this->riesgo === 'alto', fn ($query) => $query->where('score_riesgo', '>=', 0.8))
            ->when($this->riesgo === 'medio', fn ($query) => $query->whereBetween('score_riesgo', [0.5, 0.799999]))
            ->when($this->tipo !== '', fn ($query) => $query->where('tipo', 'like', '%' . $this->tipo . '%'))
            ->latest()
            ->limit(12)
            ->get();
    }
}
