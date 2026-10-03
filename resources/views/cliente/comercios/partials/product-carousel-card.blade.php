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
@endphp

<article
    class="group flex w-[13.5rem] shrink-0 snap-start flex-col overflow-hidden rounded-lg border border-atlantia-rose/15 bg-white shadow-sm transition duration-200 hover:-translate-y-0.5 hover:border-atlantia-rose/35 hover:shadow-[0_14px_30px_rgba(42,16,24,0.10)] sm:w-[14.5rem] xl:w-[15rem]"
    data-product-card
    data-product-name="{{ \Illuminate\Support\Str::lower($producto->nombre) }}"
>
    <div class="relative h-40 bg-gradient-to-b from-atlantia-blush/45 to-white p-3">
        @if ($discount !== null)
            <span class="absolute left-3 top-3 z-10 rounded-md bg-atlantia-wine px-2 py-1 text-[11px] font-black text-white">-{{ $discount }}%</span>
        @elseif ($stock > 0)
            <span class="absolute left-3 top-3 z-10 inline-flex items-center gap-1 rounded-md bg-emerald-50 px-2 py-1 text-[11px] font-black text-emerald-700">
                <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>
                Disponible
            </span>
        @endif

        <form method="POST" action="{{ route('cliente.wishlist.toggle', ['producto' => $producto->uuid]) }}" class="absolute right-3 top-3 z-10">
            @csrf
            <button type="submit" class="grid h-9 w-9 place-items-center rounded-full bg-white text-atlantia-wine shadow-sm transition hover:bg-atlantia-blush" aria-label="Guardar {{ $producto->nombre }} en favoritos">
                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                    <path d="M12 20s-7-4.4-7-10a4.2 4.2 0 0 1 7-3.1A4.2 4.2 0 0 1 19 10c0 5.6-7 10-7 10Z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/>
                </svg>
            </button>
        </form>

        <a href="{{ route('productos.show', ['producto' => $producto->uuid]) }}" class="grid h-full place-items-center rounded-md">
            @if ($imageUrl)
                <img src="{{ $imageUrl }}" alt="{{ $producto->nombre }}" class="max-h-full max-w-full object-contain transition duration-200 group-hover:scale-[1.03]" loading="lazy">
            @else
                <div class="grid h-28 w-full place-items-center rounded-md border border-dashed border-atlantia-rose/25 px-4 text-center text-xs font-bold text-atlantia-wine/65">
                    Imagen no disponible
                </div>
            @endif
        </a>
    </div>

    <div class="flex flex-1 flex-col p-4">
        <a href="{{ route('productos.show', ['producto' => $producto->uuid]) }}" class="block">
            <h3 class="line-clamp-2 min-h-10 text-sm font-black leading-5 text-atlantia-ink transition group-hover:text-atlantia-wine">{{ $producto->nombre }}</h3>
        </a>
        <p class="mt-1 text-xs font-semibold text-atlantia-ink/50">{{ $unitLabel }}</p>

        <div class="mt-auto flex items-end justify-between gap-3 pt-4">
            <div class="min-w-0">
                <p class="text-lg font-black text-atlantia-ink">{{ $money($price) }}</p>
                @if ($hasOffer)
                    <p class="text-xs font-bold text-atlantia-ink/35 line-through">{{ $money($basePrice) }}</p>
                @elseif ($stock < 1)
                    <p class="text-xs font-black text-red-600">Agotado</p>
                @endif
            </div>

            <form method="POST" action="{{ route('cliente.carrito.items.store') }}">
                @csrf
                <input type="hidden" name="producto_id" value="{{ $producto->id }}">
                <input type="hidden" name="cantidad" value="1">
                <button
                    type="submit"
                    class="grid h-10 w-10 place-items-center rounded-md bg-atlantia-wine text-white shadow-sm transition hover:bg-atlantia-wine-700 disabled:cursor-not-allowed disabled:bg-slate-300"
                    aria-label="Agregar {{ $producto->nombre }} al carrito"
                    @disabled($stock < 1)
                >
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                        <path d="M12 5v14M5 12h14" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                    </svg>
                </button>
            </form>
        </div>
    </div>
</article>
