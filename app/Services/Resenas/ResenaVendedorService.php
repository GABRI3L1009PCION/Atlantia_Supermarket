<?php

namespace App\Services\Resenas;

use App\Models\Resena;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * Servicio de resenas visibles para vendedor.
 */
class ResenaVendedorService
{
    /**
     * Pagina resenas de productos propios.
     */
    public function paginate(User $user): LengthAwarePaginator
    {
        return Resena::query()
            ->with(['producto', 'cliente'])
            ->whereHas('producto', fn ($query) => $query->where('vendor_id', $user->vendor?->id))
            ->latest()
            ->paginate(25);
    }
}

