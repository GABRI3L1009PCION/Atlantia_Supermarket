@extends('layouts.app')

@section('content')
    @php
        $overview = $metrics['overview'];
        $operacion = $metrics['operacion'];
        $pedidos = $metrics['pedidos_recientes'];
        $quickLinks = $metrics['quick_links'];
    @endphp

    <section class="mx-auto max-w-full py-2">
        <div class="space-y-6 rounded-2xl border border-atlantia-rose/20 bg-white p-4 shadow-sm sm:p-6">
            <div class="flex flex-col gap-5 xl:flex-row xl:items-start xl:justify-between">
                <x-page-header
                    title="Panel de vendedor"
                    subtitle="Control comercial de tu tienda, pedidos, inventario y predicciones."
                    class="mb-0"
                />

                <div class="grid gap-3 sm:grid-cols-2 xl:w-[520px]">
                    @foreach ($quickLinks as $link)
                        <a href="{{ $link['route'] }}" class="rounded-lg border border-atlantia-rose/20 bg-atlantia-cream p-4 transition hover:border-atlantia-wine hover:bg-atlantia-blush">
                            <p class="text-sm font-bold text-atlantia-ink">{{ $link['title'] }}</p>
                            <p class="mt-1 text-xs leading-5 text-atlantia-ink/65">{{ $link['description'] }}</p>
                        </a>
                    @endforeach
                </div>
            </div>

            <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                <x-ui.stat-card label="Ventas de hoy" value="Q{{ number_format($overview['ventas_hoy'], 2) }}" hint="Ingresos registrados este dia" class="border-atlantia-wine" />
                <x-ui.stat-card label="Ventas del mes" value="Q{{ number_format($overview['ventas_mes'], 2) }}" hint="Acumulado mensual" class="border-emerald-500" />
                <x-ui.stat-card label="Pedidos pendientes" :value="number_format($overview['pedidos_pendientes'])" hint="Requieren atencion" class="border-amber-500" />
                <x-ui.stat-card label="Productos publicados" :value="number_format($overview['productos_publicados'])" hint="Visibles en catalogo" class="border-sky-500" />
            </div>

            <div class="grid gap-4 lg:grid-cols-[1fr_0.85fr]">
                <article class="rounded-xl border border-atlantia-rose/20 bg-white p-4 shadow-sm sm:p-5">
                    <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                        <h2 class="text-xl font-bold text-atlantia-ink">Pedidos recientes</h2>
                        <a href="{{ route('vendedor.pedidos.index') }}" class="text-sm font-semibold text-atlantia-wine hover:underline">Ver pedidos</a>
                    </div>

                    <div class="mt-4 overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <tbody class="divide-y divide-atlantia-rose/15">
                                @forelse ($pedidos as $pedido)
                                    <tr>
                                        <td class="py-3">
                                            <p class="font-semibold text-atlantia-ink">{{ $pedido->numero_pedido }}</p>
                                            <p class="text-xs text-atlantia-ink/55">{{ $pedido->created_at?->format('d/m/Y H:i') }}</p>
                                        </td>
                                        <td class="py-3 font-semibold text-atlantia-wine">Q{{ number_format((float) $pedido->total, 2) }}</td>
                                        <td class="py-3 text-right">
                                            <span class="rounded-md bg-atlantia-blush px-3 py-1 text-xs font-bold text-atlantia-wine">
                                                {{ str_replace('_', ' ', $pedido->estadoValor()) }}
                                            </span>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td class="py-6 text-center text-atlantia-ink/60">Aun no hay pedidos recientes.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </article>

                <article class="rounded-xl border border-atlantia-rose/20 bg-atlantia-cream p-4 shadow-sm sm:p-5">
                    <h2 class="text-xl font-bold text-atlantia-ink">Salud operativa</h2>
                    <div class="mt-4 grid gap-3 sm:grid-cols-2">
                        @foreach ($operacion as $label => $value)
                            <div class="rounded-lg border border-atlantia-rose/15 bg-white p-4">
                                <p class="text-xs font-semibold uppercase text-atlantia-rose">{{ str_replace('_', ' ', $label) }}</p>
                                <p class="mt-2 text-2xl font-bold text-atlantia-ink">{{ number_format((float) $value) }}</p>
                            </div>
                        @endforeach
                    </div>
                </article>
            </div>
        </div>
    </section>
@endsection
