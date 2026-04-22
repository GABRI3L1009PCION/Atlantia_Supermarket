<?php

namespace App\Services\Auth;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

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

        $verified = $user->markEmailAsVerified();
        $user->clearEmailVerificationCode();

        return $verified;
    }

    /**
     * Verifica el correo con codigo temporal.
     *
     * @throws ValidationException
     */
    public function verifyCode(User $user, string $code): bool
    {
        if ($user->hasVerifiedEmail()) {
            return true;
        }

        if (
            $user->email_verification_code_hash === null
            || $user->email_verification_code_expires_at === null
            || $user->email_verification_code_expires_at->isPast()
            || ! Hash::check($code, $user->email_verification_code_hash)
        ) {
            throw ValidationException::withMessages([
                'code' => 'El codigo de verificacion no es valido o ya vencio.',
            ]);
        }

        return $this->verify($user);
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
