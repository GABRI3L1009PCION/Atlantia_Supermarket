<?php

namespace App\Services\Fel;

use App\Models\Dte\DteFactura;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

/**
 * Servicio de reportes fiscales por vendedor y administracion.
 */
class ReporteFiscalService
{
    /**
     * Pagina DTE globales para administracion.
     *
     * @param array<string, mixed> $filters
     * @return LengthAwarePaginator
     */
    public function paginateGlobal(array $filters = []): LengthAwarePaginator
    {
        return $this->queryWithFilters($filters)->paginate(50);
    }

    /**
     * Pagina DTE propios del vendedor autenticado.
     *
     * @param User $user
     * @param array<string, mixed> $filters
     * @return LengthAwarePaginator
     */
    public function paginateForVendor(User $user, array $filters = []): LengthAwarePaginator
    {
        return $this->queryWithFilters($filters)
            ->where('vendor_id', $user->vendor?->id)
            ->paginate(25);
    }

    /**
     * Carga detalle fiscal completo de un DTE.
     *
     * @param DteFactura $dte
     * @return DteFactura
     */
    public function detail(DteFactura $dte): DteFactura
    {
        return $dte->load(['vendor.fiscalProfile', 'pedido.cliente', 'items.producto', 'anulacion.usuario']);
    }

    /**
     * Resume montos fiscales por vendedor y periodo.
     *
     * @param Vendor $vendor
     * @param string $fechaInicio
     * @param string $fechaFin
     * @return array<string, mixed>
     */
    public function resumenVendor(Vendor $vendor, string $fechaInicio, string $fechaFin): array
    {
        $dtes = DteFactura::query()
            ->where('vendor_id', $vendor->id)
            ->whereBetween('created_at', [$fechaInicio, $fechaFin])
            ->whereIn('estado', ['certificado', 'anulado'])
            ->get();

        return $this->resumen($dtes);
    }

    /**
     * Construye query filtrada de DTE.
     *
     * @param array<string, mixed> $filters
     * @return \Illuminate\Database\Eloquent\Builder<DteFactura>
     */
    private function queryWithFilters(array $filters)
    {
        return DteFactura::query()
            ->with(['vendor', 'pedido'])
            ->when($filters['estado'] ?? null, fn ($query, $estado) => $query->where('estado', $estado))
            ->when($filters['tipo_dte'] ?? null, fn ($query, $tipo) => $query->where('tipo_dte', $tipo))
            ->when($filters['vendor_id'] ?? null, fn ($query, $vendorId) => $query->where('vendor_id', $vendorId))
            ->when($filters['fecha_desde'] ?? null, fn ($query, $fecha) => $query->whereDate('created_at', '>=', $fecha))
            ->when($filters['fecha_hasta'] ?? null, fn ($query, $fecha) => $query->whereDate('created_at', '<=', $fecha))
            ->latest();
    }

    /**
     * Resume montos a partir de una coleccion de DTE.
     *
     * @param Collection<int, DteFactura> $dtes
     * @return array<string, mixed>
     */
    private function resumen(Collection $dtes): array
    {
        return [
            'cantidad_dtes' => $dtes->count(),
            'certificados' => $dtes->where('estado', 'certificado')->count(),
            'anulados' => $dtes->where('estado', 'anulado')->count(),
            'monto_neto' => round((float) $dtes->sum('monto_neto'), 2),
            'monto_iva' => round((float) $dtes->sum('monto_iva'), 2),
            'monto_total' => round((float) $dtes->sum('monto_total'), 2),
        ];
    }
}
