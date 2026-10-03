@extends(auth()->user()?->isSuperAdmin() && request()->routeIs('admin.*') ? 'layouts.super-admin' : 'layouts.app')

@section('content')
    @php
        $filters = $reportes['filters'] ?? [];
        $enumValue = fn ($value) => $value instanceof \BackedEnum ? $value->value : (string) $value;
        $label = fn ($value) => ucfirst(str_replace('_', ' ', $enumValue($value)));
        $money = fn ($value) => 'Q ' . number_format((float) $value, 2);
        $ventasTotal = max(1, (float) ($reportes['ventas_total'] ?? 0));
        $pedidosTotal = max(1, (int) ($reportes['pedidos_total'] ?? 0));
        $ventasMax = max(1, (float) collect($reportes['ventas_por_periodo'] ?? [])->max('total'));
        $statusRows = collect($reportes['pedidos_por_estado'] ?? []);
        $statusColors = ['#f5b544', '#21a36a', '#ef4a72', '#4b8def', '#8b1d4d', '#7c6a74'];
        $firstVendor = collect($reportes['ingresos_por_vendedor'] ?? [])->first();
        $quickReports = [
            ['title' => 'Reporte de ventas', 'text' => 'Resumen de ventas por periodo.', 'tone' => 'rose'],
            ['title' => 'Reporte de pedidos', 'text' => 'Detalle de pedidos realizados.', 'tone' => 'amber'],
            ['title' => 'Reporte de productos', 'text' => 'Productos vendidos y rendimiento.', 'tone' => 'purple'],
            ['title' => 'Reporte de vendedores', 'text' => 'Rendimiento de vendedores.', 'tone' => 'green'],
            ['title' => 'Reporte de comisiones', 'text' => 'Comisiones pendientes y pagadas.', 'tone' => 'orange'],
            ['title' => 'Reporte DTE / FEL', 'text' => 'Documentos emitidos y rechazados.', 'tone' => 'pink'],
            ['title' => 'Reporte de metodos de pago', 'text' => 'Transacciones por metodo de pago.', 'tone' => 'violet'],
            ['title' => 'Reporte de alertas', 'text' => 'Alertas y notificaciones del sistema.', 'tone' => 'blue'],
        ];
    @endphp

    <section class="-mx-4 -my-6 overflow-x-hidden bg-white px-4 py-6 sm:-mx-6 sm:px-6 lg:-mx-8 lg:px-8">
        <div class="mx-auto max-w-7xl rounded-2xl border border-atlantia-rose/20 bg-white p-5 shadow-sm sm:p-7">
            <div>
                <div class="flex flex-col gap-2">
                    <div>
                        <p class="text-xs font-black uppercase tracking-[0.22em] text-atlantia-rose">Atlantia Supermarket</p>
                        <h1 class="mt-2 text-4xl font-black leading-tight text-atlantia-ink">Reportes</h1>
                        <p class="mt-1 text-sm text-atlantia-ink/65">Ventas, pedidos, productos y metodos de pago de Atlantia.</p>
                    </div>
                </div>

                <form id="reports-filter-form" method="GET" class="mt-6 rounded-2xl border border-atlantia-rose/15 bg-white p-4 shadow-sm">
                    <div class="grid gap-3 lg:grid-cols-12 lg:items-end">
                    <label class="lg:col-span-2">
                        <span class="mb-1 block text-xs font-black text-atlantia-ink/60">Desde</span>
                        <input type="date" name="fecha_desde" value="{{ $filters['fecha_desde'] ?? '' }}" class="h-11 w-full rounded-lg border border-atlantia-rose/30 bg-white px-3 text-sm font-semibold text-atlantia-ink outline-none transition focus:border-atlantia-wine focus:ring-2 focus:ring-atlantia-rose/20">
                    </label>
                    <label class="lg:col-span-2">
                        <span class="mb-1 block text-xs font-black text-atlantia-ink/60">Hasta</span>
                        <input type="date" name="fecha_hasta" value="{{ $filters['fecha_hasta'] ?? '' }}" class="h-11 w-full rounded-lg border border-atlantia-rose/30 bg-white px-3 text-sm font-semibold text-atlantia-ink outline-none transition focus:border-atlantia-wine focus:ring-2 focus:ring-atlantia-rose/20">
                    </label>
                    <label class="lg:col-span-3">
                        <span class="mb-1 block text-xs font-black text-atlantia-ink/60">Agrupar por</span>
                        <select name="agrupacion" class="h-11 w-full rounded-lg border border-atlantia-rose/30 bg-white px-3 text-sm font-semibold text-atlantia-ink outline-none transition focus:border-atlantia-wine focus:ring-2 focus:ring-atlantia-rose/20">
                            <option value="dia" @selected(($filters['agrupacion'] ?? 'dia') === 'dia')>Dia</option>
                            <option value="mes" @selected(($filters['agrupacion'] ?? 'dia') === 'mes')>Mes</option>
                        </select>
                    </label>
                    <div class="grid gap-2 sm:grid-cols-3 lg:col-span-5">
                        <button type="submit" class="inline-flex h-11 items-center justify-center gap-2 rounded-lg bg-atlantia-wine px-5 text-sm font-black text-white shadow-sm transition hover:bg-atlantia-wine-700">
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                <path d="M20 12a8 8 0 1 1-2.35-5.65M20 4v5h-5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                            Actualizar
                        </button>
                        <button type="button" class="inline-flex h-11 items-center justify-center gap-2 rounded-lg border border-atlantia-rose/35 bg-white px-4 text-sm font-black text-atlantia-wine transition hover:bg-atlantia-blush">
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                <path d="M12 3v12M8 11l4 4 4-4M5 19h14" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                            Exportar
                        </button>
                        <button type="button" data-print-report class="inline-flex h-11 items-center justify-center gap-2 rounded-lg border border-atlantia-rose/35 bg-white px-4 text-sm font-black text-atlantia-wine transition hover:bg-atlantia-blush">
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                <path d="M7 8V4h10v4M7 17H5a2 2 0 0 1-2-2v-4a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2v4a2 2 0 0 1-2 2h-2M7 14h10v6H7z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/>
                            </svg>
                            Imprimir
                        </button>
                    </div>
                    </div>
                </form>
            </div>

            <div class="mt-5 grid gap-3 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 2xl:grid-cols-7">
                @foreach ([
                    ['Ventas', $money($reportes['ventas_total'] ?? 0), 'Total ventas', 'cart', 'text-atlantia-wine bg-atlantia-blush'],
                    ['Pedidos', number_format((int) ($reportes['pedidos_total'] ?? 0)), 'Total pedidos', 'box', 'text-blue-700 bg-blue-50'],
                    ['Ticket promedio', $money($reportes['ticket_promedio'] ?? 0), 'Por pedido', 'ticket', 'text-purple-700 bg-purple-50'],
                    ['Vendedores activos', number_format((int) ($reportes['vendedores_activos'] ?? 0)), 'Activos', 'user', 'text-emerald-700 bg-emerald-50'],
                    ['Comisiones pendientes', number_format((int) ($reportes['comisiones_pendientes'] ?? 0)), 'Por pagar', 'wallet', 'text-amber-700 bg-amber-50'],
                    ['DTE rechazados', number_format((int) ($reportes['dtes_rechazados'] ?? 0)), 'Rechazados', 'doc', 'text-rose-700 bg-rose-50'],
                    ['Alertas pendientes', number_format((int) ($reportes['alertas_pendientes'] ?? 0)), 'Por revisar', 'bell', 'text-sky-700 bg-sky-50'],
                ] as [$title, $value, $hint, $icon, $tone])
                    <article class="rounded-xl border border-atlantia-rose/15 bg-white p-4 shadow-sm">
                        <div class="flex items-center gap-4">
                            <span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-full {{ $tone }}">
                                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                    @if ($icon === 'cart')
                                        <path d="M6 7h15l-2 7H8L6 4H3M9 20h.01M18 20h.01" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                                    @elseif ($icon === 'box')
                                        <path d="m12 3 8 4.5v9L12 21l-8-4.5v-9L12 3Z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/>
                                    @elseif ($icon === 'ticket')
                                        <path d="M4 8.5V6.75A1.75 1.75 0 0 1 5.75 5h12.5A1.75 1.75 0 0 1 20 6.75V8.5a2.5 2.5 0 0 0 0 5v1.75A1.75 1.75 0 0 1 18.25 17H5.75A1.75 1.75 0 0 1 4 15.25V13.5a2.5 2.5 0 0 0 0-5Z" stroke="currentColor" stroke-width="1.8"/>
                                    @elseif ($icon === 'user')
                                        <path d="M12 12a4 4 0 1 0 0-8 4 4 0 0 0 0 8ZM4.5 20c.55-4.15 3.3-6 7.5-6s6.95 1.85 7.5 6" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                                    @elseif ($icon === 'wallet')
                                        <path d="M5 7h13a2 2 0 0 1 2 2v8H5a2 2 0 0 1-2-2V7a2 2 0 0 1 2-2h11" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/>
                                    @elseif ($icon === 'doc')
                                        <path d="M7 3.75h7l3 3v13.5H7V3.75Z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/>
                                    @else
                                        <path d="M18 9a6 6 0 1 0-12 0c0 7-3 7-3 7h18s-3 0-3-7M10 20h4" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                                    @endif
                                </svg>
                            </span>
                            <div class="min-w-0">
                                <p class="text-xs font-black text-atlantia-ink/60">{{ $title }}</p>
                                <p class="mt-1 text-xl font-black text-atlantia-ink">{{ $value }}</p>
                                <p class="text-xs text-atlantia-ink/50">{{ $hint }}</p>
                            </div>
                        </div>
                    </article>
                @endforeach
            </div>

            <div class="mt-5 rounded-2xl border border-atlantia-rose/20 bg-white p-5">
                <div>
                    <h2 class="text-lg font-black text-atlantia-wine">Reportes disponibles</h2>
                    <p class="mt-1 text-sm text-atlantia-ink/60">Genera y consulta los reportes del sistema.</p>
                </div>

                <div class="mt-4 grid gap-3 md:grid-cols-2 xl:grid-cols-4 2xl:grid-cols-8">
                    @foreach ($quickReports as $quick)
                        <article class="rounded-xl border border-atlantia-rose/15 bg-white p-4 transition hover:-translate-y-0.5 hover:border-atlantia-wine/35 hover:shadow-md">
                            <div class="flex items-start gap-3">
                            <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-lg bg-atlantia-blush text-atlantia-wine">
                                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                    <path d="M5 4h14v16H5z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/>
                                    <path d="M8 9h8M8 13h8M8 17h4" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                                </svg>
                            </div>
                            <div class="min-w-0">
                                <h3 class="text-xs font-black leading-5 text-atlantia-ink">{{ $quick['title'] }}</h3>
                                <p class="mt-1 text-xs leading-5 text-atlantia-ink/55">{{ $quick['text'] }}</p>
                            </div>
                            </div>
                            <button type="button" class="mt-4 w-full rounded-md border border-atlantia-rose/30 px-3 py-1.5 text-xs font-black text-atlantia-wine transition hover:bg-atlantia-blush">
                                Generar
                            </button>
                        </article>
                    @endforeach
                </div>
            </div>

            <div class="mt-5 grid gap-4 xl:grid-cols-4">
                <article class="rounded-2xl border border-atlantia-rose/20 bg-white p-5">
                    <h2 class="text-sm font-black text-atlantia-ink">Ventas por periodo</h2>
                    <div class="mt-4 flex h-32 items-end justify-center gap-2 border-b border-atlantia-rose/15 px-3">
                        @forelse (($reportes['ventas_por_periodo'] ?? collect())->take(8) as $row)
                            <div class="group flex h-full flex-1 items-end justify-center">
                                <div class="w-full max-w-7 rounded-t-lg bg-atlantia-rose/70 transition group-hover:bg-atlantia-wine" style="height: {{ max(8, min(100, ((float) $row->total / $ventasMax) * 100)) }}%"></div>
                            </div>
                        @empty
                            <div class="flex h-full items-center justify-center text-center text-sm text-atlantia-ink/55">Sin ventas en este periodo.</div>
                        @endforelse
                    </div>
                    <a href="{{ route('admin.pedidos.index') }}" class="mt-4 inline-flex rounded-md border border-atlantia-rose/30 px-3 py-2 text-xs font-black text-atlantia-wine transition hover:bg-atlantia-blush">
                        Ver detalle de ventas
                    </a>
                </article>

                <article class="rounded-2xl border border-atlantia-rose/20 bg-white p-5">
                    <h2 class="text-sm font-black text-atlantia-ink">Pedidos por estado</h2>
                    <div class="mt-4 flex items-center gap-5">
                        <div class="flex h-28 w-28 shrink-0 items-center justify-center rounded-full" style="background: conic-gradient(#8b1d4d {{ min(100, (($statusRows->sum('pedidos') ?: 0) / $pedidosTotal) * 100) }}%, #f1e7ec 0);">
                            <div class="flex h-16 w-16 items-center justify-center rounded-full bg-white text-center">
                                <span class="text-lg font-black text-atlantia-ink">{{ number_format((int) ($reportes['pedidos_total'] ?? 0)) }}</span>
                            </div>
                        </div>
                        <div class="min-w-0 flex-1 space-y-2">
                            @forelse ($statusRows->take(5)->values() as $index => $row)
                                <div class="flex items-center justify-between gap-2 text-xs">
                                    <span class="flex min-w-0 items-center gap-2">
                                        <span class="h-2.5 w-2.5 rounded-full" style="background-color: {{ $statusColors[$index] ?? '#8b1d4d' }}"></span>
                                        <span class="truncate text-atlantia-ink/70">{{ $label($row->estado) }}</span>
                                    </span>
                                    <span class="font-black text-atlantia-ink">{{ $row->pedidos }}</span>
                                </div>
                            @empty
                                <p class="text-sm text-atlantia-ink/55">Sin pedidos en este periodo.</p>
                            @endforelse
                        </div>
                    </div>
                </article>

                <article class="rounded-2xl border border-atlantia-rose/20 bg-white p-5">
                    <h2 class="text-sm font-black text-atlantia-ink">Top productos</h2>
                    <div class="mt-4 space-y-3">
                        @forelse (($reportes['top_productos'] ?? collect())->take(3) as $producto)
                            <div class="rounded-xl border border-atlantia-rose/15 bg-atlantia-cream/50 px-3 py-2">
                                <p class="truncate text-sm font-black text-atlantia-ink">{{ $producto->nombre }}</p>
                                <p class="mt-1 text-xs text-atlantia-ink/60">{{ number_format((int) $producto->unidades) }} uds - {{ $money($producto->total) }}</p>
                            </div>
                        @empty
                            <p class="py-8 text-center text-sm text-atlantia-ink/55">Sin productos vendidos en este periodo.</p>
                        @endforelse
                    </div>
                    <a href="{{ route('admin.productos.index') }}" class="mt-4 inline-flex rounded-md border border-atlantia-rose/30 px-3 py-2 text-xs font-black text-atlantia-wine transition hover:bg-atlantia-blush">
                        Ver productos
                    </a>
                </article>

                <article class="rounded-2xl border border-atlantia-rose/20 bg-white p-5">
                    <h2 class="text-sm font-black text-atlantia-ink">Ingresos por vendedor</h2>
                    <div class="mt-4 space-y-3">
                        @forelse (($reportes['ingresos_por_vendedor'] ?? collect())->take(4) as $vendor)
                            <div class="flex items-center justify-between gap-3 rounded-xl border border-atlantia-rose/15 bg-atlantia-cream/40 px-3 py-2">
                                <div class="min-w-0">
                                    <p class="truncate text-sm font-black text-atlantia-ink">{{ $vendor->business_name }}</p>
                                    <p class="text-xs text-atlantia-ink/55">{{ number_format((int) $vendor->pedidos) }} pedidos</p>
                                </div>
                                <p class="shrink-0 text-sm font-black text-emerald-700">{{ $money($vendor->total_ventas) }}</p>
                            </div>
                        @empty
                            <p class="py-8 text-center text-sm text-atlantia-ink/55">Sin ingresos por vendedor.</p>
                        @endforelse
                    </div>
                    <a href="{{ route('admin.vendedores.index') }}" class="mt-4 inline-flex rounded-md border border-atlantia-rose/30 px-3 py-2 text-xs font-black text-atlantia-wine transition hover:bg-atlantia-blush">
                        Ver todos los vendedores
                    </a>
                </article>
            </div>

            <div class="mt-5 grid gap-4 xl:grid-cols-3">
                <article class="rounded-2xl border border-atlantia-rose/20 bg-white p-5">
                    <h2 class="text-sm font-black text-atlantia-ink">Metodos de pago</h2>
                    <div class="mt-4 space-y-3">
                        @forelse (($reportes['metodos_pago'] ?? collect())->take(4) as $row)
                            <div class="flex items-center justify-between rounded-xl border border-atlantia-rose/15 bg-atlantia-cream/45 px-3 py-2">
                                <span class="text-sm font-black text-atlantia-ink">{{ $label($row->metodo_pago) }}</span>
                                <span class="text-sm font-black text-atlantia-wine">{{ $money($row->total) }}</span>
                            </div>
                        @empty
                            <p class="py-8 text-center text-sm text-atlantia-ink/55">Sin pagos registrados en este periodo.</p>
                        @endforelse
                    </div>
                </article>

                <article class="rounded-2xl border border-atlantia-rose/20 bg-white p-5">
                    <h2 class="text-sm font-black text-atlantia-ink">DTE recientes</h2>
                    <div class="mt-4 space-y-3">
                        @forelse (($reportes['dtes_recientes'] ?? collect())->take(3) as $dte)
                            <div class="flex items-center justify-between rounded-xl border border-atlantia-rose/15 bg-atlantia-cream/45 px-3 py-2">
                                <div class="min-w-0">
                                    <p class="truncate text-sm font-black text-atlantia-ink">{{ $dte->numero_dte }}</p>
                                    <p class="text-xs text-atlantia-ink/55">{{ $dte->vendor?->business_name ?? 'Sin vendedor' }}</p>
                                </div>
                                <span class="rounded-md bg-atlantia-blush px-2 py-1 text-xs font-black text-atlantia-wine">{{ $dte->estado }}</span>
                            </div>
                        @empty
                            <p class="py-8 text-center text-sm text-atlantia-ink/55">Sin DTE recientes.</p>
                        @endforelse
                    </div>
                </article>

                <article class="rounded-2xl border border-atlantia-rose/20 bg-white p-5">
                    <div class="flex items-center justify-between gap-3">
                        <h2 class="text-sm font-black text-atlantia-ink">Alertas recientes</h2>
                        <a href="{{ route('admin.antifraude.index') }}" class="text-xs font-black text-atlantia-wine hover:underline">Ver todas</a>
                    </div>
                    <div class="mt-4 space-y-3">
                        @forelse (($reportes['alertas_recientes'] ?? collect())->take(3) as $alerta)
                            <div class="rounded-xl border border-amber-200 bg-amber-50 px-3 py-2">
                                <div class="flex items-center justify-between gap-3">
                                    <p class="truncate text-sm font-black text-amber-900">{{ $alerta->tipo }}</p>
                                    <span class="rounded-md bg-amber-100 px-2 py-1 text-xs font-black text-amber-700">{{ $alerta->revisada ? 'Revisada' : 'Pendiente' }}</span>
                                </div>
                                <p class="mt-1 text-xs text-amber-800">{{ $alerta->pedido?->numero_pedido ?? 'Sin pedido' }} - {{ $alerta->user?->name ?? 'Sin usuario' }}</p>
                            </div>
                        @empty
                            <p class="py-8 text-center text-sm text-atlantia-ink/55">Sin alertas recientes.</p>
                        @endforelse
                    </div>
                </article>
            </div>
        </div>
    </section>
@endsection

@push('scripts')
<script nonce="{{ request()->attributes->get('csp_nonce') }}">
    document.querySelector('[data-print-report]')?.addEventListener('click', () => window.print());
</script>
@endpush
