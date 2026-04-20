<?php

namespace App\Jobs\Ml;

use App\Events\DriftDetectado;
use App\Models\Ml\MlModelVersion;
use App\Services\Ml\MonitorDriftService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Registra metrica diaria de drift para un modelo.
 */
class CalcularDriftDiario implements ShouldQueue
{
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public string $queue = 'ml';

    /**
     * Crea el job.
     *
     * @param int $modelVersionId
     * @param array<string, mixed> $metricas
     */
    public function __construct(private readonly int $modelVersionId, private readonly array $metricas)
    {
    }

    /**
     * Registra metricas y emite alerta si el drift supera umbral.
     *
     * @param MonitorDriftService $monitorDriftService
     * @return void
     */
    public function handle(MonitorDriftService $monitorDriftService): void
    {
        $model = MlModelVersion::query()->findOrFail($this->modelVersionId);
        $metric = $monitorDriftService->registrarMetrica($model, $this->metricas);
        $threshold = (float) config('services.ml.drift_threshold', 0.25);

        if ((float) $metric->drift_score >= $threshold) {
            DriftDetectado::dispatch($metric);
        }
    }
}
