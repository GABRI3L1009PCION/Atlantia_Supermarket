<?php

namespace App\Exceptions;

/**
 * Se lanza cuando un token de seguridad o integracion ya no es valido.
 */
class TokenExpiradoException extends AtlantiaDomainException
{
    /**
     * Codigo HTTP sugerido para la excepcion.
     */
    public function statusCode(): int
    {
        return 401;
    }

    /**
     * Mensaje seguro para usuario final.
     */
    public function publicMessage(): string
    {
        return 'La sesion o token de acceso expiro.';
    }
}

