<?php

namespace App\Services\Reportes;

use App\Models\Pedido;
use App\Models\Vendor;

/**
 * Servicio de reportes administrativos.
 */
class ReporteAdminService
{
    /**
     * Devuelve resumen financiero.
     *
     * @param array<string, mixed> $filters
     * @return array<string, mixed>
     */
    public function summary(array $filters = []): array
    {
        return [
            'ventas_mes' => (float) Pedido::query()->whereMonth('created_at', now()->month)->sum('total'),
            'pedidos_mes' => Pedido::query()->whereMonth('created_at', now()->month)->count(),
            'ticket_promedio' => (float) Pedido::query()->whereMonth('created_at', now()->month)->avg('total'),
            'vendedores_activos' => Vendor::query()->approved()->count(),
        ];
    }
}

