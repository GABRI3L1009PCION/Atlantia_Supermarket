@extends('layouts.app')

@section('content')
    <section class="mx-auto max-w-full py-2">
        <div class="space-y-6 rounded-2xl border border-atlantia-rose/20 bg-white p-6 shadow-sm">
            <x-page-header title="Reentrenamiento ML" subtitle="Programa jobs, revisa ejecuciones recientes y empuja mejoras de modelos." />

            <div class="grid gap-4 md:grid-cols-3">
                <div class="rounded-xl border border-atlantia-rose/20 bg-atlantia-cream p-4">
                    <p class="text-sm text-atlantia-ink/55">Jobs activos</p>
                    <p class="mt-2 text-2xl font-bold text-atlantia-wine">{{ $dashboard['jobs_activos'] }}</p>
                </div>
                <div class="rounded-xl border border-emerald-200 bg-white p-4">
                    <p class="text-sm text-atlantia-ink/55">Completados</p>
                    <p class="mt-2 text-2xl font-bold text-emerald-600">{{ $dashboard['jobs_completados'] }}</p>
                </div>
                <div class="rounded-xl border border-rose-200 bg-white p-4">
                    <p class="text-sm text-atlantia-ink/55">Fallidos</p>
                    <p class="mt-2 text-2xl font-bold text-rose-600">{{ $dashboard['jobs_fallidos'] }}</p>
                </div>
            </div>

            <div class="grid gap-6 xl:grid-cols-[430px_1fr]">
                <form method="POST" action="{{ route('admin.ml.reentrenamiento.store') }}" class="rounded-xl border border-atlantia-rose/20 bg-atlantia-cream p-5">
                    @csrf
                    <h2 class="text-lg font-bold text-atlantia-wine">Solicitar reentrenamiento</h2>

                    <div class="mt-4 grid gap-4">
                        <div>
                            <label class="text-sm font-semibold text-atlantia-ink">Modelo</label>
                            <select name="modelo_nombre" class="mt-1 w-full rounded-md border border-atlantia-rose/35 px-3 py-2" required>
                                <option value="demand_forecast">Demand forecast</option>
                                <option value="product_recommendation">Product recommendation</option>
                                <option value="restock_suggestion">Restock suggestion</option>
                                <option value="fraud_detection">Fraud detection</option>
                                <option value="review_nlp">Review NLP</option>
                            </select>
                        </div>
                        <div class="grid gap-4 md:grid-cols-2">
                            <div>
                                <label class="text-sm font-semibold text-atlantia-ink">Fecha inicio dataset</label>
                                <input name="fecha_inicio_dataset" type="date" class="mt-1 w-full rounded-md border border-atlantia-rose/35 px-3 py-2">
                            </div>
                            <div>
                                <label class="text-sm font-semibold text-atlantia-ink">Fecha fin dataset</label>
                                <input name="fecha_fin_dataset" type="date" class="mt-1 w-full rounded-md border border-atlantia-rose/35 px-3 py-2">
                            </div>
                        </div>
                        <div>
                            <label class="text-sm font-semibold text-atlantia-ink">Motivo</label>
                            <textarea name="motivo" rows="4" class="mt-1 w-full rounded-md border border-atlantia-rose/35 px-3 py-2" required></textarea>
                        </div>
                        <div class="grid gap-4 md:grid-cols-2">
                            <div>
                                <label class="text-sm font-semibold text-atlantia-ink">Horizonte dias</label>
                                <select name="parametros[horizonte_dias]" class="mt-1 w-full rounded-md border border-atlantia-rose/35 px-3 py-2">
                                    <option value="">No aplica</option>
                                    <option value="7">7</option>
                                    <option value="14">14</option>
                                    <option value="30">30</option>
                                </select>
                            </div>
                            <div>
                                <label class="text-sm font-semibold text-atlantia-ink">Max trials</label>
                                <input name="parametros[max_trials]" type="number" min="1" max="100" class="mt-1 w-full rounded-md border border-atlantia-rose/35 px-3 py-2">
                            </div>
                        </div>
                    </div>

                    <div class="mt-4 flex flex-wrap gap-4 text-sm">
                        <label class="inline-flex items-center gap-2">
                            <input type="checkbox" name="forzar_reentrenamiento" value="1" class="rounded border-atlantia-rose text-atlantia-wine">
                            <span>Forzar reentrenamiento</span>
                        </label>
                        <label class="inline-flex items-center gap-2">
                            <input type="checkbox" name="usar_staging" value="1" checked class="rounded border-atlantia-rose text-atlantia-wine">
                            <span>Desplegar en staging</span>
                        </label>
                    </div>

                    <x-ui.button type="submit" class="mt-5 w-full">Lanzar job</x-ui.button>
                </form>

                <div class="rounded-xl border border-atlantia-rose/20 bg-white p-5">
                    <div class="flex items-center justify-between gap-3">
                        <h2 class="text-lg font-bold text-atlantia-wine">Historial de jobs</h2>
                        <form method="GET" class="flex gap-2">
                            <input type="search" name="modelo_nombre" value="{{ $filters['modelo_nombre'] ?? '' }}" placeholder="Filtrar por modelo" class="rounded-md border border-atlantia-rose/35 px-3 py-2">
                            <select name="estado" class="rounded-md border border-atlantia-rose/35 px-3 py-2">
                                <option value="">Todos</option>
                                @foreach (['queued', 'running', 'completed', 'failed', 'cancelled'] as $estado)
                                    <option value="{{ $estado }}" @selected(($filters['estado'] ?? '') === $estado)>{{ $estado }}</option>
                                @endforeach
                            </select>
                            <x-ui.button type="submit" variant="secondary">Filtrar</x-ui.button>
                        </form>
                    </div>

                    <div class="mt-5 overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <thead>
                                <tr class="border-b border-atlantia-rose/20 text-left text-atlantia-ink/55">
                                    <th class="pb-3">Modelo</th>
                                    <th class="pb-3">Estado</th>
                                    <th class="pb-3">Dataset</th>
                                    <th class="pb-3">Inicio</th>
                                    <th class="pb-3">Fin</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-atlantia-rose/15">
                                @forelse ($jobs as $job)
                                    <tr>
                                        <td class="py-3">
                                            <p class="font-semibold text-atlantia-ink">{{ $job->modelo_nombre }}</p>
                                            <p class="text-xs text-atlantia-ink/55">{{ $job->uuid }}</p>
                                        </td>
                                        <td class="py-3">
                                            <span class="rounded-md bg-atlantia-blush px-3 py-1 text-xs font-bold text-atlantia-wine">{{ $job->estado }}</span>
                                        </td>
                                        <td class="py-3 text-atlantia-ink/70">{{ $job->dataset_size ?? 0 }}</td>
                                        <td class="py-3 text-atlantia-ink/70">{{ $job->inicio_at?->format('d/m/Y H:i') ?? 'Pendiente' }}</td>
                                        <td class="py-3 text-atlantia-ink/70">{{ $job->fin_at?->format('d/m/Y H:i') ?? 'En curso' }}</td>
                                    </tr>
                                    @if ($job->error_log)
                                        <tr>
                                            <td colspan="5" class="pb-3">
                                                <div class="rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-xs text-rose-700">
                                                    {{ $job->error_log }}
                                                </div>
                                            </td>
                                        </tr>
                                    @endif
                                @empty
                                    <tr>
                                        <td colspan="5" class="py-6 text-center text-atlantia-ink/60">No hay jobs registrados para esos filtros.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-4">{{ $jobs->links() }}</div>
                </div>
            </div>
        </div>
    </section>
@endsection
