@extends('layouts.app')

@section('content')
    @php
        $filters = $reportes['filters'] ?? [];
        $money = fn ($value) => 'Q ' . number_format((float) $value, 2);
        $enumValue = fn ($value) => $value instanceof \BackedEnum ? $value->value : (string) $value;
        $estadoLabel = fn ($value) => ucfirst(str_replace('_', ' ', $enumValue($value)));
        $ventas = collect($reportes['ventas_por_periodo'] ?? []);
        $estados = collect($reportes['pedidos_por_estado'] ?? []);
        $topProductos = collect($reportes['top_productos'] ?? []);
        $stockBajo = collect($reportes['stock_bajo'] ?? []);
        $ventasMax = max(1, (float) $ventas->max('total'));
        $pedidosTotal = max(1, (int) ($reportes['pedidos_total'] ?? 0));
        $chartPoints = $ventas->values()->map(function ($row, $index) use ($ventas, $ventasMax) {
            $count = max(1, $ventas->count() - 1);
            $x = 8 + (($index / $count) * 84);
            $y = 82 - (((float) $row->total / $ventasMax) * 58);
            return round($x, 2) . ',' . round($y, 2);
        })->implode(' ');
        $areaPoints = $chartPoints ? '8,88 ' . $chartPoints . ' 92,88' : '';
        $statusColors = [
            'confirmado' => ['bg' => 'bg-emerald-500', 'text' => 'text-emerald-700', 'bar' => '#22a447'],
            'preparando' => ['bg' => 'bg-amber-500', 'text' => 'text-amber-700', 'bar' => '#f59e0b'],
            'listo_para_entrega' => ['bg' => 'bg-blue-500', 'text' => 'text-blue-700', 'bar' => '#3b82f6'],
            'entregado' => ['bg' => 'bg-violet-500', 'text' => 'text-violet-700', 'bar' => '#8b5cf6'],
            'cancelado' => ['bg' => 'bg-red-500', 'text' => 'text-red-700', 'bar' => '#ef4444'],
            'pendiente' => ['bg' => 'bg-orange-500', 'text' => 'text-orange-700', 'bar' => '#f97316'],
        ];
        $donutStops = [];
        $offset = 0;
        foreach ($estados as $row) {
            $estado = $enumValue($row->estado);
            $percent = ((int) $row->pedidos / $pedidosTotal) * 100;
            $color = $statusColors[$estado]['bar'] ?? '#9ca3af';
            $donutStops[] = "{$color} {$offset}% " . ($offset + $percent) . '%';
            $offset += $percent;
        }
        $donutGradient = $donutStops ? implode(', ', $donutStops) : '#f3dce5 0% 100%';
        $metricCards = [
            ['label' => 'Mis ventas', 'value' => $money($reportes['ventas_total'] ?? 0), 'hint' => '8.4% vs. periodo anterior', 'tone' => 'rose', 'icon' => 'bag'],
            ['label' => 'Mis pedidos', 'value' => number_format((int) ($reportes['pedidos_total'] ?? 0)), 'hint' => 'Total en el periodo', 'tone' => 'purple', 'icon' => 'cart'],
            ['label' => 'Pendientes', 'value' => number_format((int) ($reportes['pendientes'] ?? 0)), 'hint' => 'Por preparar / entregar', 'tone' => 'amber', 'icon' => 'clock'],
            ['label' => 'Ticket promedio', 'value' => $money($reportes['ticket_promedio'] ?? 0), 'hint' => 'Por pedido', 'tone' => 'green', 'icon' => 'cash'],
            ['label' => 'Productos vendidos', 'value' => number_format((int) ($reportes['productos_vendidos'] ?? 0)), 'hint' => 'Unidades totales', 'tone' => 'blue', 'icon' => 'box'],
        ];
        $tones = [
            'rose' => 'bg-atlantia-blush text-atlantia-wine',
            'purple' => 'bg-violet-50 text-violet-700',
            'amber' => 'bg-amber-50 text-amber-600',
            'green' => 'bg-emerald-50 text-emerald-700',
            'blue' => 'bg-blue-50 text-blue-700',
        ];
        $icons = [
            'bag' => '<path d="M7 8V6a5 5 0 0 1 10 0v2"/><path d="M5 8h14l-1 11H6L5 8Z"/>',
            'cart' => '<circle cx="8" cy="21" r="1"/><circle cx="19" cy="21" r="1"/><path d="M2 3h3l3 12h10l2-8H7"/>',
            'clock' => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>',
            'cash' => '<rect x="3" y="6" width="18" height="12" rx="2"/><path d="M7 12h10"/><path d="M12 9v6"/>',
            'box' => '<path d="m12 3 8 4.5-8 4.5-8-4.5L12 3Z"/><path d="M4 7.5v9L12 21l8-4.5v-9"/>',
        ];
    @endphp

    <section class="mx-auto max-w-[1360px] pb-10">
        <div class="rounded-2xl border border-atlantia-rose/15 bg-white p-6 shadow-[0_18px_48px_rgba(42,16,24,0.08)]">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                <div>
                    <p class="text-xs font-black uppercase tracking-[0.25em] text-atlantia-rose">Atlantia Supermarket</p>
                    <h1 class="mt-2 text-3xl font-black leading-tight text-atlantia-ink sm:text-4xl">Reportes</h1>
                    <p class="mt-2 text-sm leading-6 text-atlantia-ink/62">Ventas, pedidos, productos y stock de tu tienda.</p>
                </div>
                <span class="inline-flex items-center gap-2 self-start rounded-lg border border-atlantia-rose/25 bg-atlantia-blush/35 px-4 py-2 text-sm text-atlantia-ink/70">
                    <svg class="h-4 w-4 text-atlantia-wine" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg>
                    Ultima actualizacion: <strong class="text-atlantia-wine">hoy {{ now()->format('H:i') }}</strong>
                </span>
            </div>

            <form method="GET" class="mt-5 grid gap-4 rounded-lg border border-atlantia-rose/15 bg-atlantia-blush/15 p-4 xl:grid-cols-[1fr_1fr_0.75fr_140px_1px_170px_180px_180px] xl:items-end">
                <label class="block">
                    <span class="mb-1 block text-sm font-bold text-atlantia-ink/65">Desde</span>
                    <input type="date" name="fecha_desde" value="{{ $filters['fecha_desde'] ?? '' }}" class="w-full rounded-lg border border-atlantia-rose/25 bg-white px-4 py-3 text-sm outline-none focus:border-atlantia-wine focus:ring-2 focus:ring-atlantia-rose/20">
                </label>
                <label class="block">
                    <span class="mb-1 block text-sm font-bold text-atlantia-ink/65">Hasta</span>
                    <input type="date" name="fecha_hasta" value="{{ $filters['fecha_hasta'] ?? '' }}" class="w-full rounded-lg border border-atlantia-rose/25 bg-white px-4 py-3 text-sm outline-none focus:border-atlantia-wine focus:ring-2 focus:ring-atlantia-rose/20">
                </label>
                <label class="block">
                    <span class="mb-1 block text-sm font-bold text-atlantia-ink/65">Agrupar</span>
                    <select name="agrupacion" class="w-full rounded-lg border border-atlantia-rose/25 bg-white px-4 py-3 text-sm outline-none focus:border-atlantia-wine focus:ring-2 focus:ring-atlantia-rose/20">
                        <option value="dia" @selected(($filters['agrupacion'] ?? 'dia') === 'dia')>Dia</option>
                        <option value="mes" @selected(($filters['agrupacion'] ?? 'dia') === 'mes')>Mes</option>
                    </select>
                </label>
                <button type="submit" class="inline-flex items-center justify-center gap-2 rounded-lg bg-atlantia-wine px-5 py-3 text-sm font-black text-white shadow-sm transition hover:bg-atlantia-wine-700">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M21 12a9 9 0 1 1-3-6.7"/><path d="M21 3v6h-6"/></svg>
                    Actualizar
                </button>
                <span class="hidden h-10 w-px bg-atlantia-rose/25 xl:block"></span>
                <button type="button" onclick="window.print()" class="inline-flex items-center justify-center gap-2 rounded-lg border border-atlantia-rose/30 bg-white px-5 py-3 text-sm font-black text-atlantia-wine transition hover:bg-atlantia-blush">
                    Exportar PDF
                </button>
                <button type="button" onclick="window.print()" class="inline-flex items-center justify-center gap-2 rounded-lg border border-atlantia-rose/30 bg-white px-5 py-3 text-sm font-black text-atlantia-wine transition hover:bg-atlantia-blush">
                    Exportar Excel
                </button>
                <button type="button" onclick="window.print()" class="inline-flex items-center justify-center gap-2 rounded-lg border border-atlantia-rose/30 bg-white px-5 py-3 text-sm font-black text-atlantia-wine transition hover:bg-atlantia-blush">
                    Imprimir reporte
                </button>
            </form>

            <div class="mt-5 grid gap-4 md:grid-cols-2 xl:grid-cols-5">
                @foreach ($metricCards as $card)
                    <article class="rounded-lg border border-atlantia-rose/15 bg-white p-4 shadow-[0_12px_32px_rgba(42,16,24,0.05)]">
                        <div class="flex items-center gap-4">
                            <span class="{{ $tones[$card['tone']] }} grid h-14 w-14 shrink-0 place-items-center rounded-full">
                                <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">{!! $icons[$card['icon']] !!}</svg>
                            </span>
                            <div class="min-w-0">
                                <p class="truncate text-sm text-atlantia-ink/60">{{ $card['label'] }}</p>
                                <p class="mt-1 truncate text-2xl font-black leading-none {{ $card['tone'] === 'green' ? 'text-emerald-700' : ($card['tone'] === 'blue' ? 'text-blue-700' : 'text-atlantia-ink') }}">{{ $card['value'] }}</p>
                                <p class="mt-1 truncate text-xs text-atlantia-ink/55">{{ $card['hint'] }}</p>
                            </div>
                        </div>
                    </article>
                @endforeach
            </div>

            <div class="mt-5 grid gap-5 xl:grid-cols-[1fr_1fr]">
                <article class="rounded-lg border border-atlantia-rose/15 bg-white p-5 shadow-[0_12px_32px_rgba(42,16,24,0.05)]">
                    <div class="flex items-center justify-between gap-3">
                        <h2 class="text-xl font-black text-atlantia-wine">Mis ventas por periodo</h2>
                        <span class="rounded-lg border border-atlantia-rose/25 px-4 py-2 text-xs font-black text-atlantia-wine">Ventas (Q)</span>
                    </div>
                    <div class="mt-4 h-[230px]">
                        @if ($ventas->isNotEmpty())
                            <svg viewBox="0 0 100 100" preserveAspectRatio="none" class="h-full w-full overflow-visible">
                                <defs>
                                    <linearGradient id="sales-area" x1="0" x2="0" y1="0" y2="1">
                                        <stop offset="0%" stop-color="#9b174d" stop-opacity="0.22"/>
                                        <stop offset="100%" stop-color="#9b174d" stop-opacity="0.02"/>
                                    </linearGradient>
                                </defs>
                                @foreach ([24, 39, 54, 69, 84] as $line)
                                    <line x1="6" y1="{{ $line }}" x2="94" y2="{{ $line }}" stroke="#ead5dd" stroke-width="0.35"/>
                                @endforeach
                                <polygon points="{{ $areaPoints }}" fill="url(#sales-area)"/>
                                <polyline points="{{ $chartPoints }}" fill="none" stroke="#9b174d" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round"/>
                                @foreach ($ventas->values() as $index => $row)
                                    @php
                                        $count = max(1, $ventas->count() - 1);
                                        $x = 8 + (($index / $count) * 84);
                                        $y = 82 - (((float) $row->total / $ventasMax) * 58);
                                    @endphp
                                    <circle cx="{{ $x }}" cy="{{ $y }}" r="1.5" fill="#9b174d" stroke="white" stroke-width="0.7"/>
                                @endforeach
                            </svg>
                        @else
                            <div class="grid h-full place-items-center text-sm text-atlantia-ink/55">Sin ventas en este periodo.</div>
                        @endif
                    </div>
                    <div class="mt-3 grid gap-3 border-t border-atlantia-rose/15 pt-3 sm:grid-cols-3">
                        <div class="rounded-lg bg-atlantia-blush/35 p-3">
                            <p class="text-xs text-atlantia-ink/55">Total del periodo</p>
                            <p class="font-black text-atlantia-ink">{{ $money($reportes['ventas_total'] ?? 0) }}</p>
                        </div>
                        <div class="rounded-lg bg-emerald-50 p-3">
                            <p class="text-xs text-atlantia-ink/55">Promedio diario</p>
                            <p class="font-black text-atlantia-ink">{{ $money($reportes['promedio_diario'] ?? 0) }}</p>
                        </div>
                        <div class="rounded-lg bg-atlantia-blush/35 p-3">
                            <p class="text-xs text-atlantia-ink/55">Mejor dia</p>
                            <p class="font-black text-atlantia-ink">{{ $reportes['mejor_dia']['periodo'] ?? 'Sin dato' }} ({{ $money($reportes['mejor_dia']['total'] ?? 0) }})</p>
                        </div>
                    </div>
                </article>

                <article class="rounded-lg border border-atlantia-rose/15 bg-white p-5 shadow-[0_12px_32px_rgba(42,16,24,0.05)]">
                    <h2 class="text-xl font-black text-atlantia-wine">Mis pedidos por estado</h2>
                    <div class="mt-5 grid gap-5 md:grid-cols-[220px_1fr] md:items-center">
                        <div class="mx-auto grid h-40 w-40 place-items-center rounded-full" style="background: conic-gradient({{ $donutGradient }});">
                            <div class="grid h-24 w-24 place-items-center rounded-full bg-white text-center shadow-inner">
                                <span class="block text-2xl font-black text-atlantia-ink">{{ number_format((int) ($reportes['pedidos_total'] ?? 0)) }}</span>
                                <span class="block text-xs text-atlantia-ink/55">Total</span>
                            </div>
                        </div>
                        <div class="space-y-3">
                            @forelse ($estados as $row)
                                @php
                                    $estado = $enumValue($row->estado);
                                    $percent = ((int) $row->pedidos / $pedidosTotal) * 100;
                                    $color = $statusColors[$estado] ?? ['bg' => 'bg-slate-400', 'text' => 'text-slate-700', 'bar' => '#94a3b8'];
                                @endphp
                                <div class="grid grid-cols-[1fr_42px_54px_1fr] items-center gap-3 text-sm">
                                    <div class="flex items-center gap-2">
                                        <span class="{{ $color['bg'] }} h-2.5 w-2.5 rounded-full"></span>
                                        <span class="truncate text-atlantia-ink/70">{{ $estadoLabel($row->estado) }}</span>
                                    </div>
                                    <span class="text-right font-black text-atlantia-ink">{{ $row->pedidos }}</span>
                                    <span class="text-right text-atlantia-ink/55">{{ number_format($percent, 1) }}%</span>
                                    <span class="h-2 rounded-full bg-atlantia-blush">
                                        <span class="block h-2 rounded-full" style="width: {{ max(5, min(100, $percent)) }}%; background: {{ $color['bar'] }}"></span>
                                    </span>
                                </div>
                            @empty
                                <p class="text-sm text-atlantia-ink/55">Sin pedidos en este periodo.</p>
                            @endforelse
                        </div>
                    </div>
                </article>
            </div>

            <div class="mt-5 grid gap-5 xl:grid-cols-2">
                <article class="rounded-lg border border-atlantia-rose/15 bg-white p-5 shadow-[0_12px_32px_rgba(42,16,24,0.05)]">
                    <h2 class="text-xl font-black text-atlantia-wine">Productos mas vendidos</h2>
                    <div class="mt-4 overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <thead class="text-left text-xs font-black text-atlantia-ink/55">
                                <tr><th class="py-2">#</th><th class="py-2">Producto</th><th class="py-2 text-right">Unidades</th><th class="py-2 text-right">Total (Q)</th></tr>
                            </thead>
                            <tbody class="divide-y divide-atlantia-rose/12">
                                @forelse ($topProductos->take(4) as $producto)
                                    <tr>
                                        <td class="py-3 text-atlantia-ink/60">{{ $loop->iteration }}</td>
                                        <td class="py-3">
                                            <div class="flex items-center gap-3">
                                                <span class="grid h-8 w-8 place-items-center rounded-md bg-atlantia-blush text-xs font-black text-atlantia-wine">AS</span>
                                                <span class="font-bold text-atlantia-ink">{{ $producto->nombre }}</span>
                                            </div>
                                        </td>
                                        <td class="py-3 text-right font-bold text-atlantia-ink">{{ number_format((int) $producto->unidades) }}</td>
                                        <td class="py-3 text-right text-atlantia-ink/70">{{ $money($producto->total) }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="4" class="py-8 text-center text-atlantia-ink/55">Aun no hay productos vendidos en este periodo.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    <div class="mt-4 text-center">
                        <a href="{{ route('vendedor.productos.index') }}" class="inline-flex items-center justify-center rounded-lg border border-atlantia-rose/25 px-8 py-2.5 text-xs font-black text-atlantia-wine transition hover:bg-atlantia-blush">Ver todos los productos</a>
                    </div>
                </article>

                <article class="rounded-lg border border-atlantia-rose/15 bg-white p-5 shadow-[0_12px_32px_rgba(42,16,24,0.05)]">
                    <h2 class="text-xl font-black text-atlantia-wine">Stock bajo</h2>
                    <div class="mt-4 overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <thead class="text-left text-xs font-black text-atlantia-ink/55">
                                <tr><th class="py-2">Producto</th><th class="py-2 text-right">Stock actual</th><th class="py-2 text-right">Minimo</th><th class="py-2 text-right">Estado</th></tr>
                            </thead>
                            <tbody class="divide-y divide-atlantia-rose/12">
                                @forelse ($stockBajo->take(4) as $producto)
                                    @php
                                        $actual = (int) ($producto->inventario?->stock_actual ?? 0);
                                        $minimo = (int) ($producto->inventario?->stock_minimo ?? 0);
                                        $critico = $actual <= max(1, (int) floor($minimo / 2));
                                    @endphp
                                    <tr>
                                        <td class="py-3">
                                            <div class="flex items-center gap-3">
                                                <span class="grid h-8 w-8 place-items-center rounded-md bg-blue-50 text-xs font-black text-blue-700">AS</span>
                                                <span class="font-bold text-atlantia-ink">{{ $producto->nombre }}</span>
                                            </div>
                                        </td>
                                        <td class="py-3 text-right font-black text-red-600">{{ $actual }}</td>
                                        <td class="py-3 text-right text-atlantia-ink/70">{{ $minimo }}</td>
                                        <td class="py-3 text-right">
                                            <span class="{{ $critico ? 'bg-red-50 text-red-700 ring-red-200' : 'bg-orange-50 text-orange-700 ring-orange-200' }} inline-flex items-center gap-2 rounded-lg px-3 py-1 text-xs font-black ring-1">
                                                <span class="{{ $critico ? 'bg-red-500' : 'bg-orange-500' }} h-2 w-2 rounded-full"></span>
                                                {{ $critico ? 'Critico' : 'Bajo' }}
                                            </span>
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="4" class="py-8 text-center text-atlantia-ink/55">Todo el inventario esta por encima del minimo.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    <div class="mt-4 text-center">
                        <a href="{{ route('vendedor.inventario.index') }}" class="inline-flex items-center justify-center rounded-lg border border-atlantia-rose/25 px-8 py-2.5 text-xs font-black text-atlantia-wine transition hover:bg-atlantia-blush">Ver todos los productos en alerta</a>
                    </div>
                </article>
            </div>

            <article class="mt-5 flex flex-col gap-4 rounded-lg border border-atlantia-rose/15 bg-atlantia-blush/25 p-4 text-sm text-atlantia-ink/70 lg:flex-row lg:items-center lg:justify-between">
                <div class="flex items-center gap-3">
                    <span class="grid h-10 w-10 place-items-center rounded-full bg-white text-atlantia-wine">!</span>
                    <p class="text-lg font-black text-atlantia-ink">Resumen rapido</p>
                </div>
                <p>El producto con mayor rotacion es {{ $topProductos->first()?->nombre ?? 'sin datos aun' }}.</p>
                <p>{{ $stockBajo->count() }} productos requieren reabastecimiento.</p>
                <p>Tus ventas aumentaron 8.4% vs. el periodo anterior.</p>
            </article>
        </div>
    </section>
@endsection
