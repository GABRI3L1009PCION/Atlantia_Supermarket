<?php

namespace App\Exceptions;

/**
 * Se lanza cuando una transaccion critica no puede completarse.
 */
class TransaccionFallidaException extends AtlantiaDomainException
{
    /**
     * Codigo HTTP sugerido para la excepcion.
     */
    public function statusCode(): int
    {
        return 500;
    }

    /**
     * Mensaje seguro para usuario final.
     */
    public function publicMessage(): string
    {
        return $this->getMessage() ?: 'La transaccion no pudo completarse de forma segura.';
    }
}
