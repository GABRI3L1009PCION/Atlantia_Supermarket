<?php

namespace App\Enums;

/**
 * Estados operativos permitidos para pedidos.
 */
enum EstadoPedido: string
{
    case Pendiente = 'pendiente';
    case Confirmado = 'confirmado';
    case EnPreparacion = 'preparando';
    case ListoParaEntrega = 'listo_para_entrega';
    case EnRuta = 'en_ruta';
    case Entregado = 'entregado';
    case Cancelado = 'cancelado';
    case Rechazado = 'rechazado';
}
