<?php

namespace App\Exceptions;

/**
 * Se lanza cuando Mapbox o el calculo de rutas falla.
 */
class GeolocalizacionException extends AtlantiaDomainException
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
        return 'No fue posible calcular la ubicacion o ruta solicitada.';
    }
}

