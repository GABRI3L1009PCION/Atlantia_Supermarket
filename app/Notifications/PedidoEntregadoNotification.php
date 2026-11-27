<?php

namespace App\Notifications;

use App\Models\Pedido;
use App\Notifications\Concerns\AvisaPedidoPorWhatsApp;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/**
 * Aviso de pedido entregado (plataforma y WhatsApp).
 */
class PedidoEntregadoNotification extends Notification
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
            'title' => 'Pedido entregado',
            'message' => "Tu pedido {$this->pedido->numero_pedido} fue entregado. Gracias por comprar en Atlantia.",
            'route' => route('cliente.pedidos.show', $this->pedido),
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

        return $this->mensajeWhatsApp(
            $notifiable,
            'pedido_entregado',
            [$nombre, $numero],
            "Gracias {$nombre}, tu pedido {$numero} fue entregado. Cuando quieras repetirlo, entra a Mis pedidos y toca Volver a pedir."
        );
    }

    protected function pedidoAvisado(): Pedido
    {
        return $this->pedido;
    }
}
