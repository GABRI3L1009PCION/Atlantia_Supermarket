@props(['producto', 'saved' => false, 'etiqueta' => null, 'enlace' => null])
@php
    $legacyPath = $producto->imagenPrincipal?->path ?: $producto->imagen;
    $image = $producto->getFirstMediaUrl('productos', 'card') ?: ($legacyPath ? (str_starts_with($legacyPath, 'http') ? $legacyPath : asset('storage/'.ltrim($legacyPath, '/'))) : null);
    $available = max(0, (int) $producto->inventario?->stock_actual - (int) $producto->inventario?->stock_reservado);
    $offer = $producto->precio_oferta !== null && (float) $producto->precio_oferta < (float) $producto->precio_base;
    /* Los patrocinados pasan por un enlace que cuenta el clic para el anunciante. */
    $enlaceProducto = $enlace ?? route('productos.show', $producto->uuid);
@endphp
<article class="group flex h-full min-w-0 flex-col overflow-hidden rounded-xl border border-atlantia-rose/20 bg-white shadow-sm transition hover:border-atlantia-rose/40 hover:shadow-md" data-storefront-product="{{ $producto->id }}" data-vendor-id="{{ $producto->vendor_id }}">
    {{-- La foto va en posicion absoluta: el cuadro siempre mide lo mismo sin importar si la imagen es vertical u horizontal. --}}
    <div class="relative aspect-square w-full shrink-0 overflow-hidden bg-atlantia-blush/40 lg:aspect-[5/4]">
        <a href="{{ $enlaceProducto }}" class="absolute inset-0 flex items-center justify-center p-3" aria-label="Ver {{ $producto->nombre }}">
            @if ($image)
                <img src="{{ $image }}" alt="{{ $producto->nombre }}" loading="lazy" width="480" height="480" class="block h-full w-full object-contain transition duration-300 group-hover:scale-105">
            @else
                <span class="px-4 text-center text-xs text-atlantia-ink/45">Imagen no disponible</span>
            @endif
        </a>
        @if ($etiqueta)
            <span class="absolute left-2 top-2 rounded-md bg-amber-500 px-2 py-1 text-[10px] font-bold text-white" data-etiqueta-producto>{{ $etiqueta }}</span>
        @elseif ($offer)
            <span class="absolute left-2 top-2 rounded-md bg-atlantia-wine px-2 py-1 text-[10px] font-bold text-white">Oferta</span>
        @endif
        <form method="POST" action="{{ route('cliente.wishlist.toggle', $producto->uuid) }}" class="absolute right-2 top-2">
            @csrf
            <button type="submit" class="grid h-9 w-9 place-items-center rounded-full border border-atlantia-rose/15 bg-white/95 text-atlantia-wine shadow-sm transition hover:bg-atlantia-blush" aria-label="{{ $saved ? 'Quitar' : 'Guardar' }} {{ $producto->nombre }} {{ $saved ? 'de' : 'en' }} favoritos" aria-pressed="{{ $saved ? 'true' : 'false' }}">
                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="{{ $saved ? 'currentColor' : 'none' }}" aria-hidden="true"><path d="M12 20s-7-4.4-7-10a4.2 4.2 0 0 1 7-3.1A4.2 4.2 0 0 1 19 10c0 5.6-7 10-7 10Z" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"/></svg>
            </button>
        </form>
    </div>
    <div class="flex flex-1 flex-col p-3">
        <a href="{{ route('comercios.show', $producto->vendor->slug) }}" class="truncate text-[10px] font-bold uppercase tracking-wide text-atlantia-wine hover:underline" title="{{ $producto->vendor->business_name }}">{{ $producto->vendor->business_name }}</a>
        <h3 class="mt-1 line-clamp-2 min-h-10 text-sm font-semibold leading-5 text-atlantia-ink"><a href="{{ $enlaceProducto }}">{{ $producto->nombre }}</a></h3>
        {{-- Precio, precio anterior y stock ocupan siempre el mismo alto para que todas las tarjetas queden alineadas. --}}
        {{-- Movil: precio, precio anterior y stock en columna. Computadora: precio y stock en la misma fila. --}}
        <div class="mt-2 flex flex-col items-start lg:flex-row lg:items-center lg:justify-between lg:gap-2">
            <p class="whitespace-nowrap text-base font-black leading-6 text-slate-950 sm:text-lg">Q {{ number_format((float) ($producto->precio_oferta ?? $producto->precio_base), 2) }}</p>
            <p @class(['h-4 text-xs leading-4 text-atlantia-ink/45 line-through lg:hidden', 'invisible' => ! $offer]) @if (! $offer) aria-hidden="true" @endif>Q {{ number_format((float) $producto->precio_base, 2) }}</p>
            <span @class([
                'mt-1.5 whitespace-nowrap rounded px-2 py-0.5 text-[10px] font-semibold leading-4 sm:text-[11px] lg:mt-0',
                'bg-emerald-50 text-emerald-700' => $available > 5,
                'bg-amber-50 text-amber-700' => $available > 0 && $available <= 5,
                'bg-slate-100 text-slate-500' => $available === 0,
            ]) data-stock-available="{{ $available }}">
                @if ($available === 0)
                    Agotado
                @elseif ($available <= 5)
                    ¡Quedan {{ $available }}!
                @else
                    {{ $available }} disponibles
                @endif
            </span>
        </div>
        <p @class(['hidden h-4 text-xs leading-4 text-atlantia-ink/45 line-through lg:block', 'invisible' => ! $offer]) @if (! $offer) aria-hidden="true" @endif>Q {{ number_format((float) $producto->precio_base, 2) }}</p>
        <form method="POST" action="{{ route('cliente.carrito.items.store') }}" class="mt-auto pt-3">
            @csrf
            <input type="hidden" name="producto_id" value="{{ $producto->id }}">
            <input type="hidden" name="cantidad" value="1">
            <button type="submit" @disabled($available < 1) class="min-h-9 w-full rounded-md bg-atlantia-wine px-2 py-1.5 text-[11px] font-bold text-white transition hover:bg-atlantia-wine-700 disabled:cursor-not-allowed disabled:bg-slate-100 disabled:text-slate-400 sm:text-sm" aria-label="Agregar {{ $producto->nombre }} al carrito">{{ $available > 0 ? 'Agregar al carrito' : 'Sin existencias' }}</button>
        </form>
    </div>
</article>
