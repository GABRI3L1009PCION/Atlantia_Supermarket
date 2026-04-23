<?php

namespace App\Notifications;

use App\Models\Pedido;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/**
 * Notificacion in-app de pedido entregado.
 */
class PedidoEntregadoNotification extends Notification
{
    use Queueable;

    public function __construct(private readonly Pedido $pedido)
    {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'title' => 'Pedido entregado',
            'message' => "Tu pedido {$this->pedido->numero_pedido} fue entregado. Gracias por comprar en Atlantia.",
            'route' => route('cliente.pedidos.show', $this->pedido),
            'pedido_uuid' => $this->pedido->uuid,
            'variant' => 'success',
        ];
    }
}
