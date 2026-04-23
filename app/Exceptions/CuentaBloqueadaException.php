<?php

namespace App\Exceptions;

/**
 * Se lanza cuando una cuenta no puede operar por seguridad.
 */
class CuentaBloqueadaException extends AtlantiaDomainException
{
    /**
     * Codigo HTTP sugerido para la excepcion.
     */
    public function statusCode(): int
    {
        return 423;
    }

    /**
     * Mensaje seguro para usuario final.
     */
    public function publicMessage(): string
    {
        return $this->getMessage() ?: 'Tu cuenta esta bloqueada temporalmente por seguridad.';
    }
}
