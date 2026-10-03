@extends('layouts.marketplace')

@section('content')
    @php
        $heroCards = collect($heroBanners)->values();
        $heroCard = $heroCards->first();
        $categories = collect($categoriasDestacadas)->take(8)->values();
        $money = static fn ($value): string => 'Q ' . number_format((float) $value, 2);
        $mobileCategories = $categories->take(5)->values();
        $deliveryHeroImage = file_exists(public_path('images/atlantia-delivery-hero.png'))
            ? asset('images/atlantia-delivery-hero.png')
            : null;
        $staticDesktopHeroImage = file_exists(public_path('images/atlantia-marketplace-hero-v2.png'))
            ? asset('images/atlantia-marketplace-hero-v2.png')
            : null;
        $desktopHeroImage = $heroCard['desktop_image'] ?? $staticDesktopHeroImage ?? $deliveryHeroImage ?? asset('images/fondo.png');
        $mobileHeroImage = $heroCard['mobile_image'] ?? $heroCard['desktop_image'] ?? $deliveryHeroImage ?? $desktopHeroImage;
        $heroAlt = 'Banner promocional ' . ($heroCard['name'] ?? 'Fallback Atlantia');
    @endphp

    <div class="bg-white lg:hidden">
        <div class="mx-auto w-full max-w-3xl px-4 pb-12 pt-4 sm:px-6">
            <section class="relative min-h-32 overflow-hidden rounded-lg border border-atlantia-rose/15 bg-atlantia-blush shadow-sm sm:min-h-44">
                <picture class="absolute inset-0">
                    <img src="{{ $mobileHeroImage }}" alt="{{ $heroAlt }}" class="h-full w-full object-cover object-center md:hidden">
                    <img src="{{ $desktopHeroImage }}" alt="{{ $heroAlt }}" class="hidden h-full w-full object-cover object-center md:block">
                </picture>
                <div class="hidden absolute inset-0 bg-gradient-to-r from-white/95 via-white/72 to-transparent"></div>
                <div class="hidden absolute inset-0 flex-col justify-between p-5">
                    <div>
                        <span class="inline-flex items-center gap-2 rounded-lg bg-white/90 px-3 py-2 text-xs font-black uppercase text-atlantia-wine shadow-sm">
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                <path d="M4 16h9l3-6H7l-3 6ZM7 19a2 2 0 1 0 0-4 2 2 0 0 0 0 4ZM18 19a2 2 0 1 0 0-4 2 2 0 0 0 0 4Z" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                            Delivery Atlantia
                        </span>
                        <h1 class="mt-5 max-w-[17rem] text-3xl font-black leading-tight text-atlantia-ink">
                            Lo que necesitas, <span class="text-atlantia-wine">donde lo necesitas</span>
                        </h1>
                        <p class="mt-4 max-w-[16rem] text-base font-semibold leading-6 text-atlantia-ink/70">
                            Compra en comercios locales verificados y recibe tus productos en la puerta de tu casa.
                        </p>
                    </div>
                    <div class="flex justify-center gap-2">
                        <span class="h-2.5 w-2.5 rounded-full bg-atlantia-wine"></span>
                        <span class="h-2.5 w-2.5 rounded-full bg-white/80"></span>
                        <span class="h-2.5 w-2.5 rounded-full bg-white/80"></span>
                    </div>
                </div>
            </section>

            <div class="mt-3 flex justify-center gap-3" aria-label="Posicion del carrusel">
                <span class="h-2.5 w-2.5 rounded-full bg-atlantia-wine"></span>
                <span class="h-2.5 w-2.5 rounded-full bg-slate-200"></span>
                <span class="h-2.5 w-2.5 rounded-full bg-slate-200"></span>
                <span class="h-2.5 w-2.5 rounded-full bg-slate-200"></span>
            </div>

            <section class="hidden mt-4 grid-cols-3 gap-3">
                @foreach ([
                    ['title' => 'Entrega rapida', 'text' => '30-60 min', 'icon' => 'ride'],
                    ['title' => 'Comercios verificados', 'text' => 'Locales aprobados', 'icon' => 'star'],
                    ['title' => 'Pago seguro', 'text' => '100% protegido', 'icon' => 'pay'],
                ] as $feature)
                    <article class="min-h-24 rounded-2xl border border-atlantia-rose/15 bg-white p-3 shadow-sm">
                        <span class="grid h-10 w-10 place-items-center rounded-full bg-atlantia-blush text-atlantia-wine">
                            @if ($feature['icon'] === 'ride')
                                <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M4 16h9l3-6H7l-3 6ZM7 19a2 2 0 1 0 0-4 2 2 0 0 0 0 4ZM18 19a2 2 0 1 0 0-4 2 2 0 0 0 0 4Z" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/></svg>
                            @elseif ($feature['icon'] === 'star')
                                <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="m12 3 2.8 5.7 6.2.9-4.5 4.4 1.1 6.2L12 17.3 6.4 20.2 7.5 14 3 9.6l6.2-.9L12 3Z" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"/></svg>
                            @else
                                <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M6 10V8a6 6 0 0 1 12 0v2M5 10h14v10H5V10ZM9 15l2 2 4-5" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/></svg>
                            @endif
                        </span>
                        <h2 class="mt-2 text-sm font-black leading-4 text-atlantia-ink">{{ $feature['title'] }}</h2>
                        <p class="mt-1 text-xs font-semibold leading-4 text-atlantia-ink/60">{{ $feature['text'] }}</p>
                    </article>
                @endforeach
            </section>

            <form action="{{ route('comercios.index') }}" method="GET" class="hidden relative mt-5">
                <input type="hidden" name="municipio" value="{{ $municipioActivo }}">
                <svg class="pointer-events-none absolute left-4 top-1/2 h-6 w-6 -translate-y-1/2 text-atlantia-wine" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                    <path d="m21 21-4.3-4.3M10.8 18a7.2 7.2 0 1 1 0-14.4 7.2 7.2 0 0 1 0 14.4Z" stroke="currentColor" stroke-width="1.9" stroke-linecap="round"/>
                </svg>
                <input
                    type="search"
                    name="q"
                    value="{{ request('q') }}"
                    placeholder="Buscar productos, tiendas o categorias..."
                    class="h-16 w-full rounded-2xl border border-atlantia-rose/15 bg-white px-14 text-sm font-semibold text-atlantia-ink outline-none shadow-sm placeholder:text-atlantia-ink/45 focus:border-atlantia-wine focus:ring-2 focus:ring-atlantia-blush"
                >
            </form>

            <section class="mt-7">
                <div class="flex items-center justify-between gap-4">
                    <h2 class="text-xl font-black text-atlantia-ink sm:text-2xl">Explora por Categoria</h2>
                    <a href="{{ route('categorias.index') }}" class="inline-flex shrink-0 items-center gap-1 text-sm font-black text-atlantia-wine">
                        Ver todas
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="m9 5 7 7-7 7" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    </a>
                </div>

                <nav class="mt-4 flex gap-4 overflow-x-auto pb-2 [scrollbar-width:none] [&::-webkit-scrollbar]:hidden" aria-label="Categorias">
                    <a href="{{ route('comercios.index', ['municipio' => $municipioActivo]) }}" class="flex w-24 shrink-0 flex-col items-center gap-2 text-center">
                        <span class="grid h-20 w-20 place-items-center rounded-full border-2 border-atlantia-wine bg-white text-atlantia-wine shadow-sm sm:h-24 sm:w-24">
                            <svg class="h-9 w-9" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M4 4h6v6H4V4Zm10 0h6v6h-6V4ZM4 14h6v6H4v-6Zm10 0h6v6h-6v-6Z" stroke="currentColor" stroke-width="1.7"/></svg>
                        </span>
                        <span class="text-xs font-black leading-4 text-atlantia-ink">Todas las categorias</span>
                    </a>
                    @foreach ($categories as $categoria)
                        <a href="{{ $categoria['href'] }}" class="flex w-24 shrink-0 flex-col items-center gap-2 text-center">
                            <span class="grid h-20 w-20 place-items-center overflow-hidden rounded-full border-2 border-atlantia-wine bg-white p-3 text-atlantia-wine shadow-sm sm:h-24 sm:w-24">
                                @if ($categoria['image'])
                                    <img src="{{ $categoria['image'] }}" alt="{{ $categoria['nombre'] }}" class="h-full w-full object-contain" loading="lazy">
                                @else
                                    <svg class="h-9 w-9" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M4 7h16M7 4v16M17 4v16M4 17h16" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/></svg>
                                @endif
                            </span>
                            <span class="line-clamp-2 text-xs font-black leading-4 text-atlantia-ink">{{ $categoria['nombre'] }}</span>
                        </a>
                    @endforeach
                </nav>
            </section>

            <section class="mt-6 border-t border-atlantia-rose/10 pt-5">
                <div class="mb-4 flex items-center justify-between gap-3">
                    <div>
                        <h2 class="text-xl font-black text-atlantia-ink sm:text-2xl">Comercios disponibles</h2>
                        <p class="mt-1 text-xs font-semibold leading-5 text-atlantia-ink/55">Comercios locales verificados en {{ $municipioActivo }}.</p>
                    </div>
                    <a href="{{ route('comercios.index', ['municipio' => $municipioActivo]) }}" class="inline-flex items-center gap-1 text-sm font-black text-atlantia-wine">
                        Ver todos
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                            <path d="M5 12h14m-6-6 6 6-6 6" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </a>
                </div>

                <div class="mb-3 grid grid-cols-[1fr_1fr_1.15fr] gap-2">
                    <a href="{{ route('comercios.index', ['municipio' => $municipioActivo]) }}" class="flex h-10 items-center justify-center gap-1 rounded-lg border border-atlantia-rose/15 bg-white text-xs font-black text-atlantia-ink/65 shadow-sm">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M4 7h10M18 7h2M4 12h3M11 12h9M4 17h7M15 17h5" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/></svg>
                        Filtros
                    </a>
                    <a href="{{ route('comercios.index', ['municipio' => $municipioActivo, 'orden' => 'nombre']) }}" class="flex h-10 items-center justify-center gap-1 rounded-lg border border-atlantia-rose/15 bg-white text-xs font-black text-atlantia-ink/65 shadow-sm">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="m8 5-3 3-3-3M5 8V3m11 16 3-3 3 3m-3-3v5M10 7h8M10 12h8M5 17h7" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/></svg>
                        Ordenar
                    </a>
                    <a href="{{ route('comercios.index', ['municipio' => $municipioActivo, 'orden' => 'popularidad']) }}" class="flex h-10 items-center justify-between rounded-lg border border-atlantia-rose/15 bg-white px-3 text-xs font-black text-atlantia-ink/65 shadow-sm">
                        Relevancia
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="m6 9 6 6 6-6" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    </a>
                </div>

                @if ($vendorsDestacados->isEmpty())
                    <div class="rounded-2xl border border-dashed border-atlantia-rose/25 bg-atlantia-blush/40 p-6 text-center">
                        <h3 class="font-black text-atlantia-ink">Aun no hay comercios publicados</h3>
                        <p class="mt-1 text-sm text-atlantia-ink/60">Cuando apruebes negocios con productos visibles apareceran aqui.</p>
                    </div>
                @else
                    <div class="grid grid-cols-2 gap-3 sm:grid-cols-3">
                        @foreach ($vendorsDestacados->take(6) as $vendor)
                            @php
                                $rating = $ratingsDestacados->get($vendor->id, ['rating' => null, 'total' => 0]);
                                $cover = $vendor->cover_url ?: asset('images/fondo.png');
                                $category = $vendor->business_category ?: 'Comercio';
                                $deliveryLabel = $vendor->tiempo_entrega_min
                                    ? number_format((int) $vendor->tiempo_entrega_min).' min'
                                    : 'Por confirmar';
                            @endphp
                            <article class="min-w-0 overflow-hidden rounded-lg border border-atlantia-rose/15 bg-white shadow-sm">
                                <a href="{{ route('comercios.show', ['vendor' => $vendor->slug]) }}" class="block">
                                    <div class="relative h-24 overflow-hidden bg-atlantia-blush sm:h-32">
                                        <img src="{{ $cover }}" alt="{{ $vendor->business_name }}" class="h-full w-full object-cover" loading="lazy">
                                        <span class="absolute right-2 top-2 grid h-9 w-9 place-items-center rounded-full bg-white text-atlantia-wine shadow-sm">
                                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                                <path d="M12 20s-7-4.4-7-10a4.2 4.2 0 0 1 7-3.1A4.2 4.2 0 0 1 19 10c0 5.6-7 10-7 10Z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/>
                                            </svg>
                                        </span>
                                        <span class="absolute bottom-2 left-2 grid h-12 w-12 place-items-center overflow-hidden rounded-full border-2 border-white bg-white text-xs font-black text-atlantia-wine shadow">
                                            @if ($vendor->logo_url)
                                                <img src="{{ $vendor->logo_url }}" alt="" class="h-full w-full object-contain" loading="lazy">
                                            @else
                                                {{ str($vendor->business_name)->substr(0, 2)->upper() }}
                                            @endif
                                        </span>
                                    </div>
                                    <div class="p-3">
                                        <h3 class="truncate text-sm font-black leading-5 text-atlantia-ink sm:text-base">{{ $vendor->business_name }}</h3>
                                        <p class="mt-0.5 truncate text-xs font-semibold text-atlantia-ink/50">{{ $category }}</p>
                                        <p class="mt-2 flex items-center gap-2 text-[10px] font-bold text-atlantia-ink/55 sm:text-xs">
                                            <span>{{ $deliveryLabel }}</span>
                                            <span class="text-atlantia-rose">|</span>
                                            <span class="truncate">{{ $vendor->municipio ?: $municipioActivo }}</span>
                                        </p>
                                    </div>
                                </a>
                            </article>
                        @endforeach
                    </div>
                @endif
            </section>

            <section class="hidden mt-6 gap-4 rounded-2xl bg-atlantia-blush p-4">
                <div class="flex items-center gap-4">
                    <span class="grid h-16 w-16 shrink-0 place-items-center rounded-full bg-atlantia-wine text-white">
                        <svg class="h-8 w-8" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                            <path d="M4 16h9l3-6H7l-3 6ZM7 19a2 2 0 1 0 0-4 2 2 0 0 0 0 4ZM18 19a2 2 0 1 0 0-4 2 2 0 0 0 0 4Z" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </span>
                    <div>
                        <h2 class="text-xl font-black text-atlantia-wine">Envio rapido y seguro!</h2>
                        <p class="mt-1 text-sm leading-5 text-atlantia-ink/70">Recibe tus productos en la puerta de tu casa de 30 a 60 minutos.</p>
                    </div>
                </div>
                <div class="grid grid-cols-4 gap-2 rounded-xl bg-white p-3 text-center shadow-sm">
                    @foreach (['Elige tus productos', 'Confirmamos tu pedido', 'En camino', 'Listo! A disfrutar'] as $step)
                        <div>
                            <span class="mx-auto grid h-10 w-10 place-items-center rounded-full bg-atlantia-blush text-atlantia-wine">
                                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M6 8h12l1 13H5L6 8ZM9 8a3 3 0 0 1 6 0" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round"/></svg>
                            </span>
                            <p class="mt-2 text-[11px] font-bold leading-4 text-atlantia-ink">{{ $step }}</p>
                        </div>
                    @endforeach
                </div>
            </section>
        </div>

        @if ($cartItemsCount > 0)
            <aside class="fixed inset-x-4 bottom-[5.75rem] z-40 mx-auto max-w-md rounded-2xl bg-atlantia-wine p-4 text-white shadow-[0_18px_45px_rgba(42,16,24,0.32)]">
                <div class="grid grid-cols-[auto_1fr_auto] items-center gap-3">
                    <span class="grid h-12 w-12 place-items-center rounded-full bg-white text-atlantia-wine">
                        <svg class="h-7 w-7" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                            <path d="M6 8h12l1 13H5L6 8ZM9 8a3 3 0 0 1 6 0" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round"/>
                        </svg>
                    </span>
                    <div>
                        <p class="text-sm font-black">{{ number_format($cartItemsCount) }} productos - {{ $money($cartSubtotal) }}</p>
                        <p class="mt-1 text-xs font-semibold text-white/75">Envio gratis desde Q100</p>
                    </div>
                    <a href="{{ route('cliente.carrito.index') }}" class="inline-flex h-12 items-center gap-2 rounded-xl bg-white px-4 text-sm font-black text-atlantia-wine">
                        Ver carrito
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M5 12h14m-6-6 6 6-6 6" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    </a>
                </div>
            </aside>
        @endif

        <nav class="hidden fixed inset-x-0 bottom-0 z-40 border-t border-atlantia-rose/10 bg-white px-4 pb-safe shadow-[0_-12px_30px_rgba(42,16,24,0.08)]" aria-label="Navegacion inferior">
            <div class="mx-auto grid h-20 max-w-md grid-cols-5 items-center text-center text-xs font-semibold text-atlantia-ink/70">
                @foreach ([
                    ['label' => 'Inicio', 'href' => route('home'), 'active' => true, 'icon' => 'home'],
                    ['label' => 'Explorar', 'href' => route('comercios.index', ['municipio' => $municipioActivo]), 'active' => false, 'icon' => 'search'],
                    ['label' => 'Pedidos', 'href' => route('cliente.pedidos.index'), 'active' => false, 'icon' => 'bag'],
                    ['label' => 'Favoritos', 'href' => route('cliente.wishlist.index'), 'active' => false, 'icon' => 'heart'],
                    ['label' => 'Cuenta', 'href' => auth()->check() ? route('cliente.perfil.edit') : route('login'), 'active' => false, 'icon' => 'user'],
                ] as $item)
                    <a href="{{ $item['href'] }}" class="{{ $item['active'] ? 'text-atlantia-wine' : 'text-atlantia-ink/70' }} flex flex-col items-center gap-1">
                        <span class="grid h-8 w-8 place-items-center">
                            @if ($item['icon'] === 'home')
                                <svg class="h-7 w-7" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M4 10.5 12 4l8 6.5V20a1 1 0 0 1-1 1h-5v-6h-4v6H5a1 1 0 0 1-1-1v-9.5Z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/></svg>
                            @elseif ($item['icon'] === 'search')
                                <svg class="h-7 w-7" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="m21 21-4.3-4.3M10.8 18a7.2 7.2 0 1 1 0-14.4 7.2 7.2 0 0 1 0 14.4Z" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
                            @elseif ($item['icon'] === 'bag')
                                <svg class="h-7 w-7" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M6 8h12l1 13H5L6 8ZM9 8a3 3 0 0 1 6 0" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/></svg>
                            @elseif ($item['icon'] === 'heart')
                                <svg class="h-7 w-7" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M12 20s-7-4.4-7-10a4.2 4.2 0 0 1 7-3.1A4.2 4.2 0 0 1 19 10c0 5.6-7 10-7 10Z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/></svg>
                            @else
                                <svg class="h-7 w-7" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M12 12a4 4 0 1 0 0-8 4 4 0 0 0 0 8ZM4 21a8 8 0 0 1 16 0" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
                            @endif
                        </span>
                        {{ $item['label'] }}
                    </a>
                @endforeach
            </div>
        </nav>
    </div>

    <div class="hidden bg-[#fff9fb] lg:block">
        <div class="mx-auto w-full max-w-7xl px-4 py-3 xl:px-6">
            <section class="relative h-[14rem] overflow-hidden rounded-xl border border-atlantia-rose/20 bg-atlantia-wine-900 shadow-[0_14px_36px_rgba(63,13,29,0.12)]">
                <picture class="absolute inset-0">
                    <img src="{{ $mobileHeroImage }}" alt="{{ $heroAlt }}" class="h-full w-full object-cover object-center md:hidden">
                    <img src="{{ $desktopHeroImage }}" alt="{{ $heroAlt }}" class="hidden h-full w-full object-cover object-center md:block">
                </picture>
                <div class="absolute inset-0 bg-[linear-gradient(90deg,rgba(48,5,19,0.94)_0%,rgba(65,8,27,0.84)_38%,rgba(72,12,30,0.30)_70%,rgba(72,12,30,0.00)_100%)]"></div>

                <div class="relative z-10 grid h-full grid-cols-[40%_45%_15%]">
                    <div class="col-start-1 flex h-full min-w-0 flex-col justify-between py-5 pl-8 pr-4 xl:pl-10">
                        <div>
                            <span class="inline-flex h-6 items-center gap-2 rounded-full border border-white/20 bg-white/10 px-3 text-[9px] font-black uppercase text-white backdrop-blur-sm">
                                <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M4 16h9l3-6H7l-3 6ZM7 19a2 2 0 1 0 0-4 2 2 0 0 0 0 4ZM18 19a2 2 0 1 0 0-4 2 2 0 0 0 0 4Z" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                Delivery Atlantia
                            </span>
                            <h1 class="mt-2 text-[2rem] font-black leading-none text-white xl:text-[2.35rem]">
                                Lo que necesitas,
                                <span class="block text-[#f4a7c5]">donde lo necesitas</span>
                            </h1>
                            <p class="mt-2 max-w-[22rem] text-[11px] font-semibold leading-4 text-white/90 xl:text-xs">
                                Compra en comercios locales verificados y recibe tus productos en la puerta de tu casa.
                            </p>
                        </div>

                        <div class="grid max-w-[27rem] grid-cols-3 overflow-hidden rounded-lg border border-white/25 bg-white/95 shadow-lg">
                            @foreach ([
                                ['title' => 'Entrega rapida', 'text' => 'Servicio local', 'icon' => 'truck'],
                                ['title' => 'Comercios verificados', 'text' => 'Locales aprobados', 'icon' => 'star'],
                                ['title' => 'Pago seguro', 'text' => 'Opciones protegidas', 'icon' => 'lock'],
                            ] as $benefit)
                                <div class="{{ ! $loop->first ? 'border-l border-atlantia-rose/20' : '' }} min-w-0 px-3 py-2">
                                    <p class="flex items-center gap-1.5 text-[10px] font-black leading-3 text-atlantia-ink">
                                        @if ($benefit['icon'] === 'truck')
                                            <svg class="h-3.5 w-3.5 shrink-0 text-atlantia-wine" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M4 16h9l3-6H7l-3 6ZM7 19a2 2 0 1 0 0-4 2 2 0 0 0 0 4ZM18 19a2 2 0 1 0 0-4 2 2 0 0 0 0 4Z" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                        @elseif ($benefit['icon'] === 'star')
                                            <svg class="h-3.5 w-3.5 shrink-0 text-atlantia-wine" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="m12 3 2.8 5.7 6.2.9-4.5 4.4 1.1 6.2L12 17.3 6.4 20.2 7.5 14 3 9.6l6.2-.9L12 3Z" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"/></svg>
                                        @else
                                            <svg class="h-3.5 w-3.5 shrink-0 text-atlantia-wine" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M7 10V8a5 5 0 0 1 10 0v2M5 10h14v10H5V10Z" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round"/></svg>
                                        @endif
                                        {{ $benefit['title'] }}
                                    </p>
                                    <p class="mt-1 truncate text-[9px] font-semibold text-atlantia-ink/55">{{ $benefit['text'] }}</p>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <aside class="col-start-2 my-auto w-full rounded-2xl border border-atlantia-rose/30 bg-white/95 p-4 shadow-[0_18px_45px_rgba(63,13,29,0.18)] backdrop-blur-md">
                        <h2 class="flex items-center gap-2 text-lg font-black text-atlantia-ink">
                            <svg class="h-5 w-5 text-rose-500" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M12 2v4M12 18v4M4.9 4.9l2.8 2.8M16.3 16.3l2.8 2.8M2 12h4M18 12h4M4.9 19.1l2.8-2.8M16.3 7.7l2.8-2.8" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
                            Que quieres pedir hoy?
                        </h2>

                        <form action="{{ route('comercios.index') }}" method="GET" class="mt-3 space-y-3">
                            <div class="grid grid-cols-3 gap-3">
                                <label class="block text-[9px] font-bold text-atlantia-ink/60">
                                    Buscar
                                    <div class="relative mt-1">
                                        <input type="search" name="q" value="{{ request('q') }}" placeholder="Buscar en Atlantia..." class="h-10 w-full rounded-lg border border-atlantia-rose/20 bg-white px-3 pr-8 text-[10px] font-semibold text-atlantia-ink outline-none shadow-sm placeholder:text-atlantia-ink/40 focus:border-atlantia-wine focus:ring-2 focus:ring-atlantia-blush">
                                        <svg class="absolute right-2.5 top-1/2 h-4 w-4 -translate-y-1/2 text-atlantia-wine" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="m21 21-4.3-4.3M10.8 18a7.2 7.2 0 1 1 0-14.4 7.2 7.2 0 0 1 0 14.4Z" stroke="currentColor" stroke-width="1.9" stroke-linecap="round"/></svg>
                                    </div>
                                </label>

                                <label class="block text-[9px] font-bold text-atlantia-ink/60">
                                    Municipio
                                    <select name="municipio" class="mt-1 h-10 w-full rounded-lg border border-atlantia-rose/20 bg-white px-3 text-[10px] font-semibold text-atlantia-ink outline-none shadow-sm focus:border-atlantia-wine focus:ring-2 focus:ring-atlantia-blush">
                                        <option value="">Todos los municipios</option>
                                        @foreach (['Puerto Barrios', 'Santo Tomas', 'Morales', 'Los Amates', 'Livingston', 'El Estor'] as $municipio)
                                            <option value="{{ $municipio }}" @selected($municipioActivo === $municipio)>{{ $municipio }}</option>
                                        @endforeach
                                    </select>
                                </label>

                                <label class="block text-[9px] font-bold text-atlantia-ink/60">
                                    Categoria
                                    <select name="categoria" class="mt-1 h-10 w-full rounded-lg border border-atlantia-rose/20 bg-white px-3 text-[10px] font-semibold text-atlantia-ink outline-none shadow-sm focus:border-atlantia-wine focus:ring-2 focus:ring-atlantia-blush">
                                        <option value="">Todas las categorias</option>
                                        @foreach ($categories as $categoria)
                                            <option value="{{ $categoria['id'] }}">{{ $categoria['nombre'] }}</option>
                                        @endforeach
                                    </select>
                                </label>
                            </div>

                            <button class="flex h-10 w-full items-center justify-center gap-3 rounded-lg bg-atlantia-wine px-5 text-xs font-black text-white shadow-[0_10px_24px_rgba(122,31,61,0.22)] transition hover:bg-atlantia-wine-700">
                                Buscar comercios
                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M5 12h14m-6-6 6 6-6 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                            </button>
                        </form>
                    </aside>
                </div>
            </section>

            <section id="categorias" class="mt-2.5 border-y border-atlantia-rose/15 bg-white px-4 py-2.5">
                <div class="mb-2 flex items-center justify-between gap-3">
                    <h2 class="flex items-center gap-2 text-sm font-black text-atlantia-ink">
                        <svg class="h-4 w-4 text-rose-500" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M12 2v4M12 18v4M4.9 4.9l2.8 2.8M16.3 16.3l2.8 2.8M2 12h4M18 12h4" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
                        Explora por categoria
                    </h2>
                    <a href="{{ route('comercios.index', ['municipio' => $municipioActivo]) }}" class="inline-flex h-6 items-center gap-1 rounded-full border border-atlantia-rose/20 px-3 text-[9px] font-black text-atlantia-wine hover:bg-atlantia-blush">Ver todas <span aria-hidden="true">&gt;</span></a>
                </div>

                <div class="grid grid-cols-9 gap-2">
                    <a href="{{ route('comercios.index', ['municipio' => $municipioActivo]) }}" class="flex h-11 items-center justify-center gap-2 rounded-lg bg-atlantia-wine px-2 text-white shadow-[0_8px_20px_rgba(122,31,61,0.18)]">
                        <svg class="h-5 w-5 shrink-0" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M4 4h6v6H4V4ZM14 4h6v6h-6V4ZM4 14h6v6H4v-6ZM14 14h6v6h-6v-6Z" stroke="currentColor" stroke-width="1.7"/></svg>
                        <span class="text-[10px] font-black">Todas</span>
                    </a>

                    @foreach ($categories->take(8) as $categoria)
                        @php
                            $categoryKey = str($categoria['nombre'])->ascii()->lower()->toString();
                        @endphp
                        <a href="{{ $categoria['href'] }}" class="group flex h-11 min-w-0 items-center justify-center gap-2 px-1 text-atlantia-ink transition hover:text-atlantia-wine">
                            <span class="grid h-9 w-9 shrink-0 place-items-center rounded-full border border-atlantia-rose/20 bg-white text-rose-500 shadow-sm group-hover:bg-atlantia-blush">
                                @if (str_contains($categoryKey, 'bebida'))
                                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M7 3h3v5l1 2v10H6V10l1-2V3ZM15 5h3v4l1 2v9h-5v-9l1-2V5Z" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"/></svg>
                                @elseif (str_contains($categoryKey, 'bebe'))
                                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M5 5h3l2 10h8l2-7H9M11 19a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0ZM19 19a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0Z" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                @elseif (str_contains($categoryKey, 'carne'))
                                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M4 14c0-5 5-9 10-9 4 0 6 2 6 5 0 5-5 9-10 9-4 0-6-2-6-5Zm8-4c2-1 4 0 4 2s-2 3-4 2-2-3 0-4Z" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"/></svg>
                                @elseif (str_contains($categoryKey, 'pesc'))
                                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M4 12c3-4 7-6 12-4l4-3v14l-4-3c-5 2-9 0-12-4Zm4 0h.01" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                @elseif (str_contains($categoryKey, 'congel'))
                                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M12 2v20M4 7l16 10M4 17 20 7M9 4l3 3 3-3M9 20l3-3 3 3" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                @elseif (str_contains($categoryKey, 'cuidado') || str_contains($categoryKey, 'higiene'))
                                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M6 4h4v4l1 2v10H5V10l1-2V4ZM15 3h3v5l1 2v10h-5V10l1-2V3Z" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"/></svg>
                                @elseif (str_contains($categoryKey, 'desay'))
                                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M4 7h13v6a6 6 0 0 1-6 6H10a6 6 0 0 1-6-6V7Zm13 2h2a2 2 0 0 1 0 4h-2M6 3h9" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                @else
                                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M6 3h12l2 5-2 13H6L4 8l2-5ZM4 8h16M9 12v5M15 12v5" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"/></svg>
                                @endif
                            </span>
                            <span class="line-clamp-2 text-[9px] font-black leading-3">{{ $categoria['nombre'] }}</span>
                        </a>
                    @endforeach
                </div>
            </section>

            <section id="comercios" class="mt-2.5 border-y border-atlantia-rose/15 bg-white px-4 py-2.5">
                <div class="mb-2 flex items-end justify-between gap-3">
                    <div>
                        <h2 class="flex items-center gap-2 text-sm font-black text-atlantia-ink">Comercios cerca de ti <span class="text-rose-500" aria-hidden="true">&#9671;</span></h2>
                        <p class="mt-0.5 text-[9px] font-semibold text-atlantia-ink/55">Tiendas y restaurantes locales disponibles en {{ $municipioActivo }}.</p>
                    </div>
                    <a href="{{ route('comercios.index', ['municipio' => $municipioActivo]) }}" class="inline-flex h-6 items-center gap-1 rounded-full border border-atlantia-rose/20 px-3 text-[9px] font-black text-atlantia-wine hover:bg-atlantia-blush">Ver todos los comercios <span aria-hidden="true">&gt;</span></a>
                </div>

                @if ($vendorsDestacados->isEmpty())
                    <div class="grid h-[10rem] place-items-center rounded-lg border border-dashed border-atlantia-rose/25 bg-atlantia-blush/40 text-center">
                        <div>
                            <h3 class="text-sm font-black text-atlantia-ink">Aun no hay comercios publicados</h3>
                            <p class="mt-1 text-xs text-atlantia-ink/60">Los comercios aprobados con productos visibles apareceran aqui.</p>
                        </div>
                    </div>
                @else
                    <div class="grid grid-cols-5 gap-3">
                        @foreach ($vendorsDestacados->take(5) as $vendor)
                            @php
                                $rating = $ratingsDestacados->get($vendor->id, ['rating' => null, 'total' => 0]);
                                $cover = $vendor->cover_url ?: asset('images/fondo.png');
                                $category = $vendor->business_category ?: 'Comercio local';
                            @endphp
                            <article class="min-w-0 overflow-hidden rounded-lg border border-atlantia-rose/20 bg-white shadow-[0_8px_22px_rgba(63,13,29,0.07)] transition hover:-translate-y-0.5 hover:shadow-[0_12px_28px_rgba(63,13,29,0.12)]">
                                <a href="{{ route('comercios.show', ['vendor' => $vendor->slug]) }}" class="block">
                                    <div class="relative h-20 overflow-hidden bg-atlantia-blush">
                                        <img src="{{ $cover }}" alt="{{ $vendor->business_name }}" class="h-full w-full object-cover" loading="lazy">
                                        <div class="absolute inset-0 bg-gradient-to-t from-atlantia-wine-900/35 to-transparent"></div>
                                        <span class="absolute bottom-1.5 left-2 rounded-full bg-white/95 px-2 py-0.5 text-[8px] font-bold text-atlantia-ink shadow-sm">{{ $category }}</span>
                                        <span class="absolute right-2 top-2 grid h-6 w-6 place-items-center rounded-full border border-white/70 bg-atlantia-wine-900/65 text-white backdrop-blur-sm">
                                            <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M12 20s-7-4.4-7-10a4.2 4.2 0 0 1 7-3.1A4.2 4.2 0 0 1 19 10c0 5.6-7 10-7 10Z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/></svg>
                                        </span>
                                        @if ($vendor->logo_url)
                                            <span class="absolute bottom-1.5 right-2 grid h-11 w-11 place-items-center overflow-hidden rounded-full border-2 border-white bg-white shadow-md">
                                                <img src="{{ $vendor->logo_url }}" alt="" class="h-full w-full object-cover" loading="lazy">
                                            </span>
                                        @endif
                                    </div>
                                </a>

                                <div class="px-2.5 py-2">
                                    <h3 class="truncate text-xs font-black text-atlantia-ink">{{ $vendor->business_name }}</h3>
                                    <div class="mt-1 flex min-w-0 items-center gap-1 text-[8px] font-semibold text-atlantia-ink/60">
                                        <span class="font-black text-rose-500">{{ $rating['rating'] ? number_format((float) $rating['rating'], 1) : 'Nuevo' }}</span>
                                        @if ((int) $rating['total'] > 0)<span>({{ number_format((int) $rating['total']) }})</span>@endif
                                        <span aria-hidden="true">&#183;</span>
                                        <span class="truncate">{{ $vendor->municipio ?: 'Atlantia' }}</span>
                                    </div>
                                    <div class="mt-1.5 flex items-center justify-between gap-2 border-t border-atlantia-rose/10 pt-1.5 text-[8px] font-black text-atlantia-wine">
                                        <span>Entrega local disponible</span>
                                        <span class="shrink-0 text-atlantia-ink/55">{{ number_format((int) $vendor->productos_publicados_count) }} productos</span>
                                    </div>
                                </div>
                            </article>
                        @endforeach
                    </div>
                @endif
            </section>

            <section class="mt-2.5 grid grid-cols-5 divide-x divide-atlantia-rose/15 border-y border-atlantia-rose/15 bg-atlantia-blush/50 px-3 py-2">
                @foreach ([
                    ['title' => 'Entrega rapida', 'text' => 'Cobertura local'],
                    ['title' => 'Comercios verificados', 'text' => 'Locales aprobados'],
                    ['title' => 'Pago seguro', 'text' => 'Opciones protegidas'],
                    ['title' => 'Atencion 24/7', 'text' => 'Estamos para ayudarte'],
                    ['title' => 'Garantia Atlantia', 'text' => 'Soporte y seguimiento'],
                ] as $trust)
                    <div class="flex min-w-0 items-center justify-center gap-2 px-2">
                        <span class="grid h-7 w-7 shrink-0 place-items-center rounded-full bg-white text-atlantia-wine shadow-sm">
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M12 3 19 6v5c0 4.5-2.8 8.4-7 10-4.2-1.6-7-5.5-7-10V6l7-3ZM9 12l2 2 4-5" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/></svg>
                        </span>
                        <div class="min-w-0">
                            <p class="truncate text-[9px] font-black text-atlantia-ink">{{ $trust['title'] }}</p>
                            <p class="truncate text-[8px] font-semibold text-atlantia-ink/55">{{ $trust['text'] }}</p>
                        </div>
                    </div>
                @endforeach
            </section>

            <section class="relative mt-2.5 h-[4.75rem] overflow-hidden rounded-xl bg-[linear-gradient(100deg,#4a071c_0%,#7a1238_48%,#a20f4b_100%)] px-6 text-white shadow-[0_12px_28px_rgba(63,13,29,0.14)]">
                <div class="absolute -right-12 -top-16 h-48 w-48 rounded-full border border-white/10"></div>
                <div class="relative z-10 flex h-full items-center justify-between gap-5">
                    <div class="flex items-center gap-4">
                        <span class="grid h-12 w-12 shrink-0 place-items-center rounded-full border border-white/25 bg-white/10 shadow-[0_0_24px_rgba(255,104,161,0.28)]">
                            <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M6 8h12l1 13H5L6 8ZM9 8a3 3 0 0 1 6 0" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round"/></svg>
                        </span>
                        <div>
                            <h2 class="text-lg font-black">Listo para tu primer pedido?</h2>
                            <p class="mt-0.5 text-[10px] font-semibold text-white/75">Explora los comercios disponibles y encuentra lo que necesitas.</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-3 pr-16">
                        <a href="{{ route('comercios.index', ['municipio' => $municipioActivo]) }}" class="inline-flex h-10 items-center gap-3 rounded-lg bg-white px-5 text-xs font-black text-atlantia-wine shadow-lg">Explorar comercios <span aria-hidden="true">&rarr;</span></a>
                        <a href="{{ route('comercios.index', ['municipio' => $municipioActivo]) }}" class="inline-flex h-10 items-center gap-3 rounded-lg border border-white/20 bg-white/5 px-5 text-xs font-bold text-white hover:bg-white/10">Hacer mi primer pedido <span aria-hidden="true">&#9734;</span></a>
                    </div>
                </div>
            </section>
        </div>
    </div>
@endsection
