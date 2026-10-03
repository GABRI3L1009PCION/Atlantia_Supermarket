<section class="space-y-5 sm:space-y-6" aria-labelledby="catalogo-productos-title">
    <header class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <h1 id="catalogo-productos-title" class="text-xl font-bold text-slate-950 sm:text-2xl">
                Productos disponibles
            </h1>
            <p class="mt-1 max-w-2xl text-sm leading-6 text-slate-600">
                Compra a vendedores locales verificados de Puerto Barrios y Santo Tomas de Castilla.
            </p>
        </div>

        <div class="grid w-full gap-3 sm:w-auto sm:grid-cols-[220px_auto] sm:items-end">
            <x-ui.select label="Ordenar" name="orden" wire:model.live="orden">
                <option value="relevancia">Relevancia</option>
                <option value="precio_asc">Precio menor</option>
                <option value="precio_desc">Precio mayor</option>
                <option value="recientes">Recientes</option>
                <option value="mas_vendido">Mas vendido</option>
                <option value="mas_nuevo">Mas nuevo</option>
            </x-ui.select>

            <label class="inline-flex min-h-11 w-full items-center gap-3 rounded-md border border-atlantia-rose/20 bg-white px-4 text-sm font-semibold text-atlantia-ink shadow-sm">
                <input type="checkbox" wire:model.live="soloEnStock" class="rounded border-atlantia-rose text-atlantia-wine">
                Solo disponibles
            </label>
        </div>
    </header>

    @if ($search || $categoriaId || ! empty($this->categoriaIds()) || $soloEnStock || $orden !== 'relevancia')
        <div class="flex flex-wrap items-center justify-between gap-3 rounded-lg border border-atlantia-rose/15 bg-white px-4 py-3 shadow-sm">
            <div class="flex flex-wrap items-center gap-2">
            @if ($search)
                <x-ui.badge variant="info">Busqueda: {{ $search }}</x-ui.badge>
            @endif

            @if (! empty($this->categoriaIds()))
                <x-ui.badge variant="info">Categorias aplicadas</x-ui.badge>
            @endif

            @if ($soloEnStock)
                <x-ui.badge variant="success">Solo en stock</x-ui.badge>
            @endif

            @if ($orden !== 'relevancia')
                <x-ui.badge variant="info">Orden personalizado</x-ui.badge>
            @endif
            </div>

            <button type="button" class="text-sm font-semibold text-atlantia-wine hover:underline" wire:click="limpiarFiltros">
                Limpiar filtros
            </button>
        </div>
    @endif

    @if ($productos->isEmpty())
        <x-ui.empty-state
            title="No encontramos productos con esos filtros"
            message="Prueba otra categoria, vendedor o busqueda para continuar comprando."
        />
    @else
        <div class="catalog-products-track flex items-stretch gap-3 overflow-x-auto pb-2 snap-x snap-mandatory lg:grid lg:grid-cols-7 lg:items-stretch lg:gap-3 lg:overflow-visible lg:snap-none">
                @foreach ($productos as $producto)
                    <x-product-card
                        :producto="$producto"
                        wire:key="producto-{{ $producto->id }}"
                        class="w-full shrink-0 basis-[calc((100%-0.75rem)/2)] snap-start lg:shrink lg:basis-auto lg:snap-none"
                    />
                @endforeach
            </div>

        <footer class="flex flex-col gap-3 text-sm text-slate-600 min-[420px]:flex-row min-[420px]:items-center min-[420px]:justify-between">
            <span>
                Pagina {{ $pagination['current_page'] }} de {{ $pagination['last_page'] }}
            </span>

            <div class="grid grid-cols-2 gap-2 min-[420px]:flex">
                <x-ui.button
                    type="button"
                    variant="secondary"
                    class="w-full min-[420px]:w-auto"
                    wire:click="previousPage"
                    :disabled="$pagination['current_page'] <= 1"
                >
                    Anterior
                </x-ui.button>
                <x-ui.button
                    type="button"
                    variant="secondary"
                    class="w-full min-[420px]:w-auto"
                    wire:click="nextPage"
                    :disabled="$pagination['current_page'] >= $pagination['last_page']"
                >
                    Siguiente
                </x-ui.button>
            </div>
        </footer>
    @endif
</section>
