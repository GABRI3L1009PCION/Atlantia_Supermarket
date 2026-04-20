<?php

namespace App\Exceptions;

/**
 * Se lanza cuando un producto no tiene stock disponible suficiente.
 */
class StockInsuficienteException extends AtlantiaDomainException
{
    /**
     * Codigo HTTP sugerido para la excepcion.
     */
    public function statusCode(): int
    {
        return 422;
    }

    /**
     * Mensaje seguro para usuario final.
     */
    public function publicMessage(): string
    {
        return 'Uno o mas productos no tienen stock suficiente.';
    }
}

