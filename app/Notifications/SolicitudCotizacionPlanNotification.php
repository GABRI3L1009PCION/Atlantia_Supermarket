<?php

namespace App\Notifications;

use App\Models\Vendor;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/**
 * Avisa a la administración que una tienda pidió cotización del plan Personalizado.
 */
class SolicitudCotizacionPlanNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly Vendor $vendor,
        private readonly int $productos,
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
        return [
            'title' => 'Piden cotización del plan Personalizado',
            'message' => "{$this->vendor->business_name} quiere un plan a su medida (unos {$this->productos} productos). Contáctalos para darles precio.",
            'url' => route('admin.mensajes.index', ['tipo' => 'cotizacion'], false),
            'vendor_uuid' => $this->vendor->uuid,
            'variant' => 'vendor',
        ];
    }
}
