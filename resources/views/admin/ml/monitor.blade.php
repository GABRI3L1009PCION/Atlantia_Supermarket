@extends(auth()->user()?->isSuperAdmin() && request()->routeIs('admin.*') ? 'layouts.super-admin' : 'layouts.app')

@section('content')
    @php
        $mlAutomationActive = (bool) config('atlantia.features.advanced_ml_automation');
        $threshold = $filters['drift_threshold'] ?? '0.25';

        $modelName = fn (?string $name): string => match ($name) {
            'demand_forecast_prophet' => 'Pronostico de demanda',
            'product_recommendation_hybrid' => 'Recomendador de productos',
            'fraud_review_xgboost' => 'Revision antifraude',
            default => $name ? Illuminate\Support\Str::headline(str_replace('_', ' ', $name)) : 'Modelo sin nombre',
        };

        $modelStatus = fn (?string $status): string => match ($status) {
            'production' => 'En produccion',
            'staging' => 'En pruebas',
            'archived' => 'Archivado',
            'deprecated' => 'Obsoleto',
            default => $status ? Illuminate\Support\Str::headline(str_replace('_', ' ', $status)) : 'Sin estado',
        };

        $jobStatus = fn (?string $status): string => match ($status) {
            'queued' => 'En cola',
            'running' => 'En proceso',
            'completed' => 'Completado',
            'failed' => 'Fallido',
            'cancelled' => 'Cancelado',
            default => $status ? Illuminate\Support\Str::headline(str_replace('_', ' ', $status)) : 'Sin estado',
        };

        $logStatus = fn (?string $status): string => match ($status) {
            'success' => 'Exitoso',
            'failed' => 'Fallido',
            'error' => 'Error',
            default => $status ? Illuminate\Support\Str::headline(str_replace('_', ' ', $status)) : 'Sin estado',
        };

        $endpointName = fn (?string $endpoint): string => match ($endpoint) {
            '/predict/demand' => 'Prediccion de demanda',
            '/recommend/products' => 'Recomendacion de productos',
            '/fraud/review' => 'Revision antifraude',
            '/fraud/order' => 'Analisis de pedido sospechoso',
            default => $endpoint ?: 'Servicio ML',
        };

        $statCards = [
            ['label' => 'En produccion', 'value' => $monitor['modelos_produccion'], 'tone' => 'emerald', 'icon' => 'database'],
            ['label' => 'En pruebas', 'value' => $monitor['modelos_staging'], 'tone' => 'sky', 'icon' => 'flask'],
            ['label' => 'Procesos activos', 'value' => $monitor['jobs_activos'], 'tone' => 'violet', 'icon' => 'play'],
            ['label' => 'Fallidos 24h', 'value' => $monitor['jobs_fallidos_24h'], 'tone' => 'rose', 'icon' => 'alert'],
            ['label' => 'Cambio alto', 'value' => $monitor['drift_alto'], 'tone' => 'amber', 'icon' => 'trend'],
            ['label' => 'Tiempo promedio', 'value' => $monitor['latencia_promedio_ms'] . ' ms', 'tone' => 'slate', 'icon' => 'clock'],
            ['label' => 'Llamadas fallidas 24h', 'value' => $monitor['llamadas_fallidas_24h'], 'tone' => 'pink', 'icon' => 'phone'],
        ];

        $toneClasses = [
            'emerald' => ['box' => 'bg-emerald-50 text-emerald-700', 'text' => 'text-emerald-700'],
            'sky' => ['box' => 'bg-sky-50 text-sky-700', 'text' => 'text-sky-700'],
            'violet' => ['box' => 'bg-violet-50 text-violet-700', 'text' => 'text-violet-700'],
            'rose' => ['box' => 'bg-rose-50 text-rose-700', 'text' => 'text-rose-700'],
            'amber' => ['box' => 'bg-amber-50 text-amber-700', 'text' => 'text-amber-700'],
            'slate' => ['box' => 'bg-slate-100 text-slate-700', 'text' => 'text-slate-700'],
            'pink' => ['box' => 'bg-pink-50 text-pink-700', 'text' => 'text-pink-700'],
        ];
    @endphp

    <section class="-mx-4 -my-6 min-h-[calc(100vh-4rem)] bg-[#fff8fb] px-4 py-6 sm:-mx-6 sm:px-6 lg:-mx-8 lg:px-8">
        <div class="mx-auto max-w-[1500px] space-y-5">
            <div class="grid gap-5 lg:grid-cols-[minmax(0,1fr)_minmax(320px,410px)] lg:items-center">
                <div>
                    <p class="text-sm font-black uppercase tracking-[0.08em] text-atlantia-wine">Atlantia Supermarket</p>
                    <h1 class="mt-2 text-4xl font-black leading-tight text-atlantia-wine md:text-5xl">Monitor ML</h1>
                    <p class="mt-3 max-w-xl text-base leading-7 text-atlantia-ink/65">
                        Estado de modelos inteligentes, cambios en los datos, entrenamiento y velocidad del servicio.
                    </p>
                </div>

                <div class="self-start rounded-2xl border border-emerald-100 bg-white p-5 shadow-[0_18px_45px_rgba(37,27,35,0.08)]">
                    <div class="flex items-center gap-4">
                        <span class="grid h-14 w-14 shrink-0 place-items-center rounded-full bg-emerald-50 text-lg font-black text-emerald-700">ML</span>
                        <div class="min-w-0 flex-1">
                            <p class="text-sm font-black uppercase tracking-[0.12em] {{ $mlAutomationActive ? 'text-emerald-700' : 'text-atlantia-wine' }}">
                                {{ $mlAutomationActive ? 'ML activo' : 'ML pendiente' }}
                            </p>
                            <p class="mt-1 text-sm font-semibold text-atlantia-ink">Automatizacion inteligente</p>
                        </div>
                        <span class="h-3 w-3 rounded-full {{ $mlAutomationActive ? 'bg-emerald-500' : 'bg-atlantia-rose' }}"></span>
                    </div>

                    @unless ($mlAutomationActive)
                        <form method="POST" action="{{ route('admin.ml.activate') }}" class="mt-4">
                            @csrf
                            <button type="submit" class="w-full rounded-xl bg-atlantia-wine px-4 py-3 text-sm font-black text-white shadow-lg shadow-atlantia-wine/15 transition hover:bg-atlantia-wine/90">
                                Activar ML
                            </button>
                        </form>
                    @endunless
                </div>
            </div>

            <div class="grid gap-5 xl:grid-cols-[minmax(0,1fr)_420px]">
                <div class="rounded-2xl border border-emerald-100 bg-white p-6 shadow-[0_18px_45px_rgba(37,27,35,0.07)] xl:border-l-4 xl:border-l-emerald-500">
                    <div class="grid gap-5 lg:grid-cols-[86px_minmax(0,1fr)] 2xl:grid-cols-[96px_minmax(0,1fr)_240px] 2xl:items-center">
                        <div class="grid h-20 w-20 place-items-center rounded-full bg-emerald-50 text-emerald-700 lg:h-24 lg:w-24">
                            <svg class="h-10 w-10 lg:h-12 lg:w-12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <path d="M9 3a3 3 0 0 0-3 3v1a3 3 0 0 0 0 6v1a3 3 0 0 0 3 3" />
                                    <path d="M15 3a3 3 0 0 1 3 3v1a3 3 0 0 1 0 6v1a3 3 0 0 1-3 3" />
                                    <path d="M9 7h6M9 12h6M9 17h6" />
                                    <path d="M4 9H2m20 0h-2M4 15H2m20 0h-2" />
                            </svg>
                        </div>
                        <div class="min-w-0">
                            <p class="text-xs font-black uppercase tracking-[0.14em] text-emerald-700">Estado del aprendizaje automatico</p>
                            <h2 class="mt-2 max-w-2xl text-xl font-black leading-snug text-atlantia-ink lg:text-2xl">
                                {{ $mlAutomationActive ? 'ML esta activo y listo para trabajar' : 'Activa ML para habilitar funciones inteligentes' }}
                            </h2>
                            <p class="mt-2 max-w-3xl text-sm leading-6 text-atlantia-ink/60">
                                {{ $mlAutomationActive
                                    ? 'El sistema puede usar recomendaciones, predicciones de demanda, alertas inteligentes y seguimiento de modelos cuando los servicios esten disponibles.'
                                    : 'Al activarlo, Atlantia habilita demanda, reabasto, recomendaciones, fraude y seguimiento de cambios en los datos.' }}
                            </p>
                        </div>

                        <div class="rounded-2xl border border-atlantia-rose/15 bg-white px-5 py-4 shadow-sm lg:col-start-2 2xl:col-start-auto">
                            <div class="flex items-center gap-4">
                                <span class="grid h-12 w-12 place-items-center rounded-full bg-emerald-50 text-emerald-700">
                                    <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                        <path d="M12 15.5a3.5 3.5 0 1 0 0-7 3.5 3.5 0 0 0 0 7Z" />
                                        <path d="M19.4 15a1.7 1.7 0 0 0 .3 1.9l.1.1a2 2 0 0 1-2.8 2.8l-.1-.1a1.7 1.7 0 0 0-1.9-.3 1.7 1.7 0 0 0-1 1.6V21a2 2 0 0 1-4 0v-.1a1.7 1.7 0 0 0-1-1.6 1.7 1.7 0 0 0-1.9.3l-.1.1a2 2 0 0 1-2.8-2.8l.1-.1a1.7 1.7 0 0 0 .3-1.9 1.7 1.7 0 0 0-1.6-1H3a2 2 0 0 1 0-4h.1a1.7 1.7 0 0 0 1.6-1 1.7 1.7 0 0 0-.3-1.9l-.1-.1a2 2 0 1 1 2.8-2.8l.1.1a1.7 1.7 0 0 0 1.9.3h.1a1.7 1.7 0 0 0 .9-1.5V3a2 2 0 0 1 4 0v.1a1.7 1.7 0 0 0 1 1.6 1.7 1.7 0 0 0 1.9-.3l.1-.1a2 2 0 1 1 2.8 2.8l-.1.1a1.7 1.7 0 0 0-.3 1.9v.1a1.7 1.7 0 0 0 1.5.9H21a2 2 0 0 1 0 4h-.1a1.7 1.7 0 0 0-1.5.9Z" />
                                    </svg>
                                </span>
                                <div>
                                    <p class="text-xs font-black uppercase tracking-[0.14em] text-atlantia-ink/50">Configuracion</p>
                                    <p class="mt-1 text-sm font-black {{ $mlAutomationActive ? 'text-emerald-700' : 'text-atlantia-wine' }}">
                                        {{ $mlAutomationActive ? 'Activado' : 'Desactivado' }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="rounded-2xl border border-atlantia-rose/15 bg-white p-6 shadow-[0_18px_45px_rgba(37,27,35,0.07)]">
                    <p class="text-sm font-black uppercase tracking-[0.14em] text-atlantia-wine">Umbral de alerta</p>
                    <form method="GET" class="mt-6 grid gap-4 sm:grid-cols-[1fr_auto] xl:grid-cols-[160px_1fr]">
                        <input
                            type="number"
                            step="0.01"
                            min="0"
                            max="1"
                            name="drift_threshold"
                            value="{{ $threshold }}"
                            class="h-14 rounded-xl border border-atlantia-rose/25 bg-white px-4 text-base font-semibold text-atlantia-ink outline-none transition focus:border-atlantia-wine focus:ring-4 focus:ring-atlantia-rose/10"
                            aria-label="Umbral de cambio en datos"
                        >
                        <button type="submit" class="h-14 rounded-xl bg-atlantia-wine px-6 text-sm font-black text-white shadow-lg shadow-atlantia-wine/15 transition hover:bg-atlantia-wine/90">
                            Actualizar umbral
                        </button>
                    </form>
                </div>
            </div>

            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3 2xl:grid-cols-7">
                @foreach ($statCards as $card)
                    @php($tone = $toneClasses[$card['tone']])
                    <div class="rounded-2xl border border-atlantia-rose/10 bg-white p-4 shadow-[0_14px_32px_rgba(37,27,35,0.06)]">
                        <div class="flex items-center gap-4">
                            <span class="grid h-12 w-12 shrink-0 place-items-center rounded-full {{ $tone['box'] }}">
                                @switch($card['icon'])
                                    @case('database')
                                        <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><ellipse cx="12" cy="5" rx="7" ry="3" /><path d="M5 5v6c0 1.7 3.1 3 7 3s7-1.3 7-3V5" /><path d="M5 11v6c0 1.7 3.1 3 7 3s7-1.3 7-3v-6" /></svg>
                                        @break
                                    @case('flask')
                                        <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M9 3h6" /><path d="M10 3v6l-5 8a3 3 0 0 0 2.6 4.5h8.8A3 3 0 0 0 19 17l-5-8V3" /><path d="M7 16h10" /></svg>
                                        @break
                                    @case('play')
                                        <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m8 5 11 7-11 7V5Z" /></svg>
                                        @break
                                    @case('alert')
                                        <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m12 3 9 16H3l9-16Z" /><path d="M12 9v4" /><path d="M12 17h.01" /></svg>
                                        @break
                                    @case('trend')
                                        <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m3 17 6-6 4 4 8-8" /><path d="M14 7h7v7" /></svg>
                                        @break
                                    @case('clock')
                                        <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="9" /><path d="M12 7v5l3 2" /></svg>
                                        @break
                                    @default
                                        <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M22 16.9v3a2 2 0 0 1-2.2 2 19.8 19.8 0 0 1-8.6-3.1 19.5 19.5 0 0 1-6-6A19.8 19.8 0 0 1 2.1 4.2 2 2 0 0 1 4.1 2h3a2 2 0 0 1 2 1.7c.1 1 .4 2 .7 2.9a2 2 0 0 1-.5 2.1L8.1 9.9a16 16 0 0 0 6 6l1.2-1.2a2 2 0 0 1 2.1-.5c.9.3 1.9.6 2.9.7a2 2 0 0 1 1.7 2Z" /></svg>
                                @endswitch
                            </span>
                            <div class="min-w-0">
                                <p class="text-sm font-semibold leading-tight text-atlantia-ink/70">{{ $card['label'] }}</p>
                                <p class="mt-1 text-2xl font-black leading-tight {{ $tone['text'] }}">{{ $card['value'] }}</p>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="grid gap-5 xl:grid-cols-3">
                <article class="rounded-2xl border border-atlantia-rose/10 bg-white p-5 shadow-[0_14px_32px_rgba(37,27,35,0.06)]">
                    <h2 class="flex items-center gap-3 text-xl font-black text-atlantia-wine">
                        <span class="grid h-9 w-9 place-items-center rounded-lg bg-atlantia-blush text-atlantia-wine">
                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m21 16-9 5-9-5" /><path d="m21 12-9 5-9-5" /><path d="m12 3 9 5-9 5-9-5 9-5Z" /></svg>
                        </span>
                        Modelos recientes
                    </h2>

                    <div class="mt-5 space-y-3">
                        @forelse ($monitor['modelos_recientes'] as $modelo)
                            <div class="flex gap-3">
                                <span class="mt-2 h-2 w-2 rounded-full bg-emerald-500"></span>
                                <div class="min-w-0">
                                    <p class="font-black text-atlantia-ink">{{ $modelName($modelo->nombre_modelo) }} {{ $modelo->version }}</p>
                                    <p class="text-sm text-atlantia-ink/60">
                                        {{ $modelStatus($modelo->estado) }}
                                        <span class="mx-1">-</span>
                                        {{ $modelo->fecha_entrenamiento?->format('d/m/Y H:i') ?? 'Sin fecha' }}
                                    </p>
                                </div>
                            </div>
                        @empty
                            <p class="rounded-xl border border-dashed border-atlantia-rose/25 p-4 text-sm font-semibold text-atlantia-ink/55">
                                Aun no hay modelos registrados.
                            </p>
                        @endforelse
                    </div>
                </article>

                <article class="rounded-2xl border border-atlantia-rose/10 bg-white p-5 shadow-[0_14px_32px_rgba(37,27,35,0.06)]">
                    <h2 class="flex items-center gap-3 text-xl font-black text-atlantia-wine">
                        <span class="grid h-9 w-9 place-items-center rounded-lg bg-atlantia-blush text-atlantia-wine">
                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M7 7h10v10H7z" /><path d="M4 12h3M17 12h3M12 4v3M12 17v3" /></svg>
                        </span>
                        Procesos recientes
                    </h2>

                    <div class="mt-5 space-y-3">
                        @forelse ($monitor['jobs_recientes'] as $job)
                            <div class="flex gap-3">
                                <span class="mt-2 h-2 w-2 rounded-full {{ $job->estado === 'failed' ? 'bg-rose-500' : 'bg-emerald-500' }}"></span>
                                <div class="min-w-0">
                                    <p class="font-black text-atlantia-ink">{{ $modelName($job->modelo_nombre) }}</p>
                                    <p class="text-sm text-atlantia-ink/60">
                                        {{ $jobStatus($job->estado) }}
                                        <span class="mx-1">-</span>
                                        datos analizados: {{ number_format((int) ($job->dataset_size ?? 0)) }}
                                    </p>
                                </div>
                            </div>
                        @empty
                            <p class="rounded-xl border border-dashed border-atlantia-rose/25 p-4 text-sm font-semibold text-atlantia-ink/55">
                                Aun no hay procesos registrados.
                            </p>
                        @endforelse
                    </div>
                </article>

                <article class="rounded-2xl border border-atlantia-rose/10 bg-white p-5 shadow-[0_14px_32px_rgba(37,27,35,0.06)]">
                    <h2 class="flex items-center gap-3 text-xl font-black text-atlantia-wine">
                        <span class="grid h-9 w-9 place-items-center rounded-lg bg-atlantia-blush text-atlantia-wine">
                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M22 16.9v3a2 2 0 0 1-2.2 2 19.8 19.8 0 0 1-8.6-3.1 19.5 19.5 0 0 1-6-6A19.8 19.8 0 0 1 2.1 4.2 2 2 0 0 1 4.1 2h3a2 2 0 0 1 2 1.7c.1 1 .4 2 .7 2.9a2 2 0 0 1-.5 2.1L8.1 9.9a16 16 0 0 0 6 6l1.2-1.2a2 2 0 0 1 2.1-.5c.9.3 1.9.6 2.9.7a2 2 0 0 1 1.7 2Z" /></svg>
                        </span>
                        Llamadas recientes al servicio
                    </h2>

                    <div class="mt-5 space-y-3">
                        @forelse ($monitor['logs_recientes'] as $log)
                            <div class="grid grid-cols-[1fr_auto_auto] items-center gap-3">
                                <div class="min-w-0">
                                    <p class="truncate font-black text-atlantia-ink">{{ $endpointName($log->endpoint) }}</p>
                                    <p class="truncate text-sm text-atlantia-ink/55">{{ $log->endpoint }}</p>
                                </div>
                                <span class="rounded-lg px-3 py-1 text-xs font-black {{ $log->estado === 'success' ? 'bg-emerald-50 text-emerald-700' : 'bg-rose-50 text-rose-700' }}">
                                    {{ $logStatus($log->estado) }}
                                </span>
                                <span class="text-sm font-semibold text-atlantia-ink/70">{{ (int) ($log->latencia_ms ?? 0) }} ms</span>
                            </div>
                        @empty
                            <p class="rounded-xl border border-dashed border-atlantia-rose/25 p-4 text-sm font-semibold text-atlantia-ink/55">
                                Aun no hay llamadas registradas.
                            </p>
                        @endforelse
                    </div>
                </article>
            </div>

            <div class="rounded-2xl border border-atlantia-rose/10 bg-white p-5 shadow-[0_14px_32px_rgba(37,27,35,0.06)]">
                <h2 class="flex items-center gap-3 text-xl font-black text-atlantia-wine">
                    <span class="grid h-9 w-9 place-items-center rounded-lg bg-atlantia-blush text-atlantia-wine">
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 3v18h18" /><path d="M7 14v3M12 10v7M17 6v11" /></svg>
                    </span>
                    Metricas recientes
                </h2>

                <div class="mt-4 overflow-x-auto">
                    <table class="min-w-full text-left text-sm">
                        <thead>
                            <tr class="border-b border-atlantia-rose/15 text-xs font-black uppercase tracking-[0.05em] text-atlantia-ink/55">
                                <th class="whitespace-nowrap px-3 py-3">Modelo</th>
                                <th class="whitespace-nowrap px-3 py-3">Fecha</th>
                                <th class="whitespace-nowrap px-3 py-3">MAPE</th>
                                <th class="whitespace-nowrap px-3 py-3">RMSE</th>
                                <th class="whitespace-nowrap px-3 py-3">R2</th>
                                <th class="whitespace-nowrap px-3 py-3">Cambio</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-atlantia-rose/10">
                            @forelse ($monitor['metricas_recientes'] as $metrica)
                                <tr>
                                    <td class="px-3 py-3 font-bold text-atlantia-ink">{{ $modelName($metrica->modeloVersion?->nombre_modelo) }}</td>
                                    <td class="whitespace-nowrap px-3 py-3 text-atlantia-ink/70">{{ $metrica->fecha?->format('d/m/Y') ?? '--' }}</td>
                                    <td class="whitespace-nowrap px-3 py-3 text-atlantia-ink/70">{{ $metrica->mape ?? '--' }}</td>
                                    <td class="whitespace-nowrap px-3 py-3 text-atlantia-ink/70">{{ $metrica->rmse ?? '--' }}</td>
                                    <td class="whitespace-nowrap px-3 py-3 text-atlantia-ink/70">{{ $metrica->r2 ?? '--' }}</td>
                                    <td class="whitespace-nowrap px-3 py-3 font-black {{ (float) $metrica->drift_score > (float) $threshold ? 'text-rose-600' : 'text-emerald-600' }}">
                                        {{ $metrica->drift_score === null ? '--' : sprintf('%+.4f', (float) $metrica->drift_score) }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-3 py-10 text-center text-sm font-semibold text-atlantia-ink/55">
                                        Aun no hay metricas registradas para modelos ML.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </section>
@endsection
