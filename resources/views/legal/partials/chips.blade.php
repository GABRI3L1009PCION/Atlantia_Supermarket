{{-- Etiquetas del encabezado legal. Usa $vigencia, $esTerminos, $otro y $desdeCheckout del layout. --}}
<span class="inline-flex items-center gap-2 rounded-full bg-atlantia-blush/70 px-3 py-1.5 text-xs font-bold text-atlantia-ink/80 sm:text-sm">
    <x-legal.icon name="doc" class="h-4 w-4 text-atlantia-wine" /> Documento legal
</span>
<span class="inline-flex items-center gap-2 rounded-full bg-atlantia-blush/70 px-3 py-1.5 text-xs font-bold text-atlantia-ink/80 sm:text-sm">
    <x-legal.icon name="calendar" class="h-4 w-4 text-atlantia-wine" /> {{ $esTerminos ? 'Vigente desde el' : 'Actualizado el' }} {{ $vigencia }}
</span>
@unless ($soloEtiquetas ?? false)
    <a href="{{ route($otro['ruta'], $desdeCheckout ? ['from' => 'checkout'] : []) }}" class="inline-flex items-center gap-2 rounded-full border border-atlantia-rose/40 bg-white px-3.5 py-1.5 text-sm font-black text-atlantia-wine transition hover:bg-atlantia-blush">
        <x-legal.icon name="external" class="h-4 w-4" /> {{ $otro['texto'] }}
    </a>
@endunless
