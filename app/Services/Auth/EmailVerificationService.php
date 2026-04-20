<?php

namespace App\Services\Auth;

use App\Models\User;

/**
 * Servicio de verificacion de correo.
 */
class EmailVerificationService
{
    /**
     * Marca el correo como verificado.
     */
    public function verify(User $user): bool
    {
        if ($user->hasVerifiedEmail()) {
            return true;
        }

        return $user->markEmailAsVerified();
    }

    /**
     * Reenvia notificacion de verificacion.
     */
    public function resend(User $user): void
    {
        if (! $user->hasVerifiedEmail()) {
            $user->sendEmailVerificationNotification();
        }
    }
}
