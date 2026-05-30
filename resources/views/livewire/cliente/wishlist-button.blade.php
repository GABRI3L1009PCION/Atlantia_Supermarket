<button
    type="button"
    wire:click="toggle"
    wire:loading.attr="disabled"
    class="group inline-flex h-9 w-9 items-center justify-center rounded-full shadow-md backdrop-blur-sm transition-all duration-200 hover:scale-110 active:scale-95 disabled:cursor-wait
        {{ $guardado
            ? 'bg-atlantia-wine border border-atlantia-wine text-white shadow-atlantia-wine/30'
            : 'bg-white/90 border border-white/70 text-atlantia-wine hover:bg-atlantia-wine/10' }}"
    aria-label="{{ $guardado ? 'Quitar de lista de deseos' : 'Agregar a lista de deseos' }}"
    title="{{ $guardado ? 'Quitar de favoritos' : 'Agregar a favoritos' }}"
>
    {{-- Icono normal --}}
    <span wire:loading.remove wire:target="toggle">
        @if ($guardado)
            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                <path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z"/>
            </svg>
        @else
            <svg class="h-4 w-4 transition-transform duration-150 group-hover:scale-110" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/>
            </svg>
        @endif
    </span>

    {{-- Icono cargando --}}
    <span wire:loading wire:target="toggle" class="animate-pulse" aria-hidden="true">
        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="currentColor">
            <path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z"/>
        </svg>
    </span>
</button>
