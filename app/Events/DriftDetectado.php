<?php

namespace App\Events;

use App\Models\Ml\MlMetric;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Evento emitido cuando se detecta drift alto en un modelo ML.
 */
class DriftDetectado
{
    use Dispatchable;
    use SerializesModels;

    /**
     * Crea el evento.
     *
     * @param MlMetric $metric
     */
    public function __construct(public readonly MlMetric $metric)
    {
    }
}
