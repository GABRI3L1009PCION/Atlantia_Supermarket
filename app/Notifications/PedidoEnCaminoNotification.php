<?php

namespace App\Notifications;

use App\Models\Pedido;
use App\Notifications\Concerns\AvisaPedidoPorWhatsApp;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/**
 * Aviso de pedido en camino (plataforma y WhatsApp).
 */
class PedidoEnCaminoNotification extends Notification
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
            'title' => 'Pedido en camino',
            'message' => "Tu pedido {$this->pedido->numero_pedido} ya va en ruta.",
            'route' => route('cliente.pedidos.seguimiento', $this->pedido),
            'pedido_uuid' => $this->pedido->uuid,
            'variant' => 'info',
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
        $enlace = $this->enlaceSeguimiento($notifiable);

        return $this->mensajeWhatsApp(
            $notifiable,
            'pedido_en_camino',
            [$nombre, $numero, $enlace],
            "{$nombre}, tu pedido {$numero} va en camino. Mira por dónde va el repartidor: {$enlace}"
        );
    }

    protected function pedidoAvisado(): Pedido
    {
        return $this->pedido;
    }
}
