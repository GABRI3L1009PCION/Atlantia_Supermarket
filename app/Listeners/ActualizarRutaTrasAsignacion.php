<?php

namespace App\Listeners;

use App\Events\RepartidorAsignado;
use App\Jobs\CalcularRutaOptima;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

/**
 * Recalcula ruta cuando un repartidor queda asignado.
 */
class ActualizarRutaTrasAsignacion implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Procesa la asignacion de repartidor.
     *
     * @param RepartidorAsignado $event
     * @return void
     */
    public function handle(RepartidorAsignado $event): void
    {
        CalcularRutaOptima::dispatch($event->pedido->id, $event->repartidor->id);
    }
}
