<?php

namespace App\Livewire\Admin;

use App\Exceptions\MlServiceUnavailableException;
use App\Services\Ml\MlServiceClient;
use App\Services\Ml\MonitorDriftService;
use Illuminate\Contracts\View\View;
use Throwable;
use Livewire\Component;

class MonitorDriftModelos extends Component
{
    public string $health = 'checking';

    public string $healthMessage = 'Verificando microservicio ML.';

    public string $lastRefreshed;

    public function mount(): void
    {
        $this->refreshMonitor();
    }

    public function refreshMonitor(): void
    {
        $this->lastRefreshed = now()->format('d/m/Y H:i');
        $this->checkHealth();
    }

    private function checkHealth(): void
    {
        if (app()->environment(['local', 'testing'])) {
            $this->health = 'mock';
            $this->healthMessage = 'ML en modo local: se muestran datos guardados y respuestas mock.';

            return;
        }

        try {
            app(MlServiceClient::class)->get('/health');
            $this->health = 'online';
            $this->healthMessage = 'Microservicio ML activo.';
        } catch (MlServiceUnavailableException|Throwable) {
            $this->health = 'offline';
            $this->healthMessage = 'El microservicio ML no esta disponible. El panel queda en modo lectura con datos locales.';
        }
    }

    public function render(): View
    {
        try {
            $monitor = app(MonitorDriftService::class)->dashboard();
        } catch (Throwable) {
            $monitor = [
                'modelos_produccion' => 0,
                'modelos_staging' => 0,
                'jobs_activos' => 0,
                'jobs_fallidos_24h' => 0,
                'drift_alto' => 0,
                'metricas_recientes' => collect(),
                'modelos_recientes' => collect(),
                'jobs_recientes' => collect(),
                'logs_recientes' => collect(),
                'latencia_promedio_ms' => 0,
                'llamadas_fallidas_24h' => 0,
            ];
            $this->health = 'offline';
            $this->healthMessage = 'No fue posible cargar metricas ML locales.';
        }

        return view('livewire.admin.monitor-drift-modelos', [
            'monitor' => $monitor,
        ]);
    }
}
