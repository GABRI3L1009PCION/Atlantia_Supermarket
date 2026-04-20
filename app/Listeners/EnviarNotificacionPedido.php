<?php

namespace App\Listeners;

use App\Events\PedidoCreado;
use App\Events\PedidoEntregado;
use App\Events\RepartidorAsignado;
use App\Jobs\NotificarClientePedidoConfirmado;
use App\Services\Notificaciones\NotificadorPedidoService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

/**
 * Envia notificaciones relacionadas con pedidos.
 */
class EnviarNotificacionPedido implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Procesa eventos de pedido.
     *
     * @param object $event
     * @return void
     */
    public function handle(object $event): void
    {
        $notificadorPedidoService = app(NotificadorPedidoService::class);

        if ($event instanceof PedidoCreado) {
            NotificarClientePedidoConfirmado::dispatch($event->pedido->id);
        }

        if ($event instanceof RepartidorAsignado) {
            $notificadorPedidoService->rutaAsignada($event->pedido, $event->repartidor);
        }

        if ($event instanceof PedidoEntregado) {
            $notificadorPedidoService->estadoActualizado($event->pedido, 'en_ruta', 'entregado');
        }
    }
}
