@extends('layouts.app')

@section('content')
    @php
        $pestanas = \App\Services\Pedidos\PedidoVendedorService::PESTANAS;
        $filtros = [
            'q' => (string) request('q', ''),
            'estado' => (string) request('estado', ''),
            'desde' => (string) request('desde', ''),
            'hasta' => (string) request('hasta', ''),
        ];
        $hayFiltros = $filtros['q'] !== '' || $filtros['desde'] !== '' || $filtros['hasta'] !== '';
        $urlPestana = fn (?string $clave): string => route('vendedor.pedidos.index', array_filter([...$filtros, 'estado' => $clave]));
        $estados = [
            'pendiente' => ['Por confirmar', 'bg-amber-50 text-amber-800 ring-amber-200'],
            'en_revision' => ['En revisión', 'bg-amber-50 text-amber-800 ring-amber-200'],
            'confirmado' => ['Confirmado', 'bg-sky-50 text-sky-700 ring-sky-200'],
            'preparando' => ['En preparación', 'bg-indigo-50 text-indigo-700 ring-indigo-200'],
            'listo_para_entrega' => ['Listo', 'bg-emerald-50 text-emerald-700 ring-emerald-200'],
            'en_ruta' => ['En camino', 'bg-teal-50 text-teal-700 ring-teal-200'],
            'entregado' => ['Entregado', 'bg-slate-100 text-slate-700 ring-slate-200'],
            'cancelado' => ['Cancelado', 'bg-rose-50 text-rose-700 ring-rose-200'],
            'rechazado' => ['Rechazado', 'bg-rose-50 text-rose-700 ring-rose-200'],
        ];
        $pagos = [
            'pagado' => ['Pagado', 'text-emerald-700'],
            'aprobado' => ['Pagado', 'text-emerald-700'],
            'pendiente' => ['Por cobrar', 'text-amber-700'],
            'validando' => ['Validando', 'text-amber-700'],
            'rechazado' => ['Rechazado', 'text-rose-700'],
            'anulado' => ['Anulado', 'text-rose-700'],
            'reembolsado' => ['Reembolsado', 'text-slate-600'],
        ];
        $metodos = ['tarjeta' => 'Tarjeta', 'transferencia' => 'Transferencia', 'efectivo' => 'Efectivo'];
        $entregas = ['domicilio' => 'A domicilio', 'recoger' => 'Recoge en tienda', 'programado' => 'Programado'];
        $nombreTienda = auth()->user()->vendor?->business_name ?? 'Mi tienda';
    @endphp

    <section class="mx-auto max-w-[1280px] space-y-4 pb-10">
        <div class="flex flex-wrap items-end justify-between gap-3">
            <div>
                <h1 class="text-2xl font-black text-atlantia-ink">Pedidos</h1>
                <p class="mt-0.5 text-sm text-atlantia-ink/60">{{ $nombreTienda }} · {{ number_format($dashboard['total']) }} {{ $dashboard['total'] === 1 ? 'pedido' : 'pedidos' }} en total</p>
            </div>
            <dl class="grid grid-cols-3 gap-2 text-xs sm:w-auto" data-resumen-pedidos>
                <div class="rounded-xl border border-atlantia-rose/15 bg-white px-3 py-2">
                    <dt class="text-atlantia-ink/55">Hoy</dt>
                    <dd class="mt-0.5 font-black text-atlantia-ink">{{ $dashboard['hoy']['pedidos'] }} {{ $dashboard['hoy']['pedidos'] === 1 ? 'pedido' : 'pedidos' }} · Q{{ number_format($dashboard['hoy']['ventas'], 2) }}</dd>
                </div>
                <div class="rounded-xl border border-atlantia-rose/15 bg-white px-3 py-2" title="Desde que entra el pedido hasta que lo marcas listo (últimos 30 días)">
                    <dt class="text-atlantia-ink/55">Tiempo de preparación</dt>
                    <dd class="mt-0.5 font-black text-atlantia-ink">{{ $dashboard['tiempo_preparacion'] === null ? 'Aún sin datos' : $dashboard['tiempo_preparacion'].' min promedio' }}</dd>
                </div>
                <div class="rounded-xl border border-atlantia-rose/15 bg-white px-3 py-2" title="Pedidos entregados de todos los que terminaron (últimos 30 días)">
                    <dt class="text-atlantia-ink/55">Entregados</dt>
                    <dd class="mt-0.5 font-black text-atlantia-ink">{{ $dashboard['cumplimiento'] === null ? 'Aún sin datos' : $dashboard['cumplimiento'].'% de '.$dashboard['muestra'] }}</dd>
                </div>
            </dl>
        </div>

        <nav class="-mx-1 flex gap-1.5 overflow-x-auto px-1 pb-1 [scrollbar-width:none] [&::-webkit-scrollbar]:hidden" aria-label="Filtrar por estado">
            <a href="{{ $urlPestana(null) }}" @class([
                'inline-flex h-9 shrink-0 items-center gap-2 rounded-full px-4 text-sm font-bold transition',
                'bg-atlantia-wine text-white' => $filtros['estado'] === '',
                'border border-atlantia-rose/20 bg-white text-atlantia-ink/70 hover:text-atlantia-wine' => $filtros['estado'] !== '',
            ])>Todos <span class="text-xs opacity-70">{{ $dashboard['total'] }}</span></a>
            @foreach ($pestanas as $clave => [$texto])
                @php $cuenta = $dashboard['conteos'][$clave] ?? 0; $activa = $filtros['estado'] === $clave; @endphp
                <a href="{{ $urlPestana($clave) }}" @class([
                    'inline-flex h-9 shrink-0 items-center gap-2 rounded-full px-4 text-sm font-bold transition',
                    'bg-atlantia-wine text-white' => $activa,
                    'border border-atlantia-rose/20 bg-white text-atlantia-ink/70 hover:text-atlantia-wine' => ! $activa,
                ]) data-pestana="{{ $clave }}">
                    {{ $texto }}
                    @if ($clave === 'por_confirmar' && $cuenta > 0 && ! $activa)
                        <span class="grid h-5 min-w-5 place-items-center rounded-full bg-amber-500 px-1.5 text-[11px] font-black text-white">{{ $cuenta }}</span>
                    @else
                        <span class="text-xs opacity-70">{{ $cuenta }}</span>
                    @endif
                </a>
            @endforeach
        </nav>

        <article class="overflow-hidden rounded-xl border border-atlantia-rose/15 bg-white shadow-[0_12px_32px_rgba(42,16,24,0.05)]">
            <form method="GET" action="{{ route('vendedor.pedidos.index') }}" class="flex flex-wrap items-center gap-2 border-b border-atlantia-rose/10 p-3" autocomplete="off">
                @if ($filtros['estado'] !== '') <input type="hidden" name="estado" value="{{ $filtros['estado'] }}"> @endif
                <label class="relative min-w-[14rem] flex-1">
                    <span class="sr-only">Buscar pedido</span>
                    <svg class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-atlantia-ink/40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>
                    <input type="search" name="q" value="{{ $filtros['q'] }}" placeholder="Número de pedido o nombre del cliente" class="h-10 w-full rounded-lg border border-atlantia-rose/25 pl-9 pr-3 text-sm outline-none focus:border-atlantia-wine focus:ring-2 focus:ring-atlantia-blush">
                </label>
                <label class="flex items-center gap-1.5 text-xs font-bold text-atlantia-ink/60">
                    Del
                    <input type="date" name="desde" value="{{ $filtros['desde'] }}" class="h-10 rounded-lg border border-atlantia-rose/25 px-2 text-sm text-atlantia-ink outline-none focus:border-atlantia-wine">
                </label>
                <label class="flex items-center gap-1.5 text-xs font-bold text-atlantia-ink/60">
                    al
                    <input type="date" name="hasta" value="{{ $filtros['hasta'] }}" class="h-10 rounded-lg border border-atlantia-rose/25 px-2 text-sm text-atlantia-ink outline-none focus:border-atlantia-wine">
                </label>
                <button type="submit" class="h-10 rounded-lg bg-atlantia-wine px-4 text-sm font-bold text-white hover:bg-atlantia-wine-700">Buscar</button>
                @if ($hayFiltros)
                    <a href="{{ route('vendedor.pedidos.index', array_filter(['estado' => $filtros['estado']])) }}" class="text-sm font-bold text-atlantia-wine hover:underline">Quitar filtros</a>
                @endif
            </form>

            @if ($pedidos->isEmpty())
                <div class="px-6 py-14 text-center">
                    <span class="mx-auto grid h-12 w-12 place-items-center rounded-full bg-atlantia-blush text-atlantia-wine">
                        <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M7 8V6a5 5 0 0 1 10 0v2"/><path d="M5 8h14l-1 11H6L5 8Z"/></svg>
                    </span>
                    @if ($dashboard['total'] === 0)
                        <h2 class="mt-3 text-base font-black text-atlantia-ink">Aún no tienes pedidos</h2>
                        <p class="mx-auto mt-1 max-w-md text-sm text-atlantia-ink/60">Cuando un cliente compre en tu tienda, el pedido aparecerá aquí y sonará un aviso. Deja esta página abierta mientras atiendes.</p>
                    @else
                        <h2 class="mt-3 text-base font-black text-atlantia-ink">No hay pedidos aquí</h2>
                        <p class="mt-1 text-sm text-atlantia-ink/60">Prueba con otra pestaña o quita los filtros.</p>
                    @endif
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead>
                            <tr class="border-b border-atlantia-rose/10 bg-[#fbf8f7] text-left text-xs font-bold text-atlantia-ink/55">
                                <th class="px-4 py-2.5">Pedido</th>
                                <th class="px-4 py-2.5">Cliente</th>
                                <th class="px-4 py-2.5">Productos</th>
                                <th class="px-4 py-2.5">Total</th>
                                <th class="px-4 py-2.5">Pago</th>
                                <th class="px-4 py-2.5">Estado</th>
                                <th class="px-4 py-2.5 text-right">Siguiente paso</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-atlantia-rose/10">
                            @foreach ($pedidos as $pedido)
                                @php
                                    $estado = $pedido->estadoValor();
                                    $pago = $pedido->estadoPagoValor();
                                    $siguientes = \App\Services\Pedidos\PedidoVendedorService::siguientesEstados($pedido);
                                    $principal = collect($siguientes)->except('cancelado');
                                    $unidades = (int) $pedido->items->sum('cantidad');
                                    $nombres = $pedido->items->take(2)->map(fn ($i) => $i->producto_nombre_snapshot.($i->cantidad > 1 ? ' ×'.$i->cantidad : ''))->join(', ');
                                @endphp
                                <tr class="align-top hover:bg-[#fdfafa]" data-pedido="{{ $pedido->numero_pedido }}">
                                    <td class="px-4 py-3">
                                        <a href="{{ route('vendedor.pedidos.show', $pedido) }}" class="font-black text-atlantia-ink hover:text-atlantia-wine">#{{ $pedido->numero_pedido }}</a>
                                        <p class="text-xs text-atlantia-ink/50" title="{{ $pedido->created_at?->format('d/m/Y h:i a') }}">{{ $pedido->created_at?->isToday() ? 'Hoy '.$pedido->created_at->format('h:i a') : $pedido->created_at?->format('d/m/Y h:i a') }}</p>
                                    </td>
                                    <td class="px-4 py-3">
                                        <p class="font-bold text-atlantia-ink">{{ $pedido->cliente?->name ?? 'Cliente' }}</p>
                                        <p class="text-xs text-atlantia-ink/55">{{ $entregas[$pedido->tipo_entrega] ?? 'A domicilio' }}</p>
                                    </td>
                                    <td class="max-w-[16rem] px-4 py-3">
                                        <p class="font-bold text-atlantia-ink">{{ $unidades }} {{ $unidades === 1 ? 'unidad' : 'unidades' }}</p>
                                        <p class="truncate text-xs text-atlantia-ink/55" title="{{ $pedido->items->map(fn ($i) => $i->producto_nombre_snapshot.' ×'.$i->cantidad)->join(', ') }}">{{ $nombres }}{{ $pedido->items->count() > 2 ? ' y '.($pedido->items->count() - 2).' más' : '' }}</p>
                                    </td>
                                    <td class="px-4 py-3 font-black text-atlantia-ink">Q{{ number_format((float) $pedido->total, 2) }}</td>
                                    <td class="px-4 py-3">
                                        <p class="text-atlantia-ink/75">{{ $metodos[$pedido->metodoPagoValor()] ?? ucfirst($pedido->metodoPagoValor()) }}</p>
                                        <p class="text-xs font-bold {{ $pagos[$pago][1] ?? 'text-atlantia-ink/55' }}">{{ $pagos[$pago][0] ?? ucfirst($pago) }}</p>
                                    </td>
                                    <td class="px-4 py-3">
                                        <span class="inline-flex whitespace-nowrap rounded-full px-2.5 py-1 text-xs font-bold ring-1 {{ $estados[$estado][1] ?? 'bg-slate-100 text-slate-700 ring-slate-200' }}">{{ $estados[$estado][0] ?? ucfirst($estado) }}</span>
                                    </td>
                                    <td class="px-4 py-3">
                                        <div class="flex flex-wrap items-center justify-end gap-2">
                                            @if ($principal->isNotEmpty())
                                                <form method="POST" action="{{ route('vendedor.pedidos.estado', $pedido) }}">
                                                    @csrf
                                                    @method('PATCH')
                                                    <input type="hidden" name="estado" value="{{ $principal->keys()->first() }}">
                                                    <button type="submit" class="h-9 whitespace-nowrap rounded-lg bg-atlantia-wine px-3 text-xs font-bold text-white hover:bg-atlantia-wine-700">{{ $principal->first() }}</button>
                                                </form>
                                            @else
                                                <span class="text-xs text-atlantia-ink/50">{{ match ($estado) { 'listo_para_entrega' => 'Esperando repartidor', 'en_ruta' => 'Va en camino', default => '' } }}</span>
                                            @endif
                                            <a href="{{ route('vendedor.pedidos.show', $pedido) }}" class="h-9 rounded-lg border border-atlantia-rose/25 px-3 text-xs font-bold leading-9 text-atlantia-wine hover:bg-atlantia-blush">Ver</a>
                                            @if (isset($siguientes['cancelado']))
                                                <form method="POST" action="{{ route('vendedor.pedidos.estado', $pedido) }}" data-confirm="¿Cancelar el pedido #{{ $pedido->numero_pedido }}? El cliente recibirá un aviso.">
                                                    @csrf
                                                    @method('PATCH')
                                                    <input type="hidden" name="estado" value="cancelado">
                                                    <button type="submit" class="grid h-9 w-9 place-items-center rounded-lg text-atlantia-ink/40 hover:bg-rose-50 hover:text-rose-700" aria-label="Cancelar pedido #{{ $pedido->numero_pedido }}" title="Cancelar pedido">
                                                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="M6 6l12 12M18 6 6 18"/></svg>
                                                    </button>
                                                </form>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @if ($pedidos->hasPages())
                    <div class="flex flex-col gap-3 border-t border-atlantia-rose/10 px-4 py-3 text-sm text-atlantia-ink/55 sm:flex-row sm:items-center sm:justify-between">
                        <p>{{ $pedidos->firstItem() }}–{{ $pedidos->lastItem() }} de {{ $pedidos->total() }}</p>
                        {{ $pedidos->links() }}
                    </div>
                @endif
            @endif
        </article>
    </section>


    {{-- Alerta de pedidos nuevos: sonido y aviso con opcion de imprimir la comanda. --}}
    <div id="alerta-pedido-nuevo" class="fixed bottom-6 right-6 z-[80] hidden w-80 rounded-2xl bg-white p-4 shadow-2xl ring-2 ring-atlantia-wine" role="alert" aria-live="assertive">
        <p class="text-xs font-black uppercase tracking-wide text-atlantia-wine">Pedido nuevo</p>
        <p class="mt-1 text-lg font-black text-atlantia-ink" data-alerta-numero></p>
        <div class="mt-3 grid grid-cols-2 gap-2">
            <a data-alerta-ver class="rounded-lg bg-atlantia-wine px-3 py-2 text-center text-sm font-black text-white">Ver pedido</a>
            <a data-alerta-comanda target="_blank" rel="noopener" class="rounded-lg border border-atlantia-rose/30 px-3 py-2 text-center text-sm font-black text-atlantia-wine">Imprimir</a>
        </div>
        <button type="button" data-alerta-cerrar class="absolute right-3 top-2 text-lg font-black text-atlantia-ink/40" aria-label="Cerrar aviso">&times;</button>
    </div>
    <script @nonce>
        (() => {
            const url = @json(route('vendedor.pedidos.nuevos'));
            const caja = document.getElementById('alerta-pedido-nuevo');
            let ultimoId = null;
            let audio = null;

            const sonar = () => {
                try {
                    audio = audio || new (window.AudioContext || window.webkitAudioContext)();
                    [0, 0.35, 0.7].forEach((retraso) => {
                        const osc = audio.createOscillator();
                        const gain = audio.createGain();
                        osc.frequency.value = 880;
                        gain.gain.setValueAtTime(0.25, audio.currentTime + retraso);
                        gain.gain.exponentialRampToValueAtTime(0.001, audio.currentTime + retraso + 0.3);
                        osc.connect(gain).connect(audio.destination);
                        osc.start(audio.currentTime + retraso);
                        osc.stop(audio.currentTime + retraso + 0.3);
                    });
                } catch (_) { /* el navegador bloqueo el audio hasta que haya interaccion */ }
            };
            /* Los navegadores solo permiten sonido despues de una interaccion del usuario. */
            document.addEventListener('click', () => { try { audio = audio || new (window.AudioContext || window.webkitAudioContext)(); audio.resume(); } catch (_) {} }, { once: true });
            caja.querySelector('[data-alerta-cerrar]').addEventListener('click', () => caja.classList.add('hidden'));

            const revisar = async () => {
                try {
                    const respuesta = await fetch(url, { headers: { Accept: 'application/json' } });
                    if (!respuesta.ok) return;
                    const { ultimo, alerta_sonora: alertaSonora } = await respuesta.json();
                    if (!ultimo) return;
                    if (ultimoId !== null && ultimo.id > ultimoId) {
                        caja.querySelector('[data-alerta-numero]').textContent = `${ultimo.numero} · Q ${ultimo.total.toFixed(2)}`;
                        caja.querySelector('[data-alerta-ver]').href = ultimo.url;
                        caja.querySelector('[data-alerta-comanda]').href = ultimo.comanda;
                        caja.classList.remove('hidden');
                        if (alertaSonora) sonar();
                        document.title = `(Nuevo) ${document.title.replace(/^\(Nuevo\) /, '')}`;
                    }
                    ultimoId = Math.max(ultimoId ?? 0, ultimo.id);
                } catch (_) { /* sin conexion: se reintenta */ }
            };
            revisar();
            setInterval(revisar, 20000);
        })();
    </script>
@endsection
