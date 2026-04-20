<?php

namespace App\Livewire\Catalogo;

use App\Models\Categoria;
use App\Models\Producto;
use App\Services\Busqueda\MeilisearchService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Livewire\Attributes\On;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Lista interactiva de productos publicados en el catalogo.
 */
class ListaProductos extends Component
{
    use WithPagination;

    /**
     * Texto de busqueda aplicado al catalogo.
     */
    #[Url(as: 'q')]
    public string $search = '';

    /**
     * Categoria seleccionada para filtrar productos.
     */
    #[Url(as: 'categoria')]
    public ?int $categoriaId = null;

    /**
     * Municipio seleccionado para filtrar vendedores.
     */
    #[Url]
    public ?string $municipio = null;

    /**
     * Orden visual del catalogo.
     */
    #[Url]
    public string $orden = 'relevancia';

    /**
     * Cantidad de productos por pagina.
     */
    public int $perPage = 12;

    /**
     * Municipios cubiertos por Atlantia en Izabal.
     *
     * @var array<int, string>
     */
    public array $municipios = [
        'Puerto Barrios',
        'Santo Tomas',
        'Morales',
        'Los Amates',
        'Livingston',
        'El Estor',
    ];

    /**
     * Reglas de validacion de filtros.
     *
     * @return array<string, string>
     */
    protected function rules(): array
    {
        return [
            'search' => 'nullable|string|max:120',
            'categoriaId' => 'nullable|integer|exists:categorias,id',
            'municipio' => 'nullable|string|in:' . implode(',', $this->municipios),
            'orden' => 'required|string|in:relevancia,precio_asc,precio_desc,recientes',
        ];
    }

    /**
     * Actualiza la busqueda desde otros componentes del catalogo.
     *
     * @param string $search
     * @return void
     */
    #[On('catalogo.busqueda-actualizada')]
    public function aplicarBusqueda(string $search): void
    {
        $this->search = trim($search);
        $this->resetPage();
    }

    /**
     * Actualiza la categoria desde el filtro lateral.
     *
     * @param int|null $categoriaId
     * @return void
     */
    #[On('catalogo.categoria-seleccionada')]
    public function aplicarCategoria(?int $categoriaId): void
    {
        $this->categoriaId = $categoriaId;
        $this->resetPage();
    }

    /**
     * Reinicia paginacion al cambiar texto de busqueda.
     *
     * @return void
     */
    public function updatedSearch(): void
    {
        $this->validateOnly('search');
        $this->resetPage();
    }

    /**
     * Reinicia paginacion al cambiar categoria.
     *
     * @return void
     */
    public function updatedCategoriaId(): void
    {
        $this->validateOnly('categoriaId');
        $this->resetPage();
    }

    /**
     * Reinicia paginacion al cambiar municipio.
     *
     * @return void
     */
    public function updatedMunicipio(): void
    {
        $this->validateOnly('municipio');
        $this->resetPage();
    }

    /**
     * Reinicia paginacion al cambiar orden.
     *
     * @return void
     */
    public function updatedOrden(): void
    {
        $this->validateOnly('orden');
        $this->resetPage();
    }

    /**
     * Limpia todos los filtros del catalogo.
     *
     * @return void
     */
    public function limpiarFiltros(): void
    {
        $this->reset(['search', 'categoriaId', 'municipio']);
        $this->orden = 'relevancia';
        $this->resetPage();
    }

    /**
     * Solicita agregar un producto al carrito.
     *
     * @param int $productoId
     * @return void
     */
    public function agregarAlCarrito(int $productoId): void
    {
        $producto = Producto::query()->publicados()->findOrFail($productoId);

        $this->dispatch('carrito.agregar-producto', productoId: $producto->id);
        $this->dispatch('notificacion', message: "{$producto->nombre} agregado al carrito.");
    }

    /**
     * Renderiza la lista de productos.
     *
     * @return View
     */
    public function render(): View
    {
        $this->validate();

        $resultados = app(MeilisearchService::class)->search($this->filters());

        return view('livewire.catalogo.lista-productos', [
            'productos' => $this->ordenarProductos(collect($resultados['items'])),
            'pagination' => $resultados['pagination'],
            'categorias' => Categoria::query()->active()->ordered()->get(),
        ]);
    }

    /**
     * Construye filtros seguros para el servicio de busqueda.
     *
     * @return array<string, mixed>
     */
    private function filters(): array
    {
        return [
            'q' => $this->search,
            'categoria_id' => $this->categoriaId,
            'municipio' => $this->municipio,
            'per_page' => $this->perPage,
        ];
    }

    /**
     * Ordena la coleccion devuelta por el servicio sin mutar datos de negocio.
     *
     * @param Collection<int, Producto> $productos
     * @return Collection<int, Producto>
     */
    private function ordenarProductos(Collection $productos): Collection
    {
        return match ($this->orden) {
            'precio_asc' => $productos->sortBy(fn (Producto $producto) => $this->precioOrdenable($producto))->values(),
            'precio_desc' => $productos
                ->sortByDesc(fn (Producto $producto) => $this->precioOrdenable($producto))
                ->values(),
            'recientes' => $productos->sortByDesc('publicado_at')->values(),
            default => $productos,
        };
    }

    /**
     * Obtiene el precio efectivo para ordenar.
     *
     * @param Producto $producto
     * @return float
     */
    private function precioOrdenable(Producto $producto): float
    {
        return (float) ($producto->precio_oferta ?? $producto->precio_base);
    }
}
