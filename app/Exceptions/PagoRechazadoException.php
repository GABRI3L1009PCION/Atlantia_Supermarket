<?php

namespace App\Exceptions;

/**
 * Se lanza cuando la pasarela rechaza o invalida un pago.
 */
class PagoRechazadoException extends AtlantiaDomainException
{
    /**
     * Codigo HTTP sugerido para la excepcion.
     */
    public function statusCode(): int
    {
        return 402;
    }

    /**
     * Mensaje seguro para usuario final.
     */
    public function publicMessage(): string
    {
        return 'El pago fue rechazado por el procesador.';
    }
}

