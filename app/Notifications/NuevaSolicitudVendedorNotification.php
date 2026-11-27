<?php

namespace App\Notifications;

use App\Models\Vendor;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/**
 * Avisa a la administración que un comercio envió su solicitud para vender.
 */
class NuevaSolicitudVendedorNotification extends Notification
{
    use Queueable;

    public function __construct(private readonly Vendor $vendor) {}

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
            'title' => 'Nueva solicitud para vender',
            'message' => $this->vendor->status === 'approved'
                ? "{$this->vendor->business_name} quiere vender en Atlantia. Cumplió los requisitos y se aprobó automáticamente; puedes revisarla o suspenderla si algo no cuadra."
                : "{$this->vendor->business_name} quiere vender en Atlantia. Necesita tu revisión: ".implode(' ', (array) data_get($this->vendor->revision_automatica, 'motivos', ['Revisa sus documentos para aprobarla.'])),
            /* Ruta relativa: funciona igual con cualquier dominio o IP del servidor. */
            'url' => route('admin.vendedores.index', [], false),
            'vendor_uuid' => $this->vendor->uuid,
            'codigo' => $this->vendor->application_code,
            'variant' => 'vendor',
        ];
    }
}
