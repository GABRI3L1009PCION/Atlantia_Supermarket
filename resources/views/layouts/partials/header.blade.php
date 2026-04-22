@php
    $logoPath = file_exists(public_path('images/logo.png')) ? 'images/logo.png' : 'images/atlantia-logo.svg';
@endphp

<header class="border-b border-atlantia-rose/20 bg-white shadow-sm">
    <div class="mx-auto flex min-h-20 w-full max-w-7xl items-center justify-between gap-4 px-4 py-3 sm:px-6 lg:px-8">
        <a href="{{ route('home') }}" class="flex items-center" aria-label="Atlantia Supermarket">
            <img
                src="{{ asset($logoPath) }}"
                alt="Atlantia Supermarket"
                class="h-14 w-auto sm:h-16"
            >
        </a>

        <a
            href="#contenido-principal"
            class="sr-only focus:not-sr-only focus:rounded-md focus:bg-white focus:px-3 focus:py-2"
        >
            Saltar al contenido
        </a>

        <nav class="flex items-center gap-2 text-sm font-semibold text-atlantia-ink sm:gap-4" aria-label="Navegacion principal">
            <a
                href="{{ route('home') }}"
                class="rounded-md bg-atlantia-blush px-4 py-2 text-atlantia-wine hover:bg-atlantia-rose/25"
            >
                Inicio
            </a>
            <a
                href="{{ route('catalogo.index') }}#categorias"
                class="rounded-md bg-atlantia-blush px-4 py-2 text-atlantia-wine hover:bg-atlantia-rose/25"
            >
                Categorias
            </a>
            <a
                href="#contacto"
                class="rounded-md px-4 py-2 text-atlantia-ink hover:bg-atlantia-blush hover:text-atlantia-wine"
            >
                Contacto
            </a>

            <livewire:carrito.icono-carrito />

            @auth
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button
                        type="submit"
                        class="rounded-md bg-atlantia-wine px-5 py-2 text-white hover:bg-atlantia-wine-700"
                    >
                        Salir
                    </button>
                </form>
            @else
                <a
                    href="{{ route('login') }}"
                    class="rounded-md bg-atlantia-wine px-5 py-2 text-white hover:bg-atlantia-wine-700"
                >
                    Iniciar sesion
                </a>
            @endauth
        </nav>
    </div>
</header>
