<?php

namespace App\Livewire\Vendedor;

use App\Exceptions\MlServiceUnavailableException;
use App\Models\Ml\RestockSuggestion;
use App\Services\Ml\SugerenciaReabastoService;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Throwable;
use Livewire\Component;

class PanelSugerenciasReabasto extends Component
{
    public string $urgencia = 'todas';

    public ?string $notice = null;

    public ?string $error = null;

    public string $lastRefreshed;

    public function mount(): void
    {
        $this->lastRefreshed = now()->format('d/m/Y H:i');
    }

    public function refreshPanel(): void
    {
        $this->lastRefreshed = now()->format('d/m/Y H:i');
    }

    public function generarSugerencias(): void
    {
        $this->notice = null;
        $this->error = null;

        $vendor = auth()->user()?->vendor;

        if (! $vendor) {
            $this->error = 'No encontramos un perfil de vendedor activo para generar sugerencias.';
            return;
        }

        try {
            $generadas = app(SugerenciaReabastoService::class)->generarParaVendor($vendor);
            $this->notice = $generadas->isEmpty()
                ? 'No se generaron nuevas sugerencias. Tu inventario luce estable por ahora.'
                : 'Sugerencias de reabasto actualizadas.';
        } catch (MlServiceUnavailableException|Throwable) {
            $this->error = 'ML no responde en este momento. Conservamos tus sugerencias guardadas.';
        }

        $this->refreshPanel();
    }

    public function marcarAtendida(int $suggestionId): void
    {
        $vendorId = auth()->user()?->vendor?->id;

        RestockSuggestion::query()
            ->whereKey($suggestionId)
            ->where('vendor_id', $vendorId)
            ->update([
                'aceptada' => true,
                'aceptada_at' => now(),
            ]);

        $this->notice = 'Sugerencia marcada como atendida.';
        $this->refreshPanel();
    }

    public function updatedUrgencia(): void
    {
        $this->refreshPanel();
    }

    public function render(): View
    {
        $sugerencias = $this->sugerencias();

        return view('livewire.vendedor.panel-sugerencias-reabasto', [
            'sugerencias' => $sugerencias,
            'urgentes' => $sugerencias->whereIn('urgencia', ['alta', 'critica'])->count(),
            'stockSugeridoTotal' => (int) $sugerencias->sum('stock_sugerido'),
        ]);
    }

    /**
     * Sugerencias pendientes del vendedor con relaciones listas.
     *
     * @return Collection<int, RestockSuggestion>
     */
    private function sugerencias(): Collection
    {
        return RestockSuggestion::query()
            ->with(['producto.inventario', 'modeloVersion'])
            ->where('vendor_id', auth()->user()?->vendor?->id)
            ->where('aceptada', false)
            ->when($this->urgencia !== 'todas', fn ($query) => $query->where('urgencia', $this->urgencia))
            ->orderByRaw("CASE urgencia WHEN 'critica' THEN 1 WHEN 'alta' THEN 2 WHEN 'media' THEN 3 ELSE 4 END")
            ->latest()
            ->limit(20)
            ->get();
    }
}
