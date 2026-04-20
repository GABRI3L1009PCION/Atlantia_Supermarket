<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Excepcion base para errores controlados del dominio Atlantia.
 */
abstract class AtlantiaDomainException extends RuntimeException
{
    /**
     * Codigo HTTP sugerido para respuestas controladas.
     */
    public function statusCode(): int
    {
        return 500;
    }

    /**
     * Mensaje seguro para mostrar al usuario final.
     */
    public function publicMessage(): string
    {
        return 'No fue posible completar la operacion solicitada.';
    }
}

