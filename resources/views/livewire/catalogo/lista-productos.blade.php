<section class="space-y-8" aria-labelledby="catalogo-productos-title">
    <header class="glass-surface space-y-5 rounded-[8px] p-5">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <p class="text-sm font-black uppercase tracking-[0.22em] text-atlantia-cyan-700">Catalogo</p>
                <h2 id="catalogo-productos-title" class="mt-2 text-3xl font-black tracking-tight text-atlantia-deep">
                    Todo lo que necesitas, ordenado para comprar mejor
                </h2>
            </div>

            <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
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

                <select
                    name="rating"
                    wire:model.live="ratingMin"
                    class="h-12 rounded-md border border-atlantia-cyan/30 bg-white/90 px-4 text-sm text-atlantia-deep shadow-sm focus:border-atlantia-cyan-700 focus:outline-none focus:ring-2 focus:ring-atlantia-cyan"
                >
                    <option value="0">Rating: todos</option>
                    <option value="3">3 estrellas o mas</option>
                    <option value="4">4 estrellas o mas</option>
                    <option value="5">5 estrellas</option>
                </select>
            </div>
        </div>

        <div class="grid gap-4 lg:grid-cols-[minmax(0,1.2fr)_minmax(0,0.8fr)]">
            <div>
                <p class="mb-3 text-sm font-bold text-atlantia-deep">Categorias</p>
                <div class="flex flex-wrap gap-2">
                    @foreach ($categorias as $categoria)
                        @php($seleccionada = in_array($categoria->id, $this->categoriaIds(), true))
                        <label class="{{ $seleccionada ? 'catalog-chip catalog-chip-active' : 'catalog-chip' }}">
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

            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <div class="mb-2 flex items-center justify-between gap-2">
                        <label class="text-sm font-bold text-atlantia-deep">Precio minimo</label>
                        <span class="text-sm font-semibold text-atlantia-cyan-700">Q {{ number_format($precioMin, 0) }}</span>
                    </div>
                    <input type="range" min="0" max="500" step="5" wire:model.live="precioMin" class="catalog-range w-full">
                </div>

                <div>
                    <div class="mb-2 flex items-center justify-between gap-2">
                        <label class="text-sm font-bold text-atlantia-deep">Precio maximo</label>
                        <span class="text-sm font-semibold text-atlantia-cyan-700">Q {{ number_format($precioMax, 0) }}</span>
                    </div>
                    <input type="range" min="50" max="1500" step="10" wire:model.live="precioMax" class="catalog-range w-full">
                </div>
            </div>
        </div>

        <div class="flex flex-wrap items-center justify-between gap-3 border-t border-atlantia-cyan/12 pt-4">
            <div class="flex flex-wrap gap-2">
                @if ($search)
                    <x-ui.badge variant="info">Busqueda: {{ $search }}</x-ui.badge>
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
            </div>

            <div class="flex items-center gap-4">
                <label class="flex items-center gap-3 text-sm font-semibold text-atlantia-deep">
                    <input type="checkbox" wire:model.live="soloEnStock" class="rounded border-atlantia-cyan text-atlantia-cyan-700">
                    Solo disponibles
                </label>

                <button type="button" class="text-sm font-bold text-atlantia-cyan-700 transition hover:text-atlantia-deep" wire:click="limpiarFiltros">
                    Limpiar filtros
                </button>
            </div>
        </div>
    </header>

    <div wire:loading.delay class="grid gap-4 sm:grid-cols-2 xl:grid-cols-5" aria-hidden="true">
        @for ($i = 0; $i < 10; $i++)
            <div class="animate-pulse rounded-[8px] border border-atlantia-cyan/20 bg-white/90 p-3 shadow-sm">
                <div class="mb-3 aspect-[4/3] rounded-md bg-slate-200"></div>
                <div class="mb-2 h-4 w-5/6 rounded bg-slate-200"></div>
                <div class="mb-3 h-4 w-1/2 rounded bg-slate-200"></div>
                <div class="mb-3 h-9 rounded bg-slate-200"></div>
                <div class="h-10 rounded bg-slate-200"></div>
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
            <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-5">
                @foreach ($productos as $producto)
                    <x-product-card :producto="$producto" wire:key="producto-{{ $producto->id }}" />
                @endforeach
            </div>

            <footer class="flex flex-col gap-4 border-t border-atlantia-cyan/12 pt-6 sm:flex-row sm:items-center sm:justify-between">
                <p class="text-sm text-atlantia-deep/65">
                    Pagina {{ $pagination['current_page'] }} de {{ $pagination['last_page'] }}
                </p>

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
</section>
