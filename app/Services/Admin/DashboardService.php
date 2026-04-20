<?php

namespace App\Services\Admin;

use App\Models\Dte\DteFactura;
use App\Models\Ml\FraudAlert;
use App\Models\Pedido;
use App\Models\Producto;
use App\Models\User;
use App\Models\Vendor;

/**
 * Servicio de metricas consolidadas para administracion.
 */
class DashboardService
{
    /**
     * Devuelve metricas operativas del marketplace.
     *
     * @return array<string, mixed>
     */
    public function metrics(User $user): array
    {
        return [
            'ventas_hoy' => (float) Pedido::query()->whereDate('created_at', today())->sum('total'),
            'pedidos_hoy' => Pedido::query()->whereDate('created_at', today())->count(),
            'pedidos_pendientes' => Pedido::query()->whereIn('estado', ['pendiente', 'confirmado'])->count(),
            'vendedores_pendientes' => Vendor::query()->pending()->count(),
            'vendedores_activos' => Vendor::query()->approved()->count(),
            'productos_publicados' => Producto::query()->publicados()->count(),
            'dte_rechazados' => DteFactura::query()->where('estado', 'rechazado')->count(),
            'alertas_fraude_abiertas' => FraudAlert::query()->where('resuelta', false)->count(),
        ];
    }
}

