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

        <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
            <x-ui.select label="Municipio" name="municipio" wire:model.live="municipio">
                <option value="">Todos</option>
                @foreach ($municipios as $municipioOption)
                    <option value="{{ $municipioOption }}">{{ $municipioOption }}</option>
                @endforeach
            </x-ui.select>

            <x-ui.select label="Vendedor" name="vendor" wire:model.live="vendorId">
                <option value="">Todos</option>
                @foreach ($vendors as $vendor)
                    <option value="{{ $vendor->id }}">{{ $vendor->business_name }}</option>
                @endforeach
            </x-ui.select>

            <x-ui.select label="Ordenar" name="orden" wire:model.live="orden">
                <option value="relevancia">Relevancia</option>
                <option value="precio_asc">Precio menor</option>
                <option value="precio_desc">Precio mayor</option>
                <option value="recientes">Recientes</option>
                <option value="mas_vendido">Mas vendido</option>
                <option value="mas_nuevo">Mas nuevo</option>
            </x-ui.select>
        </div>
    </header>

    <div class="grid gap-4 rounded-2xl border border-atlantia-rose/20 bg-white p-4 lg:grid-cols-5">
        <div class="lg:col-span-2">
            <p class="text-sm font-bold text-atlantia-ink">Categorias</p>
            <div class="mt-3 flex flex-wrap gap-2">
                @foreach ($categorias as $categoria)
                    @php($seleccionada = in_array($categoria->id, $this->categoriaIds(), true))
                    <label class="inline-flex items-center gap-2 rounded-full border px-3 py-2 text-sm {{ $seleccionada ? 'border-atlantia-wine bg-atlantia-blush text-atlantia-wine' : 'border-slate-200 bg-white text-atlantia-ink' }}">
                        <input
                            type="checkbox"
                            value="{{ $categoria->id }}"
                            wire:model.live="categorias"
                            class="rounded border-atlantia-rose text-atlantia-wine"
                        >
                        {{ $categoria->nombre }}
                    </label>
                @endforeach
            </div>
        </div>

        <div>
            <label class="text-sm font-bold text-atlantia-ink">Precio minimo</label>
            <input type="range" min="0" max="500" step="5" wire:model.live="precioMin" class="mt-3 w-full">
            <p class="mt-2 text-sm text-atlantia-ink/70">Desde Q {{ number_format($precioMin, 0) }}</p>
        </div>

        <div>
            <label class="text-sm font-bold text-atlantia-ink">Precio maximo</label>
            <input type="range" min="50" max="1500" step="10" wire:model.live="precioMax" class="mt-3 w-full">
            <p class="mt-2 text-sm text-atlantia-ink/70">Hasta Q {{ number_format($precioMax, 0) }}</p>
        </div>

        <div class="space-y-4">
            <div>
                <label class="text-sm font-bold text-atlantia-ink">Rating minimo</label>
                <select
                    name="rating"
                    wire:model.live="ratingMin"
                    class="mt-3 w-full rounded-md border border-atlantia-rose/30 px-4 py-3 text-sm
                        focus:border-atlantia-wine focus:ring-atlantia-rose"
                >
                    <option value="0">Todos</option>
                    <option value="3">3 estrellas</option>
                    <option value="4">4 estrellas</option>
                    <option value="5">5 estrellas</option>
                </select>
            </div>
            <label class="flex items-center gap-3 text-sm font-semibold text-atlantia-ink">
                <input type="checkbox" wire:model.live="soloEnStock" class="rounded border-atlantia-rose text-atlantia-wine">
                Solo productos en stock
            </label>
        </div>
    </div>

    @if ($search || $categoriaId || ! empty($categorias) || $municipio || $vendorId || $soloEnStock || $ratingMin || $precioMin > 0 || $precioMax < 9999)
        <div class="flex flex-wrap items-center gap-2">
            @if ($search)
                <x-ui.badge variant="info">Busqueda: {{ $search }}</x-ui.badge>
            @endif

            @if (! empty($categorias))
                <x-ui.badge variant="info">Categorias aplicadas</x-ui.badge>
            @endif

            @if ($municipio)
                <x-ui.badge variant="info">{{ $municipio }}</x-ui.badge>
            @endif

            @if ($vendorId)
                <x-ui.badge variant="info">Vendedor filtrado</x-ui.badge>
            @endif

            @if ($soloEnStock)
                <x-ui.badge variant="success">Solo en stock</x-ui.badge>
            @endif

            <button type="button" class="text-sm font-semibold text-emerald-800" wire:click="limpiarFiltros">
                Limpiar filtros
            </button>
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
            message="Prueba otra categoria, municipio o busqueda para continuar comprando."
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
