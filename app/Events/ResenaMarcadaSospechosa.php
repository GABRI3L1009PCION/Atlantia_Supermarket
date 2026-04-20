<?php

namespace App\Events;

use App\Models\Ml\ReviewFlag;
use App\Models\Resena;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Evento emitido cuando una resena fue marcada como sospechosa por ML.
 */
class ResenaMarcadaSospechosa
{
    use Dispatchable;
    use SerializesModels;

    /**
     * Crea el evento.
     *
     * @param Resena $resena
     * @param ReviewFlag $flag
     */
    public function __construct(public readonly Resena $resena, public readonly ReviewFlag $flag)
    {
    }
}
