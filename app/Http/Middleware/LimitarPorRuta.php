<?php

namespace App\Http\Middleware;

use Illuminate\Routing\Middleware\ThrottleRequests;

/**
 * Igual que el throttle de Laravel, pero con un contador propio por ruta.
 *
 * Por defecto Laravel usa la misma clave (dominio + IP, o el usuario) para todas
 * las rutas con "throttle:N,M". Así, abrir una página con límite de 20/min y otra
 * de 3/hora sumaban al mismo contador y podían bloquear cualquier página durante
 * una hora. Aquí la clave incluye la ruta: cada límite cuenta solo lo suyo.
 */
class LimitarPorRuta extends ThrottleRequests
{
    protected function resolveRequestSignature($request)
    {
        $ruta = $request->route();
        $identificador = $ruta?->getName() ?? $request->method().' '.($ruta?->uri() ?? $request->path());

        return sha1(parent::resolveRequestSignature($request).'|'.$identificador);
    }
}
