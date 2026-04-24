@props([
    'producto',
])

@php
    $cardImage = $producto->getFirstMediaUrl('productos', 'card');
    $fullImage = $producto->getFirstMediaUrl('productos', 'full');
    $legacyImage = $producto->imagenPrincipal?->path ? asset('storage/' . $producto->imagenPrincipal->path) : null;
    $imageUrl = $cardImage ?: $legacyImage;
@endphp

<article {{ $attributes->merge(['class' => 'rounded-lg border border-atlantia-rose/30 bg-white shadow-sm']) }}>
    <div class="relative">
        <div class="absolute right-3 top-3 z-10">
            <livewire:cliente.wishlist-button :producto-id="$producto->id" :key="'wishlist-' . $producto->id" />
        </div>
        <a href="{{ route('productos.show', $producto) }}" class="block">
        <div class="aspect-square overflow-hidden rounded-t-lg bg-atlantia-blush">
            @if ($imageUrl)
                <img
                    src="{{ $imageUrl }}"
                    @if ($cardImage && $fullImage)
                        srcset="{{ $cardImage }} 600w, {{ $fullImage }} 1200w"
                        sizes="(min-width: 1024px) 25vw, (min-width: 640px) 50vw, 100vw"
                    @endif
                    alt="Producto {{ $producto->nombre }} de {{ $producto->vendor?->business_name ?? 'Atlantia Supermarket' }}"
                    class="h-full w-full object-cover"
                    loading="lazy"
                >
            @else
                <div class="flex h-full items-center justify-center text-sm text-atlantia-ink/60">
                    Imagen no disponible
                </div>
            @endif
        </div>
        </a>
    </div>

    <div class="p-3">
        <p class="text-xs font-semibold uppercase text-atlantia-wine">{{ $producto->vendor?->business_name }}</p>
        <h3 class="mt-1 line-clamp-2 text-sm font-semibold leading-5 text-atlantia-ink">{{ $producto->nombre }}</h3>
        <div class="mt-2 flex items-center justify-between gap-2">
            <x-price :amount="$producto->precio_oferta ?? $producto->precio_base" />
            <x-ui.badge :variant="$producto->inventario?->stock_actual > 0 ? 'success' : 'danger'">
                {{ $producto->inventario?->stock_actual > 0 ? 'Disponible' : 'Agotado' }}
            </x-ui.badge>
        </div>

        <form method="POST" action="{{ route('cliente.carrito.items.store') }}" class="mt-3">
            @csrf
            <input type="hidden" name="producto_id" value="{{ $producto->id }}">
            <input type="hidden" name="cantidad" value="1">
            <x-ui.button
                type="submit"
                class="w-full py-2.5"
                aria-label="Agregar {{ $producto->nombre }} al carrito"
                :disabled="$producto->inventario?->stock_actual < 1"
            >
                Agregar al carrito
            </x-ui.button>
        </form>
    </div>
</article>
