<?php

namespace App\Events;

use App\Models\Pedido;
use App\Models\User;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Evento emitido cuando se asigna un repartidor a un pedido.
 */
class RepartidorAsignado implements ShouldDispatchAfterCommit
{
    use Dispatchable;
    use SerializesModels;

    /**
     * Crea el evento.
     */
    public function __construct(public readonly Pedido $pedido, public readonly User $repartidor) {}
}
