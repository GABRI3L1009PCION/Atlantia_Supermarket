<?php

namespace App\Contracts;

use App\Models\User;

/**
 * Contrato de notificaciones internas.
 */
interface NotificacionContract
{
    /**
     * Envia una notificacion tipada al usuario.
     *
     * @param  array<string, mixed>  $datos
     */
    public function enviar(User $user, string $tipo, array $datos): string;
}
