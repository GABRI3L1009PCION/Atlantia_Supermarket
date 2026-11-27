<?php

namespace App\Http\Requests\Concerns;

use App\Support\Iva;

/**
 * Los formularios de producto piden el precio sin IVA y envian precio_sin_iva=1.
 * Aqui se suma el 12% para guardar el precio de venta final que vera el cliente.
 */
trait AplicaIvaAPrecios
{
    protected function aplicarIvaAPrecios(): void
    {
        if (! $this->boolean('precio_sin_iva')) {
            return;
        }

        $precios = [];

        foreach (['precio_base', 'precio_oferta'] as $campo) {
            $valor = $this->input($campo);

            if (is_numeric($valor)) {
                $precios[$campo] = number_format(Iva::agregar((float) $valor), 2, '.', '');
            }
        }

        $this->merge($precios);
    }
}
