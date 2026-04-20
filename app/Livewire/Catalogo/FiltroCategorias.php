<?php

namespace App\Livewire\Catalogo;

use App\Models\Categoria;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Component;

/**
 * Filtro de categorias activas del catalogo.
 */
class FiltroCategorias extends Component
{
    /**
     * Categoria actualmente seleccionada.
     */
    public ?int $categoriaSeleccionada = null;

    /**
     * Selecciona una categoria y notifica a la lista de productos.
     *
     * @param int|null $categoriaId
     * @return void
     */
    public function seleccionarCategoria(?int $categoriaId): void
    {
        $this->categoriaSeleccionada = $categoriaId;

        $this->dispatch('catalogo.categoria-seleccionada', categoriaId: $categoriaId);
    }

    /**
     * Renderiza el filtro de categorias.
     *
     * @return View
     */
    public function render(): View
    {
        return view('livewire.catalogo.filtro-categorias', [
            'categorias' => $this->categorias(),
        ]);
    }

    /**
     * Obtiene categorias raiz con hijas activas.
     *
     * @return Collection<int, Categoria>
     */
    private function categorias(): Collection
    {
        return Categoria::query()
            ->active()
            ->root()
            ->ordered()
            ->with(['children' => fn ($query) => $query->active()->ordered()])
            ->get();
    }
}
