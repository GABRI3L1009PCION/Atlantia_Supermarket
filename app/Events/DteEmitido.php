<?php

namespace App\Events;

use App\Models\Dte\DteFactura;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Evento emitido cuando un DTE fue certificado.
 */
class DteEmitido
{
    use Dispatchable;
    use SerializesModels;

    /**
     * Crea el evento.
     *
     * @param DteFactura $dte
     */
    public function __construct(public readonly DteFactura $dte)
    {
    }
}
