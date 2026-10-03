<?php

namespace App\Events;

use App\Models\MarketCourierStatus;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Evento emitido cuando un repartidor actualiza su GPS.
 */
class UbicacionActualizada
{
    use Dispatchable;
    use SerializesModels;

    /**
     * Crea el evento.
     */
    public function __construct(public readonly MarketCourierStatus $status) {}
}
