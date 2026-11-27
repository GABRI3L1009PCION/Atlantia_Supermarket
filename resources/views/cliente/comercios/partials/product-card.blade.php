@php
    $legacyPath = $producto->imagenPrincipal?->path;
    $legacyImage = $legacyPath
        ? (str_starts_with($legacyPath, 'http') ? $legacyPath : asset('storage/' . $legacyPath))
        : null;
    $imageUrl = $producto->getFirstMediaUrl('productos', 'card') ?: $legacyImage;
    $stock = max(
        0,
        (int) ($producto->inventario?->stock_actual ?? 0)
            - (int) ($producto->inventario?->stock_reservado ?? 0)
    );
    $price = (float) ($producto->precio_oferta ?? $producto->precio_base);
    $basePrice = (float) $producto->precio_base;
    $hasOffer = $producto->precio_oferta !== null && $price < $basePrice;
    $discount = $hasOffer && $basePrice > 0
        ? (int) round((1 - ($price / $basePrice)) * 100)
        : null;
    $unitLabel = $producto->peso_gramos
        ? ($producto->peso_gramos >= 1000
            ? rtrim(rtrim(number_format($producto->peso_gramos / 1000, 1), '0'), '.') . ' kg'
            : number_format($producto->peso_gramos) . ' g')
        : ($producto->unidad_medida ?: 'unidad');
    $detalleUrl = route('productos.show', ['producto' => $producto->uuid]);
@endphp

<article
    class="group relative flex min-w-0 flex-col overflow-hidden rounded-xl border border-atlantia-rose/15 bg-white transition hover:border-atlantia-rose/40 hover:shadow-[0_10px_24px_rgba(58,0,4,0.08)]"
    data-product-card
    data-product-name="{{ \Illuminate\Support\Str::lower($producto->nombre) }}"
>
    <a href="{{ $detalleUrl }}" class="relative block aspect-square bg-[#f8f4f3] p-4">
        @if ($imageUrl)
            <img src="{{ $imageUrl }}" alt="{{ $producto->nombre }}" class="h-full w-full object-contain mix-blend-multiply transition duration-200 group-hover:scale-[1.04]" loading="lazy">
        @else
            <span class="grid h-full w-full place-items-center text-atlantia-rose/40">
                <svg class="h-12 w-12" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M4 7h16v12H4zM8 7V5h8v2M9 12h6" stroke="currentColor" stroke-width="1.5" stroke-linejoin="round"/></svg>
            </span>
        @endif

        @if ($discount !== null)
            <span class="absolute left-2.5 top-2.5 rounded-md bg-atlantia-wine px-2 py-0.5 text-[11px] font-black text-white">-{{ $discount }}%</span>
        @elseif ($stock < 1)
            <span class="absolute left-2.5 top-2.5 rounded-md bg-slate-700 px-2 py-0.5 text-[11px] font-black text-white">Agotado</span>
        @endif
    </a>

    <form method="POST" action="{{ route('cliente.wishlist.toggle', ['producto' => $producto->uuid]) }}" class="absolute right-2.5 top-2.5">
        @csrf
        <button type="submit" class="grid h-8 w-8 place-items-center rounded-full bg-white/90 text-atlantia-wine shadow-sm transition hover:bg-atlantia-blush" aria-label="Guardar {{ $producto->nombre }} en favoritos">
            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M12 20s-7-4.4-7-10a4.2 4.2 0 0 1 7-3.1A4.2 4.2 0 0 1 19 10c0 5.6-7 10-7 10Z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/></svg>
        </button>
    </form>

    <div class="flex flex-1 flex-col gap-1 p-3">
        <a href="{{ $detalleUrl }}" class="line-clamp-2 min-h-10 text-sm font-bold leading-5 text-atlantia-ink transition group-hover:text-atlantia-wine">{{ $producto->nombre }}</a>
        <p class="flex flex-wrap items-center gap-x-1.5 text-xs text-atlantia-ink/50">
            <span>{{ $unitLabel }}</span>
            <span aria-hidden="true">·</span>
            @if ($stock < 1)
                <span class="font-bold text-red-600" data-stock="0">Agotado</span>
            @elseif ($stock <= 5)
                <span class="font-bold text-amber-700" data-stock="{{ $stock }}">¡Quedan {{ $stock }}!</span>
            @else
                <span class="font-semibold text-emerald-700" data-stock="{{ $stock }}">{{ number_format($stock) }} disponibles</span>
            @endif
        </p>

        <div class="mt-auto flex items-end justify-between gap-2 pt-2">
            <div class="min-w-0 leading-tight">
                <p class="text-base font-black text-atlantia-ink">{{ $money($price) }}</p>
                @if ($hasOffer)
                    <p class="text-xs font-semibold text-atlantia-ink/40 line-through">{{ $money($basePrice) }}</p>
                @endif
            </div>

            <form method="POST" action="{{ route('cliente.carrito.items.store') }}">
                @csrf
                <input type="hidden" name="producto_id" value="{{ $producto->id }}">
                <input type="hidden" name="cantidad" value="1">
                <button
                    type="submit"
                    class="inline-flex h-9 items-center gap-1 rounded-lg bg-atlantia-wine px-3 text-xs font-black text-white transition hover:bg-atlantia-wine-700 disabled:cursor-not-allowed disabled:bg-slate-300"
                    aria-label="Agregar {{ $producto->nombre }} al carrito"
                    @disabled($stock < 1)
                >
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M12 5v14M5 12h14" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"/></svg>
                    <span class="hidden sm:inline">Agregar</span>
                </button>
            </form>
        </div>
    </div>
</article>
