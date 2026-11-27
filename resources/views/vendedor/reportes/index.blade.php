@extends('layouts.app')

@section('content')
    @php
        $filtros = $reportes['filters'] ?? [];
        $dinero = fn ($valor) => 'Q '.number_format((float) $valor, 2);
        $valorEnum = fn ($valor) => $valor instanceof \BackedEnum ? $valor->value : (string) $valor;
        $nombresEstado = [
            'pendiente' => 'Por confirmar', 'en_revision' => 'En revisión', 'confirmado' => 'Confirmado',
            'preparando' => 'En preparación', 'listo_para_entrega' => 'Listo', 'en_ruta' => 'En camino',
            'entregado' => 'Entregado', 'cancelado' => 'Cancelado', 'rechazado' => 'Rechazado',
        ];
        $coloresEstado = [
            'pendiente' => '#f59e0b', 'en_revision' => '#f59e0b', 'confirmado' => '#0ea5e9', 'preparando' => '#6366f1',
            'listo_para_entrega' => '#10b981', 'en_ruta' => '#14b8a6', 'entregado' => '#64748b', 'cancelado' => '#ef4444', 'rechazado' => '#ef4444',
        ];
        $ventas = collect($reportes['ventas_por_periodo'] ?? []);
        $estados = collect($reportes['pedidos_por_estado'] ?? []);
        $topProductos = collect($reportes['top_productos'] ?? []);
        $stockBajo = collect($reportes['stock_bajo'] ?? []);
        $ventasMax = max(1, (float) $ventas->max('total'));
        $totalEstados = max(1, (int) $estados->sum('pedidos'));
        $comparacion = $reportes['comparacion'] ?? ['cambio' => null, 'anterior' => 0];
        $atajos = [
            'Este mes' => [now()->startOfMonth()->toDateString(), now()->toDateString()],
            'Últimos 7 días' => [now()->subDays(6)->toDateString(), now()->toDateString()],
            'Mes pasado' => [now()->subMonthNoOverflow()->startOfMonth()->toDateString(), now()->subMonthNoOverflow()->endOfMonth()->toDateString()],
        ];
        $etiquetaPeriodo = function ($periodo) use ($filtros): string {
            try {
                return ($filtros['agrupacion'] ?? 'dia') === 'mes'
                    ? \Illuminate\Support\Carbon::parse($periodo.'-01')->translatedFormat('M Y')
                    : \Illuminate\Support\Carbon::parse($periodo)->format('d/m');
            } catch (\Throwable) {
                return (string) $periodo;
            }
        };
    @endphp

    <section class="mx-auto max-w-[1180px] space-y-4 pb-10">
        <div class="flex flex-wrap items-end justify-between gap-3">
            <div>
                <h1 class="text-2xl font-black text-atlantia-ink">Reportes</h1>
                <p class="mt-0.5 text-sm text-atlantia-ink/60">Cómo te fue del {{ \Illuminate\Support\Carbon::parse($filtros['fecha_desde'])->format('d/m/Y') }} al {{ \Illuminate\Support\Carbon::parse($filtros['fecha_hasta'])->format('d/m/Y') }}. No incluye pedidos cancelados, envíos ni propinas.</p>
            </div>
            <div class="flex gap-2 print:hidden">
                <a href="{{ route('vendedor.reportes.descargar', ['fecha_desde' => $filtros['fecha_desde'], 'fecha_hasta' => $filtros['fecha_hasta']]) }}" class="inline-flex h-10 items-center gap-2 rounded-lg border border-atlantia-rose/25 bg-white px-4 text-sm font-bold text-atlantia-wine hover:bg-atlantia-blush" data-descargar-excel>
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4M7 10l5 5 5-5M12 15V3"/></svg>
                    Descargar para Excel
                </a>
                <button type="button" data-print-page class="inline-flex h-10 items-center gap-2 rounded-lg border border-atlantia-rose/25 bg-white px-4 text-sm font-bold text-atlantia-wine hover:bg-atlantia-blush" title="También puedes elegir «Guardar como PDF»">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M6 9V2h12v7M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2M6 14h12v8H6z"/></svg>
                    Imprimir o PDF
                </button>
            </div>
        </div>

        <form method="GET" class="flex flex-wrap items-end gap-2 rounded-xl border border-atlantia-rose/15 bg-white p-3 print:hidden">
            <div class="flex flex-wrap gap-1.5">
                @foreach ($atajos as $texto => [$inicio, $fin])
                    <a href="{{ route('vendedor.reportes.index', ['fecha_desde' => $inicio, 'fecha_hasta' => $fin, 'agrupacion' => $filtros['agrupacion'] ?? 'dia']) }}" @class([
                        'inline-flex h-10 items-center rounded-lg px-3 text-sm font-bold transition',
                        'bg-atlantia-wine text-white' => ($filtros['fecha_desde'] ?? '') === $inicio && ($filtros['fecha_hasta'] ?? '') === $fin,
                        'bg-[#f5efee] text-atlantia-ink/70 hover:text-atlantia-wine' => ! (($filtros['fecha_desde'] ?? '') === $inicio && ($filtros['fecha_hasta'] ?? '') === $fin),
                    ])>{{ $texto }}</a>
                @endforeach
            </div>
            <span class="mx-1 hidden h-8 w-px bg-atlantia-rose/20 sm:block"></span>
            <label class="text-xs font-bold text-atlantia-ink/60">Del
                <input type="date" name="fecha_desde" value="{{ $filtros['fecha_desde'] ?? '' }}" class="mt-0.5 block h-10 rounded-lg border border-atlantia-rose/25 px-2 text-sm text-atlantia-ink outline-none focus:border-atlantia-wine">
            </label>
            <label class="text-xs font-bold text-atlantia-ink/60">al
                <input type="date" name="fecha_hasta" value="{{ $filtros['fecha_hasta'] ?? '' }}" class="mt-0.5 block h-10 rounded-lg border border-atlantia-rose/25 px-2 text-sm text-atlantia-ink outline-none focus:border-atlantia-wine">
            </label>
            <label class="text-xs font-bold text-atlantia-ink/60">Ver por
                <select name="agrupacion" class="mt-0.5 block h-10 rounded-lg border border-atlantia-rose/25 bg-white px-2 text-sm text-atlantia-ink outline-none focus:border-atlantia-wine">
                    <option value="dia" @selected(($filtros['agrupacion'] ?? 'dia') === 'dia')>Día</option>
                    <option value="mes" @selected(($filtros['agrupacion'] ?? 'dia') === 'mes')>Mes</option>
                </select>
            </label>
            <button type="submit" class="h-10 rounded-lg bg-atlantia-wine px-4 text-sm font-bold text-white hover:bg-atlantia-wine-700">Ver</button>
        </form>

        {{-- Cifras del periodo --}}
        <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4" data-cifras-reporte>
            <article class="rounded-xl bg-[linear-gradient(140deg,#3a0004_0%,#5e0008_60%,#6e000a_100%)] p-4 text-white">
                <p class="text-xs font-bold uppercase tracking-wide text-white/70">Tus ventas</p>
                <p class="mt-0.5 text-2xl font-black">{{ $dinero($reportes['ventas_total'] ?? 0) }}</p>
                <p class="text-xs text-white/75" data-comparacion>
                    @if ($comparacion['cambio'] !== null)
                        {{ $comparacion['cambio'] >= 0 ? '▲' : '▼' }} {{ number_format(abs($comparacion['cambio']), 1) }} % vs. los {{ (int) \Illuminate\Support\Carbon::parse($filtros['fecha_desde'])->diffInDays(\Illuminate\Support\Carbon::parse($filtros['fecha_hasta'])) + 1 }} días anteriores
                    @elseif (($reportes['ventas_total'] ?? 0) > 0)
                        Sin ventas en el periodo anterior para comparar
                    @else
                        {{ $dinero($reportes['promedio_diario'] ?? 0) }} por día
                    @endif
                </p>
            </article>
            <article class="rounded-xl border border-atlantia-rose/15 bg-white p-4">
                <p class="text-xs font-bold uppercase tracking-wide text-atlantia-ink/55">Pedidos</p>
                <p class="mt-0.5 text-2xl font-black text-atlantia-ink">{{ number_format((int) ($reportes['pedidos_total'] ?? 0)) }}</p>
                <p class="text-xs text-atlantia-ink/55">{{ (int) ($reportes['pendientes'] ?? 0) }} sin entregar{{ ($reportes['cancelados'] ?? 0) > 0 ? ' · '.$reportes['cancelados'].' cancelados' : '' }}</p>
            </article>
            <article class="rounded-xl border border-atlantia-rose/15 bg-white p-4">
                <p class="text-xs font-bold uppercase tracking-wide text-atlantia-ink/55">Venta promedio por pedido</p>
                <p class="mt-0.5 text-2xl font-black text-atlantia-ink">{{ $dinero($reportes['ticket_promedio'] ?? 0) }}</p>
                <p class="text-xs text-atlantia-ink/55">{{ $dinero($reportes['promedio_diario'] ?? 0) }} por día</p>
            </article>
            <article class="rounded-xl border border-atlantia-rose/15 bg-white p-4">
                <p class="text-xs font-bold uppercase tracking-wide text-atlantia-ink/55">Unidades vendidas</p>
                <p class="mt-0.5 text-2xl font-black text-atlantia-ink">{{ number_format((int) ($reportes['productos_vendidos'] ?? 0)) }}</p>
                <p class="text-xs text-atlantia-ink/55">{{ $topProductos->count() }} {{ $topProductos->count() === 1 ? 'producto distinto' : 'productos distintos' }}</p>
            </article>
        </div>

        <div class="grid gap-4 lg:grid-cols-[minmax(0,1.4fr)_minmax(0,1fr)]">
            {{-- Ventas por día o mes (barras) --}}
            <article class="rounded-xl border border-atlantia-rose/15 bg-white p-4">
                <div class="flex items-baseline justify-between gap-3">
                    <h2 class="text-sm font-black text-atlantia-ink">Ventas por {{ ($filtros['agrupacion'] ?? 'dia') === 'mes' ? 'mes' : 'día' }}</h2>
                    @if ($reportes['mejor_dia']['periodo'] ?? null)
                        <p class="text-xs text-atlantia-ink/55">Mejor: <strong class="text-atlantia-ink">{{ $etiquetaPeriodo($reportes['mejor_dia']['periodo']) }}</strong> · {{ $dinero($reportes['mejor_dia']['total']) }}</p>
                    @endif
                </div>
                @if ($ventas->isEmpty())
                    <p class="grid h-48 place-items-center text-sm text-atlantia-ink/55">Sin ventas en este periodo.</p>
                @else
                    <div class="mt-4 flex h-48 items-end gap-1 overflow-x-auto pb-1" data-grafica-ventas>
                        @foreach ($ventas as $fila)
                            <div class="group flex min-w-[1.75rem] flex-1 flex-col items-center justify-end gap-1" title="{{ $etiquetaPeriodo($fila->periodo) }}: {{ $dinero($fila->total) }} · {{ $fila->pedidos }} {{ (int) $fila->pedidos === 1 ? 'pedido' : 'pedidos' }}">
                                <span class="text-[10px] font-bold text-atlantia-ink/0 group-hover:text-atlantia-ink/70">{{ number_format((float) $fila->total, 0) }}</span>
                                <span class="w-full max-w-[2.5rem] rounded-t-md bg-atlantia-wine/80 transition group-hover:bg-atlantia-wine" style="height: {{ max(4, round((float) $fila->total / $ventasMax * 150)) }}px"></span>
                                <span class="text-[10px] text-atlantia-ink/50">{{ $etiquetaPeriodo($fila->periodo) }}</span>
                            </div>
                        @endforeach
                    </div>
                @endif
            </article>

            {{-- Pedidos por estado --}}
            <article class="rounded-xl border border-atlantia-rose/15 bg-white p-4">
                <h2 class="text-sm font-black text-atlantia-ink">Pedidos por estado</h2>
                @if ($estados->isEmpty())
                    <p class="grid h-48 place-items-center text-sm text-atlantia-ink/55">Sin pedidos en este periodo.</p>
                @else
                    <ul class="mt-3 space-y-2">
                        @foreach ($estados as $fila)
                            @php $clave = $valorEnum($fila->estado); $porcentaje = (int) $fila->pedidos / $totalEstados * 100; @endphp
                            <li class="text-sm">
                                <div class="flex items-center justify-between gap-2">
                                    <span class="flex items-center gap-2 text-atlantia-ink/75"><span class="h-2.5 w-2.5 rounded-full" style="background: {{ $coloresEstado[$clave] ?? '#94a3b8' }}"></span>{{ $nombresEstado[$clave] ?? ucfirst($clave) }}</span>
                                    <span class="font-bold text-atlantia-ink">{{ $fila->pedidos }} <span class="font-normal text-atlantia-ink/45">· {{ number_format($porcentaje, 0) }}%</span></span>
                                </div>
                                <span class="mt-1 block h-1.5 overflow-hidden rounded-full bg-[#f1e9e8]"><span class="block h-full rounded-full" style="width: {{ max(3, $porcentaje) }}%; background: {{ $coloresEstado[$clave] ?? '#94a3b8' }}"></span></span>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </article>
        </div>

        <div class="grid gap-4 lg:grid-cols-2">
            <article class="rounded-xl border border-atlantia-rose/15 bg-white p-4">
                <h2 class="text-sm font-black text-atlantia-ink">Lo que más vendiste</h2>
                @if ($topProductos->isEmpty())
                    <p class="py-8 text-center text-sm text-atlantia-ink/55">Aún no hay productos vendidos en este periodo.</p>
                @else
                    <ol class="mt-2 divide-y divide-atlantia-rose/10">
                        @foreach ($topProductos as $producto)
                            <li class="flex items-center gap-3 py-2 text-sm">
                                <span class="grid h-6 w-6 shrink-0 place-items-center rounded-full bg-atlantia-blush text-xs font-black text-atlantia-wine">{{ $loop->iteration }}</span>
                                <span class="min-w-0 flex-1 truncate font-semibold text-atlantia-ink">{{ $producto->nombre }}</span>
                                <span class="shrink-0 text-atlantia-ink/60">{{ number_format((int) $producto->unidades) }} u.</span>
                                <span class="w-24 shrink-0 text-right font-bold text-atlantia-ink">{{ $dinero($producto->total) }}</span>
                            </li>
                        @endforeach
                    </ol>
                @endif
            </article>

            <article class="rounded-xl border border-atlantia-rose/15 bg-white p-4">
                <div class="flex items-baseline justify-between gap-3">
                    <h2 class="text-sm font-black text-atlantia-ink">Por reabastecer</h2>
                    <a href="{{ route('vendedor.inventario.index') }}" class="text-xs font-bold text-atlantia-wine hover:underline print:hidden">Ir a Inventario</a>
                </div>
                @if ($stockBajo->isEmpty())
                    <p class="py-8 text-center text-sm text-atlantia-ink/55">Todos tus productos están por encima de su mínimo.</p>
                @else
                    <ul class="mt-2 divide-y divide-atlantia-rose/10">
                        @foreach ($stockBajo as $producto)
                            @php $actual = (int) ($producto->inventario?->stock_actual ?? 0); @endphp
                            <li class="flex items-center gap-3 py-2 text-sm">
                                <span class="min-w-0 flex-1 truncate font-semibold text-atlantia-ink">{{ $producto->nombre }}</span>
                                <span class="shrink-0 font-bold {{ $actual <= 0 ? 'text-rose-700' : 'text-amber-700' }}">{{ $actual <= 0 ? 'Agotado' : 'Quedan '.$actual }}</span>
                                <span class="w-20 shrink-0 text-right text-xs text-atlantia-ink/50">mín. {{ (int) ($producto->inventario?->stock_minimo ?? 0) }}</span>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </article>
        </div>
    </section>
@endsection
