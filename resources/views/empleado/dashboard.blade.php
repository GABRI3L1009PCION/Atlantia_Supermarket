@extends('layouts.app')

@section('content')
    @php
        $overview = $metrics['overview'];
        $transferencias = $metrics['transferencias_recientes'];
        $mensajes = $metrics['mensajes_recientes'];
        $quickLinks = $metrics['quick_links'];
    @endphp

    <section class="mx-auto max-w-full py-2">
        <div class="space-y-6 rounded-2xl border border-atlantia-rose/20 bg-white p-4 shadow-sm sm:p-6">
            <div class="flex flex-col gap-5 xl:flex-row xl:items-start xl:justify-between">
                <x-page-header
                    title="Panel empleado"
                    subtitle="Bandeja operativa para transferencias, mensajes y moderacion."
                    class="mb-0"
                />

                <div class="grid gap-3 sm:grid-cols-3 xl:w-[640px]">
                    @foreach ($quickLinks as $link)
                        <a href="{{ $link['route'] }}" class="rounded-lg border border-atlantia-rose/20 bg-atlantia-cream p-4 transition hover:border-atlantia-wine hover:bg-atlantia-blush">
                            <p class="text-sm font-bold text-atlantia-ink">{{ $link['title'] }}</p>
                            <p class="mt-1 text-xs leading-5 text-atlantia-ink/65">{{ $link['description'] }}</p>
                        </a>
                    @endforeach
                </div>
            </div>

            <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                <x-ui.stat-card label="Transferencias pendientes" :value="number_format($overview['transferencias_pendientes'])" hint="Pagos por validar" class="border-amber-500" />
                <x-ui.stat-card label="Mensajes pendientes" :value="number_format($overview['mensajes_pendientes'])" hint="Clientes esperando respuesta" class="border-atlantia-wine" />
                <x-ui.stat-card label="Flags ML" :value="number_format($overview['resenas_flaggeadas'])" hint="Revision de sospecha" class="border-rose-500" />
                <x-ui.stat-card label="Resenas pendientes" :value="number_format($overview['resenas_pendientes'])" hint="Moderacion manual" class="border-sky-500" />
            </div>

            <div class="grid gap-4 xl:grid-cols-2">
                <article class="rounded-xl border border-atlantia-rose/20 bg-white p-4 shadow-sm sm:p-5">
                    <h2 class="text-xl font-bold text-atlantia-ink">Transferencias recientes</h2>
                    <div class="mt-4 divide-y divide-atlantia-rose/15">
                        @forelse ($transferencias as $payment)
                            <div class="flex flex-col gap-2 py-3 sm:flex-row sm:items-center sm:justify-between">
                                <div>
                                    <p class="font-semibold text-atlantia-ink">{{ $payment->pedido?->numero_pedido ?? 'Pago sin pedido' }}</p>
                                    <p class="text-xs text-atlantia-ink/55">{{ $payment->pedido?->cliente?->name ?? 'Cliente no disponible' }}</p>
                                </div>
                                <div class="text-left sm:text-right">
                                    <p class="font-bold text-atlantia-wine">Q{{ number_format((float) $payment->monto, 2) }}</p>
                                    <p class="text-xs text-atlantia-ink/55">{{ str_replace('_', ' ', $payment->estadoValor()) }}</p>
                                </div>
                            </div>
                        @empty
                            <p class="py-6 text-sm text-atlantia-ink/60">Sin transferencias recientes.</p>
                        @endforelse
                    </div>
                </article>

                <article class="rounded-xl border border-atlantia-rose/20 bg-atlantia-cream p-4 shadow-sm sm:p-5">
                    <h2 class="text-xl font-bold text-atlantia-ink">Mensajes por atender</h2>
                    <div class="mt-4 divide-y divide-atlantia-rose/15">
                        @forelse ($mensajes as $mensaje)
                            <div class="py-3">
                                <p class="font-semibold text-atlantia-ink">{{ $mensaje->asunto }}</p>
                                <p class="text-sm text-atlantia-ink/65">{{ $mensaje->nombre }} · {{ $mensaje->email }}</p>
                            </div>
                        @empty
                            <p class="py-6 text-sm text-atlantia-ink/60">No hay mensajes pendientes.</p>
                        @endforelse
                    </div>
                </article>
            </div>
        </div>
    </section>
@endsection
