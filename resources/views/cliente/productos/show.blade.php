@extends('layouts.marketplace')

@section('content')
    @php
        $legacyPath = $producto->imagenPrincipal?->path;
        $legacyImage = $legacyPath
            ? (str_starts_with($legacyPath, 'http') ? $legacyPath : asset('storage/' . $legacyPath))
            : null;
        $imageUrl = $producto->getFirstMediaUrl('productos', 'large')
            ?: $producto->getFirstMediaUrl('productos')
            ?: $legacyImage;
        $price = (float) ($producto->precio_oferta ?? $producto->precio_base);
        $basePrice = (float) $producto->precio_base;
        $hasOffer = $producto->precio_oferta !== null && $price < $basePrice;
        $stock = max(
            0,
            (int) ($producto->inventario?->stock_actual ?? 0)
                - (int) ($producto->inventario?->stock_reservado ?? 0)
        );
        $unitLabel = $producto->peso_gramos
            ? ($producto->peso_gramos >= 1000
                ? rtrim(rtrim(number_format($producto->peso_gramos / 1000, 1), '0'), '.') . ' kg'
                : number_format($producto->peso_gramos) . ' g')
            : ($producto->unidad_medida ?: 'unidad');
    @endphp

    <section class="bg-[#fffafb] px-4 py-5 sm:px-6 sm:py-8 lg:px-8">
        <div class="mx-auto w-full max-w-6xl">
            <a href="{{ $producto->vendor ? route('comercios.show', ['vendor' => $producto->vendor->slug]) : route('comercios.index') }}" class="inline-flex items-center gap-2 text-xs font-black text-atlantia-wine sm:text-sm">
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="m15 5-7 7 7 7" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                Volver a la tienda
            </a>

            <div class="mt-4 grid overflow-hidden rounded-lg border border-atlantia-rose/15 bg-white shadow-[0_14px_36px_rgba(42,16,24,0.08)] md:grid-cols-[minmax(0,1fr)_minmax(22rem,0.85fr)]">
                <div class="relative grid min-h-80 place-items-center bg-gradient-to-b from-atlantia-blush/55 to-white p-6 sm:min-h-[28rem]">
                    @if ($hasOffer)
                        <span class="absolute left-4 top-4 rounded-md bg-atlantia-wine px-3 py-1.5 text-xs font-black text-white">Oferta</span>
                    @endif

                    <form method="POST" action="{{ route('cliente.wishlist.toggle', ['producto' => $producto->uuid]) }}" class="absolute right-4 top-4">
                        @csrf
                        <button class="grid h-11 w-11 place-items-center rounded-full bg-white text-atlantia-wine shadow-sm" aria-label="Guardar {{ $producto->nombre }} en favoritos">
                            <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M12 20s-7-4.4-7-10a4.2 4.2 0 0 1 7-3.1A4.2 4.2 0 0 1 19 10c0 5.6-7 10-7 10Z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/></svg>
                        </button>
                    </form>

                    @if ($imageUrl)
                        <img src="{{ $imageUrl }}" alt="{{ $producto->nombre }}" class="max-h-72 max-w-full object-contain sm:max-h-96">
                    @else
                        <div class="grid h-52 w-full max-w-sm place-items-center rounded-lg border border-dashed border-atlantia-rose/30 text-sm font-black text-atlantia-wine/60">
                            Imagen no disponible
                        </div>
                    @endif
                </div>

                <div class="flex flex-col p-5 sm:p-8">
                    <p class="text-xs font-black uppercase text-atlantia-rose">{{ $producto->categoria?->nombre ?? 'Producto' }}</p>
                    <h1 class="mt-2 text-2xl font-black leading-tight text-atlantia-ink sm:text-3xl">{{ $producto->nombre }}</h1>
                    <a href="{{ $producto->vendor ? route('comercios.show', ['vendor' => $producto->vendor->slug]) : route('comercios.index') }}" class="mt-2 text-sm font-bold text-atlantia-wine">
                        {{ $producto->vendor?->business_name ?? 'Atlantia Delivery' }}
                    </a>

                    <div class="mt-5 flex flex-wrap items-end gap-3">
                        <span class="text-3xl font-black text-atlantia-wine">Q{{ number_format($price, 2) }}</span>
                        @if ($hasOffer)
                            <span class="pb-1 text-base font-bold text-atlantia-ink/35 line-through">Q{{ number_format($basePrice, 2) }}</span>
                        @endif
                    </div>
                    <p class="mt-1 text-sm font-semibold text-atlantia-ink/50">{{ $unitLabel }}</p>

                    <div class="mt-5 rounded-lg border border-atlantia-rose/15 bg-atlantia-blush/40 p-4">
                        <p class="text-sm font-black {{ $stock > 0 ? 'text-emerald-700' : 'text-red-600' }}">
                            {{ $stock > 0 ? 'Disponible para entrega' : 'Producto agotado' }}
                        </p>
                        @if ($producto->requiere_refrigeracion)
                            <p class="mt-1 text-xs font-semibold text-atlantia-ink/55">Este producto requiere refrigeracion.</p>
                        @endif
                    </div>

                    @if ($producto->descripcion)
                        <div class="mt-5">
                            <h2 class="text-sm font-black text-atlantia-ink">Descripcion</h2>
                            <p class="mt-2 text-sm leading-6 text-atlantia-ink/60">{{ $producto->descripcion }}</p>
                        </div>
                    @endif

                    <form method="POST" action="{{ route('cliente.carrito.items.store') }}" class="mt-auto pt-6">
                        @csrf
                        <input type="hidden" name="producto_id" value="{{ $producto->id }}">
                        <div class="grid grid-cols-[7rem_1fr] gap-3">
                            <label class="sr-only" for="product-detail-quantity">Cantidad</label>
                            <input id="product-detail-quantity" name="cantidad" type="number" min="1" max="99" value="1" class="h-12 rounded-lg border border-atlantia-rose/20 px-3 text-center text-sm font-black text-atlantia-ink focus:border-atlantia-wine focus:ring-atlantia-blush">
                            <button class="h-12 rounded-lg bg-atlantia-wine px-4 text-sm font-black text-white shadow-sm disabled:cursor-not-allowed disabled:bg-slate-300" @disabled($stock < 1)>
                                Agregar al carrito
                            </button>
                        </div>
                    </form>

                    <div class="mt-4 grid grid-cols-2 gap-2 text-center text-[11px] font-bold text-atlantia-ink/55">
                        <span class="rounded-md border border-atlantia-rose/10 px-2 py-2">Stock sincronizado</span>
                        <span class="rounded-md border border-atlantia-rose/10 px-2 py-2">Compra protegida</span>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
