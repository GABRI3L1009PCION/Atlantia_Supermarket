<?php

namespace App\Services\Vendedores;

use App\Models\Pedido;
use App\Models\Producto;
use App\Models\User;

/**
 * Servicio de metricas del vendedor.
 */
class DashboardVendedorService
{
    /**
     * Devuelve metricas comerciales del vendedor.
     *
     * @return array<string, mixed>
     */
    public function metrics(User $user): array
    {
        $vendorId = $user->vendor?->id;

        return [
            'ventas_hoy' => (float) Pedido::query()->where('vendor_id', $vendorId)->whereDate('created_at', today())->sum('total'),
            'pedidos_pendientes' => Pedido::query()->where('vendor_id', $vendorId)->whereIn('estado', ['pendiente', 'confirmado'])->count(),
            'productos_activos' => Producto::query()->where('vendor_id', $vendorId)->active()->count(),
            'productos_publicados' => Producto::query()->where('vendor_id', $vendorId)->publicados()->count(),
        ];
    }
}

