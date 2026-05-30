@extends(auth()->user()?->isSuperAdmin() && request()->routeIs('admin.*') ? 'layouts.super-admin' : 'layouts.app')

@section('content')
    @php
        $showCreateModal = $errors->any();
        $unidades = ['unidad', 'libra', 'kilogramo', 'gramo', 'litro', 'mililitro', 'paquete'];
        $selectedOwnerType = old('owner_type', 'atlantia');
        $perPageOptions = [8, 12, 24, 48];
        $currentPerPage = (int) $productos->perPage();
        $filterValues = [
            'q' => (string) request('q', ''),
            'categoria_id' => (string) request('categoria_id', ''),
            'vendor_id' => (string) request('vendor_id', ''),
            'estado' => (string) request('estado', ''),
            'stock' => (string) request('stock', ''),
            'orden' => (string) request('orden', 'recientes'),
        ];
        $activeFilters = collect([
            'Busqueda: ' . $filterValues['q'] => $filterValues['q'],
            'Categoria activa' => $filterValues['categoria_id'],
            'Proveedor activo' => $filterValues['vendor_id'],
            'Estado: ' . $filterValues['estado'] => $filterValues['estado'],
            'Stock: ' . $filterValues['stock'] => $filterValues['stock'],
        ])->filter();
    @endphp

    <section class="mx-auto max-w-full py-2">
        <div class="rounded-2xl border border-atlantia-rose/20 bg-white p-6 shadow-sm">
            <div class="flex flex-col gap-5 lg:flex-row lg:items-start lg:justify-between">
                <x-page-header title="Productos" subtitle="Administra el catalogo multivendedor y el inventario inicial." />

                <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
                    <div class="rounded-xl border border-atlantia-rose/25 bg-atlantia-cream px-4 py-3">
                        <p class="text-sm font-black text-atlantia-wine">{{ $productos->total() }} productos registrados</p>
                        <p class="text-xs text-atlantia-ink/60">Catalogo propio y de vendedores locales.</p>
                    </div>
                    <button
                        type="button"
                        class="rounded-lg bg-atlantia-wine px-5 py-3 text-sm font-black text-white shadow-sm transition hover:bg-atlantia-wine-700"
                        data-open-create-modal
                    >
                        Crear nuevo producto
                    </button>
                </div>
            </div>

            <div class="mt-7 overflow-hidden rounded-2xl border border-atlantia-rose/20 bg-white shadow-sm">
                <div class="border-b border-atlantia-rose/15 bg-gradient-to-r from-white via-white to-atlantia-blush/20 p-5">
                    <div class="flex flex-col gap-4 xl:flex-row xl:items-center xl:justify-between">
                        <div>
                            <h2 class="text-2xl font-black leading-tight text-atlantia-wine">Catalogo registrado</h2>
                            <p class="mt-1 text-sm text-atlantia-ink/60">Administra inventario, precios y estado de forma visual y rapida.</p>
                        </div>
                        <button
                            type="button"
                            class="inline-flex items-center justify-center gap-2 rounded-lg bg-atlantia-wine px-5 py-3 text-sm font-black text-white shadow-sm transition hover:bg-atlantia-wine-700"
                            data-open-create-modal
                        >
                            <span class="text-lg leading-none">+</span>
                            Agregar producto
                        </button>
                    </div>

                    <form method="GET" action="{{ route('admin.productos.index') }}" class="mt-5 space-y-3" autocomplete="off" data-admin-product-filters>
                        <input type="hidden" name="per_page" value="{{ $currentPerPage }}">

                        <div class="flex flex-col gap-3 lg:flex-row">
                            <div class="relative flex-1">
                                <input
                                    type="search"
                                    name="q"
                                    value="{{ $filterValues['q'] }}"
                                    placeholder="Buscar producto, SKU o codigo de barras"
                                    class="w-full rounded-lg border border-atlantia-rose/25 bg-white px-4 py-3 pr-12 text-sm outline-none transition focus:border-atlantia-wine focus:ring-2 focus:ring-atlantia-rose/20"
                                    autocomplete="off"
                                    data-admin-products-server-value="{{ $filterValues['q'] }}"
                                >
                                <button type="submit" class="absolute right-2 top-1/2 grid h-8 w-8 -translate-y-1/2 place-items-center rounded-md text-atlantia-wine transition hover:bg-atlantia-blush" aria-label="Buscar">
                                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                        <circle cx="11" cy="11" r="7" />
                                        <path d="m20 20-3.5-3.5" />
                                    </svg>
                                </button>
                            </div>
                        </div>

                        <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-5">
                            <label class="block">
                                <span class="text-xs font-black uppercase tracking-wide text-atlantia-ink/55">Categoria</span>
                                <select name="categoria_id" class="mt-1 w-full rounded-lg border border-atlantia-rose/25 bg-white px-3 py-2.5 text-sm outline-none transition focus:border-atlantia-wine focus:ring-2 focus:ring-atlantia-rose/20" autocomplete="off" data-admin-product-filter data-admin-products-server-value="{{ $filterValues['categoria_id'] }}">
                                    <option value="">Todas</option>
                                    @foreach ($categorias as $categoria)
                                        <option value="{{ $categoria->id }}" @selected($filterValues['categoria_id'] === (string) $categoria->id)>{{ $categoria->nombre }}</option>
                                    @endforeach
                                </select>
                            </label>

                            <label class="block">
                                <span class="text-xs font-black uppercase tracking-wide text-atlantia-ink/55">Proveedor</span>
                                <select name="vendor_id" class="mt-1 w-full rounded-lg border border-atlantia-rose/25 bg-white px-3 py-2.5 text-sm outline-none transition focus:border-atlantia-wine focus:ring-2 focus:ring-atlantia-rose/20" autocomplete="off" data-admin-product-filter data-admin-products-server-value="{{ $filterValues['vendor_id'] }}">
                                    <option value="">Todos</option>
                                    @foreach ($vendors as $vendor)
                                        <option value="{{ $vendor->id }}" @selected($filterValues['vendor_id'] === (string) $vendor->id)>{{ $vendor->business_name }}</option>
                                    @endforeach
                                </select>
                            </label>

                            <label class="block">
                                <span class="text-xs font-black uppercase tracking-wide text-atlantia-ink/55">Estado</span>
                                <select name="estado" class="mt-1 w-full rounded-lg border border-atlantia-rose/25 bg-white px-3 py-2.5 text-sm outline-none transition focus:border-atlantia-wine focus:ring-2 focus:ring-atlantia-rose/20" autocomplete="off" data-admin-product-filter data-admin-products-server-value="{{ $filterValues['estado'] }}">
                                    <option value="">Todos</option>
                                    <option value="activo" @selected($filterValues['estado'] === 'activo')>Activos</option>
                                    <option value="inactivo" @selected($filterValues['estado'] === 'inactivo')>Inactivos</option>
                                </select>
                            </label>

                            <label class="block">
                                <span class="text-xs font-black uppercase tracking-wide text-atlantia-ink/55">Stock</span>
                                <select name="stock" class="mt-1 w-full rounded-lg border border-atlantia-rose/25 bg-white px-3 py-2.5 text-sm outline-none transition focus:border-atlantia-wine focus:ring-2 focus:ring-atlantia-rose/20" autocomplete="off" data-admin-product-filter data-admin-products-server-value="{{ $filterValues['stock'] }}">
                                    <option value="">Todos</option>
                                    <option value="disponible" @selected($filterValues['stock'] === 'disponible')>Disponible</option>
                                    <option value="bajo" @selected($filterValues['stock'] === 'bajo')>Stock bajo</option>
                                    <option value="agotado" @selected($filterValues['stock'] === 'agotado')>Agotado</option>
                                </select>
                            </label>

                            <label class="block">
                                <span class="text-xs font-black uppercase tracking-wide text-atlantia-ink/55">Ordenar por</span>
                                <select name="orden" class="mt-1 w-full rounded-lg border border-atlantia-rose/25 bg-white px-3 py-2.5 text-sm outline-none transition focus:border-atlantia-wine focus:ring-2 focus:ring-atlantia-rose/20" autocomplete="off" data-admin-product-filter data-admin-products-server-value="{{ $filterValues['orden'] }}">
                                    <option value="recientes" @selected($filterValues['orden'] === 'recientes')>Mas recientes</option>
                                    <option value="nombre" @selected($filterValues['orden'] === 'nombre')>Nombre</option>
                                    <option value="precio_asc" @selected($filterValues['orden'] === 'precio_asc')>Precio menor</option>
                                    <option value="precio_desc" @selected($filterValues['orden'] === 'precio_desc')>Precio mayor</option>
                                    <option value="stock_asc" @selected($filterValues['orden'] === 'stock_asc')>Menor stock</option>
                                </select>
                            </label>
                        </div>

                        <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                            <div class="flex flex-wrap items-center gap-2 text-xs font-bold">
                                <span class="rounded-full border border-atlantia-rose/20 bg-white px-3 py-1.5 text-atlantia-ink/65">
                                    Filtros activos: {{ $activeFilters->count() }}
                                </span>

                                @foreach ($activeFilters as $label => $value)
                                    <span class="rounded-full bg-atlantia-blush px-3 py-1.5 text-atlantia-wine">{{ $label }}</span>
                                @endforeach

                                @if ($activeFilters->isNotEmpty())
                                    <a href="{{ route('admin.productos.index') }}" class="rounded-full px-3 py-1.5 text-atlantia-wine underline-offset-4 hover:underline">Limpiar filtros</a>
                                @endif
                            </div>

                            <div class="flex items-center gap-2">
                                <button type="submit" class="rounded-lg border border-atlantia-rose/25 bg-white px-4 py-2 text-sm font-black text-atlantia-wine transition hover:bg-atlantia-blush">
                                    Aplicar filtros
                                </button>
                                <div class="hidden items-center rounded-lg border border-atlantia-rose/20 bg-white p-1 sm:flex">
                                    <span class="rounded-md bg-atlantia-blush px-3 py-1.5 text-xs font-black text-atlantia-wine">Cards</span>
                                    <span class="px-3 py-1.5 text-xs font-black text-atlantia-ink/45">Lista</span>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>

                <div class="p-5">
                    <div class="mb-4 flex flex-col gap-2 text-sm text-atlantia-ink/60 sm:flex-row sm:items-center sm:justify-between">
                        <p>
                            Mostrando <span class="font-black text-atlantia-wine">{{ $productos->firstItem() ?? 0 }}-{{ $productos->lastItem() ?? 0 }}</span>
                            de <span class="font-black text-atlantia-wine">{{ $productos->total() }}</span> productos
                        </p>
                        <form method="GET" action="{{ route('admin.productos.index') }}" class="flex items-center gap-2" autocomplete="off" data-admin-product-per-page-form>
                            @foreach (request()->except(['per_page', 'page']) as $key => $value)
                                <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                            @endforeach
                            <span class="text-xs font-bold text-atlantia-ink/55">Mostrar</span>
                            <select name="per_page" class="rounded-md border border-atlantia-rose/25 bg-white px-2 py-1 text-xs outline-none" autocomplete="off" data-admin-product-per-page data-admin-products-server-value="{{ $currentPerPage }}">
                                @foreach ($perPageOptions as $size)
                                    <option value="{{ $size }}" @selected($currentPerPage === $size)>{{ $size }}</option>
                                @endforeach
                            </select>
                            <span class="text-xs font-bold text-atlantia-ink/55">por pagina</span>
                        </form>
                    </div>

                    <div class="grid grid-cols-[repeat(auto-fill,minmax(150px,1fr))] gap-3">
                        @forelse ($productos as $producto)
                            @php
                                $imageUrl = $producto->getFirstMediaUrl('productos', 'thumbnail')
                                    ?: $producto->getFirstMediaUrl('productos')
                                    ?: ($producto->imagenPrincipal?->path ? \Illuminate\Support\Facades\Storage::url($producto->imagenPrincipal->path) : null);
                                $isOwnProduct = $producto->vendor?->slug === 'atlantia-supermarket';
                                $stockActual = (int) ($producto->inventario?->stock_actual ?? 0);
                                $hasOffer = $producto->precio_oferta !== null && (float) $producto->precio_oferta > 0;
                                $displayPrice = $hasOffer ? $producto->precio_oferta : $producto->precio_base;
                            @endphp

                            <article class="group overflow-hidden rounded-2xl border border-atlantia-rose/20 bg-white shadow-[0_10px_24px_rgba(68,32,50,0.08)] transition hover:-translate-y-0.5 hover:border-atlantia-rose/40 hover:shadow-[0_18px_34px_rgba(68,32,50,0.14)]">
                                <div class="relative h-24 bg-gradient-to-br from-atlantia-cream via-white to-atlantia-blush/30 p-3">
                                    @if ($producto->requiere_refrigeracion)
                                        <span
                                            class="absolute left-2 top-2 grid h-9 w-9 place-items-center rounded-full bg-sky-50/90 text-sky-500 shadow-sm ring-1 ring-sky-200 backdrop-blur"
                                            title="Requiere refrigeracion"
                                            aria-label="Requiere refrigeracion"
                                        >
                                            <svg class="h-7 w-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                                <path d="M12 2v20" />
                                                <path d="m8 4 4 4 4-4" />
                                                <path d="m8 20 4-4 4 4" />
                                                <path d="M2 12h20" />
                                                <path d="m4 8 4 4-4 4" />
                                                <path d="m20 8-4 4 4 4" />
                                                <path d="m5 5 14 14" />
                                                <path d="m19 5-14 14" />
                                            </svg>
                                        </span>
                                    @endif

                                    @if ($imageUrl)
                                        <img src="{{ $imageUrl }}" alt="{{ $producto->nombre }}" class="h-full w-full object-contain drop-shadow-md transition duration-300 group-hover:scale-105">
                                    @else
                                        <div class="grid h-full place-items-center">
                                            <div class="grid h-11 w-11 place-items-center rounded-xl bg-atlantia-blush text-lg font-black text-atlantia-wine">
                                                {{ \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($producto->nombre ?? 'P', 0, 1)) }}
                                            </div>
                                        </div>
                                    @endif

                                    <div class="absolute right-2 top-2 flex flex-col items-end gap-1">
                                        <span @class([
                                            'rounded-full px-2 py-0.5 text-[9px] font-black shadow-sm ring-1 backdrop-blur',
                                            'bg-emerald-50 text-emerald-700 ring-emerald-200' => $isOwnProduct,
                                            'bg-indigo-50 text-indigo-700 ring-indigo-200' => ! $isOwnProduct,
                                        ])>
                                            {{ $isOwnProduct ? 'Propio' : 'Local' }}
                                        </span>
                                        <span @class([
                                            'rounded-full px-2 py-0.5 text-[9px] font-black shadow-sm ring-1 backdrop-blur',
                                            'bg-emerald-50 text-emerald-700 ring-emerald-200' => $producto->is_active,
                                            'bg-slate-100 text-slate-600 ring-slate-200' => ! $producto->is_active,
                                        ])>
                                            {{ $producto->is_active ? 'Activo' : 'Inactivo' }}
                                        </span>
                                    </div>
                                </div>

                                <div class="space-y-2.5 p-3">
                                    <div>
                                        <h3 class="line-clamp-1 text-[13px] font-black leading-tight text-atlantia-ink">{{ $producto->nombre }}</h3>
                                        <p class="mt-1 truncate font-mono text-[9px] font-semibold text-atlantia-ink/45">{{ $producto->codigo_barras ?: $producto->sku }}</p>
                                        <p class="mt-0.5 truncate text-[10px] text-atlantia-ink/45">{{ $producto->categoria?->nombre }} - {{ $producto->vendor?->business_name }}</p>
                                    </div>

                                    <div class="flex items-end justify-between gap-3 rounded-xl bg-atlantia-cream/45 px-2 py-1.5">
                                        <div>
                                            <p class="text-[9px] font-bold uppercase tracking-wide text-atlantia-ink/45">Precio</p>
                                            <p class="text-[15px] font-black leading-tight text-atlantia-wine">Q{{ number_format((float) $displayPrice, 2) }}</p>
                                        </div>
                                        <div class="text-right">
                                            <p class="text-[9px] font-bold uppercase tracking-wide text-atlantia-ink/45">Stock</p>
                                            <p @class([
                                                'text-xs font-black',
                                                'text-emerald-700' => $stockActual > 10,
                                                'text-amber-700' => $stockActual > 0 && $stockActual <= 10,
                                                'text-rose-700' => $stockActual <= 0,
                                            ])>
                                                {{ $stockActual }}
                                            </p>
                                        </div>
                                    </div>

                                    <div class="grid grid-cols-3 gap-1.5 border-t border-atlantia-rose/15 pt-2 text-[10px] font-black">
                                        <a href="{{ route('admin.productos.show', $producto) }}" class="inline-flex items-center justify-center gap-1 rounded-lg border border-atlantia-rose/20 bg-white px-1.5 py-1.5 text-atlantia-ink/70 transition hover:bg-atlantia-blush hover:text-atlantia-wine">
                                            <span>Ver</span>
                                        </a>
                                        <a href="{{ route('admin.productos.show', $producto) }}" class="inline-flex items-center justify-center gap-1 rounded-lg border border-atlantia-rose/20 bg-white px-1.5 py-1.5 text-atlantia-ink/70 transition hover:bg-atlantia-blush hover:text-atlantia-wine">
                                            <span>Editar</span>
                                        </a>
                                        @can('delete', $producto)
                                            <form method="POST" action="{{ route('admin.productos.destroy', $producto) }}" onsubmit="return confirm('Eliminar el producto {{ addslashes($producto->nombre) }}?')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="w-full rounded-lg border border-rose-200 bg-white px-1.5 py-1.5 text-rose-700 transition hover:bg-rose-50">
                                                    Borrar
                                                </button>
                                            </form>
                                        @else
                                            <span class="inline-flex items-center justify-center rounded-lg border border-atlantia-rose/20 bg-white px-1.5 py-1.5 text-atlantia-ink/35">Borrar</span>
                                        @endcan
                                    </div>

                                    <div class="grid grid-cols-2 gap-1.5">
                                        <button
                                            type="button"
                                            class="inline-flex items-center justify-center gap-1 rounded-lg border border-atlantia-rose/20 bg-gradient-to-r from-white to-atlantia-cream/80 px-2 py-1.5 text-[9px] font-black leading-tight text-atlantia-wine transition hover:bg-atlantia-blush"
                                            data-open-barcode-modal
                                            data-product-name="{{ $producto->nombre }}"
                                            data-product-sku="{{ $producto->sku }}"
                                            data-product-barcode="{{ $producto->codigo_barras ?: $producto->sku }}"
                                            data-product-price="Q{{ number_format((float) $displayPrice, 2) }}"
                                            data-product-vendor="{{ $producto->vendor?->business_name }}"
                                            data-product-image="{{ $imageUrl }}"
                                        >
                                            <span class="text-sm leading-none">|||</span>
                                            <span>Imprimir codigo</span>
                                        </button>
                                        <button
                                            type="button"
                                            class="inline-flex items-center justify-center gap-1 rounded-lg border border-atlantia-rose/20 bg-gradient-to-r from-white to-atlantia-cream/80 px-2 py-1.5 text-[9px] font-black leading-tight text-atlantia-wine transition hover:bg-atlantia-blush"
                                            data-open-price-modal
                                            data-product-name="{{ $producto->nombre }}"
                                            data-product-sku="{{ $producto->sku }}"
                                            data-product-barcode="{{ $producto->codigo_barras ?: $producto->sku }}"
                                            data-product-price="Q{{ number_format((float) $displayPrice, 2) }}"
                                            data-product-vendor="{{ $producto->vendor?->business_name }}"
                                            data-product-image="{{ $imageUrl }}"
                                        >
                                            <span class="text-sm leading-none">◆</span>
                                            <span>Imprimir precio</span>
                                        </button>
                                    </div>
                                </div>
                            </article>
                        @empty
                            <div class="col-span-full rounded-2xl border border-dashed border-atlantia-rose/30 bg-atlantia-cream/60 px-6 py-12 text-center">
                                <p class="text-lg font-black text-atlantia-ink">No hay productos con estos filtros.</p>
                                <p class="mt-1 text-sm text-atlantia-ink/60">Limpia los filtros o agrega un nuevo producto al catalogo.</p>
                                <button type="button" class="mt-5 rounded-lg bg-atlantia-wine px-5 py-3 text-sm font-black text-white shadow-sm transition hover:bg-atlantia-wine-700" data-open-create-modal>
                                    Agregar producto
                                </button>
                            </div>
                        @endforelse
                    </div>

                    <div class="mt-5">{{ $productos->links() }}</div>
                </div>
            </div>
        </div>
    </section>

    <div
        class="fixed inset-0 z-[60] hidden items-start justify-center overflow-y-auto bg-slate-950/60 px-2 py-1 backdrop-blur-md sm:px-3 sm:py-2"
        data-barcode-modal
        role="dialog"
        aria-modal="true"
        aria-labelledby="barcode-print-title"
    >
        <div class="w-full max-w-[820px] overflow-hidden rounded-2xl border border-atlantia-rose/20 bg-white text-xs shadow-[0_28px_90px_rgba(33,25,32,0.28)]">
            <div class="flex items-start justify-between gap-3 border-b border-atlantia-rose/15 px-4 py-2.5">
                <div>
                    <h2 id="barcode-print-title" class="text-base font-black text-atlantia-wine">Imprimir codigo de barras</h2>
                    <p class="mt-0.5 text-[11px] text-atlantia-ink/60">Genera e imprime etiquetas con el codigo de barras del producto seleccionado.</p>
                </div>
                <button type="button" class="rounded-lg p-1.5 text-atlantia-ink/50 transition hover:bg-atlantia-blush hover:text-atlantia-wine" data-close-barcode-modal aria-label="Cerrar">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <path d="m6 6 12 12M18 6 6 18" />
                    </svg>
                </button>
            </div>

            <div class="grid gap-3 px-4 py-3 lg:grid-cols-[1fr_190px]">
                <div class="space-y-2.5">
                    <div class="grid gap-2 rounded-xl border border-atlantia-rose/15 bg-atlantia-cream/35 p-2.5 md:grid-cols-[160px_1fr_125px]">
                        <div class="flex items-center gap-2 rounded-xl bg-white p-2 shadow-sm">
                            <div class="grid h-11 w-11 shrink-0 place-items-center overflow-hidden rounded-xl bg-atlantia-blush">
                                <img src="" alt="" class="hidden h-full w-full object-contain p-1" data-barcode-product-image>
                                <span class="text-base font-black text-atlantia-wine" data-barcode-product-initial>A</span>
                            </div>
                            <div class="min-w-0">
                                <p class="truncate text-[11px] font-black text-atlantia-ink" data-barcode-product-name>Producto</p>
                                <p class="mt-0.5 truncate text-[10px] text-atlantia-ink/55">SKU: <span data-barcode-product-sku></span></p>
                                <p class="truncate text-[10px] text-atlantia-ink/55">Proveedor: <span data-barcode-product-vendor></span></p>
                                <p class="mt-0.5 text-xs font-black text-atlantia-wine" data-barcode-product-price>Q0.00</p>
                            </div>
                        </div>

                        <div class="rounded-xl bg-white p-2 shadow-sm">
                            <p class="mb-1 text-[10px] font-black uppercase tracking-wide text-atlantia-ink/55">Vista del codigo de barras</p>
                            <div class="grid place-items-center rounded-lg border border-atlantia-rose/15 bg-white px-2 py-1.5">
                                <div data-barcode-svg class="w-full max-w-[240px]"></div>
                            </div>
                        </div>

                        <div class="rounded-xl bg-white p-2 shadow-sm">
                            <p class="mb-1.5 text-[11px] font-black text-atlantia-ink">Etiqueta</p>
                            <div class="grid place-items-center rounded-lg bg-atlantia-cream/60 p-1.5">
                                <div data-barcode-label-preview class="w-[94px] rounded-lg border border-atlantia-rose/25 bg-white p-1 text-center shadow-sm"></div>
                            </div>
                        </div>
                    </div>

                    <div class="grid gap-2.5 md:grid-cols-3">
                        <div class="rounded-xl border border-atlantia-rose/15 bg-white p-2.5 shadow-sm">
                            <label class="text-xs font-black text-atlantia-ink">Cantidad de etiquetas</label>
                            <div class="mt-1.5 flex overflow-hidden rounded-lg border border-atlantia-rose/25">
                                <button type="button" class="grid w-8 place-items-center bg-atlantia-cream text-base font-black text-atlantia-wine" data-barcode-qty-minus>-</button>
                                <input type="number" min="1" max="200" value="24" class="w-full border-0 px-2 py-1 text-center text-xs font-black outline-none focus:ring-0" data-barcode-quantity>
                                <button type="button" class="grid w-8 place-items-center bg-atlantia-cream text-base font-black text-atlantia-wine" data-barcode-qty-plus>+</button>
                            </div>
                            <p class="mt-1 text-[10px] text-atlantia-ink/50">Maximo recomendado: 100 etiquetas.</p>
                        </div>

                        <div class="rounded-xl border border-atlantia-rose/15 bg-white p-2.5 shadow-sm">
                            <label class="text-xs font-black text-atlantia-ink">Formato</label>
                            <div class="mt-1.5 grid grid-cols-2 gap-1.5">
                                <label class="flex cursor-pointer items-center gap-1.5 rounded-lg border border-atlantia-rose/25 px-2 py-1.5 text-[11px] font-bold text-atlantia-ink transition has-[:checked]:border-atlantia-wine has-[:checked]:bg-atlantia-blush">
                                    <input type="radio" name="barcode_format" value="thermal" class="text-atlantia-wine" data-barcode-format checked>
                                    Etiqueta termica
                                </label>
                                <label class="flex cursor-pointer items-center gap-1.5 rounded-lg border border-atlantia-rose/25 px-2 py-1.5 text-[11px] font-bold text-atlantia-ink transition has-[:checked]:border-atlantia-wine has-[:checked]:bg-atlantia-blush">
                                    <input type="radio" name="barcode_format" value="a4" class="text-atlantia-wine" data-barcode-format>
                                    Hoja A4
                                </label>
                            </div>
                        </div>

                        <div class="rounded-xl border border-atlantia-rose/15 bg-white p-2.5 shadow-sm">
                            <label class="text-xs font-black text-atlantia-ink">Tamano</label>
                            <select class="mt-1.5 w-full rounded-lg border border-atlantia-rose/25 bg-white px-2 py-1 text-[11px] outline-none focus:border-atlantia-wine focus:ring-2 focus:ring-atlantia-rose/20" data-barcode-size>
                                <option value="50x30">50 x 30 mm (recomendado)</option>
                                <option value="60x40">60 x 40 mm</option>
                                <option value="40x25">40 x 25 mm</option>
                            </select>

                            <div class="mt-1.5 grid grid-cols-2 gap-1 text-[10px] font-bold text-atlantia-ink/70">
                                <label class="flex items-center gap-2">
                                    <input type="checkbox" class="rounded border-atlantia-rose text-atlantia-wine" data-barcode-show-name checked>
                                    Mostrar nombre
                                </label>
                                <label class="flex items-center gap-2">
                                    <input type="checkbox" class="rounded border-atlantia-rose text-atlantia-wine" data-barcode-show-price checked>
                                    Mostrar precio
                                </label>
                                <label class="flex items-center gap-2">
                                    <input type="checkbox" class="rounded border-atlantia-rose text-atlantia-wine" data-barcode-show-sku>
                                    Mostrar SKU
                                </label>
                            </div>
                        </div>
                    </div>

                    <div class="rounded-xl border border-atlantia-rose/15 bg-white p-2.5 shadow-sm">
                        <label class="text-xs font-black text-atlantia-ink">Impresora</label>
                        <select class="mt-1.5 w-full rounded-lg border border-atlantia-rose/25 bg-white px-2 py-1 text-[11px] outline-none focus:border-atlantia-wine focus:ring-2 focus:ring-atlantia-rose/20" data-barcode-printer>
                            <option>Zebra ZD420 (USB)</option>
                            <option>Impresora predeterminada del sistema</option>
                            <option>Guardar como PDF</option>
                        </select>
                        <p class="mt-1.5 rounded-lg border border-atlantia-rose/15 bg-atlantia-blush/45 px-2 py-1 text-[10px] font-semibold text-atlantia-wine">
                            El codigo se genera automaticamente a partir del producto seleccionado.
                        </p>
                    </div>
                </div>

                <aside class="space-y-2">
                    <div class="rounded-xl border border-atlantia-rose/15 bg-white p-2.5 shadow-sm">
                        <p class="text-xs font-black text-atlantia-ink">Vista previa</p>
                        <div class="mt-1.5 grid grid-cols-2 gap-1.5" data-barcode-sheet-preview></div>
                        <p class="mt-1.5 text-center text-[10px] font-semibold text-atlantia-ink/45">Pagina 1 de 1</p>
                    </div>
                </aside>
            </div>

            <div class="flex flex-col-reverse gap-2 border-t border-atlantia-rose/15 bg-white px-4 py-2.5 sm:flex-row sm:justify-end">
                <button type="button" class="rounded-lg border border-atlantia-rose/25 bg-white px-4 py-1.5 text-xs font-black text-atlantia-wine transition hover:bg-atlantia-blush" data-close-barcode-modal>
                    Cancelar
                </button>
                <button type="button" class="rounded-lg border border-atlantia-rose/25 bg-white px-4 py-1.5 text-xs font-black text-atlantia-wine transition hover:bg-atlantia-blush" data-download-barcode-pdf>
                    Descargar PDF
                </button>
                <button type="button" class="rounded-lg bg-atlantia-wine px-5 py-1.5 text-xs font-black text-white shadow-sm transition hover:bg-atlantia-wine-700" data-print-barcode-labels>
                    Imprimir
                </button>
            </div>
        </div>
    </div>

    <div
        class="{{ $showCreateModal ? 'flex' : 'hidden' }} fixed inset-0 z-50 items-start justify-center overflow-y-auto bg-slate-950/60 px-2 py-2 backdrop-blur-md sm:px-4 sm:py-3"
        data-create-modal
        role="dialog"
        aria-modal="true"
        aria-labelledby="product-create-title"
    >
        <div class="flex max-h-[calc(100vh-1rem)] w-full max-w-[92rem] flex-col overflow-hidden rounded-[1.35rem] border border-atlantia-rose/20 bg-white shadow-[0_28px_90px_rgba(33,25,32,0.28)] sm:max-h-[calc(100vh-1.5rem)]">
            <div class="shrink-0 bg-gradient-to-r from-white via-white to-atlantia-blush/25 px-5 py-3 sm:px-7">
                <div class="flex items-start justify-between gap-4">
                    <div class="flex items-start gap-3">
                        <div class="mt-1 flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-atlantia-wine text-white shadow-lg shadow-atlantia-wine/25">
                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                <path d="M6 9h12l-1 11H7L6 9Z" />
                                <path d="M9 9V7a3 3 0 0 1 6 0v2" />
                            </svg>
                        </div>
                        <div>
                            <p class="text-[11px] font-black uppercase tracking-[0.2em] text-atlantia-rose">Atlantia Supermarket</p>
                            <h2 id="product-create-title" class="text-2xl font-black leading-tight text-atlantia-ink">Crear nuevo producto</h2>
                            <p class="mt-0.5 text-xs text-atlantia-ink/60">Agrega un producto propio o de vendedor local al catalogo.</p>
                        </div>
                    </div>
                    <button type="button" class="inline-flex items-center gap-2 rounded-lg border border-atlantia-rose/30 bg-white px-4 py-2 text-sm font-black text-atlantia-wine shadow-sm transition hover:bg-atlantia-blush" data-close-create-modal>
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <path d="m6 6 12 12M18 6 6 18" />
                        </svg>
                        <span>Cerrar</span>
                    </button>
                </div>
            </div>

            <form
                method="POST"
                action="{{ route('admin.productos.store') }}"
                enctype="multipart/form-data"
                class="flex min-h-0 flex-1 flex-col border-t border-atlantia-rose/15"
                data-product-create-form
            >
                @csrf

                <div class="min-h-0 flex-1 overflow-y-auto bg-gradient-to-b from-white to-atlantia-cream/50 px-5 py-3 sm:px-7">
                    @if ($errors->any())
                        <div class="mb-5 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-semibold text-red-800">
                            Revisa los campos marcados antes de guardar el producto.
                        </div>
                    @endif

                    <div class="grid gap-4 xl:grid-cols-2">
                        <section class="rounded-2xl border border-atlantia-rose/20 bg-white/95 p-4 shadow-sm xl:p-4">
                            <div class="flex items-center gap-3">
                                <div class="flex h-10 w-10 items-center justify-center rounded-full bg-atlantia-blush text-atlantia-wine">
                                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                        <path d="M8 4h8l3 3v13H5V4h3Z" />
                                        <path d="M15 4v4h4" />
                                        <path d="M8 12h8M8 16h6" />
                                    </svg>
                                </div>
                                <div>
                                    <h3 class="text-base font-black text-atlantia-wine">Informacion general</h3>
                                    <p class="text-xs text-atlantia-ink/60">Completa los datos basicos del producto.</p>
                                </div>
                            </div>

                            <div class="mt-3 grid gap-3 md:grid-cols-2">
                                <div>
                                    <label class="text-xs font-bold text-atlantia-ink">Quien vende este producto</label>
                                    <select name="owner_type" class="mt-1 w-full rounded-md border border-atlantia-rose/30 bg-white px-3 py-2 text-sm outline-none transition focus:border-atlantia-wine focus:ring-2 focus:ring-atlantia-rose/20" required data-product-owner-type>
                                        <option value="atlantia" @selected($selectedOwnerType === 'atlantia')>Atlantia Supermarket - producto propio</option>
                                        <option value="vendor" @selected($selectedOwnerType === 'vendor')>Vendedor local externo</option>
                                    </select>
                                    @error('owner_type') <p class="mt-1 text-xs font-semibold text-red-600">{{ $message }}</p> @enderror
                                </div>

                                <div @class(['hidden' => $selectedOwnerType !== 'vendor']) data-product-vendor-field>
                                    <label class="text-xs font-bold text-atlantia-ink">Vendedor local</label>
                                    <select name="vendor_id" class="mt-1 w-full rounded-md border border-atlantia-rose/30 bg-white px-3 py-2 text-sm outline-none transition focus:border-atlantia-wine focus:ring-2 focus:ring-atlantia-rose/20 disabled:bg-atlantia-cream disabled:text-atlantia-ink/45" @disabled($selectedOwnerType !== 'vendor') data-product-vendor-select>
                                        @if ($vendors->isEmpty())
                                            <option value="">No hay vendedores locales aprobados</option>
                                        @else
                                            <option value="">Selecciona un vendedor local</option>
                                            @foreach ($vendors as $vendor)
                                                <option value="{{ $vendor->id }}" @selected((string) old('vendor_id') === (string) $vendor->id)>{{ $vendor->business_name }}</option>
                                            @endforeach
                                        @endif
                                    </select>
                                    @error('vendor_id') <p class="mt-1 text-xs font-semibold text-red-600">{{ $message }}</p> @enderror
                                </div>

                                <div>
                                    <label class="text-xs font-bold text-atlantia-ink">Categoria</label>
                                    <select name="categoria_id" class="mt-1 w-full rounded-md border border-atlantia-rose/30 bg-white px-3 py-2 text-sm outline-none transition focus:border-atlantia-wine focus:ring-2 focus:ring-atlantia-rose/20" required>
                                        @foreach ($categorias as $categoria)
                                            <option value="{{ $categoria->id }}" @selected((string) old('categoria_id') === (string) $categoria->id)>{{ $categoria->nombre }}</option>
                                        @endforeach
                                    </select>
                                    @error('categoria_id') <p class="mt-1 text-xs font-semibold text-red-600">{{ $message }}</p> @enderror
                                </div>

                                <div>
                                    <label class="text-xs font-bold text-atlantia-ink">Unidad</label>
                                    <select name="unidad_medida" class="mt-1 w-full rounded-md border border-atlantia-rose/30 bg-white px-3 py-2 text-sm outline-none transition focus:border-atlantia-wine focus:ring-2 focus:ring-atlantia-rose/20" required>
                                        @foreach ($unidades as $unidad)
                                            <option value="{{ $unidad }}" @selected(old('unidad_medida', 'unidad') === $unidad)>{{ ucfirst($unidad) }}</option>
                                        @endforeach
                                    </select>
                                    @error('unidad_medida') <p class="mt-1 text-xs font-semibold text-red-600">{{ $message }}</p> @enderror
                                </div>

                                <div class="md:col-span-2">
                                    <label class="text-xs font-bold text-atlantia-ink">Nombre del producto</label>
                                    <input name="nombre" type="text" value="{{ old('nombre') }}" placeholder="Ingresa el nombre del producto" class="mt-1 w-full rounded-md border border-atlantia-rose/30 bg-white px-3 py-2 text-sm outline-none transition focus:border-atlantia-wine focus:ring-2 focus:ring-atlantia-rose/20" data-product-name required>
                                    @error('nombre') <p class="mt-1 text-xs font-semibold text-red-600">{{ $message }}</p> @enderror
                                </div>

                                <div class="md:col-span-2">
                                    <label class="text-xs font-bold text-atlantia-ink">Descripcion</label>
                                    <textarea name="descripcion" rows="2" placeholder="Describe tu producto, caracteristicas, beneficios, usos, etc." class="mt-1 w-full rounded-md border border-atlantia-rose/30 bg-white px-3 py-2 text-sm outline-none transition focus:border-atlantia-wine focus:ring-2 focus:ring-atlantia-rose/20">{{ old('descripcion') }}</textarea>
                                    @error('descripcion') <p class="mt-1 text-xs font-semibold text-red-600">{{ $message }}</p> @enderror
                                </div>

                                <div class="md:col-span-2">
                                    <label class="text-xs font-bold text-atlantia-ink">Imagenes del producto</label>
                                    <label class="mt-1 flex cursor-pointer items-center gap-3 rounded-xl border border-dashed border-atlantia-rose/45 bg-atlantia-blush/30 px-4 py-3 transition hover:border-atlantia-wine hover:bg-atlantia-blush/60">
                                        <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-white text-atlantia-wine shadow-sm">
                                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                                <path d="M4 5h16v14H4z" />
                                                <path d="m4 16 5-5 4 4 2-2 5 5" />
                                                <path d="M15 8h4M17 6v4" />
                                            </svg>
                                        </span>
                                        <span class="min-w-0">
                                            <span class="block text-sm font-black text-atlantia-wine">Arrastra imagenes o selecciona archivos</span>
                                            <span class="mt-0.5 block text-xs text-atlantia-ink/55">Hasta 8 imagenes JPG, PNG o WEBP. Maximo 5 MB cada una.</span>
                                        </span>
                                        <input name="imagenes[]" type="file" accept="image/png,image/jpeg,image/webp" multiple class="sr-only">
                                    </label>
                                    @error('imagenes') <p class="mt-1 text-sm font-semibold text-rose-700">{{ $message }}</p> @enderror
                                    @error('imagenes.*') <p class="mt-1 text-sm font-semibold text-rose-700">{{ $message }}</p> @enderror
                                </div>
                            </div>
                        </section>

                        <section class="rounded-2xl border border-atlantia-rose/20 bg-white/95 p-4 shadow-sm xl:p-4">
                            <div class="flex items-center gap-3">
                                <div class="flex h-10 w-10 items-center justify-center rounded-full bg-atlantia-blush text-atlantia-wine">
                                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                        <path d="m20 12-8 8-8-8 8-8 8 8Z" />
                                        <path d="m12 8 4 4-4 4-4-4 4-4Z" />
                                    </svg>
                                </div>
                                <div>
                                    <h3 class="text-base font-black text-atlantia-wine">Comercial e inventario</h3>
                                    <p class="text-xs text-atlantia-ink/60">Precios, inventario y opciones para el catalogo.</p>
                                </div>
                            </div>

                            <div class="mt-3 rounded-xl border border-atlantia-rose/25 bg-gradient-to-br from-atlantia-blush/50 via-white to-white p-3">
                                <div class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
                                    <div>
                                        <p class="text-sm font-black text-atlantia-wine">Identificacion automatica</p>
                                        <p class="text-xs text-atlantia-ink/60">SKU desde el nombre y codigo de barras unico.</p>
                                    </div>
                                    <span class="w-fit rounded-full border border-emerald-200 bg-emerald-50 px-3 py-1 text-xs font-black text-emerald-700">Auto</span>
                                </div>

                                <div class="mt-3 grid gap-3 md:grid-cols-2">
                                    <div>
                                        <label class="text-xs font-bold text-atlantia-ink">SKU automatico</label>
                                        <input name="sku" type="text" value="{{ old('sku') }}" placeholder="Se genera al escribir el nombre" class="mt-1 w-full rounded-md border border-atlantia-rose/30 bg-white px-3 py-2 font-mono text-xs font-black uppercase tracking-wide text-atlantia-wine outline-none transition focus:border-atlantia-wine focus:ring-2 focus:ring-atlantia-rose/20" data-product-sku readonly>
                                        @error('sku') <p class="mt-1 text-xs font-semibold text-red-600">{{ $message }}</p> @enderror
                                    </div>
                                    <div>
                                        <label class="text-xs font-bold text-atlantia-ink">Codigo de barras</label>
                                        <input type="text" value="Se asigna al guardar" class="mt-1 w-full rounded-md border border-atlantia-rose/30 bg-white px-3 py-2 font-mono text-xs font-black tracking-[0.16em] text-atlantia-ink/70 outline-none" data-product-barcode-preview readonly>
                                    </div>
                                </div>
                            </div>

                            <div class="mt-3 grid gap-3 md:grid-cols-2">
                                <div>
                                    <label class="text-xs font-bold text-atlantia-ink">Precio base</label>
                                    <div class="mt-1 flex overflow-hidden rounded-md border border-atlantia-rose/30 bg-white transition focus-within:border-atlantia-wine focus-within:ring-2 focus-within:ring-atlantia-rose/20">
                                        <span class="flex items-center px-3 text-sm font-black text-atlantia-ink/70">Q</span>
                                        <input name="precio_base" type="number" step="0.01" min="0.01" value="{{ old('precio_base') }}" placeholder="0.00" class="min-w-0 flex-1 border-0 px-2 py-2 text-sm outline-none focus:ring-0" required>
                                    </div>
                                    @error('precio_base') <p class="mt-1 text-xs font-semibold text-red-600">{{ $message }}</p> @enderror
                                </div>

                                <div>
                                    <label class="text-xs font-bold text-atlantia-ink">Oferta</label>
                                    <div class="mt-1 flex overflow-hidden rounded-md border border-atlantia-rose/30 bg-white transition focus-within:border-atlantia-wine focus-within:ring-2 focus-within:ring-atlantia-rose/20">
                                        <span class="flex items-center px-3 text-sm font-black text-atlantia-ink/70">Q</span>
                                        <input name="precio_oferta" type="number" step="0.01" min="0" value="{{ old('precio_oferta') }}" placeholder="0.00" class="min-w-0 flex-1 border-0 px-2 py-2 text-sm outline-none focus:ring-0">
                                    </div>
                                    @error('precio_oferta') <p class="mt-1 text-xs font-semibold text-red-600">{{ $message }}</p> @enderror
                                </div>

                                <div>
                                    <label class="text-xs font-bold text-atlantia-ink">Stock actual</label>
                                    <input name="stock_actual" type="number" min="0" value="{{ old('stock_actual', 0) }}" class="mt-1 w-full rounded-md border border-atlantia-rose/30 bg-white px-3 py-2 text-sm outline-none transition focus:border-atlantia-wine focus:ring-2 focus:ring-atlantia-rose/20" required>
                                    @error('stock_actual') <p class="mt-1 text-xs font-semibold text-red-600">{{ $message }}</p> @enderror
                                </div>

                                <div>
                                    <label class="text-xs font-bold text-atlantia-ink">Peso gramos</label>
                                    <input name="peso_gramos" type="number" min="0" value="{{ old('peso_gramos') }}" placeholder="0" class="mt-1 w-full rounded-md border border-atlantia-rose/30 bg-white px-3 py-2 text-sm outline-none transition focus:border-atlantia-wine focus:ring-2 focus:ring-atlantia-rose/20">
                                    @error('peso_gramos') <p class="mt-1 text-xs font-semibold text-red-600">{{ $message }}</p> @enderror
                                </div>

                                <div>
                                    <label class="text-xs font-bold text-atlantia-ink">Stock minimo</label>
                                    <input name="stock_minimo" type="number" min="0" value="{{ old('stock_minimo', 0) }}" class="mt-1 w-full rounded-md border border-atlantia-rose/30 bg-white px-3 py-2 text-sm outline-none transition focus:border-atlantia-wine focus:ring-2 focus:ring-atlantia-rose/20">
                                    @error('stock_minimo') <p class="mt-1 text-xs font-semibold text-red-600">{{ $message }}</p> @enderror
                                </div>

                                <div>
                                    <label class="text-xs font-bold text-atlantia-ink">Stock maximo</label>
                                    <input name="stock_maximo" type="number" min="0" value="{{ old('stock_maximo', 0) }}" class="mt-1 w-full rounded-md border border-atlantia-rose/30 bg-white px-3 py-2 text-sm outline-none transition focus:border-atlantia-wine focus:ring-2 focus:ring-atlantia-rose/20">
                                    @error('stock_maximo') <p class="mt-1 text-xs font-semibold text-red-600">{{ $message }}</p> @enderror
                                </div>
                            </div>

                            <div class="mt-3 rounded-xl border border-atlantia-rose/25 bg-atlantia-blush/35 p-3">
                                <p class="text-sm font-black text-atlantia-wine">Opciones del catalogo</p>
                                <div class="mt-2 grid gap-2 sm:grid-cols-3">
                                    <label class="flex items-center gap-2 rounded-lg border border-atlantia-rose/20 bg-white px-3 py-2 text-sm">
                                        <input type="checkbox" name="is_active" value="1" @checked(old('is_active', '1')) class="rounded border-atlantia-rose text-atlantia-wine">
                                        <span class="font-semibold text-atlantia-ink">Activo</span>
                                    </label>
                                    <label class="flex items-center gap-2 rounded-lg border border-atlantia-rose/20 bg-white px-3 py-2 text-sm">
                                        <input type="checkbox" name="visible_catalogo" value="1" @checked(old('visible_catalogo', '1')) class="rounded border-atlantia-rose text-atlantia-wine">
                                        <span class="font-semibold text-atlantia-ink">Visible en catalogo</span>
                                    </label>
                                    <label class="flex items-center gap-2 rounded-lg border border-atlantia-rose/20 bg-white px-3 py-2 text-sm">
                                        <input type="checkbox" name="requiere_refrigeracion" value="1" @checked(old('requiere_refrigeracion')) class="rounded border-atlantia-rose text-atlantia-wine">
                                        <span class="font-semibold text-atlantia-ink">Requiere refrigeracion</span>
                                    </label>
                                </div>
                            </div>
                        </section>
                    </div>
                </div>

                <div class="shrink-0 border-t border-atlantia-rose/15 bg-white/95 px-5 py-3 shadow-[0_-18px_30px_rgba(33,25,32,0.08)] sm:px-7">
                    <div class="flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
                        <button type="button" class="rounded-lg border border-atlantia-rose/30 bg-white px-7 py-2 text-sm font-black text-atlantia-wine transition hover:bg-atlantia-blush" data-close-create-modal>
                            Cancelar
                        </button>
                        <x-ui.button type="submit">Guardar producto</x-ui.button>
                    </div>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
    <script nonce="{{ request()->attributes->get('csp_nonce') }}">
        (() => {
            const syncAdminProductControls = () => {
                document.querySelectorAll('[data-admin-products-server-value]').forEach((control) => {
                    control.value = control.dataset.adminProductsServerValue ?? '';
                });
            };

            const submitFreshFirstPage = (form) => {
                form?.querySelector('input[name="page"]')?.remove();

                if (form?.requestSubmit) {
                    form.requestSubmit();
                    return;
                }

                form?.submit();
            };

            const initializeAdminProductFilters = () => {
                syncAdminProductControls();

                const filterForm = document.querySelector('[data-admin-product-filters]');

                if (filterForm && filterForm.dataset.adminProductFiltersReady !== 'true') {
                    filterForm.dataset.adminProductFiltersReady = 'true';
                    filterForm.querySelectorAll('[data-admin-product-filter]').forEach((control) => {
                        control.addEventListener('change', () => submitFreshFirstPage(filterForm));
                    });
                }

                const perPageForm = document.querySelector('[data-admin-product-per-page-form]');
                const perPageControl = document.querySelector('[data-admin-product-per-page]');

                if (perPageForm && perPageControl && perPageControl.dataset.adminProductPerPageReady !== 'true') {
                    perPageControl.dataset.adminProductPerPageReady = 'true';
                    perPageControl.addEventListener('change', () => submitFreshFirstPage(perPageForm));
                }
            };

            const initializeProductOwnerFields = () => {
                document.querySelectorAll('[data-product-create-form]').forEach((form) => {
                    if (form.dataset.productOwnerFieldsReady === 'true') {
                        return;
                    }

                    form.dataset.productOwnerFieldsReady = 'true';

                    const ownerType = form.querySelector('[data-product-owner-type]');
                    const vendorField = form.querySelector('[data-product-vendor-field]');
                    const vendorSelect = form.querySelector('[data-product-vendor-select]');

                    if (!ownerType || !vendorField || !vendorSelect) {
                        return;
                    }

                    const syncVendorField = () => {
                        const usesExternalVendor = ownerType.value === 'vendor';

                        vendorField.classList.toggle('hidden', !usesExternalVendor);
                        vendorSelect.disabled = !usesExternalVendor;

                        if (!usesExternalVendor) {
                            vendorSelect.value = '';
                        }
                    };

                    ownerType.addEventListener('change', syncVendorField);
                    syncVendorField();
                });
            };

            document.addEventListener('DOMContentLoaded', initializeAdminProductFilters);
            document.addEventListener('DOMContentLoaded', initializeProductOwnerFields);
            document.addEventListener('livewire:navigated', initializeAdminProductFilters);
            document.addEventListener('livewire:navigated', initializeProductOwnerFields);
            window.addEventListener('pageshow', initializeAdminProductFilters);
            window.addEventListener('pageshow', initializeProductOwnerFields);
        })();
    </script>
@endpush
