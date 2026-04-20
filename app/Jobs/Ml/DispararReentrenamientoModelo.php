<?php

namespace App\Jobs\Ml;

use App\Models\User;
use App\Services\Ml\MlTrainingService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Dispara reentrenamiento de un modelo ML.
 */
class DispararReentrenamientoModelo implements ShouldQueue
{
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public string $queue = 'ml';

    /**
     * Crea el job.
     *
     * @param string $modeloNombre
     * @param int $userId
     */
    public function __construct(private readonly string $modeloNombre, private readonly int $userId)
    {
    }

    /**
     * Inicia el entrenamiento en el servicio ML.
     *
     * @param MlTrainingService $trainingService
     * @return void
     */
    public function handle(MlTrainingService $trainingService): void
    {
        $user = User::query()->findOrFail($this->userId);

        $trainingService->start(['modelo_nombre' => $this->modeloNombre], $user);
    }
}
