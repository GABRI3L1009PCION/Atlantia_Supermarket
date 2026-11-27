<?php

namespace App\Notifications;

use App\Models\Dte\DteFactura;
use App\Models\Pedido;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/**
 * Avisa al cliente que la tienda ya emitió la factura de su pedido.
 */
class FacturaDisponibleNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly Pedido $pedido,
        private readonly DteFactura $factura,
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
        $padre = $this->pedido->pedidoPadre ?? $this->pedido;

        return [
            'title' => 'Tu factura está lista',
            'message' => ($this->pedido->vendor?->business_name ?? 'La tienda').' emitió la factura de tu pedido #'.$padre->numero_pedido.'. Puedes verla en tu pedido.',
            'url' => route('cliente.pedidos.show', $padre, false),
            'pedido_uuid' => $padre->uuid,
            'variant' => 'order',
        ];
    }
}
