@props([
    'producto',
])

@php
    $cardImage = $producto->getFirstMediaUrl('productos', 'card');
    $fullImage = $producto->getFirstMediaUrl('productos', 'full');
    $legacyImage = $producto->imagenPrincipal?->path ? asset('storage/' . $producto->imagenPrincipal->path) : null;
    $imageUrl = $cardImage ?: $legacyImage;
    $precioActual = (float) ($producto->precio_oferta ?? $producto->precio_base);
    $stockDisponible = (int) ($producto->inventario?->stock_actual ?? 0) > 0;
    $unidad = $producto->unidad_medida ?: 'unidad';
@endphp

<article {{ $attributes->merge(['class' => 'overflow-hidden rounded-lg border border-atlantia-rose/12 bg-white p-2 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md']) }}>
    <a href="{{ route('productos.show', $producto) }}" class="block">
        <div class="aspect-[4/3] overflow-hidden rounded-md bg-atlantia-cream">
            @if ($imageUrl)
                <img
                    src="{{ $imageUrl }}"
                    @if ($cardImage && $fullImage)
                        srcset="{{ $cardImage }} 600w, {{ $fullImage }} 1200w"
                        sizes="(min-width: 1280px) 18vw, (min-width: 640px) 45vw, 100vw"
                    @endif
                    alt="{{ $producto->nombre }}"
                    class="h-full w-full object-cover"
                    loading="lazy"
                >
            @else
                <div class="flex h-full items-center justify-center px-4 text-center text-sm font-semibold text-atlantia-ink/55">
                    Imagen no disponible
                </div>
            @endif
        </div>
    </a>

    <div class="space-y-3 p-2">
        <div>
            <p class="line-clamp-2 text-base font-semibold leading-5 text-atlantia-ink">{{ $producto->nombre }}</p>
            <p class="mt-1 text-xs text-atlantia-ink/60">{{ $producto->vendor?->business_name ?? 'Atlantia Supermarket' }}</p>
        </div>

        <div class="flex items-end justify-between gap-3">
            <div>
                <p class="text-2xl font-black tracking-tight text-atlantia-ink">Q{{ number_format($precioActual, 2) }}</p>
                <p class="text-xs text-atlantia-ink/55">/ {{ $unidad }}</p>
            </div>
            <span class="rounded-full px-2 py-1 text-[11px] font-bold {{ $stockDisponible ? 'bg-emerald-100 text-emerald-700' : 'bg-rose-100 text-rose-700' }}">
                {{ $stockDisponible ? 'Disponible' : 'Agotado' }}
            </span>
        </div>

        <form method="POST" action="{{ route('cliente.carrito.items.store') }}" class="space-y-2" x-data="{ qty: 1 }">
            @csrf
            <input type="hidden" name="producto_id" value="{{ $producto->id }}">
            <input type="hidden" name="cantidad" :value="qty">

            <div class="grid grid-cols-[40px_1fr_40px] overflow-hidden rounded-md border border-atlantia-rose/18">
                <button
                    type="button"
                    class="flex h-9 items-center justify-center bg-atlantia-cream text-lg font-bold text-atlantia-ink transition hover:bg-atlantia-blush"
                    @click="qty = Math.max(1, qty - 1)"
                    aria-label="Reducir cantidad"
                >
                    -
                </button>
                <div class="flex h-9 items-center justify-center bg-white text-sm font-semibold text-atlantia-ink" x-text="qty"></div>
                <button
                    type="button"
                    class="flex h-9 items-center justify-center bg-atlantia-cream text-lg font-bold text-atlantia-ink transition hover:bg-atlantia-blush"
                    @click="qty = qty + 1"
                    aria-label="Aumentar cantidad"
                >
                    +
                </button>
            </div>

            <button
                type="submit"
                class="inline-flex h-10 w-full items-center justify-center rounded-md bg-atlantia-wine px-4 text-sm font-bold uppercase tracking-wide text-white transition hover:bg-atlantia-wine-700 disabled:cursor-not-allowed disabled:opacity-60"
                aria-label="Agregar {{ $producto->nombre }} al carrito"
                @disabled(! $stockDisponible)
            >
                {{ $stockDisponible ? 'Agregar al carrito' : 'Sin stock por ahora' }}
            </button>
        </form>
    </div>
</article>
