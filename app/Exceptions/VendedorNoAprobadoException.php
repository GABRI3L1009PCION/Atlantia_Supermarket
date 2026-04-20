<?php

namespace App\Exceptions;

/**
 * Se lanza cuando un vendedor no puede operar por falta de aprobacion.
 */
class VendedorNoAprobadoException extends AtlantiaDomainException
{
    /**
     * Codigo HTTP sugerido para la excepcion.
     */
    public function statusCode(): int
    {
        return 403;
    }

    /**
     * Mensaje seguro para usuario final.
     */
    public function publicMessage(): string
    {
        return 'El vendedor aun no esta aprobado para operar.';
    }
}

