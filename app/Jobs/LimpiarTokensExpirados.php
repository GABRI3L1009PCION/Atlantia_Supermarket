<?php

namespace App\Jobs;

use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Limpia codigos de verificacion de correo ya vencidos.
 */
class LimpiarTokensExpirados implements ShouldQueue
{
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /**
     * Ejecuta la limpieza de tokens expirados.
     */
    public function handle(): void
    {
        User::query()
            ->whereNotNull('email_verification_code_hash')
            ->whereNotNull('email_verification_code_expires_at')
            ->where('email_verification_code_expires_at', '<', now())
            ->update([
                'email_verification_code_hash' => null,
                'email_verification_code_expires_at' => null,
            ]);
    }
}
