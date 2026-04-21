@extends('layouts.app')

@section('content')
    <section class="mx-auto max-w-full py-2">
        <div class="space-y-6 rounded-2xl border border-atlantia-rose/20 bg-white p-6 shadow-sm">
            <x-page-header title="Reportes" subtitle="Resumen ejecutivo comercial, fiscal y de riesgo para la operacion Atlantia." />

            <div class="rounded-xl border border-atlantia-rose/20 bg-atlantia-cream p-4">
                <form method="GET" class="grid gap-3 xl:grid-cols-[1fr_1fr_auto]">
                    <input type="date" name="fecha_desde" value="{{ $filters['fecha_desde'] ?? '' }}" class="rounded-md border border-atlantia-rose/35 px-3 py-2">
                    <input type="date" name="fecha_hasta" value="{{ $filters['fecha_hasta'] ?? '' }}" class="rounded-md border border-atlantia-rose/35 px-3 py-2">
                    <x-ui.button type="submit" variant="secondary">Actualizar</x-ui.button>
                </form>
            </div>

            <div class="grid gap-4 md:grid-cols-4 xl:grid-cols-7">
                <div class="rounded-xl border border-atlantia-rose/20 bg-atlantia-cream p-4">
                    <p class="text-sm text-atlantia-ink/55">Ventas</p>
                    <p class="mt-2 text-2xl font-bold text-atlantia-wine">Q{{ number_format($reportes['ventas_mes'], 2) }}</p>
                </div>
                <div class="rounded-xl border border-atlantia-rose/20 bg-white p-4">
                    <p class="text-sm text-atlantia-ink/55">Pedidos</p>
                    <p class="mt-2 text-2xl font-bold text-atlantia-ink">{{ $reportes['pedidos_mes'] }}</p>
                </div>
                <div class="rounded-xl border border-atlantia-rose/20 bg-white p-4">
                    <p class="text-sm text-atlantia-ink/55">Ticket promedio</p>
                    <p class="mt-2 text-2xl font-bold text-atlantia-ink">Q{{ number_format($reportes['ticket_promedio'], 2) }}</p>
                </div>
                <div class="rounded-xl border border-emerald-200 bg-white p-4">
                    <p class="text-sm text-atlantia-ink/55">Vendedores activos</p>
                    <p class="mt-2 text-2xl font-bold text-emerald-600">{{ $reportes['vendedores_activos'] }}</p>
                </div>
                <div class="rounded-xl border border-amber-200 bg-white p-4">
                    <p class="text-sm text-atlantia-ink/55">Comisiones pendientes</p>
                    <p class="mt-2 text-2xl font-bold text-amber-600">{{ $reportes['comisiones_pendientes'] }}</p>
                </div>
                <div class="rounded-xl border border-rose-200 bg-white p-4">
                    <p class="text-sm text-atlantia-ink/55">DTE rechazados</p>
                    <p class="mt-2 text-2xl font-bold text-rose-600">{{ $reportes['dtes_rechazados'] }}</p>
                </div>
                <div class="rounded-xl border border-slate-200 bg-white p-4">
                    <p class="text-sm text-atlantia-ink/55">Alertas pendientes</p>
                    <p class="mt-2 text-2xl font-bold text-slate-700">{{ $reportes['alertas_pendientes'] }}</p>
                </div>
            </div>

            <div class="grid gap-6 xl:grid-cols-2">
                <div class="rounded-2xl border border-atlantia-rose/20 bg-white p-5">
                    <h2 class="text-lg font-bold text-atlantia-wine">Pedidos por estado</h2>
                    <div class="mt-4 space-y-3">
                        @foreach ($reportes['ventas_por_estado'] as $estado => $total)
                            <div>
                                <div class="mb-1 flex items-center justify-between text-sm">
                                    <span class="font-semibold text-atlantia-ink">{{ ucfirst(str_replace('_', ' ', $estado)) }}</span>
                                    <span class="text-atlantia-ink/60">{{ $total }}</span>
                                </div>
                                <div class="h-2 rounded-full bg-atlantia-blush">
                                    <div class="h-2 rounded-full bg-atlantia-wine" style="width: {{ max(8, min(100, $reportes['pedidos_mes'] > 0 ? ($total / $reportes['pedidos_mes']) * 100 : 0)) }}%"></div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="rounded-2xl border border-atlantia-rose/20 bg-white p-5">
                    <h2 class="text-lg font-bold text-atlantia-wine">Pedidos por metodo de pago</h2>
                    <div class="mt-4 space-y-3">
                        @foreach ($reportes['ventas_por_metodo_pago'] as $metodo => $total)
                            <div>
                                <div class="mb-1 flex items-center justify-between text-sm">
                                    <span class="font-semibold text-atlantia-ink">{{ ucfirst($metodo) }}</span>
                                    <span class="text-atlantia-ink/60">{{ $total }}</span>
                                </div>
                                <div class="h-2 rounded-full bg-atlantia-blush">
                                    <div class="h-2 rounded-full bg-atlantia-wine" style="width: {{ max(8, min(100, $reportes['pedidos_mes'] > 0 ? ($total / $reportes['pedidos_mes']) * 100 : 0)) }}%"></div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

            <div class="grid gap-6 xl:grid-cols-3">
                <div class="rounded-2xl border border-atlantia-rose/20 bg-white p-5 xl:col-span-1">
                    <h2 class="text-lg font-bold text-atlantia-wine">Top vendedores</h2>
                    <div class="mt-4 space-y-3">
                        @foreach ($reportes['top_vendedores'] as $vendor)
                            <div class="rounded-xl border border-atlantia-rose/15 bg-atlantia-cream px-4 py-3">
                                <p class="font-semibold text-atlantia-ink">{{ $vendor->business_name }}</p>
                                <p class="mt-1 text-sm text-atlantia-wine">Q{{ number_format((float) $vendor->total_ventas, 2) }}</p>
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="rounded-2xl border border-atlantia-rose/20 bg-white p-5 xl:col-span-1">
                    <h2 class="text-lg font-bold text-atlantia-wine">DTE recientes</h2>
                    <div class="mt-4 space-y-3">
                        @foreach ($reportes['dtes_recientes'] as $dte)
                            <div class="rounded-xl border border-atlantia-rose/15 bg-atlantia-cream px-4 py-3">
                                <p class="font-semibold text-atlantia-ink">{{ $dte->numero_dte }}</p>
                                <p class="mt-1 text-sm text-atlantia-ink/60">{{ $dte->vendor?->business_name }} · {{ $dte->estado }}</p>
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="rounded-2xl border border-atlantia-rose/20 bg-white p-5 xl:col-span-1">
                    <h2 class="text-lg font-bold text-atlantia-wine">Alertas recientes</h2>
                    <div class="mt-4 space-y-3">
                        @foreach ($reportes['alertas_recientes'] as $alerta)
                            <div class="rounded-xl border border-atlantia-rose/15 bg-atlantia-cream px-4 py-3">
                                <p class="font-semibold text-atlantia-ink">{{ $alerta->tipo }}</p>
                                <p class="mt-1 text-sm text-atlantia-ink/60">{{ $alerta->pedido?->numero_pedido ?? 'Sin pedido' }} · {{ $alerta->user?->name ?? 'Sin usuario' }}</p>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
