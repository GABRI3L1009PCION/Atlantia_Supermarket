<?php

namespace App\Services\Logistica;

use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * Servicio administrativo de repartidores.
 */
class RepartidorService
{
    /**
     * Pagina repartidores.
     *
     * @param array<string, mixed> $filters
     * @return LengthAwarePaginator
     */
    public function paginate(array $filters = []): LengthAwarePaginator
    {
        return User::role('repartidor')
            ->with(['roles'])
            ->latest()
            ->paginate(25)
            ->withQueryString();
    }

    /**
     * Detalle operativo del repartidor.
     */
    public function detail(User $repartidor): User
    {
        return $repartidor->load(['roles']);
    }
}

