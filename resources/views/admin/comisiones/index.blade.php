@extends(auth()->user()?->isSuperAdmin() && request()->routeIs('admin.*') ? 'layouts.super-admin' : 'layouts.app')

@section('content')
    @php
        $estados = [
            'pendiente' => ['Pendiente', 'bg-amber-50 text-amber-800 ring-amber-200', 'text-amber-600'],
            'facturada' => ['Facturada', 'bg-sky-50 text-sky-800 ring-sky-200', 'text-sky-600'],
            'pagada' => ['Pagada', 'bg-emerald-50 text-emerald-800 ring-emerald-200', 'text-emerald-600'],
            'vencida' => ['Vencida', 'bg-red-50 text-red-700 ring-red-200', 'text-red-600'],
            'anulada' => ['Anulada', 'bg-slate-100 text-slate-600 ring-slate-200', 'text-slate-500'],
        ];
        $anio = $filtros['anio'] ?? null;
        $mes = $filtros['mes'] ?? null;
        $periodo = $anio && $mes ? \Illuminate\Support\Carbon::create((int) $anio, (int) $mes, 1) : null;
        $periodoValor = $periodo?->format('Y-m');
        $recalcular = $periodo ?? now()->startOfMonth();
        $estadoActivo = $filtros['estado'] ?? null;
        $hayFiltros = filled($filtros['vendor_id'] ?? null) || filled($estadoActivo) || $periodo !== null;
        $urlCon = fn (array $cambios): string => route('admin.comisiones.index', array_filter([
            'periodo' => $periodoValor,
            'vendor_id' => $filtros['vendor_id'] ?? null,
            'estado' => $estadoActivo,
            ...$cambios,
        ], fn ($valor) => filled($valor)));
        $tarjeta = 'rounded-2xl border border-atlantia-rose/15 bg-white shadow-[0_8px_20px_rgba(59,6,12,0.04)]';
        $campo = 'h-9 rounded-lg border border-atlantia-rose/30 bg-white px-3 text-sm focus:border-atlantia-wine focus:outline-none focus:ring-2 focus:ring-atlantia-wine/15';
    @endphp

    <section class="mx-auto max-w-7xl space-y-4 pb-6 text-atlantia-ink">
        {{-- Encabezado --}}
        <header class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h1 class="text-3xl font-black tracking-tight">Comisiones</h1>
                <p class="mt-0.5 text-sm text-atlantia-ink/60">Lo que cada tienda le paga a Atlantia al mes: comisión por sus ventas más la renta fija de su plan.</p>
            </div>
            <form method="POST" action="{{ route('admin.comisiones.recalcular') }}" data-confirm="¿Recalcular las comisiones de {{ $recalcular->translatedFormat('F Y') }}? Se actualizan con las ventas cobradas de ese mes.">
                @csrf
                <input type="hidden" name="anio" value="{{ $recalcular->year }}">
                <input type="hidden" name="mes" value="{{ $recalcular->month }}">
                <button class="inline-flex items-center gap-2 rounded-xl bg-atlantia-wine px-4 py-2.5 text-sm font-black text-white transition hover:bg-atlantia-wine-700">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M20 12A8 8 0 0 1 6.3 17.7M4 12A8 8 0 0 1 17.7 6.3" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"/><path d="M6 21V18H3M18 3V6H21" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    Calcular {{ $recalcular->translatedFormat('F') }}
                </button>
            </form>
        </header>

        @if ($errors->has('anio') || $errors->has('mes'))
            <p class="rounded-xl border border-red-200 bg-red-50 px-4 py-2.5 text-sm font-bold text-red-700">No se pudo recalcular: revisa el mes elegido.</p>
        @endif

        {{-- Resumen: cada estado filtra la lista --}}
        <div class="grid grid-cols-2 gap-3 md:grid-cols-3 xl:grid-cols-[1.4fr_1fr_1fr_1fr_1fr]">
            <article class="col-span-2 rounded-2xl bg-[linear-gradient(135deg,#3a0004_0%,#5e0008_100%)] px-5 py-3.5 text-white shadow-lg md:col-span-1">
                <p class="text-[11px] font-black uppercase tracking-wide text-white/70">Total {{ $periodo ? 'de '.$periodo->translatedFormat('F Y') : 'de todos los meses' }}</p>
                <p class="mt-0.5 text-3xl font-black">Q {{ number_format($dashboard['total_periodo'], 2) }}</p>
            </article>
            @foreach (['pendiente' => 'pendientes', 'facturada' => 'facturadas', 'pagada' => 'pagadas', 'vencida' => 'vencidas'] as $estado => $clave)
                @php
                    [$texto, , $color] = $estados[$estado];
                @endphp
                <a href="{{ $urlCon(['estado' => $estadoActivo === $estado ? null : $estado]) }}" @class([
                    $tarjeta,
                    'px-4 py-3.5 transition hover:border-atlantia-wine/40',
                    '!border-atlantia-wine ring-2 ring-atlantia-wine/15' => $estadoActivo === $estado,
                ])>
                    <p class="text-[11px] font-black uppercase tracking-wide text-atlantia-ink/50">{{ $texto }}s</p>
                    <p class="mt-0.5 text-3xl font-black {{ $dashboard[$clave] > 0 ? $color : 'text-atlantia-ink/30' }}">{{ $dashboard[$clave] }}</p>
                </a>
            @endforeach
        </div>

        <div class="grid gap-4 xl:grid-cols-[1fr_17rem]">
            {{-- Lista de comisiones --}}
            <article class="{{ $tarjeta }} min-w-0 overflow-hidden">
                <form method="GET" class="flex flex-wrap items-center gap-2 border-b border-atlantia-rose/10 p-3">
                    <input type="month" name="periodo" value="{{ $periodoValor }}" data-autosubmit class="{{ $campo }}" aria-label="Mes">
                    <select name="vendor_id" data-autosubmit class="{{ $campo }} min-w-0 flex-1 sm:max-w-64" aria-label="Tienda">
                        <option value="">Todas las tiendas</option>
                        @foreach ($vendors as $vendor)
                            <option value="{{ $vendor->id }}" @selected((string) ($filtros['vendor_id'] ?? '') === (string) $vendor->id)>{{ $vendor->business_name }}</option>
                        @endforeach
                    </select>
                    <select name="estado" data-autosubmit class="{{ $campo }}" aria-label="Estado">
                        <option value="">Todos los estados</option>
                        @foreach ($estados as $clave => [$texto])
                            <option value="{{ $clave }}" @selected($estadoActivo === $clave)>{{ $texto }}</option>
                        @endforeach
                    </select>
                    <button class="sr-only">Filtrar</button>
                    @if ($hayFiltros)
                        <a href="{{ route('admin.comisiones.index') }}" class="text-sm font-bold text-atlantia-wine hover:underline">Quitar filtros</a>
                    @endif
                    <span class="ml-auto text-xs font-bold text-atlantia-ink/50">{{ $comisiones->total() }} {{ $comisiones->total() === 1 ? 'comisión' : 'comisiones' }}</span>
                </form>

                @if ($comisiones->isEmpty())
                    <div class="px-6 py-12 text-center">
                        <p class="font-black">{{ $hayFiltros ? 'Ninguna comisión coincide con los filtros.' : 'Aún no hay comisiones calculadas.' }}</p>
                        <p class="mt-1 text-sm text-atlantia-ink/55">
                            {{ $hayFiltros ? 'Prueba con otro mes, tienda o estado.' : 'Usa «Calcular '.$recalcular->translatedFormat('F').'» para generarlas con las ventas cobradas del mes.' }}
                        </p>
                    </div>
                @else
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead class="bg-atlantia-blush/40 text-left text-[11px] font-black uppercase tracking-wide text-atlantia-ink/55">
                                <tr>
                                    <th class="px-4 py-2.5">Tienda</th>
                                    <th class="px-3 py-2.5 text-right">Ventas</th>
                                    <th class="px-3 py-2.5 text-right">Comisión</th>
                                    <th class="px-3 py-2.5 text-right">Renta fija</th>
                                    <th class="px-3 py-2.5 text-right">A cobrar</th>
                                    <th class="px-3 py-2.5">Estado y vencimiento</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-atlantia-rose/10">
                                @foreach ($comisiones as $comision)
                                    @php
                                        [$estadoTexto, $estadoClase] = $estados[$comision->estado] ?? [ucfirst($comision->estado), 'bg-atlantia-blush text-atlantia-wine ring-atlantia-rose/20'];
                                        $formId = 'comision-'.$comision->id;
                                    @endphp
                                    <tr class="hover:bg-atlantia-blush/20">
                                        <td class="px-4 py-2.5">
                                            <p class="font-black">{{ $comision->vendor?->business_name ?? 'Tienda no disponible' }}</p>
                                            <p class="text-xs text-atlantia-ink/50">{{ ucfirst(\Illuminate\Support\Carbon::create($comision->anio, $comision->mes, 1)->translatedFormat('F Y')) }}</p>
                                        </td>
                                        <td class="px-3 py-2.5 text-right text-atlantia-ink/75">Q {{ number_format((float) $comision->total_ventas, 2) }}</td>
                                        <td class="px-3 py-2.5 text-right text-atlantia-ink/75">Q {{ number_format((float) $comision->monto_comision, 2) }}</td>
                                        <td class="px-3 py-2.5 text-right text-atlantia-ink/75">Q {{ number_format((float) $comision->renta_fija, 2) }}</td>
                                        <td class="px-3 py-2.5 text-right font-black text-atlantia-wine">Q {{ number_format((float) $comision->monto_total, 2) }}</td>
                                        <td class="px-3 py-2">
                                            <form id="{{ $formId }}" method="POST" action="{{ route('admin.comisiones.update', $comision) }}" class="flex items-center gap-1.5">
                                                @csrf
                                                @method('PUT')
                                                <select name="estado" class="h-8 rounded-full px-2.5 text-xs font-black ring-1 {{ $estadoClase }} border-0" aria-label="Estado de la comisión">
                                                    @foreach ($estados as $clave => [$texto])
                                                        <option value="{{ $clave }}" @selected($comision->estado === $clave)>{{ $texto }}</option>
                                                    @endforeach
                                                </select>
                                                <input type="date" name="fecha_vencimiento" value="{{ optional($comision->fecha_vencimiento)->format('Y-m-d') }}" class="h-8 rounded-lg border border-atlantia-rose/25 px-2 text-xs" aria-label="Fecha de vencimiento" title="Vence">
                                                <button class="h-8 rounded-lg bg-atlantia-wine px-3 text-xs font-black text-white hover:bg-atlantia-wine-700">Guardar</button>
                                            </form>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @if ($comisiones->hasPages())
                        <div class="border-t border-atlantia-rose/10 px-4 py-3">{{ $comisiones->withQueryString()->links() }}</div>
                    @endif
                @endif
            </article>

            {{-- Tiendas que más deben --}}
            <aside class="{{ $tarjeta }} self-start p-4">
                <h2 class="text-sm font-black">Mayor saldo</h2>
                <p class="text-xs text-atlantia-ink/55">Las tiendas con el cobro más alto{{ $periodo ? ' del mes' : '' }}.</p>
                <ol class="mt-3 space-y-2">
                    @forelse ($dashboard['top_vendedores'] as $top)
                        <li class="flex items-center gap-2.5">
                            <span class="grid h-6 w-6 shrink-0 place-items-center rounded-md bg-atlantia-blush text-[11px] font-black text-atlantia-wine">{{ $loop->iteration }}</span>
                            <span class="min-w-0 flex-1">
                                <span class="block truncate text-sm font-bold">{{ $top['vendor'] }}</span>
                                <span class="block text-[11px] text-atlantia-ink/50">{{ $top['periodo'] }} · {{ $estados[$top['estado']][0] ?? ucfirst($top['estado']) }}</span>
                            </span>
                            <span class="shrink-0 text-sm font-black text-atlantia-wine">Q {{ number_format($top['monto_total'], 2) }}</span>
                        </li>
                    @empty
                        <li class="rounded-lg bg-atlantia-blush/30 px-3 py-4 text-center text-xs text-atlantia-ink/55">Sin cobros todavía.</li>
                    @endforelse
                </ol>
                <p class="mt-4 border-t border-atlantia-rose/10 pt-3 text-[11px] leading-snug text-atlantia-ink/50">Al calcular un mes se toman las ventas cobradas de cada tienda aprobada y se suma la renta fija de su plan. Si ya existía, se actualiza.</p>
            </aside>
        </div>
    </section>
@endsection
