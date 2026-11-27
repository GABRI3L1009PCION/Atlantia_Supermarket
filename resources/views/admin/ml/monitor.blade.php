@extends(auth()->user()?->isSuperAdmin() && request()->routeIs('admin.*') ? 'layouts.super-admin' : 'layouts.app')

@section('content')
    @php
        $mlActivo = (bool) config('atlantia.features.advanced_ml_automation');
        $umbral = (string) ($filters['drift_threshold'] ?? '0.25');

        $modelos = [
            'demand_forecast_prophet' => ['Pronóstico de demanda', 'Calcula cuánto se va a vender para sugerir reabasto.'],
            'product_recommendation_hybrid' => ['Recomendador de productos', 'Sugiere productos a cada cliente.'],
            'fraud_review_xgboost' => ['Detector de fraude', 'Revisa pedidos sospechosos.'],
        ];
        $nombreModelo = fn (?string $nombre): string => $modelos[$nombre][0] ?? ($nombre ? \Illuminate\Support\Str::headline($nombre) : 'Modelo sin nombre');
        $estadoModelo = fn (?string $estado): array => match ($estado) {
            'production' => ['En uso', 'bg-emerald-50 text-emerald-800 ring-emerald-200'],
            'staging' => ['En pruebas', 'bg-sky-50 text-sky-800 ring-sky-200'],
            'archived' => ['Archivado', 'bg-slate-100 text-slate-600 ring-slate-200'],
            'deprecated' => ['Retirado', 'bg-slate-100 text-slate-600 ring-slate-200'],
            default => [$estado ? ucfirst($estado) : 'Sin estado', 'bg-slate-100 text-slate-600 ring-slate-200'],
        };
        $estadoProceso = fn (?string $estado): array => match ($estado) {
            'queued' => ['En cola', 'text-sky-700'],
            'running' => ['Entrenando', 'text-violet-700'],
            'completed' => ['Completado', 'text-emerald-700'],
            'failed' => ['Falló', 'text-red-700'],
            'cancelled' => ['Cancelado', 'text-slate-500'],
            default => [$estado ? ucfirst($estado) : 'Sin estado', 'text-slate-500'],
        };
        $servicio = fn (?string $endpoint): string => match ($endpoint) {
            '/predict/demand' => 'Predicción de demanda',
            '/recommend/products' => 'Recomendación de productos',
            '/fraud/review' => 'Revisión antifraude',
            '/fraud/orders', '/fraud/order' => 'Análisis de pedido sospechoso',
            '/restock/suggest' => 'Sugerencia de reabasto',
            default => $endpoint ?: 'Servicio de IA',
        };

        $problemas = collect([
            $monitor['jobs_fallidos_24h'] > 0 ? $monitor['jobs_fallidos_24h'].' '.($monitor['jobs_fallidos_24h'] === 1 ? 'entrenamiento falló' : 'entrenamientos fallaron').' en las últimas 24 h' : null,
            $monitor['llamadas_fallidas_24h'] > 0 ? $monitor['llamadas_fallidas_24h'].' '.($monitor['llamadas_fallidas_24h'] === 1 ? 'consulta falló' : 'consultas fallaron').' en las últimas 24 h' : null,
            $monitor['drift_alto'] > 0 ? $monitor['drift_alto'].' '.($monitor['drift_alto'] === 1 ? 'medición muestra' : 'mediciones muestran').' que los datos cambiaron mucho: conviene reentrenar' : null,
        ])->filter();

        $tarjeta = 'rounded-2xl border border-atlantia-rose/15 bg-white shadow-[0_8px_20px_rgba(59,6,12,0.04)]';
        $campo = 'h-8 rounded-lg border border-atlantia-rose/30 bg-white px-2 text-xs font-bold focus:border-atlantia-wine focus:outline-none';
    @endphp

    <section class="mx-auto max-w-7xl space-y-4 pb-6 text-atlantia-ink">
        {{-- Encabezado --}}
        <header class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h1 class="text-3xl font-black tracking-tight">Monitor de inteligencia artificial</h1>
                <p class="mt-0.5 text-sm text-atlantia-ink/60">Cómo están funcionando los modelos que predicen la demanda, recomiendan productos y detectan fraude.</p>
            </div>
            <div class="flex items-center gap-2">
                @if ($mlActivo)
                    <span class="inline-flex items-center gap-2 rounded-full border border-emerald-200 bg-emerald-50 px-3 py-1.5 text-xs font-black text-emerald-800">
                        <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span> IA activada
                    </span>
                @else
                    <form method="POST" action="{{ route('admin.ml.activate') }}" data-confirm="¿Activar la inteligencia artificial? Se habilitan predicciones de demanda, reabasto, recomendaciones y revisión de fraude.">
                        @csrf
                        <button class="inline-flex items-center gap-2 rounded-xl bg-atlantia-wine px-4 py-2.5 text-sm font-black text-white hover:bg-atlantia-wine-700">Activar IA</button>
                    </form>
                @endif
                <a href="{{ route('admin.ml.reentrenamiento.index') }}" class="rounded-xl border border-atlantia-rose/25 bg-white px-4 py-2.5 text-sm font-black text-atlantia-wine hover:bg-atlantia-blush">Reentrenar modelos</a>
            </div>
        </header>

        {{-- Estado general en una línea --}}
        @if (! $mlActivo)
            <p class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-2.5 text-sm text-amber-900"><strong>La IA está apagada.</strong> La tienda funciona igual, pero sin recomendaciones, pronósticos ni revisión automática de fraude.</p>
        @elseif ($problemas->isEmpty())
            <p class="flex items-center gap-2 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-2.5 text-sm text-emerald-900">
                <span class="grid h-5 w-5 place-items-center rounded-full bg-emerald-600 text-[11px] font-black text-white">✓</span>
                <strong>Todo funcionando bien.</strong> Sin fallas en las últimas 24 horas.
            </p>
        @else
            <div class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-2.5 text-sm text-amber-900">
                <strong>Necesita atención:</strong> {{ $problemas->join('; ', ' y ') }}.
            </div>
        @endif

        {{-- Indicadores --}}
        <div class="grid grid-cols-2 gap-3 md:grid-cols-3 xl:grid-cols-5">
            <article class="{{ $tarjeta }} px-4 py-3">
                <p class="text-[11px] font-black uppercase tracking-wide text-atlantia-ink/50">Modelos en uso</p>
                <p class="mt-0.5 text-3xl font-black {{ $monitor['modelos_produccion'] > 0 ? 'text-emerald-600' : 'text-atlantia-ink/30' }}">{{ $monitor['modelos_produccion'] }}</p>
                <p class="text-xs text-atlantia-ink/55">{{ $monitor['modelos_staging'] }} en pruebas</p>
            </article>
            <article class="{{ $tarjeta }} px-4 py-3">
                <p class="text-[11px] font-black uppercase tracking-wide text-atlantia-ink/50">Entrenando ahora</p>
                <p class="mt-0.5 text-3xl font-black {{ $monitor['jobs_activos'] > 0 ? 'text-violet-600' : 'text-atlantia-ink/30' }}">{{ $monitor['jobs_activos'] }}</p>
                <p @class(['text-xs', 'font-bold text-red-700' => $monitor['jobs_fallidos_24h'] > 0, 'text-atlantia-ink/55' => $monitor['jobs_fallidos_24h'] === 0])>{{ $monitor['jobs_fallidos_24h'] }} fallidos en 24 h</p>
            </article>
            <article class="{{ $tarjeta }} px-4 py-3">
                <p class="text-[11px] font-black uppercase tracking-wide text-atlantia-ink/50">Velocidad de respuesta</p>
                <p class="mt-0.5 text-3xl font-black">{{ number_format($monitor['latencia_promedio_ms']) }} <span class="text-base text-atlantia-ink/50">ms</span></p>
                <p class="text-xs text-atlantia-ink/55">{{ $monitor['latencia_promedio_ms'] <= 500 ? 'rápida, el cliente no lo nota' : 'lenta, revisa el servicio' }}</p>
            </article>
            <article @class([$tarjeta, 'px-4 py-3', '!border-red-300 !bg-red-50/60' => $monitor['llamadas_fallidas_24h'] > 0])>
                <p class="text-[11px] font-black uppercase tracking-wide text-atlantia-ink/50">Consultas fallidas</p>
                <p class="mt-0.5 text-3xl font-black {{ $monitor['llamadas_fallidas_24h'] > 0 ? 'text-red-600' : 'text-atlantia-ink/30' }}">{{ $monitor['llamadas_fallidas_24h'] }}</p>
                <p class="text-xs text-atlantia-ink/55">en las últimas 24 h</p>
            </article>
            <article @class([$tarjeta, 'col-span-2 px-4 py-3 md:col-span-1', '!border-amber-300 !bg-amber-50/60' => $monitor['drift_alto'] > 0])>
                <div class="flex items-start justify-between gap-2">
                    <p class="text-[11px] font-black uppercase tracking-wide text-atlantia-ink/50">Datos que cambiaron</p>
                    <form method="GET" title="Qué tanto deben cambiar los datos para avisarte">
                        <select name="drift_threshold" data-autosubmit class="{{ $campo }}" aria-label="Sensibilidad del aviso">
                            @foreach (['0.1' => 'Muy sensible', '0.25' => 'Normal', '0.5' => 'Poco sensible'] as $valor => $texto)
                                <option value="{{ $valor }}" @selected((float) $umbral === (float) $valor)>{{ $texto }}</option>
                            @endforeach
                        </select>
                        <button class="sr-only">Aplicar</button>
                    </form>
                </div>
                <p class="mt-0.5 text-3xl font-black {{ $monitor['drift_alto'] > 0 ? 'text-amber-600' : 'text-atlantia-ink/30' }}">{{ $monitor['drift_alto'] }}</p>
                <p class="text-xs text-atlantia-ink/55">si sube, conviene reentrenar</p>
            </article>
        </div>

        {{-- Detalle en tres columnas --}}
        <div class="grid gap-3 lg:grid-cols-3">
            <article class="{{ $tarjeta }} flex flex-col p-4">
                <h2 class="text-sm font-black">Modelos</h2>
                <ul class="mt-2 max-h-64 flex-1 divide-y divide-atlantia-rose/10 overflow-y-auto">
                    @forelse ($monitor['modelos_recientes'] as $modelo)
                        @php
                            [$estadoTexto, $estadoClase] = $estadoModelo($modelo->estado);
                        @endphp
                        <li class="flex items-center justify-between gap-2 py-2">
                            <div class="min-w-0">
                                <p class="truncate text-sm font-black">{{ $nombreModelo($modelo->nombre_modelo) }} <span class="font-bold text-atlantia-ink/40">v{{ ltrim((string) $modelo->version, 'v') }}</span></p>
                                <p class="text-xs text-atlantia-ink/50">entrenado {{ $modelo->fecha_entrenamiento?->format('d/m/Y') ?? 'sin fecha' }}</p>
                            </div>
                            <span class="shrink-0 rounded-full px-2 py-0.5 text-[11px] font-black ring-1 {{ $estadoClase }}">{{ $estadoTexto }}</span>
                        </li>
                    @empty
                        <li class="py-4 text-center text-xs text-atlantia-ink/55">Aún no hay modelos registrados.</li>
                    @endforelse
                </ul>
            </article>

            <article class="{{ $tarjeta }} flex flex-col p-4">
                <h2 class="text-sm font-black">Entrenamientos recientes</h2>
                <ul class="mt-2 max-h-64 flex-1 divide-y divide-atlantia-rose/10 overflow-y-auto">
                    @forelse ($monitor['jobs_recientes'] as $job)
                        @php
                            [$procesoTexto, $procesoClase] = $estadoProceso($job->estado);
                        @endphp
                        <li class="flex items-center justify-between gap-2 py-2">
                            <div class="min-w-0">
                                <p class="truncate text-sm font-black">{{ $nombreModelo($job->modelo_nombre) }}</p>
                                <p class="text-xs text-atlantia-ink/50">{{ number_format((int) ($job->dataset_size ?? 0)) }} registros · {{ $job->created_at?->diffForHumans() }}</p>
                            </div>
                            <span class="shrink-0 text-xs font-black {{ $procesoClase }}">{{ $procesoTexto }}</span>
                        </li>
                    @empty
                        <li class="py-4 text-center text-xs text-atlantia-ink/55">Aún no hay entrenamientos.</li>
                    @endforelse
                </ul>
            </article>

            <article class="{{ $tarjeta }} flex flex-col p-4">
                <h2 class="text-sm font-black">Últimas consultas al servicio</h2>
                <ul class="mt-2 max-h-64 flex-1 divide-y divide-atlantia-rose/10 overflow-y-auto">
                    @forelse ($monitor['logs_recientes'] as $log)
                        <li class="flex items-center justify-between gap-2 py-2">
                            <div class="min-w-0">
                                <p class="truncate text-sm font-black">{{ $servicio($log->endpoint) }}</p>
                                <p class="text-xs text-atlantia-ink/50">{{ $log->created_at?->diffForHumans() }} · {{ (int) ($log->latencia_ms ?? 0) }} ms</p>
                            </div>
                            <span @class([
                                'shrink-0 text-xs font-black',
                                'text-emerald-700' => $log->estado === 'success',
                                'text-red-700' => $log->estado !== 'success',
                            ])>{{ $log->estado === 'success' ? 'Bien' : 'Falló' }}</span>
                        </li>
                    @empty
                        <li class="py-4 text-center text-xs text-atlantia-ink/55">Aún no hay consultas registradas.</li>
                    @endforelse
                </ul>
            </article>
        </div>

        {{-- Métricas técnicas: plegadas para no saturar --}}
        <details class="{{ $tarjeta }} group overflow-hidden">
            <summary class="flex cursor-pointer list-none items-center justify-between gap-3 px-4 py-3 text-sm font-black">
                <span>Métricas técnicas <span class="font-bold text-atlantia-ink/45">· para revisar la precisión de los modelos</span></span>
                <span class="text-atlantia-wine transition group-open:rotate-180" aria-hidden="true">▾</span>
            </summary>
            <div class="overflow-x-auto border-t border-atlantia-rose/10">
                <table class="w-full text-sm">
                    <thead class="bg-atlantia-blush/40 text-left text-[11px] font-black uppercase tracking-wide text-atlantia-ink/55">
                        <tr>
                            <th class="px-4 py-2.5">Modelo</th>
                            <th class="px-3 py-2.5">Fecha</th>
                            <th class="px-3 py-2.5" title="Error porcentual promedio: mientras más bajo, mejor">Error % (MAPE)</th>
                            <th class="px-3 py-2.5" title="Error típico en unidades: mientras más bajo, mejor">Error (RMSE)</th>
                            <th class="px-3 py-2.5" title="Qué tan bien explica los datos: mientras más cerca de 1, mejor">Ajuste (R²)</th>
                            <th class="px-3 py-2.5" title="Cuánto cambiaron los datos frente al entrenamiento">Cambio en datos</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-atlantia-rose/10">
                        @forelse ($monitor['metricas_recientes'] as $metrica)
                            <tr>
                                <td class="px-4 py-2 font-bold">{{ $nombreModelo($metrica->modeloVersion?->nombre_modelo) }}</td>
                                <td class="whitespace-nowrap px-3 py-2 text-atlantia-ink/70">{{ $metrica->fecha?->format('d/m/Y') ?? '—' }}</td>
                                <td class="px-3 py-2 text-atlantia-ink/70">{{ $metrica->mape ?? '—' }}</td>
                                <td class="px-3 py-2 text-atlantia-ink/70">{{ $metrica->rmse ?? '—' }}</td>
                                <td class="px-3 py-2 text-atlantia-ink/70">{{ $metrica->r2 ?? '—' }}</td>
                                <td class="whitespace-nowrap px-3 py-2 font-black {{ (float) $metrica->drift_score > (float) $umbral ? 'text-amber-700' : 'text-emerald-700' }}">
                                    {{ $metrica->drift_score === null ? '—' : sprintf('%+.4f', (float) $metrica->drift_score) }}
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="px-4 py-6 text-center text-xs text-atlantia-ink/55">Aún no hay métricas registradas.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </details>
    </section>
@endsection
