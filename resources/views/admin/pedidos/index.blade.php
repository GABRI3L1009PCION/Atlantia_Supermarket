@extends(auth()->user()?->isSuperAdmin() && request()->routeIs('admin.*') ? 'layouts.super-admin' : 'layouts.app')

@section('content')
    @php
        $estadoOptions = ['pendiente', 'confirmado', 'preparando', 'en_ruta', 'entregado', 'cancelado'];
        $estadoPagoOptions = ['pendiente', 'validando', 'pagado', 'rechazado', 'reembolsado'];
        $metodoPagoOptions = ['efectivo', 'transferencia', 'tarjeta'];
        $metrics = $pedidoMetrics ?? ['today' => 0, 'pending' => 0, 'delivered' => 0, 'paid' => 0];
        $paymentClasses = [
            'pagado' => 'bg-atlantia-blush text-atlantia-wine',
            'aprobado' => 'bg-emerald-50 text-emerald-700',
            'pendiente' => 'bg-amber-50 text-amber-700',
            'validando' => 'bg-sky-50 text-sky-700',
            'rechazado' => 'bg-red-50 text-red-700',
            'reembolsado' => 'bg-slate-100 text-slate-700',
        ];
    @endphp

    <section class="-mx-4 -my-6 bg-atlantia-cream/35 px-4 py-6 text-atlantia-ink sm:-mx-6 sm:px-6 lg:-mx-8 lg:px-8">
        <div class="mx-auto max-w-7xl">
            <header class="mb-6">
                <p class="text-xs font-black uppercase tracking-[0.18em] text-atlantia-rose">Atlantia Supermarket</p>
                <h1 class="mt-2 text-5xl font-black leading-tight text-atlantia-ink">Pedidos</h1>
                <p class="mt-3 text-base text-atlantia-ink/65">Supervisa el flujo comercial, pagos y reparto de todo el marketplace.</p>
            </header>

            <div class="rounded-xl border border-atlantia-rose/20 bg-white p-4 shadow-sm">
                <form method="GET" class="grid gap-3 lg:grid-cols-[1fr_240px_240px_160px]">
                    <label class="relative block">
                        <span class="pointer-events-none absolute left-4 top-1/2 -translate-y-1/2 text-atlantia-ink/45">
                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M11 19C15.4 19 19 15.4 19 11C19 6.6 15.4 3 11 3C6.6 3 3 6.6 3 11C3 15.4 6.6 19 11 19Z" stroke="currentColor" stroke-width="2"/><path d="M20.5 20.5L16.7 16.7" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
                        </span>
                        <input type="search" name="q" value="{{ request('q') }}" placeholder="Buscar por numero, cliente, vendedor o UUID" class="w-full rounded-md border border-atlantia-rose/30 bg-white py-3 pl-12 pr-4 text-sm outline-none transition focus:border-atlantia-wine focus:ring-2 focus:ring-atlantia-rose/20">
                    </label>
                    <select name="estado" class="rounded-md border border-atlantia-rose/30 bg-white px-4 py-3 text-sm outline-none transition focus:border-atlantia-wine focus:ring-2 focus:ring-atlantia-rose/20">
                        <option value="">Todos los estados</option>
                        @foreach ($estadoOptions as $estado)
                            <option value="{{ $estado }}" @selected(request('estado') === $estado)>{{ ucfirst(str_replace('_', ' ', $estado)) }}</option>
                        @endforeach
                    </select>
                    <select name="metodo_pago" class="rounded-md border border-atlantia-rose/30 bg-white px-4 py-3 text-sm outline-none transition focus:border-atlantia-wine focus:ring-2 focus:ring-atlantia-rose/20">
                        <option value="">Todos los metodos</option>
                        @foreach ($metodoPagoOptions as $metodo)
                            <option value="{{ $metodo }}" @selected(request('metodo_pago') === $metodo)>{{ ucfirst($metodo) }}</option>
                        @endforeach
                    </select>
                    <button type="submit" class="inline-flex items-center justify-center gap-2 rounded-md bg-atlantia-wine px-5 py-3 text-sm font-black text-white shadow-sm transition hover:bg-atlantia-wine-700">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M4 6H20M7 12H17M10 18H14" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
                        Filtrar
                    </button>
                </form>

                <form method="POST" action="{{ route('admin.pedidos.batch-update') }}" class="mt-4 rounded-xl bg-atlantia-cream/45 p-3" id="orders-batch-form">
                    @csrf
                    <div class="grid gap-3 lg:grid-cols-[220px_220px_1fr_220px]">
                        <select name="estado" class="rounded-md border border-atlantia-rose/30 bg-white px-4 py-3 text-sm outline-none transition focus:border-atlantia-wine focus:ring-2 focus:ring-atlantia-rose/20">
                            <option value="" disabled selected>Nuevo estado</option>
                            @foreach ($estadoOptions as $estado)
                                <option value="{{ $estado }}">{{ ucfirst(str_replace('_', ' ', $estado)) }}</option>
                            @endforeach
                        </select>
                        <select name="estado_pago" class="rounded-md border border-atlantia-rose/30 bg-white px-4 py-3 text-sm outline-none transition focus:border-atlantia-wine focus:ring-2 focus:ring-atlantia-rose/20">
                            <option value="" disabled selected>Estado de pago</option>
                            @foreach ($estadoPagoOptions as $estadoPago)
                                <option value="{{ $estadoPago }}">{{ ucfirst($estadoPago) }}</option>
                            @endforeach
                        </select>
                        <input type="text" name="notas_historial" placeholder="Nota comun para el historial del lote" class="rounded-md border border-atlantia-rose/30 bg-white px-4 py-3 text-sm outline-none transition focus:border-atlantia-wine focus:ring-2 focus:ring-atlantia-rose/20">
                        <button type="submit" class="inline-flex items-center justify-center gap-2 rounded-md bg-atlantia-wine px-5 py-3 text-sm font-black text-white shadow-sm transition hover:bg-atlantia-wine-700">
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M12 3V15" stroke="currentColor" stroke-width="2" stroke-linecap="round"/><path d="M7 10L12 15L17 10" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><path d="M5 19H19" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
                            Actualizar lote
                        </button>
                    </div>
                </form>
            </div>

            <div class="mt-5 grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                <article class="rounded-xl border border-atlantia-rose/20 bg-white p-5 shadow-sm">
                    <div class="flex items-center gap-4">
                        <span class="grid h-14 w-14 place-items-center rounded-full bg-atlantia-blush text-atlantia-wine">
                            <svg class="h-7 w-7" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M7 8V6C7 3.8 8.8 2 11 2H13C15.2 2 17 3.8 17 6V8" stroke="currentColor" stroke-width="2"/><path d="M5 8H19L18 21H6L5 8Z" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/></svg>
                        </span>
                        <div>
                            <p class="text-sm font-semibold text-atlantia-ink/65">Pedidos hoy</p>
                            <p class="mt-1 text-2xl font-black text-atlantia-wine">{{ number_format($metrics['today']) }}</p>
                            <p class="text-xs text-atlantia-ink/50">Datos reales</p>
                        </div>
                    </div>
                </article>
                <article class="rounded-xl border border-atlantia-rose/20 bg-white p-5 shadow-sm">
                    <div class="flex items-center gap-4">
                        <span class="grid h-14 w-14 place-items-center rounded-full bg-amber-50 text-amber-700">
                            <svg class="h-7 w-7" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M12 7V12L15 15" stroke="currentColor" stroke-width="2" stroke-linecap="round"/><path d="M21 12C21 17 17 21 12 21C7 21 3 17 3 12C3 7 7 3 12 3C17 3 21 7 21 12Z" stroke="currentColor" stroke-width="2"/></svg>
                        </span>
                        <div>
                            <p class="text-sm font-semibold text-atlantia-ink/65">Pendientes</p>
                            <p class="mt-1 text-2xl font-black text-atlantia-wine">{{ number_format($metrics['pending']) }}</p>
                            <p class="text-xs text-atlantia-ink/50">En flujo operativo</p>
                        </div>
                    </div>
                </article>
                <article class="rounded-xl border border-atlantia-rose/20 bg-white p-5 shadow-sm">
                    <div class="flex items-center gap-4">
                        <span class="grid h-14 w-14 place-items-center rounded-full bg-emerald-50 text-emerald-700">
                            <svg class="h-7 w-7" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M20 6L9 17L4 12" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                        </span>
                        <div>
                            <p class="text-sm font-semibold text-atlantia-ink/65">Entregados</p>
                            <p class="mt-1 text-2xl font-black text-atlantia-wine">{{ number_format($metrics['delivered']) }}</p>
                            <p class="text-xs text-atlantia-ink/50">Historico</p>
                        </div>
                    </div>
                </article>
                <article class="rounded-xl border border-atlantia-rose/20 bg-white p-5 shadow-sm">
                    <div class="flex items-center gap-4">
                        <span class="grid h-14 w-14 place-items-center rounded-full bg-violet-50 text-violet-700">
                            <svg class="h-7 w-7" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M3 6H21V18H3V6Z" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/><path d="M3 10H21" stroke="currentColor" stroke-width="2"/><path d="M7 15H10" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
                        </span>
                        <div>
                            <p class="text-sm font-semibold text-atlantia-ink/65">Pagados</p>
                            <p class="mt-1 text-2xl font-black text-atlantia-wine">{{ number_format($metrics['paid']) }}</p>
                            <p class="text-xs text-atlantia-ink/50">Pago confirmado</p>
                        </div>
                    </div>
                </article>
            </div>

            <div class="mt-5">
                <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                    @forelse ($pedidos as $pedido)
                        @php
                            $estadoPedido = str_replace('_', ' ', $pedido->estadoValor());
                            $estadoPago = str_replace('_', ' ', $pedido->estadoPagoValor());
                            $paymentClass = $paymentClasses[$pedido->estadoPagoValor()] ?? 'bg-atlantia-blush text-atlantia-wine';
                        @endphp

                        <article class="rounded-xl border border-atlantia-rose/20 bg-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:border-atlantia-wine/35 hover:shadow-md">
                            <div class="flex items-start justify-between gap-3">
                                <label class="inline-flex items-center gap-2 text-xs font-black text-atlantia-ink/65">
                                    <input form="orders-batch-form" type="checkbox" name="pedidos[]" value="{{ $pedido->uuid }}" class="h-4 w-4 rounded border-atlantia-rose/40 text-atlantia-wine focus:ring-atlantia-wine">
                                    Seleccionar
                                </label>
                                <span class="{{ $paymentClass }} rounded-full px-4 py-1 text-xs font-black">
                                    {{ $estadoPago }}
                                </span>
                            </div>

                            <div class="mt-4">
                                <p class="text-xl font-black text-atlantia-ink">{{ $pedido->numero_pedido }}</p>
                                <p class="mt-1 text-sm font-semibold text-atlantia-ink/55">{{ $pedido->created_at?->format('d/m/Y H:i') }}</p>
                            </div>

                            <div class="mt-4 grid grid-cols-2 gap-0 overflow-hidden rounded-lg border border-atlantia-rose/15 bg-atlantia-cream/40 text-sm">
                                <div class="border-r border-atlantia-rose/15 p-4">
                                    <div class="flex items-start gap-3">
                                        <svg class="mt-1 h-5 w-5 shrink-0 text-atlantia-ink/65" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M12 12C14.2 12 16 10.2 16 8C16 5.8 14.2 4 12 4C9.8 4 8 5.8 8 8C8 10.2 9.8 12 12 12Z" stroke="currentColor" stroke-width="2"/><path d="M5 21C5.8 17.6 8.5 15.5 12 15.5C15.5 15.5 18.2 17.6 19 21" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
                                        <div class="min-w-0">
                                            <p class="text-xs text-atlantia-ink/55">Cliente</p>
                                            <p class="truncate font-black text-atlantia-ink">{{ $pedido->cliente?->name ?? 'Sin cliente' }}</p>
                                        </div>
                                    </div>
                                </div>
                                <div class="p-4">
                                    <div class="flex items-start gap-3">
                                        <svg class="mt-1 h-5 w-5 shrink-0 text-atlantia-ink/65" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M4 10L5.5 5H18.5L20 10" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/><path d="M5 10H19V19H5V10Z" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/><path d="M9 14H15" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
                                        <div class="min-w-0">
                                            <p class="text-xs text-atlantia-ink/55">Vendedor</p>
                                            <p class="truncate font-black text-atlantia-ink">{{ $pedido->vendor?->business_name ?? 'Pedido consolidado' }}</p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="mt-4 grid grid-cols-2 gap-3 text-sm">
                                <div class="rounded-lg border border-atlantia-rose/15 bg-white px-4 py-3">
                                    <p class="text-xs text-atlantia-ink/55">Total</p>
                                    <p class="mt-1 text-xl font-black text-atlantia-wine">Q{{ number_format((float) $pedido->total, 2) }}</p>
                                </div>
                                <div class="rounded-lg border border-atlantia-rose/15 bg-white px-4 py-3">
                                    <div class="flex items-start gap-3">
                                        <svg class="mt-1 h-5 w-5 shrink-0 text-atlantia-ink/65" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M3 7H15V17H3V7Z" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/><path d="M15 10H19L21 13V17H15V10Z" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/><path d="M7 19C8.1 19 9 18.1 9 17H5C5 18.1 5.9 19 7 19Z" stroke="currentColor" stroke-width="2"/><path d="M18 19C19.1 19 20 18.1 20 17H16C16 18.1 16.9 19 18 19Z" stroke="currentColor" stroke-width="2"/></svg>
                                        <div class="min-w-0">
                                            <p class="text-xs text-atlantia-ink/55">Entrega</p>
                                            <p class="truncate font-black capitalize text-atlantia-ink">{{ $estadoPedido }}</p>
                                            <p class="truncate text-xs text-atlantia-ink/55">{{ $pedido->deliveryRoute?->repartidor?->name ?? 'Sin asignar' }}</p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="mt-4 flex justify-end">
                                <a href="{{ route('admin.pedidos.show', $pedido->uuid) }}" class="inline-flex items-center gap-2 rounded-md bg-atlantia-wine px-5 py-2 text-sm font-black text-white transition hover:bg-atlantia-wine-700">
                                    Gestionar
                                    <span aria-hidden="true">></span>
                                </a>
                            </div>
                        </article>
                    @empty
                        <div class="rounded-xl border border-dashed border-atlantia-rose/30 bg-white px-4 py-10 text-center md:col-span-2 xl:col-span-3">
                            <p class="text-base font-black text-atlantia-ink">No hay pedidos registrados.</p>
                            <p class="mt-1 text-sm text-atlantia-ink/60">Cuando entren ordenes, apareceran como tarjetas aqui.</p>
                        </div>
                    @endforelse
                </div>
            </div>

            <div class="mt-6 flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                <p class="text-sm font-semibold text-atlantia-ink/65">
                    Mostrando {{ $pedidos->firstItem() ?? 0 }} a {{ $pedidos->lastItem() ?? 0 }} de {{ $pedidos->total() }} resultados
                </p>
                <div>{{ $pedidos->links() }}</div>
            </div>
        </div>
    </section>
@endsection
