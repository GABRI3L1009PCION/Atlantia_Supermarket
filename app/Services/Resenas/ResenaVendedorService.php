<?php

namespace App\Services\Resenas;

use App\Models\Resena;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

/**
 * Servicio de resenas visibles para vendedor.
 */
class ResenaVendedorService
{
    /**
     * Pagina resenas de productos propios.
     */
    public function paginate(User $user, array $filters = []): LengthAwarePaginator
    {
        return Resena::query()
            ->with(['producto.categoria', 'producto.imagenPrincipal', 'cliente'])
            ->whereHas('producto', fn ($query) => $query->where('vendor_id', $user->vendor?->id))
            ->when($filters['q'] ?? null, function ($query, string $term): void {
                $query->where(function ($query) use ($term): void {
                    $query
                        ->where('contenido', 'like', "%{$term}%")
                        ->orWhere('titulo', 'like', "%{$term}%")
                        ->orWhereHas('producto', fn ($query) => $query->where('nombre', 'like', "%{$term}%"))
                        ->orWhereHas('cliente', fn ($query) => $query->where('name', 'like', "%{$term}%"));
                });
            })
            ->when($filters['rating'] ?? null, fn ($query, string $rating) => $query->where('calificacion', (int) $rating))
            ->when(($filters['orden'] ?? 'recientes') === 'mejor_calificadas', fn ($query) => $query->orderByDesc('calificacion'))
            ->when(($filters['orden'] ?? 'recientes') === 'menor_calificadas', fn ($query) => $query->orderBy('calificacion'))
            ->latest()
            ->paginate(8)
            ->withQueryString();
    }

    /**
     * Datos resumidos para el panel de resenas del vendedor.
     *
     * @return array<string, mixed>
     */
    public function dashboard(User $user): array
    {
        $reviews = Resena::query()
            ->whereHas('producto', fn ($query) => $query->where('vendor_id', $user->vendor?->id))
            ->get();

        return [
            'promedio' => $reviews->count() ? round((float) $reviews->avg('calificacion'), 1) : 0.0,
            'total' => $reviews->count(),
            'pendientes' => $reviews->where('aprobada', false)->count(),
            'productos_mejor_valorados' => $this->topRatedProductCount($reviews),
        ];
    }

    /**
     * @param Collection<int, Resena> $reviews
     */
    private function topRatedProductCount(Collection $reviews): int
    {
        return $reviews
            ->groupBy('producto_id')
            ->filter(fn (Collection $items): bool => (float) $items->avg('calificacion') >= 4.5)
            ->count();
    }
}
