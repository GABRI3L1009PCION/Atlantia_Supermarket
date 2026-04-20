<?php

namespace App\Services\Auth;

use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;

/**
 * Servicio de recuperacion de contrasenas.
 */
class PasswordResetService
{
    /**
     * Envia enlace de recuperacion.
     *
     * @param array<string, mixed> $data
     * @return string
     */
    public function sendLink(array $data): string
    {
        return Password::sendResetLink(['email' => $data['email']]);
    }

    /**
     * Alias usado por el controlador de recuperacion.
     *
     * @param array<string, mixed> $data
     * @return string
     */
    public function sendResetLink(array $data): string
    {
        return $this->sendLink($data);
    }

    /**
     * Restablece la contrasena de un usuario.
     *
     * @param array<string, mixed> $data
     * @return string
     */
    public function reset(array $data): string
    {
        return Password::reset($data, function ($user, string $password): void {
            $user->forceFill([
                'password' => Hash::make($password),
                'remember_token' => Str::random(60),
            ])->save();

            event(new PasswordReset($user));
        });
    }

    /**
     * Alias usado por el controlador de restablecimiento.
     *
     * @param array<string, mixed> $data
     * @return string
     */
    public function resetPassword(array $data): string
    {
        return $this->reset($data);
    }
}
