@extends('layouts.app')

@section('content')
    @php
        $filters = [
            'q' => (string) request('q', ''),
            'estado' => (string) request('estado', ''),
            'desde' => (string) request('desde', ''),
            'hasta' => (string) request('hasta', ''),
        ];
        $statusLabels = [
            'pendiente' => 'Pendiente',
            'confirmado' => 'Confirmado',
            'preparando' => 'Preparando',
            'listo_para_entrega' => 'Listo para entrega',
            'cancelado' => 'Cancelado',
            'rechazado' => 'Rechazado',
            'entregado' => 'Entregado',
        ];
        $statusClasses = [
            'pendiente' => 'bg-sky-50 text-sky-700 ring-sky-200',
            'confirmado' => 'bg-emerald-50 text-emerald-700 ring-emerald-200',
            'preparando' => 'bg-amber-50 text-amber-700 ring-amber-200',
            'listo_para_entrega' => 'bg-emerald-50 text-emerald-700 ring-emerald-200',
            'cancelado' => 'bg-rose-50 text-rose-700 ring-rose-200',
            'rechazado' => 'bg-rose-50 text-rose-700 ring-rose-200',
            'entregado' => 'bg-slate-100 text-slate-700 ring-slate-200',
        ];
        $payClasses = [
            'pagado' => 'bg-emerald-50 text-emerald-700 ring-emerald-200',
            'aprobado' => 'bg-emerald-50 text-emerald-700 ring-emerald-200',
            'pendiente' => 'bg-sky-50 text-sky-700 ring-sky-200',
            'validando' => 'bg-amber-50 text-amber-700 ring-amber-200',
            'rechazado' => 'bg-rose-50 text-rose-700 ring-rose-200',
        ];
        $metricCards = [
            ['label' => 'Pedidos hoy', 'value' => $dashboard['metrics']['pedidos_hoy'], 'hint' => '+2 vs ayer', 'tone' => 'rose', 'icon' => 'bag'],
            ['label' => 'Pendientes', 'value' => $dashboard['metrics']['pendientes'], 'hint' => $dashboard['percentages']['pendientes'] . '% del total', 'tone' => 'orange', 'icon' => 'clock'],
            ['label' => 'En preparacion', 'value' => $dashboard['metrics']['preparando'], 'hint' => $dashboard['percentages']['preparando'] . '% del total', 'tone' => 'blue', 'icon' => 'box'],
            ['label' => 'Listos para entrega', 'value' => $dashboard['metrics']['listos'], 'hint' => $dashboard['percentages']['listos'] . '% del total', 'tone' => 'green', 'icon' => 'bike'],
        ];
        $tones = [
            'rose' => 'bg-atlantia-blush text-atlantia-wine',
            'orange' => 'bg-orange-50 text-orange-600',
            'blue' => 'bg-sky-50 text-sky-600',
            'green' => 'bg-emerald-50 text-emerald-600',
        ];
        $icons = [
            'bag' => '<path d="M7 8V6a5 5 0 0 1 10 0v2"/><path d="M5 8h14l-1 11H6L5 8Z"/>',
            'clock' => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>',
            'box' => '<path d="m12 3 8 4.5-8 4.5-8-4.5L12 3Z"/><path d="M4 7.5v9L12 21l8-4.5v-9"/>',
            'bike' => '<circle cx="6" cy="17" r="3"/><circle cx="18" cy="17" r="3"/><path d="M8 17l4-8 3 8"/><path d="M12 9h4"/><path d="M10 6h3"/>',
        ];
    @endphp

    <section class="mx-auto max-w-[1280px] space-y-5 pb-10">
        <div>
            <p class="text-xs font-black uppercase tracking-[0.22em] text-atlantia-rose">Atlantia Supermarket</p>
            <h1 class="mt-2 text-3xl font-black leading-tight text-atlantia-ink sm:text-4xl">Pedidos recibidos</h1>
            <p class="mt-2 max-w-3xl text-sm leading-6 text-atlantia-ink/62">
                Gestiona las ordenes asignadas a tu tienda, revisa pagos y actualiza el estado operativo.
            </p>
        </div>

        <div class="grid gap-5 xl:grid-cols-[1fr_300px]">
            <div class="space-y-4">
                <article class="rounded-lg border border-atlantia-rose/15 bg-white p-4 shadow-[0_12px_32px_rgba(42,16,24,0.05)]">
                    <form method="GET" action="{{ route('vendedor.pedidos.index') }}" class="grid gap-3 lg:grid-cols-[1fr_210px_230px_120px]" autocomplete="off">
                        <div class="relative">
                            <input type="search" name="q" value="{{ $filters['q'] }}" placeholder="Buscar por N. de pedido o cliente" class="w-full rounded-lg border border-atlantia-rose/25 bg-white px-4 py-3 pl-11 text-sm outline-none transition focus:border-atlantia-wine focus:ring-2 focus:ring-atlantia-rose/20">
                            <svg class="absolute left-4 top-1/2 h-4 w-4 -translate-y-1/2 text-atlantia-ink/40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>
                        </div>
                        <select name="estado" class="rounded-lg border border-atlantia-rose/25 bg-white px-4 py-3 text-sm outline-none transition focus:border-atlantia-wine focus:ring-2 focus:ring-atlantia-rose/20">
                            <option value="">Todos los estados</option>
                            @foreach ($statusLabels as $value => $label)
                                <option value="{{ $value }}" @selected($filters['estado'] === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                        <div class="grid grid-cols-2 gap-2">
                            <input type="date" name="desde" value="{{ $filters['desde'] }}" class="rounded-lg border border-atlantia-rose/25 bg-white px-3 py-3 text-sm outline-none transition focus:border-atlantia-wine focus:ring-2 focus:ring-atlantia-rose/20">
                            <input type="date" name="hasta" value="{{ $filters['hasta'] }}" class="rounded-lg border border-atlantia-rose/25 bg-white px-3 py-3 text-sm outline-none transition focus:border-atlantia-wine focus:ring-2 focus:ring-atlantia-rose/20">
                        </div>
                        <button type="submit" class="inline-flex items-center justify-center gap-2 rounded-lg bg-atlantia-wine px-5 py-3 text-sm font-black text-white shadow-sm transition hover:bg-atlantia-wine-700">
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M21 12a9 9 0 1 1-3-6.7"/><path d="M21 3v6h-6"/></svg>
                            Actualizar
                        </button>
                    </form>
                </article>

                <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                    @foreach ($metricCards as $card)
                        <article class="rounded-lg border border-atlantia-rose/15 bg-white p-4 shadow-[0_12px_32px_rgba(42,16,24,0.05)]">
                            <div class="flex items-center gap-4">
                                <span class="{{ $tones[$card['tone']] }} grid h-12 w-12 shrink-0 place-items-center rounded-full">
                                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">{!! $icons[$card['icon']] !!}</svg>
                                </span>
                                <div>
                                    <p class="text-xs text-atlantia-ink/60">{{ $card['label'] }}</p>
                                    <p class="mt-1 text-2xl font-black leading-none text-atlantia-ink">{{ $card['value'] }}</p>
                                    <p class="mt-1 text-xs text-emerald-700">{{ $card['hint'] }}</p>
                                </div>
                            </div>
                        </article>
                    @endforeach
                </div>

                <article class="overflow-hidden rounded-lg border border-atlantia-rose/15 bg-white shadow-[0_12px_32px_rgba(42,16,24,0.05)]">
                    <div class="overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <thead>
                                <tr class="border-b border-atlantia-rose/15 bg-atlantia-cream/40 text-left text-xs font-black text-atlantia-ink/60">
                                    <th class="px-4 py-3">Pedido</th>
                                    <th class="px-4 py-3">Cliente</th>
                                    <th class="px-4 py-3">Fecha</th>
                                    <th class="px-4 py-3">Total</th>
                                    <th class="px-4 py-3">Pago</th>
                                    <th class="px-4 py-3">Estado</th>
                                    <th class="px-4 py-3 text-right">Acciones</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-atlantia-rose/12">
                                @forelse ($pedidos as $pedido)
                                    @php
                                        $estado = $pedido->estadoValor();
                                        $pago = $pedido->estadoPagoValor();
                                        $payment = $pedido->payments->first();
                                    @endphp
                                    <tr class="hover:bg-atlantia-cream/35">
                                        <td class="px-4 py-3 font-black text-atlantia-ink">#{{ $pedido->numero_pedido }}</td>
                                        <td class="px-4 py-3">
                                            <p class="font-bold text-atlantia-ink">{{ $pedido->cliente?->name ?? 'Cliente' }}</p>
                                            <p class="text-xs text-atlantia-ink/50">{{ $pedido->cliente?->phone ?? $pedido->cliente?->email }}</p>
                                        </td>
                                        <td class="px-4 py-3 text-atlantia-ink/70">
                                            <p>{{ $pedido->created_at?->format('d/m/Y') }}</p>
                                            <p class="text-xs text-atlantia-ink/50">{{ $pedido->created_at?->format('h:i a') }}</p>
                                        </td>
                                        <td class="px-4 py-3 font-black text-atlantia-ink">Q{{ number_format((float) $pedido->total, 2) }}</td>
                                        <td class="px-4 py-3">
                                            <p class="text-xs text-atlantia-ink/65">{{ ucfirst(str_replace('_', ' ', $pedido->metodoPagoValor())) }} {{ $payment?->referencia_bancaria ? '**** ' . substr($payment->referencia_bancaria, -4) : '' }}</p>
                                            <span class="{{ $payClasses[$pago] ?? 'bg-slate-100 text-slate-700 ring-slate-200' }} inline-flex rounded-full px-2 py-0.5 text-[10px] font-black ring-1">{{ ucfirst($pago) }}</span>
                                        </td>
                                        <td class="px-4 py-3">
                                            <span class="{{ $statusClasses[$estado] ?? 'bg-slate-100 text-slate-700 ring-slate-200' }} inline-flex rounded-full px-3 py-1 text-xs font-black ring-1">{{ $statusLabels[$estado] ?? ucfirst($estado) }}</span>
                                        </td>
                                        <td class="px-4 py-3">
                                            <div class="flex justify-end gap-2">
                                                <a href="{{ route('vendedor.pedidos.show', $pedido) }}" class="rounded-lg border border-atlantia-rose/30 bg-white px-3 py-2 text-xs font-black text-atlantia-wine transition hover:bg-atlantia-blush">Ver detalle</a>
                                                <form method="POST" action="{{ route('vendedor.pedidos.estado', $pedido) }}" class="flex gap-1">
                                                    @csrf
                                                    @method('PATCH')
                                                    <select name="estado" class="rounded-lg border border-atlantia-rose/30 bg-white px-2 py-2 text-xs font-black text-atlantia-wine outline-none">
                                                        @foreach (['confirmado' => 'Confirmar', 'preparando' => 'Preparar', 'listo_para_entrega' => 'Listo', 'cancelado' => 'Cancelar'] as $value => $label)
                                                            <option value="{{ $value }}" @selected($estado === $value)>{{ $label }}</option>
                                                        @endforeach
                                                    </select>
                                                    <button class="rounded-lg border border-atlantia-rose/30 bg-white px-3 py-2 text-xs font-black text-atlantia-wine transition hover:bg-atlantia-blush">Actualizar</button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="7" class="px-4 py-10 text-center text-atlantia-ink/55">No hay pedidos con estos filtros.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    <div class="flex flex-col gap-3 border-t border-atlantia-rose/15 px-4 py-3 text-sm text-atlantia-ink/55 sm:flex-row sm:items-center sm:justify-between">
                        <p>Mostrando {{ $pedidos->firstItem() ?? 0 }} a {{ $pedidos->lastItem() ?? 0 }} de {{ $pedidos->total() }} pedidos</p>
                        {{ $pedidos->links() }}
                    </div>
                </article>
            </div>

            <aside class="space-y-4">
                <article class="rounded-lg border border-atlantia-rose/15 bg-white p-5 shadow-[0_12px_32px_rgba(42,16,24,0.05)]">
                    <h2 class="text-sm font-black text-atlantia-wine">Resumen operativo</h2>
                    <div class="mt-4 border-t border-atlantia-rose/15 pt-4">
                        <p class="text-xs text-atlantia-ink/55">Tiempo promedio de preparacion</p>
                        <div class="mt-1 flex items-center gap-2"><span class="text-2xl font-black text-atlantia-ink">{{ $dashboard['summary']['tiempo_preparacion'] }} min</span><span class="rounded-full bg-emerald-50 px-2 py-1 text-[10px] font-black text-emerald-700">-5 min vs ayer</span></div>
                    </div>
                    <div class="mt-4">
                        <p class="text-xs text-atlantia-ink/55">Tasa de cumplimiento</p>
                        <div class="mt-1 flex items-center gap-2"><span class="text-2xl font-black text-atlantia-ink">{{ $dashboard['summary']['cumplimiento'] }}%</span><span class="rounded-full bg-emerald-50 px-2 py-1 text-[10px] font-black text-emerald-700">8% vs ayer</span></div>
                    </div>
                    <a href="#" class="mt-4 inline-flex text-xs font-black text-atlantia-wine hover:underline">Ver reporte completo -></a>
                </article>

                <article class="rounded-lg border border-atlantia-rose/15 bg-white p-5 shadow-[0_12px_32px_rgba(42,16,24,0.05)]">
                    <h2 class="text-sm font-black text-atlantia-ink">Proximas acciones</h2>
                    <div class="mt-4 space-y-3">
                        @foreach ($dashboard['next_actions'] as $action)
                            <div class="flex items-center gap-3 rounded-lg p-2 transition hover:bg-atlantia-cream/60">
                                <span class="{{ $tones[$action['tone']] ?? $tones['rose'] }} grid h-10 w-10 shrink-0 place-items-center rounded-full">
                                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" aria-hidden="true"><path d="M9 11h6"/><path d="M9 15h6"/><path d="M5 3v18l2-1 2 1 2-1 2 1 2-1 2 1 2-1V3Z"/></svg>
                                </span>
                                <div class="min-w-0 flex-1">
                                    <p class="text-xs font-black text-atlantia-ink">{{ $action['label'] }}</p>
                                    <p class="text-[11px] text-atlantia-ink/55">{{ $action['hint'] }}</p>
                                </div>
                                <span class="text-atlantia-ink/35">&rsaquo;</span>
                            </div>
                        @endforeach
                    </div>
                    <a href="{{ route('vendedor.pedidos.index') }}" class="mt-4 inline-flex text-xs font-black text-atlantia-wine hover:underline">Ver todos los pedidos -></a>
                </article>

                <article class="rounded-lg border border-atlantia-rose/15 bg-white p-5 shadow-[0_12px_32px_rgba(42,16,24,0.05)]">
                    <div class="flex items-center justify-between">
                        <h2 class="text-sm font-black text-atlantia-ink">Notificaciones</h2>
                        <span class="text-xs font-black text-atlantia-wine">Ver todas</span>
                    </div>
                    <div class="mt-4 flex gap-3 rounded-lg bg-atlantia-cream/55 p-3">
                        <span class="grid h-8 w-8 shrink-0 place-items-center rounded-full bg-atlantia-blush text-atlantia-wine">
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" aria-hidden="true"><path d="m20 12-8 8-8-8 8-8 8 8Z"/></svg>
                        </span>
                        <div>
                            <p class="text-xs font-black text-atlantia-ink">Nuevas promociones disponibles</p>
                            <p class="text-[11px] text-atlantia-ink/55">Crea descuentos y aumenta tus ventas.</p>
                            <p class="mt-1 text-[10px] text-atlantia-ink/45">Hace 2 horas</p>
                        </div>
                    </div>
                </article>
            </aside>
        </div>
    </section>
@endsection
