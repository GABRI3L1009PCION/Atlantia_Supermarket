@extends(auth()->user()?->isSuperAdmin() && request()->routeIs('admin.*') ? 'layouts.super-admin' : 'layouts.app')

@section('content')
    @php
        $utilidad = $reporte['utilidad'];
        $mes = $reporte['mes'];
        $nombreMes = ucfirst($mes->translatedFormat('F Y'));
        $mesAnterior = $mes->copy()->subMonth()->format('Y-m');
        $mesSiguiente = $mes->copy()->addMonth();
        $haySiguiente = $mesSiguiente->lte(now()->startOfMonth());
        $equilibrio = $reporte['pedidos_equilibrio'];
        $avance = $equilibrio ? min(100, round($reporte['pedidos'] / max(1, $equilibrio) * 100)) : null;
        $sinMovimiento = $reporte['pedidos'] === 0 && $reporte['total_ingresos'] == 0 && $reporte['total_egresos'] == 0;
        $tarjeta = 'rounded-2xl border border-atlantia-rose/15 bg-white shadow-[0_8px_20px_rgba(59,6,12,0.04)]';
        $campo = 'rounded-lg border border-atlantia-rose/30 bg-white px-3 py-2 text-sm focus:border-atlantia-wine focus:outline-none focus:ring-2 focus:ring-atlantia-wine/15';
    @endphp

    <section class="mx-auto max-w-7xl space-y-4 pb-6 text-atlantia-ink">
        {{-- Encabezado con selector de mes --}}
        <header class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h1 class="text-3xl font-black tracking-tight">Utilidad del mes</h1>
                <p class="mt-0.5 text-sm text-atlantia-ink/60">Lo que entra, lo que sale y lo que le queda a Atlantia. Cuenta los pedidos entregados en el mes.</p>
            </div>
            <form method="GET" class="flex items-center gap-1.5" aria-label="Elegir mes">
                <a href="{{ route('admin.utilidad.index', ['mes' => $mesAnterior]) }}" class="grid h-9 w-9 place-items-center rounded-lg border border-atlantia-rose/25 bg-white font-black text-atlantia-wine hover:bg-atlantia-blush" title="Mes anterior" aria-label="Mes anterior">‹</a>
                <input type="month" name="mes" value="{{ $mesValor }}" max="{{ now()->format('Y-m') }}" data-autosubmit class="{{ $campo }} h-9 py-1">
                @if ($haySiguiente)
                    <a href="{{ route('admin.utilidad.index', ['mes' => $mesSiguiente->format('Y-m')]) }}" class="grid h-9 w-9 place-items-center rounded-lg border border-atlantia-rose/25 bg-white font-black text-atlantia-wine hover:bg-atlantia-blush" title="Mes siguiente" aria-label="Mes siguiente">›</a>
                @else
                    <span class="grid h-9 w-9 place-items-center rounded-lg border border-atlantia-rose/10 font-black text-atlantia-ink/20" aria-hidden="true">›</span>
                @endif
                <button class="sr-only">Ver</button>
            </form>
        </header>

        {{-- Resultado del mes --}}
        <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-[1.5fr_1fr_1fr_1.2fr]">
            <article @class(['rounded-2xl px-5 py-4 text-white shadow-lg', 'bg-emerald-700' => $utilidad >= 0, 'bg-red-700' => $utilidad < 0])>
                <p class="text-[11px] font-black uppercase tracking-wide text-white/75">{{ $utilidad >= 0 ? 'Utilidad' : 'Pérdida' }} de {{ $mes->translatedFormat('F Y') }}</p>
                <p class="mt-0.5 text-4xl font-black" data-utilidad>{{ $utilidad < 0 ? "− " : "" }}Q {{ number_format(abs($utilidad), 2) }}</p>
                <p class="mt-0.5 text-xs text-white/80">
                    @if ($sinMovimiento)
                        Aún no hay pedidos entregados ni gastos en este mes.
                    @else
                        Ingresos Q {{ number_format($reporte['total_ingresos'], 2) }} − egresos Q {{ number_format($reporte['total_egresos'], 2) }}
                    @endif
                </p>
            </article>

            <article class="{{ $tarjeta }} px-5 py-4">
                <p class="text-[11px] font-black uppercase tracking-wide text-atlantia-ink/50">Pedidos entregados</p>
                <p class="mt-0.5 text-3xl font-black">{{ number_format($reporte['pedidos']) }}</p>
                <p class="text-xs text-atlantia-ink/55">en {{ $mes->translatedFormat('F') }}</p>
            </article>

            <article class="{{ $tarjeta }} px-5 py-4">
                <p class="text-[11px] font-black uppercase tracking-wide text-atlantia-ink/50">Ganancia por pedido</p>
                <p class="mt-0.5 text-3xl font-black">Q {{ number_format($reporte['aporte_por_pedido'], 2) }}</p>
                <p class="text-xs text-atlantia-ink/55">promedio, antes de gastos fijos</p>
            </article>

            <article class="{{ $tarjeta }} px-5 py-4">
                <p class="text-[11px] font-black uppercase tracking-wide text-atlantia-ink/50">Punto de equilibrio</p>
                @if ($equilibrio !== null)
                    <p class="mt-0.5 text-sm"><strong class="text-3xl font-black">{{ number_format($reporte['pedidos']) }}</strong> <span class="font-bold text-atlantia-ink/55">de {{ number_format($equilibrio) }} pedidos</span></p>
                    <div class="mt-2 h-2 overflow-hidden rounded-full bg-atlantia-rose/15" role="progressbar" aria-valuenow="{{ $avance }}" aria-valuemin="0" aria-valuemax="100">
                        <div @class(['h-full rounded-full', 'bg-emerald-600' => $avance >= 100, 'bg-atlantia-wine' => $avance < 100]) style="width: {{ $avance }}%"></div>
                    </div>
                    <p class="mt-1 text-xs text-atlantia-ink/55">{{ $avance >= 100 ? 'Gastos fijos cubiertos este mes.' : 'Para cubrir los gastos fijos del mes.' }}</p>
                @else
                    <p class="mt-0.5 text-3xl font-black text-atlantia-ink/30">—</p>
                    <p class="text-xs text-atlantia-ink/55">Se calcula cuando haya pedidos entregados en el mes.</p>
                @endif
            </article>
        </div>

        {{-- Detalle: entra, sale y gastos fijos --}}
        <div class="grid gap-3 lg:grid-cols-3">
            <article class="{{ $tarjeta }} flex flex-col p-5">
                <div class="flex items-baseline justify-between gap-3">
                    <h2 class="text-base font-black text-emerald-800">Entra</h2>
                    <span class="text-base font-black text-emerald-700">Q {{ number_format($reporte['total_ingresos'], 2) }}</span>
                </div>
                <dl class="mt-3 flex-1 space-y-2 text-sm">
                    @foreach ($reporte['ingresos'] as $linea)
                        <div class="flex justify-between gap-3">
                            <dt @class(['text-atlantia-ink/75', '!text-atlantia-ink/45' => $linea['monto'] == 0])>{{ $linea['concepto'] }} @if ($linea['nota'])<span class="block text-[11px] font-bold text-amber-700">{{ $linea['nota'] }}</span>@endif</dt>
                            <dd @class(['shrink-0 font-bold', 'text-emerald-700' => $linea['monto'] > 0, 'text-atlantia-ink/35' => $linea['monto'] == 0])>+ Q {{ number_format($linea['monto'], 2) }}</dd>
                        </div>
                    @endforeach
                </dl>
                <p class="mt-3 border-t border-atlantia-rose/10 pt-2 text-[11px] leading-snug text-atlantia-ink/50">Ventas de Atlantia Store: Q {{ number_format($reporte['venta_atlantia'], 2) }}. Lo que venden las tiendas locales no es ingreso: se les paga a ellas.</p>
            </article>

            <article class="{{ $tarjeta }} flex flex-col p-5">
                <div class="flex items-baseline justify-between gap-3">
                    <h2 class="text-base font-black text-red-700">Sale</h2>
                    <span class="text-base font-black text-red-700">Q {{ number_format($reporte['total_egresos'], 2) }}</span>
                </div>
                <dl class="mt-3 space-y-2 text-sm">
                    @foreach ($reporte['egresos'] as $linea)
                        <div class="flex justify-between gap-3">
                            <dt @class(['text-atlantia-ink/75', '!text-atlantia-ink/45' => $linea['monto'] == 0])>{{ $linea['concepto'] }}</dt>
                            <dd @class(['shrink-0 font-bold', 'text-red-700' => $linea['monto'] > 0, 'text-atlantia-ink/35' => $linea['monto'] == 0])>− Q {{ number_format($linea['monto'], 2) }}</dd>
                        </div>
                    @endforeach
                </dl>
                @if ($reporte['total_ingresos'] > 0)
                    @php
                        $proporcion = min(100, round($reporte['total_egresos'] / $reporte['total_ingresos'] * 100));
                    @endphp
                    <div class="mt-auto pt-4">
                        <p class="text-xs font-bold text-atlantia-ink/60">Se va el {{ $proporcion }} % de lo que entra</p>
                        <div class="mt-1.5 h-2 overflow-hidden rounded-full bg-emerald-100">
                            <div class="h-full rounded-full bg-red-600" style="width: {{ $proporcion }}%"></div>
                        </div>
                    </div>
                @endif
            </article>

            {{-- Gastos fijos del mes --}}
            <article class="{{ $tarjeta }} flex flex-col p-5">
                <div class="flex items-baseline justify-between gap-3">
                    <h2 class="text-base font-black">Gastos fijos de {{ $mes->translatedFormat('F') }}</h2>
                    <span class="text-base font-black">Q {{ number_format((float) $reporte['gastos']->sum('monto'), 2) }}</span>
                </div>
                <form method="POST" action="{{ route('admin.utilidad.gastos.store') }}" class="mt-3 grid grid-cols-[1fr_6.5rem] gap-2" data-protect-submit>
                    @csrf
                    <input type="hidden" name="mes" value="{{ $mesValor }}">
                    <select name="categoria" class="{{ $campo }} col-span-2" aria-label="Tipo de gasto">
                        @foreach ($categorias as $clave => $texto)<option value="{{ $clave }}">{{ $texto }}</option>@endforeach
                    </select>
                    <input name="concepto" required maxlength="160" value="{{ old('concepto') }}" placeholder="Ej. Servidor DigitalOcean" aria-label="Concepto" class="{{ $campo }} min-w-0">
                    <input name="monto" type="number" step="0.01" min="0.01" required value="{{ old('monto') }}" placeholder="Q 0.00" aria-label="Monto" class="{{ $campo }} min-w-0">
                    <button class="col-span-2 rounded-lg bg-atlantia-wine px-4 py-2 text-sm font-black text-white hover:bg-atlantia-wine-700">Agregar gasto</button>
                </form>
                @error('concepto') <p class="mt-1 text-xs font-bold text-red-600">{{ $message }}</p> @enderror
                @error('monto') <p class="mt-1 text-xs font-bold text-red-600">{{ $message }}</p> @enderror

                <ul class="mt-3 max-h-44 divide-y divide-atlantia-rose/10 overflow-y-auto text-sm">
                    @forelse ($reporte['gastos'] as $gasto)
                        <li class="flex items-center justify-between gap-3 py-2">
                            <span class="min-w-0">
                                <span class="block truncate font-semibold">{{ $gasto->concepto }}</span>
                                <span class="block truncate text-xs text-atlantia-ink/50">{{ $categorias[$gasto->categoria] ?? $gasto->categoria }}</span>
                            </span>
                            <span class="flex shrink-0 items-center gap-3">
                                <span class="font-bold">Q {{ number_format((float) $gasto->monto, 2) }}</span>
                                <form method="POST" action="{{ route('admin.utilidad.gastos.destroy', $gasto) }}" data-confirm="¿Quitar el gasto «{{ $gasto->concepto }}»?">
                                    @csrf
                                    @method('DELETE')
                                    <button class="text-xs font-bold text-red-600 hover:underline">Quitar</button>
                                </form>
                            </span>
                        </li>
                    @empty
                        <li class="py-3 text-xs text-atlantia-ink/55">Aún no registras gastos fijos este mes: servidor, internet, sueldos, publicidad…</li>
                    @endforelse
                </ul>
            </article>
        </div>
    </section>
@endsection
