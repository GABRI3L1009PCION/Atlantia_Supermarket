<?php

namespace App\Services\Catalogo;

use App\Models\Categoria;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Str;

/**
 * Servicio de categorias jerarquicas.
 */
class CategoriaService
{
    /**
     * Devuelve arbol de categorias.
     *
     * @param array<string, mixed> $filters
     * @return Collection<int, Categoria>
     */
    public function tree(array $filters = []): Collection
    {
        return Categoria::query()
            ->with('children')
            ->root()
            ->ordered()
            ->get();
    }

    /**
     * Crea una categoria.
     *
     * @param array<string, mixed> $data
     */
    public function create(array $data): Categoria
    {
        $data['slug'] = $data['slug'] ?? Str::slug((string) $data['nombre']);

        return Categoria::query()->create($data);
    }

    /**
     * Actualiza una categoria.
     *
     * @param array<string, mixed> $data
     */
    public function update(Categoria $categoria, array $data): Categoria
    {
        if (isset($data['nombre']) && empty($data['slug'])) {
            $data['slug'] = Str::slug((string) $data['nombre']);
        }

        $categoria->update($data);

        return $categoria->refresh();
    }

    /**
     * Desactiva la categoria sin eliminar historial.
     */
    public function delete(Categoria $categoria): void
    {
        $categoria->update(['is_active' => false]);
    }
}

