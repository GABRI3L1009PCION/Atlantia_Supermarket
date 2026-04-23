<?php

namespace App\Notifications;

use App\Models\Pedido;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/**
 * Notificacion in-app de pedido confirmado.
 */
class PedidoConfirmadoNotification extends Notification
{
    use Queueable;

    /**
     * @param Pedido $pedido
     */
    public function __construct(private readonly Pedido $pedido)
    {
    }

    /**
     * Canales utilizados.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * Payload de base de datos.
     *
     * @return array<string, mixed>
     */
    public function toDatabase(object $notifiable): array
    {
        return [
            'title' => 'Pedido confirmado',
            'message' => "Tu pedido {$this->pedido->numero_pedido} fue confirmado correctamente.",
            'route' => route('cliente.pedidos.show', $this->pedido),
            'pedido_uuid' => $this->pedido->uuid,
            'variant' => 'success',
        ];
    }
}
