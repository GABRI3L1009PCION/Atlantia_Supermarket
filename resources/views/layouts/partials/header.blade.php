<header class="border-b border-slate-200 bg-white">
    <div class="mx-auto flex h-16 w-full max-w-7xl items-center justify-between px-4 sm:px-6 lg:px-8">
        <a href="{{ route('home') }}" class="text-xl font-bold text-emerald-800">
            Atlantia
        </a>

        <a
            href="#contenido-principal"
            class="sr-only focus:not-sr-only focus:rounded-md focus:bg-white focus:px-3 focus:py-2"
        >
            Saltar al contenido
        </a>

        <nav class="flex items-center gap-4 text-sm font-medium text-slate-700" aria-label="Navegacion principal">
            <a href="{{ route('catalogo.index') }}" class="hover:text-emerald-700">Catalogo</a>

            @auth
                <a href="{{ route('cliente.carrito.index') }}" class="hover:text-emerald-700">Carrito</a>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="hover:text-emerald-700">Salir</button>
                </form>
            @else
                <a href="{{ route('login') }}" class="hover:text-emerald-700">Ingresar</a>
            @endauth
        </nav>
    </div>
</header>
