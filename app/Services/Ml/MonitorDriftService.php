<?php

namespace App\Services\Ml;

use App\Models\Ml\MlMetric;
use App\Models\Ml\MlModelVersion;
use App\Models\Ml\MlTrainingJob;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

/**
 * Servicio de monitoreo de modelos ML y drift.
 */
class MonitorDriftService
{
    /**
     * Dashboard administrativo de ML.
     *
     * @param array<string, mixed> $filters
     * @return array<string, mixed>
     */
    public function dashboard(array $filters = []): array
    {
        return [
            'modelos_produccion' => MlModelVersion::query()->production()->count(),
            'jobs_activos' => MlTrainingJob::query()->active()->count(),
            'jobs_fallidos_24h' => MlTrainingJob::query()->failed()->where('created_at', '>=', now()->subDay())->count(),
            'drift_alto' => MlMetric::query()->driftAbove((float) ($filters['drift_threshold'] ?? 0.25))->count(),
            'metricas_recientes' => MlMetric::query()->with('modeloVersion')->latest()->limit(20)->get(),
        ];
    }

    /**
     * Pagina jobs de entrenamiento.
     *
     * @param array<string, mixed> $filters
     * @return LengthAwarePaginator
     */
    public function paginateJobs(array $filters = []): LengthAwarePaginator
    {
        return MlTrainingJob::query()
            ->with('modeloVersion')
            ->when($filters['estado'] ?? null, fn ($query, $estado) => $query->where('estado', $estado))
            ->when($filters['modelo_nombre'] ?? null, fn ($query, $nombre) => $query->where('modelo_nombre', $nombre))
            ->latest()
            ->paginate(50);
    }

    /**
     * Registra metrica diaria de drift.
     *
     * @param MlModelVersion $modelVersion
     * @param array<string, mixed> $data
     * @return MlMetric
     */
    public function registrarMetrica(MlModelVersion $modelVersion, array $data): MlMetric
    {
        return MlMetric::query()->updateOrCreate(
            ['modelo_version_id' => $modelVersion->id, 'fecha' => $data['fecha'] ?? now()->toDateString()],
            [
                'mape' => $data['mape'] ?? null,
                'rmse' => $data['rmse'] ?? null,
                'r2' => $data['r2'] ?? null,
                'drift_score' => $data['drift_score'] ?? null,
            ]
        );
    }

    /**
     * Procesa webhook del microservicio ML.
     *
     * @param array<string, mixed> $payload
     * @return void
     */
    public function handleWebhook(array $payload): void
    {
        DB::transaction(function () use ($payload): void {
            $job = MlTrainingJob::query()->where('uuid', $payload['job_uuid'] ?? null)->first();

            if ($job !== null) {
                $job->update([
                    'estado' => $payload['estado'] ?? $job->estado,
                    'fin_at' => in_array($payload['estado'] ?? '', ['completed', 'failed', 'cancelled'], true) ? now() : $job->fin_at,
                    'metricas_finales' => $payload['metricas_finales'] ?? $job->metricas_finales,
                    'error_log' => $payload['error_log'] ?? $job->error_log,
                ]);
            }
        });
    }
}
