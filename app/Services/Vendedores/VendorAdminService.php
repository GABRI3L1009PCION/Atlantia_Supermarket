<?php

namespace App\Services\Vendedores;

use App\Models\User;
use App\Models\Vendor;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

/**
 * Servicio administrativo de vendedores.
 */
class VendorAdminService
{
    /**
     * Pagina vendedores.
     *
     * @param array<string, mixed> $filters
     * @return LengthAwarePaginator
     */
    public function paginate(array $filters = []): LengthAwarePaginator
    {
        return $this->reportQuery()
            ->when($filters['status'] ?? null, fn ($query, $status) => $query->where('status', $status))
            ->latest()
            ->paginate(25)
            ->withQueryString();
    }

    /**
     * Datos reales para el reporte administrativo de vendedores.
     *
     * @return array<string, mixed>
     */
    public function report(): array
    {
        $vendors = $this->reportQuery()->latest()->get();
        $statusTabs = [
            'pending' => 'Pendientes',
            'approved' => 'Aprobados',
            'rejected' => 'Rechazados',
            'suspended' => 'Suspendidos',
        ];
        $statusLabels = [
            'pending' => 'Pendiente',
            'approved' => 'Aprobado',
            'rejected' => 'Rechazado',
            'suspended' => 'Suspendido',
        ];

        $items = $vendors->map(function (Vendor $vendor) use ($statusLabels): array {
            $reviews = $vendor->productos->flatMap->resenas;
            $orders30 = (int) ($vendor->orders_30_count ?? 0);
            $delivered30 = (int) ($vendor->delivered_30_count ?? 0);
            $documents = collect($vendor->documents ?? [])->filter()->count();

            return [
                'business' => $vendor->business_name,
                'owner' => $vendor->user?->name ?? $vendor->business_name,
                'email' => $vendor->user?->email ?? $vendor->email_publico ?? 'No registrado',
                'phone' => $vendor->telefono_publico ?: $vendor->user?->phone ?: 'No registrado',
                'document_type' => strtoupper((string) ($vendor->document_type ?? 'DPI')),
                'document_number' => $vendor->document_number ?: 'Pendiente',
                'category' => $vendor->business_category ?: 'Sin categoria',
                'status' => $vendor->status,
                'status_label' => $statusLabels[$vendor->status] ?? ucfirst((string) $vendor->status),
                'documents' => $documents,
                'documents_total' => $vendor->has_nit ? 5 : 4,
                'sales_30' => (float) ($vendor->sales_30_total ?? 0),
                'commission_owed' => (float) ($vendor->pending_commission_total ?? 0),
                'rating' => $reviews->count() ? round((float) $reviews->avg('calificacion'), 1) : 0.0,
                'orders_30' => $orders30,
                'compliance' => $orders30 > 0 ? round(($delivered30 / $orders30) * 100) : 100,
                'created_relative' => optional($vendor->created_at)->diffForHumans() ?? 'Sin fecha',
                'created_at' => optional($vendor->created_at)->format('d/m/Y H:i') ?? 'Sin fecha',
            ];
        })->values();

        $ratings = $items->pluck('rating')->filter(fn ($rating) => (float) $rating > 0);

        return [
            'generated_at' => now(),
            'status_tabs' => $statusTabs,
            'status_counts' => collect($statusTabs)
                ->mapWithKeys(fn ($label, $status) => [$status => $items->where('status', $status)->count()])
                ->all(),
            'metrics' => [
                'total' => $items->count(),
                'approved' => $items->where('status', 'approved')->count(),
                'sales_30' => (float) $items->sum('sales_30'),
                'pending_commission' => (float) $items->sum('commission_owed'),
                'avg_rating' => $ratings->count() ? round((float) $ratings->avg(), 1) : 0.0,
            ],
            'vendors' => $items->all(),
        ];
    }

    /**
     * Detalle administrativo de vendedor.
     */
    public function detail(Vendor $vendor): Vendor
    {
        return $vendor->load(['user', 'fiscalProfile', 'productos', 'deliveryZones', 'commissions']);
    }

    /**
     * Aprueba vendedor.
     */
    public function approve(Vendor $vendor, array $data, User $admin): Vendor
    {
        return DB::transaction(function () use ($vendor, $data, $admin): Vendor {
            $vendor->update([
                'is_approved' => true,
                'approved_by' => $admin->id,
                'approved_at' => now(),
                'status' => 'approved',
                'commission_percentage' => $data['commission_percentage'],
                'monthly_rent' => $data['monthly_rent'],
                'accepts_cash' => (bool) ($data['acepta_cash'] ?? true),
                'accepts_transfer' => (bool) ($data['acepta_transfer'] ?? true),
                'accepts_card' => (bool) ($data['acepta_card'] ?? true),
                'suspendido_at' => null,
                'suspendido_por' => null,
                'motivo_suspension' => null,
            ]);

            $vendor->user?->update(['status' => 'active']);

            return $vendor->refresh();
        });
    }

    /**
     * Suspende vendedor.
     *
     * @param array<string, mixed> $data
     */
    public function suspend(Vendor $vendor, array $data, User $admin): Vendor
    {
        return DB::transaction(function () use ($vendor, $data, $admin): Vendor {
            $vendor->update([
                'is_approved' => false,
                'status' => 'suspended',
                'suspendido_at' => now(),
                'suspendido_por' => $admin->id,
                'motivo_suspension' => $data['motivo_suspension'] ?? $data['motivo'] ?? 'Suspension administrativa.',
            ]);

            $vendor->user?->update(['status' => 'suspended']);
            $vendor->productos()->update(['is_active' => false, 'visible_catalogo' => false]);

            return $vendor->refresh();
        });
    }

    /**
     * Reactiva vendedor suspendido.
     */
    public function reactivate(Vendor $vendor, User $admin): Vendor
    {
        return DB::transaction(function () use ($vendor, $admin): Vendor {
            $vendor->update([
                'is_approved' => true,
                'approved_by' => $admin->id,
                'approved_at' => now(),
                'status' => 'approved',
                'suspendido_at' => null,
                'suspendido_por' => null,
                'motivo_suspension' => null,
            ]);

            $vendor->user?->update(['status' => 'active']);
            $vendor->productos()->update(['is_active' => true]);

            return $vendor->refresh();
        });
    }

    /**
     * Elimina logicamente un vendedor y oculta su catalogo.
     */
    public function delete(Vendor $vendor): void
    {
        DB::transaction(function () use ($vendor): void {
            $vendor->productos()->update([
                'is_active' => false,
                'visible_catalogo' => false,
                'publicado_at' => null,
            ]);

            $vendor->update([
                'is_approved' => false,
                'status' => 'suspended',
                'suspendido_at' => now(),
                'motivo_suspension' => 'Cuenta de vendedor eliminada por administracion.',
            ]);

            $vendor->delete();
        });
    }

    /**
     * Consulta base con metricas reales para la administracion de vendedores.
     *
     * @return Builder<Vendor>
     */
    private function reportQuery(): Builder
    {
        return Vendor::query()
            ->with(['user', 'fiscalProfile', 'productos.resenas', 'commissions'])
            ->withCount([
                'productos as active_products_count' => fn ($query) => $query->where('is_active', true)->where('visible_catalogo', true),
                'pedidos as orders_30_count' => fn ($query) => $query->where('created_at', '>=', now()->subDays(30)),
                'pedidos as delivered_30_count' => fn ($query) => $query->where('created_at', '>=', now()->subDays(30))->where('estado', 'entregado'),
                'pedidos as cancelled_30_count' => fn ($query) => $query->where('created_at', '>=', now()->subDays(30))->where('estado', 'cancelado'),
            ])
            ->withSum([
                'pedidos as sales_30_total' => fn ($query) => $query->where('created_at', '>=', now()->subDays(30)),
            ], 'total')
            ->withSum([
                'commissions as pending_commission_total' => fn ($query) => $query->whereIn('estado', ['pendiente', 'facturada']),
            ], 'monto_total');
    }
}
