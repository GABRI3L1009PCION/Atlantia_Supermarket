@extends(auth()->user()?->isSuperAdmin() && request()->routeIs('admin.*') ? 'layouts.super-admin' : 'layouts.app')

@section('content')
    @php
        $estadoOptions = ['pendiente', 'confirmado', 'preparando', 'en_ruta', 'entregado', 'cancelado'];
        $estadoPagoOptions = ['pendiente', 'validando', 'pagado', 'rechazado', 'reembolsado'];
        $metodoPagoOptions = ['efectivo' => 'Efectivo', 'transferencia' => 'Transferencia', 'tarjeta' => 'Tarjeta'];
        $metrics = $pedidoMetrics ?? ['today' => 0, 'pending' => 0, 'delivered' => 0, 'paid' => 0];
        $estadoActual = (string) request('estado', '');

        $estados = [
            'pendiente' => ['Por confirmar', 'bg-amber-50 text-amber-800'],
            'confirmado' => ['Confirmado', 'bg-sky-50 text-sky-800'],
            'en_revision' => ['En revisión', 'bg-amber-50 text-amber-800'],
            'preparando' => ['Preparando', 'bg-violet-50 text-violet-800'],
            'listo_para_entrega' => ['Listo', 'bg-indigo-50 text-indigo-800'],
            'en_ruta' => ['En camino', 'bg-atlantia-blush text-atlantia-wine'],
            'entregado' => ['Entregado', 'bg-emerald-50 text-emerald-700'],
            'cancelado' => ['Cancelado', 'bg-slate-100 text-slate-600'],
            'rechazado' => ['Rechazado', 'bg-red-50 text-red-700'],
        ];
        $pagos = [
            'pagado' => ['Pagado', 'text-emerald-700'],
            'aprobado' => ['Pagado', 'text-emerald-700'],
            'pendiente' => ['Pago pendiente', 'text-amber-700'],
            'validando' => ['Validando pago', 'text-sky-700'],
            'rechazado' => ['Pago rechazado', 'text-red-700'],
            'reembolsado' => ['Reembolsado', 'text-slate-500'],
        ];
        $chips = ['' => 'Todos', 'pendiente' => 'Por confirmar', 'confirmado' => 'Confirmados', 'preparando' => 'Preparando', 'en_ruta' => 'En camino', 'entregado' => 'Entregados', 'cancelado' => 'Cancelados'];
        $control = 'h-10 rounded-lg border border-atlantia-rose/30 bg-white px-3 text-sm outline-none transition focus:border-atlantia-wine focus:ring-2 focus:ring-atlantia-rose/20';
    @endphp

    <section class="mx-auto max-w-7xl space-y-4 pb-24" x-data="{ seleccion: [], todos(e) { this.seleccion = e.target.checked ? [...document.querySelectorAll('[data-pedido-check]')].map(c => c.value) : [] } }">
        {{-- Encabezado --}}
        <header>
            <h1 class="text-3xl font-black tracking-tight text-atlantia-ink">Pedidos</h1>
            <p class="mt-1 text-sm text-atlantia-ink/60">Todos los pedidos de Atlantia Store y de las tiendas locales: pago, preparación y reparto.</p>
        </header>

        {{-- Resumen --}}
        <div class="grid grid-cols-2 gap-3 lg:grid-cols-4">
            @foreach ([
                ['Hoy', $metrics['today'], 'pedidos recibidos', null],
                ['Por atender', $metrics['pending'], 'sin entregar todavía', 'pendiente'],
                ['Entregados', $metrics['delivered'], 'en total', 'entregado'],
                ['Pagados', $metrics['paid'], 'con pago confirmado', null],
            ] as [$etiqueta, $valor, $nota, $filtro])
                <a href="{{ $filtro ? route('admin.pedidos.index', ['estado' => $filtro]) : route('admin.pedidos.index') }}" class="rounded-2xl border border-atlantia-rose/15 bg-white px-4 py-3 shadow-[0_8px_20px_rgba(59,6,12,0.04)] transition hover:border-atlantia-wine/30">
                    <p class="text-[11px] font-black uppercase tracking-wide text-atlantia-ink/50">{{ $etiqueta }}</p>
                    <p class="mt-0.5 text-2xl font-black text-atlantia-ink">{{ number_format($valor) }}</p>
                    <p class="text-xs text-atlantia-ink/50">{{ $nota }}</p>
                </a>
            @endforeach
        </div>

        <div class="overflow-hidden rounded-2xl border border-atlantia-rose/15 bg-white shadow-[0_10px_26px_rgba(59,6,12,0.05)]">
            {{-- Filtros --}}
            <form method="GET" class="space-y-3 border-b border-atlantia-rose/10 p-3">
                <div class="flex gap-1.5 overflow-x-auto">
                    @foreach ($chips as $valor => $texto)
                        <a href="{{ route('admin.pedidos.index', array_filter(['estado' => $valor, 'q' => request('q'), 'metodo_pago' => request('metodo_pago')])) }}"
                           @class(['shrink-0 rounded-lg border px-3 py-2 text-sm font-black transition', 'border-atlantia-wine bg-atlantia-wine text-white' => $estadoActual === (string) $valor, 'border-atlantia-rose/25 bg-white text-atlantia-wine hover:bg-atlantia-blush' => $estadoActual !== (string) $valor])>{{ $texto }}</a>
                    @endforeach
                </div>
                @if ($estadoActual !== '')
                    <input type="hidden" name="estado" value="{{ $estadoActual }}">
                @endif
                <div class="flex flex-col gap-2 sm:flex-row">
                    <div class="relative flex-1">
                        <span class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-atlantia-ink/40">
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M11 19a8 8 0 1 0 0-16 8 8 0 0 0 0 16ZM20.5 20.5l-3.8-3.8" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
                        </span>
                        <input type="search" name="q" value="{{ request('q') }}" placeholder="Buscar por número de pedido, cliente o tienda" aria-label="Buscar pedido" class="{{ $control }} w-full pl-9">
                    </div>
                    <select name="metodo_pago" aria-label="Método de pago" class="{{ $control }} sm:w-48" data-autosubmit>
                        <option value="">Cualquier pago</option>
                        @foreach ($metodoPagoOptions as $metodo => $texto)
                            <option value="{{ $metodo }}" @selected(request('metodo_pago') === $metodo)>{{ $texto }}</option>
                        @endforeach
                    </select>
                    <button type="submit" class="h-10 shrink-0 rounded-lg border border-atlantia-rose/30 px-4 text-sm font-black text-atlantia-wine hover:bg-atlantia-blush">Buscar</button>
                </div>
            </form>

            {{-- Lista --}}
            <div class="overflow-x-auto">
                <table class="w-full min-w-[900px] text-sm">
                    <thead>
                        <tr class="border-b border-atlantia-rose/10 bg-atlantia-cream/50 text-left text-[11px] font-black uppercase tracking-wide text-atlantia-ink/50">
                            <th class="w-10 px-4 py-2.5"><input type="checkbox" class="h-4 w-4 rounded border-atlantia-rose/40 text-atlantia-wine focus:ring-atlantia-wine" aria-label="Seleccionar todos" @change="todos($event)" :checked="seleccion.length > 0 && seleccion.length === document.querySelectorAll('[data-pedido-check]').length"></th>
                            <th class="px-3 py-2.5">Pedido</th>
                            <th class="px-3 py-2.5">Cliente</th>
                            <th class="px-3 py-2.5">Tienda</th>
                            <th class="px-3 py-2.5">Estado</th>
                            <th class="px-3 py-2.5">Repartidor</th>
                            <th class="px-3 py-2.5 text-right">Total</th>
                            <th class="px-4 py-2.5"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-atlantia-rose/10">
                        @forelse ($pedidos as $pedido)
                            @php
                                [$estadoTexto, $estadoClase] = $estados[$pedido->estadoValor()] ?? [ucfirst(str_replace('_', ' ', $pedido->estadoValor())), 'bg-slate-100 text-slate-600'];
                                [$pagoTexto, $pagoClase] = $pagos[$pedido->estadoPagoValor()] ?? [ucfirst($pedido->estadoPagoValor()), 'text-atlantia-ink/55'];
                                $metodo = $pedido->metodo_pago instanceof \BackedEnum ? $pedido->metodo_pago->value : (string) $pedido->metodo_pago;
                            @endphp
                            <tr class="transition hover:bg-atlantia-cream/60" :class="seleccion.includes('{{ $pedido->uuid }}') && 'bg-atlantia-blush/40'">
                                <td class="px-4 py-3">
                                    <input form="orders-batch-form" type="checkbox" name="pedidos[]" value="{{ $pedido->uuid }}" x-model="seleccion" data-pedido-check aria-label="Seleccionar {{ $pedido->numero_pedido }}" class="h-4 w-4 rounded border-atlantia-rose/40 text-atlantia-wine focus:ring-atlantia-wine">
                                </td>
                                <td class="px-3 py-3">
                                    <a href="{{ route('admin.pedidos.show', $pedido->uuid) }}" class="font-black text-atlantia-ink hover:text-atlantia-wine">{{ $pedido->numero_pedido }}</a>
                                    <p class="text-xs text-atlantia-ink/50" title="{{ $pedido->created_at?->format('d/m/Y H:i') }}">{{ $pedido->created_at?->diffForHumans() }}</p>
                                </td>
                                <td class="max-w-[10rem] truncate px-3 py-3 text-atlantia-ink/80">{{ $pedido->cliente?->name ?? 'Sin cliente' }}</td>
                                <td class="max-w-[11rem] px-3 py-3">
                                    @if ($pedido->vendor)
                                        <span class="block truncate text-atlantia-ink/80">{{ $pedido->vendor->business_name }}</span>
                                    @else
                                        <span class="rounded-full bg-atlantia-cream px-2 py-0.5 text-xs font-bold text-atlantia-ink/60" title="Pedido de varias tiendas; cada tienda tiene su propio pedido">Varias tiendas</span>
                                    @endif
                                </td>
                                <td class="px-3 py-3">
                                    <span class="rounded-full px-2.5 py-1 text-xs font-black {{ $estadoClase }}">{{ $estadoTexto }}</span>
                                    <p class="mt-1 text-[11px] font-bold {{ $pagoClase }}">{{ $pagoTexto }}</p>
                                </td>
                                <td class="max-w-[9rem] truncate px-3 py-3 text-xs text-atlantia-ink/65">{{ $pedido->deliveryRoute?->repartidor?->name ?? '—' }}</td>
                                <td class="px-3 py-3 text-right">
                                    <p class="font-black text-atlantia-ink">Q {{ number_format((float) $pedido->total, 2) }}</p>
                                    <p class="text-[11px] text-atlantia-ink/50">{{ $metodoPagoOptions[$metodo] ?? ucfirst($metodo) }}</p>
                                </td>
                                <td class="px-4 py-3 text-right">
                                    <a href="{{ route('admin.pedidos.show', $pedido->uuid) }}" class="rounded-lg border border-atlantia-rose/30 px-3 py-1.5 text-xs font-black text-atlantia-wine transition hover:bg-atlantia-blush">Gestionar</a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="px-4 py-12 text-center">
                                    <p class="font-black text-atlantia-ink">{{ request('q') || $estadoActual !== '' || request('metodo_pago') ? 'No hay pedidos con ese filtro.' : 'Todavía no hay pedidos.' }}</p>
                                    <p class="mt-1 text-sm text-atlantia-ink/55">Cuando un cliente compre, el pedido aparecerá aquí.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="flex flex-col items-center justify-between gap-2 border-t border-atlantia-rose/10 px-4 py-3 sm:flex-row">
                <p class="text-xs font-semibold text-atlantia-ink/55">Mostrando {{ $pedidos->firstItem() ?? 0 }}-{{ $pedidos->lastItem() ?? 0 }} de {{ $pedidos->total() }} pedidos</p>
                <div>{{ $pedidos->withQueryString()->links() }}</div>
            </div>
        </div>

        {{-- Barra de cambio en lote: aparece al seleccionar pedidos --}}
        <form method="POST" action="{{ route('admin.pedidos.batch-update') }}" id="orders-batch-form"
              class="fixed inset-x-3 bottom-3 z-40 mx-auto hidden max-w-5xl rounded-2xl border border-atlantia-rose/20 bg-white p-3 shadow-[0_18px_44px_rgba(59,6,12,0.22)] xl:left-[19rem]"
              :class="{ 'hidden': seleccion.length === 0 }"
              data-confirm="¿Cambiar el estado de los pedidos seleccionados?">
            @csrf
            <div class="flex flex-col gap-2 lg:flex-row lg:items-center">
                <p class="shrink-0 text-sm font-black text-atlantia-ink"><span x-text="seleccion.length"></span> <span x-text="seleccion.length === 1 ? 'seleccionado' : 'seleccionados'"></span></p>
                <select name="estado" required aria-label="Nuevo estado" class="{{ $control }} lg:w-44">
                    <option value="" disabled selected>Nuevo estado</option>
                    @foreach ($estadoOptions as $estado)
                        <option value="{{ $estado }}">{{ $estados[$estado][0] ?? ucfirst($estado) }}</option>
                    @endforeach
                </select>
                <select name="estado_pago" required aria-label="Estado del pago" class="{{ $control }} lg:w-44">
                    <option value="" disabled selected>Estado del pago</option>
                    @foreach ($estadoPagoOptions as $estadoPago)
                        <option value="{{ $estadoPago }}">{{ ucfirst($estadoPago) }}</option>
                    @endforeach
                </select>
                <input type="text" name="notas_historial" maxlength="1000" placeholder="Nota para el historial (opcional)" class="{{ $control }} min-w-0 flex-1">
                <div class="flex shrink-0 gap-2">
                    <button type="button" class="h-10 rounded-lg px-3 text-sm font-bold text-atlantia-ink/60 hover:bg-atlantia-cream" @click="seleccion = []">Cancelar</button>
                    <button type="submit" class="h-10 rounded-lg bg-atlantia-wine px-4 text-sm font-black text-white hover:bg-atlantia-wine-700">Aplicar cambio</button>
                </div>
            </div>
        </form>
    </section>
@endsection
