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

            <a
                href="{{ route('cliente.carrito.index') }}"
                class="relative flex h-10 w-10 items-center justify-center rounded-md text-atlantia-wine hover:bg-atlantia-blush"
                aria-label="Carrito"
            >
                <svg class="h-7 w-7" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                    <path d="M4 5H6L8.1 15.2C8.3 16.2 9.2 17 10.3 17H17.8C18.8 17 19.6 16.4 19.9 15.5L21 9H7" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    <path d="M10 21C10.6 21 11 20.6 11 20C11 19.4 10.6 19 10 19C9.4 19 9 19.4 9 20C9 20.6 9.4 21 10 21Z" fill="currentColor"/>
                    <path d="M18 21C18.6 21 19 20.6 19 20C19 19.4 18.6 19 18 19C17.4 19 17 19.4 17 20C17 20.6 17.4 21 18 21Z" fill="currentColor"/>
                </svg>
                <span class="absolute -right-1 -top-1 min-w-5 rounded-full bg-atlantia-wine px-1 text-center text-xs font-bold text-white">
                    0
                </span>
            </a>

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
