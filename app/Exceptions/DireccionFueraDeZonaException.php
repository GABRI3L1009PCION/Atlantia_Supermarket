<?php

namespace App\Exceptions;

/**
 * Se lanza cuando una direccion no tiene cobertura operativa.
 */
class DireccionFueraDeZonaException extends AtlantiaDomainException
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
        return $this->getMessage() ?: 'La direccion seleccionada esta fuera de nuestra zona de entrega.';
    }
}
