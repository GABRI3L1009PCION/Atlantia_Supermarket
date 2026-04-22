<?php

namespace App\Notifications\Auth;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Notificacion con codigo de verificacion de correo.
 */
class EmailVerificationCodeNotification extends Notification
{
    use Queueable;

    /**
     * Crea una nueva notificacion.
     */
    public function __construct(private readonly string $code)
    {
    }

    /**
     * Canales de envio.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Construye el correo de verificacion.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage())
            ->from(config('mail.from.address'), 'Atlantia Supermarket')
            ->subject('Tu codigo de verificacion de Atlantia Supermarket')
            ->view('emails.auth.verification-code', [
                'user' => $notifiable,
                'code' => $this->code,
                'expiresInMinutes' => 15,
            ]);
    }
}
