@php
    $municipiosAtlantia = collect(config('atlantia.marketplace.municipios', ['Puerto Barrios', 'Santo Tomas']))
        ->map(fn ($municipio) => trim((string) $municipio))
        ->filter()
        ->values()
        ->all();
    $municipioPredeterminado = trim((string) config('atlantia.marketplace.default_municipio', 'Puerto Barrios'));
    if (! in_array($municipioPredeterminado, $municipiosAtlantia, true)) {
        $municipioPredeterminado = $municipiosAtlantia[0] ?? 'Puerto Barrios';
    }
    $municipioSolicitado = request('municipio') ?: session('cliente_municipio');
    $municipioActivo = in_array($municipioSolicitado, $municipiosAtlantia, true) ? $municipioSolicitado : $municipioPredeterminado;
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
        ['label' => 'Inicio', 'href' => route('home'), 'active' => request()->routeIs('home')],
        ['label' => 'Categorias', 'href' => route('categorias.index'), 'active' => request()->routeIs('categorias.*')],
        ['label' => 'Comercios', 'href' => route('comercios.index'), 'active' => request()->routeIs('comercios.*')],
        ['label' => 'Pedidos', 'href' => route('cliente.pedidos.index'), 'active' => request()->routeIs('cliente.pedidos.*')],
        ['label' => 'Mis favoritos', 'href' => route('cliente.wishlist.index'), 'active' => request()->routeIs('cliente.wishlist.*')],
        ['label' => 'Contacto', 'href' => route('contacto'), 'active' => request()->routeIs('contacto')],
    ];
@endphp

<header class="sticky top-0 z-50 border-b border-atlantia-rose/15 bg-white shadow-sm">
    <div class="mx-auto w-full max-w-7xl bg-white px-3 py-3 sm:px-5">
        <a
            href="#contenido-principal"
            class="sr-only focus:not-sr-only focus:rounded-md focus:bg-white focus:px-3 focus:py-2"
        >
            Saltar al contenido
        </a>

        <div class="lg:hidden">
            <div class="grid grid-cols-[minmax(0,1fr)_auto_auto_auto] items-center gap-2">
                <a href="{{ route('home') }}" class="inline-flex min-w-0 items-center text-atlantia-wine" aria-label="Atlantia Delivery">
                    @if ($marketplaceLogoUrl)
                        <span class="relative block h-12 w-36 overflow-hidden sm:h-14 sm:w-44">
                            <img
                                src="{{ $marketplaceLogoUrl }}"
                                alt="Atlantia Delivery"
                                class="absolute left-0 top-1/2 w-full -translate-y-1/2 object-contain"
                            >
                        </span>
                    @else
                        <span class="inline-flex items-center gap-2">
                            <span class="grid h-9 w-9 place-items-center rounded-full border-2 border-atlantia-wine text-base font-black leading-none">A</span>
                            <span class="leading-none">
                                <span class="block text-lg font-black">Atlantia</span>
                                <span class="block text-center text-[8px] font-black uppercase">Delivery</span>
                            </span>
                        </span>
                    @endif
                </a>

                <details class="group relative">
                    <summary class="grid h-11 w-11 cursor-pointer list-none place-items-center rounded-lg border border-atlantia-rose/15 bg-white text-atlantia-wine shadow-sm [&::-webkit-details-marker]:hidden" aria-label="Abrir menu">
                        <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                            <path d="M5 7h14M5 12h14M5 17h14" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                        </svg>
                    </summary>

                    <div class="absolute right-0 top-14 z-[80] w-[19rem] max-w-[calc(100vw-1.5rem)] rounded-lg border border-atlantia-rose/15 bg-white p-3 shadow-[0_22px_60px_rgba(42,16,24,0.20)]">
                        <div class="border-b border-atlantia-rose/10 px-2 pb-3">
                            <p class="text-xs font-black uppercase text-atlantia-wine">Ubicacion de entrega</p>
                            <p class="mt-1 text-sm font-semibold text-atlantia-ink/55">{{ $municipioActivo }}</p>
                        </div>

                        <div class="mt-2 grid grid-cols-2 gap-1">
                            @foreach ($municipiosAtlantia as $municipio)
                                <form method="POST" action="{{ route('cliente.ubicacion.store') }}">
                                    @csrf
                                    <input type="hidden" name="municipio" value="{{ $municipio }}">
                                    <button class="{{ $municipioActivo === $municipio ? 'bg-atlantia-wine text-white' : 'bg-atlantia-blush/45 text-atlantia-ink' }} flex h-10 w-full items-center justify-center rounded-md px-2 text-xs font-black" type="submit">
                                        {{ $municipio }}
                                    </button>
                                </form>
                            @endforeach
                        </div>

                        <nav class="mt-3 grid gap-1 border-t border-atlantia-rose/10 pt-3" aria-label="Menu movil">
                            @foreach ($navItems as $item)
                                <a href="{{ $item['href'] }}" class="{{ $item['active'] ? 'bg-atlantia-blush text-atlantia-wine' : 'text-atlantia-ink/70 hover:bg-atlantia-blush' }} flex h-10 items-center rounded-md px-3 text-sm font-black">
                                    {{ $item['label'] }}
                                </a>
                            @endforeach
                        </nav>
                    </div>
                </details>

                <livewire:carrito.icono-carrito />

                @auth
                    <a href="{{ route('cliente.perfil.edit') }}" class="grid h-11 w-11 place-items-center rounded-lg bg-atlantia-wine text-white" aria-label="Mi perfil">
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M12 12a4 4 0 1 0 0-8 4 4 0 0 0 0 8ZM4 21a8 8 0 0 1 16 0" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
                    </a>
                @else
                    <a href="{{ route('login') }}" class="inline-flex h-11 items-center rounded-lg bg-atlantia-wine px-3 text-xs font-black text-white shadow-sm sm:px-5 sm:text-sm">
                        Entrar
                    </a>
                @endauth
            </div>

            <form action="{{ route('comercios.index') }}" method="GET" class="relative mt-3">
                <label for="header-marketplace-search-mobile" class="sr-only">Buscar en Atlantia</label>
                <input type="hidden" name="municipio" value="{{ $municipioActivo }}">
                <svg class="pointer-events-none absolute left-4 top-1/2 h-5 w-5 -translate-y-1/2 text-atlantia-ink/55" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                    <path d="m21 21-4.3-4.3M10.8 18a7.2 7.2 0 1 1 0-14.4 7.2 7.2 0 0 1 0 14.4Z" stroke="currentColor" stroke-width="1.9" stroke-linecap="round"/>
                </svg>
                <input
                    id="header-marketplace-search-mobile"
                    type="search"
                    name="q"
                    value="{{ request('q') }}"
                    placeholder="Buscar productos, tiendas o categorias..."
                    class="h-12 w-full rounded-lg border border-atlantia-rose/15 bg-white pl-12 pr-24 text-sm font-semibold text-atlantia-ink outline-none shadow-sm placeholder:text-atlantia-ink/40 focus:border-atlantia-wine focus:ring-2 focus:ring-atlantia-blush sm:h-13 sm:text-base"
                >
                <button class="absolute right-1 top-1 h-10 rounded-md bg-atlantia-wine px-4 text-sm font-black text-white sm:px-6">Buscar</button>
            </form>
        </div>

        <div class="hidden gap-3 lg:grid lg:grid-cols-[auto_190px_minmax(280px,1fr)_auto] lg:items-center">
            <div class="flex min-w-0 items-center justify-between gap-3">
                <a href="{{ route('home') }}" class="inline-flex shrink-0 items-center text-atlantia-wine" aria-label="Atlantia Delivery">
                    @if ($marketplaceLogoUrl)
                        <img
                            src="{{ $marketplaceLogoUrl }}"
                            alt="Atlantia Delivery"
                            class="h-14 w-auto max-w-[14rem] object-contain"
                        >
                    @else
                        <span class="inline-flex items-center gap-2">
                            <span class="grid h-11 w-11 place-items-center rounded-full border-[3px] border-atlantia-wine text-xl font-black leading-none">A</span>
                            <span class="leading-none">
                                <span class="block text-2xl font-black tracking-normal">Atlantia</span>
                                <span class="block text-center text-[11px] font-black uppercase">Delivery</span>
                            </span>
                        </span>
                    @endif
                </a>
            </div>

            <details class="group relative hidden lg:block">
                <summary class="flex h-12 cursor-pointer list-none items-center justify-between gap-2 rounded-xl border border-atlantia-rose/15 bg-atlantia-blush/45 px-4 text-sm font-bold text-atlantia-ink shadow-sm transition hover:border-atlantia-rose/30 [&::-webkit-details-marker]:hidden">
                    <span class="inline-flex min-w-0 items-center gap-2">
                        <svg class="h-5 w-5 shrink-0 text-atlantia-wine" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                            <path d="M12 21s7-6.1 7-12A7 7 0 1 0 5 9c0 5.9 7 12 7 12Zm0-9.2A2.8 2.8 0 1 1 12 6a2.8 2.8 0 0 1 0 5.8Z"/>
                        </svg>
                        <span class="truncate">{{ $municipioActivo }}</span>
                    </span>
                    <svg class="h-4 w-4 shrink-0 text-atlantia-ink/55 transition group-open:rotate-180" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                        <path d="m6 9 6 6 6-6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </summary>

                <div class="absolute left-0 top-14 z-[70] w-72 rounded-2xl border border-atlantia-rose/15 bg-white p-3 shadow-[0_20px_55px_rgba(42,16,24,0.18)]">
                    <div class="border-b border-atlantia-rose/10 px-2 pb-3">
                        <p class="text-xs font-black uppercase text-atlantia-wine">Ubicacion de entrega</p>
                        <p class="mt-1 text-sm font-semibold text-atlantia-ink/60">Filtra comercios disponibles por municipio.</p>
                    </div>

                    <div class="mt-2 space-y-1">
                        @foreach ($municipiosAtlantia as $municipio)
                            <form method="POST" action="{{ route('cliente.ubicacion.store') }}">
                                @csrf
                                <input type="hidden" name="municipio" value="{{ $municipio }}">
                                <button class="{{ $municipioActivo === $municipio ? 'bg-atlantia-wine text-white' : 'bg-white text-atlantia-ink hover:bg-atlantia-blush hover:text-atlantia-wine' }} flex h-11 w-full items-center justify-between rounded-xl px-3 text-left text-sm font-black transition" type="submit">
                                    <span>{{ $municipio }}</span>
                                    @if ($municipioActivo === $municipio)
                                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                            <path d="M5 12l4 4L19 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                        </svg>
                                    @endif
                                </button>
                            </form>
                        @endforeach
                    </div>

                </div>
            </details>

            <form action="{{ route('comercios.index') }}" method="GET" class="relative hidden lg:block">
                <label for="header-marketplace-search" class="sr-only">Buscar en Atlantia</label>
                <input type="hidden" name="municipio" value="{{ $municipioActivo }}">
                <svg class="pointer-events-none absolute left-4 top-1/2 h-5 w-5 -translate-y-1/2 text-atlantia-wine/70" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                    <path d="m21 21-4.3-4.3M10.8 18a7.2 7.2 0 1 1 0-14.4 7.2 7.2 0 0 1 0 14.4Z" stroke="currentColor" stroke-width="1.9" stroke-linecap="round"/>
                </svg>
                <input
                    id="header-marketplace-search"
                    type="search"
                    name="q"
                    value="{{ request('q') }}"
                    placeholder="Buscar productos, tiendas o categorias..."
                    class="h-12 w-full rounded-full border border-atlantia-rose/15 bg-white px-12 text-sm font-semibold text-atlantia-ink outline-none shadow-sm transition placeholder:text-atlantia-ink/45 focus:border-atlantia-wine focus:ring-2 focus:ring-atlantia-blush"
                >
            </form>

            <div class="hidden items-center justify-end gap-3 lg:flex">
                <a href="{{ route('cliente.wishlist.index') }}" class="inline-flex h-11 items-center gap-2 rounded-full px-3 text-sm font-black text-atlantia-ink hover:bg-atlantia-blush hover:text-atlantia-wine">
                    <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                        <path d="M12 20s-7-4.4-7-10a4.2 4.2 0 0 1 7-3.1A4.2 4.2 0 0 1 19 10c0 5.6-7 10-7 10Z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/>
                    </svg>
                    Favoritos
                </a>

                <livewire:carrito.icono-carrito />

                @auth
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="inline-flex h-12 items-center gap-2 rounded-full bg-atlantia-wine px-5 text-sm font-black text-white shadow-sm hover:bg-atlantia-wine-700">
                            Salir
                        </button>
                    </form>
                @else
                    <a href="{{ route('login') }}" class="inline-flex h-12 items-center gap-2 rounded-full bg-atlantia-wine px-5 text-sm font-black text-white shadow-sm hover:bg-atlantia-wine-700">
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                            <path d="M12 12a4 4 0 1 0 0-8 4 4 0 0 0 0 8ZM4 21a8 8 0 0 1 16 0" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                        </svg>
                        Iniciar sesion
                    </a>
                @endauth
            </div>
        </div>

        <nav class="mt-3 hidden items-center justify-center gap-3 border-t border-atlantia-rose/10 pt-3 text-sm font-black text-atlantia-ink lg:flex" aria-label="Navegacion principal">
            @foreach ($navItems as $item)
                <a
                    href="{{ $item['href'] }}"
                    class="{{ $item['active'] ? 'border-atlantia-rose text-atlantia-wine' : 'border-transparent text-atlantia-ink hover:text-atlantia-wine' }} inline-flex h-10 items-center gap-2 border-b-2 px-3 transition"
                >
                    @switch($item['label'])
                        @case('Inicio')
                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M4 10.5 12 4l8 6.5V20a1 1 0 0 1-1 1h-5v-6h-4v6H5a1 1 0 0 1-1-1v-9.5Z" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round"/></svg>
                            @break
                        @case('Categorias')
                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M4 4h7v7H4V4ZM13 4h7v7h-7V4ZM4 13h7v7H4v-7ZM13 13h7v7h-7v-7Z" stroke="currentColor" stroke-width="1.7"/></svg>
                            @break
                        @case('Comercios')
                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M4 10h16l-1-5H5l-1 5ZM6 10v10h12V10M9 20v-6h6v6" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round"/></svg>
                            @break
                        @case('Pedidos')
                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M7 4h10v3h2v13H5V7h2V4Z" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round"/></svg>
                            @break
                        @case('Mis favoritos')
                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M12 20s-7-4.4-7-10a4.2 4.2 0 0 1 7-3.1A4.2 4.2 0 0 1 19 10c0 5.6-7 10-7 10Z" stroke="currentColor" stroke-width="1.7"/></svg>
                            @break
                        @default
                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M4 5h16v14H4V5ZM8 9h8M8 13h5" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    @endswitch
                    {{ $item['label'] }}
                </a>
            @endforeach
        </nav>
    </div>
</header>
