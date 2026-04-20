<?php

namespace App\Events;

use App\Models\Ml\MlTrainingJob;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Evento emitido cuando un entrenamiento ML finaliza.
 */
class ModeloReentrenado
{
    use Dispatchable;
    use SerializesModels;

    /**
     * Crea el evento.
     *
     * @param MlTrainingJob $trainingJob
     */
    public function __construct(public readonly MlTrainingJob $trainingJob)
    {
    }
}
