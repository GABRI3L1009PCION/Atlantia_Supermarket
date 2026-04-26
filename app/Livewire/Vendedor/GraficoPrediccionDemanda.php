<?php

namespace App\Livewire\Vendedor;

use App\Exceptions\MlServiceUnavailableException;
use App\Models\Ml\MlPredictionLog;
use App\Models\Ml\SalesPrediction;
use App\Models\Producto;
use App\Services\Ml\PrediccionDemandaService;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Throwable;
use Livewire\Component;

class GraficoPrediccionDemanda extends Component
{
    public int $horizonteDias = 7;

    public ?string $notice = null;

    public ?string $error = null;

    public string $lastRefreshed;

    public function mount(): void
    {
        $this->lastRefreshed = now()->format('d/m/Y H:i');
    }

    public function refreshChart(): void
    {
        $this->lastRefreshed = now()->format('d/m/Y H:i');
    }

    public function generarPredicciones(): void
    {
        $this->notice = null;
        $this->error = null;

        $vendor = auth()->user()?->vendor;

        if (! $vendor) {
            $this->error = 'No encontramos un perfil de vendedor activo para generar predicciones.';
            return;
        }

        $productos = Producto::query()
            ->with('inventario')
            ->where('vendor_id', $vendor->id)
            ->active()
            ->limit(8)
            ->get();

        if ($productos->isEmpty()) {
            $this->error = 'No hay productos activos suficientes para generar predicciones.';
            return;
        }

        try {
            foreach ($productos as $producto) {
                app(PrediccionDemandaService::class)->forProduct($producto, [
                    'horizonte_dias' => $this->horizonteDias,
                ]);
            }

            $this->notice = $this->mlHasRecentFailures()
                ? 'Predicciones actualizadas con fallback local porque ML no respondio correctamente.'
                : 'Predicciones actualizadas correctamente.';
        } catch (MlServiceUnavailableException|Throwable) {
            $this->error = 'ML no responde en este momento. Conservamos las predicciones guardadas para que puedas seguir operando.';
        }

        $this->refreshChart();
    }

    public function render(): View
    {
        $predicciones = $this->predicciones();

        return view('livewire.vendedor.grafico-prediccion-demanda', [
            'predicciones' => $predicciones,
            'totalPredicho' => (float) $predicciones->sum('valor_predicho'),
            'mlDegradado' => $this->mlHasRecentFailures(),
        ]);
    }

    /**
     * Predicciones recientes del vendedor con producto y modelo cargados.
     *
     * @return Collection<int, SalesPrediction>
     */
    private function predicciones(): Collection
    {
        return SalesPrediction::query()
            ->with(['producto', 'modeloVersion'])
            ->where('vendor_id', auth()->user()?->vendor?->id)
            ->latest('fecha_prediccion')
            ->latest()
            ->limit(12)
            ->get();
    }

    private function mlHasRecentFailures(): bool
    {
        return MlPredictionLog::query()
            ->where('endpoint', 'like', '%forecast%')
            ->where('estado', 'failed')
            ->where('created_at', '>=', now()->subDay())
            ->exists();
    }
}
