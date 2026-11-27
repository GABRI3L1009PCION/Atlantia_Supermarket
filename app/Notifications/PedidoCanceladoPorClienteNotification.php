<?php

namespace App\Notifications;

use App\Models\Pedido;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/**
 * Avisa a la tienda que su pedido se canceló (lo canceló el cliente o Atlantia).
 */
class PedidoCanceladoPorClienteNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly Pedido $pedido,
        private readonly string $origen = 'cliente',
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * @return array<string, mixed>
     */
    public function toDatabase(object $notifiable): array
    {
        $quien = $this->origen === 'cliente' ? 'El cliente' : 'Atlantia';

        return [
            'title' => $this->origen === 'cliente' ? 'Pedido cancelado por el cliente' : 'Pedido cancelado por Atlantia',
            'message' => "{$quien} canceló el pedido #{$this->pedido->numero_pedido}. No tienes que prepararlo; los productos volvieron a tu inventario.",
            'url' => route('vendedor.pedidos.show', $this->pedido, false),
            'pedido_uuid' => $this->pedido->uuid,
            'variant' => 'order',
        ];
    }
}
