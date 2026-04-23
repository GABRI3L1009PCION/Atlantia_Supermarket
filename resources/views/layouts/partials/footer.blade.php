<footer id="contacto" class="mt-16 border-t border-atlantia-rose/12 bg-atlantia-cream">
    <div class="mx-auto grid w-full max-w-7xl gap-8 px-4 py-10 sm:px-6 lg:grid-cols-[minmax(0,1.2fr)_repeat(3,minmax(0,0.6fr))] lg:px-8">
        <div class="space-y-4">
            <p class="text-2xl font-black tracking-tight text-atlantia-ink">Atlantia Supermarket</p>
            <p class="max-w-md text-sm leading-7 text-atlantia-ink/70">
                Supermercado digital para compras locales con productos frescos, despensa y esenciales del hogar,
                pensado para una experiencia clara y directa.
            </p>
        </div>

        <div class="space-y-3">
            <p class="text-sm font-black uppercase tracking-[0.18em] text-atlantia-wine">Compra</p>
            <a href="{{ route('home') }}" class="block text-sm text-atlantia-ink/75 transition hover:text-atlantia-wine">Inicio</a>
            <a href="{{ route('catalogo.index') }}" class="block text-sm text-atlantia-ink/75 transition hover:text-atlantia-wine">Catalogo</a>
            <a href="{{ route('catalogo.index') }}#categorias" class="block text-sm text-atlantia-ink/75 transition hover:text-atlantia-wine">Categorias</a>
        </div>

        <div class="space-y-3">
            <p class="text-sm font-black uppercase tracking-[0.18em] text-atlantia-wine">Cobertura</p>
            <p class="text-sm text-atlantia-ink/75">Santo Tomas de Castilla</p>
            <p class="text-sm text-atlantia-ink/75">Puerto Barrios</p>
            <p class="text-sm text-atlantia-ink/75">Izabal, Guatemala</p>
        </div>

        <div class="space-y-3">
            <p class="text-sm font-black uppercase tracking-[0.18em] text-atlantia-wine">Cuenta</p>
            @auth
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="text-sm text-atlantia-ink/75 transition hover:text-atlantia-wine">Cerrar sesion</button>
                </form>
            @else
                <a href="{{ route('login') }}" class="block text-sm text-atlantia-ink/75 transition hover:text-atlantia-wine">Iniciar sesion</a>
                <a href="{{ route('register') }}" class="block text-sm text-atlantia-ink/75 transition hover:text-atlantia-wine">Crear cuenta</a>
            @endauth
        </div>
    </div>

    <div class="border-t border-atlantia-rose/10">
        <div class="mx-auto flex w-full max-w-7xl flex-col gap-3 px-4 py-4 text-sm text-atlantia-ink/60 sm:px-6 md:flex-row md:items-center md:justify-between lg:px-8">
            <p><span class="font-bold text-atlantia-ink">Atlantia</span> &copy; {{ now()->year }}. Todos los derechos reservados.</p>
            <p>Compra local con entrega cercana y catalogo verificado.</p>
        </div>
    </div>
</footer>
