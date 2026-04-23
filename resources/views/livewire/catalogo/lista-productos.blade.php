<section class="grid gap-8 lg:grid-cols-[280px_minmax(0,1fr)]" aria-labelledby="catalogo-productos-title">
    <aside class="space-y-5 lg:sticky lg:top-24 lg:self-start">
        <div class="rounded-lg border border-atlantia-rose/20 bg-white p-5 shadow-sm">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <p class="text-xs font-bold uppercase tracking-wide text-atlantia-wine">Filtros</p>
                    <h2 class="mt-1 text-lg font-bold text-atlantia-ink">Ajusta tu compra</h2>
                </div>

                @if ($search || $categoriaId || ! empty($categorias) || $municipio || $vendorId || $soloEnStock || $ratingMin || $precioMin > 0 || $precioMax < 9999)
                    <button
                        type="button"
                        class="text-sm font-semibold text-atlantia-wine transition hover:text-atlantia-wine-700"
                        wire:click="limpiarFiltros"
                    >
                        Limpiar
                    </button>
                @endif
            </div>

            <div class="mt-5 space-y-5">
                <div>
                    <label class="text-sm font-bold text-atlantia-ink">Municipio</label>
                    <x-ui.select label="" name="municipio" wire:model.live="municipio">
                        <option value="">Todos</option>
                        @foreach ($municipios as $municipioOption)
                            <option value="{{ $municipioOption }}">{{ $municipioOption }}</option>
                        @endforeach
                    </x-ui.select>
                </div>

                <div>
                    <label class="text-sm font-bold text-atlantia-ink">Vendedor</label>
                    <x-ui.select label="" name="vendor" wire:model.live="vendorId">
                        <option value="">Todos</option>
                        @foreach ($vendors as $vendor)
                            <option value="{{ $vendor->id }}">{{ $vendor->business_name }}</option>
                        @endforeach
                    </x-ui.select>
                </div>

                <div>
                    <label class="text-sm font-bold text-atlantia-ink">Categorias</label>
                    <div class="mt-3 space-y-2">
                        @foreach ($categorias as $categoria)
                            @php($seleccionada = in_array($categoria->id, $this->categoriaIds(), true))
                            <label class="flex items-center gap-3 rounded-md border px-3 py-2 text-sm transition {{ $seleccionada ? 'border-atlantia-wine bg-atlantia-blush text-atlantia-wine' : 'border-slate-200 bg-white text-atlantia-ink hover:border-atlantia-rose/40' }}">
                                <input
                                    type="checkbox"
                                    value="{{ $categoria->id }}"
                                    wire:model.live="categorias"
                                    class="rounded border-atlantia-rose text-atlantia-wine"
                                >
                                <span class="truncate">{{ $categoria->nombre }}</span>
                            </label>
                        @endforeach
                    </div>
                </div>

                <div>
                    <div class="flex items-center justify-between gap-3">
                        <label class="text-sm font-bold text-atlantia-ink">Precio</label>
                        <span class="text-xs font-semibold uppercase tracking-wide text-atlantia-wine">
                            Q {{ number_format($precioMin, 0) }} - Q {{ number_format($precioMax, 0) }}
                        </span>
                    </div>

                    <div class="mt-4 space-y-4">
                        <div>
                            <input type="range" min="0" max="500" step="5" wire:model.live="precioMin" class="catalog-range w-full">
                            <p class="mt-1 text-xs text-atlantia-ink/60">Minimo</p>
                        </div>
                        <div>
                            <input type="range" min="50" max="1500" step="10" wire:model.live="precioMax" class="catalog-range w-full">
                            <p class="mt-1 text-xs text-atlantia-ink/60">Maximo</p>
                        </div>
                    </div>
                </div>

                <div>
                    <label class="text-sm font-bold text-atlantia-ink">Ordenar por</label>
                    <x-ui.select label="" name="orden" wire:model.live="orden">
                        <option value="relevancia">Relevancia</option>
                        <option value="precio_asc">Precio menor</option>
                        <option value="precio_desc">Precio mayor</option>
                        <option value="recientes">Recientes</option>
                        <option value="mas_vendido">Mas vendido</option>
                        <option value="mas_nuevo">Mas nuevo</option>
                    </x-ui.select>
                </div>

                <div>
                    <label class="text-sm font-bold text-atlantia-ink">Rating minimo</label>
                    <select
                        name="rating"
                        wire:model.live="ratingMin"
                        class="mt-3 h-12 w-full rounded-md border border-atlantia-rose/30 bg-white px-4 text-sm text-atlantia-ink focus:border-atlantia-wine focus:outline-none focus:ring-2 focus:ring-atlantia-rose/40"
                    >
                        <option value="0">Todos</option>
                        <option value="3">3 estrellas</option>
                        <option value="4">4 estrellas</option>
                        <option value="5">5 estrellas</option>
                    </select>
                </div>

                <label class="flex items-center gap-3 rounded-md border border-slate-200 bg-atlantia-cream px-3 py-3 text-sm font-semibold text-atlantia-ink">
                    <input type="checkbox" wire:model.live="soloEnStock" class="rounded border-atlantia-rose text-atlantia-wine">
                    Solo productos disponibles hoy
                </label>
            </div>
        </div>
    </aside>

    <div class="space-y-6">
        <header class="flex flex-col gap-4 rounded-lg border border-atlantia-rose/15 bg-white p-5 shadow-sm">
            <div class="flex flex-col gap-4 md:flex-row md:items-end md:justify-between">
                <div>
                    <p class="text-xs font-bold uppercase tracking-wide text-atlantia-wine">Catalogo activo</p>
                    <h1 id="catalogo-productos-title" class="mt-1 text-3xl font-black tracking-tight text-atlantia-ink">
                        Productos disponibles
                    </h1>
                    <p class="mt-2 max-w-2xl text-sm leading-6 text-atlantia-ink/70">
                        Filtra por municipio, compara precios y compra a vendedores locales verificados sin perder visibilidad del stock.
                    </p>
                </div>

                <div class="rounded-lg bg-atlantia-cream px-4 py-3 text-sm font-semibold text-atlantia-ink/75">
                    Pagina {{ $pagination['current_page'] }} de {{ $pagination['last_page'] }}
                </div>
            </div>

            @if ($search || $categoriaId || ! empty($categorias) || $municipio || $vendorId || $soloEnStock || $ratingMin || $precioMin > 0 || $precioMax < 9999)
                <div class="flex flex-wrap gap-2">
                    @if ($search)
                        <x-ui.badge variant="info">Busqueda: {{ $search }}</x-ui.badge>
                    @endif

                    @if (! empty($categorias))
                        <x-ui.badge variant="info">{{ count($categorias) }} categorias</x-ui.badge>
                    @endif

                    @if ($municipio)
                        <x-ui.badge variant="info">{{ $municipio }}</x-ui.badge>
                    @endif

                    @if ($vendorId)
                        <x-ui.badge variant="info">Vendedor filtrado</x-ui.badge>
                    @endif

                    @if ($soloEnStock)
                        <x-ui.badge variant="success">En stock</x-ui.badge>
                    @endif

                    @if ($ratingMin)
                        <x-ui.badge variant="info">{{ $ratingMin }} estrellas o mas</x-ui.badge>
                    @endif
                </div>
            @endif
        </header>

        <div wire:loading.delay class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3 2xl:grid-cols-4" aria-hidden="true">
            @for ($i = 0; $i < 8; $i++)
                <div class="animate-pulse rounded-lg border border-atlantia-rose/20 bg-white p-4 shadow-sm">
                    <div class="mb-4 aspect-[4/3] rounded-lg bg-slate-200"></div>
                    <div class="mb-2 h-4 w-2/3 rounded bg-slate-200"></div>
                    <div class="mb-4 h-5 w-5/6 rounded bg-slate-200"></div>
                    <div class="mb-3 h-4 w-1/3 rounded bg-slate-200"></div>
                    <div class="h-11 rounded-md bg-slate-200"></div>
                </div>
            @endfor
        </div>

        <div wire:loading.remove>
            @if ($productos->isEmpty())
                <x-ui.empty-state
                    title="No encontramos productos con esos filtros"
                    message="Prueba otra categoria, municipio o busqueda para seguir comprando."
                />
            @else
                <div class="grid gap-5 sm:grid-cols-2 xl:grid-cols-3 2xl:grid-cols-4">
                    @foreach ($productos as $producto)
                        <x-product-card :producto="$producto" wire:key="producto-{{ $producto->id }}" />
                    @endforeach
                </div>

                <footer class="flex flex-col gap-4 rounded-lg border border-atlantia-rose/15 bg-white p-5 shadow-sm sm:flex-row sm:items-center sm:justify-between">
                    <div class="text-sm text-atlantia-ink/70">
                        Mostrando la pagina {{ $pagination['current_page'] }} de {{ $pagination['last_page'] }}.
                    </div>

                    <div class="flex flex-wrap gap-2">
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
    </div>
</section>
