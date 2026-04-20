<header class="border-b border-atlantia-rose/30 bg-white/95 backdrop-blur">
    <div class="mx-auto flex h-16 w-full max-w-7xl items-center justify-between px-4 sm:px-6 lg:px-8">
        <a href="{{ route('home') }}" class="text-xl font-bold text-atlantia-wine">
            Atlantia
        </a>

        <a
            href="#contenido-principal"
            class="sr-only focus:not-sr-only focus:rounded-md focus:bg-white focus:px-3 focus:py-2"
        >
            Saltar al contenido
        </a>

        <nav class="flex items-center gap-4 text-sm font-medium text-atlantia-ink" aria-label="Navegacion principal">
            <a href="{{ route('catalogo.index') }}" class="hover:text-atlantia-wine">Catalogo</a>

            @auth
                <a href="{{ route('cliente.carrito.index') }}" class="hover:text-atlantia-wine">Carrito</a>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="hover:text-atlantia-wine">Salir</button>
                </form>
            @else
                <a href="{{ route('login') }}" class="hover:text-atlantia-wine">Ingresar</a>
            @endauth
        </nav>
    </div>
</header>
