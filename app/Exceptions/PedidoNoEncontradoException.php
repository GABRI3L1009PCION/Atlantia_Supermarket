<?php

namespace App\Exceptions;

/**
 * Se lanza cuando un pedido no existe o no pertenece al usuario actual.
 */
class PedidoNoEncontradoException extends AtlantiaDomainException
{
    /**
     * Codigo HTTP sugerido para la excepcion.
     */
    public function statusCode(): int
    {
        return 404;
    }

    /**
     * Mensaje seguro para usuario final.
     */
    public function publicMessage(): string
    {
        return 'No fue posible encontrar el pedido solicitado.';
    }
}

