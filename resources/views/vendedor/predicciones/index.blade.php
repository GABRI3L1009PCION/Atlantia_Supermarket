@extends('layouts.app')

@section('content')
    @php
        $filters = [
            'q' => (string) request('q', ''),
            'horizonte' => (string) request('horizonte', (string) ($dashboard['horizonte_activo'] ?? 7)),
            'orden' => (string) request('orden', 'mayor_demanda'),
        ];
        $rows = collect($predicciones->items());
        $max = max(1, (float) ($dashboard['max_prediccion'] ?? $rows->max('valor_predicho')));
        $chartPoints = $rows->values()->map(function ($prediction, $index) use ($rows, $max) {
            $count = max(1, $rows->count() - 1);
            $x = 8 + (($index / $count) * 84);
            $y = 78 - (((float) $prediction->valor_predicho / $max) * 54);
            return round($x, 2) . ',' . round($y, 2);
        })->implode(' ');
        $areaPoints = $chartPoints ? '8,84 ' . $chartPoints . ' 92,84' : '';
        $metricCards = [
            ['label' => 'Demanda esperada', 'value' => number_format((float) ($dashboard['total_predicho'] ?? 0)), 'hint' => 'Unidades proyectadas', 'tone' => 'rose', 'icon' => 'activity'],
            ['label' => 'Productos evaluados', 'value' => number_format((int) ($dashboard['productos_evaluados'] ?? 0)), 'hint' => 'Con prediccion ML', 'tone' => 'blue', 'icon' => 'box'],
            ['label' => 'Demanda alta', 'value' => number_format((int) ($dashboard['demanda_alta'] ?? 0)), 'hint' => 'Sobre promedio', 'tone' => 'green', 'icon' => 'trend'],
            ['label' => 'Riesgo de stock', 'value' => number_format((int) ($dashboard['riesgo_stock'] ?? 0)), 'hint' => 'Revisar inventario', 'tone' => 'amber', 'icon' => 'alert'],
        ];
        $tones = [
            'rose' => 'bg-atlantia-blush text-atlantia-wine',
            'blue' => 'bg-blue-50 text-blue-700',
            'green' => 'bg-emerald-50 text-emerald-700',
            'amber' => 'bg-amber-50 text-amber-700',
        ];
        $icons = [
            'activity' => '<path d="M22 12h-4l-3 8-6-16-3 8H2"/>',
            'box' => '<path d="m12 3 8 4.5-8 4.5-8-4.5L12 3Z"/><path d="M4 7.5v9L12 21l8-4.5v-9"/>',
            'trend' => '<path d="m3 17 6-6 4 4 8-8"/><path d="M14 7h7v7"/>',
            'alert' => '<path d="M10.3 3.9 1.8 18a2 2 0 0 0 1.7 3h17a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0Z"/><path d="M12 9v4"/><path d="M12 17h.01"/>',
        ];
    @endphp

    <section class="mx-auto max-w-[1280px] space-y-5 pb-10">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <p class="text-xs font-black uppercase tracking-[0.22em] text-atlantia-rose">Atlantia Supermarket</p>
                <h1 class="mt-2 text-3xl font-black leading-tight text-atlantia-ink sm:text-4xl">Prediccion de demanda</h1>
                <p class="mt-2 max-w-3xl text-sm leading-6 text-atlantia-ink/62">
                    Pronosticos por producto para anticipar inventario, reabasto y disponibilidad.
                </p>
            </div>
            <span class="inline-flex items-center gap-2 self-start rounded-lg border border-atlantia-rose/20 bg-white px-4 py-3 text-sm text-atlantia-ink/60 shadow-sm lg:self-auto">
                <svg class="h-4 w-4 text-atlantia-wine" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg>
                Actualizado {{ now()->format('d/m/Y H:i') }}
            </span>
        </div>

        <form method="GET" action="{{ route('vendedor.predicciones.index') }}" class="grid gap-3 rounded-lg border border-atlantia-rose/15 bg-white p-4 shadow-[0_12px_32px_rgba(42,16,24,0.05)] lg:grid-cols-[1fr_220px_240px_140px]" autocomplete="off" data-demand-filters>
            <div class="relative">
                <input type="search" name="q" value="{{ $filters['q'] }}" placeholder="Buscar producto o SKU" class="w-full rounded-lg border border-atlantia-rose/25 bg-white px-4 py-3 pl-11 text-sm outline-none transition focus:border-atlantia-wine focus:ring-2 focus:ring-atlantia-rose/20">
                <svg class="absolute left-4 top-1/2 h-4 w-4 -translate-y-1/2 text-atlantia-ink/40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>
            </div>
            <select name="horizonte" class="rounded-lg border border-atlantia-rose/25 bg-white px-4 py-3 text-sm outline-none transition focus:border-atlantia-wine focus:ring-2 focus:ring-atlantia-rose/20">
                <option value="7" @selected($filters['horizonte'] === '7')>Horizonte 7 dias</option>
                <option value="14" @selected($filters['horizonte'] === '14')>Horizonte 14 dias</option>
                <option value="30" @selected($filters['horizonte'] === '30')>Horizonte 30 dias</option>
            </select>
            <select name="orden" class="rounded-lg border border-atlantia-rose/25 bg-white px-4 py-3 text-sm outline-none transition focus:border-atlantia-wine focus:ring-2 focus:ring-atlantia-rose/20">
                <option value="mayor_demanda" @selected($filters['orden'] === 'mayor_demanda')>Mayor demanda</option>
                <option value="menor_demanda" @selected($filters['orden'] === 'menor_demanda')>Menor demanda</option>
                <option value="recientes" @selected($filters['orden'] === 'recientes')>Mas recientes</option>
            </select>
            <button type="submit" class="inline-flex items-center justify-center gap-2 rounded-lg bg-atlantia-wine px-5 py-3 text-sm font-black text-white transition hover:bg-atlantia-wine-700">
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M21 12a9 9 0 1 1-3-6.7"/><path d="M21 3v6h-6"/></svg>
                Actualizar
            </button>
        </form>

        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
            @foreach ($metricCards as $card)
                <article class="rounded-lg border border-atlantia-rose/15 bg-white p-5 shadow-[0_12px_32px_rgba(42,16,24,0.05)]">
                    <div class="flex items-center gap-4">
                        <span class="{{ $tones[$card['tone']] }} grid h-14 w-14 shrink-0 place-items-center rounded-full">
                            <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">{!! $icons[$card['icon']] !!}</svg>
                        </span>
                        <div class="min-w-0">
                            <p class="truncate text-sm text-atlantia-ink/60">{{ $card['label'] }}</p>
                            <p class="mt-1 text-3xl font-black leading-none text-atlantia-ink">{{ $card['value'] }}</p>
                            <p class="mt-1 text-xs text-atlantia-ink/55">{{ $card['hint'] }}</p>
                        </div>
                    </div>
                </article>
            @endforeach
        </div>

        <div class="grid gap-5 xl:grid-cols-[1.1fr_0.9fr]">
            <article class="rounded-lg border border-atlantia-rose/15 bg-white p-5 shadow-[0_12px_32px_rgba(42,16,24,0.05)]">
                <div class="flex items-center justify-between gap-3">
                    <div>
                        <h2 class="text-xl font-black text-atlantia-wine">Demanda proyectada</h2>
                        <p class="mt-1 text-sm text-atlantia-ink/55">Productos ordenados por demanda esperada.</p>
                    </div>
                    <span class="rounded-lg bg-atlantia-blush px-4 py-2 text-xs font-black text-atlantia-wine">{{ $filters['horizonte'] }} dias</span>
                </div>
                <div class="mt-5 h-[260px]">
                    @if ($rows->isNotEmpty())
                        <svg viewBox="0 0 100 100" preserveAspectRatio="none" class="h-full w-full overflow-visible">
                            <defs>
                                <linearGradient id="demand-area" x1="0" x2="0" y1="0" y2="1">
                                    <stop offset="0%" stop-color="#9b174d" stop-opacity="0.22"/>
                                    <stop offset="100%" stop-color="#9b174d" stop-opacity="0.02"/>
                                </linearGradient>
                            </defs>
                            @foreach ([24, 39, 54, 69, 84] as $line)
                                <line x1="6" y1="{{ $line }}" x2="94" y2="{{ $line }}" stroke="#ead5dd" stroke-width="0.35"/>
                            @endforeach
                            <polygon points="{{ $areaPoints }}" fill="url(#demand-area)"/>
                            <polyline points="{{ $chartPoints }}" fill="none" stroke="#9b174d" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round"/>
                            @foreach ($rows->values() as $index => $prediction)
                                @php
                                    $count = max(1, $rows->count() - 1);
                                    $x = 8 + (($index / $count) * 84);
                                    $y = 78 - (((float) $prediction->valor_predicho / $max) * 54);
                                @endphp
                                <circle cx="{{ $x }}" cy="{{ $y }}" r="1.5" fill="#9b174d" stroke="white" stroke-width="0.7"/>
                            @endforeach
                        </svg>
                    @else
                        <div class="grid h-full place-items-center rounded-lg border border-dashed border-atlantia-rose/25 bg-atlantia-blush/20 text-center">
                            <div>
                                <p class="font-black text-atlantia-ink">Aun no hay predicciones</p>
                                <p class="mt-1 text-sm text-atlantia-ink/55">Genera pronosticos desde el flujo ML para ver el tablero.</p>
                            </div>
                        </div>
                    @endif
                </div>
            </article>

            <article class="rounded-lg border border-atlantia-rose/15 bg-white p-5 shadow-[0_12px_32px_rgba(42,16,24,0.05)]">
                <h2 class="text-xl font-black text-atlantia-wine">Prioridad operativa</h2>
                <div class="mt-5 space-y-3">
                    @forelse ($rows->take(5) as $prediction)
                        @php
                            $stock = (int) ($prediction->producto?->inventario?->stock_actual ?? 0);
                            $forecast = (float) $prediction->valor_predicho;
                            $risk = $stock > 0 && $forecast >= $stock;
                            $coverage = $forecast > 0 ? round(($stock / $forecast) * 100) : 100;
                        @endphp
                        <div class="rounded-lg border border-atlantia-rose/15 p-4">
                            <div class="flex items-start justify-between gap-4">
                                <div class="min-w-0">
                                    <p class="truncate font-black text-atlantia-ink">{{ $prediction->producto?->nombre ?? 'Producto no disponible' }}</p>
                                    <p class="mt-1 text-xs text-atlantia-ink/55">Stock {{ $stock }} unidades · cobertura {{ $coverage }}%</p>
                                </div>
                                <span class="{{ $risk ? 'bg-red-50 text-red-700 ring-red-200' : 'bg-emerald-50 text-emerald-700 ring-emerald-200' }} rounded-full px-3 py-1 text-xs font-black ring-1">
                                    {{ $risk ? 'Reabasto' : 'Normal' }}
                                </span>
                            </div>
                            <div class="mt-3 h-2 rounded-full bg-atlantia-blush">
                                <div class="h-2 rounded-full bg-atlantia-wine" style="width: {{ max(5, min(100, ($forecast / $max) * 100)) }}%"></div>
                            </div>
                        </div>
                    @empty
                        <p class="rounded-lg border border-dashed border-atlantia-rose/25 p-8 text-center text-sm text-atlantia-ink/55">Sin datos para priorizar.</p>
                    @endforelse
                </div>
            </article>
        </div>

        <article class="overflow-hidden rounded-lg border border-atlantia-rose/15 bg-white shadow-[0_12px_32px_rgba(42,16,24,0.05)]">
            <div class="border-b border-atlantia-rose/15 px-5 py-4">
                <h2 class="text-xl font-black text-atlantia-ink">Predicciones por producto</h2>
                <p class="mt-1 text-sm text-atlantia-ink/55">Rango estimado, modelo y fecha de prediccion.</p>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-atlantia-cream/45 text-left text-xs font-black uppercase tracking-wide text-atlantia-ink/55">
                        <tr>
                            <th class="px-5 py-3">Producto</th>
                            <th class="px-5 py-3">Horizonte</th>
                            <th class="px-5 py-3">Prediccion</th>
                            <th class="px-5 py-3">Rango estimado</th>
                            <th class="px-5 py-3">Modelo</th>
                            <th class="px-5 py-3 text-right">Estado</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-atlantia-rose/12">
                        @forelse ($predicciones as $prediction)
                            @php
                                $image = $prediction->producto?->getFirstMediaUrl('productos', 'thumbnail')
                                    ?: $prediction->producto?->getFirstMediaUrl('productos')
                                    ?: ($prediction->producto?->imagenPrincipal?->path ? \Illuminate\Support\Facades\Storage::url($prediction->producto->imagenPrincipal->path) : null);
                                $stock = (int) ($prediction->producto?->inventario?->stock_actual ?? 0);
                                $risk = $stock > 0 && (float) $prediction->valor_predicho >= $stock;
                            @endphp
                            <tr class="hover:bg-atlantia-cream/35">
                                <td class="px-5 py-4">
                                    <div class="flex items-center gap-3">
                                        <div class="grid h-12 w-12 shrink-0 place-items-center overflow-hidden rounded-lg border border-atlantia-rose/15 bg-atlantia-cream">
                                            @if ($image)
                                                <img src="{{ $image }}" alt="{{ $prediction->producto?->nombre }}" class="h-full w-full object-cover">
                                            @else
                                                <span class="text-xs font-black text-atlantia-wine">ML</span>
                                            @endif
                                        </div>
                                        <div class="min-w-0">
                                            <p class="truncate font-black text-atlantia-ink">{{ $prediction->producto?->nombre ?? 'Producto no disponible' }}</p>
                                            <p class="truncate text-xs text-atlantia-ink/50">{{ $prediction->producto?->sku ?? 'SKU sin dato' }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-5 py-4 font-bold text-atlantia-ink">{{ $prediction->horizonte_dias }} dias</td>
                                <td class="px-5 py-4">
                                    <p class="text-xl font-black text-atlantia-wine">{{ number_format((float) $prediction->valor_predicho, 0) }}</p>
                                    <p class="text-xs text-atlantia-ink/50">unidades</p>
                                </td>
                                <td class="px-5 py-4 text-atlantia-ink/70">
                                    {{ number_format((float) ($prediction->intervalo_inferior ?? 0), 0) }}
                                    -
                                    {{ number_format((float) ($prediction->intervalo_superior ?? 0), 0) }}
                                    unidades
                                </td>
                                <td class="px-5 py-4 text-atlantia-ink/60">{{ $prediction->modeloVersion?->nombre ?? 'Fallback local' }}</td>
                                <td class="px-5 py-4 text-right">
                                    <span class="{{ $risk ? 'bg-red-50 text-red-700 ring-red-200' : 'bg-emerald-50 text-emerald-700 ring-emerald-200' }} inline-flex rounded-full px-3 py-1 text-xs font-black ring-1">
                                        {{ $risk ? 'Revisar stock' : 'Controlado' }}
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="px-5 py-12 text-center text-atlantia-ink/55">No hay predicciones con estos filtros.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="flex flex-col gap-3 border-t border-atlantia-rose/15 px-5 py-3 text-sm text-atlantia-ink/55 sm:flex-row sm:items-center sm:justify-between">
                <p>Mostrando {{ $predicciones->firstItem() ?? 0 }} a {{ $predicciones->lastItem() ?? 0 }} de {{ $predicciones->total() }} predicciones</p>
                {{ $predicciones->links() }}
            </div>
        </article>
    </section>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const form = document.querySelector('[data-demand-filters]');
            form?.querySelectorAll('select').forEach((select) => {
                select.addEventListener('change', () => form.submit());
            });
        });
    </script>
@endsection
