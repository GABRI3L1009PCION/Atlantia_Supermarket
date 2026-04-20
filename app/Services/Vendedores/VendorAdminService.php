<?php

namespace App\Services\Vendedores;

use App\Models\User;
use App\Models\Vendor;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
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
        return Vendor::query()
            ->with(['user', 'fiscalProfile'])
            ->when($filters['status'] ?? null, fn ($query, $status) => $query->where('status', $status))
            ->latest()
            ->paginate(25)
            ->withQueryString();
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
    public function approve(Vendor $vendor, User $admin): Vendor
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

            $vendor->productos()->update(['is_active' => false, 'visible_catalogo' => false]);

            return $vendor->refresh();
        });
    }
}

