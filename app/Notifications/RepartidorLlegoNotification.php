<?php

namespace App\Notifications;

use App\Models\Pedido;
use App\Notifications\Concerns\AvisaPedidoPorWhatsApp;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/**
 * Aviso de que el repartidor llego a la direccion (plataforma y WhatsApp).
 */
class RepartidorLlegoNotification extends Notification
{
    use AvisaPedidoPorWhatsApp;
    use Queueable;

    public function __construct(private readonly Pedido $pedido) {}

    /**
     * Payload de base de datos.
     *
     * @return array<string, mixed>
     */
    public function toDatabase(object $notifiable): array
    {
        return [
            'title' => 'Tu repartidor llegó',
            'message' => "El repartidor llegó con tu pedido {$this->pedido->numero_pedido}. Ten listo tu código de entrega.",
            'route' => route('cliente.pedidos.seguimiento', $this->pedido),
            'pedido_uuid' => $this->pedido->uuid,
            'variant' => 'success',
        ];
    }

    /**
     * Mensaje de WhatsApp.
     *
     * @return array{telefono: ?string, plantilla: string, parametros: array<int, string>, texto: string}
     */
    public function toWhatsApp(object $notifiable): array
    {
        $nombre = $this->nombreCliente($notifiable);
        $numero = $this->pedido->numero_pedido;
        $codigo = (string) ($this->pedido->deliveryRoute?->confirmation_code ?? '');

        return $this->mensajeWhatsApp(
            $notifiable,
            'repartidor_llego',
            [$nombre, $numero, $codigo],
            "{$nombre}, tu repartidor llegó con el pedido {$numero}. Dale este código de entrega al recibir: {$codigo}"
        );
    }

    protected function pedidoAvisado(): Pedido
    {
        return $this->pedido;
    }
}
