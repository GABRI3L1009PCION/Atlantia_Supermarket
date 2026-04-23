@php
    $logoPath = file_exists(public_path('images/logo.png')) ? 'images/logo.png' : 'images/atlantia-logo.svg';
@endphp

<header class="sticky top-0 z-50 mx-auto mt-2 w-[min(96%,1280px)] rounded-full border border-white/70 bg-white/72 shadow-[0_14px_36px_rgba(18,51,66,0.10)] backdrop-blur-xl">
    <div class="mx-auto flex min-h-14 w-full max-w-7xl items-center justify-between gap-4 px-5 py-2 sm:px-6 lg:px-8">
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
                    class="h-10 w-auto sm:h-11"
                >
            </a>

            <x-nav-mobile :items="[
                ['label' => 'Inicio', 'href' => route('home'), 'active' => request()->routeIs('home')],
                ['label' => 'Categorias', 'href' => route('catalogo.index') . '#categorias', 'active' => request()->routeIs('catalogo.*')],
                ['label' => 'Ofertas', 'href' => route('catalogo.index', ['orden' => 'precio_asc'])],
                ['label' => 'Catalogo', 'href' => route('catalogo.index')],
            ]" />
        </div>

        <nav class="hidden items-center gap-6 lg:flex" aria-label="Navegacion principal">
            <a href="{{ route('home') }}" class="border-b-2 border-atlantia-cyan-700 pb-1 text-sm font-medium text-atlantia-deep">
                Inicio
            </a>
            <a href="{{ route('catalogo.index') }}#categorias" class="inline-flex items-center gap-2 text-sm font-medium text-atlantia-deep transition hover:text-atlantia-cyan-700">
                Categorias
                <span class="text-sm">⌄</span>
            </a>
            <a href="{{ route('catalogo.index', ['orden' => 'precio_asc']) }}" class="text-sm font-medium text-atlantia-deep transition hover:text-atlantia-cyan-700">
                Ofertas
            </a>
            <a href="{{ route('catalogo.index') }}" class="text-sm font-medium text-atlantia-deep transition hover:text-atlantia-cyan-700">
                Catalogo
            </a>
        </nav>

        <div class="flex items-center gap-3">
            @auth
                <a
                    href="{{ route('cliente.wishlist.index') }}"
                    class="hidden rounded-md border border-atlantia-cyan/40 bg-white/85 px-4 py-2 text-sm font-semibold text-atlantia-deep transition hover:border-atlantia-cyan-700 hover:text-atlantia-cyan-700 sm:inline-flex"
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
                        class="rounded-md border border-atlantia-cyan/40 bg-white/88 px-5 py-2 text-sm font-semibold text-atlantia-deep transition hover:border-atlantia-cyan-700 hover:text-atlantia-cyan-700"
                    >
                        Salir
                    </button>
                </form>
            @else
                <a
                    href="{{ route('login') }}"
                    class="rounded-md border border-atlantia-cyan/40 bg-white/88 px-5 py-2 text-sm font-semibold text-atlantia-deep transition hover:border-atlantia-cyan-700 hover:text-atlantia-cyan-700"
                >
                    Iniciar sesion
                </a>
            @endauth
        </div>
    </div>
</header>
