<section class="space-y-6" aria-labelledby="catalogo-productos-title">
    <header class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <h1 id="catalogo-productos-title" class="text-2xl font-bold text-slate-950">
                Productos disponibles
            </h1>
            <p class="mt-1 text-sm text-slate-600">
                Compra a vendedores locales verificados de Puerto Barrios y Santo Tomas de Castilla.
            </p>
        </div>

        <div class="grid gap-3 sm:grid-cols-[220px_auto] sm:items-end">
            <x-ui.select label="Ordenar" name="orden" wire:model.live="orden">
                <option value="relevancia">Relevancia</option>
                <option value="precio_asc">Precio menor</option>
                <option value="precio_desc">Precio mayor</option>
                <option value="recientes">Recientes</option>
                <option value="mas_vendido">Mas vendido</option>
                <option value="mas_nuevo">Mas nuevo</option>
            </x-ui.select>

            <label class="inline-flex h-11 items-center gap-3 rounded-md border border-atlantia-rose/20 bg-white px-4 text-sm font-semibold text-atlantia-ink shadow-sm">
                <input type="checkbox" wire:model.live="soloEnStock" class="rounded border-atlantia-rose text-atlantia-wine">
                Solo disponibles
            </label>
        </div>
    </header>

    <div class="rounded-2xl border border-atlantia-rose/20 bg-white p-4 shadow-sm">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <p class="text-sm font-bold text-atlantia-ink">Categorias</p>

            @if ($search || $categoriaId || ! empty($categorias) || $soloEnStock || $orden !== 'relevancia')
                <button type="button" class="text-sm font-semibold text-atlantia-wine hover:underline sm:text-right" wire:click="limpiarFiltros">
                    Limpiar filtros
                </button>
            @endif
        </div>

        <div class="mt-3 flex flex-wrap gap-2">
            @foreach ($categorias as $categoria)
                @php($seleccionada = in_array($categoria->id, $this->categoriaIds(), true))
                <label class="inline-flex items-center gap-2 rounded-full border px-3 py-2 text-sm {{ $seleccionada ? 'border-atlantia-wine bg-atlantia-blush text-atlantia-wine' : 'border-slate-200 bg-white text-atlantia-ink hover:border-atlantia-rose/35' }}">
                    <input
                        type="checkbox"
                        value="{{ $categoria->id }}"
                        wire:model.live="categorias"
                        class="hidden"
                    >
                    {{ $categoria->nombre }}
                </label>
            @endforeach
        </div>
    </div>

    @if ($search || $categoriaId || ! empty($categorias) || $soloEnStock || $orden !== 'relevancia')
        <div class="flex flex-wrap items-center gap-2">
            @if ($search)
                <x-ui.badge variant="info">Busqueda: {{ $search }}</x-ui.badge>
            @endif

            @if (! empty($categorias))
                <x-ui.badge variant="info">Categorias aplicadas</x-ui.badge>
            @endif

            @if ($soloEnStock)
                <x-ui.badge variant="success">Solo en stock</x-ui.badge>
            @endif

            @if ($orden !== 'relevancia')
                <x-ui.badge variant="info">Orden personalizado</x-ui.badge>
            @endif
        </div>
    @endif

    <div wire:loading.delay class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3" aria-hidden="true">
        @for ($i = 0; $i < 6; $i++)
            <div class="animate-pulse rounded-lg border border-atlantia-rose/20 bg-white p-4 shadow-sm">
                <div class="mb-4 h-48 rounded-lg bg-slate-200"></div>
                <div class="mb-2 h-4 w-3/4 rounded bg-slate-200"></div>
                <div class="mb-4 h-4 w-1/2 rounded bg-slate-200"></div>
                <div class="h-10 rounded bg-slate-200"></div>
            </div>
        @endfor
    </div>

    <div wire:loading.remove>
    @if ($productos->isEmpty())
        <x-ui.empty-state
            title="No encontramos productos con esos filtros"
            message="Prueba otra categoria, vendedor o busqueda para continuar comprando."
        />
    @else
        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
                @foreach ($productos as $producto)
                    <div wire:key="producto-{{ $producto->id }}" class="space-y-3">
                        <x-product-card :producto="$producto" />

                        <x-ui.button
                            type="button"
                            class="w-full"
                            wire:click="agregarAlCarrito({{ $producto->id }})"
                            wire:loading.attr="disabled"
                            wire:target="agregarAlCarrito({{ $producto->id }})"
                        >
                            <span wire:loading.remove wire:target="agregarAlCarrito({{ $producto->id }})">
                                Agregar al carrito
                            </span>
                            <span wire:loading wire:target="agregarAlCarrito({{ $producto->id }})">
                                Procesando...
                            </span>
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
    </div>
</section>
