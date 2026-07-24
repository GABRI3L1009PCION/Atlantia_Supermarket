@php
    $statusLabels = [
        'pendiente' => 'Pendiente',
        'confirmado' => 'Confirmado',
        'en_revision' => 'En revision',
        'preparando' => 'Preparando',
        'listo_para_entrega' => 'Listo para recoger',
        'en_ruta' => 'En camino',
        'entregado' => 'Entregado',
        'cancelado' => 'Cancelado',
        'rechazado' => 'Rechazado',
    ];
    $statusClasses = [
        'pendiente' => 'bg-amber-50 text-amber-700 ring-amber-200',
        'confirmado' => 'bg-sky-50 text-sky-700 ring-sky-200',
        'en_revision' => 'bg-violet-50 text-violet-700 ring-violet-200',
        'preparando' => 'bg-amber-50 text-amber-700 ring-amber-200',
        'listo_para_entrega' => 'bg-cyan-50 text-cyan-700 ring-cyan-200',
        'en_ruta' => 'bg-emerald-50 text-emerald-700 ring-emerald-200',
        'entregado' => 'bg-emerald-50 text-emerald-700 ring-emerald-200',
        'cancelado' => 'bg-rose-50 text-rose-700 ring-rose-200',
        'rechazado' => 'bg-rose-50 text-rose-700 ring-rose-200',
    ];
    $productImage = static function ($item): ?string {
        $product = $item->producto;
        $mediaUrl = $product?->getFirstMediaUrl('productos', 'thumbnail') ?: $product?->getFirstMediaUrl('productos');
        $legacyPath = $product?->imagenPrincipal?->path;

        if ($mediaUrl) {
            return $mediaUrl;
        }

        if (! $legacyPath) {
            return null;
        }

        return str_starts_with($legacyPath, 'http') ? $legacyPath : \Illuminate\Support\Facades\Storage::url($legacyPath);
    };
    $orderItems = static function ($order) {
        return $order->items->isNotEmpty() ? $order->items : $order->pedidosHijos->flatMap->items;
    };
    $orderVendors = static function ($order) {
        return $order->vendor
            ? collect([$order->vendor])
            : $order->pedidosHijos->pluck('vendor')->filter()->unique('id')->values();
    };
    $orderInvoices = static function ($order) {
        return $order->dteFacturas
            ->concat($order->pedidosHijos->flatMap->dteFacturas)
            ->filter(fn ($dte) => $dte->estado === 'certificado' && filled($dte->pdf_path))
            ->unique('id')
            ->values();
    };
@endphp

<div wire:poll.8s="refreshOrders" class="bg-[linear-gradient(180deg,#fff9fb_0%,#ffffff_42%)]">
    <section class="relative overflow-hidden border-b border-atlantia-rose/10">
        <img
            src="{{ asset('images/pedidos-header-linea.png') }}"
            alt=""
            aria-hidden="true"
            width="360"
            height="154"
            class="pointer-events-none absolute right-8 top-3 hidden h-[154px] w-[360px] object-contain opacity-45 xl:block"
        >

        <div class="relative mx-auto w-full max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
            <div>
                <h1 class="text-3xl font-black tracking-tight text-atlantia-ink sm:text-4xl">
                    {{ $tab === 'history' ? 'Historial de pedidos' : 'Tus pedidos' }}
                </h1>
                <p class="mt-2 text-sm font-semibold text-atlantia-ink/60">
                    {{ $tab === 'history' ? 'Revisa tus compras anteriores y descarga tus facturas.' : 'Consulta el estado actualizado de tus pedidos.' }}
                </p>
            </div>

            <div class="mt-5 grid overflow-hidden rounded-2xl border border-atlantia-rose/15 bg-atlantia-blush/45 sm:grid-cols-3" role="tablist" aria-label="Vistas de pedidos">
                <button
                    type="button"
                    wire:click="selectTab('active')"
                    class="flex min-h-11 items-center justify-center gap-2 px-4 text-sm font-black transition {{ $tab === 'active' ? 'bg-white text-atlantia-wine shadow-sm' : 'text-atlantia-ink/70 hover:bg-white/60' }}"
                    role="tab"
                    aria-selected="{{ $tab === 'active' ? 'true' : 'false' }}"
                >
                    Activos
                    <span class="rounded-full bg-atlantia-blush px-2 py-0.5 text-xs text-atlantia-wine">{{ (int) $summary['active'] }}</span>
                </button>
                <button
                    type="button"
                    wire:click="selectTab('empty')"
                    @disabled((int) $summary['total'] > 0)
                    class="flex min-h-11 items-center justify-center gap-2 border-y border-atlantia-rose/10 px-4 text-sm font-black transition sm:border-x sm:border-y-0 {{ $tab === 'empty' ? 'bg-white text-atlantia-wine shadow-sm' : 'text-atlantia-ink/70' }} disabled:cursor-default"
                    role="tab"
                    aria-selected="{{ $tab === 'empty' ? 'true' : 'false' }}"
                >
                    Sin pedidos
                    <span class="rounded-full bg-atlantia-blush px-2 py-0.5 text-xs text-atlantia-wine">0</span>
                </button>
                <button
                    type="button"
                    wire:click="selectTab('history')"
                    class="flex min-h-11 items-center justify-center gap-2 px-4 text-sm font-black transition {{ $tab === 'history' ? 'bg-white text-atlantia-wine shadow-sm' : 'text-atlantia-ink/70 hover:bg-white/60' }}"
                    role="tab"
                    aria-selected="{{ $tab === 'history' ? 'true' : 'false' }}"
                >
                    Historial
                    <span class="rounded-full bg-atlantia-blush px-2 py-0.5 text-xs text-atlantia-wine">{{ (int) $summary['closed'] }}</span>
                </button>
            </div>
        </div>
    </section>

    <section class="mx-auto w-full max-w-7xl px-4 py-5 sm:px-6 lg:px-8">
        <div wire:loading.delay class="mb-3 w-full rounded-lg bg-atlantia-blush px-4 py-2 text-center text-xs font-black text-atlantia-wine" role="status">
            Actualizando pedidos...
        </div>

        @if ($tab === 'empty')
            <div class="overflow-hidden rounded-2xl border border-atlantia-rose/12 bg-white px-6 py-8 text-center shadow-[0_18px_45px_rgba(116,15,48,0.07)] sm:px-10">
                <img
                    src="{{ asset('images/pedidos-sin-pedidos.png') }}"
                    alt="Bolsa de compras Atlantia"
                    width="340"
                    height="150"
                    class="mx-auto h-[150px] w-[340px] max-w-full object-contain"
                >
                <h2 class="mt-3 text-2xl font-black text-atlantia-ink sm:text-3xl">Aun no has realizado ningun pedido</h2>
                <p class="mx-auto mt-3 max-w-xl text-sm leading-6 text-atlantia-ink/65 sm:text-base">
                    Explora nuestros comercios y encuentra todo lo que necesitas. Tu primer pedido esta a un clic.
                </p>
                <div class="mt-6 flex flex-col justify-center gap-3 sm:flex-row">
                    <a href="{{ route('comercios.index') }}" class="inline-flex min-h-11 items-center justify-center gap-2 rounded-xl bg-atlantia-wine px-7 py-3 text-sm font-black text-white shadow-sm transition hover:bg-atlantia-wine-700">
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M4 9h16l-1 11H5L4 9Zm2-5h12l2 5H4l2-5Zm3 8v5m6-5v5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>
                        Explorar comercios
                    </a>
                    <a href="{{ route('categorias.index') }}" class="inline-flex min-h-11 items-center justify-center gap-2 rounded-xl border border-atlantia-rose/30 bg-white px-7 py-3 text-sm font-black text-atlantia-wine transition hover:bg-atlantia-blush">
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M4 4h6v6H4V4Zm10 0h6v6h-6V4ZM4 14h6v6H4v-6Zm10 0h6v6h-6v-6Z" stroke="currentColor" stroke-width="1.8"/></svg>
                        Ver categorias
                    </a>
                </div>
            </div>

            <div class="mt-4 grid overflow-hidden rounded-2xl border border-atlantia-rose/10 bg-white sm:grid-cols-3">
                @foreach ([
                    ['Entrega rapida', 'Tus pedidos donde los necesitas, cuando los necesitas.', 'truck'],
                    ['Comercios verificados', 'Compra unicamente en negocios aprobados.', 'shield'],
                    ['Pago protegido', 'Tus opciones de pago se manejan de forma segura.', 'lock'],
                ] as [$title, $description, $icon])
                    <article class="flex items-center gap-4 px-6 py-4 sm:border-r sm:last:border-r-0 sm:border-atlantia-rose/10">
                        <span class="grid h-11 w-11 shrink-0 place-items-center rounded-full bg-atlantia-blush text-atlantia-wine">
                            @if ($icon === 'truck')
                                <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M3 6h11v11H3V6Zm11 4h4l3 3v4h-7v-7ZM7 20a2 2 0 1 0 0-4 2 2 0 0 0 0 4Zm10 0a2 2 0 1 0 0-4 2 2 0 0 0 0 4Z" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round"/></svg>
                            @elseif ($icon === 'shield')
                                <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M12 3 19 6v5c0 4.5-2.8 8.4-7 10-4.2-1.6-7-5.5-7-10V6l7-3Zm-3 9 2 2 4-5" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/></svg>
                            @else
                                <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M7 10V8a5 5 0 0 1 10 0v2M5 10h14v10H5V10Z" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round"/></svg>
                            @endif
                        </span>
                        <div>
                            <h3 class="text-sm font-black text-atlantia-ink">{{ $title }}</h3>
                            <p class="mt-1 text-xs leading-5 text-atlantia-ink/60">{{ $description }}</p>
                        </div>
                    </article>
                @endforeach
            </div>
        @elseif ($tab === 'active')
            <div class="grid gap-5 xl:grid-cols-[minmax(0,1fr)_300px]">
                <div class="space-y-4">
                    @foreach ($activeOrders as $order)
                        @php
                            $state = $order->estadoValor();
                            $items = $orderItems($order);
                            $vendors = $orderVendors($order);
                            $vendor = $vendors->first();
                            $route = $order->deliveryRoute;
                            $progress = match ($state) {
                                'pendiente', 'confirmado', 'en_revision' => 25,
                                'preparando' => 50,
                                'listo_para_entrega' => 70,
                                'en_ruta' => 86,
                                default => 10,
                            };
                        @endphp
                        <article wire:key="active-order-{{ $order->uuid }}" class="overflow-hidden rounded-2xl border border-atlantia-rose/12 bg-white shadow-[0_14px_34px_rgba(116,15,48,0.07)]">
                            <div class="flex flex-col gap-4 border-b border-atlantia-rose/10 px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
                                <div class="flex min-w-0 items-center gap-4">
                                    <span class="grid h-12 w-12 shrink-0 place-items-center overflow-hidden rounded-full border border-atlantia-rose/15 bg-white text-sm font-black text-atlantia-wine">
                                        @if ($vendor?->logo_url)
                                            <img src="{{ $vendor->logo_url }}" alt="" class="h-full w-full object-cover">
                                        @else
                                            {{ str($vendor?->business_name ?? 'AT')->substr(0, 2)->upper() }}
                                        @endif
                                    </span>
                                    <div class="min-w-0">
                                        <p class="text-[10px] font-black uppercase text-atlantia-ink/45">{{ $vendors->count() > 1 ? $vendors->count().' comercios' : 'Comercio' }}</p>
                                        <h2 class="truncate text-lg font-black text-atlantia-ink">{{ $vendors->pluck('business_name')->join(', ') ?: 'Atlantia Delivery' }}</h2>
                                        <p class="mt-1 text-xs font-semibold text-atlantia-ink/50">{{ $order->numero_pedido }} · {{ ($order->confirmado_at ?? $order->created_at)->format('d/m/Y h:i a') }}</p>
                                    </div>
                                </div>
                                <span class="inline-flex shrink-0 items-center gap-2 rounded-full px-3 py-1.5 text-xs font-black ring-1 {{ $statusClasses[$state] ?? 'bg-slate-50 text-slate-700 ring-slate-200' }}">
                                    <span class="h-2 w-2 rounded-full bg-current opacity-75"></span>
                                    {{ $statusLabels[$state] ?? ucfirst($state) }}
                                </span>
                            </div>

                            <div class="grid gap-5 px-5 py-4 lg:grid-cols-[minmax(0,1fr)_1fr]">
                                <div class="flex min-w-0 items-center gap-2 overflow-hidden">
                                    @foreach ($items->take(5) as $item)
                                        @php
                                            $image = $productImage($item);
                                        @endphp
                                        <span class="grid h-12 w-12 shrink-0 place-items-center overflow-hidden rounded-lg border border-atlantia-rose/10 bg-atlantia-blush/35" title="{{ $item->producto_nombre_snapshot }}">
                                            @if ($image)
                                                <img src="{{ $image }}" alt="{{ $item->producto_nombre_snapshot }}" class="h-full w-full object-contain p-1" loading="lazy">
                                            @else
                                                <svg class="h-5 w-5 text-atlantia-wine/55" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M4 7 12 3l8 4v10l-8 4-8-4V7Zm0 0 8 4m8-4-8 4m0 10V11" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"/></svg>
                                            @endif
                                        </span>
                                    @endforeach
                                    @if ($items->count() > 5)
                                        <span class="grid h-12 w-12 shrink-0 place-items-center rounded-lg bg-atlantia-blush text-xs font-black text-atlantia-wine">+{{ $items->count() - 5 }}</span>
                                    @endif
                                </div>
                                <dl class="grid gap-3 text-xs sm:grid-cols-3">
                                    <div><dt class="font-bold text-atlantia-ink/45">Total</dt><dd class="mt-1 font-black text-atlantia-ink">Q{{ number_format((float) $order->total, 2) }}</dd></div>
                                    <div><dt class="font-bold text-atlantia-ink/45">Entrega estimada</dt><dd class="mt-1 font-black text-atlantia-ink">{{ $route?->tiempo_estimado_min ? $route->tiempo_estimado_min.' min' : 'Por confirmar' }}</dd></div>
                                    <div><dt class="font-bold text-atlantia-ink/45">Direccion</dt><dd class="mt-1 line-clamp-2 font-black text-atlantia-ink">{{ $order->direccion?->direccion_linea_1 ?? 'Direccion registrada' }}</dd></div>
                                </dl>
                            </div>

                            <div class="px-5 pb-4">
                                <div class="relative h-1 rounded-full bg-slate-200">
                                    <span class="absolute inset-y-0 left-0 rounded-full bg-atlantia-wine transition-all duration-500" style="width: {{ $progress }}%"></span>
                                </div>
                                <div class="mt-2 grid grid-cols-4 text-center text-[10px] font-bold text-atlantia-ink/55">
                                    <span>Confirmado</span><span>Preparando</span><span>En camino</span><span>Entregado</span>
                                </div>
                            </div>

                            <div class="grid gap-2 border-t border-atlantia-rose/10 px-5 py-3 sm:grid-cols-2">
                                <a href="{{ route('cliente.pedidos.seguimiento', $order) }}" class="inline-flex min-h-10 items-center justify-center gap-2 rounded-xl bg-atlantia-wine px-5 py-2 text-xs font-black text-white transition hover:bg-atlantia-wine-700">
                                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M12 21s7-6.1 7-12A7 7 0 1 0 5 9c0 5.9 7 12 7 12Zm0-9.2A2.8 2.8 0 1 1 12 6a2.8 2.8 0 0 1 0 5.8Z" stroke="currentColor" stroke-width="1.8"/></svg>
                                    Seguir pedido
                                </a>
                                <a href="{{ route('cliente.pedidos.show', $order) }}" class="inline-flex min-h-10 items-center justify-center rounded-xl border border-atlantia-rose/25 px-5 py-2 text-xs font-black text-atlantia-wine transition hover:bg-atlantia-blush">Ver detalle</a>
                            </div>
                        </article>
                    @endforeach
                </div>

                <aside class="space-y-4">
                    <section class="rounded-2xl border border-atlantia-rose/12 bg-white p-5 shadow-sm">
                        <h2 class="font-black text-atlantia-ink">Resumen de pedidos</h2>
                        <dl class="mt-4 space-y-4 text-sm">
                            <div class="flex items-center justify-between border-b border-atlantia-rose/10 pb-4"><dt class="text-atlantia-ink/60">Pedidos activos</dt><dd class="text-lg font-black text-atlantia-wine">{{ (int) $summary['active'] }}</dd></div>
                            <div class="flex items-center justify-between"><dt class="text-atlantia-ink/60">Total activo</dt><dd class="text-lg font-black text-atlantia-wine">Q{{ number_format((float) $summary['active_amount'], 2) }}</dd></div>
                        </dl>
                    </section>
                    <section class="rounded-2xl border border-atlantia-rose/12 bg-white p-5 shadow-sm">
                        <h2 class="font-black text-atlantia-ink">Informacion util</h2>
                        <div class="mt-4 grid gap-2 text-sm">
                            <a href="{{ route('cliente.direcciones.index') }}" class="flex items-center justify-between rounded-xl bg-atlantia-blush/45 px-4 py-3 font-bold text-atlantia-ink hover:bg-atlantia-blush"><span>Direcciones de entrega</span><span aria-hidden="true">›</span></a>
                            <a href="{{ route('contacto') }}" class="flex items-center justify-between rounded-xl bg-atlantia-blush/45 px-4 py-3 font-bold text-atlantia-ink hover:bg-atlantia-blush"><span>Ayuda y soporte</span><span aria-hidden="true">›</span></a>
                        </div>
                    </section>
                </aside>
            </div>
        @else
            <div class="grid gap-5 xl:grid-cols-[minmax(0,1fr)_300px]">
                <div>
                    <div class="mb-4 grid gap-3 rounded-2xl border border-atlantia-rose/12 bg-white p-4 shadow-sm md:grid-cols-[minmax(0,1fr)_220px]">
                        <label class="relative block">
                            <span class="sr-only">Buscar por comercio o numero de pedido</span>
                            <svg class="pointer-events-none absolute left-3 top-1/2 h-5 w-5 -translate-y-1/2 text-atlantia-wine" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="m21 21-4.3-4.3M10.8 18a7.2 7.2 0 1 1 0-14.4 7.2 7.2 0 0 1 0 14.4Z" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
                            <input wire:model.live.debounce.350ms="search" type="search" placeholder="Buscar comercio o numero de pedido" class="h-11 w-full rounded-xl border border-atlantia-rose/20 pl-11 pr-4 text-sm outline-none focus:border-atlantia-wine focus:ring-2 focus:ring-atlantia-blush">
                        </label>
                        <label>
                            <span class="sr-only">Filtrar por estado</span>
                            <select wire:model.live="historyStatus" class="h-11 w-full rounded-xl border border-atlantia-rose/20 bg-white px-4 text-sm font-bold text-atlantia-ink outline-none focus:border-atlantia-wine focus:ring-2 focus:ring-atlantia-blush">
                                <option value="all">Todos los estados</option>
                                <option value="entregado">Entregados</option>
                                <option value="cancelado">Cancelados</option>
                                <option value="rechazado">Rechazados</option>
                            </select>
                        </label>
                    </div>

                    <div class="space-y-3">
                        @forelse ($historyOrders as $order)
                            @php
                                $state = $order->estadoValor();
                                $items = $orderItems($order);
                                $vendors = $orderVendors($order);
                                $invoices = $orderInvoices($order);
                            @endphp
                            <article wire:key="history-order-{{ $order->uuid }}" class="rounded-2xl border border-atlantia-rose/12 bg-white p-4 shadow-sm">
                                <div class="grid gap-4 lg:grid-cols-[minmax(180px,.85fr)_minmax(0,1fr)_150px] lg:items-center">
                                    <div class="min-w-0">
                                        <p class="text-[10px] font-black uppercase text-atlantia-ink/45">{{ $vendors->count() > 1 ? $vendors->count().' comercios' : 'Comercio' }}</p>
                                        <h2 class="mt-1 truncate font-black text-atlantia-ink">{{ $vendors->pluck('business_name')->join(', ') ?: 'Atlantia Delivery' }}</h2>
                                        <p class="mt-1 text-xs font-semibold text-atlantia-ink/55">{{ $order->numero_pedido }}</p>
                                        <p class="mt-1 text-xs text-atlantia-ink/50">{{ ($order->confirmado_at ?? $order->created_at)->format('d/m/Y h:i a') }}</p>
                                    </div>
                                    <div class="flex min-w-0 items-center gap-2 overflow-hidden">
                                        @foreach ($items->take(4) as $item)
                                            @php
                                                $image = $productImage($item);
                                            @endphp
                                            <span class="grid h-11 w-11 shrink-0 place-items-center overflow-hidden rounded-lg border border-atlantia-rose/10 bg-atlantia-blush/35">
                                                @if ($image)
                                                    <img src="{{ $image }}" alt="" class="h-full w-full object-contain p-1" loading="lazy">
                                                @else
                                                    <svg class="h-5 w-5 text-atlantia-wine/55" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M4 7 12 3l8 4v10l-8 4-8-4V7Zm0 0 8 4m8-4-8 4m0 10V11" stroke="currentColor" stroke-width="1.6"/></svg>
                                                @endif
                                            </span>
                                        @endforeach
                                        <div class="ml-2 min-w-0">
                                            <p class="font-black text-atlantia-ink">Q{{ number_format((float) $order->total, 2) }}</p>
                                            <p class="mt-1 truncate text-xs text-atlantia-ink/50">{{ $items->sum('cantidad') }} productos</p>
                                        </div>
                                    </div>
                                    <div class="flex flex-col items-stretch gap-2">
                                        <span class="inline-flex items-center justify-center rounded-full px-3 py-1 text-xs font-black ring-1 {{ $statusClasses[$state] ?? 'bg-slate-50 text-slate-700 ring-slate-200' }}">{{ $statusLabels[$state] ?? ucfirst($state) }}</span>
                                        <a href="{{ route('cliente.pedidos.show', $order) }}" class="inline-flex min-h-9 items-center justify-center rounded-lg border border-atlantia-rose/25 px-3 text-xs font-black text-atlantia-wine hover:bg-atlantia-blush">Ver detalle</a>
                                    </div>
                                </div>

                                @if ($invoices->isNotEmpty())
                                    <div class="mt-4 flex flex-wrap items-center gap-2 border-t border-atlantia-rose/10 pt-3">
                                        <span class="mr-1 text-xs font-bold text-atlantia-ink/55">Facturas disponibles:</span>
                                        @foreach ($invoices as $invoice)
                                            <a
                                                href="{{ route('dte.pdf', ['dte' => $invoice, 'download' => 1]) }}"
                                                class="inline-flex min-h-9 items-center gap-2 rounded-lg bg-atlantia-blush px-3 text-xs font-black text-atlantia-wine transition hover:bg-atlantia-rose/20"
                                                wire:ignore
                                            >
                                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M12 3v12m0 0 4-4m-4 4-4-4M5 20h14" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                                Descargar {{ $invoice->numero_dte }}
                                            </a>
                                        @endforeach
                                    </div>
                                @elseif ($state === 'entregado')
                                    <p class="mt-3 border-t border-atlantia-rose/10 pt-3 text-xs font-semibold text-atlantia-ink/45">La factura estara disponible cuando finalice su certificacion FEL.</p>
                                @endif
                            </article>
                        @empty
                            <div class="rounded-2xl border border-dashed border-atlantia-rose/25 bg-white px-6 py-12 text-center">
                                <h2 class="text-xl font-black text-atlantia-ink">No encontramos pedidos</h2>
                                <p class="mt-2 text-sm text-atlantia-ink/60">Cambia el texto de busqueda o el filtro de estado.</p>
                            </div>
                        @endforelse
                    </div>

                    @if ($historyOrders?->hasPages())
                        <div class="mt-5">{{ $historyOrders->links() }}</div>
                    @endif
                </div>

                <aside class="rounded-2xl border border-atlantia-rose/12 bg-white p-5 shadow-sm xl:self-start">
                    <h2 class="font-black text-atlantia-ink">Resumen de tu historial</h2>
                    <dl class="mt-4 space-y-3 text-sm">
                        <div class="flex items-center justify-between rounded-xl bg-emerald-50 px-4 py-3"><dt class="text-emerald-800">Pedidos completados</dt><dd class="text-lg font-black text-emerald-800">{{ (int) $summary['completed'] }}</dd></div>
                        <div class="flex items-center justify-between rounded-xl bg-atlantia-blush/50 px-4 py-3"><dt class="text-atlantia-ink/65">Gasto total</dt><dd class="text-lg font-black text-atlantia-wine">Q{{ number_format((float) $summary['spent'], 2) }}</dd></div>
                        <div class="flex items-center justify-between rounded-xl bg-amber-50 px-4 py-3"><dt class="text-amber-800">Cancelados</dt><dd class="text-lg font-black text-amber-800">{{ (int) $summary['cancelled'] }}</dd></div>
                        <div class="flex items-center justify-between rounded-xl bg-violet-50 px-4 py-3"><dt class="text-violet-800">Promedio por pedido</dt><dd class="text-lg font-black text-violet-800">Q{{ number_format((float) $summary['average'], 2) }}</dd></div>
                    </dl>
                </aside>
            </div>
        @endif
    </section>
</div>
