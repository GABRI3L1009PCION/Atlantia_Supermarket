<?php

namespace App\Jobs;

use App\Models\Pedido;
use App\Services\Notificaciones\NotificadorPedidoService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Notifica al cliente que su pedido fue confirmado.
 */
class NotificarClientePedidoConfirmado implements ShouldQueue
{
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public string $queue = 'notifications';

    /**
     * Crea el job.
     *
     * @param int $pedidoId
     */
    public function __construct(private readonly int $pedidoId)
    {
    }

    /**
     * Envia notificacion interna del pedido.
     *
     * @param NotificadorPedidoService $notificadorPedidoService
     * @return void
     */
    public function handle(NotificadorPedidoService $notificadorPedidoService): void
    {
        $pedido = Pedido::query()->with('cliente')->findOrFail($this->pedidoId);

        $notificadorPedidoService->pedidoConfirmado($pedido);
    }
}
