<button
    type="button"
    wire:click="toggle"
    wire:loading.attr="disabled"
    class="inline-flex h-10 w-10 items-center justify-center rounded-full border border-white/80 bg-white/90
        text-atlantia-wine shadow-md backdrop-blur transition hover:scale-105"
    aria-label="{{ $guardado ? 'Quitar de lista de deseos' : 'Agregar a lista de deseos' }}"
>
    <span wire:loading.remove wire:target="toggle">
        @if ($guardado)
            &#9829;
        @else
            &#9825;
        @endif
    </span>
    <span wire:loading wire:target="toggle">...</span>
</button>
