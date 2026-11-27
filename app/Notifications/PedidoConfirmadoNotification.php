<?php

namespace App\Notifications;

use App\Models\Pedido;
use App\Notifications\Concerns\AvisaPedidoPorWhatsApp;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/**
 * Aviso de pedido confirmado (plataforma y WhatsApp).
 */
class PedidoConfirmadoNotification extends Notification
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
            'title' => 'Pedido confirmado',
            'message' => "Tu pedido {$this->pedido->numero_pedido} fue confirmado correctamente.",
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
        $enlace = $this->enlaceSeguimiento($notifiable);

        return $this->mensajeWhatsApp(
            $notifiable,
            'pedido_confirmado',
            [$nombre, $numero, $enlace],
            "Hola {$nombre}, tu pedido {$numero} de Atlantia Store fue confirmado. Te avisaremos cuando vaya en camino. Síguelo aquí: {$enlace}"
        );
    }

    protected function pedidoAvisado(): Pedido
    {
        return $this->pedido;
    }
}
