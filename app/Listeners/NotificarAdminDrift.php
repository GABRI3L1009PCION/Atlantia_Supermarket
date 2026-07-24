<?php

namespace App\Listeners;

use App\Events\DriftDetectado;
use App\Jobs\Ml\NotificarDegradacionModelo;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

/**
 * Notifica al administrador cuando hay drift alto.
 */
class NotificarAdminDrift implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Procesa drift detectado.
     */
    public function handle(DriftDetectado $event): void
    {
        NotificarDegradacionModelo::dispatch($event->metric->id);
    }
}
