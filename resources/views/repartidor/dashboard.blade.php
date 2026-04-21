@extends('layouts.app')

@section('content')
    @php
        $overview = $metrics['overview'];
        $rutas = $metrics['rutas_recientes'];
        $quickLinks = $metrics['quick_links'];
    @endphp

    <section class="mx-auto max-w-full py-2">
        <div class="space-y-6 rounded-2xl border border-atlantia-rose/20 bg-white p-4 shadow-sm sm:p-6">
            <div class="flex flex-col gap-5 xl:flex-row xl:items-start xl:justify-between">
                <x-page-header
                    title="Panel repartidor"
                    subtitle="Control de entregas asignadas, rutas y actividad del dia."
                    class="mb-0"
                />

                <div class="grid gap-3 sm:grid-cols-2 xl:w-[420px]">
                    @foreach ($quickLinks as $link)
                        <a href="{{ $link['route'] }}" class="rounded-lg border border-atlantia-rose/20 bg-atlantia-cream p-4 transition hover:border-atlantia-wine hover:bg-atlantia-blush">
                            <p class="text-sm font-bold text-atlantia-ink">{{ $link['title'] }}</p>
                            <p class="mt-1 text-xs leading-5 text-atlantia-ink/65">{{ $link['description'] }}</p>
                        </a>
                    @endforeach
                </div>
            </div>

            <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                <x-ui.stat-card label="Rutas activas" :value="number_format($overview['rutas_activas'])" hint="En trabajo o asignadas" class="border-atlantia-wine" />
                <x-ui.stat-card label="Entregas hoy" :value="number_format($overview['entregas_hoy'])" hint="Completadas este dia" class="border-emerald-500" />
                <x-ui.stat-card label="Pendientes" :value="number_format($overview['pendientes'])" hint="Aun sin iniciar" class="border-amber-500" />
                <x-ui.stat-card label="En ruta" :value="number_format($overview['en_ruta'])" hint="Activas ahora" class="border-sky-500" />
            </div>

            <article class="rounded-xl border border-atlantia-rose/20 bg-white p-4 shadow-sm sm:p-5">
                <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                    <h2 class="text-xl font-bold text-atlantia-ink">Rutas recientes</h2>
                    <a href="{{ route('repartidor.rutas.index') }}" class="text-sm font-semibold text-atlantia-wine hover:underline">Ver rutas</a>
                </div>

                <div class="mt-4 overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <tbody class="divide-y divide-atlantia-rose/15">
                            @forelse ($rutas as $ruta)
                                <tr>
                                    <td class="py-3">
                                        <p class="font-semibold text-atlantia-ink">{{ $ruta->pedido?->numero_pedido ?? 'Ruta sin pedido' }}</p>
                                        <p class="text-xs text-atlantia-ink/55">{{ $ruta->pedido?->cliente?->name ?? 'Cliente no disponible' }}</p>
                                    </td>
                                    <td class="py-3 text-atlantia-ink/70">{{ number_format((float) $ruta->distancia_km, 2) }} km</td>
                                    <td class="py-3 text-right">
                                        <span class="rounded-md bg-atlantia-blush px-3 py-1 text-xs font-bold text-atlantia-wine">
                                            {{ $ruta->estado }}
                                        </span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td class="py-6 text-center text-atlantia-ink/60">No tienes rutas recientes.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </article>
        </div>
    </section>
@endsection
