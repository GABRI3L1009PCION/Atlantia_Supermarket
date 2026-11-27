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
        $opiniones = $opiniones ?? ['total' => 0, 'promedio' => null, 'distribucion' => [], 'lista' => collect()];
        $nombreCorto = function (?string $nombre): string {
            $partes = preg_split('/\s+/', trim((string) $nombre)) ?: [];
            $inicial = isset($partes[1]) ? ' '.mb_substr($partes[1], 0, 1).'.' : '';

            return ($partes[0] ?? '') !== '' ? $partes[0].$inicial : 'Cliente';
        };
    @endphp

    <section class="bg-[#fffafa] px-4 py-5 sm:px-6 sm:py-8 lg:px-8">
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
                        {{ $producto->vendor?->business_name ?? 'Atlantia Store' }}
                    </a>
                    <a href="#opiniones" class="mt-1.5 inline-flex w-fit items-center gap-1.5 text-sm" data-calificacion-producto>
                        @if ($opiniones['total'] > 0)
                            <span class="text-amber-400" aria-hidden="true">@for ($i = 1; $i <= 5; $i++){{ $i <= round($opiniones['promedio']) ? '★' : '☆' }}@endfor</span>
                            <span class="font-black text-atlantia-ink">{{ number_format($opiniones['promedio'], 1) }}</span>
                            <span class="text-atlantia-ink/55 underline-offset-2 hover:underline">({{ $opiniones['total'] }} {{ $opiniones['total'] === 1 ? 'opinión' : 'opiniones' }})</span>
                        @else
                            <span class="text-atlantia-ink/50">Aún sin opiniones</span>
                        @endif
                    </a>

                    <div class="mt-5 flex flex-wrap items-end gap-3">
                        <span class="text-3xl font-black text-atlantia-wine">Q{{ number_format($price, 2) }}</span>
                        @if ($hasOffer)
                            <span class="pb-1 text-base font-bold text-atlantia-ink/35 line-through">Q{{ number_format($basePrice, 2) }}</span>
                        @endif
                    </div>
                    <p class="mt-1 text-sm font-semibold text-atlantia-ink/50">{{ $unitLabel }}</p>

                    <div class="mt-5 rounded-lg border border-atlantia-rose/15 bg-atlantia-blush/40 p-4">
                        @if ($stock > 0)
                            <div class="flex flex-wrap items-center justify-between gap-2" data-stock-disponible>
                                <p class="text-sm font-black text-emerald-700">Disponible para entrega</p>
                                @if ($stock <= 5)
                                    <span class="rounded-full bg-amber-100 px-2.5 py-1 text-xs font-black text-amber-800">¡{{ $stock === 1 ? 'Queda solo 1 unidad' : 'Quedan solo '.$stock.' unidades' }}!</span>
                                @else
                                    <span class="rounded-full bg-white px-2.5 py-1 text-xs font-bold text-atlantia-ink/70">{{ $stock }} unidades disponibles</span>
                                @endif
                            </div>
                        @else
                            <p class="text-sm font-black text-red-600">Producto agotado</p>
                        @endif
                        @if ($producto->requiere_refrigeracion)
                            <p class="mt-1 text-xs font-semibold text-atlantia-ink/55">Este producto requiere refrigeracion.</p>
                        @endif
                    </div>

                    @if ($producto->descripcion)
                        <div class="mt-5">
                            <h2 class="text-sm font-black text-atlantia-ink">Descripción</h2>
                            <p class="mt-2 text-sm leading-6 text-atlantia-ink/60">{{ $producto->descripcion }}</p>
                        </div>
                    @endif

                    <form method="POST" action="{{ route('cliente.carrito.items.store') }}" class="mt-auto pt-6">
                        @csrf
                        <input type="hidden" name="producto_id" value="{{ $producto->id }}">
                        @php $maxCantidad = max(1, min(99, (int) $stock)); @endphp
                        {{-- Selector de cantidad con − y +: al pasar del stock avisa cuantas unidades hay. --}}
                        <div x-data="{
                                cantidad: 1,
                                max: {{ $maxCantidad }},
                                aviso: false,
                                subir() { if (this.cantidad >= this.max) { this.avisar(); return; } this.cantidad++; this.aviso = false; },
                                bajar() { this.cantidad = Math.max(1, this.cantidad - 1); this.aviso = false; },
                                ajustar() { const n = parseInt(this.cantidad) || 1; if (n > this.max) { this.avisar(); } this.cantidad = Math.min(this.max, Math.max(1, n)); },
                                avisar() { this.aviso = true; clearTimeout(this.t); this.t = setTimeout(() => { this.aviso = false; }, 4000); },
                            }">
                        <div class="grid grid-cols-[8.5rem_1fr] gap-3">
                            <div class="flex h-12 items-center rounded-lg border bg-white transition" :class="aviso ? 'border-amber-400 ring-2 ring-amber-200' : 'border-atlantia-rose/20'" data-selector-cantidad>
                                <button type="button" class="flex h-full w-10 shrink-0 items-center justify-center rounded-l-lg text-atlantia-wine transition hover:bg-atlantia-blush disabled:text-atlantia-ink/25 disabled:hover:bg-transparent" aria-label="Quitar uno" @click="bajar()" :disabled="cantidad <= 1">
                                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M5 12h14" stroke="currentColor" stroke-width="2.6" stroke-linecap="round"/></svg>
                                </button>
                                <label class="sr-only" for="product-detail-quantity">Cantidad</label>
                                <input id="product-detail-quantity" name="cantidad" type="number" inputmode="numeric" min="1" max="{{ $maxCantidad }}" value="1" x-model.number="cantidad" @blur="ajustar()" class="h-full w-full min-w-0 border-0 bg-transparent p-0 text-center text-base font-black text-atlantia-ink [appearance:textfield] focus:outline-none focus:ring-0 [&::-webkit-inner-spin-button]:appearance-none [&::-webkit-outer-spin-button]:appearance-none">
                                <button type="button" class="flex h-full w-10 shrink-0 items-center justify-center rounded-r-lg text-atlantia-wine transition hover:bg-atlantia-blush disabled:text-atlantia-ink/25 disabled:hover:bg-transparent" aria-label="Agregar uno" @click="subir()" :class="cantidad >= max && 'text-atlantia-ink/30'">
                                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M12 5v14M5 12h14" stroke="currentColor" stroke-width="2.6" stroke-linecap="round"/></svg>
                                </button>
                            </div>
                            <button class="h-12 rounded-lg bg-atlantia-wine px-4 text-sm font-black text-white shadow-sm disabled:cursor-not-allowed disabled:bg-slate-300" @disabled($stock < 1)>
                                Agregar al carrito
                            </button>
                        </div>
                        <p x-cloak x-show="aviso" x-transition.opacity class="mt-2 flex items-center gap-2 rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-xs font-bold text-amber-900" role="status" aria-live="polite" data-aviso-stock>
                            <svg class="h-4 w-4 shrink-0 text-amber-600" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M12 9v4m0 4h.01M10.3 3.9 2.4 17.5A2 2 0 0 0 4.1 20.5h15.8a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0Z" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"/></svg>
                            <span>{{ $maxCantidad < 99 ? ($maxCantidad === 1 ? 'Solo hay 1 unidad disponible de este producto.' : 'Solo hay '.$maxCantidad.' unidades disponibles de este producto.') : 'Puedes pedir hasta 99 unidades por vez.' }}</span>
                        </p>
                        </div>
                    </form>

                    <div class="mt-4 grid grid-cols-2 gap-2 text-[11px] font-bold text-atlantia-ink/65">
                        <span class="flex items-center justify-center gap-1.5 rounded-md border border-atlantia-rose/10 px-2 py-2">
                            <svg class="h-4 w-4 shrink-0 text-atlantia-wine" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M3 15h12V6H3v9Zm12-6h3l3 3v3h-6V9ZM7 19a2 2 0 1 0 0-4 2 2 0 0 0 0 4Zm11 0a2 2 0 1 0 0-4 2 2 0 0 0 0 4Z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/></svg>
                            Entrega a domicilio
                        </span>
                        <span class="flex items-center justify-center gap-1.5 rounded-md border border-atlantia-rose/10 px-2 py-2">
                            <svg class="h-4 w-4 shrink-0 text-atlantia-wine" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M12 3 19 6v5c0 4.5-2.8 8.4-7 10-4.2-1.6-7-5.5-7-10V6l7-3Zm-3 9 2 2 4-5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>
                            Compra protegida
                        </span>
                    </div>
                </div>
            </div>

            {{-- Opiniones de clientes que compraron el producto --}}
            <section id="opiniones" class="mt-5 scroll-mt-28 rounded-lg border border-atlantia-rose/15 bg-white p-5 sm:p-6" data-opiniones>
                <h2 class="text-lg font-black text-atlantia-ink">Opiniones de clientes</h2>

                @if ($opiniones['total'] === 0)
                    <p class="mt-2 text-sm text-atlantia-ink/60">Todavía nadie ha opinado sobre este producto. Si lo compras, podrás calificarlo cuando te llegue.</p>
                @else
                    <div class="mt-4 grid gap-6 md:grid-cols-[14rem_minmax(0,1fr)]">
                        <div>
                            <p class="text-4xl font-black leading-none text-atlantia-ink">{{ number_format($opiniones['promedio'], 1) }}</p>
                            <p class="mt-1 text-lg leading-none text-amber-400" aria-label="{{ number_format($opiniones['promedio'], 1) }} de 5 estrellas">@for ($i = 1; $i <= 5; $i++){{ $i <= round($opiniones['promedio']) ? '★' : '☆' }}@endfor</p>
                            <p class="mt-1 text-xs text-atlantia-ink/55">{{ $opiniones['total'] }} {{ $opiniones['total'] === 1 ? 'opinión' : 'opiniones' }} de compras verificadas</p>
                            <div class="mt-3 space-y-1">
                                @foreach ($opiniones['distribucion'] as $estrellas => $cuenta)
                                    <div class="flex items-center gap-2 text-xs">
                                        <span class="w-8 shrink-0 font-bold text-atlantia-ink/65">{{ $estrellas }} ★</span>
                                        <span class="h-2 flex-1 overflow-hidden rounded-full bg-[#f1e9e8]"><span class="block h-full rounded-full bg-amber-400" style="width: {{ round($cuenta / max(1, $opiniones['total']) * 100) }}%"></span></span>
                                        <span class="w-5 shrink-0 text-right text-atlantia-ink/50">{{ $cuenta }}</span>
                                    </div>
                                @endforeach
                            </div>
                        </div>

                        <ul class="divide-y divide-atlantia-rose/10">
                            @foreach ($opiniones['lista'] as $resena)
                                <li class="py-4 first:pt-0" data-opinion>
                                    <div class="flex flex-wrap items-center gap-2">
                                        <span class="leading-none text-amber-400" aria-label="{{ (int) $resena->calificacion }} de 5 estrellas">@for ($i = 1; $i <= 5; $i++){{ $i <= (int) $resena->calificacion ? '★' : '☆' }}@endfor</span>
                                        @if ($resena->titulo)<span class="text-sm font-bold text-atlantia-ink">{{ $resena->titulo }}</span>@endif
                                    </div>
                                    @if ($resena->contenido)
                                        <p class="mt-1 text-sm leading-6 text-atlantia-ink/75">{{ $resena->contenido }}</p>
                                    @endif
                                    <p class="mt-1 text-xs text-atlantia-ink/50">{{ $nombreCorto($resena->cliente?->name) }} · compra verificada · {{ $resena->created_at?->format('d/m/Y') }}</p>
                                    @if ($resena->respuesta_vendedor)
                                        <div class="mt-2 rounded-lg border-l-4 border-atlantia-wine bg-[#fbf7f6] px-3 py-2" data-respuesta-vendedor>
                                            <p class="text-xs font-black text-atlantia-wine">Respuesta de {{ $producto->vendor?->business_name ?? 'la tienda' }}</p>
                                            <p class="mt-0.5 text-sm text-atlantia-ink/75">{{ $resena->respuesta_vendedor }}</p>
                                        </div>
                                    @endif
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif
            </section>
        </div>
    </section>
@endsection
