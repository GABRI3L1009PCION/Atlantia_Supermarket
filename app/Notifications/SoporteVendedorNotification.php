<?php

namespace App\Notifications;

use App\Models\ContactMessage;
use App\Models\Vendor;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Str;

/**
 * Avisa a la administración que una tienda pidió ayuda desde su panel.
 */
class SoporteVendedorNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly ContactMessage $mensaje,
        private readonly Vendor $vendor,
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
        $tema = ContactMessage::CATEGORIAS_SOPORTE[$this->mensaje->categoria] ?? 'Soporte';

        return [
            'title' => ($this->mensaje->prioridad === 'alta' ? 'Urgente · ' : '').$this->vendor->business_name.' pide ayuda',
            'message' => $tema.': '.Str::limit($this->mensaje->asunto, 90),
            'url' => route('admin.mensajes.index', ['tipo' => 'soporte'], false),
            'mensaje_uuid' => $this->mensaje->uuid,
            'variant' => 'vendor',
        ];
    }
}
