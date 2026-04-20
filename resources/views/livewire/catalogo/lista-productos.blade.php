<section class="space-y-6" aria-labelledby="catalogo-productos-title">
    <header class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <h1 id="catalogo-productos-title" class="text-2xl font-bold text-slate-950">
                Productos disponibles
            </h1>
            <p class="mt-1 text-sm text-slate-600">
                Compra a vendedores locales verificados de Izabal.
            </p>
        </div>

        <div class="grid gap-3 sm:grid-cols-2">
            <x-ui.select label="Municipio" name="municipio" wire:model.live="municipio">
                <option value="">Todos</option>
                @foreach ($municipios as $municipioOption)
                    <option value="{{ $municipioOption }}">{{ $municipioOption }}</option>
                @endforeach
            </x-ui.select>

            <x-ui.select label="Ordenar" name="orden" wire:model.live="orden">
                <option value="relevancia">Relevancia</option>
                <option value="precio_asc">Precio menor</option>
                <option value="precio_desc">Precio mayor</option>
                <option value="recientes">Recientes</option>
            </x-ui.select>
        </div>
    </header>

    @if ($search || $categoriaId || $municipio)
        <div class="flex flex-wrap items-center gap-2">
            @if ($search)
                <x-ui.badge variant="info">Busqueda: {{ $search }}</x-ui.badge>
            @endif

            @if ($categoriaId)
                <x-ui.badge variant="info">Categoria seleccionada</x-ui.badge>
            @endif

            @if ($municipio)
                <x-ui.badge variant="info">{{ $municipio }}</x-ui.badge>
            @endif

            <button type="button" class="text-sm font-semibold text-emerald-800" wire:click="limpiarFiltros">
                Limpiar filtros
            </button>
        </div>
    @endif

    <div wire:loading.delay class="rounded-lg border border-sky-200 bg-sky-50 px-4 py-3 text-sm text-sky-900">
        Actualizando catalogo...
    </div>

    @if ($productos->isEmpty())
        <x-ui.empty-state
            title="No encontramos productos con esos filtros"
            message="Prueba otra categoria, municipio o busqueda para continuar comprando."
        />
    @else
        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
            @foreach ($productos as $producto)
                <div wire:key="producto-{{ $producto->id }}" class="space-y-3">
                    <x-product-card :producto="$producto" />

                    <x-ui.button type="button" class="w-full" wire:click="agregarAlCarrito({{ $producto->id }})">
                        Agregar al carrito
                    </x-ui.button>
                </div>
            @endforeach
        </div>

        <footer class="flex items-center justify-between text-sm text-slate-600">
            <span>
                Pagina {{ $pagination['current_page'] }} de {{ $pagination['last_page'] }}
            </span>

            <div class="flex gap-2">
                <x-ui.button
                    type="button"
                    variant="secondary"
                    wire:click="previousPage"
                    :disabled="$pagination['current_page'] <= 1"
                >
                    Anterior
                </x-ui.button>
                <x-ui.button
                    type="button"
                    variant="secondary"
                    wire:click="nextPage"
                    :disabled="$pagination['current_page'] >= $pagination['last_page']"
                >
                    Siguiente
                </x-ui.button>
            </div>
        </footer>
    @endif
</section>
