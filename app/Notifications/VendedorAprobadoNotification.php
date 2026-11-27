<?php

namespace App\Notifications;

use App\Models\Vendor;
use App\Services\Vendedores\AccesoVendedorService;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Correo al vendedor aprobado con el enlace para crear su contraseña.
 */
class VendedorAprobadoNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly Vendor $vendor,
        private readonly string $enlace,
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->from(config('mail.from.address'), 'Atlantia Store')
            ->subject('¡Tu tienda '.$this->vendor->business_name.' fue aprobada en Atlantia Store!')
            ->view('emails.vendedores.aprobado', [
                'user' => $notifiable,
                'vendor' => $this->vendor,
                'enlace' => $this->enlace,
                'horas' => AccesoVendedorService::HORAS_VIGENCIA,
            ]);
    }
}
