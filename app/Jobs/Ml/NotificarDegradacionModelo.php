<?php

namespace App\Jobs\Ml;

use App\Models\Ml\MlMetric;
use App\Models\User;
use App\Services\Notificaciones\NotificationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Notifica a administradores cuando un modelo presenta degradacion.
 */
class NotificarDegradacionModelo implements ShouldQueue
{
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public string $queue = 'notifications';

    /**
     * Crea el job.
     *
     * @param int $metricId
     */
    public function __construct(private readonly int $metricId)
    {
    }

    /**
     * Envia notificacion interna a administradores.
     *
     * @param NotificationService $notificationService
     * @return void
     */
    public function handle(NotificationService $notificationService): void
    {
        $metric = MlMetric::query()->with('modeloVersion')->findOrFail($this->metricId);

        User::role('admin')->active()->chunkById(100, function ($admins) use ($notificationService, $metric): void {
            foreach ($admins as $admin) {
                $notificationService->create($admin, 'ml.drift.detectado', [
                    'modelo' => $metric->modeloVersion?->nombre_modelo,
                    'version' => $metric->modeloVersion?->version,
                    'drift_score' => $metric->drift_score,
                    'fecha' => $metric->fecha,
                ]);
            }
        });
    }
}
