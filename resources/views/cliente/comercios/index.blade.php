@extends('layouts.marketplace')

@section('content')
    <div class="min-h-full bg-[#fffafb]">
        <section class="mx-auto w-full max-w-7xl px-4 pb-2 pt-4 sm:px-6 lg:px-8">
            <div class="flex items-end justify-between gap-5">
                <div>
                    <h1 class="text-2xl font-black leading-none text-atlantia-ink lg:text-[1.75rem]">Comercios cerca de ti</h1>
                    <p class="mt-1.5 text-xs font-semibold text-atlantia-ink/60">
                        Descubre tiendas verificadas, restaurantes, farmacias y negocios locales con entrega segura.
                    </p>
                </div>
                <div class="hidden items-center gap-2 text-[10px] font-bold text-atlantia-wine lg:flex">
                    <span class="grid h-8 w-8 place-items-center rounded-full bg-atlantia-blush">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M12 21s7-6.1 7-12A7 7 0 1 0 5 9c0 5.9 7 12 7 12Zm0-9.2A2.8 2.8 0 1 1 12 6a2.8 2.8 0 0 1 0 5.8Z" stroke="currentColor" stroke-width="1.6"/></svg>
                    </span>
                    {{ $filters['municipio'] ?: 'Todos los municipios' }}
                </div>
            </div>

            <div class="mt-4 grid grid-cols-[1fr_1fr_1.15fr] gap-2 lg:hidden">
                <a href="#filtros-comercios" class="flex h-10 items-center justify-center gap-1 rounded-lg border border-atlantia-rose/15 bg-white text-xs font-black text-atlantia-ink/65 shadow-sm">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M4 7h10M18 7h2M4 12h3M11 12h9M4 17h7M15 17h5" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/></svg>
                    Filtros
                </a>
                <a href="{{ route('comercios.index', array_merge(request()->except(['page', 'orden']), ['orden' => 'nombre'])) }}" class="flex h-10 items-center justify-center gap-1 rounded-lg border border-atlantia-rose/15 bg-white text-xs font-black text-atlantia-ink/65 shadow-sm">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="m8 5-3 3-3-3M5 8V3m11 16 3-3 3 3m-3-3v5M10 7h8M10 12h8M5 17h7" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    Ordenar
                </a>
                <span class="flex h-10 items-center justify-between rounded-lg border border-atlantia-rose/15 bg-white px-3 text-xs font-black text-atlantia-ink/65 shadow-sm">
                    Relevancia
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="m6 9 6 6 6-6" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>
                </span>
            </div>

            <form id="filtros-comercios" action="{{ route('comercios.index') }}" method="GET" class="mt-4 hidden scroll-mt-44 gap-3 rounded-xl border border-atlantia-rose/20 bg-white p-4 shadow-[0_10px_28px_rgba(63,13,29,0.07)] target:grid target:grid-cols-2 lg:grid lg:grid-cols-[1.2fr_1fr_1fr_0.9fr_auto] lg:items-end">
                <input type="hidden" name="orden" value="{{ $filters['orden'] }}">
                <input type="hidden" name="vista" value="{{ $filters['vista'] }}">

                <label class="block text-[10px] font-black text-atlantia-ink">
                    Buscar comercio
                    <div class="relative mt-1.5">
                        <input
                            type="search"
                            name="q"
                            value="{{ $filters['q'] }}"
                            placeholder="Buscar comercio o tienda..."
                            class="h-10 w-full rounded-lg border border-atlantia-rose/20 bg-white px-3 pr-9 text-[11px] font-semibold text-atlantia-ink outline-none shadow-sm placeholder:text-atlantia-ink/40 focus:border-atlantia-wine focus:ring-2 focus:ring-atlantia-blush"
                        >
                        <svg class="pointer-events-none absolute right-3 top-1/2 h-4 w-4 -translate-y-1/2 text-atlantia-wine" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="m21 21-4.3-4.3M10.8 18a7.2 7.2 0 1 1 0-14.4 7.2 7.2 0 0 1 0 14.4Z" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
                    </div>
                </label>

                <label class="block text-[10px] font-black text-atlantia-ink">
                    Municipio
                    <select name="municipio" class="mt-1.5 h-10 w-full rounded-lg border border-atlantia-rose/20 bg-white px-3 text-[11px] font-semibold text-atlantia-ink outline-none shadow-sm focus:border-atlantia-wine focus:ring-2 focus:ring-atlantia-blush">
                        <option value="">Todos los municipios</option>
                        @foreach ($municipios as $municipio)
                            <option value="{{ $municipio }}" @selected($filters['municipio'] === $municipio)>{{ $municipio }}</option>
                        @endforeach
                    </select>
                </label>

                <label class="block text-[10px] font-black text-atlantia-ink">
                    Categoria
                    <select name="categoria" class="mt-1.5 h-10 w-full rounded-lg border border-atlantia-rose/20 bg-white px-3 text-[11px] font-semibold text-atlantia-ink outline-none shadow-sm focus:border-atlantia-wine focus:ring-2 focus:ring-atlantia-blush">
                        <option value="">Todas las categorias</option>
                        @foreach ($categorias as $categoria)
                            <option value="{{ $categoria->id }}" @selected((int) $filters['categoria'] === $categoria->id)>{{ $categoria->nombre }}</option>
                        @endforeach
                    </select>
                </label>

                <label class="block text-[10px] font-black text-atlantia-ink">
                    Tiempo de entrega
                    <select name="tiempo" class="mt-1.5 h-10 w-full rounded-lg border border-atlantia-rose/20 bg-white px-3 text-[11px] font-semibold text-atlantia-ink outline-none shadow-sm focus:border-atlantia-wine focus:ring-2 focus:ring-atlantia-blush">
                        <option value="">Cualquier tiempo</option>
                        <option value="30" @selected($filters['tiempo'] === 30)>Hasta 30 minutos</option>
                        <option value="45" @selected($filters['tiempo'] === 45)>Hasta 45 minutos</option>
                        <option value="60" @selected($filters['tiempo'] === 60)>Hasta 60 minutos</option>
                    </select>
                </label>

                <button class="flex h-10 items-center justify-center gap-2 rounded-lg bg-atlantia-wine px-6 text-xs font-black text-white shadow-[0_10px_22px_rgba(122,31,61,0.20)] transition hover:bg-atlantia-wine-700">
                    Buscar
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="m21 21-4.3-4.3M10.8 18a7.2 7.2 0 1 1 0-14.4 7.2 7.2 0 0 1 0 14.4Z" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
                </button>
            </form>

            <div class="mt-2.5 flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                <div class="flex min-w-0 gap-2 overflow-x-auto pb-1 [scrollbar-width:none] [&::-webkit-scrollbar]:hidden">
                    <a href="{{ route('comercios.index', request()->except(['page', 'categoria'])) }}" class="{{ $filters['categoria'] === null ? 'border-atlantia-rose/30 bg-atlantia-blush text-atlantia-wine' : 'border-atlantia-rose/15 bg-white text-atlantia-ink hover:bg-atlantia-blush' }} inline-flex h-9 shrink-0 items-center gap-2 rounded-full border px-4 text-[10px] font-black transition">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M4 10h16l-1-5H5l-1 5ZM6 10v10h12V10M9 20v-6h6v6" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round"/></svg>
                        Todos
                    </a>
                    @foreach ($categorias->take(5) as $categoria)
                        <a href="{{ route('comercios.index', array_merge(request()->except(['page', 'categoria']), ['categoria' => $categoria->id])) }}" class="{{ (int) $filters['categoria'] === $categoria->id ? 'border-atlantia-rose/30 bg-atlantia-blush text-atlantia-wine' : 'border-atlantia-rose/15 bg-white text-atlantia-ink hover:bg-atlantia-blush' }} inline-flex h-9 shrink-0 items-center gap-2 rounded-full border px-4 text-[10px] font-black transition">
                            <span class="grid h-5 w-5 place-items-center rounded-full bg-atlantia-blush text-atlantia-wine">
                                <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M5 5h14v14H5V5ZM8 9h8M8 13h5" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/></svg>
                            </span>
                            {{ $categoria->nombre }}
                        </a>
                    @endforeach
                    <a href="{{ route('comercios.index', request()->except(['page', 'categoria'])) }}" class="inline-flex h-9 shrink-0 items-center gap-2 rounded-full border border-atlantia-rose/15 bg-white px-4 text-[10px] font-black text-atlantia-wine transition hover:bg-atlantia-blush">
                        Ver todas
                        <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M4 4h6v6H4V4ZM14 4h6v6h-6V4ZM4 14h6v6H4v-6ZM14 14h6v6h-6v-6Z" stroke="currentColor" stroke-width="1.7"/></svg>
                    </a>
                </div>

                <div class="hidden shrink-0 items-center justify-end gap-2 lg:flex">
                    <form action="{{ route('comercios.index') }}" method="GET" class="flex items-center gap-1.5">
                        @foreach (['q', 'municipio', 'categoria', 'tiempo', 'vista'] as $filterName)
                            @if ($filters[$filterName] !== null && $filters[$filterName] !== '')
                                <input type="hidden" name="{{ $filterName }}" value="{{ $filters[$filterName] }}">
                            @endif
                        @endforeach
                        <label for="commerce-sort" class="sr-only">Ordenar comercios</label>
                        <select id="commerce-sort" name="orden" class="h-9 min-w-40 rounded-lg border border-atlantia-rose/15 bg-white px-3 text-[10px] font-black text-atlantia-ink outline-none shadow-sm focus:border-atlantia-wine focus:ring-2 focus:ring-atlantia-blush">
                            <option value="popularidad" @selected($filters['orden'] === 'popularidad')>Mas populares</option>
                            <option value="catalogo" @selected($filters['orden'] === 'catalogo')>Mayor catalogo</option>
                            <option value="recientes" @selected($filters['orden'] === 'recientes')>Mas recientes</option>
                            <option value="nombre" @selected($filters['orden'] === 'nombre')>Nombre A-Z</option>
                        </select>
                        <button class="grid h-9 w-9 place-items-center rounded-lg border border-atlantia-rose/15 bg-white text-atlantia-wine shadow-sm hover:bg-atlantia-blush" type="submit" title="Aplicar orden">
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M8 6h12M4 6h.01M8 12h12M4 12h.01M8 18h12M4 18h.01" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
                        </button>
                    </form>

                    <div class="flex overflow-hidden rounded-lg border border-atlantia-rose/15 bg-white shadow-sm" aria-label="Modo de visualizacion">
                        <a href="{{ route('comercios.index', array_merge(request()->except(['page', 'vista']), ['vista' => 'cuadricula'])) }}" class="{{ $filters['vista'] === 'cuadricula' ? 'bg-atlantia-wine text-white' : 'text-atlantia-wine hover:bg-atlantia-blush' }} grid h-9 w-9 place-items-center" aria-label="Vista en cuadricula" title="Vista en cuadricula">
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M4 4h6v6H4V4ZM14 4h6v6h-6V4ZM4 14h6v6H4v-6ZM14 14h6v6h-6v-6Z" stroke="currentColor" stroke-width="1.7"/></svg>
                        </a>
                        <a href="{{ route('comercios.index', array_merge(request()->except(['page', 'vista']), ['vista' => 'lista'])) }}" class="{{ $filters['vista'] === 'lista' ? 'bg-atlantia-wine text-white' : 'text-atlantia-wine hover:bg-atlantia-blush' }} grid h-9 w-9 place-items-center border-l border-atlantia-rose/15" aria-label="Vista en lista" title="Vista en lista">
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M9 6h11M9 12h11M9 18h11M4 6h.01M4 12h.01M4 18h.01" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
                        </a>
                    </div>
                </div>
            </div>
        </section>

        <section class="mx-auto w-full max-w-7xl px-4 pb-8 pt-2 sm:px-6 lg:px-8">
            <div class="mb-3 flex items-end justify-between gap-3">
                <div>
                    <h2 class="text-sm font-black text-atlantia-ink">Negocios disponibles</h2>
                    <p class="mt-0.5 text-[10px] font-semibold text-atlantia-ink/55">{{ number_format($vendors->total()) }} comercios con productos publicados.</p>
                </div>
                <a href="{{ route('catalogo.index') }}" class="text-[10px] font-black text-atlantia-wine hover:underline">Ver todos los productos</a>
            </div>

            @if ($vendors->isEmpty())
                <div class="grid min-h-64 place-items-center rounded-xl border border-dashed border-atlantia-rose/25 bg-white p-10 text-center">
                    <div>
                        <span class="mx-auto grid h-12 w-12 place-items-center rounded-full bg-atlantia-blush text-atlantia-wine">
                            <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="m21 21-4.3-4.3M10.8 18a7.2 7.2 0 1 1 0-14.4 7.2 7.2 0 0 1 0 14.4Z" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
                        </span>
                        <h3 class="mt-3 text-base font-black text-atlantia-ink">No encontramos comercios con esos filtros</h3>
                        <p class="mt-1 text-xs text-atlantia-ink/60">Prueba con otro municipio, categoria, tiempo o nombre de negocio.</p>
                        <a href="{{ route('comercios.index') }}" class="mt-4 inline-flex h-9 items-center rounded-lg bg-atlantia-wine px-4 text-xs font-black text-white">Limpiar filtros</a>
                    </div>
                </div>
            @else
                <div class="{{ $filters['vista'] === 'lista' ? 'grid grid-cols-2 gap-3 lg:grid-cols-2' : 'grid grid-cols-2 gap-3 lg:grid-cols-3 xl:grid-cols-4' }}">
                    @foreach ($vendors as $vendor)
                        @php
                            $rating = $ratings->get($vendor->id, ['rating' => null, 'total' => 0]);
                            $initials = str($vendor->business_name)->substr(0, 2)->upper()->toString();
                            $cover = $vendor->cover_url ?: asset('images/atlantia-marketplace-hero-v2.png');
                            $deliveryLabel = $vendor->tiempo_entrega_min
                                ? number_format((int) $vendor->tiempo_entrega_min).' min'
                                : 'Por confirmar';
                        @endphp
                        <article class="group min-w-0 overflow-hidden rounded-lg border border-atlantia-rose/20 bg-white shadow-[0_8px_22px_rgba(63,13,29,0.07)] transition hover:-translate-y-0.5 hover:shadow-[0_14px_30px_rgba(63,13,29,0.12)]">
                            <a href="{{ route('comercios.show', ['vendor' => $vendor->slug]) }}" class="block">
                                <div class="relative h-24 overflow-hidden bg-atlantia-blush">
                                    <img src="{{ $cover }}" alt="{{ $vendor->business_name }}" class="h-full w-full object-cover" loading="lazy">
                                    <div class="absolute inset-0 bg-gradient-to-t from-atlantia-wine-900/45 via-transparent to-transparent"></div>
                                    <span class="absolute left-2 top-2 rounded-full bg-white/95 px-2 py-0.5 text-[8px] font-bold text-atlantia-ink shadow-sm">{{ $vendor->business_category ?: 'Comercio local' }}</span>
                                    <span class="absolute right-2 top-2 h-3 w-3 rounded-full border-2 border-white bg-emerald-500 shadow" title="Comercio disponible"></span>
                                    <span class="absolute bottom-1.5 left-1/2 grid h-14 w-14 -translate-x-1/2 place-items-center overflow-hidden rounded-full border-2 border-white bg-white text-sm font-black text-atlantia-wine shadow-lg">
                                        @if ($vendor->logo_url)
                                            <img src="{{ $vendor->logo_url }}" alt="" class="h-full w-full object-cover" loading="lazy">
                                        @else
                                            {{ $initials }}
                                        @endif
                                    </span>
                                </div>
                            </a>

                            <div class="px-3 pb-2.5 pt-2">
                                <h3 class="truncate text-sm font-black text-atlantia-ink group-hover:text-atlantia-wine">{{ $vendor->business_name }}</h3>
                                <p class="mt-1 line-clamp-1 min-h-4 text-[9px] font-semibold text-atlantia-ink/55">{{ $vendor->descripcion ?: 'Productos disponibles para entrega por Atlantia.' }}</p>

                                <div class="mt-2 grid grid-cols-3 divide-x divide-atlantia-rose/15 border-y border-atlantia-rose/10 py-1.5 text-[8px] font-bold text-atlantia-ink/65">
                                    <span class="flex items-center gap-1 pr-2">
                                        <svg class="h-3.5 w-3.5 shrink-0 text-rose-500" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="m12 3 2.8 5.7 6.2.9-4.5 4.4 1.1 6.2L12 17.3 6.4 20.2 7.5 14 3 9.6l6.2-.9L12 3Z" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"/></svg>
                                        {{ $rating['rating'] ? number_format((float) $rating['rating'], 1) : 'Nuevo' }}
                                        @if ((int) $rating['total'] > 0)<span class="text-atlantia-ink/40">({{ number_format((int) $rating['total']) }})</span>@endif
                                    </span>
                                    <span class="flex items-center justify-center gap-1 px-2">
                                        <svg class="h-3.5 w-3.5 shrink-0 text-rose-500" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M12 7v5l3 2M20 12a8 8 0 1 1-16 0 8 8 0 0 1 16 0Z" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                        <span class="truncate">{{ $deliveryLabel }}</span>
                                    </span>
                                    <span class="flex items-center justify-end gap-1 pl-2">
                                        <svg class="h-3.5 w-3.5 shrink-0 text-rose-500" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M12 21s6-5.2 6-11a6 6 0 1 0-12 0c0 5.8 6 11 6 11Zm0-8.5a2.5 2.5 0 1 1 0-5 2.5 2.5 0 0 1 0 5Z" stroke="currentColor" stroke-width="1.6"/></svg>
                                        <span class="truncate">{{ $vendor->municipio ?: 'Atlantia' }}</span>
                                    </span>
                                </div>

                                <div class="mt-2 flex items-center justify-between gap-2">
                                    <span class="inline-flex items-center gap-1 text-[8px] font-black text-atlantia-ink/65">
                                        <svg class="h-3.5 w-3.5 text-rose-500" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M6 8h12l1 13H5L6 8ZM9 8a3 3 0 0 1 6 0" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round"/></svg>
                                        {{ number_format((int) $vendor->productos_publicados_count) }} productos
                                    </span>
                                    <a href="{{ route('comercios.show', ['vendor' => $vendor->slug]) }}" class="inline-flex h-7 items-center gap-2 rounded-full border border-atlantia-rose/25 bg-atlantia-blush/55 px-3 text-[8px] font-black text-atlantia-wine transition group-hover:bg-atlantia-wine group-hover:text-white">
                                        Ver tienda <span aria-hidden="true">&rarr;</span>
                                    </a>
                                </div>
                            </div>
                        </article>
                    @endforeach
                </div>

                <div class="mt-5 flex flex-col items-center justify-between gap-4 sm:flex-row">
                    @if ($vendors->hasMorePages())
                        <a href="{{ $vendors->nextPageUrl() }}" class="inline-flex h-9 items-center gap-3 rounded-full border border-atlantia-rose/30 bg-white px-10 text-[10px] font-black text-atlantia-wine shadow-sm hover:bg-atlantia-blush">
                            Cargar mas comercios <span aria-hidden="true">&#8964;</span>
                        </a>
                    @else
                        <span class="text-[10px] font-semibold text-atlantia-ink/45">Mostrando todos los comercios disponibles</span>
                    @endif

                    <div class="min-w-0">{{ $vendors->links() }}</div>
                </div>
            @endif
        </section>
    </div>
@endsection
