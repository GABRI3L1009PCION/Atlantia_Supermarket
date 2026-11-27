<?php

namespace App\Notifications;

use App\Models\Devolucion;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/**
 * Avisa al vendedor que un cliente pidió devolver un pedido suyo, o que Atlantia ya decidió.
 */
class DevolucionVendedorNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly Devolucion $devolucion,
        private readonly string $momento,
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
        $numero = $this->devolucion->pedido?->numero_pedido;

        [$titulo, $mensaje] = match ($this->momento) {
            'aprobada' => ['Devolución aprobada', "Atlantia aprobó la devolución del pedido #{$numero}. Esa venta ya no se te paga."],
            'rechazada' => ['Devolución rechazada', "Atlantia rechazó la devolución del pedido #{$numero}. Tu venta sigue igual."],
            default => ['Un cliente pidió una devolución', "Revisa el pedido #{$numero} y cuéntanos tu versión antes de que Atlantia decida."],
        };

        return [
            'title' => $titulo,
            'message' => $mensaje,
            'url' => route('vendedor.devoluciones.index', [], false),
            'devolucion_uuid' => $this->devolucion->uuid,
            'variant' => 'warning',
        ];
    }
}
