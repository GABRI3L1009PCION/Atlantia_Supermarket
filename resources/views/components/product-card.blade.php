@props([
    'producto',
])

@php
    $legacyPath  = $producto->imagenPrincipal?->path;
    $legacyImage = $legacyPath
        ? (str_starts_with($legacyPath, 'http') ? $legacyPath : asset('storage/' . $legacyPath))
        : ($producto->imagen && str_starts_with($producto->imagen, 'http') ? $producto->imagen : null);
    $cardImage = $producto->getFirstMediaUrl('productos', 'card');
    $fullImage  = $producto->getFirstMediaUrl('productos', 'full');
    $imageUrl   = $cardImage ?: $legacyImage;
@endphp

<article
    x-data="{ productoId: {{ $producto->id }} }"
    @click="$wire.dispatch('open-product-modal', { productId: productoId })"
    @keydown.enter.prevent="$wire.dispatch('open-product-modal', { productId: productoId })"
    @keydown.space.prevent="$wire.dispatch('open-product-modal', { productId: productoId })"
    {{ $attributes->merge(['class' => 'product-card-root flex h-full cursor-pointer flex-col rounded-lg border border-atlantia-rose/30 bg-white shadow-sm transition hover:-translate-y-0.5 hover:shadow-md lg:rounded-md']) }}
    role="button"
    tabindex="0"
>
    <div class="relative">
        {{-- Wishlist: detener propagación con Alpine --}}
        <div class="absolute right-3 top-3 z-10" @click.stop>
            <livewire:cliente.wishlist-button :producto-id="$producto->id" :key="'wishlist-' . $producto->id" />
        </div>

        <div class="product-card-media flex aspect-square items-center justify-center overflow-hidden rounded-t-lg bg-atlantia-blush lg:rounded-t-md">
            @if ($imageUrl)
                <img
                    src="{{ $imageUrl }}"
                    @if ($cardImage && $fullImage)
                        srcset="{{ $cardImage }} 600w, {{ $fullImage }} 1200w"
                        sizes="(min-width: 1024px) 25vw, (min-width: 640px) 50vw, 100vw"
                    @endif
                    alt="{{ $producto->nombre }}"
                    class="product-card-image h-full w-full object-cover"
                    loading="lazy"
                >
            @else
                <div class="product-card-empty flex h-full w-full items-center justify-center text-sm text-atlantia-wine/75">
                    Imagen no disponible
                </div>
            @endif
        </div>
    </div>

    <div class="product-card-body flex flex-1 flex-col p-2.5 sm:p-3 lg:p-2">
        <p class="product-card-vendor text-[10px] font-semibold uppercase leading-4 text-atlantia-wine sm:text-xs">
            {{ $producto->vendor?->business_name ?? 'Atlantia Supermarket' }}
        </p>
        <h3 class="product-card-title mt-1 line-clamp-2 min-h-10 text-sm font-semibold leading-5 text-atlantia-ink sm:text-sm">
            {{ $producto->nombre }}
        </h3>
        <div class="product-card-price-row mt-2 flex min-h-8 items-center justify-between gap-1.5 sm:gap-2">
            <x-price :amount="$producto->precio_oferta ?? $producto->precio_base" class="product-card-price" />
            <x-ui.badge :variant="$producto->inventario?->stock_actual > 0 ? 'success' : 'danger'">
                {{ $producto->inventario?->stock_actual > 0 ? 'Disponible' : 'Agotado' }}
            </x-ui.badge>
        </div>

        {{-- Formulario: detener propagación con Alpine para no abrir el modal --}}
        <form
            method="POST"
            action="{{ route('cliente.carrito.items.store') }}"
            class="product-card-cart-form mt-auto pt-3"
            @click.stop
        >
            @csrf
            <input type="hidden" name="producto_id" value="{{ $producto->id }}">
            <input type="hidden" name="cantidad" value="1">
            <x-ui.button
                type="submit"
                class="w-full py-2 text-[11px] sm:py-2.5 sm:text-sm"
                aria-label="Agregar {{ $producto->nombre }} al carrito"
                :disabled="$producto->inventario?->stock_actual < 1"
            >
                Agregar al carrito
            </x-ui.button>
        </form>
    </div>
</article>
