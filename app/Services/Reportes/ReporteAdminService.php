<?php

namespace App\Services\Reportes;

use App\Models\Dte\DteFactura;
use App\Models\Ml\FraudAlert;
use App\Models\Pedido;
use App\Models\VendorCommission;
use App\Models\Vendor;
use Illuminate\Support\Facades\DB;

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
        $desde = $filters['fecha_desde'] ?? now()->startOfMonth()->toDateString();
        $hasta = $filters['fecha_hasta'] ?? now()->toDateString();

        $pedidosBase = Pedido::query()->whereBetween('created_at', [$desde . ' 00:00:00', $hasta . ' 23:59:59']);

        return [
            'ventas_mes' => round((float) (clone $pedidosBase)->sum('total'), 2),
            'pedidos_mes' => (clone $pedidosBase)->count(),
            'ticket_promedio' => round((float) (clone $pedidosBase)->avg('total'), 2),
            'vendedores_activos' => Vendor::query()->approved()->count(),
            'comisiones_pendientes' => VendorCommission::query()->where('estado', 'pendiente')->count(),
            'dtes_rechazados' => DteFactura::query()->where('estado', 'rechazado')->count(),
            'alertas_pendientes' => FraudAlert::query()->where('revisada', false)->count(),
            'ventas_por_estado' => (clone $pedidosBase)
                ->select('estado', DB::raw('COUNT(*) as total'))
                ->groupBy('estado')
                ->pluck('total', 'estado'),
            'ventas_por_metodo_pago' => (clone $pedidosBase)
                ->select('metodo_pago', DB::raw('COUNT(*) as total'))
                ->groupBy('metodo_pago')
                ->pluck('total', 'metodo_pago'),
            'top_vendedores' => Vendor::query()
                ->select('vendors.id', 'vendors.business_name', DB::raw('COALESCE(SUM(pedidos.total), 0) as total_ventas'))
                ->leftJoin('pedidos', 'pedidos.vendor_id', '=', 'vendors.id')
                ->whereNull('pedidos.deleted_at')
                ->groupBy('vendors.id', 'vendors.business_name')
                ->orderByDesc('total_ventas')
                ->limit(5)
                ->get(),
            'dtes_recientes' => DteFactura::query()->with('vendor')->latest()->limit(5)->get(),
            'alertas_recientes' => FraudAlert::query()->with(['pedido', 'user'])->latest()->limit(5)->get(),
        ];
    }
}
