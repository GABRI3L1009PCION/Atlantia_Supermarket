<?php

namespace App\Enums;

/**
 * Estados permitidos para pagos y estado de pago del pedido.
 */
enum EstadoPago: string
{
    case Pendiente = 'pendiente';
    case Validando = 'validando';
    case Aprobado = 'aprobado';
    case Pagado = 'pagado';
    case Rechazado = 'rechazado';
    case Anulado = 'anulado';
    case Reembolsado = 'reembolsado';
}
