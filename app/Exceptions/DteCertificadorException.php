<?php

namespace App\Exceptions;

/**
 * Se lanza cuando el certificador FEL no responde de forma valida.
 */
class DteCertificadorException extends AtlantiaDomainException
{
    /**
     * Codigo HTTP sugerido para la excepcion.
     */
    public function statusCode(): int
    {
        return 502;
    }

    /**
     * Mensaje seguro para usuario final.
     */
    public function publicMessage(): string
    {
        return 'El certificador FEL no pudo procesar el documento.';
    }
}

