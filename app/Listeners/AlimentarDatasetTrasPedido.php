<?php

namespace App\Listeners;

use App\Events\PedidoEntregado;
use App\Jobs\Ml\DetectarFraudeEnPedido;
use App\Jobs\Ml\ExportarDatasetParaEntrenamiento;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

/**
 * Alimenta procesos ML cuando un pedido finaliza.
 */
class AlimentarDatasetTrasPedido implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Procesa pedido entregado.
     *
     * @param PedidoEntregado $event
     * @return void
     */
    public function handle(PedidoEntregado $event): void
    {
        DetectarFraudeEnPedido::dispatch($event->pedido->id);
        ExportarDatasetParaEntrenamiento::dispatch('ventas');
    }
}
