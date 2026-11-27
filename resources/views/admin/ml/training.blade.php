@extends(auth()->user()?->isSuperAdmin() && request()->routeIs('admin.*') ? 'layouts.super-admin' : 'layouts.app')

@section('content')
    @php
        $modelos = [
            'demand_forecast' => ['Pronóstico de demanda', 'Calcula cuánto se va a vender de cada producto.'],
            'restock_suggestion' => ['Sugerencia de reabasto', 'Propone qué y cuánto comprar.'],
            'product_recommendation' => ['Recomendador de productos', 'Sugiere productos a cada cliente.'],
            'fraud_detection' => ['Detección de fraude', 'Revisa pedidos sospechosos.'],
            'review_nlp' => ['Análisis de reseñas', 'Detecta reseñas falsas o spam.'],
        ];
        $alias = [
            'demand_forecast_prophet' => 'demand_forecast',
            'product_recommendation_hybrid' => 'product_recommendation',
            'fraud_review_xgboost' => 'fraud_detection',
        ];
        $nombreModelo = fn (?string $nombre): string => $modelos[$alias[$nombre] ?? $nombre][0] ?? ($nombre ? \Illuminate\Support\Str::headline($nombre) : 'Modelo sin nombre');
        $estados = [
            'queued' => ['En cola', 'bg-sky-50 text-sky-800 ring-sky-200'],
            'running' => ['Entrenando', 'bg-violet-50 text-violet-800 ring-violet-200'],
            'completed' => ['Completado', 'bg-emerald-50 text-emerald-800 ring-emerald-200'],
            'failed' => ['Falló', 'bg-red-50 text-red-700 ring-red-200'],
            'cancelled' => ['Cancelado', 'bg-slate-100 text-slate-600 ring-slate-200'],
        ];
        $estadoActivo = $filters['estado'] ?? null;
        $motivosRapidos = [
            'Datos cambiaron' => 'Los datos cambiaron y las predicciones ya no son precisas.',
            'Productos nuevos' => 'Se agregaron muchos productos nuevos al catálogo.',
            'Actualización mensual' => 'Actualización mensual con las ventas más recientes.',
        ];
        $hoy = now()->format('Y-m-d');
        $tarjeta = 'rounded-2xl border border-atlantia-rose/15 bg-white shadow-[0_8px_20px_rgba(59,6,12,0.04)]';
        $campo = 'h-9 w-full rounded-lg border border-atlantia-rose/30 bg-white px-3 text-sm focus:border-atlantia-wine focus:outline-none focus:ring-2 focus:ring-atlantia-wine/15';
        $etiqueta = 'mb-1 block text-[11px] font-black uppercase tracking-wide text-atlantia-ink/50';
    @endphp

    <section class="mx-auto max-w-7xl space-y-4 pb-6 text-atlantia-ink">
        {{-- Encabezado --}}
        <header class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h1 class="text-3xl font-black tracking-tight">Reentrenar modelos</h1>
                <p class="mt-0.5 text-sm text-atlantia-ink/60">Vuelve a entrenar un modelo con las ventas más recientes para que sus predicciones sigan siendo precisas.</p>
            </div>
            <a href="{{ route('admin.ml.monitor') }}" class="rounded-xl border border-atlantia-rose/25 bg-white px-4 py-2.5 text-sm font-black text-atlantia-wine hover:bg-atlantia-blush">← Monitor de IA</a>
        </header>

        <div class="grid gap-4 xl:grid-cols-[24rem_1fr]">
            {{-- Formulario --}}
            <form
                method="POST"
                action="{{ route('admin.ml.reentrenamiento.store') }}"
                class="{{ $tarjeta }} self-start p-4"
                data-protect-submit
                x-data="{
                    modelo: @js(old('modelo_nombre', 'demand_forecast')),
                    periodo: '90',
                    inicio: @js(old('fecha_inicio_dataset', now()->subDays(90)->format('Y-m-d'))),
                    fin: @js(old('fecha_fin_dataset', $hoy)),
                    motivo: @js(old('motivo', '')),
                    elegirPeriodo(dias) {
                        this.periodo = dias;
                        if (dias === 'todo') { this.inicio = ''; this.fin = ''; return; }
                        if (dias === 'otro') return;
                        const d = new Date(); d.setDate(d.getDate() - Number(dias));
                        this.inicio = d.toISOString().slice(0, 10);
                        this.fin = @js($hoy);
                    },
                }"
            >
                @csrf
                <h2 class="text-base font-black">Nuevo entrenamiento</h2>

                <fieldset class="mt-3">
                    <legend class="{{ $etiqueta }}">Modelo</legend>
                    <div class="space-y-1.5">
                        @foreach ($modelos as $valor => [$nombre, $descripcion])
                            <label class="flex cursor-pointer items-start gap-2.5 rounded-lg border px-3 py-1.5 transition" :class="modelo === @js($valor) ? 'border-atlantia-wine bg-atlantia-blush/40' : 'border-atlantia-rose/20 hover:bg-atlantia-blush/20'">
                                <input type="radio" name="modelo_nombre" value="{{ $valor }}" x-model="modelo" class="mt-0.5 h-4 w-4 border-atlantia-rose text-atlantia-wine focus:ring-atlantia-wine">
                                <span class="min-w-0">
                                    <span class="block text-sm font-black">{{ $nombre }}</span>
                                    <span class="block text-xs text-atlantia-ink/55">{{ $descripcion }}</span>
                                </span>
                            </label>
                        @endforeach
                    </div>
                </fieldset>

                <div class="mt-3">
                    <span class="{{ $etiqueta }}">Ventas a usar</span>
                    <div class="flex flex-wrap gap-1.5">
                        @foreach (['30' => '30 días', '90' => '90 días', '180' => '6 meses', 'todo' => 'Todo', 'otro' => 'Elegir fechas'] as $valor => $texto)
                            <button type="button" x-on:click="elegirPeriodo(@js($valor))" class="rounded-full px-3 py-1 text-xs font-black transition" :class="periodo === @js($valor) ? 'bg-atlantia-wine text-white' : 'bg-atlantia-blush/50 text-atlantia-wine hover:bg-atlantia-blush'">{{ $texto }}</button>
                        @endforeach
                    </div>
                    <div class="mt-2 grid grid-cols-2 gap-2" :class="{ 'hidden': periodo !== 'otro' }">
                        <input type="date" name="fecha_inicio_dataset" x-model="inicio" max="{{ $hoy }}" class="{{ $campo }}" aria-label="Desde">
                        <input type="date" name="fecha_fin_dataset" x-model="fin" max="{{ $hoy }}" class="{{ $campo }}" aria-label="Hasta">
                    </div>
                </div>

                <label class="mt-3 block">
                    <span class="{{ $etiqueta }}">¿Por qué lo reentrenas?</span>
                    <textarea name="motivo" x-model="motivo" rows="2" required minlength="10" maxlength="1000" placeholder="Mínimo 10 caracteres" class="w-full rounded-lg border border-atlantia-rose/30 px-3 py-2 text-sm focus:border-atlantia-wine focus:outline-none focus:ring-2 focus:ring-atlantia-wine/15"></textarea>
                </label>
                <div class="mt-1 flex flex-wrap gap-1">
                    @foreach ($motivosRapidos as $corto => $rapido)
                        <button type="button" x-on:click="motivo = @js($rapido)" title="{{ $rapido }}" class="rounded-md bg-atlantia-blush/40 px-2 py-0.5 text-[11px] font-bold text-atlantia-wine hover:bg-atlantia-blush">{{ $corto }}</button>
                    @endforeach
                </div>

                <details class="mt-3 rounded-lg border border-atlantia-rose/15 px-3 py-2">
                    <summary class="cursor-pointer text-xs font-black text-atlantia-ink/60">Opciones avanzadas</summary>
                    <div class="mt-2 grid grid-cols-2 gap-2">
                        <label :class="{ 'hidden': modelo !== 'demand_forecast' && modelo !== 'restock_suggestion' }">
                            <span class="{{ $etiqueta }}">Predecir a</span>
                            <select name="parametros[horizonte_dias]" class="{{ $campo }}" :disabled="modelo !== 'demand_forecast' && modelo !== 'restock_suggestion'">
                                <option value="">Por defecto</option>
                                <option value="7">7 días</option>
                                <option value="14">14 días</option>
                                <option value="30">30 días</option>
                            </select>
                        </label>
                        <label>
                            <span class="{{ $etiqueta }}">Pruebas máximas</span>
                            <input name="parametros[max_trials]" type="number" min="1" max="100" placeholder="Auto" class="{{ $campo }}">
                        </label>
                    </div>
                    <input type="hidden" name="usar_staging" value="0">
                    <label class="mt-2 flex items-start gap-2 text-xs">
                        <input type="checkbox" name="usar_staging" value="1" checked class="mt-0.5 h-4 w-4 rounded border-atlantia-rose text-atlantia-wine">
                        <span><strong>Probar antes de usarlo</strong> · el nuevo modelo queda en pruebas y no reemplaza al actual hasta que lo apruebes.</span>
                    </label>
                    <input type="hidden" name="forzar_reentrenamiento" value="0">
                    <label class="mt-1.5 flex items-start gap-2 text-xs">
                        <input type="checkbox" name="forzar_reentrenamiento" value="1" class="mt-0.5 h-4 w-4 rounded border-atlantia-rose text-atlantia-wine">
                        <span><strong>Forzar</strong> · entrenar aunque no haya datos nuevos.</span>
                    </label>
                </details>

                @if ($errors->any())
                    <p class="mt-2 text-xs font-bold text-red-600">{{ $errors->first() }}</p>
                @endif

                <button class="mt-3 h-10 w-full rounded-lg bg-atlantia-wine text-sm font-black text-white hover:bg-atlantia-wine-700">Iniciar entrenamiento</button>
            </form>

            {{-- Historial --}}
            <article class="{{ $tarjeta }} min-w-0 self-start overflow-hidden">
                <div class="flex flex-wrap items-center gap-2 border-b border-atlantia-rose/10 px-4 py-3">
                    <h2 class="mr-2 text-base font-black">Historial</h2>
                    @foreach ([
                        [null, 'Todos', null],
                        ['running', 'Activos', $dashboard['jobs_activos']],
                        ['completed', 'Completados', $dashboard['jobs_completados']],
                        ['failed', 'Fallidos', $dashboard['jobs_fallidos']],
                    ] as [$valor, $texto, $cantidad])
                        <a href="{{ route('admin.ml.reentrenamiento.index', $valor ? ['estado' => $valor] : []) }}" @class([
                            'inline-flex items-center gap-1.5 rounded-full px-3 py-1 text-xs font-black transition',
                            'bg-atlantia-wine text-white' => $estadoActivo === $valor,
                            'bg-atlantia-blush/50 text-atlantia-wine hover:bg-atlantia-blush' => $estadoActivo !== $valor,
                            '!bg-red-100 !text-red-700' => $valor === 'failed' && $cantidad > 0 && $estadoActivo !== $valor,
                        ])>
                            {{ $texto }}
                            @if ($cantidad !== null)
                                <span @class(['rounded-full px-1.5 text-[10px]', 'bg-white/20' => $estadoActivo === $valor, 'bg-white' => $estadoActivo !== $valor])>{{ $cantidad }}</span>
                            @endif
                        </a>
                    @endforeach
                </div>

                @if ($jobs->isEmpty())
                    <div class="px-6 py-10 text-center">
                        <p class="font-black">{{ $estadoActivo ? 'No hay entrenamientos con ese estado.' : 'Aún no hay entrenamientos.' }}</p>
                        <p class="mt-1 text-sm text-atlantia-ink/55">Usa el formulario para entrenar un modelo por primera vez.</p>
                    </div>
                @else
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead class="bg-atlantia-blush/40 text-left text-[11px] font-black uppercase tracking-wide text-atlantia-ink/55">
                                <tr>
                                    <th class="px-4 py-2.5">Modelo</th>
                                    <th class="px-3 py-2.5">Estado</th>
                                    <th class="px-3 py-2.5 text-right">Registros</th>
                                    <th class="px-3 py-2.5">Fecha</th>
                                    <th class="px-4 py-2.5">Duración</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-atlantia-rose/10">
                                @foreach ($jobs as $job)
                                    @php
                                        [$estadoTexto, $estadoClase] = $estados[$job->estado] ?? [ucfirst((string) $job->estado), 'bg-slate-100 text-slate-600 ring-slate-200'];
                                        $duracion = $job->inicio_at && $job->fin_at ? $job->inicio_at->diffForHumans($job->fin_at, \Carbon\CarbonInterface::DIFF_ABSOLUTE, true) : null;
                                    @endphp
                                    <tr class="align-top hover:bg-atlantia-blush/20">
                                        <td class="px-4 py-2.5">
                                            <p class="font-black">{{ $nombreModelo($job->modelo_nombre) }}</p>
                                            @if ($job->error_log)
                                                <p class="mt-0.5 line-clamp-2 max-w-md text-xs text-red-700" title="{{ $job->error_log }}">{{ $job->error_log }}</p>
                                            @endif
                                        </td>
                                        <td class="px-3 py-2.5"><span class="whitespace-nowrap rounded-full px-2.5 py-1 text-xs font-black ring-1 {{ $estadoClase }}">{{ $estadoTexto }}</span></td>
                                        <td class="px-3 py-2.5 text-right text-atlantia-ink/70">{{ number_format((int) ($job->dataset_size ?? 0)) }}</td>
                                        <td class="whitespace-nowrap px-3 py-2.5 text-atlantia-ink/70">{{ ($job->inicio_at ?? $job->created_at)?->format('d/m/Y H:i') }}</td>
                                        <td class="whitespace-nowrap px-4 py-2.5 text-atlantia-ink/70">{{ $duracion ?? (in_array($job->estado, ['queued', 'running'], true) ? 'en curso' : '—') }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @if ($jobs->hasPages())
                        <div class="border-t border-atlantia-rose/10 px-4 py-3">{{ $jobs->withQueryString()->links() }}</div>
                    @endif
                @endif
            </article>
        </div>
    </section>
@endsection
