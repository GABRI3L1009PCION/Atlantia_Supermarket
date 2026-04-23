@php
    $logoPath = file_exists(public_path('images/logo.png')) ? 'images/logo.png' : 'images/atlantia-logo.svg';
    $navItems = [
        ['label' => 'Inicio', 'href' => route('home'), 'active' => request()->routeIs('home')],
        ['label' => 'Categorias', 'href' => route('catalogo.index') . '#categorias', 'active' => request()->routeIs('catalogo.*')],
        ['label' => 'Ofertas locales', 'href' => route('catalogo.index', ['orden' => 'precio_asc'])],
    ];
@endphp

<header class="sticky top-0 z-50 border-b border-atlantia-rose/15 bg-white/95 backdrop-blur-md">
    <div class="bg-atlantia-wine text-white">
        <div class="mx-auto flex w-full max-w-7xl flex-col gap-2 px-4 py-2 text-xs font-semibold sm:px-6 md:flex-row md:items-center md:justify-between lg:px-8">
            <div class="flex flex-wrap items-center gap-3">
                <span class="rounded-full bg-white/10 px-3 py-1">Entrega local en Izabal</span>
                <span class="rounded-full bg-white/10 px-3 py-1">Vendedores verificados</span>
            </div>
            <div class="flex flex-wrap items-center gap-4 text-white/85">
                <a href="{{ route('catalogo.index') }}#categorias" class="transition hover:text-white">Categorias</a>
                <a href="#contacto" class="transition hover:text-white">Contacto</a>
            </div>
        </div>
    </div>

    <div class="mx-auto w-full max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="flex min-h-20 items-center justify-between gap-4 py-4">
            <a
                href="#contenido-principal"
                class="sr-only focus:not-sr-only focus:rounded-md focus:bg-white focus:px-3 focus:py-2"
            >
                Saltar al contenido
            </a>

            <div class="flex items-center gap-3">
                <a href="{{ route('home') }}" class="flex items-center gap-3" aria-label="Atlantia Supermarket">
                    <img
                        src="{{ asset($logoPath) }}"
                        alt="Atlantia Supermarket"
                        class="h-14 w-auto sm:h-16"
                    >
                    <div class="hidden lg:block">
                        <p class="text-xs font-bold uppercase tracking-[0.24em] text-atlantia-wine">Atlantia Marketplace</p>
                        <p class="mt-1 text-sm font-semibold text-atlantia-ink/70">Supermercado local con entrega segura</p>
                    </div>
                </a>

                <x-nav-mobile :items="$navItems" />
            </div>

            <nav class="hidden items-center gap-2 xl:flex" aria-label="Navegacion principal">
                @foreach ($navItems as $item)
                    <a
                        href="{{ $item['href'] }}"
                        class="rounded-md px-4 py-2 text-sm font-semibold transition {{ !empty($item['active']) ? 'bg-atlantia-blush text-atlantia-wine' : 'text-atlantia-ink hover:bg-atlantia-blush hover:text-atlantia-wine' }}"
                    >
                        {{ $item['label'] }}
                    </a>
                @endforeach
            </nav>

            <div class="hidden min-w-0 max-w-xl flex-1 xl:block">
                <form method="GET" action="{{ route('catalogo.index') }}">
                    <label for="header-search" class="sr-only">Buscar productos</label>
                    <div class="flex h-12 items-center gap-3 rounded-lg border border-atlantia-rose/20 bg-atlantia-cream px-4 shadow-sm">
                        <svg class="h-5 w-5 text-atlantia-wine" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                            <path d="M11 19C15.4 19 19 15.4 19 11C19 6.6 15.4 3 11 3C6.6 3 3 6.6 3 11C3 15.4 6.6 19 11 19Z" stroke="currentColor" stroke-width="2"/>
                            <path d="M20.5 20.5L16.7 16.7" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                        </svg>
                        <input
                            id="header-search"
                            type="search"
                            name="q"
                            value="{{ request('q') }}"
                            placeholder="Busca frutas, despensa, limpieza o marcas"
                            class="h-full w-full border-0 bg-transparent px-0 text-sm text-atlantia-ink placeholder:text-atlantia-ink/45 focus:outline-none focus:ring-0"
                        >
                    </div>
                </form>
            </div>

            <div class="flex items-center gap-2 sm:gap-3">
                @auth
                    @if (auth()->user()?->hasRole('cliente'))
                        <a
                            href="{{ route('cliente.wishlist.index') }}"
                            class="hidden rounded-md px-3 py-2 text-sm font-semibold text-atlantia-ink transition hover:bg-atlantia-blush hover:text-atlantia-wine lg:inline-flex"
                        >
                            Mi lista
                        </a>
                    @endif
                    <livewire:cliente.campanilla-notificaciones />
                @endauth

                <div class="hidden sm:block">
                    <livewire:carrito.icono-carrito />
                </div>

                @auth
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button
                            type="submit"
                            class="rounded-md bg-atlantia-wine px-4 py-2 text-sm font-bold text-white transition hover:bg-atlantia-wine-700"
                        >
                            Salir
                        </button>
                    </form>
                @else
                    <a
                        href="{{ route('login') }}"
                        class="rounded-md bg-atlantia-wine px-4 py-2 text-sm font-bold text-white transition hover:bg-atlantia-wine-700"
                    >
                        Entrar
                    </a>
                @endauth
            </div>
        </div>
    </div>
</header>
