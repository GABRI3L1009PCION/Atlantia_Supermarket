{{-- Menú del botón ☰ en celular: compacto, con íconos, invitación a vender y ayuda por WhatsApp. --}}
@php
    $usuarioMenu = auth()->user();
    $primerNombre = $usuarioMenu ? \Illuminate\Support\Str::of($usuarioMenu->name)->explode(' ')->first() : null;
    $whatsappSoporte = preg_replace('/\D+/', '', (string) config('atlantia.support.whatsapp'));
    $iconos = [
        'inicio' => 'M4 10.5 12 4l8 6.5V20a1 1 0 0 1-1 1h-5v-6h-4v6H5a1 1 0 0 1-1-1v-9.5Z',
        'categorias' => 'M4 4h7v7H4V4ZM13 4h7v7h-7V4ZM4 13h7v7H4v-7ZM13 13h7v7h-7v-7Z',
        'tiendas' => 'M4 10h16l-1.2-5H5.2L4 10Zm1 0v10h14V10M9.5 20v-5h5v5',
        'ofertas' => 'M20 12 12 20l-8-8V4h8l8 8ZM8 8h.01',
        'pedidos' => 'M6 8h12l1 13H5L6 8Zm3 0a3 3 0 0 1 6 0',
        'favoritos' => 'M12 20s-7-4.4-7-10a4.2 4.2 0 0 1 7-3.1A4.2 4.2 0 0 1 19 10c0 5.6-7 10-7 10Z',
        'servicios' => 'M12 3 3 8l9 5 9-5-9-5ZM3 13l9 5 9-5',
        'contacto' => 'M4 5h16v11H8l-4 4V5Z',
    ];
    /* Principales: cuadrícula de 2 x 2. */
    $principales = [
        ['icono' => 'inicio', 'texto' => 'Inicio', 'href' => route('home'), 'activo' => request()->routeIs('home', 'catalogo.index')],
        ['icono' => 'categorias', 'texto' => 'Categorías', 'href' => route('categorias.index'), 'activo' => request()->routeIs('categorias.*')],
        ['icono' => 'tiendas', 'texto' => 'Tiendas afiliadas', 'href' => route('comercios.index'), 'activo' => request()->routeIs('comercios.*')],
        ['icono' => 'ofertas', 'texto' => 'Ofertas', 'href' => route('novedades'), 'activo' => request()->routeIs('novedades')],
    ];
    /* Secundarios: una fila de 4 accesos pequeños. */
    $secundarios = [
        ['icono' => 'pedidos', 'texto' => 'Pedidos', 'href' => route('cliente.pedidos.index'), 'activo' => request()->routeIs('cliente.pedidos.*')],
        ['icono' => 'favoritos', 'texto' => 'Favoritos', 'href' => route('cliente.wishlist.index'), 'activo' => request()->routeIs('cliente.wishlist.*')],
        ['icono' => 'servicios', 'texto' => 'Servicios', 'href' => route('servicios'), 'activo' => request()->routeIs('servicios', 'anunciate', 'eventos', 'envios.*')],
        ['icono' => 'contacto', 'texto' => 'Contacto', 'href' => route('contacto'), 'activo' => request()->routeIs('contacto')],
    ];
@endphp

<div class="absolute inset-x-0 top-full z-[80] mt-2 max-h-[calc(100dvh-11.5rem)] overflow-y-auto rounded-2xl border border-atlantia-rose/15 bg-white p-2 shadow-[0_22px_60px_rgba(42,16,24,0.28)]" data-menu-movil>
    {{-- Saludo en una sola línea --}}
    <div class="flex items-center justify-between gap-2 rounded-xl bg-[linear-gradient(110deg,#5e0008_0%,#8a1020_100%)] px-3 py-2 text-white">
        @if ($usuarioMenu)
            <p class="truncate text-sm font-black">Hola, {{ $primerNombre }} 👋</p>
            <a href="{{ route('cliente.perfil.edit') }}" class="shrink-0 text-[11px] font-bold text-white/80 underline">Mi cuenta</a>
        @else
            <p class="min-w-0 truncate text-sm font-black">¡Bienvenido!</p>
            <div class="flex shrink-0 gap-1.5">
                <a href="{{ route('login') }}" class="flex h-8 items-center rounded-lg bg-white px-3 text-[11px] font-black text-atlantia-wine">Entrar</a>
                <a href="{{ route('register') }}" class="flex h-8 items-center rounded-lg border border-white/40 px-2.5 text-[11px] font-black text-white">Crear cuenta</a>
            </div>
        @endif
    </div>

    <nav class="mt-2 space-y-2" aria-label="Menu movil">
        <div class="grid grid-cols-2 gap-1.5">
            @foreach ($principales as $item)
                <a href="{{ $item['href'] }}" class="{{ $item['activo'] ? 'bg-atlantia-wine text-white' : 'bg-atlantia-blush/60 text-atlantia-ink hover:bg-atlantia-blush' }} flex h-11 items-center gap-2 rounded-xl px-2.5 text-[13px] font-black" @if ($item['activo']) aria-current="page" @endif>
                    <svg class="{{ $item['activo'] ? 'text-white' : 'text-atlantia-wine' }} h-5 w-5 shrink-0" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="{{ $iconos[$item['icono']] }}" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    <span class="truncate">{{ $item['texto'] }}</span>
                </a>
            @endforeach
        </div>

        <div class="grid grid-cols-4 gap-1 border-t border-atlantia-rose/10 pt-2">
            @foreach ($secundarios as $item)
                <a href="{{ $item['href'] }}" class="{{ $item['activo'] ? 'bg-atlantia-blush text-atlantia-wine' : 'text-atlantia-ink/75 hover:bg-atlantia-blush/60' }} flex flex-col items-center gap-1 rounded-lg py-1.5 text-[11px] font-bold" @if ($item['activo']) aria-current="page" @endif>
                    <svg class="h-5 w-5 text-atlantia-wine" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="{{ $iconos[$item['icono']] }}" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    {{ $item['texto'] }}
                </a>
            @endforeach
        </div>

        {{-- Invitación a vender --}}
        <a href="{{ route('vende') }}" class="flex items-center justify-between gap-2 rounded-xl border border-atlantia-rose/25 bg-atlantia-cream px-3 py-2 hover:border-atlantia-wine/40">
            <span class="min-w-0 text-xs"><span class="font-black text-atlantia-wine">¿Tienes un negocio?</span> <span class="text-atlantia-ink/60">Vende sin mensualidad</span></span>
            <span class="shrink-0 rounded-full bg-atlantia-wine px-2.5 py-1 text-[11px] font-black text-white">Vender</span>
        </a>

        @if ($whatsappSoporte !== '')
            <a href="https://wa.me/{{ $whatsappSoporte }}" target="_blank" rel="noopener" class="flex items-center justify-center gap-1.5 py-1 text-[11px] font-bold text-emerald-700">
                <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12 2a10 10 0 0 0-8.6 15.1L2 22l5-1.3A10 10 0 1 0 12 2Zm5.3 14.1c-.2.6-1.3 1.2-1.8 1.2-.5.1-1 .2-3.3-.7-2.8-1.1-4.5-3.9-4.7-4.1-.1-.2-1.1-1.5-1.1-2.9 0-1.4.7-2 1-2.3.2-.3.5-.3.7-.3h.5c.2 0 .4 0 .6.5l.8 2c.1.2.1.4 0 .5l-.4.6-.4.4c-.1.1-.3.3-.1.6.2.3.8 1.3 1.7 2.1 1.2 1 2.1 1.3 2.4 1.5.3.1.5.1.6-.1l.9-1.1c.2-.3.4-.2.6-.1l1.9.9c.3.1.5.2.5.3.1.2.1.8-.1 1.3Z"/></svg>
                ¿Necesitas ayuda? WhatsApp
            </a>
        @endif
    </nav>
</div>
