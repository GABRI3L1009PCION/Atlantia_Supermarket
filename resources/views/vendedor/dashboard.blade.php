@extends('layouts.app')

@section('content')
    @php
        $tienda = $metrics['tienda'];
        $estadoTienda = $tienda['estado'];
        $kpis = $metrics['kpis'];
        $dias = $metrics['ventas_dias'];
        $maxDia = max(1, (float) $dias->max('total'));
        $totalPeriodo = (float) $dias->sum('total');
        $pasos = collect($metrics['primeros_pasos']);
        $pasosHechos = $pasos->where('hecho', true)->count();
        $hora = (int) now()->format('G');
        $saludo = $hora < 12 ? 'Buenos días' : ($hora < 19 ? 'Buenas tardes' : 'Buenas noches');
        $nombre = \Illuminate\Support\Str::of(auth()->user()?->name ?? '')->explode(' ')->first();
        $tarjeta = 'rounded-2xl border border-atlantia-rose/15 bg-white p-5 shadow-[0_10px_26px_rgba(59,6,12,0.05)]';
        $estados = [
            'pendiente' => ['Nuevo', 'bg-amber-50 text-amber-800'],
            'confirmado' => ['Confirmado', 'bg-sky-50 text-sky-800'],
            'en_revision' => ['En revisión', 'bg-amber-50 text-amber-800'],
            'preparando' => ['Preparando', 'bg-violet-50 text-violet-800'],
            'listo_para_entrega' => ['Listo para entregar', 'bg-emerald-50 text-emerald-800'],
        ];
        $indicadores = [
            ['Ventas de hoy', 'Q '.number_format($kpis['ventas_hoy']['valor'], 2), $kpis['ventas_hoy']],
            ['Ventas del mes', 'Q '.number_format($kpis['ventas_mes']['valor'], 2), $kpis['ventas_mes']],
            ['Pedidos del mes', number_format($kpis['pedidos_mes']['valor']), $kpis['pedidos_mes']],
            ['Ticket promedio', 'Q '.number_format($kpis['ticket_promedio']['valor'], 2), $kpis['ticket_promedio']],
        ];
    @endphp

    <section class="mx-auto max-w-7xl space-y-5 pb-8 text-atlantia-ink">
        @php
            $tiendaPanel = auth()->user()->vendor;
            $planElegido = (string) ($tiendaPanel?->plan ?: '');
            $planVigente = $tiendaPanel ? app(\App\Services\Monetizacion\PlanesVendedorService::class)->planActual($tiendaPanel) : null;
            $datosElegido = $planElegido !== '' && $planElegido !== $planVigente && \App\Services\Monetizacion\PlanesVendedorService::tieneMensualidad($planElegido) && ! ($tiendaPanel?->enPrueba() ?? false)
                ? \App\Services\Monetizacion\PlanesVendedorService::datos($planElegido) : null;
        @endphp
        @if ($datosElegido && ($datosElegido['precio'] ?? null) !== null)
            <div class="flex flex-wrap items-center justify-between gap-3 rounded-xl border border-atlantia-wine/20 bg-atlantia-blush px-4 py-3" data-plan-pendiente>
                <p class="text-sm text-atlantia-ink">
                    <strong class="text-atlantia-wine">Tu plan {{ $datosElegido['nombre'] }} está pendiente de pago.</strong>
                    Mientras tanto vendes con el plan {{ \App\Services\Monetizacion\PlanesVendedorService::datos($planVigente)['nombre'] }} ({{ \App\Services\Monetizacion\PlanesVendedorService::datos($planVigente)['max_productos'] }} productos y comisión por venta). Actívalo para tener todos sus beneficios.
                </p>
                <form method="POST" action="{{ route('vendedor.plan.elegir') }}">
                    @csrf
                    <input type="hidden" name="plan" value="{{ $planElegido }}">
                    <button type="submit" class="h-10 rounded-lg bg-atlantia-wine px-5 text-sm font-bold text-white hover:bg-atlantia-wine-700">Pagar Q{{ number_format((float) $datosElegido['precio'], 0) }} y activar</button>
                </form>
            </div>
        @endif
        {{-- Saludo y estado de la tienda --}}
        <div class="flex flex-wrap items-end justify-between gap-3">
            <div>
                <p class="text-sm font-bold text-atlantia-ink/55">{{ ucfirst(now()->translatedFormat('l j \d\e F')) }}</p>
                <h1 class="text-3xl font-black tracking-tight">{{ $saludo }}{{ $nombre ? ', '.$nombre : '' }}</h1>
                <p class="mt-1 flex flex-wrap items-center gap-2 text-sm text-atlantia-ink/65">
                    <strong class="text-atlantia-ink">{{ $tienda['nombre'] }}</strong>
                    <span @class([
                        'inline-flex items-center gap-1.5 rounded-full px-2.5 py-0.5 text-xs font-black',
                        'bg-emerald-50 text-emerald-800' => $estadoTienda['abierto'],
                        'bg-amber-50 text-amber-800' => $estadoTienda['pausado'],
                        'bg-slate-100 text-slate-700' => ! $estadoTienda['abierto'] && ! $estadoTienda['pausado'],
                    ])>
                        <span @class(['h-1.5 w-1.5 rounded-full', 'bg-emerald-500' => $estadoTienda['abierto'], 'bg-amber-500' => $estadoTienda['pausado'], 'bg-slate-400' => ! $estadoTienda['abierto'] && ! $estadoTienda['pausado']])></span>
                        {{ $estadoTienda['abierto'] ? 'Abierta ahora' : ($estadoTienda['pausado'] ? 'En pausa' : 'Cerrada') }}
                    </span>
                    <span class="text-xs">Horario {{ \App\Support\HorarioTienda::legible($estadoTienda['apertura']) }} a {{ \App\Support\HorarioTienda::legible($estadoTienda['cierre']) }}</span>
                </p>
            </div>
            <div class="flex gap-2">
                <a href="{{ route('vendedor.operacion.edit') }}" class="rounded-xl border border-atlantia-rose/25 bg-white px-4 py-2.5 text-sm font-black text-atlantia-wine hover:bg-atlantia-blush">Horario y pausa</a>
                <a href="{{ route('vendedor.productos.index') }}" class="rounded-xl bg-atlantia-wine px-4 py-2.5 text-sm font-black text-white hover:bg-atlantia-wine-700">Mis productos</a>
            </div>
        </div>

        {{-- Guía para dejar lista la tienda (solo mientras falte algo) --}}
        @if ($pasos->isNotEmpty() && $pasosHechos < $pasos->count())
            <article class="rounded-2xl border border-atlantia-wine/20 bg-[linear-gradient(135deg,#fff_0%,#fbf1f1_100%)] p-5 shadow-[0_10px_26px_rgba(59,6,12,0.05)]" data-primeros-pasos>
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <h2 class="text-lg font-black">Deja lista tu tienda para vender</h2>
                        <p class="text-sm text-atlantia-ink/60">Completa estos pasos y tus productos empezarán a aparecer a los clientes.</p>
                    </div>
                    <span class="rounded-full bg-atlantia-wine px-3 py-1 text-xs font-black text-white">{{ $pasosHechos }} de {{ $pasos->count() }} listos</span>
                </div>
                <div class="mt-3 h-1.5 overflow-hidden rounded-full bg-atlantia-rose/15">
                    <div class="h-full rounded-full bg-atlantia-wine" style="width: {{ round($pasosHechos / max(1, $pasos->count()) * 100) }}%"></div>
                </div>
                <ol class="mt-4 grid gap-2 sm:grid-cols-2 xl:grid-cols-4">
                    @foreach ($pasos as $paso)
                        <li>
                            <a href="{{ $paso['url'] }}" @class([
                                'flex h-full items-start gap-3 rounded-xl border p-3 transition',
                                'border-emerald-200 bg-emerald-50/60' => $paso['hecho'],
                                'border-atlantia-rose/20 bg-white hover:border-atlantia-wine/40 hover:bg-atlantia-blush/30' => ! $paso['hecho'],
                            ])>
                                <span @class([
                                    'grid h-7 w-7 shrink-0 place-items-center rounded-full text-sm font-black',
                                    'bg-emerald-600 text-white' => $paso['hecho'],
                                    'bg-atlantia-blush text-atlantia-wine' => ! $paso['hecho'],
                                ])>{{ $paso['hecho'] ? '✓' : $loop->iteration }}</span>
                                <span class="min-w-0">
                                    <span @class(['block text-sm font-black', 'text-emerald-900 line-through decoration-emerald-400' => $paso['hecho']])>{{ $paso['titulo'] }}</span>
                                    <span class="block text-xs text-atlantia-ink/55">{{ $paso['hecho'] ? 'Listo' : $paso['detalle'] }}</span>
                                </span>
                            </a>
                        </li>
                    @endforeach
                </ol>
            </article>
        @endif

        {{-- Indicadores --}}
        <div class="grid grid-cols-2 gap-3 lg:grid-cols-4">
            @foreach ($indicadores as [$etiqueta, $valor, $dato])
                <article @class([$tarjeta, 'bg-[linear-gradient(135deg,#3a0004_0%,#5e0008_100%)] !border-transparent text-white' => $loop->first])>
                    <p @class(['text-xs font-black uppercase tracking-wide', 'text-white/70' => $loop->first, 'text-atlantia-ink/50' => ! $loop->first])>{{ $etiqueta }}</p>
                    <p class="mt-1.5 text-2xl font-black sm:text-3xl">{{ $valor }}</p>
                    <p class="mt-1 text-xs font-bold">
                        @if ($dato['cambio'] === null)
                            <span @class(['text-white/60' => $loop->first, 'text-atlantia-ink/45' => ! $loop->first])>{{ $dato['compara'] }}</span>
                        @else
                            <span @class([
                                'text-emerald-300' => $loop->first && $dato['cambio'] >= 0,
                                'text-red-200' => $loop->first && $dato['cambio'] < 0,
                                'text-emerald-700' => ! $loop->first && $dato['cambio'] >= 0,
                                'text-red-700' => ! $loop->first && $dato['cambio'] < 0,
                            ])>{{ $dato['cambio'] >= 0 ? '▲' : '▼' }} {{ number_format(abs($dato['cambio']), 1) }} %</span>
                            <span @class(['text-white/60' => $loop->first, 'text-atlantia-ink/45' => ! $loop->first])>{{ $dato['compara'] }}</span>
                        @endif
                    </p>
                </article>
            @endforeach
        </div>

        <div class="grid gap-4 lg:grid-cols-[0.95fr_1.05fr]">
            {{-- Pedidos por atender --}}
            <article class="{{ $tarjeta }}">
                <div class="flex items-center justify-between gap-3">
                    <h2 class="flex items-center gap-2 text-lg font-black">
                        Pedidos por atender
                        @if ($metrics['total_por_atender'] > 0)
                            <span class="rounded-full bg-atlantia-wine px-2.5 py-0.5 text-xs font-black text-white">{{ $metrics['total_por_atender'] }}</span>
                        @endif
                    </h2>
                    <a href="{{ route('vendedor.pedidos.index') }}" class="text-sm font-black text-atlantia-wine hover:underline">Ver todos →</a>
                </div>
                <div class="mt-2 divide-y divide-atlantia-rose/10">
                    @forelse ($metrics['por_atender'] as $pedido)
                        @php
                            [$estadoTexto, $estadoClase] = $estados[$pedido->estadoValor()] ?? [ucfirst(str_replace('_', ' ', $pedido->estadoValor())), 'bg-slate-100 text-slate-700'];
                        @endphp
                        <a href="{{ route('vendedor.pedidos.show', $pedido->uuid) }}" class="flex items-center gap-3 py-3 hover:bg-atlantia-blush/30">
                            <div class="min-w-0 flex-1">
                                <p class="truncate text-sm font-black">{{ $pedido->numero_pedido }}</p>
                                <p class="text-xs text-atlantia-ink/55">hace {{ $pedido->created_at?->diffForHumans(null, true) }}</p>
                            </div>
                            <span class="rounded-full px-2.5 py-1 text-xs font-black {{ $estadoClase }}">{{ $estadoTexto }}</span>
                            <span class="w-24 text-right text-sm font-black">Q {{ number_format((float) $pedido->total, 2) }}</span>
                        </a>
                    @empty
                        <div class="mt-3 flex flex-col items-center rounded-xl bg-emerald-50 px-4 py-8 text-center">
                            <span class="grid h-10 w-10 place-items-center rounded-full bg-emerald-600 text-lg font-black text-white">✓</span>
                            <p class="mt-2 font-black text-emerald-800">Todo al día</p>
                            <p class="text-xs text-emerald-700/80">No tienes pedidos esperando. Te avisamos cuando llegue uno.</p>
                        </div>
                    @endforelse
                </div>
            </article>

            {{-- Ventas de los últimos 14 días --}}
            <article class="{{ $tarjeta }}">
                <div class="flex flex-wrap items-start justify-between gap-2">
                    <div>
                        <h2 class="text-lg font-black">Ventas de los últimos 14 días</h2>
                        <p class="text-xs text-atlantia-ink/55">Total: <strong class="text-atlantia-ink">Q {{ number_format($totalPeriodo, 2) }}</strong></p>
                    </div>
                    <a href="{{ route('vendedor.reportes.index') }}" class="text-sm font-black text-atlantia-wine hover:underline">Reportes →</a>
                </div>
                <div class="mt-5 flex h-44 items-end gap-1.5 border-b border-atlantia-rose/20 sm:gap-2" role="img" aria-label="Ventas por día de los últimos 14 días">
                    @foreach ($dias as $dia)
                        <div class="group relative flex h-full flex-1 flex-col justify-end" title="{{ $dia['fecha']->translatedFormat('D j M') }}: Q {{ number_format($dia['total'], 2) }}">
                            <span class="pointer-events-none absolute -top-1 left-1/2 z-10 hidden -translate-x-1/2 -translate-y-full whitespace-nowrap rounded-md bg-atlantia-ink px-2 py-1 text-[11px] font-bold text-white group-hover:block">Q {{ number_format($dia['total'], 2) }}</span>
                            @if ($dia['total'] > 0)
                                <span class="block w-full rounded-t-sm bg-atlantia-wine" style="height: {{ round($dia['total'] / $maxDia * 100, 2) }}%"></span>
                            @else
                                <span class="block h-0.5 w-full bg-atlantia-rose/20"></span>
                            @endif
                        </div>
                    @endforeach
                </div>
                <div class="mt-1.5 flex gap-1.5 sm:gap-2">
                    @foreach ($dias as $dia)
                        <span @class(['flex-1 text-center text-[10px] font-bold', 'text-atlantia-wine' => $loop->last, 'text-atlantia-ink/45' => ! $loop->last])>{{ $loop->last ? 'Hoy' : $dia['fecha']->format('j') }}</span>
                    @endforeach
                </div>
            </article>
        </div>

        <div class="grid gap-4 lg:grid-cols-2">
            {{-- Lo más vendido --}}
            <article class="{{ $tarjeta }}">
                <h2 class="text-lg font-black">Lo más vendido</h2>
                <p class="text-xs text-atlantia-ink/55">Últimos 30 días</p>
                <ol class="mt-3 space-y-2.5">
                    @forelse ($metrics['top_productos'] as $producto)
                        <li class="flex items-center gap-3">
                            <span class="grid h-7 w-7 shrink-0 place-items-center rounded-lg bg-atlantia-blush text-xs font-black text-atlantia-wine">{{ $loop->iteration }}</span>
                            <span class="min-w-0 flex-1">
                                <span class="block truncate text-sm font-bold">{{ $producto->nombre }}</span>
                                <span class="block text-xs text-atlantia-ink/55">{{ number_format((int) $producto->cantidad) }} vendidos · Q {{ number_format((float) $producto->venta, 2) }}</span>
                            </span>
                        </li>
                    @empty
                        <li class="rounded-xl bg-atlantia-blush/30 px-4 py-6 text-center text-sm text-atlantia-ink/55">Aquí verás tus productos más vendidos cuando tengas ventas.</li>
                    @endforelse
                </ol>
            </article>

            {{-- Inventario por reponer --}}
            <article class="{{ $tarjeta }}">
                <div class="flex items-center justify-between gap-3">
                    <h2 class="text-lg font-black">Productos por reponer</h2>
                    <a href="{{ route('vendedor.inventario.index') }}" class="text-sm font-black text-atlantia-wine hover:underline">Inventario →</a>
                </div>
                <p class="text-xs text-atlantia-ink/55">Llegaron a su cantidad mínima</p>
                <ul class="mt-3 space-y-2">
                    @forelse ($metrics['stock_bajo'] as $inventario)
                        <li class="flex items-center justify-between gap-3 rounded-xl border border-amber-200 bg-amber-50/60 px-3 py-2">
                            <span class="min-w-0 truncate text-sm font-bold">{{ $inventario->producto?->nombre ?? 'Producto' }}</span>
                            <span class="shrink-0 text-xs font-black text-amber-800">Quedan {{ (int) $inventario->stock_actual }} · mínimo {{ (int) $inventario->stock_minimo }}</span>
                        </li>
                    @empty
                        <li class="rounded-xl bg-emerald-50 px-4 py-6 text-center text-sm text-emerald-800">Todo tu inventario está bien abastecido.</li>
                    @endforelse
                </ul>
                <div class="mt-4 flex flex-wrap gap-2 border-t border-atlantia-rose/10 pt-3 text-xs font-bold">
                    <a href="{{ route('vendedor.productos.index') }}" class="rounded-full bg-atlantia-blush/50 px-3 py-1 text-atlantia-wine hover:bg-atlantia-blush">{{ $metrics['resumen']['productos_publicados'] }} productos publicados</a>
                    @if ($metrics['resumen']['sugerencias_reabasto'] > 0)
                        <a href="{{ route('vendedor.reabasto.index') }}" class="rounded-full bg-sky-50 px-3 py-1 text-sky-800 hover:bg-sky-100">{{ $metrics['resumen']['sugerencias_reabasto'] }} sugerencias de reabasto</a>
                    @endif
                    @if ($metrics['resumen']['comisiones_pendientes'] > 0)
                        <a href="{{ route('vendedor.comisiones.index') }}" class="rounded-full bg-amber-50 px-3 py-1 text-amber-800 hover:bg-amber-100">{{ $metrics['resumen']['comisiones_pendientes'] }} comisiones por pagar</a>
                    @endif
                </div>
            </article>
        </div>
    </section>
@endsection
