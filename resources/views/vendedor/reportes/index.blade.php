@extends('layouts.app')

@section('content')
    @php
        $filters = $reportes['filters'] ?? [];
        $enumValue = fn ($value) => $value instanceof \BackedEnum ? $value->value : (string) $value;
        $estadoLabel = fn ($value) => ucfirst(str_replace('_', ' ', $enumValue($value)));
        $money = fn ($value) => 'Q ' . number_format((float) $value, 2);
        $pedidosTotal = max(1, (int) ($reportes['pedidos_total'] ?? 0));
    @endphp

    <section class="mx-auto max-w-7xl px-4 py-8">
        <div class="space-y-6 rounded-2xl border border-atlantia-rose/20 bg-white p-6 shadow-sm">
            <x-page-header title="Reportes" subtitle="Ventas, pedidos, productos y stock de tu tienda." />

            <div class="rounded-xl border border-atlantia-rose/20 bg-atlantia-cream p-4">
                <form method="GET" class="grid gap-3 md:grid-cols-[1fr_1fr_180px_auto]">
                    <label class="block">
                        <span class="mb-1 block text-xs font-bold uppercase tracking-normal text-atlantia-ink/60">Desde</span>
                        <input type="date" name="fecha_desde" value="{{ $filters['fecha_desde'] ?? '' }}" class="w-full rounded-md border border-atlantia-rose/35 px-3 py-2">
                    </label>
                    <label class="block">
                        <span class="mb-1 block text-xs font-bold uppercase tracking-normal text-atlantia-ink/60">Hasta</span>
                        <input type="date" name="fecha_hasta" value="{{ $filters['fecha_hasta'] ?? '' }}" class="w-full rounded-md border border-atlantia-rose/35 px-3 py-2">
                    </label>
                    <label class="block">
                        <span class="mb-1 block text-xs font-bold uppercase tracking-normal text-atlantia-ink/60">Agrupar</span>
                        <select name="agrupacion" class="w-full rounded-md border border-atlantia-rose/35 px-3 py-2">
                            <option value="dia" @selected(($filters['agrupacion'] ?? 'dia') === 'dia')>Dia</option>
                            <option value="mes" @selected(($filters['agrupacion'] ?? 'dia') === 'mes')>Mes</option>
                        </select>
                    </label>
                    <div class="flex items-end">
                        <x-ui.button type="submit" variant="secondary">Actualizar</x-ui.button>
                    </div>
                </form>
            </div>

            <div class="grid gap-4 md:grid-cols-4">
                <div class="rounded-xl border border-atlantia-rose/20 bg-atlantia-cream p-4">
                    <p class="text-sm text-atlantia-ink/55">Mis ventas</p>
                    <p class="mt-2 text-2xl font-bold text-atlantia-wine">{{ $money($reportes['ventas_total']) }}</p>
                </div>
                <div class="rounded-xl border border-atlantia-rose/20 bg-white p-4">
                    <p class="text-sm text-atlantia-ink/55">Mis pedidos</p>
                    <p class="mt-2 text-2xl font-bold text-atlantia-ink">{{ $reportes['pedidos_total'] }}</p>
                </div>
                <div class="rounded-xl border border-amber-200 bg-white p-4">
                    <p class="text-sm text-atlantia-ink/55">Pendientes</p>
                    <p class="mt-2 text-2xl font-bold text-amber-600">{{ $reportes['pendientes'] }}</p>
                </div>
                <div class="rounded-xl border border-atlantia-rose/20 bg-white p-4">
                    <p class="text-sm text-atlantia-ink/55">Ticket promedio</p>
                    <p class="mt-2 text-2xl font-bold text-atlantia-ink">{{ $money($reportes['ticket_promedio']) }}</p>
                </div>
            </div>

            <div class="grid gap-6 xl:grid-cols-2">
                <article class="rounded-2xl border border-atlantia-rose/20 bg-white p-5">
                    <h2 class="text-lg font-bold text-atlantia-wine">Mis ventas por periodo</h2>
                    <div class="mt-4 overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <thead class="text-left text-atlantia-ink/60">
                                <tr>
                                    <th class="py-2">Periodo</th>
                                    <th class="py-2">Pedidos</th>
                                    <th class="py-2 text-right">Total</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-atlantia-rose/15">
                                @forelse ($reportes['ventas_por_periodo'] as $row)
                                    <tr>
                                        <td class="py-3 font-semibold text-atlantia-ink">{{ $row->periodo }}</td>
                                        <td class="py-3 text-atlantia-ink/70">{{ $row->pedidos }}</td>
                                        <td class="py-3 text-right font-bold text-atlantia-wine">{{ $money($row->total) }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="3" class="py-6 text-center text-atlantia-ink/60">Sin ventas en este periodo.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </article>

                <article class="rounded-2xl border border-atlantia-rose/20 bg-white p-5">
                    <h2 class="text-lg font-bold text-atlantia-wine">Mis pedidos por estado</h2>
                    <div class="mt-4 space-y-3">
                        @forelse ($reportes['pedidos_por_estado'] as $row)
                            <div>
                                <div class="mb-1 flex items-center justify-between text-sm">
                                    <span class="font-semibold text-atlantia-ink">{{ $estadoLabel($row->estado) }}</span>
                                    <span class="text-atlantia-ink/60">{{ $row->pedidos }} pedidos - {{ $money($row->total) }}</span>
                                </div>
                                <div class="h-2 rounded-full bg-atlantia-blush">
                                    <div class="h-2 rounded-full bg-atlantia-wine" style="width: {{ max(6, min(100, ((int) $row->pedidos / $pedidosTotal) * 100)) }}%"></div>
                                </div>
                            </div>
                        @empty
                            <p class="py-6 text-center text-sm text-atlantia-ink/60">Sin pedidos en este periodo.</p>
                        @endforelse
                    </div>
                </article>
            </div>

            <div class="grid gap-6 xl:grid-cols-2">
                <article class="rounded-2xl border border-atlantia-rose/20 bg-white p-5">
                    <h2 class="text-lg font-bold text-atlantia-wine">Mis productos mas vendidos</h2>
                    <div class="mt-4 space-y-3">
                        @forelse ($reportes['top_productos'] as $producto)
                            <div class="rounded-xl border border-atlantia-rose/15 bg-atlantia-cream px-4 py-3">
                                <div class="flex items-start justify-between gap-3">
                                    <p class="font-semibold text-atlantia-ink">{{ $producto->nombre }}</p>
                                    <span class="rounded-full bg-white px-2 py-1 text-xs font-bold text-atlantia-wine">{{ $producto->unidades }} uds</span>
                                </div>
                                <p class="mt-2 text-sm text-atlantia-ink/65">{{ $money($producto->total) }}</p>
                            </div>
                        @empty
                            <p class="py-6 text-center text-sm text-atlantia-ink/60">Aun no hay productos vendidos en este periodo.</p>
                        @endforelse
                    </div>
                </article>

                <article class="rounded-2xl border border-atlantia-rose/20 bg-white p-5">
                    <h2 class="text-lg font-bold text-atlantia-wine">Stock bajo</h2>
                    <div class="mt-4 space-y-3">
                        @forelse ($reportes['stock_bajo'] as $producto)
                            <div class="rounded-xl border border-amber-200 bg-white px-4 py-3">
                                <div class="flex items-start justify-between gap-3">
                                    <p class="font-semibold text-atlantia-ink">{{ $producto->nombre }}</p>
                                    <span class="rounded-full bg-amber-50 px-2 py-1 text-xs font-bold text-amber-700">Revisar</span>
                                </div>
                                <p class="mt-2 text-sm text-atlantia-ink/65">
                                    Actual: {{ $producto->inventario?->stock_actual ?? 0 }}
                                    · Minimo: {{ $producto->inventario?->stock_minimo ?? 0 }}
                                    · Reservado: {{ $producto->inventario?->stock_reservado ?? 0 }}
                                </p>
                            </div>
                        @empty
                            <p class="py-6 text-center text-sm text-atlantia-ink/60">Todo el inventario esta por encima del minimo.</p>
                        @endforelse
                    </div>
                </article>
            </div>
        </div>
    </section>
@endsection
