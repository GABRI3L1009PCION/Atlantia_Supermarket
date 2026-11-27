@extends('layouts.super-admin')

@section('content')
    @php
        $kpis = $negocio['kpis'];
        $pendientes = $negocio['pendientes'];
        $dias = $negocio['ventas_dias'];
        $operacion = $negocio['operacion'];
        $maxDia = max(1, (float) $dias->max('total'));
        $totalPeriodo = (float) $dias->sum('total');
        $tarjeta = 'rounded-2xl border border-atlantia-rose/15 bg-white p-5 shadow-[0_10px_26px_rgba(59,6,12,0.05)]';
        $hora = (int) now()->format('G');
        $saludo = $hora < 12 ? 'Buenos días' : ($hora < 19 ? 'Buenas tardes' : 'Buenas noches');
        $nombre = \Illuminate\Support\Str::of(auth()->user()?->name ?? '')->explode(' ')->first();
        $indicadores = [
            ['Ventas de hoy', 'Q '.number_format($kpis['ventas_hoy']['valor'], 2), $kpis['ventas_hoy'], 'vs. ayer'],
            ['Pedidos de hoy', number_format($kpis['pedidos_hoy']['valor']), $kpis['pedidos_hoy'], 'vs. ayer'],
            ['Utilidad del mes', ($kpis['utilidad_mes']['valor'] < 0 ? '− ' : '').'Q '.number_format(abs($kpis['utilidad_mes']['valor']), 2), $kpis['utilidad_mes'], 'vs. mes pasado'],
            ['Ticket promedio', 'Q '.number_format($kpis['ticket_promedio']['valor'], 2), $kpis['ticket_promedio'], 'vs. mes pasado'],
        ];
        $estados = [
            'pendiente' => ['Por confirmar', 'bg-amber-50 text-amber-800'],
            'confirmado' => ['Confirmado', 'bg-sky-50 text-sky-800'],
            'en_revision' => ['En revisión', 'bg-amber-50 text-amber-800'],
            'preparando' => ['Preparando', 'bg-violet-50 text-violet-800'],
            'listo_para_entrega' => ['Listo', 'bg-indigo-50 text-indigo-800'],
            'en_ruta' => ['En camino', 'bg-atlantia-blush text-atlantia-wine'],
        ];
        $niveles = [
            'alta' => 'bg-red-600',
            'media' => 'bg-amber-500',
            'baja' => 'bg-yellow-400',
        ];
    @endphp

    <section class="mx-auto max-w-7xl space-y-5 pb-10">
        {{-- Saludo --}}
        <div class="flex flex-wrap items-end justify-between gap-3">
            <div>
                <p class="text-sm font-bold text-atlantia-ink/55">{{ ucfirst(now()->translatedFormat('l j \d\e F')) }}</p>
                <h1 class="text-3xl font-black tracking-tight text-atlantia-ink">{{ $saludo }}{{ $nombre ? ', '.$nombre : '' }}</h1>
            </div>
            <div class="flex gap-2">
                <a href="{{ route('admin.productos.index') }}" class="rounded-xl border border-atlantia-rose/25 bg-white px-4 py-2.5 text-sm font-black text-atlantia-wine hover:bg-atlantia-blush">Productos</a>
                <a href="{{ route('admin.pedidos.index') }}" class="rounded-xl bg-atlantia-wine px-4 py-2.5 text-sm font-black text-white hover:bg-atlantia-wine-700">Ver pedidos</a>
            </div>
        </div>

        {{-- 1. Indicadores del día --}}
        <div class="grid grid-cols-2 gap-3 lg:grid-cols-4">
            @foreach ($indicadores as [$etiqueta, $valor, $dato, $comparado])
                <article @class([$tarjeta, 'bg-[linear-gradient(135deg,#3a0004_0%,#5e0008_100%)] !border-transparent text-white' => $loop->first])>
                    <p @class(['text-xs font-black uppercase tracking-wide', 'text-white/70' => $loop->first, 'text-atlantia-ink/50' => ! $loop->first])>{{ $etiqueta }}</p>
                    <p @class(['mt-1.5 text-2xl font-black sm:text-3xl', 'text-atlantia-ink' => ! $loop->first]) data-kpi>{{ $valor }}</p>
                    <p class="mt-1 text-xs font-bold">
                        @if ($dato['cambio'] === null)
                            <span @class(['text-white/60' => $loop->first, 'text-atlantia-ink/45' => ! $loop->first])>Sin datos para comparar</span>
                        @else
                            <span @class([
                                'text-emerald-300' => $loop->first && $dato['cambio'] >= 0,
                                'text-red-200' => $loop->first && $dato['cambio'] < 0,
                                'text-emerald-700' => ! $loop->first && $dato['cambio'] >= 0,
                                'text-red-700' => ! $loop->first && $dato['cambio'] < 0,
                            ])>{{ $dato['cambio'] >= 0 ? '▲' : '▼' }} {{ number_format(abs($dato['cambio']), 1) }} %</span>
                            <span @class(['text-white/60' => $loop->first, 'text-atlantia-ink/45' => ! $loop->first])>{{ $comparado }}</span>
                        @endif
                    </p>
                </article>
            @endforeach
        </div>

        <div class="grid gap-4 lg:grid-cols-[0.9fr_1.1fr]">
            {{-- 2. Requiere tu atención --}}
            <article class="{{ $tarjeta }}" data-pendientes>
                <div class="flex items-center justify-between gap-3">
                    <h2 class="text-lg font-black text-atlantia-ink">Requiere tu atención</h2>
                    @if ($pendientes->isNotEmpty())
                        <span class="rounded-full bg-atlantia-wine px-2.5 py-0.5 text-xs font-black text-white">{{ $pendientes->count() }}</span>
                    @endif
                </div>
                @forelse ($pendientes as $item)
                    <a href="{{ $item['url'] }}" class="group mt-3 flex items-start gap-3 rounded-xl border border-atlantia-rose/15 p-3 transition hover:border-atlantia-wine/40 hover:bg-atlantia-blush/40">
                        <span class="mt-1.5 h-2.5 w-2.5 shrink-0 rounded-full {{ $niveles[$item['nivel']] ?? 'bg-amber-500' }}" aria-hidden="true"></span>
                        <span class="min-w-0 flex-1">
                            <span class="block text-sm font-black text-atlantia-ink">{{ $item['titulo'] }}</span>
                            <span class="block text-xs text-atlantia-ink/60">{{ $item['detalle'] }}</span>
                        </span>
                        <span class="text-atlantia-wine/50 transition group-hover:translate-x-0.5 group-hover:text-atlantia-wine" aria-hidden="true">→</span>
                    </a>
                @empty
                    <div class="mt-4 flex flex-col items-center rounded-xl bg-emerald-50 px-4 py-8 text-center">
                        <span class="grid h-11 w-11 place-items-center rounded-full bg-emerald-600 text-xl font-black text-white">✓</span>
                        <p class="mt-3 font-black text-emerald-800">Todo al día</p>
                        <p class="text-xs text-emerald-700/80">No hay pedidos, pagos ni solicitudes esperando por ti.</p>
                    </div>
                @endforelse
            </article>

            {{-- 3. Ventas de los últimos 14 días --}}
            <article class="{{ $tarjeta }}">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <h2 class="text-lg font-black text-atlantia-ink">Ventas de los últimos 14 días</h2>
                        <p class="text-xs text-atlantia-ink/55">Total: <strong class="text-atlantia-ink">Q {{ number_format($totalPeriodo, 2) }}</strong></p>
                    </div>
                    <div class="flex gap-3 text-xs font-bold text-atlantia-ink/65">
                        <span class="flex items-center gap-1.5"><span class="h-2.5 w-2.5 rounded-sm bg-atlantia-wine"></span>Atlantia Store</span>
                        <span class="flex items-center gap-1.5"><span class="h-2.5 w-2.5 rounded-sm bg-[#e3a3aa]"></span>Tiendas locales</span>
                    </div>
                </div>
                <div class="mt-5 flex h-48 items-end gap-1.5 border-b border-atlantia-rose/20 sm:gap-2" role="img" aria-label="Ventas por día de los últimos 14 días">
                    @foreach ($dias as $dia)
                        <div class="group relative flex h-full flex-1 flex-col justify-end" title="{{ $dia['fecha']->translatedFormat('D j M') }}: Q {{ number_format($dia['total'], 2) }}">
                            <span class="pointer-events-none absolute -top-1 left-1/2 z-10 hidden -translate-x-1/2 -translate-y-full whitespace-nowrap rounded-md bg-atlantia-ink px-2 py-1 text-[11px] font-bold text-white group-hover:block">
                                Q {{ number_format($dia['total'], 2) }}
                            </span>
                            @if ($dia['total'] > 0)
                                <span class="block w-full rounded-t-sm bg-[#e3a3aa]" style="height: {{ round($dia['locales'] / $maxDia * 100, 2) }}%"></span>
                                <span @class(['block w-full bg-atlantia-wine', 'rounded-t-sm' => $dia['locales'] <= 0]) style="height: {{ round($dia['atlantia'] / $maxDia * 100, 2) }}%"></span>
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

        <div class="grid gap-4 lg:grid-cols-[1.25fr_0.75fr]">
            {{-- 4a. Pedidos en curso --}}
            <article class="{{ $tarjeta }}">
                <div class="flex items-center justify-between gap-3">
                    <h2 class="text-lg font-black text-atlantia-ink">Pedidos en curso</h2>
                    <a href="{{ route('admin.pedidos.index') }}" class="text-sm font-black text-atlantia-wine hover:underline">Ver todos →</a>
                </div>
                <div class="mt-2 divide-y divide-atlantia-rose/10">
                    @forelse ($negocio['en_curso'] as $pedido)
                        @php
                            [$estadoTexto, $estadoClase] = $estados[$pedido['estado']] ?? [ucfirst(str_replace('_', ' ', $pedido['estado'])), 'bg-slate-100 text-slate-700'];
                        @endphp
                        <a href="{{ route('admin.pedidos.show', $pedido['uuid']) }}" class="flex flex-wrap items-center gap-x-4 gap-y-1 py-3 hover:bg-atlantia-blush/30 sm:flex-nowrap">
                            <div class="min-w-0 flex-1">
                                <p class="truncate text-sm font-black text-atlantia-ink">{{ $pedido['numero'] }} · {{ $pedido['tienda'] }}</p>
                                <p class="truncate text-xs text-atlantia-ink/55">
                                    {{ $pedido['cliente'] }} · hace {{ $pedido['hace'] }}
                                    · {{ $pedido['repartidor'] ? '🛵 '.$pedido['repartidor'] : 'sin repartidor' }}
                                </p>
                            </div>
                            <span class="rounded-full px-2.5 py-1 text-xs font-black {{ $estadoClase }}">{{ $estadoTexto }}</span>
                            <span class="w-24 text-right text-sm font-black text-atlantia-ink">Q {{ number_format($pedido['total'], 2) }}</span>
                        </a>
                    @empty
                        <p class="py-8 text-center text-sm text-atlantia-ink/50">No hay pedidos en curso en este momento.</p>
                    @endforelse
                </div>
            </article>

            {{-- 4b. Lo más vendido --}}
            <article class="{{ $tarjeta }}">
                <h2 class="text-lg font-black text-atlantia-ink">Lo más vendido</h2>
                <p class="text-xs text-atlantia-ink/55">Atlantia Store, últimos 7 días</p>
                <ol class="mt-3 space-y-2.5">
                    @forelse ($negocio['top_productos'] as $producto)
                        <li class="flex items-center gap-3">
                            <span class="grid h-7 w-7 shrink-0 place-items-center rounded-lg bg-atlantia-blush text-xs font-black text-atlantia-wine">{{ $loop->iteration }}</span>
                            <span class="min-w-0 flex-1">
                                <span class="block truncate text-sm font-bold text-atlantia-ink">{{ $producto['nombre'] }}</span>
                                <span class="block text-xs text-atlantia-ink/55">{{ number_format($producto['cantidad']) }} unidades · Q {{ number_format($producto['venta'], 2) }}</span>
                            </span>
                        </li>
                    @empty
                        <li class="py-6 text-center text-sm text-atlantia-ink/50">Aún no hay ventas esta semana.</li>
                    @endforelse
                </ol>
            </article>
        </div>

        {{-- 5. Operación --}}
        <div class="grid grid-cols-2 gap-3 lg:grid-cols-4">
            <article class="rounded-2xl border border-atlantia-rose/15 bg-white px-4 py-3.5">
                <p class="text-xs font-black uppercase tracking-wide text-atlantia-ink/50">Repartidores</p>
                <p class="mt-1 text-sm text-atlantia-ink"><strong class="text-xl font-black">{{ $operacion['repartidores_disponibles'] }}</strong> disponibles</p>
                <p class="text-xs text-atlantia-ink/55">{{ $operacion['repartidores_ocupados'] }} ocupados · {{ $operacion['pedidos_en_ruta'] }} en camino</p>
            </article>
            <article class="rounded-2xl border border-atlantia-rose/15 bg-white px-4 py-3.5">
                <p class="text-xs font-black uppercase tracking-wide text-atlantia-ink/50">Tiendas abiertas</p>
                <p class="mt-1 text-sm text-atlantia-ink"><strong class="text-xl font-black">{{ $operacion['tiendas_abiertas'] }}</strong> de {{ $operacion['tiendas_total'] }}</p>
                <a href="{{ route('admin.vendedores.index') }}" class="text-xs font-bold text-atlantia-wine hover:underline">Ver tiendas</a>
            </article>
            @foreach ($operacion['zonas'] as $zona => $cantidad)
                <article class="rounded-2xl border border-atlantia-rose/15 bg-white px-4 py-3.5">
                    <p class="truncate text-xs font-black uppercase tracking-wide text-atlantia-ink/50">{{ $zona }}</p>
                    <p class="mt-1 text-sm text-atlantia-ink"><strong class="text-xl font-black">{{ number_format($cantidad) }}</strong> pedidos</p>
                    <p class="text-xs text-atlantia-ink/55">en lo que va del mes</p>
                </article>
            @endforeach
        </div>
    </section>
@endsection
