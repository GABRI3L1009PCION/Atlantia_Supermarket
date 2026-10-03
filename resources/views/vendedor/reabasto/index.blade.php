@extends('layouts.app')

@section('content')
    @php
        $filters = [
            'q' => (string) request('q', ''),
            'categoria' => (string) request('categoria', ''),
            'urgencia' => (string) request('urgencia', ''),
        ];
        $rows = collect($sugerencias->items());
        $maxSuggested = max(1, (int) $rows->max('stock_sugerido'));
        $chartPoints = $rows->values()->map(function ($suggestion, $index) use ($rows, $maxSuggested) {
            $count = max(1, $rows->count() - 1);
            $x = 8 + (($index / $count) * 84);
            $y = 78 - (((int) $suggestion->stock_sugerido / $maxSuggested) * 50);
            return round($x, 2) . ',' . round($y, 2);
        })->implode(' ');
        $areaPoints = $chartPoints ? '8,84 ' . $chartPoints . ' 92,84' : '';
        $urgencyMeta = [
            'critica' => ['label' => 'Critica', 'class' => 'bg-red-50 text-red-700 ring-red-200', 'dot' => 'bg-red-500'],
            'alta' => ['label' => 'Alta', 'class' => 'bg-red-50 text-red-700 ring-red-200', 'dot' => 'bg-red-500'],
            'media' => ['label' => 'Media', 'class' => 'bg-amber-50 text-amber-700 ring-amber-200', 'dot' => 'bg-amber-500'],
            'baja' => ['label' => 'Baja', 'class' => 'bg-emerald-50 text-emerald-700 ring-emerald-200', 'dot' => 'bg-emerald-500'],
        ];
        $metricCards = [
            ['label' => 'Sugerencias activas', 'value' => number_format((int) ($dashboard['activas'] ?? 0)), 'hint' => '12% vs. ayer', 'tone' => 'rose', 'icon' => 'cart'],
            ['label' => 'Criticas', 'value' => number_format((int) ($dashboard['criticas'] ?? 0)), 'hint' => 'Atencion inmediata', 'tone' => 'red', 'icon' => 'alert'],
            ['label' => 'Unidades sugeridas', 'value' => number_format((int) ($dashboard['unidades_sugeridas'] ?? 0)), 'hint' => 'Total recomendado', 'tone' => 'rose', 'icon' => 'box'],
            ['label' => 'Productos en riesgo', 'value' => number_format((int) ($dashboard['productos_en_riesgo'] ?? 0)), 'hint' => 'Podrian quedar sin stock', 'tone' => 'rose', 'icon' => 'shield'],
        ];
        $tones = [
            'rose' => 'bg-atlantia-blush text-atlantia-wine',
            'red' => 'bg-red-50 text-red-700',
        ];
        $icons = [
            'cart' => '<circle cx="8" cy="21" r="1"/><circle cx="19" cy="21" r="1"/><path d="M2 3h3l3 12h10l2-8H7"/>',
            'alert' => '<path d="M10.3 3.9 1.8 18a2 2 0 0 0 1.7 3h17a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0Z"/><path d="M12 9v4"/><path d="M12 17h.01"/>',
            'box' => '<path d="m12 3 8 4.5-8 4.5-8-4.5L12 3Z"/><path d="M4 7.5v9L12 21l8-4.5v-9"/>',
            'shield' => '<path d="M12 3 20 7v5c0 5-3.5 8-8 9-4.5-1-8-4-8-9V7l8-4Z"/><path d="m9 12 2 2 4-5"/>',
        ];
    @endphp

    <section class="mx-auto max-w-[1280px] space-y-5 pb-10">
        <div class="flex items-center gap-5">
            <span class="grid h-16 w-16 shrink-0 place-items-center rounded-lg border border-atlantia-rose/15 bg-white text-atlantia-wine shadow-sm">
                <svg class="h-9 w-9" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 3v18h18"/><path d="m7 15 4-4 3 3 6-7"/><path d="M18 7h2v2"/></svg>
            </span>
            <div>
                <h1 class="text-3xl font-black leading-tight text-atlantia-ink sm:text-4xl">Sugerencias de reabasto</h1>
                <p class="mt-2 text-sm leading-6 text-atlantia-ink/62">Alertas de inventario basadas en demanda.</p>
            </div>
        </div>

        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
            @foreach ($metricCards as $card)
                <article class="rounded-lg border border-atlantia-rose/15 bg-white p-5 shadow-[0_12px_32px_rgba(42,16,24,0.05)]">
                    <div class="flex items-center gap-5">
                        <span class="{{ $tones[$card['tone']] }} grid h-16 w-16 shrink-0 place-items-center rounded-full">
                            <svg class="h-8 w-8" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">{!! $icons[$card['icon']] !!}</svg>
                        </span>
                        <div>
                            <p class="text-sm text-atlantia-ink/65">{{ $card['label'] }}</p>
                            <p class="mt-1 text-3xl font-black leading-none text-atlantia-ink">{{ $card['value'] }}</p>
                            <p class="mt-2 text-sm {{ $card['tone'] === 'red' ? 'text-red-600' : 'text-atlantia-ink/55' }}">{{ $card['hint'] }}</p>
                        </div>
                    </div>
                </article>
            @endforeach
        </div>

        <form method="GET" action="{{ route('vendedor.reabasto.index') }}" class="grid gap-3 rounded-lg border border-atlantia-rose/15 bg-white p-4 shadow-[0_12px_32px_rgba(42,16,24,0.05)] lg:grid-cols-[1fr_260px_240px_150px_150px]" autocomplete="off" data-restock-filters>
            <div class="relative">
                <input type="search" name="q" value="{{ $filters['q'] }}" placeholder="Buscar producto..." class="w-full rounded-lg border border-atlantia-rose/25 bg-white px-4 py-3 pl-11 text-sm outline-none transition focus:border-atlantia-wine focus:ring-2 focus:ring-atlantia-rose/20">
                <svg class="absolute left-4 top-1/2 h-4 w-4 -translate-y-1/2 text-atlantia-ink/40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>
            </div>
            <select name="categoria" class="rounded-lg border border-atlantia-rose/25 bg-white px-4 py-3 text-sm outline-none transition focus:border-atlantia-wine focus:ring-2 focus:ring-atlantia-rose/20">
                <option value="">Todas las categorias</option>
                @foreach (($dashboard['categorias'] ?? collect()) as $categoria)
                    <option value="{{ $categoria->id }}" @selected($filters['categoria'] === (string) $categoria->id)>{{ $categoria->nombre }}</option>
                @endforeach
            </select>
            <select name="urgencia" class="rounded-lg border border-atlantia-rose/25 bg-white px-4 py-3 text-sm outline-none transition focus:border-atlantia-wine focus:ring-2 focus:ring-atlantia-rose/20">
                <option value="">Todos los niveles</option>
                @foreach ($urgencyMeta as $value => $meta)
                    <option value="{{ $value }}" @selected($filters['urgencia'] === $value)>{{ $meta['label'] }}</option>
                @endforeach
            </select>
            <button type="submit" class="inline-flex items-center justify-center gap-2 rounded-lg border border-atlantia-rose/30 bg-white px-5 py-3 text-sm font-black text-atlantia-wine transition hover:bg-atlantia-blush">
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M21 12a9 9 0 1 1-3-6.7"/><path d="M21 3v6h-6"/></svg>
                Actualizar
            </button>
            <button type="button" onclick="window.print()" class="inline-flex items-center justify-center gap-2 rounded-lg bg-atlantia-wine px-5 py-3 text-sm font-black text-white transition hover:bg-atlantia-wine-700">
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><path d="M7 10l5 5 5-5"/><path d="M12 15V3"/></svg>
                Exportar
            </button>
        </form>

        <article class="rounded-lg border border-atlantia-rose/15 bg-white p-4 shadow-[0_12px_32px_rgba(42,16,24,0.05)]">
            <div class="mb-3 flex items-center gap-3">
                <h2 class="text-xl font-black text-atlantia-ink">Sugerencias de reabasto</h2>
                <span class="rounded-full bg-atlantia-blush px-3 py-1 text-sm font-bold text-atlantia-ink/60">{{ $sugerencias->total() }} resultados</span>
            </div>

            <div class="overflow-x-auto rounded-lg border border-atlantia-rose/15">
                <table class="min-w-full text-sm">
                    <thead class="bg-atlantia-blush/30 text-left text-xs font-black text-atlantia-ink/70">
                        <tr>
                            <th class="px-3 py-3">Producto</th>
                            <th class="px-3 py-3">Categoria</th>
                            <th class="px-3 py-3 text-center">Stock actual</th>
                            <th class="px-3 py-3 text-center">Reservado</th>
                            <th class="px-3 py-3 text-center">Stock minimo</th>
                            <th class="px-3 py-3 text-center">Demanda 7 dias</th>
                            <th class="px-3 py-3 text-center">Sugerido</th>
                            <th class="px-3 py-3 text-center">Dias a quiebre</th>
                            <th class="px-3 py-3 text-center">Urgencia</th>
                            <th class="px-3 py-3 text-right">Accion</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-atlantia-rose/12">
                        @forelse ($sugerencias as $suggestion)
                            @php
                                $producto = $suggestion->producto;
                                $inventario = $producto?->inventario;
                                $prediction7 = $producto?->salesPredictions?->where('horizonte_dias', 7)->sortByDesc('fecha_prediccion')->first();
                                $demanda7 = (int) round((float) ($prediction7?->valor_predicho ?? max($suggestion->stock_sugerido, $suggestion->stock_actual)));
                                $meta = $urgencyMeta[$suggestion->urgencia] ?? $urgencyMeta['media'];
                                $image = $producto?->getFirstMediaUrl('productos', 'thumbnail')
                                    ?: $producto?->getFirstMediaUrl('productos')
                                    ?: ($producto?->imagenPrincipal?->path ? \Illuminate\Support\Facades\Storage::url($producto->imagenPrincipal->path) : null);
                            @endphp
                            <tr class="hover:bg-atlantia-cream/35">
                                <td class="px-3 py-3">
                                    <div class="flex items-center gap-3">
                                        <div class="grid h-10 w-10 shrink-0 place-items-center overflow-hidden rounded-md border border-atlantia-rose/15 bg-atlantia-cream">
                                            @if ($image)
                                                <img src="{{ $image }}" alt="{{ $producto?->nombre }}" class="h-full w-full object-cover">
                                            @else
                                                <span class="text-xs font-black text-atlantia-wine">AS</span>
                                            @endif
                                        </div>
                                        <p class="font-bold text-atlantia-ink">{{ $producto?->nombre ?? 'Producto no disponible' }}</p>
                                    </div>
                                </td>
                                <td class="px-3 py-3 text-atlantia-ink/70">{{ $producto?->categoria?->nombre ?? 'Sin categoria' }}</td>
                                <td class="px-3 py-3 text-center text-atlantia-ink">{{ $suggestion->stock_actual }}</td>
                                <td class="px-3 py-3 text-center text-atlantia-ink">{{ $inventario?->stock_reservado ?? 0 }}</td>
                                <td class="px-3 py-3 text-center text-atlantia-ink">{{ $inventario?->stock_minimo ?? 0 }}</td>
                                <td class="px-3 py-3 text-center text-atlantia-ink">{{ $demanda7 }}</td>
                                <td class="px-3 py-3 text-center text-lg font-black text-atlantia-wine">{{ $suggestion->stock_sugerido }}</td>
                                <td class="px-3 py-3 text-center text-atlantia-ink">{{ number_format((float) $suggestion->dias_hasta_quiebre, 1) }}</td>
                                <td class="px-3 py-3 text-center">
                                    <span class="{{ $meta['class'] }} inline-flex items-center gap-2 rounded-lg px-3 py-1 text-xs font-black ring-1">
                                        <span class="{{ $meta['dot'] }} h-2 w-2 rounded-full"></span>
                                        {{ $meta['label'] }}
                                    </span>
                                </td>
                                <td class="px-3 py-3">
                                    <div class="flex justify-end gap-2">
                                        <form method="POST" action="{{ route('vendedor.reabasto.accept', $suggestion) }}">
                                            @csrf
                                            @method('PATCH')
                                            <input type="hidden" name="cantidad_recibida" value="{{ $suggestion->stock_sugerido }}">
                                            <button class="rounded-lg bg-atlantia-wine px-4 py-2 text-xs font-black text-white transition hover:bg-atlantia-wine-700">Aceptar</button>
                                        </form>
                                        <button type="button" class="rounded-lg border border-atlantia-rose/30 bg-white px-4 py-2 text-xs font-black text-atlantia-ink transition hover:bg-atlantia-blush">Editar</button>
                                        <button type="button" class="rounded-lg border border-atlantia-rose/30 bg-white p-2 text-atlantia-ink/60 transition hover:bg-atlantia-blush" aria-label="Mas opciones">
                                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><circle cx="12" cy="5" r="1.8"/><circle cx="12" cy="12" r="1.8"/><circle cx="12" cy="19" r="1.8"/></svg>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="10" class="px-3 py-12 text-center text-atlantia-ink/55">No hay sugerencias con estos filtros.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="mt-3">{{ $sugerencias->links() }}</div>
        </article>

        <div class="grid gap-5 xl:grid-cols-[1fr_1fr]">
            <article class="rounded-lg border border-atlantia-rose/15 bg-white p-5 shadow-[0_12px_32px_rgba(42,16,24,0.05)]">
                <div class="flex gap-4">
                    <span class="grid h-12 w-12 shrink-0 place-items-center rounded-lg bg-atlantia-blush text-atlantia-wine">
                        <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="m12 3 2.7 5.47 6.04.88-4.37 4.26 1.03 6.01L12 16.78l-5.4 2.84 1.03-6.01-4.37-4.26 6.04-.88L12 3Z"/></svg>
                    </span>
                    <div>
                        <h2 class="text-xl font-black text-atlantia-ink">Recomendacion del sistema</h2>
                        <p class="mt-2 leading-6 text-atlantia-ink/70">
                            Prioriza las sugerencias criticas para evitar quiebres de stock. Los calculos se basan en demanda, stock reservado y niveles minimos configurados.
                        </p>
                        <p class="mt-2 font-black text-atlantia-rose">Atiende primero los {{ min(5, (int) ($dashboard['criticas'] ?? 0)) }} productos criticos para mantener la continuidad de tus ventas.</p>
                    </div>
                </div>
            </article>

            <article class="rounded-lg border border-atlantia-rose/15 bg-white p-5 shadow-[0_12px_32px_rgba(42,16,24,0.05)]">
                <div class="grid gap-4 md:grid-cols-[1fr_180px] md:items-center">
                    <div>
                        <h2 class="text-xl font-black text-atlantia-ink">Tendencia de demanda (7 dias)</h2>
                        <div class="mt-3 h-28">
                            @if ($rows->isNotEmpty())
                                <svg viewBox="0 0 100 100" preserveAspectRatio="none" class="h-full w-full overflow-visible">
                                    <defs>
                                        <linearGradient id="restock-area" x1="0" x2="0" y1="0" y2="1">
                                            <stop offset="0%" stop-color="#9b174d" stop-opacity="0.22"/>
                                            <stop offset="100%" stop-color="#9b174d" stop-opacity="0.02"/>
                                        </linearGradient>
                                    </defs>
                                    <polygon points="{{ $areaPoints }}" fill="url(#restock-area)"/>
                                    <polyline points="{{ $chartPoints }}" fill="none" stroke="#9b174d" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                            @else
                                <div class="grid h-full place-items-center text-sm text-atlantia-ink/55">Sin tendencia disponible.</div>
                            @endif
                        </div>
                    </div>
                    <div>
                        <p class="text-sm text-atlantia-ink/55">Demanda total</p>
                        <p class="mt-1 text-3xl font-black text-atlantia-ink">{{ number_format((int) ($dashboard['demanda_total'] ?? 0)) }}</p>
                        <p class="mt-1 text-sm text-emerald-700">8% vs. semana anterior</p>
                    </div>
                </div>
            </article>
        </div>
    </section>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const form = document.querySelector('[data-restock-filters]');
            form?.querySelectorAll('select').forEach((select) => {
                select.addEventListener('change', () => form.submit());
            });
        });
    </script>
@endsection
