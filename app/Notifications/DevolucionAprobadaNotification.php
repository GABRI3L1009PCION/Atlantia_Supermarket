<?php

namespace App\Notifications;

use App\Models\Devolucion;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/**
 * Notificacion in-app de devolucion aprobada.
 */
class DevolucionAprobadaNotification extends Notification
{
    use Queueable;

    public function __construct(private readonly Devolucion $devolucion)
    {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'title' => 'Devolucion aprobada',
            'message' => "Tu solicitud de devolucion del pedido {$this->devolucion->pedido?->numero_pedido} fue aprobada.",
            'route' => route('cliente.pedidos.show', $this->devolucion->pedido),
            'pedido_uuid' => $this->devolucion->pedido?->uuid,
            'variant' => 'success',
        ];
    }
}
