@extends('layouts.app')

@section('content')
    <section class="mx-auto max-w-full py-2">
        <div class="space-y-6 rounded-2xl border border-atlantia-rose/20 bg-white p-6 shadow-sm">
            <x-page-header title="Monitor ML" subtitle="Salud de modelos, drift, entrenamiento y latencia del microservicio." />

            <div class="rounded-xl border border-atlantia-rose/20 bg-atlantia-cream p-4">
                <form method="GET" class="grid gap-3 md:grid-cols-[1fr_auto]">
                    <input type="number" step="0.01" min="0" max="1" name="drift_threshold" value="{{ $filters['drift_threshold'] ?? '0.25' }}" class="rounded-md border border-atlantia-rose/35 px-3 py-2" placeholder="Umbral drift">
                    <x-ui.button type="submit" variant="secondary">Actualizar umbral</x-ui.button>
                </form>
            </div>

            <div class="grid gap-4 md:grid-cols-4 xl:grid-cols-7">
                <div class="rounded-xl border border-emerald-200 bg-white p-4">
                    <p class="text-sm text-atlantia-ink/55">Produccion</p>
                    <p class="mt-2 text-2xl font-bold text-emerald-600">{{ $monitor['modelos_produccion'] }}</p>
                </div>
                <div class="rounded-xl border border-sky-200 bg-white p-4">
                    <p class="text-sm text-atlantia-ink/55">Staging</p>
                    <p class="mt-2 text-2xl font-bold text-sky-600">{{ $monitor['modelos_staging'] }}</p>
                </div>
                <div class="rounded-xl border border-atlantia-rose/20 bg-atlantia-cream p-4">
                    <p class="text-sm text-atlantia-ink/55">Jobs activos</p>
                    <p class="mt-2 text-2xl font-bold text-atlantia-wine">{{ $monitor['jobs_activos'] }}</p>
                </div>
                <div class="rounded-xl border border-rose-200 bg-white p-4">
                    <p class="text-sm text-atlantia-ink/55">Fallidos 24h</p>
                    <p class="mt-2 text-2xl font-bold text-rose-600">{{ $monitor['jobs_fallidos_24h'] }}</p>
                </div>
                <div class="rounded-xl border border-amber-200 bg-white p-4">
                    <p class="text-sm text-atlantia-ink/55">Drift alto</p>
                    <p class="mt-2 text-2xl font-bold text-amber-600">{{ $monitor['drift_alto'] }}</p>
                </div>
                <div class="rounded-xl border border-slate-200 bg-white p-4">
                    <p class="text-sm text-atlantia-ink/55">Latencia promedio</p>
                    <p class="mt-2 text-2xl font-bold text-slate-700">{{ $monitor['latencia_promedio_ms'] }} ms</p>
                </div>
                <div class="rounded-xl border border-rose-200 bg-white p-4">
                    <p class="text-sm text-atlantia-ink/55">Llamadas fallidas 24h</p>
                    <p class="mt-2 text-2xl font-bold text-rose-600">{{ $monitor['llamadas_fallidas_24h'] }}</p>
                </div>
            </div>

            <div class="grid gap-6 xl:grid-cols-3">
                <div class="rounded-2xl border border-atlantia-rose/20 bg-white p-5 xl:col-span-1">
                    <h2 class="text-lg font-bold text-atlantia-wine">Modelos recientes</h2>
                    <div class="mt-4 space-y-3">
                        @foreach ($monitor['modelos_recientes'] as $modelo)
                            <div class="rounded-xl border border-atlantia-rose/15 bg-atlantia-cream px-4 py-3">
                                <p class="font-semibold text-atlantia-ink">{{ $modelo->nombre_modelo }} {{ $modelo->version }}</p>
                                <p class="mt-1 text-sm text-atlantia-ink/60">{{ $modelo->estado }} · {{ $modelo->fecha_entrenamiento?->format('d/m/Y H:i') ?? 'Sin fecha' }}</p>
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="rounded-2xl border border-atlantia-rose/20 bg-white p-5 xl:col-span-1">
                    <h2 class="text-lg font-bold text-atlantia-wine">Jobs recientes</h2>
                    <div class="mt-4 space-y-3">
                        @foreach ($monitor['jobs_recientes'] as $job)
                            <div class="rounded-xl border border-atlantia-rose/15 bg-atlantia-cream px-4 py-3">
                                <p class="font-semibold text-atlantia-ink">{{ $job->modelo_nombre }}</p>
                                <p class="mt-1 text-sm text-atlantia-ink/60">{{ $job->estado }} · dataset {{ $job->dataset_size ?? 0 }}</p>
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="rounded-2xl border border-atlantia-rose/20 bg-white p-5 xl:col-span-1">
                    <h2 class="text-lg font-bold text-atlantia-wine">Llamadas recientes al servicio</h2>
                    <div class="mt-4 space-y-3">
                        @foreach ($monitor['logs_recientes'] as $log)
                            <div class="rounded-xl border border-atlantia-rose/15 bg-atlantia-cream px-4 py-3">
                                <div class="flex items-center justify-between gap-3">
                                    <p class="font-semibold text-atlantia-ink">{{ $log->endpoint }}</p>
                                    <span class="text-xs font-bold {{ $log->estado === 'success' ? 'text-emerald-600' : 'text-rose-600' }}">{{ $log->estado }}</span>
                                </div>
                                <p class="mt-1 text-sm text-atlantia-ink/60">{{ $log->latencia_ms ?? 0 }} ms</p>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

            <div class="rounded-2xl border border-atlantia-rose/20 bg-white p-5">
                <h2 class="text-lg font-bold text-atlantia-wine">Metricas recientes</h2>
                <div class="mt-4 overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead>
                            <tr class="border-b border-atlantia-rose/20 text-left text-atlantia-ink/55">
                                <th class="pb-3">Modelo</th>
                                <th class="pb-3">Fecha</th>
                                <th class="pb-3">MAPE</th>
                                <th class="pb-3">RMSE</th>
                                <th class="pb-3">R2</th>
                                <th class="pb-3">Drift</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-atlantia-rose/15">
                            @foreach ($monitor['metricas_recientes'] as $metrica)
                                <tr>
                                    <td class="py-3 font-semibold text-atlantia-ink">{{ $metrica->modeloVersion?->nombre_modelo }}</td>
                                    <td class="py-3 text-atlantia-ink/70">{{ $metrica->fecha?->format('d/m/Y') }}</td>
                                    <td class="py-3 text-atlantia-ink/70">{{ $metrica->mape }}</td>
                                    <td class="py-3 text-atlantia-ink/70">{{ $metrica->rmse }}</td>
                                    <td class="py-3 text-atlantia-ink/70">{{ $metrica->r2 }}</td>
                                    <td class="py-3 font-semibold {{ (float) $metrica->drift_score > 0.25 ? 'text-rose-600' : 'text-emerald-600' }}">{{ $metrica->drift_score }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </section>
@endsection
