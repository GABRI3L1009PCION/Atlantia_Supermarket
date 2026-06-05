@extends('layouts.app')

@section('content')
    @php
        $user = auth()->user();
        $puedeCrear = $user?->can('create', App\Models\Producto::class);
        $modeloCobro = match (true) {
            $vendor?->monthly_rent > 0 && $vendor?->commission_percentage > 0 => 'Renta mensual + comision',
            $vendor?->monthly_rent > 0 => 'Renta mensual',
            $vendor?->commission_percentage > 0 => 'Comision sobre ventas',
            default => 'Pendiente de configurar',
        };
        $initials = collect(preg_split('/\s+/', trim((string) ($user?->name ?? 'V'))))
            ->filter()
            ->take(2)
            ->map(fn ($part) => \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($part, 0, 1)))
            ->join('') ?: 'V';
        $inputBase = 'mt-1 w-full rounded-md border bg-white px-3 py-2 text-sm text-atlantia-ink outline-none transition focus:border-atlantia-wine focus:ring-2 focus:ring-atlantia-rose/20 disabled:cursor-not-allowed disabled:bg-atlantia-cream disabled:text-atlantia-ink/45';
        $inputNormal = 'border-atlantia-rose/30';
        $inputError = 'border-rose-500 bg-rose-50';
        $errorText = 'mt-1 text-xs font-semibold text-rose-700';
        $showCreateModal = $errors->any();
        $filterValues = [
            'q' => (string) request('q', ''),
            'categoria_id' => (string) request('categoria_id', ''),
            'estado' => (string) request('estado', ''),
            'stock' => (string) request('stock', ''),
            'orden' => (string) request('orden', 'recientes'),
            'per_page' => (int) request('per_page', $productos->perPage()),
        ];
        $activeFilters = collect([
            'Busqueda: ' . $filterValues['q'] => $filterValues['q'],
            'Categoria activa' => $filterValues['categoria_id'],
            'Estado: ' . $filterValues['estado'] => $filterValues['estado'],
            'Stock: ' . $filterValues['stock'] => $filterValues['stock'],
        ])->filter();
    @endphp

    <section class="mx-auto max-w-[1280px] space-y-5 pb-10">
        <div class="grid gap-4 lg:grid-cols-[1fr_330px] lg:items-end">
            <div>
                <p class="text-xs font-black uppercase tracking-[0.22em] text-atlantia-rose">Atlantia Supermarket</p>
                <h1 class="mt-2 text-3xl font-black leading-tight text-atlantia-ink sm:text-4xl">Mis productos</h1>
                <p class="mt-2 max-w-2xl text-sm leading-6 text-atlantia-ink/62">
                    Administra tu catalogo como vendedor local independiente dentro de Atlantia.
                </p>
            </div>

            <a href="{{ route('vendedor.perfil-fiscal.edit') }}" class="group flex min-h-[5.5rem] items-center gap-4 rounded-lg border border-atlantia-rose/15 bg-white p-4 shadow-[0_14px_34px_rgba(42,16,24,0.07)] transition hover:border-atlantia-wine/30">
                <span class="grid h-14 w-14 shrink-0 place-items-center rounded-full bg-atlantia-blush/70 text-atlantia-wine">
                    <span class="grid h-10 w-10 place-items-center rounded-full bg-white text-sm font-black shadow-sm">{{ $initials }}</span>
                </span>
                <span class="min-w-0 flex-1">
                    <span class="block truncate text-sm font-black text-atlantia-ink">{{ $vendor?->business_name ?? $user?->name }}</span>
                    <span class="mt-1 block text-xs font-bold text-orange-600">{{ $modeloCobro }}</span>
                </span>
                <span class="text-atlantia-ink/45 transition group-hover:translate-x-0.5 group-hover:text-atlantia-wine" aria-hidden="true">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round">
                        <path d="m9 18 6-6-6-6"/>
                    </svg>
                </span>
            </a>
        </div>

        @if ($vendor === null)
            <article class="rounded-lg border border-amber-200 bg-amber-50 p-5 text-amber-900">
                <h2 class="text-lg font-black">Tu cuenta aun no tiene perfil de vendedor</h2>
                <p class="mt-2 text-sm">Para crear productos se necesita un perfil comercial con datos de tienda, modelo de cobro y configuracion fiscal.</p>
            </article>
        @elseif (! $puedeCrear)
            <article class="rounded-lg border border-amber-200 bg-amber-50 p-5 text-amber-900">
                <h2 class="text-lg font-black">Tu puesto de venta esta pendiente de aprobacion</h2>
                <p class="mt-2 text-sm">Puedes revisar el panel, pero la publicacion de productos se habilita cuando Atlantia aprueba tu perfil comercial.</p>
            </article>
        @endif

        <article class="rounded-lg border border-atlantia-rose/15 bg-white shadow-[0_12px_32px_rgba(42,16,24,0.05)]">
            <div class="border-b border-atlantia-rose/15 p-5 sm:p-6">
                <div class="flex flex-col gap-4 xl:flex-row xl:items-start xl:justify-between">
                    <div class="flex items-start gap-3">
                        <span class="grid h-10 w-10 shrink-0 place-items-center rounded-lg bg-atlantia-blush text-atlantia-wine">
                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="M7 8V6a5 5 0 0 1 10 0v2"/><path d="M5 8h14l-1 11H6L5 8Z"/>
                            </svg>
                        </span>
                        <div>
                            <h2 class="text-xl font-black text-atlantia-ink">Catalogo propio</h2>
                            <p class="mt-1 text-sm text-atlantia-ink/58">Busca, filtra y administra los productos de tu tienda.</p>
                        </div>
                    </div>

                    <div class="flex flex-col gap-2 sm:flex-row sm:items-center">
                        <span class="w-fit rounded-md bg-atlantia-blush px-3 py-1.5 text-xs font-black text-atlantia-wine">
                            {{ $productos->total() }} {{ \Illuminate\Support\Str::plural('producto', $productos->total()) }}
                        </span>
                        <button
                            type="button"
                            class="inline-flex items-center justify-center gap-2 rounded-lg bg-atlantia-wine px-5 py-3 text-sm font-black text-white shadow-sm transition hover:bg-atlantia-wine-700 disabled:cursor-not-allowed disabled:opacity-55"
                            data-open-create-product-modal
                            @disabled(! $puedeCrear)
                        >
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="M12 5v14"/><path d="M5 12h14"/>
                            </svg>
                            Crear producto
                        </button>
                    </div>
                </div>

                <form method="GET" action="{{ route('vendedor.productos.index') }}" class="mt-5 space-y-3" autocomplete="off" data-vendor-product-filters>
                    <div class="flex flex-col gap-3 lg:flex-row">
                        <div class="relative flex-1">
                            <input
                                type="search"
                                name="q"
                                value="{{ $filterValues['q'] }}"
                                placeholder="Buscar producto, SKU o codigo de barras"
                                class="w-full rounded-lg border border-atlantia-rose/25 bg-white px-4 py-3 pr-12 text-sm outline-none transition focus:border-atlantia-wine focus:ring-2 focus:ring-atlantia-rose/20"
                            >
                            <button type="submit" class="absolute right-2 top-1/2 grid h-8 w-8 -translate-y-1/2 place-items-center rounded-md text-atlantia-wine transition hover:bg-atlantia-blush" aria-label="Buscar">
                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                    <circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/>
                                </svg>
                            </button>
                        </div>
                        <button type="submit" class="rounded-lg border border-atlantia-rose/25 bg-white px-5 py-3 text-sm font-black text-atlantia-wine transition hover:bg-atlantia-blush">
                            Aplicar filtros
                        </button>
                    </div>

                    <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-5">
                        <label class="block">
                            <span class="text-xs font-black uppercase tracking-wide text-atlantia-ink/55">Categoria</span>
                            <select name="categoria_id" class="mt-1 w-full rounded-lg border border-atlantia-rose/25 bg-white px-3 py-2.5 text-sm outline-none transition focus:border-atlantia-wine focus:ring-2 focus:ring-atlantia-rose/20" data-vendor-product-filter>
                                <option value="">Todas</option>
                                @foreach ($categorias as $categoria)
                                    <option value="{{ $categoria->id }}" @selected($filterValues['categoria_id'] === (string) $categoria->id)>{{ $categoria->nombre }}</option>
                                @endforeach
                            </select>
                        </label>

                        <label class="block">
                            <span class="text-xs font-black uppercase tracking-wide text-atlantia-ink/55">Estado</span>
                            <select name="estado" class="mt-1 w-full rounded-lg border border-atlantia-rose/25 bg-white px-3 py-2.5 text-sm outline-none transition focus:border-atlantia-wine focus:ring-2 focus:ring-atlantia-rose/20" data-vendor-product-filter>
                                <option value="">Todos</option>
                                <option value="activo" @selected($filterValues['estado'] === 'activo')>Activos</option>
                                <option value="inactivo" @selected($filterValues['estado'] === 'inactivo')>Inactivos</option>
                                <option value="visible" @selected($filterValues['estado'] === 'visible')>Visibles</option>
                                <option value="oculto" @selected($filterValues['estado'] === 'oculto')>Ocultos</option>
                            </select>
                        </label>

                        <label class="block">
                            <span class="text-xs font-black uppercase tracking-wide text-atlantia-ink/55">Stock</span>
                            <select name="stock" class="mt-1 w-full rounded-lg border border-atlantia-rose/25 bg-white px-3 py-2.5 text-sm outline-none transition focus:border-atlantia-wine focus:ring-2 focus:ring-atlantia-rose/20" data-vendor-product-filter>
                                <option value="">Todos</option>
                                <option value="disponible" @selected($filterValues['stock'] === 'disponible')>Disponible</option>
                                <option value="bajo" @selected($filterValues['stock'] === 'bajo')>Stock bajo</option>
                                <option value="agotado" @selected($filterValues['stock'] === 'agotado')>Agotado</option>
                            </select>
                        </label>

                        <label class="block">
                            <span class="text-xs font-black uppercase tracking-wide text-atlantia-ink/55">Ordenar por</span>
                            <select name="orden" class="mt-1 w-full rounded-lg border border-atlantia-rose/25 bg-white px-3 py-2.5 text-sm outline-none transition focus:border-atlantia-wine focus:ring-2 focus:ring-atlantia-rose/20" data-vendor-product-filter>
                                <option value="recientes" @selected($filterValues['orden'] === 'recientes')>Mas recientes</option>
                                <option value="nombre" @selected($filterValues['orden'] === 'nombre')>Nombre</option>
                                <option value="precio_asc" @selected($filterValues['orden'] === 'precio_asc')>Precio menor</option>
                                <option value="precio_desc" @selected($filterValues['orden'] === 'precio_desc')>Precio mayor</option>
                                <option value="stock_asc" @selected($filterValues['orden'] === 'stock_asc')>Menor stock</option>
                            </select>
                        </label>

                        <label class="block">
                            <span class="text-xs font-black uppercase tracking-wide text-atlantia-ink/55">Mostrar</span>
                            <select name="per_page" class="mt-1 w-full rounded-lg border border-atlantia-rose/25 bg-white px-3 py-2.5 text-sm outline-none transition focus:border-atlantia-wine focus:ring-2 focus:ring-atlantia-rose/20" data-vendor-product-filter>
                                @foreach ([8, 12, 24, 48] as $size)
                                    <option value="{{ $size }}" @selected((int) $filterValues['per_page'] === $size)>{{ $size }} por pagina</option>
                                @endforeach
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
                                <a href="{{ route('vendedor.productos.index') }}" class="rounded-full px-3 py-1.5 text-atlantia-wine underline-offset-4 hover:underline">Limpiar filtros</a>
                            @endif
                        </div>
                    </div>
                </form>
            </div>

            <div class="p-5 sm:p-6">
                <div class="grid grid-cols-[repeat(auto-fill,minmax(165px,1fr))] gap-3">
                    @forelse ($productos as $producto)
                        @php
                            $imageUrl = $producto->getFirstMediaUrl('productos', 'thumbnail')
                                ?: $producto->getFirstMediaUrl('productos')
                                ?: ($producto->imagenPrincipal?->path ? \Illuminate\Support\Facades\Storage::url($producto->imagenPrincipal->path) : null);
                            $stockActual = (int) ($producto->inventario?->stock_actual ?? 0);
                            $hasOffer = $producto->precio_oferta !== null && (float) $producto->precio_oferta > 0;
                            $displayPrice = $hasOffer ? $producto->precio_oferta : $producto->precio_base;
                        @endphp

                        <article class="group overflow-hidden rounded-2xl border border-atlantia-rose/20 bg-white shadow-[0_10px_24px_rgba(68,32,50,0.08)] transition hover:-translate-y-0.5 hover:border-atlantia-rose/40 hover:shadow-[0_18px_34px_rgba(68,32,50,0.14)]">
                            <div class="relative h-28 bg-gradient-to-br from-atlantia-cream via-white to-atlantia-blush/30 p-3">
                                @if ($producto->requiere_refrigeracion)
                                    <span class="absolute left-2 top-2 grid h-8 w-8 place-items-center rounded-full bg-sky-50/90 text-sky-500 shadow-sm ring-1 ring-sky-200 backdrop-blur" title="Requiere refrigeracion" aria-label="Requiere refrigeracion">
                                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                            <path d="M12 2v20"/><path d="M2 12h20"/><path d="m5 5 14 14"/><path d="m19 5-14 14"/>
                                        </svg>
                                    </span>
                                @endif

                                @if ($imageUrl)
                                    <img src="{{ $imageUrl }}" alt="{{ $producto->nombre }}" class="h-full w-full object-contain drop-shadow-md transition duration-300 group-hover:scale-105">
                                @else
                                    <div class="grid h-full place-items-center">
                                        <div class="grid h-12 w-12 place-items-center rounded-xl bg-atlantia-blush text-lg font-black text-atlantia-wine">
                                            {{ \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($producto->nombre ?? 'P', 0, 1)) }}
                                        </div>
                                    </div>
                                @endif

                                <div class="absolute right-2 top-2 flex flex-col items-end gap-1">
                                    <span @class([
                                        'rounded-full px-2 py-0.5 text-[9px] font-black shadow-sm ring-1 backdrop-blur',
                                        'bg-emerald-50 text-emerald-700 ring-emerald-200' => $producto->is_active,
                                        'bg-slate-100 text-slate-600 ring-slate-200' => ! $producto->is_active,
                                    ])>{{ $producto->is_active ? 'Activo' : 'Inactivo' }}</span>
                                    <span @class([
                                        'rounded-full px-2 py-0.5 text-[9px] font-black shadow-sm ring-1 backdrop-blur',
                                        'bg-emerald-50 text-emerald-700 ring-emerald-200' => $producto->visible_catalogo,
                                        'bg-orange-50 text-orange-700 ring-orange-200' => ! $producto->visible_catalogo,
                                    ])>{{ $producto->visible_catalogo ? 'Visible' : 'Oculto' }}</span>
                                </div>
                            </div>

                            <div class="space-y-2.5 p-3">
                                <div>
                                    <h3 class="line-clamp-1 text-[13px] font-black leading-tight text-atlantia-ink">{{ $producto->nombre }}</h3>
                                    <p class="mt-1 truncate font-mono text-[9px] font-semibold text-atlantia-ink/45">{{ $producto->codigo_barras ?: $producto->sku }}</p>
                                    <p class="mt-0.5 truncate text-[10px] text-atlantia-ink/45">{{ $producto->categoria?->nombre ?? 'Sin categoria' }}</p>
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
                                        ])>{{ $stockActual }}</p>
                                    </div>
                                </div>

                                <div class="grid grid-cols-2 gap-1.5 border-t border-atlantia-rose/15 pt-2 text-[10px] font-black">
                                    <button
                                        type="button"
                                        class="inline-flex items-center justify-center rounded-lg border border-atlantia-rose/20 bg-white px-1.5 py-1.5 text-atlantia-ink/70 transition hover:bg-atlantia-blush hover:text-atlantia-wine"
                                        data-open-edit-product-modal="{{ $producto->uuid }}"
                                    >
                                        Editar
                                    </button>
                                    <form method="POST" action="{{ route('vendedor.productos.destroy', $producto->uuid) }}" onsubmit="return confirm('Deseas retirar este producto del catalogo?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="w-full rounded-lg border border-rose-200 bg-white px-1.5 py-1.5 text-rose-700 transition hover:bg-rose-50">
                                            Retirar
                                        </button>
                                    </form>
                                </div>

                                <div class="grid grid-cols-2 gap-1.5">
                                    <button
                                        type="button"
                                        class="inline-flex items-center justify-center gap-1 rounded-lg border border-atlantia-rose/20 bg-gradient-to-r from-white to-atlantia-cream/80 px-2 py-1.5 text-[9px] font-black leading-tight text-atlantia-wine transition hover:bg-atlantia-blush"
                                        data-print-product-label
                                        data-label-type="barcode"
                                        data-product-name="{{ $producto->nombre }}"
                                        data-product-sku="{{ $producto->sku }}"
                                        data-product-barcode="{{ $producto->codigo_barras ?: $producto->sku }}"
                                        data-product-price="Q{{ number_format((float) $displayPrice, 2) }}"
                                        data-product-vendor="{{ $vendor?->business_name ?? $user?->name }}"
                                        data-product-image="{{ $imageUrl }}"
                                    >
                                        <span class="text-sm leading-none">|||</span>
                                        <span>Imprimir codigo</span>
                                    </button>
                                    <button
                                        type="button"
                                        class="inline-flex items-center justify-center gap-1 rounded-lg border border-atlantia-rose/20 bg-gradient-to-r from-white to-atlantia-cream/80 px-2 py-1.5 text-[9px] font-black leading-tight text-atlantia-wine transition hover:bg-atlantia-blush"
                                        data-print-product-label
                                        data-label-type="price"
                                        data-product-name="{{ $producto->nombre }}"
                                        data-product-sku="{{ $producto->sku }}"
                                        data-product-barcode="{{ $producto->codigo_barras ?: $producto->sku }}"
                                        data-product-price="Q{{ number_format((float) $displayPrice, 2) }}"
                                        data-product-vendor="{{ $vendor?->business_name ?? $user?->name }}"
                                        data-product-image="{{ $imageUrl }}"
                                    >
                                        <span class="text-sm leading-none">◆</span>
                                        <span>Imprimir precio</span>
                                    </button>
                                </div>
                            </div>
                        </article>

                        <div
                            class="fixed inset-0 z-[72] hidden items-start justify-center overflow-y-auto bg-slate-950/55 px-3 py-6 backdrop-blur-sm sm:py-10"
                            data-edit-product-modal="{{ $producto->uuid }}"
                            role="dialog"
                            aria-modal="true"
                            aria-labelledby="edit-product-title-{{ $producto->uuid }}"
                        >
                            <div class="w-full max-w-[92rem] overflow-hidden rounded-2xl border border-atlantia-rose/20 bg-white shadow-[0_28px_90px_rgba(33,25,32,0.28)]">
                                <div class="flex items-start justify-between gap-3 px-6 pt-5">
                                    <div class="flex items-start gap-6">
                                        <span class="grid h-16 w-16 shrink-0 place-items-center rounded-full bg-atlantia-wine text-white shadow-lg shadow-atlantia-wine/20">
                                            <svg class="h-8 w-8" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                                <path d="M20 13 11 4 4 11l9 9 7-7Z"/><path d="M7.5 7.5h.01"/>
                                            </svg>
                                        </span>
                                        <div>
                                            <h2 id="edit-product-title-{{ $producto->uuid }}" class="text-3xl font-black leading-tight text-atlantia-ink">Editar producto</h2>
                                            <p class="mt-1 text-base text-atlantia-ink/58">{{ $producto->nombre }}</p>
                                        </div>
                                    </div>
                                    <button type="button" class="rounded-lg p-2 text-atlantia-ink/50 transition hover:bg-atlantia-blush hover:text-atlantia-wine" data-close-edit-product-modal aria-label="Cerrar">
                                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                            <path d="m6 6 12 12M18 6 6 18"/>
                                        </svg>
                                    </button>
                                </div>

                                <form method="POST" action="{{ route('vendedor.productos.update', $producto->uuid) }}" enctype="multipart/form-data">
                                    @csrf
                                    @method('PUT')
                                    <div class="max-h-[calc(100vh-12rem)] overflow-y-auto px-6 pb-4 pt-5">
                                        <div class="mb-4 grid grid-cols-5 gap-5 border-b border-atlantia-rose/15 text-sm">
                                            @foreach (['General', 'Precios', 'Inventario', 'Imagenes', 'Publicacion'] as $tab)
                                                <span class="{{ $loop->first ? 'border-atlantia-wine text-atlantia-wine' : 'border-transparent text-atlantia-ink/58' }} flex items-center gap-2 border-b-2 px-2 pb-3 font-black">
                                                    {{ $tab }}
                                                </span>
                                            @endforeach
                                        </div>

                                        <div class="grid gap-3 xl:grid-cols-[1.1fr_0.75fr]">
                                            <div class="space-y-3">
                                                <section class="rounded-xl border border-atlantia-rose/15 bg-white p-3 shadow-sm">
                                                    <h3 class="text-sm font-black text-atlantia-wine">Informacion basica</h3>
                                                    <div class="mt-3 grid gap-3 md:grid-cols-2">
                                                        <label class="block">
                                                            <span class="text-xs font-bold text-atlantia-ink">Categoria <span class="text-atlantia-wine">*</span></span>
                                                            <select name="categoria_id" class="{{ $inputBase }} {{ $inputNormal }}" required>
                                                                @foreach ($categorias as $categoria)
                                                                    <option value="{{ $categoria->id }}" @selected((int) $producto->categoria_id === (int) $categoria->id)>{{ $categoria->nombre }}</option>
                                                                @endforeach
                                                            </select>
                                                        </label>
                                                        <label class="block">
                                                            <span class="text-xs font-bold text-atlantia-ink">Nombre del producto <span class="text-atlantia-wine">*</span></span>
                                                            <input name="nombre" value="{{ $producto->nombre }}" class="{{ $inputBase }} {{ $inputNormal }}" required>
                                                        </label>
                                                        <label class="block">
                                                            <span class="text-xs font-bold text-atlantia-ink">Subcategoria</span>
                                                            <select class="{{ $inputBase }} {{ $inputNormal }}">
                                                                <option>Alimentos secos</option>
                                                                <option>Producto refrigerado</option>
                                                                <option>Producto general</option>
                                                            </select>
                                                        </label>
                                                        <label class="block">
                                                            <span class="text-xs font-bold text-atlantia-ink">Marca</span>
                                                            <input placeholder="Marca o fabricante" value="{{ $producto->nombre }}" class="{{ $inputBase }} {{ $inputNormal }}">
                                                            <span class="mt-1 block text-[11px] text-atlantia-ink/45">Marca o fabricante del producto.</span>
                                                        </label>
                                                        <label class="block">
                                                            <span class="text-xs font-bold text-atlantia-ink">SKU <span class="text-atlantia-wine">*</span></span>
                                                            <input name="sku" value="{{ $producto->sku }}" class="{{ $inputBase }} {{ $inputNormal }} font-mono text-xs font-black uppercase tracking-wide text-atlantia-wine" required>
                                                            <span class="mt-1 block text-[11px] text-atlantia-ink/45">Codigo unico interno para este producto.</span>
                                                        </label>
                                                        <label class="block">
                                                            <span class="text-xs font-bold text-atlantia-ink">Unidad de medida <span class="text-atlantia-wine">*</span></span>
                                                            <select name="unidad_medida" class="{{ $inputBase }} {{ $inputNormal }}" required>
                                                                @foreach (['unidad', 'libra', 'kilogramo', 'gramo', 'litro', 'mililitro', 'paquete'] as $unidad)
                                                                    <option value="{{ $unidad }}" @selected($producto->unidad_medida === $unidad)>{{ ucfirst($unidad) }}</option>
                                                                @endforeach
                                                            </select>
                                                        </label>
                                                        <label class="block">
                                                            <span class="text-xs font-bold text-atlantia-ink">Codigo de barras <span class="text-atlantia-wine">*</span></span>
                                                            <input name="codigo_barras" value="{{ $producto->codigo_barras }}" class="{{ $inputBase }} {{ $inputNormal }} font-mono text-xs font-black tracking-[0.16em] text-atlantia-ink/70" required>
                                                            <span class="mt-1 block text-[11px] text-atlantia-ink/45">EAN/UPC del producto.</span>
                                                        </label>
                                                        <label class="block">
                                                            <span class="text-xs font-bold text-atlantia-ink">Presentacion</span>
                                                            <input value="1 {{ $producto->unidad_medida }}" class="{{ $inputBase }} {{ $inputNormal }}">
                                                            <span class="mt-1 block text-[11px] text-atlantia-ink/45">Referencia visual; no se guarda como campo separado.</span>
                                                        </label>
                                                    </div>
                                                </section>

                                                <section class="rounded-xl border border-atlantia-rose/15 bg-white p-3 shadow-sm">
                                                    <h3 class="text-sm font-black text-atlantia-wine">Descripcion</h3>
                                                    <div class="mt-3 grid gap-3 md:grid-cols-2">
                                                        <label class="block">
                                                            <span class="text-xs font-bold text-atlantia-ink">Descripcion corta</span>
                                                            <textarea name="descripcion" rows="3" maxlength="5000" class="{{ $inputBase }} {{ $inputNormal }}">{{ $producto->descripcion }}</textarea>
                                                            <span class="mt-1 block text-[11px] text-atlantia-ink/45">Max. 160 caracteres sugeridos.</span>
                                                        </label>
                                                        <label class="block">
                                                            <span class="text-xs font-bold text-atlantia-ink">Descripcion larga</span>
                                                            <textarea rows="3" class="{{ $inputBase }} {{ $inputNormal }}" placeholder="Cuentales a tus clientes caracteristicas, beneficios y uso.">{{ $producto->descripcion }}</textarea>
                                                            <span class="mt-1 block text-[11px] text-atlantia-ink/45">Referencia visual; usa la descripcion corta para guardar.</span>
                                                        </label>
                                                    </div>
                                                </section>

                                                <section class="rounded-xl border border-atlantia-rose/15 bg-white p-3 shadow-sm">
                                                    <h3 class="text-sm font-black text-atlantia-wine">Imagenes</h3>
                                                    <div class="mt-3 grid gap-3 md:grid-cols-[220px_1fr]">
                                                        <label class="flex min-h-32 cursor-pointer flex-col items-center justify-center rounded-lg border border-dashed border-atlantia-rose/45 bg-white px-4 py-4 text-center transition hover:border-atlantia-wine hover:bg-atlantia-blush/35">
                                                            <svg class="h-7 w-7 text-atlantia-wine" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                                                <path d="M12 16V4"/><path d="m7 9 5-5 5 5"/><path d="M4 20h16"/>
                                                            </svg>
                                                            <span class="mt-2 text-sm font-black text-atlantia-wine">Subir imagenes</span>
                                                            <span class="mt-1 text-[11px] leading-4 text-atlantia-ink/55">Hasta 8 imagenes JPG, PNG o WEBP.</span>
                                                            <input name="imagenes[]" type="file" accept="image/png,image/jpeg,image/webp" multiple class="sr-only" data-product-image-input>
                                                        </label>
                                                        <div>
                                                            <div class="grid grid-cols-[repeat(auto-fill,minmax(90px,1fr))] gap-2">
                                                                @forelse ($producto->imagenes as $imagen)
                                                                    <div class="overflow-hidden rounded-lg border border-atlantia-rose/15 bg-atlantia-cream">
                                                                        <img src="{{ \Illuminate\Support\Facades\Storage::url($imagen->path) }}" alt="{{ $imagen->alt_text }}" class="h-24 w-full object-cover">
                                                                    </div>
                                                                @empty
                                                                    <div class="grid h-24 place-items-center rounded-lg border border-dashed border-atlantia-rose/25 text-xs text-atlantia-ink/45">Sin imagenes</div>
                                                                @endforelse
                                                            </div>
                                                            <div class="mt-2 hidden grid-cols-[repeat(auto-fill,minmax(90px,1fr))] gap-2" data-product-image-preview></div>
                                                        </div>
                                                    </div>
                                                </section>
                                            </div>

                                            <div class="space-y-3">
                                                <section class="rounded-xl border border-atlantia-rose/15 bg-white p-3 shadow-sm">
                                                    <h3 class="text-sm font-black text-atlantia-wine">Precios</h3>
                                                    <div class="mt-3 grid gap-3 sm:grid-cols-3">
                                                        <label class="block">
                                                            <span class="text-xs font-bold text-atlantia-ink">Precio base Q <span class="text-atlantia-wine">*</span></span>
                                                            <input name="precio_base" inputmode="decimal" value="{{ number_format((float) $producto->precio_base, 2, '.', '') }}" class="{{ $inputBase }} {{ $inputNormal }}" required>
                                                        </label>
                                                        <label class="block">
                                                            <span class="text-xs font-bold text-atlantia-ink">Precio oferta Q</span>
                                                            <input name="precio_oferta" inputmode="decimal" value="{{ $producto->precio_oferta !== null ? number_format((float) $producto->precio_oferta, 2, '.', '') : '' }}" placeholder="Opcional" class="{{ $inputBase }} {{ $inputNormal }}">
                                                            <span class="mt-1 block text-[11px] text-atlantia-ink/45">Dejar vacio si no hay oferta.</span>
                                                        </label>
                                                        <label class="block">
                                                            <span class="text-xs font-bold text-atlantia-ink">Costo interno Q</span>
                                                            <input inputmode="decimal" placeholder="12.00" class="{{ $inputBase }} {{ $inputNormal }}">
                                                            <span class="mt-1 block text-[11px] text-atlantia-ink/45">Solo visible para ti.</span>
                                                        </label>
                                                        <label class="block">
                                                            <span class="text-xs font-bold text-atlantia-ink">Inicio oferta</span>
                                                            <input type="date" class="{{ $inputBase }} {{ $inputNormal }}">
                                                        </label>
                                                        <label class="block">
                                                            <span class="text-xs font-bold text-atlantia-ink">Fin oferta</span>
                                                            <input type="date" class="{{ $inputBase }} {{ $inputNormal }}">
                                                        </label>
                                                        <div class="rounded-lg border border-atlantia-rose/15 bg-atlantia-cream/45 p-3 sm:col-span-3">
                                                            <p class="text-sm font-black text-atlantia-wine">Margen estimado: <span class="text-emerald-700">52.0%</span></p>
                                                            <p class="mt-1 text-[11px] text-atlantia-ink/55">Basado en precio base y costo interno.</p>
                                                        </div>
                                                    </div>
                                                </section>

                                                <section class="rounded-xl border border-atlantia-rose/15 bg-white p-3 shadow-sm">
                                                    <h3 class="text-sm font-black text-atlantia-wine">Inventario</h3>
                                                    <div class="mt-3 grid gap-3 sm:grid-cols-3">
                                                        <label class="block">
                                                            <span class="text-xs font-bold text-atlantia-ink">Stock actual</span>
                                                            <input name="stock_actual" type="number" min="0" value="{{ (int) ($producto->inventario?->stock_actual ?? 0) }}" class="{{ $inputBase }} {{ $inputNormal }}">
                                                        </label>
                                                        <label class="block">
                                                            <span class="text-xs font-bold text-atlantia-ink">Stock minimo</span>
                                                            <input name="stock_minimo" type="number" min="0" value="{{ (int) ($producto->inventario?->stock_minimo ?? 5) }}" class="{{ $inputBase }} {{ $inputNormal }}">
                                                        </label>
                                                        <label class="block">
                                                            <span class="text-xs font-bold text-atlantia-ink">Stock maximo</span>
                                                            <input name="stock_maximo" type="number" min="0" value="{{ (int) ($producto->inventario?->stock_maximo ?? 100) }}" class="{{ $inputBase }} {{ $inputNormal }}">
                                                        </label>
                                                        <label class="block">
                                                            <span class="text-xs font-bold text-atlantia-ink">Stock reservado</span>
                                                            <input type="number" value="{{ (int) ($producto->inventario?->stock_reservado ?? 0) }}" class="{{ $inputBase }} {{ $inputNormal }}" readonly>
                                                        </label>
                                                        <label class="block">
                                                            <span class="text-xs font-bold text-atlantia-ink">Stock disponible</span>
                                                            <input type="number" value="{{ max(0, (int) ($producto->inventario?->stock_actual ?? 0) - (int) ($producto->inventario?->stock_reservado ?? 0)) }}" class="{{ $inputBase }} border-emerald-200 bg-emerald-50 text-emerald-700" readonly>
                                                        </label>
                                                        <label class="block">
                                                            <span class="text-xs font-bold text-atlantia-ink">Ubicacion interna</span>
                                                            <input placeholder="Estante A3" class="{{ $inputBase }} {{ $inputNormal }}">
                                                        </label>
                                                    </div>
                                                </section>

                                                <section class="rounded-xl border border-atlantia-rose/15 bg-white p-3 shadow-sm">
                                                    <h3 class="text-sm font-black text-atlantia-wine">Publicacion y operacion</h3>
                                                    <div class="mt-3 grid gap-x-4 gap-y-3 sm:grid-cols-2">
                                                        <input type="hidden" name="is_active" value="0">
                                                        <input type="hidden" name="visible_catalogo" value="0">
                                                        <input type="hidden" name="requiere_refrigeracion" value="0">
                                                        @foreach ([
                                                            ['name' => 'is_active', 'checked' => $producto->is_active, 'label' => 'Activo', 'hint' => 'El producto esta activo.'],
                                                            ['name' => 'visible_catalogo', 'checked' => $producto->visible_catalogo, 'label' => 'Visible en catalogo', 'hint' => 'Los clientes pueden verlo.'],
                                                            ['name' => 'requiere_refrigeracion', 'checked' => $producto->requiere_refrigeracion, 'label' => 'Requiere refrigeracion', 'hint' => 'Mantener temperatura controlada.'],
                                                        ] as $toggle)
                                                            <label class="flex items-start gap-2">
                                                                <input type="checkbox" name="{{ $toggle['name'] }}" value="1" @checked($toggle['checked']) class="peer sr-only">
                                                                <span class="mt-0.5 flex h-4 w-8 shrink-0 items-center rounded-full bg-slate-200 p-0.5 transition peer-checked:bg-atlantia-wine peer-checked:[&>span]:translate-x-4">
                                                                    <span class="h-3 w-3 rounded-full bg-white shadow transition"></span>
                                                                </span>
                                                                <span class="min-w-0">
                                                                    <span class="block text-[11px] font-black leading-4 text-atlantia-ink">{{ $toggle['label'] }}</span>
                                                                    <span class="block text-[10px] leading-3 text-atlantia-ink/48">{{ $toggle['hint'] }}</span>
                                                                </span>
                                                            </label>
                                                        @endforeach
                                                        <label class="flex items-start gap-2 opacity-80">
                                                            <input type="checkbox" disabled class="peer sr-only">
                                                            <span class="mt-0.5 flex h-4 w-8 shrink-0 items-center rounded-full bg-slate-200 p-0.5 transition peer-checked:bg-atlantia-wine peer-checked:[&>span]:translate-x-4"><span class="h-3 w-3 rounded-full bg-white shadow transition"></span></span>
                                                            <span><span class="block text-[11px] font-black leading-4 text-atlantia-ink">Producto destacado</span><span class="block text-[10px] leading-3 text-atlantia-ink/48">Aparecer en destacados.</span></span>
                                                        </label>
                                                    </div>
                                                </section>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="flex flex-col-reverse gap-2 border-t border-atlantia-rose/15 bg-white px-6 py-5 sm:flex-row sm:items-center sm:justify-between">
                                        <button type="button" class="rounded-lg border border-atlantia-rose/30 bg-white px-6 py-2.5 text-sm font-black text-atlantia-wine transition hover:bg-atlantia-blush" data-close-edit-product-modal>
                                            Cancelar
                                        </button>
                                        <div class="flex flex-col-reverse gap-2 sm:flex-row">
                                            <button type="button" class="rounded-lg border border-atlantia-rose/30 bg-white px-6 py-2.5 text-sm font-black text-atlantia-wine transition hover:bg-atlantia-blush">
                                                Guardar borrador
                                            </button>
                                            <button type="submit" class="rounded-lg bg-atlantia-wine px-8 py-2.5 text-sm font-black text-white shadow-sm transition hover:bg-atlantia-wine-700">
                                                Guardar cambios
                                            </button>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                    @empty
                        <div class="col-span-full rounded-2xl border border-dashed border-atlantia-rose/30 bg-atlantia-cream/60 px-6 py-12 text-center">
                            <p class="text-lg font-black text-atlantia-ink">No hay productos con estos filtros.</p>
                            <p class="mt-1 text-sm text-atlantia-ink/60">Limpia los filtros o crea un nuevo producto para activar tu catalogo.</p>
                            <button type="button" class="mt-5 rounded-lg bg-atlantia-wine px-5 py-3 text-sm font-black text-white shadow-sm transition hover:bg-atlantia-wine-700" data-open-create-product-modal>
                                Crear producto
                            </button>
                        </div>
                    @endforelse
                </div>

                <div class="mt-5">
                    {{ $productos->links() }}
                </div>
            </div>
        </article>
    </section>

    <div
        class="{{ $showCreateModal ? 'flex' : 'hidden' }} fixed inset-0 z-[70] items-start justify-center overflow-y-auto bg-slate-950/55 px-3 py-6 backdrop-blur-sm sm:py-10"
        data-create-product-modal
        role="dialog"
        aria-modal="true"
        aria-labelledby="create-product-title"
    >
        <div class="w-full max-w-[92rem] overflow-hidden rounded-2xl border border-atlantia-rose/20 bg-white shadow-[0_28px_90px_rgba(33,25,32,0.28)]">
            <div class="flex items-start justify-between gap-3 px-6 pt-5">
                <div class="flex items-start gap-6">
                    <span class="grid h-16 w-16 shrink-0 place-items-center rounded-full bg-atlantia-wine text-white shadow-lg shadow-atlantia-wine/25">
                        <svg class="h-8 w-8" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M20 13 11 4 4 11l9 9 7-7Z"/><path d="M7.5 7.5h.01"/>
                        </svg>
                    </span>
                    <div>
                        <h2 id="create-product-title" class="text-3xl font-black leading-tight text-atlantia-ink">Crear producto</h2>
                        <p class="mt-1 text-base text-atlantia-ink/58" data-product-modal-subtitle>Nuevo producto</p>
                    </div>
                </div>
                <button type="button" class="rounded-lg p-2 text-atlantia-ink/50 transition hover:bg-atlantia-blush hover:text-atlantia-wine" data-close-create-product-modal aria-label="Cerrar">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <path d="m6 6 12 12M18 6 6 18"/>
                    </svg>
                </button>
            </div>

            <div class="border-b border-atlantia-rose/15 px-6 pt-4">
                <nav class="flex flex-wrap gap-8 text-sm font-bold text-atlantia-ink/62" aria-label="Secciones del producto">
                    <span class="inline-flex items-center gap-2 border-b-2 border-atlantia-wine px-1 pb-3 text-atlantia-wine">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 20a8 8 0 1 0 0-16 8 8 0 0 0 0 16Z"/><path d="m9 12 2 2 4-5"/></svg>
                        General
                    </span>
                    <span class="inline-flex items-center gap-2 px-1 pb-3">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 13 11 4 4 11l9 9 7-7Z"/><path d="M7.5 7.5h.01"/></svg>
                        Precios
                    </span>
                    <span class="inline-flex items-center gap-2 px-1 pb-3">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m12 3 8 4.5-8 4.5-8-4.5L12 3Z"/><path d="M4 7.5v9L12 21l8-4.5v-9"/></svg>
                        Inventario
                    </span>
                    <span class="inline-flex items-center gap-2 px-1 pb-3">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="5" width="18" height="14" rx="2"/><path d="m3 16 5-5 4 4 2-2 7 7"/></svg>
                        Imagenes
                    </span>
                    <span class="inline-flex items-center gap-2 px-1 pb-3">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/></svg>
                        Publicacion
                    </span>
                </nav>
            </div>

            <form method="POST" action="{{ route('vendedor.productos.store') }}" enctype="multipart/form-data">
                @csrf
                <fieldset @disabled(! $puedeCrear) class="px-5 py-4">
                    <div class="grid gap-3 xl:grid-cols-[1.2fr_0.85fr]">
                        <div class="space-y-3">
                            <section class="rounded-xl border border-atlantia-rose/15 bg-white p-3 shadow-sm">
                                <div class="flex items-center gap-2">
                                    <span class="grid h-8 w-8 place-items-center rounded-lg bg-atlantia-blush text-atlantia-wine">
                                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                            <path d="M5 3v18l2-1 2 1 2-1 2 1 2-1 2 1 2-1V3Z"/><path d="M8 7h8"/><path d="M8 11h8"/>
                                        </svg>
                                    </span>
                                    <h3 class="text-sm font-black text-atlantia-wine">Informacion basica</h3>
                                </div>

                                <div class="mt-3 grid gap-3 md:grid-cols-2">
                                    <label class="block">
                                        <span class="text-xs font-bold text-atlantia-ink">Categoria <span class="text-atlantia-wine">*</span></span>
                                        <select name="categoria_id" class="{{ $inputBase }} {{ $errors->has('categoria_id') ? $inputError : $inputNormal }}" required>
                                            <option value="">Selecciona una categoria</option>
                                            @foreach ($categorias as $categoria)
                                                <option value="{{ $categoria->id }}" @selected((string) old('categoria_id') === (string) $categoria->id)>{{ $categoria->nombre }}</option>
                                            @endforeach
                                        </select>
                                        @error('categoria_id') <p class="{{ $errorText }}">{{ $message }}</p> @enderror
                                    </label>
                                    <label class="block">
                                        <span class="text-xs font-bold text-atlantia-ink">Nombre del producto <span class="text-atlantia-wine">*</span></span>
                                        <input name="nombre" value="{{ old('nombre') }}" placeholder="Ej. Naruto" class="{{ $inputBase }} {{ $errors->has('nombre') ? $inputError : $inputNormal }}" data-product-name required>
                                        @error('nombre') <p class="{{ $errorText }}">{{ $message }}</p> @enderror
                                    </label>
                                    <label class="block">
                                        <span class="text-xs font-bold text-atlantia-ink">Subcategoria</span>
                                        <select class="{{ $inputBase }} {{ $inputNormal }}">
                                            <option>Alimentos secos</option>
                                            <option>Producto refrigerado</option>
                                            <option>Producto general</option>
                                        </select>
                                    </label>
                                    <label class="block">
                                        <span class="text-xs font-bold text-atlantia-ink">Marca</span>
                                        <input placeholder="Marca o fabricante" class="{{ $inputBase }} {{ $inputNormal }}">
                                        <span class="mt-1 block text-[11px] text-atlantia-ink/45">Marca o fabricante del producto.</span>
                                    </label>
                                    <label class="block">
                                        <span class="text-xs font-bold text-atlantia-ink">SKU automatico <span class="text-atlantia-wine">*</span></span>
                                        <input name="sku" value="{{ old('sku') }}" placeholder="Se genera al escribir el nombre" class="{{ $inputBase }} {{ $errors->has('sku') ? $inputError : $inputNormal }} font-mono text-xs font-black uppercase tracking-wide text-atlantia-wine" data-product-sku required readonly>
                                        <span class="mt-1 block text-[11px] text-atlantia-ink/45">Codigo unico interno para este producto.</span>
                                        @error('sku') <p class="{{ $errorText }}">{{ $message }}</p> @enderror
                                    </label>
                                    <label class="block">
                                        <span class="text-xs font-bold text-atlantia-ink">Unidad de medida <span class="text-atlantia-wine">*</span></span>
                                        <select name="unidad_medida" class="{{ $inputBase }} {{ $inputNormal }}" required>
                                            @foreach (['unidad', 'libra', 'kilogramo', 'gramo', 'litro', 'mililitro', 'paquete'] as $unidad)
                                                <option value="{{ $unidad }}" @selected(old('unidad_medida', 'unidad') === $unidad)>{{ ucfirst($unidad) }}</option>
                                            @endforeach
                                        </select>
                                    </label>
                                    <label class="block">
                                        <span class="text-xs font-bold text-atlantia-ink">Codigo de barras automatico <span class="text-atlantia-wine">*</span></span>
                                        <input name="codigo_barras" value="{{ old('codigo_barras') }}" placeholder="Se genera al escribir el nombre" class="{{ $inputBase }} {{ $errors->has('codigo_barras') ? $inputError : $inputNormal }} font-mono text-xs font-black tracking-[0.16em] text-atlantia-ink/70" data-product-barcode required readonly>
                                        <span class="mt-1 block text-[11px] text-atlantia-ink/45">EAN/UPC generado automaticamente.</span>
                                        @error('codigo_barras') <p class="{{ $errorText }}">{{ $message }}</p> @enderror
                                    </label>
                                    <label class="block">
                                        <span class="text-xs font-bold text-atlantia-ink">Presentacion</span>
                                        <input value="{{ old('presentacion') }}" placeholder="Ej. 1 unidad" class="{{ $inputBase }} {{ $inputNormal }}" data-product-presentation>
                                        <span class="mt-1 block text-[11px] text-atlantia-ink/45">Texto de referencia visual; no se guarda como campo separado.</span>
                                    </label>
                                </div>
                            </section>

                            <section class="rounded-xl border border-atlantia-rose/15 bg-white p-3 shadow-sm">
                                <div class="flex items-center gap-2">
                                    <span class="grid h-8 w-8 place-items-center rounded-lg bg-atlantia-blush text-atlantia-wine">
                                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                            <path d="M4 19V5"/><path d="M4 19h16"/><path d="m7 15 4-4 3 3 5-7"/>
                                        </svg>
                                    </span>
                                    <h3 class="text-sm font-black text-atlantia-wine">Descripcion</h3>
                                </div>
                                <div class="mt-3 grid gap-3 md:grid-cols-2">
                                    <label class="block">
                                        <span class="text-xs font-bold text-atlantia-ink">Descripcion corta</span>
                                        <textarea name="descripcion" rows="3" maxlength="5000" class="{{ $inputBase }} {{ $errors->has('descripcion') ? $inputError : $inputNormal }}" placeholder="Describe origen, presentacion y calidad.">{{ old('descripcion') }}</textarea>
                                        <span class="mt-1 block text-[11px] text-atlantia-ink/45">Max. 160 caracteres sugeridos.</span>
                                        @error('descripcion') <p class="{{ $errorText }}">{{ $message }}</p> @enderror
                                    </label>
                                    <label class="block">
                                        <span class="text-xs font-bold text-atlantia-ink">Descripcion larga</span>
                                        <textarea rows="3" class="{{ $inputBase }} {{ $inputNormal }}" placeholder="Cuentales a tus clientes caracteristicas, beneficios y uso."></textarea>
                                        <span class="mt-1 block text-[11px] text-atlantia-ink/45">Referencia visual; usa la descripcion corta para guardar.</span>
                                    </label>
                                </div>
                            </section>

                            <section class="rounded-xl border border-atlantia-rose/15 bg-white p-3 shadow-sm">
                                <div class="flex items-center gap-2">
                                    <span class="grid h-8 w-8 place-items-center rounded-lg bg-atlantia-blush text-atlantia-wine">
                                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                            <rect x="3" y="5" width="18" height="14" rx="2"/><path d="m3 16 5-5 4 4 2-2 7 7"/>
                                        </svg>
                                    </span>
                                    <h3 class="text-sm font-black text-atlantia-wine">Imagenes</h3>
                                </div>
                                <div class="mt-3 grid gap-3 md:grid-cols-[220px_1fr]">
                                    <label class="flex min-h-32 cursor-pointer flex-col items-center justify-center rounded-lg border border-dashed border-atlantia-rose/45 bg-white px-4 py-4 text-center transition hover:border-atlantia-wine hover:bg-atlantia-blush/35">
                                        <svg class="h-7 w-7 text-atlantia-wine" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                            <path d="M12 16V4"/><path d="m7 9 5-5 5 5"/><path d="M4 20h16"/>
                                        </svg>
                                        <span class="mt-2 text-sm font-black text-atlantia-wine">Subir imagenes</span>
                                        <span class="mt-1 text-[11px] leading-4 text-atlantia-ink/55">Hasta 8 imagenes JPG, PNG o WEBP. Maximo 5 MB cada una.</span>
                                        <input name="imagenes[]" type="file" accept="image/png,image/jpeg,image/webp" multiple class="sr-only" data-product-image-input>
                                    </label>
                                    <div class="hidden grid-cols-[repeat(auto-fill,minmax(90px,1fr))] gap-2" data-product-image-preview></div>
                                </div>
                                @error('imagenes') <p class="{{ $errorText }}">{{ $message }}</p> @enderror
                                @error('imagenes.*') <p class="{{ $errorText }}">{{ $message }}</p> @enderror
                            </section>
                        </div>

                        <div class="space-y-3">
                            <section class="rounded-xl border border-atlantia-rose/15 bg-white p-3 shadow-sm">
                                <div class="flex items-center gap-2">
                                    <span class="grid h-8 w-8 place-items-center rounded-lg bg-atlantia-blush text-atlantia-wine">
                                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                            <path d="M20 13 11 4 4 11l9 9 7-7Z"/><path d="M7.5 7.5h.01"/>
                                        </svg>
                                    </span>
                                    <h3 class="text-sm font-black text-atlantia-wine">Precios</h3>
                                </div>
                                <div class="mt-3 grid gap-3 sm:grid-cols-3">
                                    <label class="block">
                                        <span class="text-xs font-bold text-atlantia-ink">Precio base Q <span class="text-atlantia-wine">*</span></span>
                                        <input name="precio_base" inputmode="decimal" value="{{ old('precio_base') }}" placeholder="25.00" class="{{ $inputBase }} {{ $errors->has('precio_base') ? $inputError : $inputNormal }}" data-price-base required>
                                        @error('precio_base') <p class="{{ $errorText }}">{{ $message }}</p> @enderror
                                    </label>
                                    <label class="block">
                                        <span class="text-xs font-bold text-atlantia-ink">Precio oferta Q</span>
                                        <input name="precio_oferta" inputmode="decimal" value="{{ old('precio_oferta') }}" placeholder="Opcional" class="{{ $inputBase }} {{ $errors->has('precio_oferta') ? $inputError : $inputNormal }}">
                                        <span class="mt-1 block text-[11px] text-atlantia-ink/45">Dejar vacio si no hay oferta.</span>
                                        @error('precio_oferta') <p class="{{ $errorText }}">{{ $message }}</p> @enderror
                                    </label>
                                    <label class="block">
                                        <span class="text-xs font-bold text-atlantia-ink">Costo interno Q</span>
                                        <input inputmode="decimal" placeholder="12.00" class="{{ $inputBase }} {{ $inputNormal }}">
                                        <span class="mt-1 block text-[11px] text-atlantia-ink/45">Solo visible para ti.</span>
                                    </label>
                                    <label class="block">
                                        <span class="text-xs font-bold text-atlantia-ink">Inicio oferta</span>
                                        <input type="date" class="{{ $inputBase }} {{ $inputNormal }}">
                                    </label>
                                    <label class="block">
                                        <span class="text-xs font-bold text-atlantia-ink">Fin oferta</span>
                                        <input type="date" class="{{ $inputBase }} {{ $inputNormal }}">
                                    </label>
                                    <div class="rounded-lg border border-atlantia-rose/15 bg-atlantia-cream/45 p-3 sm:col-span-3">
                                        <p class="text-sm font-black text-atlantia-wine">Margen estimado: <span class="text-emerald-700">52.0%</span></p>
                                        <p class="mt-1 text-[11px] text-atlantia-ink/55">Basado en precio base y costo interno.</p>
                                    </div>
                                </div>
                            </section>

                            <section class="rounded-xl border border-atlantia-rose/15 bg-white p-3 shadow-sm">
                                <div class="flex items-center gap-2">
                                    <span class="grid h-8 w-8 place-items-center rounded-lg bg-atlantia-blush text-atlantia-wine">
                                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                            <path d="m12 3 8 4.5-8 4.5-8-4.5L12 3Z"/><path d="M4 7.5v9L12 21l8-4.5v-9"/>
                                        </svg>
                                    </span>
                                    <h3 class="text-sm font-black text-atlantia-wine">Inventario</h3>
                                </div>
                                <div class="mt-3 grid gap-3 sm:grid-cols-3">
                                    <label class="block">
                                        <span class="text-xs font-bold text-atlantia-ink">Stock actual <span class="text-atlantia-wine">*</span></span>
                                        <input name="stock_actual" type="number" min="0" value="{{ old('stock_actual', 0) }}" class="{{ $inputBase }} {{ $errors->has('stock_actual') ? $inputError : $inputNormal }}" required>
                                        @error('stock_actual') <p class="{{ $errorText }}">{{ $message }}</p> @enderror
                                    </label>
                                    <label class="block">
                                        <span class="text-xs font-bold text-atlantia-ink">Stock minimo <span class="text-atlantia-wine">*</span></span>
                                        <input name="stock_minimo" type="number" min="0" value="{{ old('stock_minimo', 5) }}" class="{{ $inputBase }} {{ $errors->has('stock_minimo') ? $inputError : $inputNormal }}" required>
                                        @error('stock_minimo') <p class="{{ $errorText }}">{{ $message }}</p> @enderror
                                    </label>
                                    <label class="block">
                                        <span class="text-xs font-bold text-atlantia-ink">Stock maximo</span>
                                        <input name="stock_maximo" type="number" min="0" value="{{ old('stock_maximo', 100) }}" class="{{ $inputBase }} {{ $errors->has('stock_maximo') ? $inputError : $inputNormal }}">
                                        <span class="mt-1 block text-[11px] text-atlantia-ink/45">Max. sugerido para reposicion.</span>
                                        @error('stock_maximo') <p class="{{ $errorText }}">{{ $message }}</p> @enderror
                                    </label>
                                    <label class="block">
                                        <span class="text-xs font-bold text-atlantia-ink">Stock reservado</span>
                                        <input type="number" value="0" class="{{ $inputBase }} {{ $inputNormal }}" readonly>
                                        <span class="mt-1 block text-[11px] text-atlantia-ink/45">Pedidos pendientes.</span>
                                    </label>
                                    <label class="block">
                                        <span class="text-xs font-bold text-atlantia-ink">Stock disponible</span>
                                        <input type="number" value="{{ old('stock_actual', 0) }}" class="{{ $inputBase }} border-emerald-200 bg-emerald-50 text-emerald-700" data-stock-available readonly>
                                        <span class="mt-1 block text-[11px] text-atlantia-ink/45">Lo que puedes vender.</span>
                                    </label>
                                    <label class="block">
                                        <span class="text-xs font-bold text-atlantia-ink">Ubicacion interna</span>
                                        <input placeholder="Estante A3" class="{{ $inputBase }} {{ $inputNormal }}">
                                        <span class="mt-1 block text-[11px] text-atlantia-ink/45">Ej. Bodega, Estante A3.</span>
                                    </label>
                                    <label class="block sm:col-span-1">
                                        <span class="text-xs font-bold text-atlantia-ink">Estado de stock</span>
                                        <select class="{{ $inputBase }} border-emerald-200 bg-emerald-50 text-emerald-700">
                                            <option>Disponible</option>
                                            <option>Stock bajo</option>
                                            <option>Agotado</option>
                                        </select>
                                    </label>
                                    <div class="rounded-lg border border-emerald-200 bg-emerald-50 p-3 sm:col-span-2">
                                        <p class="text-sm font-black text-emerald-700">Stock normal</p>
                                        <p class="mt-1 text-[11px] text-emerald-700/75">Niveles dentro del rango establecido.</p>
                                    </div>
                                </div>
                            </section>

                            <section class="rounded-xl border border-atlantia-rose/15 bg-white p-3 shadow-sm">
                                <div class="flex items-center gap-2">
                                    <span class="grid h-7 w-7 place-items-center rounded-lg bg-atlantia-blush text-atlantia-wine">
                                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                            <path d="M12 20a8 8 0 1 0 0-16 8 8 0 0 0 0 16Z"/><path d="m9 12 2 2 4-5"/>
                                        </svg>
                                    </span>
                                    <h3 class="text-sm font-black text-atlantia-wine">Publicacion y operacion</h3>
                                </div>
                                <div class="mt-3 grid gap-x-4 gap-y-3 sm:grid-cols-2">
                                    <label class="flex items-start gap-2">
                                        <input type="checkbox" name="is_active" value="1" checked class="peer sr-only">
                                        <span class="mt-0.5 flex h-4 w-8 shrink-0 items-center rounded-full bg-slate-200 p-0.5 transition peer-checked:bg-atlantia-wine peer-checked:[&>span]:translate-x-4">
                                            <span class="h-3 w-3 rounded-full bg-white shadow transition"></span>
                                        </span>
                                        <span class="min-w-0">
                                            <span class="block text-[11px] font-black leading-4 text-atlantia-ink">Activo</span>
                                            <span class="block text-[10px] leading-3 text-atlantia-ink/48">El producto esta activo.</span>
                                        </span>
                                    </label>
                                    <label class="flex items-start gap-2">
                                        <input type="checkbox" name="visible_catalogo" value="1" @checked(old('visible_catalogo', true)) class="peer sr-only">
                                        <span class="mt-0.5 flex h-4 w-8 shrink-0 items-center rounded-full bg-slate-200 p-0.5 transition peer-checked:bg-atlantia-wine peer-checked:[&>span]:translate-x-4">
                                            <span class="h-3 w-3 rounded-full bg-white shadow transition"></span>
                                        </span>
                                        <span class="min-w-0">
                                            <span class="block text-[11px] font-black leading-4 text-atlantia-ink">Visible en catalogo</span>
                                            <span class="block text-[10px] leading-3 text-atlantia-ink/48">Los clientes pueden verlo.</span>
                                        </span>
                                    </label>
                                    <label class="flex items-start gap-2 opacity-80">
                                        <input type="checkbox" disabled class="peer sr-only">
                                        <span class="mt-0.5 flex h-4 w-8 shrink-0 items-center rounded-full bg-slate-200 p-0.5 transition peer-checked:bg-atlantia-wine peer-checked:[&>span]:translate-x-4">
                                            <span class="h-3 w-3 rounded-full bg-white shadow transition"></span>
                                        </span>
                                        <span class="min-w-0">
                                            <span class="block text-[11px] font-black leading-4 text-atlantia-ink">Producto destacado</span>
                                            <span class="block text-[10px] leading-3 text-atlantia-ink/48">Aparecer en destacados.</span>
                                        </span>
                                    </label>
                                    <label class="flex items-start gap-2">
                                        <input type="checkbox" name="requiere_refrigeracion" value="1" @checked(old('requiere_refrigeracion')) class="peer sr-only">
                                        <span class="mt-0.5 flex h-4 w-8 shrink-0 items-center rounded-full bg-slate-200 p-0.5 transition peer-checked:bg-atlantia-wine peer-checked:[&>span]:translate-x-4">
                                            <span class="h-3 w-3 rounded-full bg-white shadow transition"></span>
                                        </span>
                                        <span class="min-w-0">
                                            <span class="block text-[11px] font-black leading-4 text-atlantia-ink">Requiere refrigeracion</span>
                                            <span class="block text-[10px] leading-3 text-atlantia-ink/48">Mantener temperatura controlada.</span>
                                        </span>
                                    </label>
                                    <label class="flex items-start gap-2">
                                        <input type="checkbox" checked disabled class="peer sr-only">
                                        <span class="mt-0.5 flex h-4 w-8 shrink-0 items-center rounded-full bg-slate-200 p-0.5 transition peer-checked:bg-atlantia-wine peer-checked:[&>span]:translate-x-4">
                                            <span class="h-3 w-3 rounded-full bg-white shadow transition"></span>
                                        </span>
                                        <span class="min-w-0">
                                            <span class="block text-[11px] font-black leading-4 text-atlantia-ink">Disponible para entrega</span>
                                            <span class="block text-[10px] leading-3 text-atlantia-ink/48">Disponible para envio a domicilio.</span>
                                        </span>
                                    </label>
                                    <label class="flex items-start gap-2">
                                        <input type="checkbox" checked disabled class="peer sr-only">
                                        <span class="mt-0.5 flex h-4 w-8 shrink-0 items-center rounded-full bg-slate-200 p-0.5 transition peer-checked:bg-atlantia-wine peer-checked:[&>span]:translate-x-4">
                                            <span class="h-3 w-3 rounded-full bg-white shadow transition"></span>
                                        </span>
                                        <span class="min-w-0">
                                            <span class="block text-[11px] font-black leading-4 text-atlantia-ink">Disponible para recoger en tienda</span>
                                            <span class="block text-[10px] leading-3 text-atlantia-ink/48">Los clientes pueden recoger en tienda.</span>
                                        </span>
                                    </label>
                                </div>
                            </section>

                            <section class="rounded-xl border border-atlantia-rose/15 bg-atlantia-cream/40 p-3">
                                <h3 class="text-sm font-black text-atlantia-wine">Informacion del vendedor</h3>
                                <p class="mt-3 text-xs text-atlantia-ink/55">Vendedor</p>
                                <p class="text-sm font-black text-atlantia-ink">{{ $vendor?->business_name ?? $user?->name }}</p>
                                <p class="mt-3 text-xs text-atlantia-ink/55">Estado</p>
                                <span class="mt-1 inline-flex rounded-full bg-emerald-50 px-3 py-1 text-xs font-black text-emerald-700 ring-1 ring-emerald-200">Aprobado</span>
                            </section>
                        </div>
                    </div>
                </fieldset>

                <div class="flex flex-col-reverse gap-2 border-t border-atlantia-rose/15 bg-white px-6 py-5 sm:flex-row sm:items-center sm:justify-between">
                    <button type="button" class="rounded-lg border border-atlantia-rose/30 bg-white px-6 py-2.5 text-sm font-black text-atlantia-wine transition hover:bg-atlantia-blush" data-close-create-product-modal>
                        Cancelar
                    </button>
                    <div class="flex flex-col-reverse gap-2 sm:flex-row">
                        <button type="button" class="rounded-lg border border-atlantia-rose/30 bg-white px-6 py-2.5 text-sm font-black text-atlantia-wine transition hover:bg-atlantia-blush">
                            Guardar borrador
                        </button>
                        <button type="submit" class="rounded-lg bg-atlantia-wine px-8 py-2.5 text-sm font-black text-white shadow-sm transition hover:bg-atlantia-wine-700 disabled:cursor-not-allowed disabled:opacity-55" @disabled(! $puedeCrear)>
                            Guardar cambios
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
    <script nonce="{{ request()->attributes->get('csp_nonce') }}">
        (() => {
            const modal = document.querySelector('[data-create-product-modal]');

            const openModal = () => {
                modal?.classList.remove('hidden');
                modal?.classList.add('flex');
                document.documentElement.classList.add('overflow-hidden');
            };

            const closeModal = () => {
                modal?.classList.add('hidden');
                modal?.classList.remove('flex');
                document.documentElement.classList.remove('overflow-hidden');
            };

            document.querySelectorAll('[data-open-create-product-modal]').forEach((button) => {
                button.addEventListener('click', openModal);
            });

            document.querySelectorAll('[data-close-create-product-modal]').forEach((button) => {
                button.addEventListener('click', closeModal);
            });

            document.querySelectorAll('[data-open-edit-product-modal]').forEach((button) => {
                button.addEventListener('click', () => {
                    const modal = document.querySelector(`[data-edit-product-modal="${button.dataset.openEditProductModal}"]`);

                    modal?.classList.remove('hidden');
                    modal?.classList.add('flex');
                    document.documentElement.classList.add('overflow-hidden');
                });
            });

            document.querySelectorAll('[data-close-edit-product-modal]').forEach((button) => {
                button.addEventListener('click', () => {
                    const modal = button.closest('[data-edit-product-modal]');

                    modal?.classList.add('hidden');
                    modal?.classList.remove('flex');
                    document.documentElement.classList.remove('overflow-hidden');
                });
            });

            document.querySelectorAll('[data-edit-product-modal]').forEach((editModal) => {
                editModal.addEventListener('click', (event) => {
                    if (event.target === editModal) {
                        editModal.classList.add('hidden');
                        editModal.classList.remove('flex');
                        document.documentElement.classList.remove('overflow-hidden');
                    }
                });
            });

            modal?.addEventListener('click', (event) => {
                if (event.target === modal) {
                    closeModal();
                }
            });

            document.addEventListener('keydown', (event) => {
                if (event.key === 'Escape') {
                    closeModal();
                    document.querySelectorAll('[data-edit-product-modal]').forEach((editModal) => {
                        editModal.classList.add('hidden');
                        editModal.classList.remove('flex');
                    });
                    document.documentElement.classList.remove('overflow-hidden');
                }
            });

            document.querySelectorAll('[data-vendor-product-filter]').forEach((control) => {
                control.addEventListener('change', () => control.form?.requestSubmit());
            });

            const normalizeName = (value) => String(value ?? '')
                .normalize('NFD')
                .replace(/[\u0300-\u036f]/g, '')
                .replace(/[^a-zA-Z0-9]+/g, ' ')
                .trim();

            const skuFromName = (value) => {
                const normalized = normalizeName(value)
                    .toUpperCase()
                    .split(/\s+/)
                    .filter(Boolean)
                    .slice(0, 3)
                    .join('-');

                return normalized ? `TAL-${normalized}` : '';
            };

            const ean13CheckDigit = (body) => {
                const sum = body.split('').reduce((total, digit, index) => {
                    return total + (Number(digit) * (index % 2 === 0 ? 1 : 3));
                }, 0);

                return String((10 - (sum % 10)) % 10);
            };

            const barcodeFromName = (value) => {
                const normalized = normalizeName(value).toUpperCase();

                if (!normalized) {
                    return '';
                }

                let hash = 0;

                for (let index = 0; index < normalized.length; index += 1) {
                    hash = ((hash * 31) + normalized.charCodeAt(index)) >>> 0;
                }

                const body = `750${String(hash).padStart(9, '0').slice(-9)}`;

                return `${body}${ean13CheckDigit(body)}`;
            };

            document.querySelectorAll('[data-product-name]').forEach((nameInput) => {
                const form = nameInput.closest('form');
                const skuInput = form?.querySelector('[data-product-sku]');
                const barcodeInput = form?.querySelector('[data-product-barcode]');
                const subtitle = document.querySelector('[data-product-modal-subtitle]');
                const syncCodes = () => {
                    if (skuInput && !skuInput.dataset.touchedManually) {
                        skuInput.value = skuFromName(nameInput.value);
                    }

                    if (barcodeInput && !barcodeInput.dataset.touchedManually) {
                        barcodeInput.value = barcodeFromName(nameInput.value);
                    }

                    if (subtitle) {
                        subtitle.textContent = nameInput.value.trim() || 'Nuevo producto';
                    }
                };

                skuInput?.addEventListener('input', () => {
                    skuInput.dataset.touchedManually = 'true';
                });
                barcodeInput?.addEventListener('input', () => {
                    barcodeInput.dataset.touchedManually = 'true';
                });
                nameInput.addEventListener('input', syncCodes);
                syncCodes();
            });

            document.querySelectorAll('input[name="stock_actual"]').forEach((stockInput) => {
                const form = stockInput.closest('form');
                const availableInput = form?.querySelector('[data-stock-available]');
                const syncStock = () => {
                    if (availableInput) {
                        availableInput.value = stockInput.value || '0';
                    }
                };

                stockInput.addEventListener('input', syncStock);
                syncStock();
            });

            document.querySelectorAll('[data-product-image-input]').forEach((input) => {
                const preview = input.closest('div')?.querySelector('[data-product-image-preview]');

                input.addEventListener('change', () => {
                    if (!preview) {
                        return;
                    }

                    preview.innerHTML = '';
                    const files = Array.from(input.files ?? []).slice(0, 8);

                    preview.classList.toggle('hidden', files.length === 0);
                    preview.classList.toggle('grid', files.length > 0);

                    files.forEach((file) => {
                        const card = document.createElement('div');
                        card.className = 'overflow-hidden rounded-lg border border-atlantia-rose/20 bg-white shadow-sm';

                        const image = document.createElement('img');
                        image.className = 'h-24 w-full bg-atlantia-cream object-contain p-2';
                        image.alt = file.name;
                        image.src = URL.createObjectURL(file);
                        image.addEventListener('load', () => URL.revokeObjectURL(image.src), { once: true });

                        const name = document.createElement('p');
                        name.className = 'truncate px-2 py-2 text-[11px] font-bold text-atlantia-ink/70';
                        name.textContent = file.name;

                        card.append(image, name);
                        preview.append(card);
                    });
                });
            });

            const escapeHtml = (value) => String(value ?? '')
                .replaceAll('&', '&amp;')
                .replaceAll('<', '&lt;')
                .replaceAll('>', '&gt;')
                .replaceAll('"', '&quot;')
                .replaceAll("'", '&#039;');

            const barcodeBars = (value, height = 42) => {
                const digits = String(value || '7500000000000').replace(/\D/g, '') || '7500000000000';
                const patterns = ['212222', '222122', '222221', '121223', '121322', '131222', '122213', '122312', '132212', '221213'];
                const sequence = `111${digits.split('').map((digit) => patterns[Number(digit) % 10]).join('')}111`;

                return sequence.split('').map((width, index) => {
                    const isBar = index % 2 === 0;
                    const color = isBar ? '#1f1117' : 'transparent';

                    return `<span style="display:inline-block;width:${Number(width)}px;height:${height}px;background:${color}"></span>`;
                }).join('');
            };

            const labelMarkup = (product, isPrice, compact = false) => `
                <article class="label ${isPrice ? 'price-label' : 'barcode-label'} ${compact ? 'compact' : ''}">
                    ${product.image && isPrice ? `<img src="${escapeHtml(product.image)}" alt="">` : ''}
                    <h1>${escapeHtml(product.name)}</h1>
                    <p class="vendor">${escapeHtml(product.vendor)}</p>
                    ${isPrice ? `<p class="price">${escapeHtml(product.price)}</p>` : ''}
                    <div class="barcode">${barcodeBars(product.barcode, isPrice ? 34 : 42)}</div>
                    <p class="code">${escapeHtml(product.barcode)}</p>
                    <p class="sku">SKU: ${escapeHtml(product.sku)}</p>
                    ${!isPrice ? `<p class="mini-price">${escapeHtml(product.price)}</p>` : ''}
                </article>
            `;

            document.querySelectorAll('[data-print-product-label]').forEach((button) => {
                button.addEventListener('click', () => {
                    const mode = button.dataset.labelType;
                    const product = {
                        name: button.dataset.productName,
                        sku: button.dataset.productSku,
                        barcode: button.dataset.productBarcode,
                        price: button.dataset.productPrice,
                        vendor: button.dataset.productVendor,
                        image: button.dataset.productImage,
                    };
                    const isPrice = mode === 'price';
                    const copies = isPrice ? 12 : 30;
                    const labels = Array.from({ length: copies }, () => labelMarkup(product, isPrice, !isPrice)).join('');
                    const popup = window.open('', '_blank', 'width=980,height=720');

                    if (!popup) {
                        return;
                    }

                    popup.document.write(`
                        <!doctype html>
                        <html>
                            <head>
                                <title>${isPrice ? 'Etiqueta de precio' : 'Codigo de barras'} - ${escapeHtml(product.name)}</title>
                                <style>
                                    * { box-sizing: border-box; }
                                    @page { size: letter; margin: 10mm; }
                                    body { margin: 0; background: #f7f2f4; font-family: Arial, sans-serif; color: #2a1018; }
                                    .toolbar {
                                        position: sticky;
                                        top: 0;
                                        z-index: 2;
                                        display: flex;
                                        align-items: center;
                                        justify-content: space-between;
                                        gap: 16px;
                                        padding: 14px 18px;
                                        border-bottom: 1px solid #ead5dd;
                                        background: #fff;
                                        box-shadow: 0 8px 24px rgba(42, 16, 24, .08);
                                    }
                                    .toolbar h2 { margin: 0; font-size: 16px; }
                                    .toolbar p { margin: 3px 0 0; color: #6d5560; font-size: 12px; }
                                    .actions { display: flex; gap: 8px; }
                                    button {
                                        border: 1px solid #d9a9b9;
                                        border-radius: 8px;
                                        background: #fff;
                                        color: #7a1f3d;
                                        cursor: pointer;
                                        font-weight: 900;
                                        padding: 10px 16px;
                                    }
                                    button.primary { border-color: #7a1f3d; background: #7a1f3d; color: #fff; }
                                    .sheet {
                                        width: 216mm;
                                        min-height: 279mm;
                                        margin: 18px auto;
                                        background: #fff;
                                        padding: 10mm;
                                        box-shadow: 0 12px 36px rgba(42, 16, 24, .12);
                                    }
                                    .grid {
                                        display: grid;
                                        grid-template-columns: repeat(${isPrice ? 3 : 3}, 1fr);
                                        gap: ${isPrice ? '7mm' : '4mm'};
                                    }
                                    .label {
                                        break-inside: avoid;
                                        border: 1px dashed #d9a9b9;
                                        border-radius: 8px;
                                        min-height: ${isPrice ? '56mm' : '29mm'};
                                        padding: ${isPrice ? '5mm' : '3mm'};
                                        text-align: center;
                                        display: flex;
                                        flex-direction: column;
                                        align-items: center;
                                        justify-content: center;
                                    }
                                    .label img { width: 24mm; height: 18mm; object-fit: contain; margin-bottom: 2mm; }
                                    .label h1 { margin: 0; max-width: 100%; font-size: ${isPrice ? '13px' : '10px'}; line-height: 1.15; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
                                    .vendor { margin: 1.5mm 0 0; max-width: 100%; color: #6d5560; font-size: ${isPrice ? '10px' : '8px'}; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
                                    .price { margin: 2mm 0; color: #7a1f3d; font-size: 24px; font-weight: 900; line-height: 1; }
                                    .mini-price { margin: 1.5mm 0 0; color: #7a1f3d; font-size: 12px; font-weight: 900; }
                                    .barcode { margin-top: 2mm; line-height: 0; white-space: nowrap; transform-origin: center; transform: scaleX(${isPrice ? '.72' : '.62'}); }
                                    .code { margin: 1.5mm 0 0; font-family: "Courier New", monospace; font-size: ${isPrice ? '10px' : '8px'}; font-weight: 900; letter-spacing: .08em; }
                                    .sku { margin: .5mm 0 0; color: #6d5560; font-size: ${isPrice ? '9px' : '7px'}; }
                                    @media print {
                                        body { background: #fff; }
                                        .toolbar { display: none; }
                                        .sheet { width: auto; min-height: auto; margin: 0; padding: 0; box-shadow: none; }
                                        .grid { gap: ${isPrice ? '6mm' : '3mm'}; }
                                    }
                                </style>
                            </head>
                            <body>
                                <header class="toolbar">
                                    <div>
                                        <h2>${isPrice ? 'Hoja de etiquetas de precio' : 'Hoja de codigos de barras'}</h2>
                                        <p>${escapeHtml(product.name)} · ${copies} etiquetas listas para imprimir</p>
                                    </div>
                                    <div class="actions">
                                        <button onclick="window.close()">Cerrar</button>
                                        <button class="primary" onclick="window.print()">Imprimir</button>
                                    </div>
                                </header>
                                <div class="sheet">
                                    <div class="grid">${labels}</div>
                                </div>
                                <script>
                                    window.addEventListener('load', () => {
                                        setTimeout(() => window.print(), 350);
                                    });
                                <\/script>
                            </body>
                        </html>
                    `);
                    popup.document.close();
                });
            });
        })();
    </script>
@endpush
