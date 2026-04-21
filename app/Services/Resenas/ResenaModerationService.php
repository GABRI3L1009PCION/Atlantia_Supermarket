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
            ->with(['producto.vendor', 'cliente', 'reviewFlags'])
            ->when(isset($filters['aprobada']), fn ($query) => $query->where('aprobada', (bool) $filters['aprobada']))
            ->when(isset($filters['flagged_ml']), fn ($query) => $query->where('flagged_ml', (bool) $filters['flagged_ml']))
            ->when($filters['q'] ?? null, function ($query, string $search): void {
                $query->where(function ($builder) use ($search): void {
                    $builder->where('titulo', 'like', '%' . $search . '%')
                        ->orWhere('contenido', 'like', '%' . $search . '%')
                        ->orWhereHas('producto', fn ($productQuery) => $productQuery->where('nombre', 'like', '%' . $search . '%'))
                        ->orWhereHas('cliente', fn ($userQuery) => $userQuery->where('name', 'like', '%' . $search . '%'));
                });
            })
            ->latest()
            ->paginate(25)
            ->withQueryString();
    }

    /**
     * Carga detalle de una resena para moderacion.
     */
    public function detail(Resena $resena): Resena
    {
        return $resena->load(['producto.vendor', 'cliente', 'pedido', 'imagenes', 'reviewFlags.revisadaPor']);
    }

    /**
     * Resume el estado del panel de moderacion.
     *
     * @param array<string, mixed> $filters
     * @return array<string, int>
     */
    public function dashboard(array $filters = []): array
    {
        $resenas = Resena::query()->when(isset($filters['flagged_ml']), fn ($query) => $query->where('flagged_ml', (bool) $filters['flagged_ml']))->get();

        return [
            'total' => $resenas->count(),
            'pendientes' => $resenas->where('aprobada', false)->count(),
            'aprobadas' => $resenas->where('aprobada', true)->count(),
            'flagged_ml' => $resenas->where('flagged_ml', true)->count(),
        ];
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
            'moderada_por' => $user->id,
            'moderada_at' => now(),
        ]);

        $accionTomada = ! empty($data['notas'])
            ? $data['notas']
            : ((bool) ($data['aprobada'] ?? false) ? 'Resena aprobada.' : 'Resena rechazada o marcada.');

        $resena->reviewFlags()
            ->where('revisada', false)
            ->update([
                'revisada' => true,
                'accion_tomada' => $accionTomada,
                'revisada_por' => $user->id,
                'revisada_at' => now(),
            ]);

        return $resena->refresh();
    }
}
