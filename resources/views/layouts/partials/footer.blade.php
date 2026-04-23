@php
    $logoPath = file_exists(public_path('images/logo.png')) ? 'images/logo.png' : 'images/atlantia-logo.svg';
@endphp

<footer id="contacto" class="mt-16 border-t border-atlantia-rose/15 bg-atlantia-wine text-white">
    <div class="mx-auto grid w-full max-w-7xl gap-8 px-4 py-10 sm:px-6 lg:grid-cols-[minmax(0,1.2fr)_repeat(3,minmax(0,0.6fr))] lg:px-8 lg:py-14">
        <div class="space-y-5">
            <div class="flex items-center gap-4">
                <img
                    src="{{ asset($logoPath) }}"
                    alt="Atlantia Supermarket"
                    class="h-14 w-auto rounded-md bg-white/90 p-1"
                >
                <div>
                    <p class="text-lg font-black tracking-tight">Atlantia Supermarket</p>
                    <p class="text-sm text-white/75">Compra local con entrega clara, segura y cercana.</p>
                </div>
            </div>

            <p class="max-w-md text-sm leading-7 text-white/80">
                Un marketplace para comprar despensa, frescos y esenciales del hogar a vendedores aprobados
                de Izabal, con filtros utiles, stock visible y una experiencia de compra ordenada.
            </p>
        </div>

        <div class="space-y-3">
            <p class="text-sm font-black uppercase tracking-wide text-white">Compra</p>
            <a href="{{ route('home') }}" class="block text-sm text-white/80 transition hover:text-white">Inicio</a>
            <a href="{{ route('catalogo.index') }}" class="block text-sm text-white/80 transition hover:text-white">Catalogo</a>
            <a href="{{ route('catalogo.index') }}#categorias" class="block text-sm text-white/80 transition hover:text-white">Categorias</a>
        </div>

        <div class="space-y-3">
            <p class="text-sm font-black uppercase tracking-wide text-white">Cobertura</p>
            <p class="text-sm text-white/80">Santo Tomas de Castilla</p>
            <p class="text-sm text-white/80">Puerto Barrios</p>
            <p class="text-sm text-white/80">Izabal, Guatemala</p>
        </div>

        <div class="space-y-3">
            <p class="text-sm font-black uppercase tracking-wide text-white">Cuenta</p>
            @auth
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="text-sm text-white/80 transition hover:text-white">Cerrar sesion</button>
                </form>
            @else
                <a href="{{ route('login') }}" class="block text-sm text-white/80 transition hover:text-white">Iniciar sesion</a>
                <a href="{{ route('register') }}" class="block text-sm text-white/80 transition hover:text-white">Crear cuenta</a>
            @endauth
        </div>
    </div>

    <div class="border-t border-white/10">
        <div class="mx-auto flex w-full max-w-7xl flex-col gap-3 px-4 py-4 text-sm text-white/70 sm:px-6 md:flex-row md:items-center md:justify-between lg:px-8">
            <p><span class="font-bold text-white">Atlantia</span> &copy; {{ now()->year }}. Todos los derechos reservados.</p>
            <p>Supermercado digital para compras locales en Izabal.</p>
        </div>
    </div>
</footer>
