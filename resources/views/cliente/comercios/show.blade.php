@extends('layouts.marketplace')

@section('content')
    @php
        $money = static fn ($value): string => 'Q ' . number_format((float) $value, 2);
        $initials = \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($vendor->business_name, 0, 2));
        $storeCategory = $vendor->business_category ?: 'Comercio local';
        $ratingValue = $rating['rating'] ? number_format((float) $rating['rating'], 1) : 'Nuevo';
        $ratingTotal = (int) $rating['total'];
        $coverImage = $coverUrl ?: asset('images/fondo.png');
        $logoImage = $logoUrl;
        $isAtlantiaStore = \Illuminate\Support\Str::contains(\Illuminate\Support\Str::lower($vendor->business_name), 'atlantia');
        $deliveryMax = $estimatedTime + 15;
        $freeShippingThreshold = max(75, $minimumOrder * 4);
        $categories = $productsByCategory->keys()->values();
        $paymentMethods = collect([
            $vendor->accepts_cash ? 'Efectivo' : null,
            $vendor->accepts_transfer ? 'Transferencia' : null,
            $vendor->accepts_card ? 'Tarjeta' : null,
        ])->filter()->values();
        $cartSubtotal = $cartItems->sum(fn ($item) => (float) $item->precio_unitario_snapshot * (int) $item->cantidad);
        $cartDelivery = $cartItems->isEmpty() ? 0 : (float) $deliveryFee;
        $cartTotal = $cartSubtotal + $cartDelivery;
    @endphp

    <div class="bg-white">
        <div class="mx-auto w-full max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
            <a href="{{ route('comercios.index') }}" class="inline-flex items-center gap-2 text-sm font-bold text-atlantia-ink/70 transition hover:text-atlantia-wine">
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                    <path d="M19 12H5m6-6-6 6 6 6" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
                Volver a comercios
            </a>

            <section class="mt-6 overflow-hidden rounded-2xl border border-atlantia-rose/15 bg-white shadow-[0_18px_45px_rgba(42,16,24,0.09)]">
                <div class="relative min-h-[17rem]">
                    <img src="{{ $coverImage }}" alt="{{ $vendor->business_name }}" class="absolute inset-0 h-full w-full object-cover">
                    <div class="absolute inset-0 bg-gradient-to-r from-white via-white/88 to-white/5"></div>
                    <div class="absolute inset-y-0 right-0 hidden w-1/2 bg-gradient-to-l from-black/10 to-transparent lg:block"></div>

                    <div class="relative grid min-h-[17rem] gap-5 p-5 sm:p-7 lg:grid-cols-[180px_minmax(0,1fr)_96px] lg:items-center">
                        <div class="flex items-center justify-center">
                            <div class="grid h-32 w-32 place-items-center overflow-hidden rounded-2xl border border-atlantia-rose/15 bg-atlantia-blush p-4 text-4xl font-black text-atlantia-wine shadow-[0_18px_38px_rgba(42,16,24,0.14)] sm:h-40 sm:w-40">
                                @if ($isAtlantiaStore)
                                    <div class="text-center text-atlantia-wine">
                                        <svg class="mx-auto h-12 w-12 sm:h-16 sm:w-16" viewBox="0 0 64 64" fill="none" aria-hidden="true">
                                            <circle cx="32" cy="32" r="28" fill="white" stroke="currentColor" stroke-width="4"/>
                                            <path d="M21 23h5l4 19h18l4-13H30" stroke="currentColor" stroke-width="4" stroke-linecap="round" stroke-linejoin="round"/>
                                            <circle cx="34" cy="47" r="3" fill="currentColor"/>
                                            <circle cx="47" cy="47" r="3" fill="currentColor"/>
                                        </svg>
                                        <span class="mt-2 block text-xl font-black leading-none sm:text-2xl">Atlantia</span>
                                        <span class="mt-1 block text-[10px] font-black uppercase tracking-[0.18em] sm:text-xs sm:tracking-[0.22em]">Supermarket</span>
                                    </div>
                                @elseif ($logoImage)
                                    <img src="{{ $logoImage }}" alt="Logo {{ $vendor->business_name }}" class="max-h-full max-w-full object-contain">
                                @else
                                    {{ $initials }}
                                @endif
                            </div>
                        </div>

                        <div class="min-w-0">
                            <div class="flex flex-wrap items-center gap-2">
                                <h1 class="text-3xl font-black leading-tight text-atlantia-ink sm:text-4xl">{{ $vendor->business_name }}</h1>
                                <span class="grid h-6 w-6 place-items-center rounded-full bg-atlantia-wine text-white">
                                    <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                        <path d="M5 12l4 4L19 6" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"/>
                                    </svg>
                                </span>
                            </div>

                            <div class="mt-3 flex flex-wrap items-center gap-3 text-sm font-semibold text-atlantia-ink/65">
                                <span>{{ $storeCategory }}</span>
                                <span class="inline-flex items-center gap-1">
                                    <svg class="h-4 w-4 fill-amber-400 text-amber-400" viewBox="0 0 24 24" aria-hidden="true">
                                        <path d="m12 3 2.8 5.7 6.2.9-4.5 4.4 1.1 6.2L12 17.3 6.4 20.2 7.5 14 3 9.6l6.2-.9L12 3Z"/>
                                    </svg>
                                    <span class="font-black text-atlantia-ink">{{ $ratingValue }}</span>
                                    <span>({{ number_format($ratingTotal) }})</span>
                                </span>
                                <span>{{ $distanceKm }} km</span>
                            </div>

                            <div class="mt-4 flex flex-wrap items-center gap-3 text-sm">
                                <span class="rounded-full bg-emerald-50 px-3 py-1 font-black text-emerald-700">Abierto</span>
                                <span class="font-semibold text-atlantia-ink/65">Cierra a las 10:00 PM</span>
                            </div>

                            <div class="mt-4 flex flex-wrap items-center gap-3 text-sm font-semibold text-atlantia-ink/70">
                                <span class="inline-flex items-center gap-2">
                                    <svg class="h-5 w-5 text-atlantia-wine" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                        <path d="M4 16h9l3-6H7l-3 6ZM2 16h2M16 16h3M7 19a2 2 0 1 0 0-4 2 2 0 0 0 0 4ZM18 19a2 2 0 1 0 0-4 2 2 0 0 0 0 4Z" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/>
                                    </svg>
                                    Entrega desde {{ $money($deliveryFee) }}
                                </span>
                                <span>{{ $estimatedTime }}-{{ $deliveryMax }} min</span>
                                <span>Min. {{ $money($minimumOrder) }}</span>
                            </div>

                            <div class="mt-5 flex flex-wrap gap-2">
                                <span class="rounded-full bg-atlantia-blush px-3 py-1.5 text-xs font-black text-atlantia-wine">{{ $storeCategory }}</span>
                                <span class="rounded-full bg-atlantia-blush px-3 py-1.5 text-xs font-black text-atlantia-wine">Productos frescos</span>
                                <span class="rounded-full bg-atlantia-blush px-3 py-1.5 text-xs font-black text-atlantia-wine">Precios bajos</span>
                            </div>
                        </div>

                        <div class="absolute right-4 top-4 flex flex-col gap-3 sm:right-6 sm:top-6">
                            <button type="button" class="grid h-12 w-12 place-items-center rounded-full border border-atlantia-rose/15 bg-white text-atlantia-wine shadow-sm transition hover:bg-atlantia-blush" aria-label="Guardar comercio">
                                <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                    <path d="M12 20s-7-4.4-7-10a4.2 4.2 0 0 1 7-3.1A4.2 4.2 0 0 1 19 10c0 5.6-7 10-7 10Z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/>
                                </svg>
                            </button>
                            <button type="button" class="grid h-12 w-12 place-items-center rounded-full border border-atlantia-rose/15 bg-white text-atlantia-ink shadow-sm transition hover:bg-atlantia-blush hover:text-atlantia-wine" aria-label="Compartir comercio">
                                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                    <path d="M18 8a3 3 0 1 0-2.8-4.1M6 14a3 3 0 1 0 0 6 3 3 0 0 0 0-6Zm12 1a3 3 0 1 0 0 6 3 3 0 0 0 0-6ZM8.6 15.3l6.8-4.1M8.7 18.5l6.6 1.9" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>
            </section>

            <nav class="mt-7 flex gap-8 border-b border-atlantia-rose/15 text-sm font-black text-atlantia-ink" aria-label="Secciones del comercio">
                <a href="#productos" class="border-b-2 border-atlantia-rose px-1 pb-4 text-atlantia-wine">Productos</a>
                <a href="#informacion" class="border-b-2 border-transparent px-1 pb-4 transition hover:text-atlantia-wine">Informacion</a>
                <a href="#opiniones" class="border-b-2 border-transparent px-1 pb-4 transition hover:text-atlantia-wine">Opiniones ({{ number_format($ratingTotal) }})</a>
            </nav>

            <section id="productos" class="mt-7 grid gap-6 lg:grid-cols-[200px_minmax(0,1fr)_300px]">
                <aside class="min-w-0 lg:sticky lg:top-28 lg:self-start">
                    <div class="min-w-0 rounded-2xl border border-atlantia-rose/15 bg-white p-4 shadow-sm">
                        <h2 class="text-base font-black text-atlantia-ink">Categorias</h2>
                        <nav class="mt-4 flex max-w-full gap-2 overflow-x-auto pb-1 lg:flex-col lg:overflow-visible" aria-label="Categorias del comercio">
                            <a href="#productos" class="flex shrink-0 items-center gap-3 rounded-xl bg-atlantia-blush px-3 py-3 text-sm font-black text-atlantia-wine lg:w-full">
                                <span class="grid h-6 w-6 place-items-center rounded-md border border-atlantia-rose/20">
                                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M4 4h7v7H4V4Zm9 0h7v7h-7V4ZM4 13h7v7H4v-7Zm9 0h7v7h-7v-7Z" stroke="currentColor" stroke-width="1.7"/></svg>
                                </span>
                                Todas
                            </a>
                            @foreach ($categories as $categoryName)
                                <a href="#categoria-{{ \Illuminate\Support\Str::slug($categoryName) }}" class="flex shrink-0 items-center gap-3 rounded-xl px-3 py-3 text-sm font-bold text-atlantia-ink/75 transition hover:bg-atlantia-blush hover:text-atlantia-wine lg:w-full">
                                    <span class="grid h-6 w-6 place-items-center rounded-md border border-atlantia-rose/20 text-atlantia-wine">
                                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M5 7h14M7 4v16M17 4v16M5 17h14" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/></svg>
                                    </span>
                                    <span class="whitespace-nowrap lg:whitespace-normal">{{ $categoryName }}</span>
                                </a>
                            @endforeach
                        </nav>
                    </div>
                </aside>

                <div class="min-w-0 space-y-7">
                    <div class="grid gap-4 rounded-2xl border border-atlantia-rose/15 bg-atlantia-blush/70 p-4 sm:grid-cols-[auto_1fr_auto] sm:items-center">
                        <span class="grid h-14 w-14 place-items-center rounded-full bg-atlantia-wine text-white">
                            <svg class="h-7 w-7" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                <path d="M20 12v8H4v-8M3 8h18v4H3V8ZM12 8v12M12 8c-3 0-4-1.3-4-3a2 2 0 0 1 3.4-1.4C12.6 4.8 12 8 12 8Zm0 0s-.6-3.2.6-4.4A2 2 0 0 1 16 5c0 1.7-1 3-4 3Z" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </span>
                        <div>
                            <h2 class="text-xl font-black text-atlantia-wine">Envio gratis!</h2>
                            <p class="mt-1 text-sm font-semibold text-atlantia-ink/65">En pedidos desde {{ $money($freeShippingThreshold) }}</p>
                        </div>
                        <div class="hidden h-16 w-32 items-center justify-center text-atlantia-wine sm:flex">
                            <svg class="h-16 w-28" viewBox="0 0 120 64" fill="none" aria-hidden="true">
                                <path d="M22 42h50l12-20H38L22 42Z" fill="#8b123d" opacity=".92"/>
                                <path d="M12 42h11M73 42h21" stroke="#8b123d" stroke-width="4" stroke-linecap="round"/>
                                <circle cx="34" cy="48" r="7" fill="#fff" stroke="#8b123d" stroke-width="4"/>
                                <circle cx="86" cy="48" r="7" fill="#fff" stroke="#8b123d" stroke-width="4"/>
                                <path d="M41 20h27l8 8H50" stroke="#8b123d" stroke-width="4" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </div>
                    </div>

                    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                        <h2 class="text-2xl font-black text-atlantia-ink">Productos destacados</h2>
                        <form method="GET" action="{{ route('comercios.show', ['vendor' => $vendor->slug]) }}" class="flex items-center gap-3">
                            <label for="orden-productos" class="text-sm font-semibold text-atlantia-ink/65">Ordenar por</label>
                            <select id="orden-productos" name="orden" data-auto-submit class="h-11 rounded-lg border border-atlantia-rose/20 bg-white px-3 text-sm font-bold text-atlantia-ink outline-none focus:border-atlantia-wine focus:ring-2 focus:ring-atlantia-blush">
                                <option value="relevancia" @selected($sort === 'relevancia')>Relevancia</option>
                                <option value="precio_asc" @selected($sort === 'precio_asc')>Menor precio</option>
                                <option value="precio_desc" @selected($sort === 'precio_desc')>Mayor precio</option>
                                <option value="nombre" @selected($sort === 'nombre')>Nombre</option>
                            </select>
                        </form>
                    </div>

                    @forelse ($productsByCategory as $categoryName => $categoryProducts)
                        <section id="categoria-{{ \Illuminate\Support\Str::slug($categoryName) }}" class="scroll-mt-28">
                            @if ($productsByCategory->count() > 1)
                                <div class="mb-4 flex items-center justify-between gap-3">
                                    <h3 class="text-lg font-black text-atlantia-ink">{{ $categoryName }}</h3>
                                    <span class="rounded-full bg-atlantia-blush px-3 py-1 text-xs font-black text-atlantia-wine">{{ number_format($categoryProducts->count()) }} productos</span>
                                </div>
                            @endif

                            <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
                                @foreach ($categoryProducts as $producto)
                                    @php
                                        $legacyPath = $producto->imagenPrincipal?->path;
                                        $legacyImage = $legacyPath ? (str_starts_with($legacyPath, 'http') ? $legacyPath : asset('storage/' . $legacyPath)) : null;
                                        $imageUrl = $producto->getFirstMediaUrl('productos', 'card') ?: $legacyImage;
                                        $stock = max(0, (int) ($producto->inventario?->stock_actual ?? 0) - (int) ($producto->inventario?->stock_reservado ?? 0));
                                        $price = (float) ($producto->precio_oferta ?? $producto->precio_base);
                                        $basePrice = (float) $producto->precio_base;
                                        $hasOffer = $producto->precio_oferta !== null && $price < $basePrice;
                                        $discount = $hasOffer && $basePrice > 0 ? (int) round((1 - ($price / $basePrice)) * 100) : null;
                                        $unitLabel = $producto->peso_gramos
                                            ? ($producto->peso_gramos >= 1000 ? rtrim(rtrim(number_format($producto->peso_gramos / 1000, 1), '0'), '.') . ' kg' : number_format($producto->peso_gramos) . ' g')
                                            : ($producto->unidad_medida ?: 'unidad');
                                        $quantityId = 'cantidad-producto-' . $producto->id;
                                    @endphp

                                    <article class="overflow-hidden rounded-2xl border border-atlantia-rose/15 bg-white shadow-sm transition hover:-translate-y-0.5 hover:shadow-[0_18px_38px_rgba(42,16,24,0.11)]">
                                        <div class="relative h-44 bg-atlantia-blush/50 p-4">
                                            @if ($discount !== null)
                                                <span class="absolute left-3 top-3 z-10 rounded-md bg-atlantia-wine px-2 py-1 text-xs font-black text-white">-{{ $discount }}%</span>
                                            @elseif ($producto->publicado_at && $producto->publicado_at->gt(now()->subDays(14)))
                                                <span class="absolute left-3 top-3 z-10 rounded-md bg-emerald-500 px-2 py-1 text-xs font-black text-white">Nuevo</span>
                                            @endif

                                            <form method="POST" action="{{ route('cliente.wishlist.toggle', ['producto' => $producto->uuid]) }}" class="absolute right-3 top-3 z-10">
                                                @csrf
                                                <button type="submit" class="grid h-10 w-10 place-items-center rounded-full bg-white text-atlantia-wine shadow-sm transition hover:bg-atlantia-blush" aria-label="Guardar {{ $producto->nombre }}">
                                                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                                        <path d="M12 20s-7-4.4-7-10a4.2 4.2 0 0 1 7-3.1A4.2 4.2 0 0 1 19 10c0 5.6-7 10-7 10Z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/>
                                                    </svg>
                                                </button>
                                            </form>

                                            <a href="{{ route('productos.show', ['producto' => $producto->uuid]) }}" class="grid h-full place-items-center">
                                                @if ($imageUrl)
                                                    <img src="{{ $imageUrl }}" alt="{{ $producto->nombre }}" class="max-h-full max-w-full object-contain mix-blend-multiply" loading="lazy">
                                                @else
                                                    <div class="grid h-full w-full place-items-center rounded-xl border border-dashed border-atlantia-rose/25 bg-white px-4 text-center text-xs font-black text-atlantia-wine">
                                                        Sin imagen
                                                    </div>
                                                @endif
                                            </a>
                                        </div>

                                        <div class="space-y-3 p-4">
                                            <div>
                                                <a href="{{ route('productos.show', ['producto' => $producto->uuid]) }}" class="block">
                                                    <h3 class="line-clamp-2 min-h-11 text-base font-black leading-5 text-atlantia-ink hover:text-atlantia-wine">{{ $producto->nombre }}</h3>
                                                </a>
                                                <p class="mt-1 text-sm font-semibold text-atlantia-ink/55">{{ $unitLabel }}</p>
                                            </div>

                                            <div class="flex min-h-7 items-end gap-2">
                                                <span class="text-lg font-black text-atlantia-ink">{{ $money($price) }}</span>
                                                @if ($hasOffer)
                                                    <span class="pb-0.5 text-sm font-bold text-atlantia-ink/40 line-through">{{ $money($basePrice) }}</span>
                                                @endif
                                            </div>

                                            <form method="POST" action="{{ route('cliente.carrito.items.store') }}" class="space-y-2">
                                                @csrf
                                                <input type="hidden" name="producto_id" value="{{ $producto->id }}">
                                                <div class="grid h-10 grid-cols-[36px_1fr_36px] overflow-hidden rounded-lg border border-atlantia-rose/20 bg-white">
                                                    <button type="button" class="grid place-items-center text-atlantia-wine transition hover:bg-atlantia-blush" data-quantity-step="-1" data-quantity-target="{{ $quantityId }}" aria-label="Reducir cantidad">-</button>
                                                    <input id="{{ $quantityId }}" name="cantidad" type="number" min="1" max="99" value="1" class="h-full border-0 bg-white px-2 text-center text-sm font-black text-atlantia-ink focus:ring-0">
                                                    <button type="button" class="grid place-items-center text-atlantia-wine transition hover:bg-atlantia-blush" data-quantity-step="1" data-quantity-target="{{ $quantityId }}" aria-label="Aumentar cantidad">+</button>
                                                </div>
                                                <button class="h-11 w-full rounded-lg bg-atlantia-wine px-4 text-sm font-black text-white transition hover:bg-atlantia-wine-700 disabled:cursor-not-allowed disabled:bg-slate-300" @disabled($stock < 1)>
                                                    Agregar
                                                </button>
                                            </form>
                                        </div>
                                    </article>
                                @endforeach
                            </div>
                        </section>
                    @empty
                        <div class="rounded-2xl border border-dashed border-atlantia-rose/25 bg-white p-10 text-center">
                            <h2 class="text-lg font-black text-atlantia-ink">Este comercio aun no tiene productos publicados</h2>
                            <p class="mt-2 text-sm text-atlantia-ink/60">Cuando el negocio active su catalogo, aparecera aqui.</p>
                        </div>
                    @endforelse
                </div>

                <aside class="space-y-5 lg:sticky lg:top-28 lg:self-start">
                    <section class="rounded-2xl border border-atlantia-rose/15 bg-white p-4 shadow-sm">
                        <div class="mb-4 flex items-center justify-between gap-3">
                            <h2 class="text-lg font-black text-atlantia-ink">Tu pedido</h2>
                            <svg class="h-6 w-6 text-atlantia-ink/70" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                <path d="M6 8h12l1 13H5L6 8Zm3 0a3 3 0 0 1 6 0" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round"/>
                            </svg>
                        </div>

                        @if ($cartItems->isEmpty())
                            <div class="rounded-xl bg-atlantia-blush/60 p-4 text-center">
                                <p class="text-sm font-black text-atlantia-ink">Aun no agregaste productos</p>
                                <p class="mt-1 text-xs font-semibold leading-5 text-atlantia-ink/55">Selecciona cantidades y prepara tu pedido desde los productos.</p>
                            </div>
                        @else
                            <div class="divide-y divide-atlantia-rose/10">
                                @foreach ($cartItems as $item)
                                    @php
                                        $itemProduct = $item->producto;
                                        $itemPath = $itemProduct?->imagenPrincipal?->path;
                                        $itemImage = $itemProduct?->getFirstMediaUrl('productos', 'thumb')
                                            ?: ($itemPath ? (str_starts_with($itemPath, 'http') ? $itemPath : asset('storage/' . $itemPath)) : null);
                                    @endphp
                                    <article class="grid grid-cols-[54px_1fr] gap-3 py-3">
                                        <div class="h-14 w-14 overflow-hidden rounded-lg border border-atlantia-rose/15 bg-atlantia-blush">
                                            @if ($itemImage)
                                                <img src="{{ $itemImage }}" alt="{{ $itemProduct?->nombre ?? 'Producto' }}" class="h-full w-full object-contain" loading="lazy">
                                            @endif
                                        </div>
                                        <div class="min-w-0">
                                            <div class="flex items-start justify-between gap-2">
                                                <div class="min-w-0">
                                                    <h3 class="line-clamp-2 text-sm font-black leading-5 text-atlantia-ink">{{ $itemProduct?->nombre ?? 'Producto' }}</h3>
                                                    <p class="mt-0.5 text-xs font-semibold text-atlantia-ink/55">{{ $item->cantidad }} unidad(es)</p>
                                                </div>
                                                <form method="POST" action="{{ route('cliente.carrito.items.destroy', $item) }}">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="text-atlantia-ink/40 transition hover:text-red-600" aria-label="Quitar {{ $itemProduct?->nombre ?? 'Producto' }}">x</button>
                                                </form>
                                            </div>
                                            <div class="mt-2 flex items-center justify-between gap-2">
                                                <span class="text-sm font-black text-atlantia-ink">{{ $money($item->precio_unitario_snapshot) }}</span>
                                                <form method="POST" action="{{ route('cliente.carrito.items.update', $item) }}" class="grid h-8 grid-cols-[28px_34px_28px] overflow-hidden rounded-md border border-atlantia-rose/15">
                                                    @csrf
                                                    @method('PUT')
                                                    <button type="submit" class="text-atlantia-wine" data-quantity-submit data-quantity-step="-1" data-quantity-target="cantidad-carrito-{{ $item->id }}" aria-label="Reducir y actualizar">-</button>
                                                    <input id="cantidad-carrito-{{ $item->id }}" name="cantidad" type="number" min="1" max="99" value="{{ $item->cantidad }}" class="h-full border-0 px-1 text-center text-xs font-black focus:ring-0">
                                                    <button type="submit" class="text-atlantia-wine" data-quantity-submit data-quantity-target="cantidad-carrito-{{ $item->id }}" data-quantity-step="1" aria-label="Aumentar y actualizar">+</button>
                                                </form>
                                            </div>
                                        </div>
                                    </article>
                                @endforeach
                            </div>

                            <div class="mt-4 space-y-2 border-t border-atlantia-rose/15 pt-4 text-sm">
                                <div class="flex items-center justify-between gap-3">
                                    <span class="text-atlantia-ink/65">Subtotal</span>
                                    <span class="font-black text-atlantia-ink">{{ $money($cartSubtotal) }}</span>
                                </div>
                                <div class="flex items-center justify-between gap-3">
                                    <span class="text-atlantia-ink/65">Entrega</span>
                                    <span class="font-black text-atlantia-ink">{{ $money($cartDelivery) }}</span>
                                </div>
                                <div class="flex items-center justify-between gap-3 border-t border-atlantia-rose/15 pt-3">
                                    <span class="font-black text-atlantia-ink">Total</span>
                                    <span class="text-lg font-black text-atlantia-wine">{{ $money($cartTotal) }}</span>
                                </div>
                            </div>
                        @endif

                        <div class="mt-4 space-y-2">
                            <a href="{{ route('cliente.checkout.create') }}" class="{{ $cartItems->isEmpty() ? 'pointer-events-none bg-slate-300 text-white' : 'bg-atlantia-wine text-white hover:bg-atlantia-wine-700' }} flex h-12 w-full items-center justify-center rounded-lg px-4 text-sm font-black transition">
                                Continuar pedido
                            </a>
                            <a href="{{ route('cliente.carrito.index') }}" class="flex h-11 w-full items-center justify-center rounded-lg border border-atlantia-rose/35 bg-white px-4 text-sm font-black text-atlantia-wine transition hover:bg-atlantia-blush">
                                Ver carrito
                            </a>
                        </div>
                    </section>

                    <section class="rounded-2xl border border-emerald-100 bg-emerald-50 p-4 text-emerald-950 shadow-sm">
                        <div class="flex items-start gap-3">
                            <span class="grid h-9 w-9 shrink-0 place-items-center rounded-full bg-white text-emerald-700">
                                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M12 8v5l3 2M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>
                            </span>
                            <div>
                                <h3 class="text-sm font-black">Entrega estimada</h3>
                                <p class="mt-1 text-sm font-black">{{ $estimatedTime }} - {{ $deliveryMax }} min</p>
                                <p class="mt-1 text-xs font-semibold leading-5">Envio a: Zona 1, {{ $vendor->municipio ?: 'Puerto Barrios' }}</p>
                                <a href="{{ route('cliente.direcciones.index') }}" class="mt-2 inline-flex text-xs font-black underline">Cambiar direccion</a>
                            </div>
                        </div>
                        <div class="mt-4 border-t border-emerald-200 pt-4">
                            <p class="text-sm font-black">Necesitas ayuda?</p>
                            <p class="mt-1 text-xs font-semibold">Contactanos por WhatsApp o soporte local.</p>
                        </div>
                    </section>
                </aside>
            </section>

            <section id="informacion" class="mt-10 grid gap-5 lg:grid-cols-[minmax(0,1fr)_300px]">
                <div class="rounded-2xl border border-atlantia-rose/15 bg-white p-5 shadow-sm">
                    <h2 class="text-xl font-black text-atlantia-ink">Informacion del comercio</h2>
                    <div class="mt-5 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                        <article class="flex gap-3">
                            <span class="grid h-11 w-11 shrink-0 place-items-center rounded-full bg-atlantia-blush text-atlantia-wine">
                                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M12 21s7-6.1 7-12A7 7 0 1 0 5 9c0 5.9 7 12 7 12Zm0-9a3 3 0 1 0 0-6 3 3 0 0 0 0 6Z" stroke="currentColor" stroke-width="1.7"/></svg>
                            </span>
                            <div>
                                <h3 class="text-sm font-black text-atlantia-ink">Direccion</h3>
                                <p class="mt-1 text-sm leading-5 text-atlantia-ink/60">{{ $vendor->direccion_comercial ?: 'Direccion por confirmar' }}<br>{{ $vendor->municipio ?: 'Izabal' }}</p>
                            </div>
                        </article>
                        <article class="flex gap-3">
                            <span class="grid h-11 w-11 shrink-0 place-items-center rounded-full bg-atlantia-blush text-atlantia-wine">
                                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M12 7v5l3 2M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/></svg>
                            </span>
                            <div>
                                <h3 class="text-sm font-black text-atlantia-ink">Horarios</h3>
                                <p class="mt-1 text-sm leading-5 text-atlantia-ink/60">Lunes a Domingo<br>7:00 AM - 10:00 PM</p>
                            </div>
                        </article>
                        <article class="flex gap-3">
                            <span class="grid h-11 w-11 shrink-0 place-items-center rounded-full bg-atlantia-blush text-atlantia-wine">
                                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M4 7h16v10H4V7Zm0 3h16M7 15h3" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/></svg>
                            </span>
                            <div>
                                <h3 class="text-sm font-black text-atlantia-ink">Metodos de pago</h3>
                                <p class="mt-1 text-sm leading-5 text-atlantia-ink/60">{{ $paymentMethods->isEmpty() ? 'Pago al confirmar' : $paymentMethods->join(', ') }}</p>
                            </div>
                        </article>
                        <article class="flex gap-3">
                            <span class="grid h-11 w-11 shrink-0 place-items-center rounded-full bg-atlantia-blush text-atlantia-wine">
                                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M7 4h10v16H7V4Zm3 4h4M10 12h4M10 16h2" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/></svg>
                            </span>
                            <div>
                                <h3 class="text-sm font-black text-atlantia-ink">Politicas</h3>
                                <p class="mt-1 text-sm leading-5 text-atlantia-ink/60">Entrega, soporte y devoluciones segun validacion Atlantia.</p>
                            </div>
                        </article>
                    </div>
                </div>

                <div id="opiniones" class="rounded-2xl border border-atlantia-rose/15 bg-white p-5 shadow-sm">
                    <h2 class="text-base font-black text-atlantia-ink">Opiniones de clientes</h2>
                    <div class="mt-4 flex items-end gap-2">
                        <span class="text-4xl font-black text-atlantia-ink">{{ $ratingValue }}</span>
                        <span class="pb-2 text-amber-400">&#9733;&#9733;&#9733;&#9733;&#9733;</span>
                    </div>
                    <p class="mt-1 text-sm font-semibold text-atlantia-ink/55">Basado en {{ number_format($ratingTotal) }} opiniones</p>
                    <a href="#opiniones" class="mt-5 flex h-11 items-center justify-center rounded-lg border border-atlantia-rose/35 px-4 text-sm font-black text-atlantia-wine transition hover:bg-atlantia-blush">
                        Ver todas las opiniones
                    </a>
                </div>
            </section>

            <section class="mt-8 grid gap-4 rounded-2xl border border-atlantia-rose/15 bg-white p-5 shadow-sm sm:grid-cols-2 lg:grid-cols-4">
                @foreach ([
                    ['title' => 'Comercios verificados', 'text' => 'Todos nuestros aliados pasan por un proceso de verificacion.'],
                    ['title' => 'Pago seguro', 'text' => 'Tus pagos estan protegidos con los mas altos estandares.'],
                    ['title' => 'Atencion 24/7', 'text' => 'Estamos aqui para ayudarte cuando lo necesites.'],
                    ['title' => 'Garantia Atlantia', 'text' => 'Si algo no esta bien, damos soporte y seguimiento.'],
                ] as $trust)
                    <article class="flex gap-4">
                        <span class="grid h-12 w-12 shrink-0 place-items-center rounded-full bg-atlantia-blush text-atlantia-wine">
                            <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                <path d="M12 3 19 6v5c0 4.5-2.8 8.4-7 10-4.2-1.6-7-5.5-7-10V6l7-3Z" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round"/>
                                <path d="M9 12l2 2 4-5" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </span>
                        <div>
                            <h3 class="text-sm font-black text-atlantia-ink">{{ $trust['title'] }}</h3>
                            <p class="mt-1 text-sm leading-5 text-atlantia-ink/60">{{ $trust['text'] }}</p>
                        </div>
                    </article>
                @endforeach
            </section>
        </div>
    </div>
@endsection

@push('scripts')
    <script nonce="{{ request()->attributes->get('csp_nonce') }}">
        (() => {
            const clampQuantity = (input, step) => {
                if (!input) return;

                const min = Number(input.min || 1);
                const max = Number(input.max || 99);
                const current = Number(input.value || min);

                input.value = Math.min(max, Math.max(min, current + step));
            };

            document.querySelectorAll('[data-quantity-step]').forEach((button) => {
                button.addEventListener('click', (event) => {
                    const input = document.getElementById(button.dataset.quantityTarget);
                    clampQuantity(input, Number(button.dataset.quantityStep || 0));

                    if (button.hasAttribute('data-quantity-submit')) {
                        event.preventDefault();
                        button.closest('form')?.submit();
                    }
                });
            });

            document.querySelectorAll('[data-auto-submit]').forEach((control) => {
                control.addEventListener('change', () => control.closest('form')?.submit());
            });
        })();
    </script>
@endpush
