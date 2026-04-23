@props([
    'producto',
])

@php
    $cardImage = $producto->getFirstMediaUrl('productos', 'card');
    $fullImage = $producto->getFirstMediaUrl('productos', 'full');
    $legacyImage = $producto->imagenPrincipal?->path ? asset('storage/' . $producto->imagenPrincipal->path) : null;
    $imageUrl = $cardImage ?: $legacyImage;
    $precioActual = (float) ($producto->precio_oferta ?? $producto->precio_base);
    $precioOriginal = $producto->precio_oferta ? (float) $producto->precio_base : null;
    $stockDisponible = (int) ($producto->inventario?->stock_actual ?? 0) > 0;
@endphp

<article {{ $attributes->merge(['class' => 'overflow-hidden rounded-lg border border-atlantia-rose/20 bg-white shadow-sm transition hover:-translate-y-0.5 hover:shadow-lg']) }}>
    <div class="relative">
        <div class="absolute left-3 top-3 z-10 flex items-center gap-2">
            <span class="rounded-full bg-white/95 px-3 py-1 text-xs font-bold uppercase tracking-wide text-atlantia-wine shadow-sm">
                {{ $producto->categoria?->nombre ?? 'Catalogo' }}
            </span>
        </div>

        <div class="absolute right-3 top-3 z-10">
            <livewire:cliente.wishlist-button :producto-id="$producto->id" :key="'wishlist-' . $producto->id" />
        </div>

        <a href="{{ route('productos.show', $producto) }}" class="block">
            <div class="aspect-[4/3] overflow-hidden bg-atlantia-blush">
                @if ($imageUrl)
                    <img
                        src="{{ $imageUrl }}"
                        @if ($cardImage && $fullImage)
                            srcset="{{ $cardImage }} 600w, {{ $fullImage }} 1200w"
                            sizes="(min-width: 1536px) 20vw, (min-width: 1280px) 25vw, (min-width: 640px) 50vw, 100vw"
                        @endif
                        alt="{{ $producto->nombre }}"
                        class="h-full w-full object-cover transition duration-300 hover:scale-[1.03]"
                        loading="lazy"
                    >
                @else
                    <div class="flex h-full items-center justify-center px-6 text-center text-sm font-semibold text-atlantia-ink/60">
                        Imagen no disponible
                    </div>
                @endif
            </div>
        </a>
    </div>

    <div class="space-y-4 p-4">
        <div class="space-y-2">
            <p class="text-xs font-bold uppercase tracking-wide text-atlantia-wine">
                {{ $producto->vendor?->business_name ?? 'Atlantia Supermarket' }}
            </p>

            <a href="{{ route('productos.show', $producto) }}" class="block">
                <h3 class="line-clamp-2 text-lg font-bold leading-6 text-atlantia-ink">
                    {{ $producto->nombre }}
                </h3>
            </a>

            <p class="line-clamp-2 text-sm leading-6 text-atlantia-ink/65">
                {{ $producto->descripcion }}
            </p>
        </div>

        <div class="flex items-end justify-between gap-3">
            <div class="min-w-0">
                @if ($precioOriginal)
                    <p class="text-sm font-semibold text-atlantia-ink/40 line-through">
                        Q {{ number_format($precioOriginal, 2) }}
                    </p>
                @endif

                <p class="text-2xl font-black tracking-tight text-atlantia-ink">
                    Q {{ number_format($precioActual, 2) }}
                </p>
            </div>

            <span class="rounded-full px-3 py-1 text-xs font-bold {{ $stockDisponible ? 'bg-emerald-100 text-emerald-700' : 'bg-rose-100 text-rose-700' }}">
                {{ $stockDisponible ? 'Disponible' : 'Agotado' }}
            </span>
        </div>

        <form method="POST" action="{{ route('cliente.carrito.items.store') }}" class="space-y-3">
            @csrf
            <input type="hidden" name="producto_id" value="{{ $producto->id }}">
            <input type="hidden" name="cantidad" value="1">

            <x-ui.button
                type="submit"
                class="w-full"
                aria-label="Agregar {{ $producto->nombre }} al carrito"
                :disabled="! $stockDisponible"
            >
                {{ $stockDisponible ? 'Agregar al carrito' : 'Sin stock por ahora' }}
            </x-ui.button>
        </form>
    </div>
</article>
