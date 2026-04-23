@php
    $logoPath = file_exists(public_path('images/logo.png')) ? 'images/logo.png' : 'images/atlantia-logo.svg';
@endphp

<header class="sticky top-0 z-50 border-b border-atlantia-rose/12 bg-atlantia-cream/95 backdrop-blur-md">
    <div class="mx-auto flex min-h-20 w-full max-w-7xl items-center justify-between gap-4 px-4 py-3 sm:px-6 lg:px-8">
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
            </a>

            <x-nav-mobile :items="[
                ['label' => 'Inicio', 'href' => route('home'), 'active' => request()->routeIs('home')],
                ['label' => 'Categorias', 'href' => route('catalogo.index') . '#categorias', 'active' => request()->routeIs('catalogo.*')],
                ['label' => 'Ofertas', 'href' => route('catalogo.index', ['orden' => 'precio_asc'])],
                ['label' => 'Catalogo', 'href' => route('catalogo.index')],
            ]" />
        </div>

        <nav class="hidden items-center gap-8 lg:flex" aria-label="Navegacion principal">
            <a href="{{ route('home') }}" class="border-b-2 border-atlantia-wine pb-1 text-xl font-medium text-atlantia-ink">Inicio</a>
            <a href="{{ route('catalogo.index') }}#categorias" class="inline-flex items-center gap-2 text-xl font-medium text-atlantia-ink transition hover:text-atlantia-wine">
                Categorias
                <span class="text-sm">⌄</span>
            </a>
            <a href="{{ route('catalogo.index', ['orden' => 'precio_asc']) }}" class="text-xl font-medium text-atlantia-ink transition hover:text-atlantia-wine">Ofertas</a>
            <a href="{{ route('catalogo.index') }}" class="text-xl font-medium text-atlantia-ink transition hover:text-atlantia-wine">Catalogo</a>
        </nav>

        <div class="flex items-center gap-3">
            @auth
                <a
                    href="{{ route('cliente.wishlist.index') }}"
                    class="hidden rounded-md border border-atlantia-wine/30 bg-white px-4 py-2 text-sm font-semibold text-atlantia-ink transition hover:border-atlantia-wine hover:text-atlantia-wine sm:inline-flex"
                >
                    Mi lista
                </a>
            @endauth

            <div class="hidden sm:block">
                <livewire:carrito.icono-carrito />
            </div>

            @auth
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button
                        type="submit"
                        class="rounded-md border border-atlantia-wine/35 bg-white px-5 py-2 text-base font-semibold text-atlantia-ink transition hover:border-atlantia-wine hover:text-atlantia-wine"
                    >
                        Salir
                    </button>
                </form>
            @else
                <a
                    href="{{ route('login') }}"
                    class="rounded-md border border-atlantia-wine/35 bg-white px-5 py-2 text-base font-semibold text-atlantia-ink transition hover:border-atlantia-wine hover:text-atlantia-wine"
                >
                    Iniciar sesion
                </a>
            @endauth
        </div>
    </div>
</header>
