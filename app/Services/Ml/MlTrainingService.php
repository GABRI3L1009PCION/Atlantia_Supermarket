<?php

namespace App\Services\Ml;

use App\Models\Ml\MlTrainingJob;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Servicio de administracion de entrenamientos ML.
 */
class MlTrainingService
{
    /**
     * Crea una instancia del servicio.
     */
    public function __construct(
        private readonly MlServiceClient $mlClient,
        private readonly ExportadorDatasetService $exportadorDatasetService,
        private readonly MonitorDriftService $monitorDriftService
    ) {
    }

    /**
     * Pagina jobs de entrenamiento.
     *
     * @param array<string, mixed> $filters
     * @return LengthAwarePaginator
     */
    public function paginate(array $filters = []): LengthAwarePaginator
    {
        return $this->monitorDriftService->paginateJobs($filters);
    }

    /**
     * Resume la operacion del centro de entrenamiento.
     *
     * @param array<string, mixed> $filters
     * @return array<string, mixed>
     */
    public function dashboard(array $filters = []): array
    {
        return [
            'jobs_activos' => MlTrainingJob::query()->active()->count(),
            'jobs_completados' => MlTrainingJob::query()->completed()->count(),
            'jobs_fallidos' => MlTrainingJob::query()->failed()->count(),
            'jobs_recientes' => MlTrainingJob::query()->with('modeloVersion')->latest()->limit(10)->get(),
        ];
    }

    /**
     * Inicia reentrenamiento.
     *
     * @param array<string, mixed> $data
     * @param User $user
     * @return MlTrainingJob
     */
    public function start(array $data, User $user): MlTrainingJob
    {
        return DB::transaction(function () use ($data): MlTrainingJob {
            $job = MlTrainingJob::query()->create([
                'uuid' => (string) Str::uuid(),
                'modelo_nombre' => $data['modelo_nombre'],
                'inicio_at' => now(),
                'estado' => 'queued',
                'dataset_size' => $this->datasetSize($data['modelo_nombre']),
                'metricas_finales' => [
                    'motivo' => $data['motivo'],
                    'forzar_reentrenamiento' => (bool) ($data['forzar_reentrenamiento'] ?? false),
                    'usar_staging' => (bool) ($data['usar_staging'] ?? true),
                    'fecha_inicio_dataset' => $data['fecha_inicio_dataset'] ?? null,
                    'fecha_fin_dataset' => $data['fecha_fin_dataset'] ?? null,
                    'parametros' => $data['parametros'] ?? [],
                    'solicitado_por' => [
                        'id' => $user->id,
                        'email' => $user->email,
                        'name' => $user->name,
                    ],
                ],
            ]);

            try {
                $this->mlClient->post('/training/start', [
                    'job_uuid' => $job->uuid,
                    'modelo_nombre' => $job->modelo_nombre,
                    'fecha_inicio_dataset' => $data['fecha_inicio_dataset'] ?? null,
                    'fecha_fin_dataset' => $data['fecha_fin_dataset'] ?? null,
                    'forzar_reentrenamiento' => (bool) ($data['forzar_reentrenamiento'] ?? false),
                    'usar_staging' => (bool) ($data['usar_staging'] ?? true),
                    'parametros' => $data['parametros'] ?? [],
                ]);
            } catch (\Throwable) {
                $job->update(['estado' => 'failed', 'fin_at' => now(), 'error_log' => 'No se pudo contactar ML service.']);
            }

            return $job->refresh();
        });
    }

    /**
     * Calcula tamano de dataset por modelo.
     *
     * @param string $modeloNombre
     * @return int
     */
    private function datasetSize(string $modeloNombre): int
    {
        return str_contains($modeloNombre, 'recommend')
            ? $this->exportadorDatasetService->catalogo()->count()
            : $this->exportadorDatasetService->ventas()->count();
    }
}
