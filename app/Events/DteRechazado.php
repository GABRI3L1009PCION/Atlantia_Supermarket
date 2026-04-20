<?php

namespace App\Events;

use App\Models\Dte\DteFactura;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Evento emitido cuando el certificador rechaza un DTE.
 */
class DteRechazado
{
    use Dispatchable;
    use SerializesModels;

    /**
     * Crea el evento.
     *
     * @param DteFactura $dte
     * @param string|null $motivo
     */
    public function __construct(public readonly DteFactura $dte, public readonly ?string $motivo = null)
    {
    }
}
