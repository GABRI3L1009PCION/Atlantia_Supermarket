<?php

namespace App\Support;

/**
 * IVA de Guatemala (12%).
 *
 * Los precios de venta al consumidor ya incluyen el IVA: el total no se recarga,
 * solo se desglosa cuanto del precio corresponde al impuesto.
 */
final class Iva
{
    public const TASA = 0.12;

    /**
     * Precio de venta a partir de un precio sin IVA.
     */
    public static function agregar(float $precioSinIva): float
    {
        return round($precioSinIva * (1 + self::TASA), 2);
    }

    /**
     * Precio sin IVA a partir de un precio de venta que ya lo incluye.
     */
    public static function quitar(float $precioConIva): float
    {
        return round($precioConIva / (1 + self::TASA), 2);
    }

    /**
     * Monto de IVA contenido en un precio que ya lo incluye.
     */
    public static function incluido(float $precioConIva): float
    {
        return round($precioConIva - $precioConIva / (1 + self::TASA), 2);
    }
}
