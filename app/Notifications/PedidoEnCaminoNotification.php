<?php

namespace App\Notifications;

use App\Models\Pedido;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/**
 * Notificacion in-app cuando el pedido ya va en ruta.
 */
class PedidoEnCaminoNotification extends Notification
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
            'title' => 'Pedido en camino',
            'message' => "Tu pedido {$this->pedido->numero_pedido} ya va en ruta.",
            'route' => route('cliente.pedidos.seguimiento', $this->pedido),
            'pedido_uuid' => $this->pedido->uuid,
            'variant' => 'info',
        ];
    }
}
