{{-- Menú "Mi cuenta" de la barra superior. $compacto: versión de celular (solo el círculo). --}}
@php
    $usuarioMenu = auth()->user();
    $panelMenu = \App\Support\MenuCuenta::panelDe($usuarioMenu);
    $opcionesMenu = $panelMenu === null ? \App\Support\MenuCuenta::cliente() : [];
    $nombreCorto = \Illuminate\Support\Str::before(trim((string) $usuarioMenu?->name), ' ') ?: 'Mi cuenta';
    $compacto = $compacto ?? false;
@endphp

<details class="group relative" data-close-outside data-menu-cuenta>
    <summary @class([
        'flex cursor-pointer list-none items-center gap-2 rounded-full bg-white text-atlantia-wine shadow-sm transition hover:bg-atlantia-blush [&::-webkit-details-marker]:hidden',
        'h-11 w-11 justify-center' => $compacto,
        'h-12 py-1 pl-1 pr-4' => ! $compacto,
    ]) aria-label="Abrir menú de mi cuenta">
        <span class="grid h-9 w-9 place-items-center rounded-full bg-atlantia-wine text-xs font-black text-white">{{ \App\Support\MenuCuenta::iniciales($usuarioMenu?->name) }}</span>
        @unless ($compacto)
            <span class="max-w-[8rem] truncate text-sm font-black">{{ $nombreCorto }}</span>
            <svg class="h-4 w-4 transition group-open:rotate-180" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="m6 9 6 6 6-6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
        @endunless
    </summary>

    <div class="absolute right-0 top-full z-[90] mt-2 w-64 overflow-hidden rounded-xl border border-atlantia-rose/15 bg-white text-atlantia-ink shadow-[0_22px_60px_rgba(42,16,24,0.25)]">
        <div class="border-b border-atlantia-rose/10 bg-[#fbf7f6] px-4 py-3">
            <p class="truncate text-sm font-black">{{ $usuarioMenu?->name }}</p>
            <p class="truncate text-xs text-atlantia-ink/55">{{ $usuarioMenu?->email }}</p>
        </div>

        <nav class="grid gap-0.5 p-1.5" aria-label="Mi cuenta">
            @if ($panelMenu !== null)
                <a href="{{ route($panelMenu) }}" class="flex h-10 items-center gap-3 rounded-lg px-3 text-sm font-bold text-atlantia-ink/80 hover:bg-atlantia-blush hover:text-atlantia-wine">
                    <svg class="h-4 w-4 text-atlantia-wine" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M4 4h7v7H4zM13 4h7v7h-7zM4 13h7v7H4zM13 13h7v7h-7z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/></svg>
                    Ir a mi panel
                </a>
            @endif
            @foreach ($opcionesMenu as $opcion)
                <a href="{{ route($opcion['ruta']) }}" @class([
                    'flex h-10 items-center gap-3 rounded-lg px-3 text-sm font-bold hover:bg-atlantia-blush hover:text-atlantia-wine',
                    'bg-atlantia-blush text-atlantia-wine' => request()->routeIs(...$opcion['activa']),
                    'text-atlantia-ink/80' => ! request()->routeIs(...$opcion['activa']),
                ])>
                    <svg class="h-4 w-4 text-atlantia-wine" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="{{ $opcion['icono'] }}" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    {{ $opcion['texto'] }}
                </a>
            @endforeach
        </nav>

        <form method="POST" action="{{ route('logout') }}" class="border-t border-atlantia-rose/10 p-1.5">
            @csrf
            <button type="submit" class="flex h-10 w-full items-center gap-3 rounded-lg px-3 text-sm font-bold text-red-700 hover:bg-red-50">
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M15 4h4v16h-4M10 8l-4 4 4 4M6 12h10" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>
                Cerrar sesión
            </button>
        </form>
    </div>
</details>
