@extends('layouts.marketplace')

@section('content')
    @php
        $money = static fn ($value): string => 'Q' . number_format((float) $value, 2);
        $initials = \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($vendor->business_name, 0, 2));
        $storeCategory = $vendor->categoriaTexto() ?: 'Comercio local';
        $ratingTotal = (int) $rating['total'];
        $ratingValue = $ratingTotal > 0 && $rating['rating'] ? number_format((float) $rating['rating'], 1) : null;
        $deliveryMax = $estimatedTime === null ? null : $estimatedTime + 15;
        $paymentMethods = collect([
            $vendor->accepts_cash ? 'Efectivo' : null,
            $vendor->accepts_transfer ? 'Transferencia' : null,
            $vendor->accepts_card ? 'Tarjeta' : null,
        ])->filter()->values();
        $envioTexto = $deliveryFee === null ? 'Se calcula al pagar' : ($deliveryFee > 0 ? 'Desde ' . $money($deliveryFee) : 'Gratis');
        $tiempoTexto = $estimatedTime === null ? null : $estimatedTime . '–' . $deliveryMax . ' min';
        $horarioTexto = \App\Support\HorarioTienda::legible($horario['apertura']) . ' a ' . \App\Support\HorarioTienda::legible($horario['cierre']);
        $estadoTexto = $horario['pausado'] || ! $horario['abierto'] ? $horario['mensaje'] : 'Abierto ahora';
        $estadoAbierto = $horario['abierto'] && ! $horario['pausado'];
        $direccion = collect([$vendor->direccion_comercial, $vendor->municipio])->filter()->join(', ');
        $cartSubtotal = $cartItems->sum(fn ($item) => (float) $item->precio_unitario_snapshot * (int) $item->cantidad);
        $cartCount = (int) $cartItems->sum('cantidad');
        $filtrando = $filters['q'] !== '' || $filters['categoria'] || $filters['ofertas'];
        $storeRoute = static function (array $changes = []) use ($vendor, $filters): string {
            $query = array_merge($filters, $changes);
            $query = array_filter($query, static fn ($value): bool => $value !== null && $value !== '' && $value !== false);

            return route('comercios.show', ['vendor' => $vendor->slug, ...$query]);
        };
        $chip = 'inline-flex h-8 shrink-0 items-center rounded-full px-4 text-xs font-bold transition';
        $chipActivo = 'bg-atlantia-wine text-white';
        $chipNormal = 'border border-atlantia-rose/20 bg-white text-atlantia-ink/70 hover:border-atlantia-rose/40 hover:text-atlantia-wine';
    @endphp

    <div class="bg-[#f8f5f4]">
        {{-- Portada de lado a lado, pegada al menú. Medida 6 a 1 (1800 x 300 px). --}}
        <div class="relative w-full overflow-hidden bg-[linear-gradient(120deg,#3a0004_0%,#5e0008_60%,#7d1a22_100%)] {{ $coverUrl ? 'aspect-[4/1] sm:aspect-[6/1] sm:max-h-80' : 'h-24 sm:h-28' }}" data-portada-tienda>
            @if ($coverUrl)
                <img src="{{ $coverUrl }}" alt="Portada de {{ $vendor->business_name }}" class="absolute inset-0 h-full w-full object-cover">
            @else
                <div class="pointer-events-none absolute -right-20 -top-24 h-72 w-72 rounded-full bg-white/5"></div>
                <div class="pointer-events-none absolute -bottom-32 left-1/3 h-72 w-72 rounded-full bg-white/5"></div>
            @endif
        </div>

        <section class="border-b border-atlantia-rose/15 bg-white" aria-label="Datos de la tienda">
            <div class="mx-auto w-full max-w-[1440px] px-4 sm:px-6 lg:px-8">
                <div class="flex flex-col gap-3 pb-4 sm:flex-row sm:items-end sm:gap-4">
                    <div class="relative -mt-8 grid h-16 w-16 shrink-0 place-items-center overflow-hidden rounded-xl bg-white p-1.5 text-xl font-black text-atlantia-wine shadow-md ring-4 ring-white sm:-mt-10 sm:h-20 sm:w-20">
                        @if ($logoUrl)
                            <img src="{{ $logoUrl }}" alt="Logo de {{ $vendor->business_name }}" class="max-h-full max-w-full object-contain">
                        @else
                            {{ $initials }}
                        @endif
                    </div>

                    <div class="flex min-w-0 flex-1 flex-col gap-2 lg:flex-row lg:items-end lg:justify-between lg:gap-6">
                        <div class="min-w-0">
                            <div class="flex flex-wrap items-center gap-2">
                                <h1 class="text-xl font-black leading-tight text-atlantia-ink sm:text-2xl">{{ $vendor->business_name }}</h1>
                                <span class="inline-flex items-center gap-1 rounded-full bg-sky-50 px-2 py-0.5 text-[11px] font-bold text-sky-700" title="Comercio verificado por Atlantia">
                                    <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="m6 12 4 4 8-9" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                    Verificada
                                </span>
                                <span class="inline-flex items-center gap-1.5 rounded-full px-2 py-0.5 text-[11px] font-bold {{ $estadoAbierto ? 'bg-emerald-50 text-emerald-700' : 'bg-amber-50 text-amber-800' }}" data-estado-tienda>
                                    <span class="h-1.5 w-1.5 rounded-full {{ $estadoAbierto ? 'bg-emerald-500' : 'bg-amber-500' }}"></span>
                                    {{ $estadoTexto }}
                                </span>
                            </div>
                            <p class="mt-0.5 text-sm text-atlantia-ink/55">
                                {{ $storeCategory }}@if ($vendor->municipio) · {{ $vendor->municipio }}@endif
                                ·
                                @if ($ratingValue)
                                    <span class="font-bold text-atlantia-ink"><span class="text-amber-500">&#9733;</span> {{ $ratingValue }}</span> ({{ number_format($ratingTotal) }} {{ $ratingTotal === 1 ? 'opinión' : 'opiniones' }})
                                @else
                                    Tienda nueva
                                @endif
                                @if ((float) $vendor->pedido_minimo > 0)
                                    · <span class="font-bold text-atlantia-wine" data-pedido-minimo>Pedido mínimo Q {{ number_format((float) $vendor->pedido_minimo, 0) }}</span>
                                @endif
                            </p>
                        </div>

                        <dl class="flex flex-wrap gap-x-5 gap-y-1 text-xs text-atlantia-ink/70 lg:shrink-0 lg:justify-end">
                            <div class="flex items-center gap-1.5">
                                <dt><span class="sr-only">Envío</span><svg class="h-4 w-4 text-atlantia-wine" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M3 7h11v9H3zM14 10h4l3 3v3h-7M7 19a2 2 0 1 0 0-4 2 2 0 0 0 0 4Zm10 0a2 2 0 1 0 0-4 2 2 0 0 0 0 4Z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/></svg></dt>
                                <dd>Envío: <strong class="text-atlantia-ink">{{ \Illuminate\Support\Str::lcfirst($envioTexto) }}</strong>@if ($tiempoTexto) · {{ $tiempoTexto }}@endif</dd>
                            </div>
                            <div class="flex items-center gap-1.5">
                                <dt><span class="sr-only">Horario</span><svg class="h-4 w-4 text-atlantia-wine" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M12 7v5l3 2M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg></dt>
                                <dd><strong class="text-atlantia-ink">{{ $horarioTexto }}</strong></dd>
                            </div>
                            <div class="flex items-center gap-1.5">
                                <dt><span class="sr-only">Pagos</span><svg class="h-4 w-4 text-atlantia-wine" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M3 7h18v10H3zM3 11h18" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/></svg></dt>
                                <dd><strong class="text-atlantia-ink">{{ $paymentMethods->isEmpty() ? 'Pago al confirmar' : $paymentMethods->join(', ') }}</strong></dd>
                            </div>
                        </dl>
                    </div>
                </div>
            </div>
        </section>

        <div class="mx-auto w-full max-w-[1440px] px-4 py-5 sm:px-6 lg:px-8">
            <div class="grid gap-5 lg:grid-cols-[minmax(0,1fr)_17rem]">
                {{-- Catálogo --}}
                <section id="productos" class="min-w-0 scroll-mt-28" aria-label="Productos de la tienda">
                    <div class="flex flex-col gap-2 sm:flex-row">
                        <form method="GET" action="{{ route('comercios.show', ['vendor' => $vendor->slug]) }}" class="relative min-w-0 flex-1">
                            <svg class="pointer-events-none absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-atlantia-ink/40" viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle cx="11" cy="11" r="7" stroke="currentColor" stroke-width="2"/><path d="m16 16 4 4" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
                            <input name="q" value="{{ $filters['q'] }}" type="search" placeholder="Buscar en {{ $vendor->business_name }}..." aria-label="Buscar productos en esta tienda" class="h-10 w-full rounded-lg border border-atlantia-rose/20 bg-white pl-10 pr-3 text-sm text-atlantia-ink outline-none transition placeholder:text-atlantia-ink/40 focus:border-atlantia-wine focus:ring-2 focus:ring-atlantia-blush">
                            @if ($filters['categoria']) <input type="hidden" name="categoria" value="{{ $filters['categoria'] }}"> @endif
                            @if ($filters['ofertas']) <input type="hidden" name="ofertas" value="1"> @endif
                            <input type="hidden" name="orden" value="{{ $filters['orden'] }}">
                        </form>

                        <div class="flex gap-2">
                            <a href="{{ $storeRoute(['ofertas' => ! $filters['ofertas']]) }}" class="inline-flex h-10 flex-1 items-center justify-center gap-1.5 rounded-lg border px-4 text-sm font-bold transition sm:flex-none {{ $filters['ofertas'] ? 'border-atlantia-wine bg-atlantia-wine text-white' : 'border-atlantia-rose/20 bg-white text-atlantia-wine hover:bg-atlantia-blush' }}">
                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M4 5h8l8 8-7 7-8-8V5Zm4 4h.01" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                Ofertas
                            </a>

                            <form method="GET" action="{{ route('comercios.show', ['vendor' => $vendor->slug]) }}" class="flex-1 sm:flex-none">
                                @if ($filters['q'] !== '') <input type="hidden" name="q" value="{{ $filters['q'] }}"> @endif
                                @if ($filters['categoria']) <input type="hidden" name="categoria" value="{{ $filters['categoria'] }}"> @endif
                                @if ($filters['ofertas']) <input type="hidden" name="ofertas" value="1"> @endif
                                <select name="orden" data-auto-submit aria-label="Ordenar productos" class="h-10 w-full rounded-lg border border-atlantia-rose/20 bg-white px-3 text-sm font-bold text-atlantia-ink outline-none focus:border-atlantia-wine focus:ring-2 focus:ring-atlantia-blush sm:w-40">
                                    <option value="relevancia" @selected($filters['orden'] === 'relevancia')>Más recientes</option>
                                    <option value="precio_asc" @selected($filters['orden'] === 'precio_asc')>Menor precio</option>
                                    <option value="precio_desc" @selected($filters['orden'] === 'precio_desc')>Mayor precio</option>
                                    <option value="nombre" @selected($filters['orden'] === 'nombre')>Nombre A–Z</option>
                                </select>
                            </form>
                        </div>
                    </div>

                    @if ($availableCategories->count() > 1)
                        <nav class="mt-3 flex max-w-full gap-2 overflow-x-auto pb-1 [scrollbar-width:none] [&::-webkit-scrollbar]:hidden" aria-label="Categorías de la tienda">
                            <a href="{{ $storeRoute(['categoria' => null]) }}" class="{{ $chip }} {{ $filters['categoria'] === null ? $chipActivo : $chipNormal }}">Todo</a>
                            @foreach ($availableCategories as $category)
                                <a href="{{ $storeRoute(['categoria' => $category->id]) }}" class="{{ $chip }} {{ $filters['categoria'] === $category->id ? $chipActivo : $chipNormal }}">{{ $category->nombre }}</a>
                            @endforeach
                        </nav>
                    @endif

                    <div class="mt-4 flex items-center justify-between gap-3">
                        <h2 class="text-base font-black text-atlantia-ink">
                            {{ $filtrando ? 'Resultados' : 'Todos los productos' }}
                            <span class="ml-1 text-sm font-semibold text-atlantia-ink/45">{{ number_format($products->count()) }}</span>
                        </h2>
                        @if ($filtrando)
                            <a href="{{ route('comercios.show', ['vendor' => $vendor->slug]) }}" class="text-xs font-bold text-atlantia-wine underline decoration-atlantia-rose/40 underline-offset-4">Quitar filtros</a>
                        @endif
                    </div>

                    @if ($products->isEmpty())
                        <div class="mt-3 rounded-xl border border-dashed border-atlantia-rose/30 bg-white px-6 py-12 text-center">
                            <span class="mx-auto grid h-12 w-12 place-items-center rounded-full bg-atlantia-blush text-atlantia-wine">
                                <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle cx="11" cy="11" r="7" stroke="currentColor" stroke-width="1.8"/><path d="m16 16 4 4" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
                            </span>
                            @if ($filtrando)
                                <h3 class="mt-4 text-base font-black text-atlantia-ink">No encontramos productos con esa búsqueda</h3>
                                <p class="mt-1 text-sm text-atlantia-ink/55">Prueba con otra palabra o mira todo lo que vende esta tienda.</p>
                                <a href="{{ route('comercios.show', ['vendor' => $vendor->slug]) }}" class="mt-4 inline-flex h-10 items-center rounded-lg bg-atlantia-wine px-5 text-sm font-bold text-white">Ver todos los productos</a>
                            @else
                                <h3 class="mt-4 text-base font-black text-atlantia-ink">Esta tienda está preparando su catálogo</h3>
                                <p class="mt-1 text-sm text-atlantia-ink/55">Vuelve pronto para ver sus productos.</p>
                            @endif
                        </div>
                    @else
                        <div class="mt-3 grid grid-cols-2 gap-3 sm:grid-cols-3 xl:grid-cols-4 2xl:grid-cols-5" data-product-grid>
                            @foreach ($products as $producto)
                                @include('cliente.comercios.partials.product-card', ['producto' => $producto, 'money' => $money])
                            @endforeach
                        </div>
                    @endif
                </section>

                {{-- Lateral: pedido e información --}}
                <aside class="space-y-3 lg:sticky lg:top-24 lg:self-start" aria-label="Tu pedido e información de la tienda">
                    <div class="rounded-xl border border-atlantia-rose/15 bg-white p-4" id="tu-pedido">
                        <div class="flex items-center justify-between gap-3">
                            <h2 class="flex items-center gap-2 text-sm font-black text-atlantia-ink">
                                <svg class="h-5 w-5 text-atlantia-wine" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M4 5h2l2 10h9l2-7H7M10 20a1 1 0 1 0 0-2 1 1 0 0 0 0 2Zm7 0a1 1 0 1 0 0-2 1 1 0 0 0 0 2Z" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                Tu pedido
                            </h2>
                            <span class="grid h-6 min-w-6 place-items-center rounded-full bg-atlantia-wine px-1.5 text-xs font-black text-white">{{ $cartCount }}</span>
                        </div>
                        @if ($cartCount > 0)
                            <div class="mt-3 flex items-center justify-between text-sm">
                                <span class="text-atlantia-ink/55">Subtotal</span>
                                <span class="font-black text-atlantia-ink">{{ $money($cartSubtotal) }}</span>
                            </div>
                            <a href="{{ route('cliente.carrito.index') }}" class="mt-3 flex h-10 items-center justify-center rounded-lg bg-atlantia-wine px-4 text-sm font-bold text-white transition hover:bg-atlantia-wine-700">Ver carrito</a>
                        @else
                            <p class="mt-2 text-sm text-atlantia-ink/55">Aún no agregas productos de esta tienda.</p>
                        @endif
                    </div>

                    <div class="rounded-xl border border-atlantia-rose/15 bg-white p-4" id="informacion">
                        <h2 class="text-sm font-black text-atlantia-ink">Sobre la tienda</h2>
                        @if ($vendor->descripcion)
                            <p class="mt-2 line-clamp-4 text-sm leading-6 text-atlantia-ink/65" title="{{ $vendor->descripcion }}">{{ $vendor->descripcion }}</p>
                        @endif
                        <dl class="mt-3 space-y-2.5 text-sm">
                            @if ($direccion !== '')
                                <div class="flex gap-2.5">
                                    <dt><span class="sr-only">Dirección</span><svg class="mt-0.5 h-4 w-4 text-atlantia-wine" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M12 21s7-6.2 7-12a7 7 0 1 0-14 0c0 5.8 7 12 7 12Z" stroke="currentColor" stroke-width="1.8"/><circle cx="12" cy="9" r="2.5" stroke="currentColor" stroke-width="1.8"/></svg></dt>
                                    <dd class="text-atlantia-ink/70">{{ $direccion }}</dd>
                                </div>
                            @endif
                            <div class="flex gap-2.5">
                                <dt><span class="sr-only">Horario</span><svg class="mt-0.5 h-4 w-4 text-atlantia-wine" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M12 7v5l3 2M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg></dt>
                                <dd class="text-atlantia-ink/70">Todos los días, {{ $horarioTexto }}</dd>
                            </div>
                            <div class="flex gap-2.5">
                                <dt><span class="sr-only">Envío</span><svg class="mt-0.5 h-4 w-4 text-atlantia-wine" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M3 7h11v9H3zM14 10h4l3 3v3h-7M7 19a2 2 0 1 0 0-4 2 2 0 0 0 0 4Zm10 0a2 2 0 1 0 0-4 2 2 0 0 0 0 4Z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/></svg></dt>
                                <dd class="text-atlantia-ink/70">Envío: {{ \Illuminate\Support\Str::lcfirst($envioTexto) }}@if ($tiempoTexto) · llega en {{ $tiempoTexto }}@endif</dd>
                            </div>
                            @if ($vendor->telefono_publico)
                                <div class="flex gap-2.5">
                                    <dt><span class="sr-only">Teléfono</span><svg class="mt-0.5 h-4 w-4 text-atlantia-wine" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M5 4h4l2 5-2.5 1.5a11 11 0 0 0 5 5L15 13l5 2v4a1 1 0 0 1-1 1A16 16 0 0 1 4 5a1 1 0 0 1 1-1Z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/></svg></dt>
                                    <dd><a href="tel:{{ preg_replace('/[^\d+]/', '', $vendor->telefono_publico) }}" class="font-semibold text-atlantia-wine">{{ $vendor->telefono_publico }}</a></dd>
                                </div>
                            @endif
                        </dl>
                    </div>

                    <div class="rounded-xl border border-atlantia-rose/15 bg-white p-4" id="opiniones">
                        <h2 class="text-sm font-black text-atlantia-ink">Opiniones</h2>
                        @if ($ratingValue)
                            <div class="mt-2 flex items-baseline gap-2">
                                <span class="text-2xl font-black text-atlantia-ink">{{ $ratingValue }}</span>
                                <span class="text-amber-400" aria-hidden="true">{{ str_repeat('★', (int) round((float) $rating['rating'])) }}<span class="text-atlantia-ink/15">{{ str_repeat('★', 5 - (int) round((float) $rating['rating'])) }}</span></span>
                            </div>
                            <p class="mt-1 text-xs text-atlantia-ink/55">Según {{ number_format($ratingTotal) }} {{ $ratingTotal === 1 ? 'opinión' : 'opiniones' }} de compradores.</p>
                        @else
                            <p class="mt-2 text-sm text-atlantia-ink/55">Todavía no hay opiniones. Después de tu compra podrás calificar los productos.</p>
                        @endif
                    </div>

                    {{-- Denunciar la tienda --}}
                    @php
                        $bolsaDenuncia = $errors->getBag('denuncia');
                        $pedidosConTienda = auth()->check()
                            ? \App\Models\Pedido::query()->where('cliente_id', auth()->id())->where('vendor_id', $vendor->id)->latest('id')->limit(10)->get(['uuid', 'numero_pedido', 'pedido_padre_id', 'created_at'])
                            : collect();
                    @endphp
                    <details id="denunciar" class="rounded-xl border border-atlantia-rose/15 bg-white" @if ($bolsaDenuncia->any() || request('denunciar')) open @endif data-denunciar-tienda>
                        <summary class="cursor-pointer list-none px-4 py-3 text-xs font-bold text-atlantia-ink/55 hover:text-rose-700 [&::-webkit-details-marker]:hidden">Denunciar esta tienda</summary>
                        <div class="border-t border-atlantia-rose/10 px-4 py-3">
                            @auth
                                <form method="POST" action="{{ route('cliente.tiendas.denunciar', $vendor->slug) }}" class="space-y-2">
                                    @csrf
                                    <p class="text-xs text-atlantia-ink/60">Cuéntanos qué pasó. Atlantia revisa cada denuncia; tu nombre no se le muestra a la tienda.</p>
                                    <select name="motivo" required class="w-full rounded-lg border border-atlantia-rose/30 px-3 py-2 text-sm">
                                        <option value="">¿Qué pasó?</option>
                                        @foreach (\App\Models\DenunciaTienda::MOTIVOS as $clave => $texto)
                                            <option value="{{ $clave }}" @selected(old('motivo') === $clave)>{{ $texto }}</option>
                                        @endforeach
                                    </select>
                                    @if ($pedidosConTienda->isNotEmpty())
                                        <select name="pedido_uuid" class="w-full rounded-lg border border-atlantia-rose/30 px-3 py-2 text-sm">
                                            <option value="">Pedido relacionado (opcional)</option>
                                            @foreach ($pedidosConTienda as $pedidoTienda)
                                                <option value="{{ $pedidoTienda->uuid }}" @selected(old('pedido_uuid', request('pedido')) === $pedidoTienda->uuid)>Pedido del {{ $pedidoTienda->created_at?->format('d/m/Y') }}</option>
                                            @endforeach
                                        </select>
                                    @endif
                                    <textarea name="descripcion" rows="3" required minlength="15" maxlength="1500" placeholder="Describe lo que pasó con detalle" class="w-full resize-y rounded-lg border border-atlantia-rose/30 px-3 py-2 text-sm">{{ old('descripcion') }}</textarea>
                                    @if ($bolsaDenuncia->any())
                                        <p class="text-xs font-bold text-red-600">{{ $bolsaDenuncia->first() }}</p>
                                    @endif
                                    <button type="submit" class="h-10 w-full rounded-lg bg-rose-700 text-sm font-bold text-white hover:bg-rose-800">Enviar denuncia</button>
                                </form>
                            @else
                                <p class="text-xs text-atlantia-ink/60"><a href="{{ route('login') }}" class="font-bold text-atlantia-wine hover:underline">Inicia sesión</a> para denunciar una tienda.</p>
                            @endauth
                        </div>
                    </details>
                </aside>
            </div>
        </div>

        @if ($cartCount > 0)
            <a href="{{ route('cliente.carrito.index') }}" class="fixed inset-x-4 bottom-20 z-30 flex h-12 items-center justify-between rounded-xl bg-atlantia-wine px-4 text-sm font-bold text-white shadow-[0_12px_30px_rgba(58,0,4,0.35)] lg:hidden">
                <span class="flex items-center gap-2"><span class="grid h-6 min-w-6 place-items-center rounded-full bg-white px-1.5 text-xs font-black text-atlantia-wine">{{ $cartCount }}</span> Ver carrito</span>
                <span>{{ $money($cartSubtotal) }}</span>
            </a>
        @endif
    </div>
@endsection

@push('scripts')
    <script nonce="{{ request()->attributes->get('csp_nonce') }}">
        document.querySelectorAll('[data-auto-submit]').forEach((control) => {
            control.addEventListener('change', () => control.closest('form')?.submit());
        });
    </script>
@endpush
