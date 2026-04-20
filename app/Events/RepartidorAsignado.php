<?php

namespace App\Events;

use App\Models\Pedido;
use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Evento emitido cuando se asigna un repartidor a un pedido.
 */
class RepartidorAsignado
{
    use Dispatchable;
    use SerializesModels;

    /**
     * Crea el evento.
     *
     * @param Pedido $pedido
     * @param User $repartidor
     */
    public function __construct(public readonly Pedido $pedido, public readonly User $repartidor)
    {
    }
}
