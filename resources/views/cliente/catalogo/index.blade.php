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
        $mobileHeroImage = $deliveryHeroImage ?? $heroCard['mobile_image'] ?? $heroCard['desktop_image'] ?? asset('images/fondo.png');
    @endphp

    <div class="bg-white lg:hidden">
        <div class="mx-auto w-full max-w-md px-4 pb-36 pt-4">
            <section class="relative overflow-hidden rounded-2xl border border-atlantia-rose/15 bg-atlantia-blush shadow-sm">
                <img src="{{ $mobileHeroImage }}" alt="Atlantia Delivery" class="h-64 w-full object-cover object-center">
                <div class="absolute inset-0 bg-gradient-to-r from-white/95 via-white/72 to-transparent"></div>
                <div class="absolute inset-0 flex flex-col justify-between p-5">
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

            <section class="mt-4 grid grid-cols-3 gap-3">
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

            <form action="{{ route('comercios.index') }}" method="GET" class="relative mt-5">
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

            <nav class="mt-4 flex gap-2 overflow-x-auto pb-1 [scrollbar-width:none] [&::-webkit-scrollbar]:hidden" aria-label="Categorias">
                <a href="{{ route('comercios.index', ['municipio' => $municipioActivo]) }}" class="inline-flex h-12 shrink-0 items-center gap-2 rounded-full bg-atlantia-wine px-5 text-sm font-black text-white">
                    Todas
                </a>
                @foreach ($mobileCategories as $categoria)
                    <a href="{{ $categoria['href'] }}" class="inline-flex h-12 shrink-0 items-center gap-2 rounded-full border border-atlantia-rose/15 bg-white px-4 text-sm font-bold text-atlantia-ink shadow-sm">
                        <span class="grid h-6 w-6 place-items-center text-atlantia-wine">
                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                <path d="M4 7h16M7 4v16M17 4v16M4 17h16" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/>
                            </svg>
                        </span>
                        {{ $categoria['nombre'] }}
                    </a>
                @endforeach
            </nav>

            <section class="mt-6 border-t border-atlantia-rose/10 pt-5">
                <div class="mb-4 flex items-center justify-between gap-3">
                    <h2 class="text-xl font-black text-atlantia-ink">Comercios cerca de ti</h2>
                    <a href="{{ route('comercios.index', ['municipio' => $municipioActivo]) }}" class="inline-flex items-center gap-1 text-sm font-black text-atlantia-wine">
                        Ver todos
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                            <path d="M5 12h14m-6-6 6 6-6 6" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </a>
                </div>

                @if ($vendorsDestacados->isEmpty())
                    <div class="rounded-2xl border border-dashed border-atlantia-rose/25 bg-atlantia-blush/40 p-6 text-center">
                        <h3 class="font-black text-atlantia-ink">Aun no hay comercios publicados</h3>
                        <p class="mt-1 text-sm text-atlantia-ink/60">Cuando apruebes negocios con productos visibles apareceran aqui.</p>
                    </div>
                @else
                    <div class="flex snap-x snap-mandatory gap-3 overflow-x-auto pb-2 [scrollbar-width:none] [&::-webkit-scrollbar]:hidden">
                        @foreach ($vendorsDestacados->take(6) as $vendor)
                            @php
                                $rating = $ratingsDestacados->get($vendor->id, ['rating' => null, 'total' => 0]);
                                $cover = $vendor->cover_url ?: asset('images/fondo.png');
                                $category = $vendor->business_category ?: 'Comercio';
                                $shippingPromo = max(50, (int) (ceil(((int) $vendor->id + 1) / 25) * 25));
                            @endphp
                            <article class="w-44 shrink-0 snap-start overflow-hidden rounded-2xl border border-atlantia-rose/15 bg-white shadow-sm">
                                <a href="{{ route('comercios.show', ['vendor' => $vendor->slug]) }}" class="block">
                                    <div class="relative h-36 overflow-hidden bg-atlantia-blush">
                                        <img src="{{ $cover }}" alt="{{ $vendor->business_name }}" class="h-full w-full object-cover" loading="lazy">
                                        <span class="absolute bottom-2 left-2 rounded-md bg-white px-2 py-1 text-[11px] font-black text-atlantia-wine shadow-sm">{{ $category }}</span>
                                        <span class="absolute right-2 top-2 grid h-9 w-9 place-items-center rounded-full bg-white text-atlantia-wine shadow-sm">
                                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                                <path d="M12 20s-7-4.4-7-10a4.2 4.2 0 0 1 7-3.1A4.2 4.2 0 0 1 19 10c0 5.6-7 10-7 10Z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/>
                                            </svg>
                                        </span>
                                    </div>
                                    <div class="p-3">
                                        <h3 class="line-clamp-2 min-h-10 text-base font-black leading-5 text-atlantia-ink">{{ $vendor->business_name }}</h3>
                                        <p class="mt-1 text-sm font-semibold leading-5 text-atlantia-ink/55">25-35 min - {{ number_format(0.8 + (($vendor->id % 7) * 0.35), 1) }} km</p>
                                        <div class="mt-2 flex items-center gap-1 text-sm">
                                            <span class="font-black text-amber-500">*</span>
                                            <span class="font-black text-atlantia-ink">{{ $rating['rating'] ? number_format((float) $rating['rating'], 1) : 'Nuevo' }}</span>
                                            <span class="text-atlantia-ink/50">({{ number_format((int) $rating['total']) }})</span>
                                        </div>
                                        <div class="mt-3 rounded-lg bg-atlantia-blush px-3 py-2 text-xs font-black leading-4 text-atlantia-wine">
                                            Envio gratis desde Q{{ number_format($shippingPromo) }}
                                        </div>
                                    </div>
                                </a>
                            </article>
                        @endforeach
                    </div>
                @endif
            </section>

            <section class="mt-6 grid gap-4 rounded-2xl bg-atlantia-blush p-4">
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

        <nav class="fixed inset-x-0 bottom-0 z-40 border-t border-atlantia-rose/10 bg-white px-4 pb-safe shadow-[0_-12px_30px_rgba(42,16,24,0.08)]" aria-label="Navegacion inferior">
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

    <div class="hidden bg-white lg:block">
        <div class="mx-auto w-full max-w-7xl bg-white p-4 sm:p-6">
            <section class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_350px]">
                <article class="relative min-h-[28rem] overflow-hidden rounded-2xl bg-atlantia-blush shadow-sm">
                    @if ($heroCard)
                        <img
                            src="{{ $heroCard['desktop_image'] }}"
                            alt="Banner promocional {{ $heroCard['name'] }}"
                            class="hidden h-full w-full object-cover object-center md:block"
                        >
                        <img
                            src="{{ $heroCard['mobile_image'] }}"
                            alt="Banner promocional {{ $heroCard['name'] }}"
                            class="h-full w-full object-cover object-center md:hidden"
                        >
                    @else
                        <img
                            src="{{ asset('images/fondo.png') }}"
                            alt="Banner promocional Atlantia Delivery"
                            class="hidden h-full w-full object-cover object-center md:block"
                        >
                        <img
                            src="{{ asset('images/fondo.png') }}"
                            alt="Banner promocional Atlantia Delivery"
                            class="h-full w-full object-cover object-center md:hidden"
                        >
                    @endif

                    <div class="absolute inset-0 bg-gradient-to-r from-white/95 via-white/72 to-black/10"></div>
                    <div class="absolute inset-0 flex flex-col justify-between p-6 sm:p-8">
                        <div class="max-w-xl">
                            <span class="inline-flex items-center gap-2 rounded-lg bg-white/90 px-3 py-2 text-xs font-black uppercase text-atlantia-wine shadow-sm">
                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                    <path d="M5 17h10l3-5H8l-3 5ZM3 17h2M15 17h3M7 20a2 2 0 1 0 0-4 2 2 0 0 0 0 4ZM17 20a2 2 0 1 0 0-4 2 2 0 0 0 0 4Z" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                                Delivery Atlantia
                            </span>
                            <h1 class="mt-6 max-w-lg text-4xl font-black leading-tight text-atlantia-ink sm:text-5xl">
                                Lo que necesitas, <span class="text-atlantia-wine">donde lo necesitas</span>
                            </h1>
                            <p class="mt-5 max-w-md text-base font-semibold leading-7 text-atlantia-ink/70">
                                Compra en comercios locales verificados y recibe tus productos en la puerta de tu casa.
                            </p>
                        </div>

                        <div class="grid gap-3 sm:grid-cols-3">
                            <div class="rounded-xl bg-white/92 p-4 shadow-sm">
                                <p class="flex items-center gap-2 text-sm font-black text-atlantia-ink">
                                    <svg class="h-6 w-6 text-atlantia-wine" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                        <path d="M4 16h9l3-6H7l-3 6ZM2 16h2M16 16h3M7 19a2 2 0 1 0 0-4 2 2 0 0 0 0 4ZM18 19a2 2 0 1 0 0-4 2 2 0 0 0 0 4Z" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/>
                                    </svg>
                                    Entrega rapida
                                </p>
                                <p class="mt-1 text-xs font-bold text-atlantia-ink/55">30-60 min</p>
                            </div>
                            <div class="rounded-xl bg-white/92 p-4 shadow-sm">
                                <p class="flex items-center gap-2 text-sm font-black text-atlantia-ink">
                                    <svg class="h-6 w-6 text-atlantia-wine" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                        <path d="m12 3 2.8 5.7 6.2.9-4.5 4.4 1.1 6.2L12 17.3 6.4 20.2 7.5 14 3 9.6l6.2-.9L12 3Z" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"/>
                                    </svg>
                                    Comercios verificados
                                </p>
                                <p class="mt-1 text-xs font-bold text-atlantia-ink/55">Locales aprobados</p>
                            </div>
                            <div class="rounded-xl bg-white/92 p-4 shadow-sm">
                                <p class="flex items-center gap-2 text-sm font-black text-atlantia-ink">
                                    <svg class="h-6 w-6 text-atlantia-wine" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                        <path d="M6 10V8a6 6 0 0 1 12 0v2M5 10h14v10H5V10Z" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round"/>
                                    </svg>
                                    Pago seguro
                                </p>
                                <p class="mt-1 text-xs font-bold text-atlantia-ink/55">100% protegido</p>
                            </div>
                        </div>
                    </div>
                </article>

                <aside class="rounded-2xl border border-atlantia-rose/15 bg-white p-6 shadow-[0_18px_42px_rgba(42,16,24,0.10)]">
                    <h2 class="text-2xl font-black text-atlantia-ink">Que quieres pedir hoy?</h2>
                    <form action="{{ route('comercios.index') }}" method="GET" class="mt-5 space-y-4">
                        <label class="block text-sm font-bold text-atlantia-ink">
                            Buscar
                            <div class="relative mt-1">
                                <input
                                    type="search"
                                    name="q"
                                    value="{{ request('q') }}"
                                    placeholder="Buscar en todo Atlantia..."
                                    class="h-12 w-full rounded-lg border border-atlantia-rose/20 px-4 pr-11 text-sm font-semibold outline-none focus:border-atlantia-wine focus:ring-2 focus:ring-atlantia-blush"
                                >
                                <svg class="absolute right-4 top-1/2 h-5 w-5 -translate-y-1/2 text-atlantia-wine" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                    <path d="m21 21-4.3-4.3M10.8 18a7.2 7.2 0 1 1 0-14.4 7.2 7.2 0 0 1 0 14.4Z" stroke="currentColor" stroke-width="1.9" stroke-linecap="round"/>
                                </svg>
                            </div>
                        </label>

                        <label class="block text-sm font-bold text-atlantia-ink">
                            Municipio
                            <select name="municipio" class="mt-1 h-12 w-full rounded-lg border border-atlantia-rose/20 px-4 text-sm font-semibold outline-none focus:border-atlantia-wine focus:ring-2 focus:ring-atlantia-blush">
                                <option value="">Todos los municipios</option>
                                <option value="Puerto Barrios" @selected($municipioActivo === 'Puerto Barrios')>Puerto Barrios</option>
                                <option value="Santo Tomas" @selected($municipioActivo === 'Santo Tomas')>Santo Tomas</option>
                                <option value="Morales" @selected($municipioActivo === 'Morales')>Morales</option>
                                <option value="Los Amates" @selected($municipioActivo === 'Los Amates')>Los Amates</option>
                                <option value="Livingston" @selected($municipioActivo === 'Livingston')>Livingston</option>
                                <option value="El Estor" @selected($municipioActivo === 'El Estor')>El Estor</option>
                            </select>
                        </label>

                        <label class="block text-sm font-bold text-atlantia-ink">
                            Categoria
                            <select name="categoria" class="mt-1 h-12 w-full rounded-lg border border-atlantia-rose/20 px-4 text-sm font-semibold outline-none focus:border-atlantia-wine focus:ring-2 focus:ring-atlantia-blush">
                                <option value="">Todas las categorias</option>
                                @foreach ($categories as $categoria)
                                    <option value="{{ $categoria['id'] }}">{{ $categoria['nombre'] }}</option>
                                @endforeach
                            </select>
                        </label>

                        <button class="h-12 w-full rounded-lg bg-atlantia-wine px-5 text-sm font-black text-white shadow-sm hover:bg-atlantia-wine-700">
                            Buscar comercios
                        </button>
                    </form>
                </aside>
            </section>

            <section id="categorias" class="mt-8 rounded-2xl border border-atlantia-rose/15 bg-white p-5 shadow-sm">
                <div class="mb-5 flex items-center justify-between gap-3">
                    <h2 class="text-xl font-black text-atlantia-ink">Explora por categoria</h2>
                    <a href="{{ route('comercios.index', ['municipio' => $municipioActivo]) }}" class="text-sm font-black text-atlantia-wine hover:underline">Ver todas</a>
                </div>

                <div class="flex snap-x snap-mandatory gap-5 overflow-x-auto pb-2 [scrollbar-width:none] [&::-webkit-scrollbar]:hidden">
                    <a href="{{ route('comercios.index', ['municipio' => $municipioActivo]) }}" class="group flex min-w-[7rem] snap-start flex-col items-center text-center">
                        <span class="grid h-20 w-20 place-items-center rounded-full border-2 border-atlantia-wine bg-atlantia-wine text-white shadow-[0_14px_30px_rgba(135,22,61,0.16)]">
                            <svg class="h-9 w-9" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                <path d="M4 4h7v7H4V4ZM13 4h7v7h-7V4ZM4 13h7v7H4v-7ZM13 13h7v7h-7v-7Z" stroke="currentColor" stroke-width="1.8"/>
                            </svg>
                        </span>
                        <span class="mt-3 text-xs font-black text-atlantia-wine">Todas</span>
                    </a>

                    @foreach ($categories as $categoria)
                        <a href="{{ $categoria['href'] }}" class="group flex min-w-[7rem] snap-start flex-col items-center text-center">
                            <span class="grid h-20 w-20 place-items-center overflow-hidden rounded-full border border-atlantia-rose/25 bg-white p-4 text-atlantia-wine shadow-sm transition group-hover:border-atlantia-wine group-hover:bg-atlantia-blush">
                                @if ($categoria['image'])
                                    <img
                                        src="{{ $categoria['image'] }}"
                                        alt="{{ $categoria['nombre'] }}"
                                        class="h-full w-full object-contain mix-blend-multiply"
                                        loading="lazy"
                                    >
                                @else
                                    <svg class="h-9 w-9" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                        <path d="M4 7h16M7 4v16M17 4v16M4 17h16" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/>
                                    </svg>
                                @endif
                            </span>
                            <span class="mt-3 line-clamp-2 text-xs font-black text-atlantia-ink">{{ $categoria['nombre'] }}</span>
                        </a>
                    @endforeach
                </div>
            </section>

            <section id="comercios" class="mt-8 rounded-2xl border border-atlantia-rose/15 bg-white p-5 shadow-sm">
                <div class="mb-5 flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
                    <div>
                        <h2 class="text-xl font-black text-atlantia-ink">Comercios cerca de ti</h2>
                        <p class="mt-1 text-sm text-atlantia-ink/60">Tiendas y restaurantes locales listos para llevarte lo mejor.</p>
                    </div>
                    <a href="{{ route('comercios.index', ['municipio' => $municipioActivo]) }}" class="text-sm font-black text-atlantia-wine hover:underline">Ver todos -></a>
                </div>

                @if ($vendorsDestacados->isEmpty())
                    <div class="rounded-2xl border border-dashed border-atlantia-rose/25 bg-atlantia-blush/40 p-8 text-center">
                        <h3 class="font-black text-atlantia-ink">Aun no hay comercios publicados</h3>
                        <p class="mt-1 text-sm text-atlantia-ink/60">Cuando apruebes negocios con productos visibles apareceran aqui.</p>
                    </div>
                @else
                    <div class="flex snap-x snap-mandatory gap-4 overflow-x-auto pb-2 [scrollbar-width:none] [&::-webkit-scrollbar]:hidden">
                        @foreach ($vendorsDestacados->take(6) as $vendor)
                            @php
                                $rating = $ratingsDestacados->get($vendor->id, ['rating' => null, 'total' => 0]);
                                $cover = $vendor->cover_url ?: asset('images/fondo.png');
                                $category = $vendor->business_category ?: 'Comercio';
                                $shippingPromo = max(50, (int) (ceil(((int) $vendor->id + 1) / 25) * 25));
                            @endphp
                            <article class="min-w-[17rem] snap-start overflow-hidden rounded-2xl border border-atlantia-rose/15 bg-white shadow-sm">
                                <a href="{{ route('comercios.show', ['vendor' => $vendor->slug]) }}" class="block">
                                    <div class="relative h-44 overflow-hidden bg-atlantia-blush">
                                        <img src="{{ $cover }}" alt="{{ $vendor->business_name }}" class="h-full w-full object-cover" loading="lazy">
                                        <span class="absolute bottom-3 left-3 rounded-md bg-white px-2.5 py-1 text-xs font-black text-atlantia-wine shadow-sm">{{ $category }}</span>
                                        <span class="absolute right-3 top-3 grid h-10 w-10 place-items-center rounded-full bg-white text-atlantia-wine shadow-sm">
                                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                                <path d="M12 20s-7-4.4-7-10a4.2 4.2 0 0 1 7-3.1A4.2 4.2 0 0 1 19 10c0 5.6-7 10-7 10Z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/>
                                            </svg>
                                        </span>
                                    </div>
                                </a>

                                <div class="space-y-3 p-4">
                                    <div>
                                        <h3 class="truncate text-lg font-black text-atlantia-ink">{{ $vendor->business_name }}</h3>
                                        <p class="mt-1 truncate text-sm font-semibold text-atlantia-ink/55">{{ $category }} - 20-35 min - {{ $vendor->municipio ?: 'Atlantia' }}</p>
                                    </div>
                                    <div class="flex items-center gap-2 text-sm">
                                        <span class="font-black text-amber-500">*</span>
                                        <span class="font-black text-atlantia-ink">{{ $rating['rating'] ? number_format((float) $rating['rating'], 1) : 'Nuevo' }}</span>
                                        <span class="text-atlantia-ink/50">({{ number_format((int) $rating['total']) }})</span>
                                        <span class="text-atlantia-ink/50">- Min. Q25</span>
                                    </div>
                                    <div class="rounded-lg bg-atlantia-blush px-3 py-2 text-xs font-black text-atlantia-wine">
                                        Envio gratis desde Q{{ number_format($shippingPromo) }}
                                    </div>
                                </div>
                            </article>
                        @endforeach
                    </div>
                @endif
            </section>

            <section class="mt-8 grid gap-5 rounded-2xl bg-atlantia-blush p-5 lg:grid-cols-[0.8fr_1.2fr] lg:items-center">
                <div class="flex items-center gap-4">
                    <span class="grid h-20 w-20 shrink-0 place-items-center rounded-full bg-atlantia-wine text-white">
                        <svg class="h-10 w-10" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                            <path d="M4 16h9l3-6H7l-3 6ZM2 16h2M16 16h3M7 19a2 2 0 1 0 0-4 2 2 0 0 0 0 4ZM18 19a2 2 0 1 0 0-4 2 2 0 0 0 0 4Z" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </span>
                    <div>
                        <h2 class="text-2xl font-black text-atlantia-wine">Envio rapido y seguro!</h2>
                        <p class="mt-1 text-sm leading-6 text-atlantia-ink/70">Recibe tus productos en la puerta de tu casa de 30 a 60 minutos.</p>
                        <a href="{{ route('comercios.index', ['municipio' => $municipioActivo]) }}" class="mt-3 inline-flex rounded-md bg-atlantia-wine px-4 py-2 text-sm font-black text-white">Conocer mas</a>
                    </div>
                </div>

                <div class="grid gap-3 rounded-xl bg-white p-4 shadow-sm sm:grid-cols-4">
                    @foreach ([
                        ['label' => 'Elige tus productos', 'icon' => 'bag'],
                        ['label' => 'Confirmamos tu pedido', 'icon' => 'check'],
                        ['label' => 'En camino', 'icon' => 'ride'],
                        ['label' => 'Listo! A disfrutar', 'icon' => 'home'],
                    ] as $step)
                        <div class="text-center">
                            <span class="mx-auto grid h-14 w-14 place-items-center rounded-full bg-atlantia-blush text-atlantia-wine">
                                @if ($step['icon'] === 'bag')
                                    <svg class="h-7 w-7" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M6 8h12l1 13H5L6 8ZM9 8a3 3 0 0 1 6 0" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round"/></svg>
                                @elseif ($step['icon'] === 'check')
                                    <svg class="h-7 w-7" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M6 4h12v16H6V4ZM9 12l2 2 4-5" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                @elseif ($step['icon'] === 'ride')
                                    <svg class="h-7 w-7" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M4 16h9l3-6H7l-3 6ZM7 19a2 2 0 1 0 0-4 2 2 0 0 0 0 4ZM18 19a2 2 0 1 0 0-4 2 2 0 0 0 0 4Z" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                @else
                                    <svg class="h-7 w-7" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M4 10.5 12 4l8 6.5V20a1 1 0 0 1-1 1h-5v-6h-4v6H5a1 1 0 0 1-1-1v-9.5Z" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round"/></svg>
                                @endif
                            </span>
                            <p class="mt-3 text-xs font-black text-atlantia-ink">{{ $step['label'] }}</p>
                        </div>
                    @endforeach
                </div>
            </section>

            <section class="mt-8 grid gap-4 rounded-2xl border border-atlantia-rose/15 bg-white p-5 shadow-sm sm:grid-cols-2 lg:grid-cols-4">
                @foreach ([
                    ['title' => 'Comercios verificados', 'text' => 'Todos nuestros aliados pasan por un proceso de verificacion.'],
                    ['title' => 'Pago seguro', 'text' => 'Tus pagos estan protegidos con los mas altos estandares.'],
                    ['title' => 'Atencion 24/7', 'text' => 'Estamos aqui para ayudarte en cualquier momento que lo necesites.'],
                    ['title' => 'Garantia Atlantia', 'text' => 'Si algo no esta bien, te ayudamos con soporte y seguimiento.'],
                ] as $trust)
                    <article class="flex gap-4">
                        <span class="grid h-12 w-12 shrink-0 place-items-center rounded-full bg-atlantia-blush text-atlantia-wine">
                            <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                <path d="M12 3 19 6v5c0 4.5-2.8 8.4-7 10-4.2-1.6-7-5.5-7-10V6l7-3Z" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round"/>
                                <path d="M9 12l2 2 4-5" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </span>
                        <div>
                            <h3 class="text-sm font-black text-atlantia-ink">{{ $trust['title'] }}</h3>
                            <p class="mt-1 text-sm leading-5 text-atlantia-ink/60">{{ $trust['text'] }}</p>
                        </div>
                    </article>
                @endforeach
            </section>

            <section class="mt-8 overflow-hidden rounded-2xl bg-atlantia-wine p-6 text-white shadow-sm">
                <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h2 class="text-2xl font-black">Listo para tu primer pedido?</h2>
                        <p class="mt-1 text-sm font-semibold text-white/75">Unete a miles de clientes satisfechos en Atlantia Delivery.</p>
                    </div>
                    <a href="{{ route('comercios.index', ['municipio' => $municipioActivo]) }}" class="inline-flex h-12 items-center justify-center rounded-md bg-white px-6 text-sm font-black text-atlantia-wine">
                        Explorar comercios
                    </a>
                </div>
            </section>

        </div>
    </div>
@endsection
