@php
    $mobileNavItems = [
        ['label' => 'Inicio', 'href' => route('home'), 'active' => request()->routeIs('home'), 'icon' => 'home'],
        ['label' => 'Categorías', 'href' => route('categorias.index'), 'active' => request()->routeIs('categorias.*'), 'icon' => 'grid'],
        ['label' => 'Pedidos', 'href' => route('cliente.pedidos.index'), 'active' => request()->routeIs('cliente.pedidos.*'), 'icon' => 'bag'],
        /* Favoritos sigue en Mi cuenta; aquí va el acceso rápido a las tiendas. */
        ['label' => 'Tiendas', 'href' => route('comercios.index'), 'active' => request()->routeIs('comercios.*'), 'icon' => 'store'],
        ['label' => auth()->check() ? 'Mi cuenta' : 'Entrar', 'href' => auth()->check() ? (($panelCuenta = \App\Support\MenuCuenta::panelDe(auth()->user())) ? route($panelCuenta) : route('cliente.perfil.edit')) : route('login'), 'active' => request()->routeIs('cliente.perfil.*', 'cliente.direcciones.*', 'cliente.saldo.*', 'cliente.membresia.*', 'cliente.resenas.*', 'cliente.wishlist.*', 'login'), 'icon' => 'user'],
    ];
@endphp

<nav class="fixed inset-x-0 bottom-0 z-50 border-t border-white/10 bg-[linear-gradient(100deg,#3a0004_0%,#5e0008_48%,#3a0004_100%)] px-2 pb-safe shadow-[0_-10px_28px_rgba(42,16,24,0.25)] lg:hidden" aria-label="Navegacion inferior">
    <div class="mx-auto grid h-[4.75rem] max-w-3xl grid-cols-5 items-center gap-1">
        @foreach ($mobileNavItems as $item)
            <a href="{{ $item['href'] }}" class="{{ $item['active'] ? 'bg-white/15 text-white' : 'text-white/75 hover:text-white' }} flex h-[3.6rem] min-w-0 flex-col items-center justify-center gap-1 rounded-2xl text-[10px] font-black sm:text-xs">
                <span class="grid h-7 w-7 place-items-center">
                    @if ($item['icon'] === 'home')
                        <svg class="h-6 w-6" viewBox="0 0 24 24" fill="{{ $item['active'] ? 'currentColor' : 'none' }}" aria-hidden="true"><path d="M4 10.5 12 4l8 6.5V20a1 1 0 0 1-1 1h-5v-6h-4v6H5a1 1 0 0 1-1-1v-9.5Z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/></svg>
                    @elseif ($item['icon'] === 'grid')
                        <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M4 4h6v6H4V4Zm10 0h6v6h-6V4ZM4 14h6v6H4v-6Zm10 0h6v6h-6v-6Z" stroke="currentColor" stroke-width="1.8"/></svg>
                    @elseif ($item['icon'] === 'bag')
                        <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M6 8h12l1 13H5L6 8Zm3 0a3 3 0 0 1 6 0" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/></svg>
                    @elseif ($item['icon'] === 'store')
                        <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M4 10h16l-1.2-5H5.2L4 10Zm1 0v10h14V10M9.5 20v-5h5v5" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/></svg>
                    @elseif ($item['icon'] === 'heart')
                        <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M12 20s-7-4.4-7-10a4.2 4.2 0 0 1 7-3.1A4.2 4.2 0 0 1 19 10c0 5.6-7 10-7 10Z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/></svg>
                    @else
                        <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M12 12a4 4 0 1 0 0-8 4 4 0 0 0 0 8ZM4 21a8 8 0 0 1 16 0" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
                    @endif
                </span>
                <span class="max-w-full truncate">{{ $item['label'] }}</span>
            </a>
        @endforeach
    </div>
</nav>
