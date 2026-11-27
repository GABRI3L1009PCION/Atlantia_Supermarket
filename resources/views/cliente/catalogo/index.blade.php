@extends('layouts.marketplace')

@section('content')
    @php
        $selectedCategory = (int) ($filters['categoria_id'] ?? 0);
        $hasFilters = filled($filters['q'] ?? null) || $selectedCategory || ($filters['orden'] ?? 'relevancia') !== 'relevancia';
    @endphp
    <div class="storefront-home bg-white">
        <div class="mx-auto max-w-[1680px] px-4 pb-10 pt-5 sm:px-6 lg:px-8 lg:pt-7">
            <section class="relative" aria-label="Promociones de Atlantia" data-storefront-carousel>
                <div class="storefront-promotions flex snap-x snap-mandatory gap-4 overflow-x-auto rounded-xl [scrollbar-width:none] [&::-webkit-scrollbar]:hidden" data-storefront-track>
                    @foreach ($heroBanners as $banner)
                        <a href="{{ $banner['url'] ?? '#productos' }}" @if (! empty($banner['url'])) target="_blank" rel="noopener sponsored" @endif class="block min-w-0 shrink-0 basis-full snap-start overflow-hidden rounded-xl bg-atlantia-blush md:basis-[calc((100%-1rem)/2)] lg:basis-[calc((100%-2rem)/3)]">
                            <picture>
                                <source media="(max-width: 639px)" srcset="{{ $banner['mobile_image'] ?? $banner['desktop_image'] }}">
                                <img src="{{ $banner['desktop_image'] }}" alt="Banner promocional {{ $banner['name'] }}" class="aspect-[2.5/1] w-full object-cover" @if (!$loop->first) loading="lazy" @else fetchpriority="high" @endif>
                            </picture>
                        </a>
                    @endforeach
                </div>
                @if (count($heroBanners) > 1)
                    <div class="mt-3 flex items-center justify-center gap-3">
                        <button type="button" class="grid h-8 w-8 place-items-center rounded-full border border-amber-600/30 text-amber-700 hover:bg-amber-50" data-storefront-prev aria-label="Promoción anterior">&#8249;</button>
                        <span class="text-xs text-atlantia-ink/50" data-storefront-position aria-live="polite">Promociones</span>
                        <button type="button" class="grid h-8 w-8 place-items-center rounded-full border border-amber-600/30 text-amber-700 hover:bg-amber-50" data-storefront-next aria-label="Siguiente promoción">&#8250;</button>
                    </div>
                @endif
            </section>

            <section class="border-b border-atlantia-rose/15 pb-7 pt-7 sm:pb-9 sm:pt-9" aria-labelledby="storefront-categories-title">
                <h2 id="storefront-categories-title" class="text-xl font-extrabold text-atlantia-ink sm:text-2xl">Explora por Categoría</h2>
                {{-- Carrusel de categorias: flechas en computadora y tablet; en celular se desliza con el dedo. --}}
                <div class="relative mt-5" data-category-scroller>
                <button type="button" class="absolute left-0 top-10 z-10 hidden h-11 w-11 -translate-x-1/2 -translate-y-1/2 place-items-center rounded-full border border-atlantia-rose/25 bg-white text-atlantia-wine shadow-[0_6px_18px_rgba(59,6,12,0.18)] transition hover:bg-atlantia-wine hover:text-white sm:top-14 sm:grid disabled:pointer-events-none disabled:opacity-0" data-category-prev aria-label="Ver categorías anteriores">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="m15 5-7 7 7 7" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                </button>
                <button type="button" class="absolute right-0 top-10 z-10 hidden h-11 w-11 -translate-y-1/2 translate-x-1/2 place-items-center rounded-full border border-atlantia-rose/25 bg-white text-atlantia-wine shadow-[0_6px_18px_rgba(59,6,12,0.18)] transition hover:bg-atlantia-wine hover:text-white sm:top-14 sm:grid disabled:pointer-events-none disabled:opacity-0" data-category-next aria-label="Ver más categorías">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="m9 5 7 7-7 7" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                </button>
                <nav class="flex snap-x gap-5 overflow-x-auto scroll-smooth px-1 pb-2 [scrollbar-width:none] [&::-webkit-scrollbar]:hidden sm:gap-8" aria-label="Categorías de productos" data-category-track>
                    <a href="{{ route('catalogo.index', ['municipio' => $municipioActivo]) }}#productos" class="flex w-24 shrink-0 snap-start flex-col items-center gap-3 text-center sm:w-32" @if (!$selectedCategory) aria-current="page" @endif>
                        <span @class(['grid h-20 w-20 place-items-center rounded-full border-[3px] border-double border-atlantia-wine text-atlantia-wine sm:h-28 sm:w-28', 'bg-atlantia-blush/60' => !$selectedCategory])>
                            <svg class="h-10 w-10 sm:h-12 sm:w-12" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M12 3v18M3 12h18M5.6 5.6l12.8 12.8M5.6 18.4 18.4 5.6" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg>
                        </span>
                        <span class="text-[10px] font-black uppercase leading-4 tracking-wider text-atlantia-wine sm:text-xs">Todas las<br>categorías</span>
                    </a>
                    @foreach ($categoriasDestacadas as $categoria)
                        <a href="{{ $categoria['href'] }}" class="flex w-24 shrink-0 snap-start flex-col items-center gap-3 text-center sm:w-32" @if ($selectedCategory === $categoria['id']) aria-current="page" @endif>
                            <span @class(['grid h-20 w-20 place-items-center overflow-hidden rounded-full border-[3px] border-double border-atlantia-wine p-3 text-atlantia-wine sm:h-28 sm:w-28 sm:p-5', 'bg-atlantia-blush/60' => $selectedCategory === $categoria['id'], 'bg-white' => $selectedCategory !== $categoria['id']])>
                                @if ($categoria['image'])
                                    {{-- Las imagenes traen fondo gris claro opaco: se aclara a blanco y se funde con el circulo para que no se vea un recuadro. --}}
                                    <img src="{{ $categoria['image'] }}" alt="{{ $categoria['nombre'] }}" class="h-full w-full object-contain mix-blend-multiply brightness-[1.13] contrast-[1.04]" loading="lazy" width="112" height="112">
                                @else
                                    <svg class="h-9 w-9" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M4 7h16M7 4v16M17 4v16M4 17h16" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg>
                                @endif
                            </span>
                            <span class="line-clamp-2 text-[10px] font-black uppercase leading-4 tracking-wider text-atlantia-wine sm:text-xs">{{ $categoria['nombre'] }}</span>
                        </a>
                    @endforeach
                </nav>
                </div>
            </section>

            <section id="productos" class="scroll-mt-64 pt-7 sm:pt-10" aria-labelledby="storefront-products-title">
                <header>
                    <h1 id="storefront-products-title" class="text-2xl font-extrabold text-slate-950 sm:text-3xl">Productos disponibles</h1>
                    <p class="mt-1 max-w-xl text-sm leading-6 text-slate-500">Compra a tiendas locales verificadas de Puerto Barrios y Santo Tomás de Castilla.</p>
                </header>

                @if ($hasFilters)
                    <div class="mt-5 flex flex-wrap items-center justify-between gap-3 rounded-lg border border-atlantia-rose/15 px-4 py-3 text-xs">
                        <div class="flex flex-wrap gap-2 text-atlantia-wine">
                            @if (filled($filters['q'] ?? null))<span class="rounded bg-atlantia-blush px-2 py-1">Búsqueda: {{ $filters['q'] }}</span>@endif
                            @if ($selectedCategory)<span class="rounded bg-atlantia-blush px-2 py-1">{{ $categoriasDestacadas->firstWhere('id', $selectedCategory)['nombre'] ?? 'Categoría seleccionada' }}</span>@endif
                            @if (($filters['orden'] ?? 'relevancia') !== 'relevancia')<span class="rounded bg-sky-50 px-2 py-1 text-sky-800">Orden personalizado</span>@endif
                        </div>
                        <a href="{{ route('catalogo.index', ['municipio' => $municipioActivo]) }}#productos" class="font-semibold text-atlantia-wine hover:underline">Limpiar filtros</a>
                    </div>
                @endif

                <div class="mb-4 mt-4 flex items-center justify-between gap-3 border-b border-atlantia-rose/10 pb-3">
                    <p class="min-w-0 text-xs text-slate-500">{{ number_format($catalogo->total()) }} producto(s) · Entregas en {{ $zonaEntrega }}</p>
                    <form action="{{ route('catalogo.index') }}#productos" method="GET" class="flex shrink-0 items-center gap-2" data-storefront-filters>
                        @if ($municipioActivo)
                            <input type="hidden" name="municipio" value="{{ $municipioActivo }}">
                        @endif
                        @if (filled($filters['q'] ?? null))<input type="hidden" name="q" value="{{ $filters['q'] }}">@endif
                        @if ($selectedCategory)<input type="hidden" name="categoria_id" value="{{ $selectedCategory }}">@endif
                        <label for="orden-catalogo" class="hidden text-xs font-semibold text-slate-500 sm:inline">Ordenar por</label>
                        <span class="relative">
                            <svg class="pointer-events-none absolute left-2.5 top-1/2 h-3.5 w-3.5 -translate-y-1/2 text-atlantia-wine sm:hidden" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" aria-hidden="true"><path d="M7 4v16M3 16l4 4 4-4M17 20V4M13 8l4-4 4 4"/></svg>
                            <select id="orden-catalogo" name="orden" aria-label="Ordenar productos" class="h-9 w-auto max-w-[11rem] cursor-pointer appearance-none rounded-full border border-atlantia-rose/25 bg-white py-0 pl-7 pr-8 text-xs font-bold text-atlantia-ink outline-none focus:border-atlantia-wine focus:ring-2 focus:ring-atlantia-blush sm:pl-3.5">
                                @foreach (['relevancia' => 'Relevancia', 'precio_asc' => 'Precio menor', 'precio_desc' => 'Precio mayor', 'recientes' => 'Más recientes', 'nombre' => 'Nombre A–Z'] as $value => $label)
                                    <option value="{{ $value }}" @selected(($filters['orden'] ?? 'relevancia') === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                            <svg class="pointer-events-none absolute right-2.5 top-1/2 h-3.5 w-3.5 -translate-y-1/2 text-atlantia-wine" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m6 9 6 6 6-6"/></svg>
                        </span>
                        <noscript><button type="submit" class="h-9 rounded-full border border-atlantia-rose/25 px-3 text-xs font-bold text-atlantia-wine hover:bg-atlantia-blush">Aplicar</button></noscript>
                    </form>
                </div>
                @if (($patrocinados ?? collect())->isNotEmpty())
                    <div class="mb-5 rounded-2xl border border-amber-200 bg-amber-50/40 p-3" data-patrocinados>
                        <p class="mb-2 text-[11px] font-black uppercase tracking-wide text-amber-800">Patrocinado</p>
                        <div class="grid grid-cols-2 items-stretch gap-3 sm:grid-cols-3 sm:gap-4 lg:grid-cols-4 xl:grid-cols-5 wide:grid-cols-6">
                            @foreach ($patrocinados as $anuncio)
                                <x-storefront-product :producto="$anuncio['producto']" :saved="in_array($anuncio['producto']->id, $favoritos, true)" etiqueta="Patrocinado" :enlace="route('publicidad.patrocinado', $anuncio['servicio'])" />
                            @endforeach
                        </div>
                    </div>
                @endif
                @if ($catalogo->isEmpty())
                    <div class="rounded-xl border border-dashed border-atlantia-rose/30 bg-atlantia-cream px-6 py-12 text-center">
                        <h2 class="text-lg font-bold">No encontramos productos con esos filtros</h2>
                        <p class="mt-2 text-sm text-slate-500">Prueba otra búsqueda o categoría para continuar comprando.</p>
                        <a href="{{ route('catalogo.index', ['municipio' => $municipioActivo]) }}#productos" class="mt-4 inline-block text-sm font-bold text-atlantia-wine underline">Ver todos los productos</a>
                    </div>
                @else
                    @unless ($catalogo->onFirstPage())
                        <a href="{{ $catalogo->previousPageUrl() }}#productos" rel="prev" class="mb-4 inline-flex items-center gap-2 text-sm font-bold text-atlantia-wine hover:underline">&larr; Ver productos anteriores</a>
                    @endunless
                    <div class="grid grid-cols-2 items-stretch gap-3 sm:grid-cols-3 sm:gap-4 lg:grid-cols-4 xl:grid-cols-5 wide:grid-cols-6" data-storefront-products>
                        @foreach ($catalogo as $producto)
                            <x-storefront-product :producto="$producto" :saved="in_array($producto->id, $favoritos, true)" />
                        @endforeach
                    </div>

                    {{-- Carga progresiva: se cargan mas productos al bajar; despues de dos cargas automaticas se usa el boton. Sin JavaScript el boton abre la pagina siguiente. --}}
                    @php
                        $mostrados = (int) ($catalogo->lastItem() ?? 0);
                        $progreso = $catalogo->total() > 0 ? min(100, round($mostrados / $catalogo->total() * 100)) : 100;
                    @endphp
                    <div
                        class="mt-8 flex flex-col items-center gap-3 border-t border-atlantia-rose/15 pt-6 text-center"
                        data-load-more
                        data-next="{{ $catalogo->hasMorePages() ? $catalogo->nextPageUrl() : '' }}"
                        data-total="{{ $catalogo->total() }}"
                        data-shown="{{ $mostrados }}"
                        aria-live="polite"
                    >
                        <p class="text-sm font-semibold text-atlantia-ink/70" data-load-more-count>
                            Mostrando {{ number_format($mostrados) }} de {{ number_format($catalogo->total()) }} productos
                        </p>
                        <div class="h-1.5 w-56 overflow-hidden rounded-full bg-atlantia-blush" aria-hidden="true">
                            <div class="h-full rounded-full bg-atlantia-wine transition-all duration-500" style="width: {{ $progreso }}%" data-load-more-bar></div>
                        </div>
                        @if ($catalogo->hasMorePages())
                            <a
                                href="{{ $catalogo->nextPageUrl() }}#productos"
                                rel="next"
                                class="mt-1 inline-flex min-h-11 items-center justify-center rounded-full border-2 border-atlantia-wine px-8 text-sm font-black text-atlantia-wine transition hover:bg-atlantia-wine hover:text-white"
                                data-load-more-button
                            >
                                Ver más productos
                            </a>
                        @else
                            <p class="text-xs text-atlantia-ink/50">Ya viste todos los productos.</p>
                        @endif
                        <div class="h-px w-full" data-load-more-sentinel aria-hidden="true"></div>
                    </div>
                @endif
            </section>
        </div>
    </div>
@endsection
