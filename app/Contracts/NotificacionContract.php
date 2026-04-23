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
     * @param User $user
     * @param string $tipo
     * @param array<string, mixed> $datos
     * @return string
     */
    public function enviar(User $user, string $tipo, array $datos): string;
}
