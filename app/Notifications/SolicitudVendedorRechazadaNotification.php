<?php

namespace App\Notifications;

use App\Models\Vendor;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Correo al solicitante cuando su solicitud para vender no fue aprobada.
 */
class SolicitudVendedorRechazadaNotification extends Notification
{
    use Queueable;

    public function __construct(private readonly Vendor $vendor) {}

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
            ->subject('Sobre tu solicitud para vender en Atlantia Store')
            ->view('emails.vendedores.rechazado', [
                'user' => $notifiable,
                'vendor' => $this->vendor,
            ]);
    }
}
