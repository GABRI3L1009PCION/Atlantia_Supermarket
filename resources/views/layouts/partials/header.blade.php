@php
    $logoPath = file_exists(public_path('images/logo.png')) ? 'images/logo.png' : 'images/atlantia-logo.svg';
    $navItems = [
        ['label' => 'Inicio', 'href' => route('home'), 'active' => request()->routeIs('home')],
        ['label' => 'Categorias', 'href' => route('catalogo.index') . '#categorias', 'active' => request()->routeIs('catalogo.*')],
        ['label' => 'Mis favoritos', 'href' => route('cliente.wishlist.index'), 'active' => request()->routeIs('cliente.wishlist.*')],
    ];
@endphp

<header class="sticky top-0 z-50 border-b border-atlantia-rose/20 bg-white shadow-sm">
    <div class="mx-auto flex h-16 w-full max-w-7xl items-center justify-between gap-2 px-3 sm:h-16 sm:gap-4 sm:px-6 lg:px-8">
        <a
            href="#contenido-principal"
            class="sr-only focus:not-sr-only focus:rounded-md focus:bg-white focus:px-3 focus:py-2"
        >
            Saltar al contenido
        </a>

        {{-- Logo --}}
        <div class="flex min-w-0 items-center">
            <a href="{{ route('home') }}" class="shrink-0" aria-label="Atlantia Supermarket">
                <img
                    src="{{ asset($logoPath) }}"
                    alt="Atlantia Supermarket"
                    class="h-9 w-auto max-w-[132px] object-contain min-[380px]:max-w-[160px] sm:h-11 sm:max-w-[220px]"
                >
            </a>
        </div>

        {{-- Desktop nav --}}
        <nav class="hidden items-center gap-1 text-sm font-semibold text-atlantia-ink md:flex" aria-label="Navegacion principal">
            <a
                href="{{ route('home') }}"
                class="rounded-lg px-4 py-2 text-atlantia-wine hover:bg-atlantia-blush"
            >
                Inicio
            </a>
            <a
                href="{{ route('catalogo.index') }}#categorias"
                class="rounded-lg px-4 py-2 text-atlantia-ink hover:bg-atlantia-blush hover:text-atlantia-wine"
            >
                Categorias
            </a>
            <a
                href="{{ route('contacto') }}"
                class="{{ request()->routeIs('contacto') ? 'bg-atlantia-blush text-atlantia-wine' : 'text-atlantia-ink hover:bg-atlantia-blush hover:text-atlantia-wine' }} rounded-lg px-4 py-2"
            >
                Contacto
            </a>
            <a
                href="{{ route('cliente.wishlist.index') }}"
                class="{{ request()->routeIs('cliente.wishlist.*') ? 'bg-atlantia-blush text-atlantia-wine' : 'text-atlantia-ink hover:bg-atlantia-blush hover:text-atlantia-wine' }} rounded-lg px-4 py-2"
            >
                Mis favoritos
            </a>

            @auth
                <livewire:cliente.campanilla-notificaciones />
            @endauth

            <livewire:carrito.icono-carrito />

            @auth
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button
                        type="submit"
                        class="ml-1 rounded-lg bg-atlantia-wine px-4 py-2 text-sm font-bold text-white hover:bg-atlantia-wine-700"
                    >
                        Salir
                    </button>
                </form>
            @else
                <a
                    href="{{ route('login') }}"
                    class="ml-1 rounded-lg bg-atlantia-wine px-4 py-2 text-sm font-bold text-white hover:bg-atlantia-wine-700"
                >
                    Iniciar sesion
                </a>
            @endauth
        </nav>

        {{-- Mobile right side: notifications + cart + login/logout --}}
        <div class="flex shrink-0 items-center gap-1.5 md:hidden">
            <x-nav-mobile :items="$navItems" :contact-href="route('contacto')" :contact-active="request()->routeIs('contacto')" />

            @auth
                <livewire:cliente.campanilla-notificaciones />
            @endauth

            <span class="hidden min-[380px]:inline-flex">
                <livewire:carrito.icono-carrito />
            </span>

            @auth
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button
                        type="submit"
                        class="flex items-center gap-1 rounded-lg bg-atlantia-wine px-3 py-2 text-xs font-bold text-white"
                        aria-label="Cerrar sesion"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2h6a2 2 0 012 2v1"/>
                        </svg>
                        <span class="hidden sm:inline">Salir</span>
                    </button>
                </form>
            @else
                <a
                    href="{{ route('login') }}"
                    class="rounded-lg bg-atlantia-wine px-3 py-2 text-xs font-bold text-white sm:px-4 sm:text-sm"
                    aria-label="Iniciar sesion"
                >
                    Entrar
                </a>
            @endauth
        </div>
    </div>
</header>
