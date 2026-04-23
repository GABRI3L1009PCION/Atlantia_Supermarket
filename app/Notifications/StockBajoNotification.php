<?php

namespace App\Notifications;

use App\Models\Producto;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/**
 * Notificacion de alerta por stock bajo.
 */
class StockBajoNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly Producto $producto,
        private readonly int $stockActual
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'title' => 'Stock bajo detectado',
            'message' => "El producto {$this->producto->nombre} bajo a {$this->stockActual} unidades.",
            'route' => route('vendedor.inventario.index'),
            'producto_uuid' => $this->producto->uuid,
            'variant' => 'warning',
        ];
    }
}
