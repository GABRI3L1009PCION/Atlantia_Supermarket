<?php

namespace App\Events;

use App\Models\Ml\FraudAlert;
use App\Models\Pedido;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Evento emitido cuando un pedido confirmado requiere revision antifraude.
 */
class FraudeDetectado
{
    use Dispatchable;
    use SerializesModels;

    /**
     * Crea el evento.
     *
     * @param Pedido $pedido
     * @param FraudAlert $alerta
     */
    public function __construct(
        public readonly Pedido $pedido,
        public readonly FraudAlert $alerta
    ) {
    }
}
