<section wire:poll.60s="refreshMonitor" class="rounded-lg border border-atlantia-rose/20 bg-white p-5 shadow-sm">
    <div class="flex flex-col gap-3 border-b border-atlantia-rose/15 pb-4 lg:flex-row lg:items-end lg:justify-between">
        <div>
            <p class="text-sm font-bold uppercase tracking-normal text-atlantia-wine">ML Monitor</p>
            <h2 class="mt-1 text-2xl font-black text-atlantia-ink">Drift de modelos</h2>
            <p class="mt-1 text-sm text-atlantia-ink/65">Salud de modelos, entrenamiento y llamadas al microservicio.</p>
        </div>
        <p class="text-sm font-semibold text-atlantia-ink/55">Actualizado {{ $lastRefreshed }}</p>
    </div>

    <div
        class="mt-4 rounded-lg border px-4 py-3 text-sm font-semibold
            {{ $health === 'online' ? 'border-emerald-200 bg-emerald-50 text-emerald-700' : '' }}
            {{ $health === 'mock' ? 'border-sky-200 bg-sky-50 text-sky-700' : '' }}
            {{ $health === 'offline' ? 'border-amber-200 bg-amber-50 text-amber-800' : '' }}"
    >
        {{ $healthMessage }}
    </div>

    <div class="mt-5 grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
        <article class="rounded-lg border border-atlantia-rose/20 bg-atlantia-blush p-4">
            <p class="text-sm text-atlantia-wine">Produccion</p>
            <p class="mt-2 text-3xl font-black text-atlantia-ink">{{ number_format((int) ($monitor['modelos_produccion'] ?? 0)) }}</p>
        </article>
        <article class="rounded-lg border border-sky-200 bg-sky-50 p-4">
            <p class="text-sm text-sky-800">Staging</p>
            <p class="mt-2 text-3xl font-black text-sky-700">{{ number_format((int) ($monitor['modelos_staging'] ?? 0)) }}</p>
        </article>
        <article class="rounded-lg border border-amber-200 bg-amber-50 p-4">
            <p class="text-sm text-amber-800">Drift alto</p>
            <p class="mt-2 text-3xl font-black text-amber-700">{{ number_format((int) ($monitor['drift_alto'] ?? 0)) }}</p>
        </article>
        <article class="rounded-lg border border-rose-200 bg-rose-50 p-4">
            <p class="text-sm text-rose-800">Fallos 24h</p>
            <p class="mt-2 text-3xl font-black text-rose-700">{{ number_format((int) ($monitor['llamadas_fallidas_24h'] ?? 0)) }}</p>
        </article>
    </div>

    <div class="mt-5 grid gap-4 xl:grid-cols-[1fr_0.95fr]">
        <article class="rounded-lg border border-atlantia-rose/15 p-4">
            <div class="flex items-center justify-between">
                <h3 class="font-black text-atlantia-ink">Metricas recientes</h3>
                <span class="text-xs font-bold text-atlantia-ink/55">
                    Latencia prom. {{ number_format((int) ($monitor['latencia_promedio_ms'] ?? 0)) }} ms
                </span>
            </div>

            <div class="mt-4 space-y-3">
                @forelse ($monitor['metricas_recientes'] ?? [] as $metric)
                    <div class="rounded-lg bg-atlantia-blush/50 p-4">
                        <div class="flex items-center justify-between gap-3">
                            <p class="font-bold text-atlantia-ink">
                                {{ $metric->modeloVersion?->nombre_modelo ?? 'Modelo sin nombre' }}
                                <span class="text-atlantia-ink/55">v{{ $metric->modeloVersion?->version ?? 'n/a' }}</span>
                            </p>
                            <span class="rounded-full px-3 py-1 text-xs font-black {{ (float) $metric->drift_score > 0.25 ? 'bg-amber-100 text-amber-800' : 'bg-emerald-100 text-emerald-700' }}">
                                Drift {{ number_format((float) $metric->drift_score, 3) }}
                            </span>
                        </div>
                        <p class="mt-2 text-xs text-atlantia-ink/60">
                            MAPE {{ number_format((float) $metric->mape, 3) }} · RMSE {{ number_format((float) $metric->rmse, 3) }} · R2 {{ number_format((float) $metric->r2, 3) }}
                        </p>
                    </div>
                @empty
                    <p class="rounded-lg bg-atlantia-blush px-4 py-6 text-center text-sm text-atlantia-ink/65">
                        Todavia no hay metricas de drift registradas.
                    </p>
                @endforelse
            </div>
        </article>

        <article class="rounded-lg border border-atlantia-rose/15 p-4">
            <h3 class="font-black text-atlantia-ink">Entrenamientos recientes</h3>
            <div class="mt-4 space-y-3">
                @forelse ($monitor['jobs_recientes'] ?? [] as $job)
                    <div class="flex items-center justify-between gap-3 rounded-lg bg-white p-3 ring-1 ring-atlantia-rose/15">
                        <div>
                            <p class="font-bold text-atlantia-ink">{{ $job->modelo_nombre }}</p>
                            <p class="text-xs text-atlantia-ink/55">{{ optional($job->created_at)->format('d/m/Y H:i') }}</p>
                        </div>
                        <span class="rounded-full bg-atlantia-blush px-3 py-1 text-xs font-bold text-atlantia-wine">
                            {{ str_replace('_', ' ', $job->estado) }}
                        </span>
                    </div>
                @empty
                    <p class="rounded-lg bg-atlantia-blush px-4 py-6 text-center text-sm text-atlantia-ink/65">
                        Sin entrenamientos recientes para mostrar.
                    </p>
                @endforelse
            </div>
        </article>
    </div>
</section>
