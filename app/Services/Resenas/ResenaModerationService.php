<?php

namespace App\Services\Resenas;

use App\Models\Resena;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * Servicio de moderacion de resenas.
 */
class ResenaModerationService
{
    /**
     * Pagina resenas para moderacion.
     *
     * @param array<string, mixed> $filters
     * @return LengthAwarePaginator
     */
    public function paginate(array $filters = []): LengthAwarePaginator
    {
        return Resena::query()
            ->with(['producto.vendor', 'cliente'])
            ->when(isset($filters['aprobada']), fn ($query) => $query->where('aprobada', (bool) $filters['aprobada']))
            ->when(isset($filters['flagged_ml']), fn ($query) => $query->where('flagged_ml', (bool) $filters['flagged_ml']))
            ->latest()
            ->paginate(25)
            ->withQueryString();
    }

    /**
     * Modera una resena.
     *
     * @param array<string, mixed> $data
     */
    public function moderate(Resena $resena, array $data, User $user): Resena
    {
        $resena->update([
            'aprobada' => (bool) ($data['aprobada'] ?? $resena->aprobada),
            'flagged_ml' => (bool) ($data['flagged_ml'] ?? $resena->flagged_ml),
        ]);

        return $resena->refresh();
    }
}

