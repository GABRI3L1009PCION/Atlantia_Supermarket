@extends('layouts.app')

@section('content')
    @php
        $overview = $metrics['overview'];
        $operacion = $metrics['operacion'];
        $recentOrders = $metrics['recent_orders'];
        $monthlySales = $metrics['monthly_sales'];
        $notifications = $metrics['notifications'];
        $courierStatus = $metrics['courier_status'];
        $quickLinks = $metrics['quick_links'];
    @endphp

    <section class="mx-auto max-w-full py-2">
        <div class="rounded-2xl border border-atlantia-rose/20 bg-white p-6 shadow-sm">
            <div class="flex flex-col gap-6 xl:flex-row xl:items-start xl:justify-between">
                <x-page-header
                    title="Dashboard General"
                    subtitle="Vista ejecutiva del marketplace Atlantia con operacion, pedidos y accesos de control."
                    class="mb-0"
                />

                <div class="grid gap-3 sm:grid-cols-2 xl:w-[420px]">
                    @foreach ($quickLinks as $link)
                        <a
                            href="{{ $link['route'] }}"
                            class="rounded-lg border border-atlantia-rose/20 bg-atlantia-cream px-4 py-4 transition hover:border-atlantia-wine hover:bg-atlantia-blush"
                        >
                            <p class="text-sm font-bold text-atlantia-ink">{{ $link['title'] }}</p>
                            <p class="mt-1 text-xs leading-5 text-atlantia-ink/65">{{ $link['description'] }}</p>
                        </a>
                    @endforeach
                </div>
            </div>

            <div class="mt-8 grid gap-4 lg:grid-cols-2 xl:grid-cols-4">
                <article class="rounded-xl border border-emerald-500 bg-white p-5 shadow-sm">
                    <p class="text-sm text-atlantia-ink/65">Clientes registrados</p>
                    <p class="mt-3 text-4xl font-bold text-atlantia-ink">{{ number_format($overview['clientes_registrados']) }}</p>
                </article>
                <article class="rounded-xl border border-atlantia-wine bg-white p-5 shadow-sm">
                    <p class="text-sm text-atlantia-ink/65">Ingresos totales</p>
                    <p class="mt-3 text-4xl font-bold text-atlantia-ink">Q{{ number_format($overview['ingresos_totales'], 2) }}</p>
                </article>
                <article class="rounded-xl border border-amber-500 bg-white p-5 shadow-sm">
                    <p class="text-sm text-atlantia-ink/65">Productos disponibles</p>
                    <p class="mt-3 text-4xl font-bold text-atlantia-ink">{{ number_format($overview['productos_disponibles']) }}</p>
                </article>
                <article class="rounded-xl border border-red-500 bg-white p-5 shadow-sm">
                    <p class="text-sm text-atlantia-ink/65">Ordenes completadas</p>
                    <p class="mt-3 text-4xl font-bold text-atlantia-ink">{{ number_format($overview['ordenes_completadas']) }}</p>
                </article>
            </div>

            <div class="mt-8 grid gap-4 2xl:grid-cols-[1.1fr_1fr]">
                <article class="rounded-xl border border-atlantia-rose/20 bg-white p-5 shadow-sm">
                    <div class="flex items-center justify-between">
                        <h2 class="text-2xl font-bold text-atlantia-ink">Ultimos pedidos</h2>
                        <a href="{{ route('admin.pedidos.index') }}" class="text-sm font-semibold text-atlantia-wine hover:underline">
                            Ver todos
                        </a>
                    </div>

                    @if ($recentOrders->isNotEmpty())
                        <div class="mt-5 overflow-x-auto">
                            <table class="min-w-full text-left text-sm">
                                <thead>
                                    <tr class="border-b border-atlantia-rose/20 text-atlantia-ink/55">
                                        <th class="pb-3 font-semibold">#</th>
                                        <th class="pb-3 font-semibold">Cliente</th>
                                        <th class="pb-3 font-semibold">Total</th>
                                        <th class="pb-3 font-semibold">Estado</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-atlantia-rose/15">
                                    @foreach ($recentOrders as $order)
                                        <tr>
                                            <td class="py-3 font-semibold text-atlantia-ink">{{ $order['numero'] }}</td>
                                            <td class="py-3 text-atlantia-ink/80">
                                                <p>{{ $order['cliente'] }}</p>
                                                <p class="text-xs text-atlantia-ink/45">{{ $order['fecha'] }}</p>
                                            </td>
                                            <td class="py-3 font-semibold text-atlantia-ink">Q{{ number_format($order['total'], 2) }}</td>
                                            <td class="py-3">
                                                <span class="rounded-md bg-atlantia-blush px-3 py-1 text-xs font-bold text-atlantia-wine">
                                                    {{ str_replace('_', ' ', $order['estado']) }}
                                                </span>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <p class="mt-5 text-sm text-atlantia-ink/65">Sin pedidos recientes.</p>
                    @endif
                </article>

                <article class="rounded-xl border border-atlantia-rose/20 bg-white p-5 shadow-sm">
                    <div class="flex items-center justify-between">
                        <h2 class="text-2xl font-bold text-atlantia-ink">Ventas recientes</h2>
                        <span class="text-sm font-semibold text-atlantia-ink/45">Ultimos 6 meses</span>
                    </div>

                    <div class="mt-6 space-y-4">
                        @foreach ($monthlySales as $sale)
                            <div>
                                <div class="mb-1 flex items-center justify-between text-sm">
                                    <span class="font-medium text-atlantia-ink">{{ $sale['label'] }}</span>
                                    <span class="text-atlantia-ink/65">Q{{ number_format($sale['total'], 2) }}</span>
                                </div>
                                <div class="h-3 rounded-full bg-atlantia-blush">
                                    <div
                                        class="h-3 rounded-full bg-atlantia-wine"
                                        style="width: {{ $sale['width'] }}%;"
                                    ></div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </article>
            </div>

            <div class="mt-8 grid gap-4 2xl:grid-cols-[1.1fr_1fr]">
                <article class="rounded-xl border border-atlantia-rose/20 bg-white p-5 shadow-sm">
                    <h2 class="text-2xl font-bold text-atlantia-ink">Notificaciones administrativas</h2>

                    @if ($notifications->isNotEmpty())
                        <div class="mt-5 space-y-3">
                            @foreach ($notifications as $notification)
                                <div class="rounded-lg border border-atlantia-rose/15 bg-atlantia-cream px-4 py-4">
                                    <p class="font-semibold text-atlantia-ink">{{ $notification['title'] }}</p>
                                    <p class="mt-1 text-sm text-atlantia-ink/65">{{ $notification['description'] }}</p>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <p class="mt-5 text-sm text-atlantia-ink/65">Sin notificaciones pendientes.</p>
                    @endif
                </article>

                <article class="rounded-xl border border-atlantia-rose/20 bg-white p-5 shadow-sm">
                    <h2 class="text-2xl font-bold text-atlantia-ink">Operacion de reparto</h2>

                    <div class="mt-5 grid gap-3 sm:grid-cols-3">
                        <div class="rounded-lg bg-atlantia-cream p-4">
                            <p class="text-sm text-atlantia-ink/55">Repartidores</p>
                            <p class="mt-2 text-3xl font-bold text-atlantia-ink">{{ number_format($courierStatus['total']) }}</p>
                        </div>
                        <div class="rounded-lg bg-atlantia-cream p-4">
                            <p class="text-sm text-atlantia-ink/55">En ruta</p>
                            <p class="mt-2 text-3xl font-bold text-atlantia-ink">{{ number_format($courierStatus['en_ruta']) }}</p>
                        </div>
                        <div class="rounded-lg bg-atlantia-cream p-4">
                            <p class="text-sm text-atlantia-ink/55">Disponibles</p>
                            <p class="mt-2 text-3xl font-bold text-atlantia-ink">{{ number_format($courierStatus['disponibles']) }}</p>
                        </div>
                    </div>

                    <div class="mt-5 grid gap-3 md:grid-cols-2">
                        <div class="rounded-lg border border-atlantia-rose/20 p-4">
                            <p class="text-sm font-semibold text-atlantia-wine">Pedidos de hoy</p>
                            <p class="mt-2 text-3xl font-bold text-atlantia-ink">{{ number_format($operacion['pedidos_hoy']) }}</p>
                        </div>
                        <div class="rounded-lg border border-atlantia-rose/20 p-4">
                            <p class="text-sm font-semibold text-atlantia-wine">Ventas de hoy</p>
                            <p class="mt-2 text-3xl font-bold text-atlantia-ink">Q{{ number_format($operacion['ventas_hoy'], 2) }}</p>
                        </div>
                        <div class="rounded-lg border border-atlantia-rose/20 p-4">
                            <p class="text-sm font-semibold text-atlantia-wine">Pedidos pendientes</p>
                            <p class="mt-2 text-3xl font-bold text-atlantia-ink">{{ number_format($operacion['pedidos_pendientes']) }}</p>
                        </div>
                        <div class="rounded-lg border border-atlantia-rose/20 p-4">
                            <p class="text-sm font-semibold text-atlantia-wine">Vendedores pendientes</p>
                            <p class="mt-2 text-3xl font-bold text-atlantia-ink">{{ number_format($operacion['vendedores_pendientes']) }}</p>
                        </div>
                    </div>
                </article>
            </div>
        </div>
    </section>
@endsection
