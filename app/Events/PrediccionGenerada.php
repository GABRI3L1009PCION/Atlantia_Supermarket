<?php

namespace App\Events;

use App\Models\Ml\SalesPrediction;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Evento emitido cuando ML genera una prediccion de demanda.
 */
class PrediccionGenerada
{
    use Dispatchable;
    use SerializesModels;

    /**
     * Crea el evento.
     *
     * @param SalesPrediction $prediction
     */
    public function __construct(public readonly SalesPrediction $prediction)
    {
    }
}
