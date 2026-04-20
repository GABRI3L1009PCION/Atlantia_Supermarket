<?php

namespace App\Services\Reportes;

use App\Models\Pedido;
use App\Models\User;

/**
 * Servicio de reportes del vendedor.
 */
class ReporteVendedorService
{
    /**
     * Devuelve resumen del vendedor.
     *
     * @return array<string, mixed>
     */
    public function summary(User $user): array
    {
        $vendorId = $user->vendor?->id;

        return [
            'ventas_mes' => (float) Pedido::query()->where('vendor_id', $vendorId)->whereMonth('created_at', now()->month)->sum('total'),
            'pedidos_mes' => Pedido::query()->where('vendor_id', $vendorId)->whereMonth('created_at', now()->month)->count(),
            'pendientes' => Pedido::query()->where('vendor_id', $vendorId)->whereIn('estado', ['pendiente', 'confirmado'])->count(),
        ];
    }
}

