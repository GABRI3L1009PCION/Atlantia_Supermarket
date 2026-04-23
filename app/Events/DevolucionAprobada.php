<?php

namespace App\Events;

use App\Models\Devolucion;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Evento emitido cuando una devolucion queda aprobada.
 */
class DevolucionAprobada
{
    use Dispatchable;
    use SerializesModels;

    /**
     * Crea el evento.
     */
    public function __construct(public readonly Devolucion $devolucion)
    {
    }
}
