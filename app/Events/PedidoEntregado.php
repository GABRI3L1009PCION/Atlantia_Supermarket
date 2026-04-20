<?php

namespace App\Events;

use App\Models\Pedido;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Evento emitido cuando un pedido fue entregado.
 */
class PedidoEntregado
{
    use Dispatchable;
    use SerializesModels;

    /**
     * Crea el evento.
     *
     * @param Pedido $pedido
     */
    public function __construct(public readonly Pedido $pedido)
    {
    }
}
