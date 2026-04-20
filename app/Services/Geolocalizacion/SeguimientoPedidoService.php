<?php

namespace App\Services\Geolocalizacion;

use App\Models\Pedido;
use App\Models\MarketCourierStatus;

/**
 * Servicio de seguimiento de pedidos.
 */
class SeguimientoPedidoService
{
    /**
     * Devuelve estado, ruta y ultima ubicacion del pedido.
     *
     * @return array<string, mixed>
     */
    public function detail(Pedido $pedido): array
    {
        $pedido->load(['deliveryRoute', 'estados.usuario', 'direccion', 'vendor']);

        return [
            'pedido' => $pedido,
            'ruta' => $pedido->deliveryRoute,
            'historial' => $pedido->estados,
            'ultima_ubicacion' => $pedido->deliveryRoute?->repartidor_id
                ? MarketCourierStatus::query()
                    ->where('repartidor_id', $pedido->deliveryRoute->repartidor_id)
                    ->where('pedido_id', $pedido->id)
                    ->latest('timestamp_gps')
                    ->first()
                : null,
        ];
    }
}
