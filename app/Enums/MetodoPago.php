<?php

namespace App\Enums;

/**
 * Metodos de pago disponibles en Atlantia.
 */
enum MetodoPago: string
{
    case Tarjeta = 'tarjeta';
    case Transferencia = 'transferencia';
    case Efectivo = 'efectivo';
}
