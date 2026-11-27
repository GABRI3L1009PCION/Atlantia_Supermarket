@php
    $marketplaceLogoRelativePath = collect([
        'images/logo-atlantia-delivery.png',
        'images/logo-atlantia-delivery.svg',
        'images/logo.png',
        'images/atlantia-logo.svg',
    ])->first(fn (string $path): bool => file_exists(public_path($path)));
    $marketplaceLogoUrl = $marketplaceLogoRelativePath
        ? asset($marketplaceLogoRelativePath).'?v='.filemtime(public_path($marketplaceLogoRelativePath))
        : null;
    $navItems = [
        ['label' => 'Inicio', 'href' => route('home'), 'active' => request()->routeIs('home', 'catalogo.index')],
        ['label' => 'Categorías', 'href' => route('categorias.index'), 'active' => request()->routeIs('categorias.*')],
        ['label' => 'Tiendas afiliadas', 'href' => route('comercios.index'), 'active' => request()->routeIs('comercios.*')],
        ['label' => 'Pedidos', 'href' => route('cliente.pedidos.index'), 'active' => request()->routeIs('cliente.pedidos.*')],
        ['label' => 'Mis favoritos', 'href' => route('cliente.wishlist.index'), 'active' => request()->routeIs('cliente.wishlist.*')],
        ['label' => 'Servicios', 'href' => route('servicios'), 'active' => request()->routeIs('servicios', 'vende', 'anunciate', 'eventos', 'envios.*', 'novedades')],
        ['label' => 'Contacto', 'href' => route('contacto'), 'active' => request()->routeIs('contacto')],
    ];
@endphp

<header class="sticky top-0 z-50 shadow-[0_6px_20px_rgba(42,16,24,0.18)]">
    {{-- Franja vino: logo, menu, carrito y sesion. --}}
    <div class="border-b border-white/10 bg-[linear-gradient(100deg,#3a0004_0%,#5e0008_48%,#3a0004_100%)] text-white">
    <div class="mx-auto w-full max-w-[1680px] px-3 py-3 sm:px-6 lg:px-8">
        <a
            href="#contenido-principal"
            class="sr-only focus:not-sr-only focus:rounded-md focus:bg-white focus:px-3 focus:py-2 focus:text-atlantia-wine"
        >
            Saltar al contenido
        </a>

        {{-- ===================== Movil ===================== --}}
        <div class="lg:hidden">
            <div class="relative grid grid-cols-[minmax(0,1fr)_auto_auto_auto] items-center gap-2">
                <a href="{{ route('home') }}" class="inline-flex min-w-0 items-center text-white" aria-label="Atlantia Store">
                    @if ($marketplaceLogoUrl)
                        <img
                            src="{{ $marketplaceLogoUrl }}"
                            alt="Atlantia Store"
                            class="h-11 w-auto max-w-full object-contain object-left sm:h-14"
                        >
                    @else
                        <span class="inline-flex items-center gap-2">
                            <span class="grid h-9 w-9 place-items-center rounded-full border-2 border-white text-base font-black leading-none">A</span>
                            <span class="leading-none">
                                <span class="block text-lg font-black">Atlantia</span>
                                <span class="block text-center text-[8px] font-black uppercase">Delivery</span>
                            </span>
                        </span>
                    @endif
                </a>

                <details class="group" data-close-outside>
                    <summary class="grid h-11 w-11 cursor-pointer list-none place-items-center rounded-lg border border-white/25 bg-white/10 text-white shadow-sm hover:bg-white/20 [&::-webkit-details-marker]:hidden" aria-label="Abrir menu">
                        <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                            <path d="M5 7h14M5 12h14M5 17h14" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                        </svg>
                    </summary>

                    @include('layouts.partials.menu-movil')
                </details>

                <livewire:carrito.icono-carrito />

                @auth
                    @include('layouts.partials.menu-cuenta', ['compacto' => true])
                @else
                    <a href="{{ route('login') }}" class="inline-flex h-11 items-center rounded-lg bg-white px-3 text-xs font-black text-atlantia-wine shadow-sm sm:px-5 sm:text-sm">
                        Entrar
                    </a>
                @endauth
            </div>
        </div>

        {{-- ===================== Escritorio ===================== --}}
        <div class="hidden lg:block">
            <div class="flex items-center justify-between gap-4">
                <a href="{{ route('home') }}" class="inline-flex shrink-0 items-center text-white" aria-label="Atlantia Store">
                    @if ($marketplaceLogoUrl)
                        <img
                            src="{{ $marketplaceLogoUrl }}"
                            alt="Atlantia Store"
                            class="h-14 w-auto max-w-[13rem] object-contain"
                        >
                    @else
                        <span class="inline-flex items-center gap-2">
                            <span class="grid h-11 w-11 place-items-center rounded-full border-[3px] border-white text-xl font-black leading-none">A</span>
                            <span class="leading-none">
                                <span class="block text-2xl font-black tracking-normal">Atlantia</span>
                                <span class="block text-center text-[11px] font-black uppercase">Delivery</span>
                            </span>
                        </span>
                    @endif
                </a>

                <nav class="flex min-w-0 flex-1 items-center justify-center gap-1 text-sm font-black xl:gap-2" aria-label="Navegacion principal">
                    @foreach ($navItems as $item)
                        <a
                            href="{{ $item['href'] }}"
                            class="{{ $item['active'] ? 'border-white text-white' : 'border-transparent text-white/80 hover:border-white/40 hover:text-white' }} inline-flex h-11 items-center gap-2 whitespace-nowrap border-b-2 px-2 transition xl:px-3"
                            @if ($item['active']) aria-current="page" @endif
                        >
                            @switch($item['label'])
                                @case('Inicio')
                                    <svg class="hidden h-5 w-5 shrink-0 xl:block" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M4 10.5 12 4l8 6.5V20a1 1 0 0 1-1 1h-5v-6h-4v6H5a1 1 0 0 1-1-1v-9.5Z" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round"/></svg>
                                    @break
                                @case('Categorías')
                                    <svg class="hidden h-5 w-5 shrink-0 xl:block" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M4 4h7v7H4V4ZM13 4h7v7h-7V4ZM4 13h7v7H4v-7ZM13 13h7v7h-7v-7Z" stroke="currentColor" stroke-width="1.7"/></svg>
                                    @break
                                @case('Tiendas afiliadas')
                                    <svg class="hidden h-5 w-5 shrink-0 xl:block" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M4 10h16l-1-5H5l-1 5ZM6 10v10h12V10M9 20v-6h6v6" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round"/></svg>
                                    @break
                                @case('Pedidos')
                                    <svg class="hidden h-5 w-5 shrink-0 xl:block" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M7 4h10v3h2v13H5V7h2V4Z" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round"/></svg>
                                    @break
                                @case('Mis favoritos')
                                    <svg class="hidden h-5 w-5 shrink-0 xl:block" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M12 20s-7-4.4-7-10a4.2 4.2 0 0 1 7-3.1A4.2 4.2 0 0 1 19 10c0 5.6-7 10-7 10Z" stroke="currentColor" stroke-width="1.7"/></svg>
                                    @break
                                @default
                                    <svg class="hidden h-5 w-5 shrink-0 xl:block" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M4 5h16v14H4V5ZM8 9h8M8 13h5" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/></svg>
                            @endswitch
                            {{ $item['label'] }}
                        </a>
                    @endforeach
                </nav>

                <div class="flex shrink-0 items-center gap-3">
                    <livewire:carrito.icono-carrito />

                    @auth
                        @include('layouts.partials.menu-cuenta')
                    @else
                        <a href="{{ route('login') }}" class="inline-flex h-12 items-center gap-2 rounded-full bg-white px-5 text-sm font-black text-atlantia-wine shadow-sm hover:bg-atlantia-blush">
                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                <path d="M12 12a4 4 0 1 0 0-8 4 4 0 0 0 0 8ZM4 21a8 8 0 0 1 16 0" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                            </svg>
                            Iniciar sesion
                        </a>
                    @endauth
                </div>
            </div>
        </div>
    </div>
    </div>
</header>

{{-- Barra de busqueda fuera del menu. En la pantalla de pago se oculta: ahi el cliente ya esta finalizando su compra. --}}
{{-- En checkout y en las pantallas de acceso no se muestra el buscador. --}}
@unless (request()->routeIs('cliente.checkout.*', 'login', 'login.*', 'register', 'register.*', 'password.*', 'two-factor.*', 'verification.*', 'vendedor.acceso.*', 'acceso.*', 'comercios.show', 'servicios', 'vende', 'anunciate', 'envios.*', 'contacto', 'legal.*'))
<div>
        <div class="mx-auto w-full max-w-7xl px-3 pt-4 sm:px-5 sm:pt-5">
            <form action="{{ route('catalogo.index') }}" method="GET" class="relative mx-auto max-w-4xl" role="search">
                <label for="header-marketplace-search" class="sr-only">Buscar en Atlantia</label>
                <svg class="pointer-events-none absolute left-4 top-1/2 h-5 w-5 -translate-y-1/2 text-atlantia-wine/70 sm:left-5" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                    <path d="m21 21-4.3-4.3M10.8 18a7.2 7.2 0 1 1 0-14.4 7.2 7.2 0 0 1 0 14.4Z" stroke="currentColor" stroke-width="1.9" stroke-linecap="round"/>
                </svg>
                <input
                    id="header-marketplace-search"
                    type="search"
                    name="q"
                    value="{{ request('q') }}"
                    placeholder="Buscar en todo el supermercado..."
                    class="h-11 w-full rounded-full border border-atlantia-rose/30 bg-atlantia-cream/60 pl-11 pr-24 text-sm font-semibold text-atlantia-ink outline-none transition placeholder:text-atlantia-ink/45 focus:border-atlantia-wine focus:bg-white focus:ring-2 focus:ring-atlantia-blush sm:h-12 sm:pl-12 sm:pr-32"
                >
                <button class="absolute right-1 top-1 h-9 rounded-full bg-atlantia-wine px-4 text-sm font-black text-white hover:bg-atlantia-wine-700 sm:right-1.5 sm:top-1.5 sm:px-6">Buscar</button>
            </form>
        </div>
</div>
@endunless
