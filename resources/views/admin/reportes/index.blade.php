@extends(auth()->user()?->isSuperAdmin() && request()->routeIs('admin.*') ? 'layouts.super-admin' : 'layouts.app')

@section('content')
    @php
        $filters = $reportes['filters'] ?? [];
        $anterior = $reportes['anterior'] ?? [];
        $enumValue = fn ($value) => $value instanceof \BackedEnum ? $value->value : (string) $value;
        $money = fn ($value) => 'Q '.number_format((float) $value, 2);
        $desde = \Illuminate\Support\Carbon::parse($filters['fecha_desde']);
        $hasta = \Illuminate\Support\Carbon::parse($filters['fecha_hasta']);
        $rango = fn (\Illuminate\Support\Carbon $a, \Illuminate\Support\Carbon $b) => $a->isSameDay($b)
            ? $a->translatedFormat('j \d\e F Y')
            : ($a->isSameMonth($b) ? $a->format('j').' al '.$b->translatedFormat('j \d\e F Y') : $a->translatedFormat('j M Y').' al '.$b->translatedFormat('j M Y'));
        $cambio = function (float $actual, float $antes): ?float {
            return abs($antes) < 0.01 ? null : round((($actual - $antes) / abs($antes)) * 100, 1);
        };

        $estadoNombres = [
            'pendiente' => 'Por confirmar', 'confirmado' => 'Confirmado', 'en_revision' => 'En revisión',
            'preparando' => 'Preparando', 'listo_para_entrega' => 'Listo', 'en_ruta' => 'En camino',
            'entregado' => 'Entregado', 'cancelado' => 'Cancelado', 'rechazado' => 'Rechazado',
        ];
        $estadoColores = [
            'entregado' => '#15803d', 'en_ruta' => '#5e0008', 'preparando' => '#a63d44', 'listo_para_entrega' => '#d98a93',
            'confirmado' => '#0369a1', 'pendiente' => '#d97706', 'en_revision' => '#ca8a04', 'cancelado' => '#94a3b8', 'rechazado' => '#475569',
        ];
        $metodoNombres = ['tarjeta' => 'Tarjeta', 'efectivo' => 'Efectivo', 'transferencia' => 'Transferencia', 'saldo' => 'Saldo Atlantia'];

        // Atajos de fecha.
        $hoy = today();
        $atajos = [
            'Hoy' => [$hoy, $hoy, 'dia'],
            'Últimos 7 días' => [$hoy->copy()->subDays(6), $hoy, 'dia'],
            'Este mes' => [$hoy->copy()->startOfMonth(), $hoy, 'dia'],
            'Mes pasado' => [$hoy->copy()->subMonthNoOverflow()->startOfMonth(), $hoy->copy()->subMonthNoOverflow()->endOfMonth(), 'dia'],
            'Este año' => [$hoy->copy()->startOfYear(), $hoy, 'mes'],
        ];

        // Indicadores principales.
        $indicadores = [
            ['Ventas', $money($reportes['ventas_total'] ?? 0), $cambio((float) ($reportes['ventas_total'] ?? 0), (float) ($anterior['ventas_total'] ?? 0)), true],
            ['Pedidos', number_format((int) ($reportes['pedidos_total'] ?? 0)), $cambio((float) ($reportes['pedidos_total'] ?? 0), (float) ($anterior['pedidos_total'] ?? 0)), true],
            ['Ticket promedio', $money($reportes['ticket_promedio'] ?? 0), $cambio((float) ($reportes['ticket_promedio'] ?? 0), (float) ($anterior['ticket_promedio'] ?? 0)), true],
            ['Cancelados', number_format((int) ($reportes['cancelados'] ?? 0)), null, false],
        ];

        // Gráfica de ventas.
        $periodos = collect($reportes['ventas_por_periodo'] ?? []);
        $maxPeriodo = max(1, (float) $periodos->max('total'));
        $etiquetaPeriodo = function ($periodo) use ($filters) {
            try {
                return ($filters['agrupacion'] ?? 'dia') === 'mes'
                    ? \Illuminate\Support\Carbon::createFromFormat('Y-m', (string) $periodo)->translatedFormat('M Y')
                    : \Illuminate\Support\Carbon::parse((string) $periodo)->translatedFormat('j M');
            } catch (\Throwable) {
                return (string) $periodo;
            }
        };

        // Dona de estados.
        $estados = collect($reportes['pedidos_por_estado'] ?? []);
        $totalEstados = max(1, (int) $estados->sum('pedidos'));
        $acumulado = 0;
        $segmentos = $estados->map(function ($fila) use (&$acumulado, $totalEstados, $estadoColores, $enumValue) {
            $clave = $enumValue($fila->estado);
            $inicio = $acumulado;
            $acumulado += ((int) $fila->pedidos / $totalEstados) * 100;

            return ($estadoColores[$clave] ?? '#5e0008').' '.round($inicio, 2).'% '.round($acumulado, 2).'%';
        })->implode(', ');

        $productos = collect($reportes['top_productos'] ?? []);
        $maxProducto = max(1, (float) $productos->max('unidades'));
        $tiendas = collect($reportes['ingresos_por_vendedor'] ?? [])->filter(fn ($v) => (float) $v->total_ventas > 0)->values();
        $totalTiendas = max(1, (float) $tiendas->sum('total_ventas'));
        $metodos = collect($reportes['metodos_pago'] ?? []);
        $totalMetodos = max(1, (float) $metodos->sum('total'));

        $estadoControl = [
            ['Vendedores activos', (int) ($reportes['vendedores_activos'] ?? 0), route('admin.vendedores.index'), false],
            ['Comisiones por cobrar', (int) ($reportes['comisiones_pendientes'] ?? 0), route('admin.comisiones.index'), true],
            ['Facturas rechazadas', (int) ($reportes['dtes_rechazados'] ?? 0), route('admin.dte.index'), true],
            ['Alertas de fraude', (int) ($reportes['alertas_pendientes'] ?? 0), route('admin.antifraude.index'), true],
        ];
        $tarjeta = 'rounded-2xl border border-atlantia-rose/15 bg-white p-5 shadow-[0_10px_26px_rgba(59,6,12,0.05)] print:shadow-none print:break-inside-avoid';
    @endphp

    <section class="mx-auto max-w-7xl space-y-5 pb-10">
        {{-- Encabezado --}}
        <div class="flex flex-wrap items-end justify-between gap-3">
            <div>
                <h1 class="text-3xl font-black tracking-tight text-atlantia-ink">Reportes</h1>
                <p class="mt-1 text-sm text-atlantia-ink/60">
                    Del <strong class="text-atlantia-ink">{{ $rango($desde, $hasta) }}</strong>
                    @if (! empty($anterior))
                        <span class="text-atlantia-ink/45">· comparado con {{ $rango(\Illuminate\Support\Carbon::parse($anterior['desde']), \Illuminate\Support\Carbon::parse($anterior['hasta'])) }}</span>
                    @endif
                </p>
            </div>
            <div class="flex gap-2 print:hidden">
                <a href="{{ route('admin.reportes.exportar', request()->only(['fecha_desde', 'fecha_hasta', 'agrupacion'])) }}" class="inline-flex items-center gap-2 rounded-xl border border-atlantia-rose/25 bg-white px-4 py-2.5 text-sm font-black text-atlantia-wine hover:bg-atlantia-blush" data-exportar>
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M12 3v12M8 11l4 4 4-4M5 19h14" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    Descargar Excel
                </a>
                <button type="button" data-print-report class="inline-flex items-center gap-2 rounded-xl bg-atlantia-wine px-4 py-2.5 text-sm font-black text-white hover:bg-atlantia-wine-700">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M7 8V4h10v4M7 17H5a2 2 0 0 1-2-2v-4a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2v4a2 2 0 0 1-2 2h-2M7 14h10v6H7z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/></svg>
                    Imprimir
                </button>
            </div>
        </div>

        {{-- Filtros --}}
        <form method="GET" class="{{ $tarjeta }} !p-4 print:hidden">
            <div class="flex flex-wrap gap-2">
                @foreach ($atajos as $texto => [$a, $b, $agr])
                    @php $activo = $a->toDateString() === $filters['fecha_desde'] && $b->toDateString() === $filters['fecha_hasta']; @endphp
                    <a href="{{ route('admin.reportes.index', ['fecha_desde' => $a->toDateString(), 'fecha_hasta' => $b->toDateString(), 'agrupacion' => $agr]) }}"
                       @class(['rounded-full px-3.5 py-1.5 text-xs font-black transition', 'bg-atlantia-wine text-white' => $activo, 'bg-atlantia-blush/60 text-atlantia-wine hover:bg-atlantia-blush' => ! $activo])>{{ $texto }}</a>
                @endforeach
            </div>
            <div class="mt-3 grid gap-2 border-t border-atlantia-rose/10 pt-3 sm:grid-cols-[1fr_1fr_1fr_auto] sm:items-end">
                <label>
                    <span class="mb-1 block text-xs font-bold text-atlantia-ink/55">Desde</span>
                    <input type="date" name="fecha_desde" value="{{ $filters['fecha_desde'] }}" class="h-10 w-full rounded-lg border border-atlantia-rose/30 px-3 text-sm text-atlantia-ink focus:border-atlantia-wine focus:ring-2 focus:ring-atlantia-rose/20">
                </label>
                <label>
                    <span class="mb-1 block text-xs font-bold text-atlantia-ink/55">Hasta</span>
                    <input type="date" name="fecha_hasta" value="{{ $filters['fecha_hasta'] }}" class="h-10 w-full rounded-lg border border-atlantia-rose/30 px-3 text-sm text-atlantia-ink focus:border-atlantia-wine focus:ring-2 focus:ring-atlantia-rose/20">
                </label>
                <label>
                    <span class="mb-1 block text-xs font-bold text-atlantia-ink/55">Ver por</span>
                    <select name="agrupacion" class="h-10 w-full rounded-lg border border-atlantia-rose/30 px-3 text-sm text-atlantia-ink focus:border-atlantia-wine focus:ring-2 focus:ring-atlantia-rose/20">
                        <option value="dia" @selected(($filters['agrupacion'] ?? 'dia') === 'dia')>Día</option>
                        <option value="mes" @selected(($filters['agrupacion'] ?? 'dia') === 'mes')>Mes</option>
                    </select>
                </label>
                <button type="submit" class="h-10 rounded-lg bg-atlantia-wine px-5 text-sm font-black text-white hover:bg-atlantia-wine-700">Aplicar</button>
            </div>
        </form>

        {{-- Indicadores --}}
        <div class="grid grid-cols-2 gap-3 lg:grid-cols-4">
            @foreach ($indicadores as [$etiqueta, $valor, $variacion, $comparable])
                <article class="{{ $tarjeta }}">
                    <p class="text-xs font-black uppercase tracking-wide text-atlantia-ink/50">{{ $etiqueta }}</p>
                    <p class="mt-1.5 text-2xl font-black text-atlantia-ink sm:text-3xl">{{ $valor }}</p>
                    <p class="mt-1 text-xs font-bold">
                        @if (! $comparable)
                            <span class="text-atlantia-ink/45">No cuentan en las ventas</span>
                        @elseif ($variacion === null)
                            <span class="text-atlantia-ink/45">Sin datos del periodo anterior</span>
                        @else
                            <span @class(['text-emerald-700' => $variacion >= 0, 'text-red-700' => $variacion < 0])>{{ $variacion >= 0 ? '▲' : '▼' }} {{ number_format(abs($variacion), 1) }} %</span>
                            <span class="text-atlantia-ink/45">vs. periodo anterior</span>
                        @endif
                    </p>
                </article>
            @endforeach
        </div>

        {{-- Estado de control --}}
        <div class="grid grid-cols-2 gap-2 lg:grid-cols-4">
            @foreach ($estadoControl as [$texto, $cantidad, $url, $esAlerta])
                <a href="{{ $url }}" @class([
                    'flex items-center justify-between gap-2 rounded-xl border px-4 py-2.5 text-sm transition',
                    'border-red-200 bg-red-50 text-red-800 hover:bg-red-100' => $esAlerta && $cantidad > 0,
                    'border-atlantia-rose/15 bg-white text-atlantia-ink/70 hover:bg-atlantia-blush/40' => ! ($esAlerta && $cantidad > 0),
                ])>
                    <span class="font-bold">{{ $texto }}</span>
                    <span class="text-base font-black">{{ number_format($cantidad) }}</span>
                </a>
            @endforeach
        </div>

        {{-- Ventas por periodo + estados --}}
        <div class="grid gap-4 lg:grid-cols-[1.6fr_1fr]">
            <article class="{{ $tarjeta }}">
                <div class="flex items-center justify-between gap-3">
                    <h2 class="text-lg font-black text-atlantia-ink">Ventas por {{ ($filters['agrupacion'] ?? 'dia') === 'mes' ? 'mes' : 'día' }}</h2>
                    <a href="{{ route('admin.pedidos.index') }}" class="text-sm font-black text-atlantia-wine hover:underline print:hidden">Ver pedidos →</a>
                </div>
                @if ($periodos->isEmpty())
                    <p class="py-16 text-center text-sm text-atlantia-ink/50">No hubo ventas en este periodo.</p>
                @else
                    <div class="mt-5 flex h-52 items-end gap-1 border-b border-atlantia-rose/20 sm:gap-1.5" role="img" aria-label="Ventas por periodo">
                        @foreach ($periodos as $fila)
                            <div class="group relative flex h-full min-w-0 flex-1 flex-col justify-end">
                                <span class="pointer-events-none absolute left-1/2 z-10 hidden -translate-x-1/2 -translate-y-full whitespace-nowrap rounded-md bg-atlantia-ink px-2 py-1 text-[11px] font-bold text-white group-hover:block" style="bottom: {{ round((float) $fila->total / $maxPeriodo * 100, 2) }}%">
                                    {{ $etiquetaPeriodo($fila->periodo) }} · {{ $money($fila->total) }} · {{ $fila->pedidos }} ped.
                                </span>
                                <span class="block w-full rounded-t bg-atlantia-wine/85 transition group-hover:bg-atlantia-wine" style="height: {{ max(2, round((float) $fila->total / $maxPeriodo * 100, 2)) }}%"></span>
                            </div>
                        @endforeach
                    </div>
                    <div class="mt-1.5 flex gap-1 sm:gap-1.5">
                        @foreach ($periodos as $fila)
                            <span class="min-w-0 flex-1 truncate text-center text-[10px] font-bold text-atlantia-ink/45">{{ $periodos->count() <= 16 || $loop->first || $loop->last || $loop->iteration % 5 === 0 ? $etiquetaPeriodo($fila->periodo) : '' }}</span>
                        @endforeach
                    </div>
                @endif
            </article>

            <article class="{{ $tarjeta }}">
                <h2 class="text-lg font-black text-atlantia-ink">Pedidos por estado</h2>
                @if ($estados->isEmpty())
                    <p class="py-16 text-center text-sm text-atlantia-ink/50">Sin pedidos en este periodo.</p>
                @else
                    <div class="mt-4 flex items-center gap-5">
                        <div class="grid h-32 w-32 shrink-0 place-items-center rounded-full" style="background: conic-gradient({{ $segmentos }});">
                            <div class="grid h-20 w-20 place-items-center rounded-full bg-white text-center">
                                <span>
                                    <span class="block text-xl font-black leading-none text-atlantia-ink">{{ number_format($estados->sum('pedidos')) }}</span>
                                    <span class="text-[10px] font-bold text-atlantia-ink/50">pedidos</span>
                                </span>
                            </div>
                        </div>
                        <ul class="min-w-0 flex-1 space-y-1.5">
                            @foreach ($estados as $fila)
                                @php $clave = $enumValue($fila->estado); @endphp
                                <li class="flex items-center justify-between gap-2 text-xs">
                                    <span class="flex min-w-0 items-center gap-2">
                                        <span class="h-2.5 w-2.5 shrink-0 rounded-full" style="background-color: {{ $estadoColores[$clave] ?? '#5e0008' }}"></span>
                                        <span class="truncate font-semibold text-atlantia-ink/75">{{ $estadoNombres[$clave] ?? ucfirst(str_replace('_', ' ', $clave)) }}</span>
                                    </span>
                                    <span class="font-black text-atlantia-ink">{{ $fila->pedidos }}</span>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif
            </article>
        </div>

        {{-- Productos y tiendas --}}
        <div class="grid gap-4 lg:grid-cols-2">
            <article class="{{ $tarjeta }}">
                <div class="flex items-center justify-between gap-3">
                    <h2 class="text-lg font-black text-atlantia-ink">Productos más vendidos</h2>
                    <a href="{{ route('admin.productos.index') }}" class="text-sm font-black text-atlantia-wine hover:underline print:hidden">Productos →</a>
                </div>
                <ol class="mt-3 space-y-3">
                    @forelse ($productos as $producto)
                        <li>
                            <div class="flex items-baseline justify-between gap-3 text-sm">
                                <span class="min-w-0 truncate font-bold text-atlantia-ink"><span class="mr-1.5 text-atlantia-ink/40">{{ $loop->iteration }}.</span>{{ $producto->nombre }}</span>
                                <span class="shrink-0 text-xs text-atlantia-ink/55"><strong class="text-atlantia-ink">{{ number_format((int) $producto->unidades) }}</strong> uds · {{ $money($producto->total) }}</span>
                            </div>
                            <div class="mt-1 h-1.5 rounded-full bg-atlantia-blush/70"><div class="h-full rounded-full bg-atlantia-wine" style="width: {{ round((float) $producto->unidades / $maxProducto * 100, 1) }}%"></div></div>
                        </li>
                    @empty
                        <li class="py-10 text-center text-sm text-atlantia-ink/50">Sin productos vendidos en este periodo.</li>
                    @endforelse
                </ol>
            </article>

            <article class="{{ $tarjeta }}">
                <div class="flex items-center justify-between gap-3">
                    <h2 class="text-lg font-black text-atlantia-ink">Ventas por tienda</h2>
                    <a href="{{ route('admin.vendedores.index') }}" class="text-sm font-black text-atlantia-wine hover:underline print:hidden">Tiendas →</a>
                </div>
                <div class="mt-3 overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-atlantia-rose/15 text-left text-xs font-black uppercase tracking-wide text-atlantia-ink/45">
                                <th class="py-2 pr-3">Tienda</th>
                                <th class="py-2 pr-3 text-right">Pedidos</th>
                                <th class="py-2 pr-3 text-right">Ventas</th>
                                <th class="py-2 text-right">Parte</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-atlantia-rose/10">
                            @forelse ($tiendas as $tienda)
                                <tr>
                                    <td class="max-w-[12rem] truncate py-2.5 pr-3 font-bold text-atlantia-ink">{{ $tienda->business_name }}</td>
                                    <td class="py-2.5 pr-3 text-right text-atlantia-ink/70">{{ number_format((int) $tienda->pedidos) }}</td>
                                    <td class="py-2.5 pr-3 text-right font-black text-atlantia-ink">{{ $money($tienda->total_ventas) }}</td>
                                    <td class="py-2.5 text-right text-xs font-bold text-atlantia-wine">{{ number_format((float) $tienda->total_ventas / $totalTiendas * 100, 1) }} %</td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="py-10 text-center text-sm text-atlantia-ink/50">Ninguna tienda vendió en este periodo.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </article>
        </div>

        {{-- Métodos de pago, facturas y alertas --}}
        <div class="grid gap-4 lg:grid-cols-3">
            <article class="{{ $tarjeta }}">
                <h2 class="text-lg font-black text-atlantia-ink">Métodos de pago</h2>
                <div class="mt-3 space-y-3">
                    @forelse ($metodos as $fila)
                        @php $clave = $enumValue($fila->metodo_pago); $parte = (float) $fila->total / $totalMetodos * 100; @endphp
                        <div>
                            <div class="flex justify-between gap-3 text-sm">
                                <span class="font-bold text-atlantia-ink">{{ $metodoNombres[$clave] ?? ucfirst(str_replace('_', ' ', $clave)) }}</span>
                                <span class="text-atlantia-ink/60"><strong class="text-atlantia-ink">{{ $money($fila->total) }}</strong> · {{ number_format($parte, 0) }} %</span>
                            </div>
                            <div class="mt-1 h-1.5 rounded-full bg-atlantia-blush/70"><div class="h-full rounded-full bg-atlantia-wine" style="width: {{ round($parte, 1) }}%"></div></div>
                            <p class="mt-0.5 text-[11px] text-atlantia-ink/45">{{ $fila->pedidos }} {{ (int) $fila->pedidos === 1 ? 'pedido' : 'pedidos' }}</p>
                        </div>
                    @empty
                        <p class="py-10 text-center text-sm text-atlantia-ink/50">Sin pagos en este periodo.</p>
                    @endforelse
                </div>
            </article>

            <article class="{{ $tarjeta }}">
                <div class="flex items-center justify-between gap-3">
                    <h2 class="text-lg font-black text-atlantia-ink">Facturas recientes</h2>
                    <a href="{{ route('admin.dte.index') }}" class="text-sm font-black text-atlantia-wine hover:underline print:hidden">Ver →</a>
                </div>
                <div class="mt-2 divide-y divide-atlantia-rose/10">
                    @forelse (($reportes['dtes_recientes'] ?? collect())->take(4) as $dte)
                        <div class="flex items-center justify-between gap-3 py-2.5">
                            <div class="min-w-0">
                                <p class="truncate text-sm font-bold text-atlantia-ink">{{ $dte->numero_dte }}</p>
                                <p class="truncate text-xs text-atlantia-ink/55">{{ $dte->vendor?->business_name ?? 'Sin tienda' }}</p>
                            </div>
                            <span @class([
                                'shrink-0 rounded-full px-2.5 py-0.5 text-xs font-black',
                                'bg-red-50 text-red-700' => $dte->estado === 'rechazado',
                                'bg-emerald-50 text-emerald-700' => in_array($dte->estado, ['certificado', 'emitido', 'aceptado'], true),
                                'bg-atlantia-blush text-atlantia-wine' => ! in_array($dte->estado, ['rechazado', 'certificado', 'emitido', 'aceptado'], true),
                            ])>{{ ucfirst($dte->estado) }}</span>
                        </div>
                    @empty
                        <p class="py-10 text-center text-sm text-atlantia-ink/50">Aún no hay facturas.</p>
                    @endforelse
                </div>
            </article>

            <article class="{{ $tarjeta }}">
                <div class="flex items-center justify-between gap-3">
                    <h2 class="text-lg font-black text-atlantia-ink">Alertas de fraude</h2>
                    <a href="{{ route('admin.antifraude.index') }}" class="text-sm font-black text-atlantia-wine hover:underline print:hidden">Ver →</a>
                </div>
                <div class="mt-2 divide-y divide-atlantia-rose/10">
                    @forelse (($reportes['alertas_recientes'] ?? collect())->take(4) as $alerta)
                        <div class="flex items-center justify-between gap-3 py-2.5">
                            <div class="min-w-0">
                                <p class="truncate text-sm font-bold text-atlantia-ink">{{ ucfirst(str_replace('_', ' ', (string) $alerta->tipo)) }}</p>
                                <p class="truncate text-xs text-atlantia-ink/55">{{ $alerta->pedido?->numero_pedido ?? 'Sin pedido' }} · {{ $alerta->user?->name ?? 'Sin usuario' }}</p>
                            </div>
                            <span @class(['shrink-0 rounded-full px-2.5 py-0.5 text-xs font-black', 'bg-emerald-50 text-emerald-700' => $alerta->revisada, 'bg-amber-50 text-amber-800' => ! $alerta->revisada])>{{ $alerta->revisada ? 'Revisada' : 'Pendiente' }}</span>
                        </div>
                    @empty
                        <div class="flex flex-col items-center py-8 text-center">
                            <span class="grid h-9 w-9 place-items-center rounded-full bg-emerald-50 font-black text-emerald-700">✓</span>
                            <p class="mt-2 text-sm text-atlantia-ink/55">Sin alertas. Todo en orden.</p>
                        </div>
                    @endforelse
                </div>
            </article>
        </div>
    </section>
@endsection

@push('scripts')
<script nonce="{{ request()->attributes->get('csp_nonce') }}">
    document.querySelector('[data-print-report]')?.addEventListener('click', () => window.print());
</script>
@endpush
