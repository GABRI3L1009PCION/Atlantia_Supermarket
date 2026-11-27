<?php

namespace App\Notifications;

use App\Models\Pedido;
use App\Services\Pedidos\CancelacionService;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/**
 * Avisa al cliente que su pedido (o la parte de una tienda) se canceló y, si pagó con tarjeta,
 * cuánto se le devuelve y en qué plazo.
 */
class PedidoCanceladoNotification extends Notification
{
    use Queueable;

    /**
     * @param  array<int, string>  $tiendas
     */
    public function __construct(
        private readonly Pedido $pedido,
        private readonly array $tiendas,
        private readonly float $reembolso,
        private readonly bool $completo,
        private readonly string $origen,
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
        $quien = $this->origen === 'tienda' ? 'La tienda' : 'Atlantia';
        $mensaje = $this->completo
            ? "{$quien} canceló tu pedido #{$this->pedido->numero_pedido}."
            : (count($this->tiendas) === 1 ? $this->tiendas[0] : 'Una de las tiendas').' no podrá entregar su parte de tu pedido #'.$this->pedido->numero_pedido.'. Lo demás sigue en camino.';

        if ($this->reembolso > 0) {
            $mensaje .= ' Te devolveremos Q'.number_format($this->reembolso, 2).' a tu tarjeta en un plazo '.CancelacionService::PLAZO_DEVOLUCION.'.';
        }

        return [
            'title' => $this->completo ? 'Tu pedido fue cancelado' : 'Parte de tu pedido fue cancelada',
            'message' => $mensaje,
            'url' => route('cliente.pedidos.show', $this->pedido, false),
            'pedido_uuid' => $this->pedido->uuid,
            'variant' => 'order',
        ];
    }
}
