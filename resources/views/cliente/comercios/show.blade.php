@extends('layouts.marketplace')

@section('content')
    @php
        $money = static fn ($value): string => 'Q' . number_format((float) $value, 2);
        $initials = \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($vendor->business_name, 0, 2));
        $storeCategory = $vendor->business_category ?: 'Comercio local';
        $ratingValue = $rating['rating'] ? number_format((float) $rating['rating'], 1) : 'Nuevo';
        $ratingTotal = (int) $rating['total'];
        $coverImage = $coverUrl ?: asset('images/fondo.png');
        $deliveryMax = $estimatedTime === null ? null : $estimatedTime + 15;
        $paymentMethods = collect([
            $vendor->accepts_cash ? 'Efectivo' : null,
            $vendor->accepts_transfer ? 'Transferencia' : null,
            $vendor->accepts_card ? 'Tarjeta con POS' : null,
        ])->filter()->values();
        $cartSubtotal = $cartItems->sum(fn ($item) => (float) $item->precio_unitario_snapshot * (int) $item->cantidad);
        $cartDelivery = $cartItems->isEmpty() || $deliveryFee === null ? 0 : (float) $deliveryFee;
        $cartTotal = $cartSubtotal + $cartDelivery;
        $cartCount = (int) $cartItems->sum('cantidad');
        $storeRoute = static function (array $changes = []) use ($vendor, $filters): string {
            $query = array_merge($filters, $changes);
            $query = array_filter($query, static fn ($value): bool => $value !== null && $value !== '' && $value !== false);

            return route('comercios.show', ['vendor' => $vendor->slug, ...$query]);
        };
    @endphp

    <div class="bg-[linear-gradient(180deg,#fff_0%,#fff9fb_48%,#fff_100%)]">
        <div class="mx-auto w-full max-w-[1536px] px-4 py-5 sm:px-6 lg:px-8">
            <section class="overflow-hidden rounded-lg border border-atlantia-rose/15 bg-white shadow-[0_14px_36px_rgba(42,16,24,0.08)]">
                <div class="grid min-h-[12.5rem] lg:grid-cols-[minmax(31rem,42%)_1fr]">
                    <div class="flex flex-col justify-between gap-5 p-5 sm:p-7">
                        <div class="flex min-w-0 items-center gap-4">
                            <div class="grid h-20 w-20 shrink-0 place-items-center overflow-hidden rounded-full border border-atlantia-rose/15 bg-atlantia-blush p-3 text-2xl font-black text-atlantia-wine shadow-sm">
                                @if ($logoUrl)
                                    <img src="{{ $logoUrl }}" alt="Logo de {{ $vendor->business_name }}" class="max-h-full max-w-full object-contain">
                                @else
                                    {{ $initials }}
                                @endif
                            </div>

                            <div class="min-w-0">
                                <div class="flex flex-wrap items-center gap-2">
                                    <h1 class="text-2xl font-black leading-tight text-atlantia-ink sm:text-3xl">{{ $vendor->business_name }}</h1>
                                    <span class="grid h-5 w-5 place-items-center rounded-full bg-sky-500 text-white" title="Comercio verificado">
                                        <svg class="h-3 w-3" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="m6 12 4 4 8-9" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                    </span>
                                </div>
                                <p class="mt-1 text-sm font-semibold text-atlantia-ink/55">{{ $storeCategory }}</p>
                                <div class="mt-2 flex flex-wrap items-center gap-2 text-sm">
                                    <span class="font-black text-amber-500">&#9733;</span>
                                    <span class="font-black text-atlantia-ink">{{ $ratingValue }}</span>
                                    <span class="font-semibold text-atlantia-ink/50">({{ number_format($ratingTotal) }} opiniones)</span>
                                </div>
                            </div>
                        </div>

                        <div class="grid gap-2 sm:grid-cols-3">
                            <div class="flex min-h-14 items-center gap-2 rounded-lg border border-atlantia-rose/15 px-3 py-2">
                                <span class="grid h-8 w-8 shrink-0 place-items-center rounded-full bg-emerald-50 text-emerald-700">
                                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="m6 12 4 4 8-9" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                </span>
                                <div><p class="text-xs font-black text-emerald-700">Disponible</p><p class="text-[11px] font-semibold text-atlantia-ink/50">Recibe pedidos</p></div>
                            </div>
                            <div class="flex min-h-14 items-center gap-2 rounded-lg border border-atlantia-rose/15 px-3 py-2">
                                <svg class="h-5 w-5 shrink-0 text-atlantia-wine" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M12 7v5l3 2M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
                                <div><p class="text-xs font-black text-atlantia-ink">{{ $estimatedTime === null ? 'Por confirmar' : $estimatedTime . ' - ' . $deliveryMax . ' min' }}</p><p class="text-[11px] font-semibold text-atlantia-ink/50">Entrega estimada</p></div>
                            </div>
                            <div class="flex min-h-14 items-center gap-2 rounded-lg border border-atlantia-rose/15 px-3 py-2">
                                <svg class="h-5 w-5 shrink-0 text-atlantia-wine" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M5 7h14l-1 13H6L5 7Zm4 0a3 3 0 0 1 6 0" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/></svg>
                                <div><p class="text-xs font-black text-atlantia-ink">{{ $deliveryFee === null ? 'Por confirmar' : $money($deliveryFee) }}</p><p class="text-[11px] font-semibold text-atlantia-ink/50">Costo de entrega</p></div>
                            </div>
                        </div>
                    </div>

                    <div class="relative min-h-52 overflow-hidden lg:min-h-full">
                        <img src="{{ $coverImage }}" alt="Portada de {{ $vendor->business_name }}" class="absolute inset-0 h-full w-full object-cover">
                        <div class="absolute inset-0 bg-gradient-to-r from-white/15 via-transparent to-black/5"></div>
                    </div>
                </div>
            </section>

            <nav class="grid grid-cols-3 border-b border-atlantia-rose/15 pt-4 text-xs font-black sm:flex sm:gap-8 sm:text-sm" aria-label="Secciones del comercio">
                <a href="#productos" class="border-b-2 border-atlantia-rose px-1 py-3 text-center text-atlantia-wine">Productos</a>
                <a href="#informacion" class="border-b-2 border-transparent px-1 py-3 text-center text-atlantia-ink/60 transition hover:text-atlantia-wine">Informacion</a>
                <a href="#opiniones" class="border-b-2 border-transparent px-1 py-3 text-center text-atlantia-ink/60 transition hover:text-atlantia-wine">Opiniones</a>
            </nav>

            <section id="productos" class="scroll-mt-28 pt-4">
                <div class="grid gap-3 lg:grid-cols-[minmax(0,1fr)_16rem]">
                    <div class="min-w-0 space-y-3">
                        <div class="flex flex-col gap-3 sm:flex-row">
                            <form method="GET" action="{{ route('comercios.show', ['vendor' => $vendor->slug]) }}" class="relative min-w-0 flex-1">
                                <svg class="pointer-events-none absolute left-4 top-1/2 h-5 w-5 -translate-y-1/2 text-atlantia-ink/45" viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle cx="11" cy="11" r="7" stroke="currentColor" stroke-width="1.8"/><path d="m16 16 4 4" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
                                <input name="q" value="{{ $filters['q'] }}" type="search" placeholder="Buscar en esta tienda..." class="h-11 w-full rounded-lg border border-atlantia-rose/20 bg-white pl-12 pr-24 text-sm font-semibold text-atlantia-ink outline-none transition placeholder:text-atlantia-ink/35 focus:border-atlantia-wine focus:ring-2 focus:ring-atlantia-blush">
                                @if ($filters['categoria']) <input type="hidden" name="categoria" value="{{ $filters['categoria'] }}"> @endif
                                @if ($filters['ofertas']) <input type="hidden" name="ofertas" value="1"> @endif
                                <input type="hidden" name="orden" value="{{ $filters['orden'] }}">
                                <button class="absolute right-1.5 top-1.5 h-8 rounded-md bg-atlantia-wine px-4 text-xs font-black text-white transition hover:bg-atlantia-wine-700">Buscar</button>
                            </form>

                            <a href="{{ $storeRoute(['ofertas' => ! $filters['ofertas']]) }}" class="inline-flex h-11 shrink-0 items-center justify-center gap-2 rounded-lg border px-4 text-sm font-black transition {{ $filters['ofertas'] ? 'border-atlantia-wine bg-atlantia-wine text-white' : 'border-atlantia-rose/20 bg-white text-atlantia-wine hover:bg-atlantia-blush' }}">
                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M4 5h8l8 8-7 7-8-8V5Zm4 4h.01" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                Ofertas
                            </a>

                            <form method="GET" action="{{ route('comercios.show', ['vendor' => $vendor->slug]) }}">
                                @if ($filters['q'] !== '') <input type="hidden" name="q" value="{{ $filters['q'] }}"> @endif
                                @if ($filters['categoria']) <input type="hidden" name="categoria" value="{{ $filters['categoria'] }}"> @endif
                                @if ($filters['ofertas']) <input type="hidden" name="ofertas" value="1"> @endif
                                <select name="orden" data-auto-submit aria-label="Ordenar productos" class="h-11 w-full rounded-lg border border-atlantia-rose/20 bg-white px-3 text-sm font-black text-atlantia-ink outline-none focus:border-atlantia-wine focus:ring-2 focus:ring-atlantia-blush sm:w-44">
                                    <option value="relevancia" @selected($filters['orden'] === 'relevancia')>Relevancia</option>
                                    <option value="precio_asc" @selected($filters['orden'] === 'precio_asc')>Menor precio</option>
                                    <option value="precio_desc" @selected($filters['orden'] === 'precio_desc')>Mayor precio</option>
                                    <option value="nombre" @selected($filters['orden'] === 'nombre')>Nombre</option>
                                </select>
                            </form>
                        </div>

                        <nav class="flex max-w-full gap-2 overflow-x-auto pb-1 [scrollbar-width:none] [&::-webkit-scrollbar]:hidden" aria-label="Categorias del comercio">
                            <a href="{{ $storeRoute(['categoria' => null]) }}" class="inline-flex h-9 shrink-0 items-center rounded-full px-5 text-xs font-black transition {{ $filters['categoria'] === null ? 'bg-atlantia-wine text-white' : 'border border-atlantia-rose/20 bg-white text-atlantia-ink/65 hover:bg-atlantia-blush hover:text-atlantia-wine' }}">Todas</a>
                            @foreach ($availableCategories as $category)
                                <a href="{{ $storeRoute(['categoria' => $category->id]) }}" class="inline-flex h-9 shrink-0 items-center rounded-full px-5 text-xs font-black transition {{ $filters['categoria'] === $category->id ? 'bg-atlantia-wine text-white' : 'border border-atlantia-rose/20 bg-white text-atlantia-ink/65 hover:bg-atlantia-blush hover:text-atlantia-wine' }}">{{ $category->nombre }}</a>
                            @endforeach
                        </nav>
                    </div>

                    <aside class="rounded-lg border border-atlantia-rose/15 bg-white p-4 shadow-sm" aria-label="Resumen del carrito">
                        <div class="flex items-center justify-between gap-3">
                            <div class="flex items-center gap-2">
                                <svg class="h-5 w-5 text-atlantia-wine" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M4 5h2l2 10h9l2-7H7M10 20a1 1 0 1 0 0-2 1 1 0 0 0 0 2Zm7 0a1 1 0 1 0 0-2 1 1 0 0 0 0 2Z" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                <h2 class="text-sm font-black text-atlantia-ink">Tu pedido</h2>
                            </div>
                            <span class="grid h-6 min-w-6 place-items-center rounded-full bg-atlantia-wine px-1.5 text-xs font-black text-white">{{ $cartCount }}</span>
                        </div>
                        <div class="mt-3 flex items-center justify-between gap-3 text-sm">
                            <span class="font-semibold text-atlantia-ink/55">{{ $deliveryFee === null ? 'Subtotal' : 'Total aproximado' }}</span>
                            <span class="font-black text-atlantia-ink">{{ $money($cartTotal) }}</span>
                        </div>
                        <a href="{{ route('cliente.carrito.index') }}" class="mt-3 flex h-10 items-center justify-center rounded-md bg-atlantia-wine px-4 text-sm font-black text-white transition hover:bg-atlantia-wine-700">Ver carrito</a>
                    </aside>
                </div>

                @if ($filters['q'] !== '' || $filters['categoria'] || $filters['ofertas'])
                    <div class="mt-3 flex flex-wrap items-center gap-3 text-xs font-semibold text-atlantia-ink/55">
                        <span>{{ number_format($products->count()) }} resultado(s)</span>
                        <a href="{{ route('comercios.show', ['vendor' => $vendor->slug]) }}" class="font-black text-atlantia-wine underline decoration-atlantia-rose/40 underline-offset-4">Limpiar filtros</a>
                    </div>
                @endif

                <div class="mt-5 space-y-7">
                    @forelse ($productsByCategory as $categoryName => $categoryProducts)
                        @php $carouselId = 'productos-' . $loop->index; @endphp
                        <section id="categoria-{{ \Illuminate\Support\Str::slug($categoryName) }}" class="scroll-mt-28" data-product-carousel>
                            <div class="mb-3 flex items-center justify-between gap-4">
                                <div>
                                    <h2 class="text-lg font-black text-atlantia-ink">{{ $categoryName }}</h2>
                                    <p class="mt-0.5 text-xs font-semibold text-atlantia-ink/45">{{ number_format($categoryProducts->count()) }} productos disponibles en el catalogo</p>
                                </div>
                                <div class="flex shrink-0 gap-2">
                                    <button type="button" data-carousel-prev aria-controls="{{ $carouselId }}" aria-label="Ver productos anteriores" class="grid h-9 w-9 place-items-center rounded-md border border-atlantia-rose/20 bg-white text-atlantia-wine transition hover:bg-atlantia-blush disabled:cursor-not-allowed disabled:opacity-35">
                                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="m15 5-7 7 7 7" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                    </button>
                                    <button type="button" data-carousel-next aria-controls="{{ $carouselId }}" aria-label="Ver mas productos" class="grid h-9 w-9 place-items-center rounded-md border border-atlantia-rose/20 bg-white text-atlantia-wine transition hover:bg-atlantia-blush disabled:cursor-not-allowed disabled:opacity-35">
                                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="m9 5 7 7-7 7" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                    </button>
                                </div>
                            </div>

                            <div id="{{ $carouselId }}" data-carousel-track class="flex snap-x snap-mandatory gap-3 overflow-x-auto pb-3 [scrollbar-color:rgba(122,31,61,0.25)_transparent] [scrollbar-width:thin]">
                                @foreach ($categoryProducts as $producto)
                                    @include('cliente.comercios.partials.product-carousel-card', ['producto' => $producto, 'money' => $money])
                                @endforeach
                            </div>
                        </section>
                    @empty
                        <div class="rounded-lg border border-dashed border-atlantia-rose/30 bg-white px-6 py-12 text-center">
                            <span class="mx-auto grid h-12 w-12 place-items-center rounded-full bg-atlantia-blush text-atlantia-wine">
                                <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M4 5h2l2 10h9l2-7H7M9 20h.01M17 20h.01" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>
                            </span>
                            <h2 class="mt-4 text-lg font-black text-atlantia-ink">No encontramos productos</h2>
                            <p class="mt-1 text-sm text-atlantia-ink/55">Prueba otra categoria o limpia los filtros de busqueda.</p>
                            <a href="{{ route('comercios.show', ['vendor' => $vendor->slug]) }}" class="mt-4 inline-flex h-10 items-center rounded-md bg-atlantia-wine px-5 text-sm font-black text-white">Ver todo el catalogo</a>
                        </div>
                    @endforelse
                </div>
            </section>

            <section id="informacion" class="mt-8 grid scroll-mt-28 gap-3 lg:grid-cols-[minmax(0,1fr)_19rem]">
                <div class="grid overflow-hidden rounded-lg border border-atlantia-rose/15 bg-white shadow-sm sm:grid-cols-2 xl:grid-cols-4">
                    <article class="border-b border-atlantia-rose/10 p-5 sm:border-r xl:border-b-0">
                        <h2 class="text-sm font-black text-atlantia-ink">Informacion de la tienda</h2>
                        <p class="mt-3 text-sm leading-6 text-atlantia-ink/55">{{ $vendor->direccion_comercial ?: 'Direccion pendiente de publicacion' }}<br>{{ $vendor->municipio ?: 'Municipio pendiente' }}</p>
                    </article>
                    <article class="border-b border-atlantia-rose/10 p-5 xl:border-b-0 xl:border-r">
                        <h2 class="text-sm font-black text-atlantia-ink">Pagos aceptados</h2>
                        <p class="mt-3 text-sm leading-6 text-atlantia-ink/55">{{ $paymentMethods->isEmpty() ? 'Se confirman al finalizar el pedido' : $paymentMethods->join(', ') }}</p>
                    </article>
                    <article class="border-b border-atlantia-rose/10 p-5 sm:border-r sm:border-b-0">
                        <h2 class="text-sm font-black text-atlantia-ink">Envio</h2>
                        <p class="mt-3 text-sm leading-6 text-atlantia-ink/55">
                            {{ $deliveryFee === null ? 'Costo por confirmar' : 'Desde ' . $money($deliveryFee) }}<br>
                            {{ $estimatedTime === null ? 'Tiempo por confirmar' : 'Tiempo estimado de ' . $estimatedTime . ' a ' . $deliveryMax . ' minutos.' }}
                        </p>
                    </article>
                    <article class="p-5">
                        <h2 class="text-sm font-black text-atlantia-ink">Compra protegida</h2>
                        <p class="mt-3 text-sm leading-6 text-atlantia-ink/55">Catalogo y stock sincronizados con el comercio.</p>
                    </article>
                </div>

                <aside id="opiniones" class="scroll-mt-28 rounded-lg border border-atlantia-rose/15 bg-white p-5 shadow-sm">
                    <div class="flex items-center justify-between gap-3">
                        <h2 class="text-sm font-black text-atlantia-ink">Opiniones de esta tienda</h2>
                        <span class="text-xs font-black text-atlantia-wine">{{ number_format($ratingTotal) }}</span>
                    </div>
                    <div class="mt-4 flex items-center gap-2">
                        <span class="text-3xl font-black text-atlantia-ink">{{ $ratingValue }}</span>
                        <span class="text-amber-400">&#9733;&#9733;&#9733;&#9733;&#9733;</span>
                    </div>
                    <p class="mt-2 text-xs font-semibold text-atlantia-ink/50">Calificacion calculada con resenas aprobadas.</p>
                </aside>
            </section>
        </div>
    </div>
@endsection

@push('scripts')
    <script nonce="{{ request()->attributes->get('csp_nonce') }}">
        (() => {
            document.querySelectorAll('[data-auto-submit]').forEach((control) => {
                control.addEventListener('change', () => control.closest('form')?.submit());
            });

            document.querySelectorAll('[data-product-carousel]').forEach((carousel) => {
                const track = carousel.querySelector('[data-carousel-track]');
                const previous = carousel.querySelector('[data-carousel-prev]');
                const next = carousel.querySelector('[data-carousel-next]');

                if (!track || !previous || !next) return;

                const updateControls = () => {
                    const maxScroll = Math.max(0, track.scrollWidth - track.clientWidth);
                    previous.disabled = track.scrollLeft <= 4;
                    next.disabled = track.scrollLeft >= maxScroll - 4;
                };

                const move = (direction) => {
                    track.scrollBy({ left: direction * Math.max(220, track.clientWidth * 0.82), behavior: 'smooth' });
                };

                previous.addEventListener('click', () => move(-1));
                next.addEventListener('click', () => move(1));
                track.addEventListener('scroll', updateControls, { passive: true });
                window.addEventListener('resize', updateControls, { passive: true });
                updateControls();
            });
        })();
    </script>
@endpush
