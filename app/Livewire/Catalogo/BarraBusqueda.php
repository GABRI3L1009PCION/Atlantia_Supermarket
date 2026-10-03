<?php

namespace App\Livewire\Catalogo;

use Illuminate\Contracts\View\View;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * Barra de busqueda reactiva del catalogo.
 */
class BarraBusqueda extends Component
{
    /**
     * Texto de busqueda escrito por el cliente.
     */
    #[Url(as: 'q')]
    public string $search = '';

    /**
     * Reglas de validacion.
     *
     * @return array<string, string>
     */
    protected function rules(): array
    {
        return [
            'search' => 'nullable|string|max:120',
        ];
    }

    /**
     * Emite la busqueda actual.
     */
    public function buscar(): void
    {
        $this->validate();

        $this->dispatch('catalogo.busqueda-actualizada', search: trim($this->search));
    }

    /**
     * Limpia la busqueda actual.
     */
    public function limpiar(): void
    {
        $this->search = '';

        $this->dispatch('catalogo.busqueda-actualizada', search: '');
    }

    /**
     * Sincroniza cambios mientras el usuario escribe.
     */
    public function updatedSearch(): void
    {
        $this->buscar();
    }

    /**
     * Renderiza la barra de busqueda.
     */
    public function render(): View
    {
        return view('livewire.catalogo.barra-busqueda');
    }
}
