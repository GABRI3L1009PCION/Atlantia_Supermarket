<?php

namespace App\Exceptions;

/**
 * Se lanza cuando el microservicio ML no esta disponible.
 */
class MlServiceUnavailableException extends AtlantiaDomainException
{
    /**
     * Codigo HTTP sugerido para la excepcion.
     */
    public function statusCode(): int
    {
        return 503;
    }

    /**
     * Mensaje seguro para usuario final.
     */
    public function publicMessage(): string
    {
        return 'El servicio de inteligencia de Atlantia no esta disponible temporalmente.';
    }
}

