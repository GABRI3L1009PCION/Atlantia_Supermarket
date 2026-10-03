@extends('layouts.app')

@section('content')
    @php
        $filters = [
            'q' => (string) request('q', ''),
            'rating' => (string) request('rating', ''),
            'orden' => (string) request('orden', 'recientes'),
        ];
        $metricCards = [
            ['label' => 'Calificacion promedio', 'value' => number_format((float) $dashboard['promedio'], 1), 'suffix' => '/ 5', 'hint' => 'rating', 'icon' => 'star'],
            ['label' => 'Total resenas', 'value' => number_format((int) $dashboard['total']), 'suffix' => '', 'hint' => '12% vs. mes anterior', 'icon' => 'message'],
            ['label' => 'Pendientes de responder', 'value' => number_format((int) $dashboard['pendientes']), 'suffix' => '', 'hint' => 'Resenas de los ultimos 30 dias', 'icon' => 'reply'],
            ['label' => 'Productos mejor valorados', 'value' => number_format((int) $dashboard['productos_mejor_valorados']), 'suffix' => '', 'hint' => 'Con calificacion >= 4.5', 'icon' => 'award'],
        ];
        $icons = [
            'star' => '<path d="m12 3 2.7 5.47 6.04.88-4.37 4.26 1.03 6.01L12 16.78l-5.4 2.84 1.03-6.01-4.37-4.26 6.04-.88L12 3Z"/>',
            'message' => '<path d="M21 15a4 4 0 0 1-4 4H8l-5 3V7a4 4 0 0 1 4-4h10a4 4 0 0 1 4 4Z"/>',
            'reply' => '<path d="M21 15a4 4 0 0 1-4 4H7l-4 3V7a4 4 0 0 1 4-4h10a4 4 0 0 1 4 4Z"/><path d="M8 9h8M8 13h5"/>',
            'award' => '<circle cx="12" cy="8" r="5"/><path d="M8.5 12.5 7 22l5-3 5 3-1.5-9.5"/>',
        ];
        $ratingOptions = [
            '' => 'Todas las calificaciones',
            '5' => '5 estrellas',
            '4' => '4 estrellas',
            '3' => '3 estrellas',
            '2' => '2 estrellas',
            '1' => '1 estrella',
        ];
        $orderOptions = [
            'recientes' => 'Mas recientes',
            'mejor_calificadas' => 'Mejor calificadas',
            'menor_calificadas' => 'Menor calificadas',
        ];
    @endphp

    <section class="mx-auto max-w-[1280px] space-y-5 pb-10">
        <div>
            <p class="text-xs font-black uppercase tracking-[0.18em] text-atlantia-rose">Atlantia Supermarket</p>
            <h1 class="mt-2 text-3xl font-black leading-tight text-atlantia-ink sm:text-4xl">Resenas recibidas</h1>
            <p class="mt-2 text-sm leading-6 text-atlantia-ink/62">Opiniones de clientes verificados sobre tus productos.</p>
        </div>

        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
            @foreach ($metricCards as $card)
                <article class="rounded-lg border border-atlantia-rose/15 bg-white p-5 shadow-[0_12px_32px_rgba(42,16,24,0.05)]">
                    <div class="flex items-center gap-5">
                        <span class="grid h-14 w-14 shrink-0 place-items-center rounded-full bg-atlantia-blush text-atlantia-wine">
                            <svg class="h-7 w-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">{!! $icons[$card['icon']] !!}</svg>
                        </span>
                        <div>
                            <p class="text-sm text-atlantia-ink/68">{{ $card['label'] }}</p>
                            <p class="mt-1 text-3xl font-black leading-none text-atlantia-ink">
                                {{ $card['value'] }} <span class="text-lg font-bold text-atlantia-ink/65">{{ $card['suffix'] }}</span>
                            </p>
                            @if ($card['hint'] === 'rating')
                                <p class="mt-2 text-lg leading-none text-amber-400">
                                    @for ($i = 1; $i <= 5; $i++)
                                        <span>{{ $i <= round((float) $dashboard['promedio']) ? '★' : '☆' }}</span>
                                    @endfor
                                </p>
                            @else
                                <p class="mt-2 text-xs text-atlantia-ink/55">{{ $card['hint'] }}</p>
                            @endif
                        </div>
                    </div>
                </article>
            @endforeach
        </div>

        <article class="rounded-lg border border-atlantia-rose/15 bg-white p-4 shadow-[0_12px_32px_rgba(42,16,24,0.05)]">
            <form method="GET" action="{{ route('vendedor.resenas.index') }}" class="grid gap-3 lg:grid-cols-[1fr_260px_260px_190px]" autocomplete="off" data-review-filters>
                <div class="relative">
                    <input type="search" name="q" value="{{ $filters['q'] }}" placeholder="Buscar resena o producto" class="w-full rounded-lg border border-atlantia-rose/25 bg-white px-4 py-3 pl-11 text-sm outline-none transition focus:border-atlantia-wine focus:ring-2 focus:ring-atlantia-rose/20">
                    <svg class="absolute left-4 top-1/2 h-4 w-4 -translate-y-1/2 text-atlantia-ink/40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>
                </div>
                <select name="rating" class="rounded-lg border border-atlantia-rose/25 bg-white px-4 py-3 text-sm outline-none transition focus:border-atlantia-wine focus:ring-2 focus:ring-atlantia-rose/20">
                    @foreach ($ratingOptions as $value => $label)
                        <option value="{{ $value }}" @selected($filters['rating'] === $value)>{{ $label }}</option>
                    @endforeach
                </select>
                <select name="orden" class="rounded-lg border border-atlantia-rose/25 bg-white px-4 py-3 text-sm outline-none transition focus:border-atlantia-wine focus:ring-2 focus:ring-atlantia-rose/20">
                    @foreach ($orderOptions as $value => $label)
                        <option value="{{ $value }}" @selected($filters['orden'] === $value)>{{ $label }}</option>
                    @endforeach
                </select>
                <button type="button" onclick="window.print()" class="inline-flex items-center justify-center gap-2 rounded-lg border border-atlantia-wine/45 bg-white px-5 py-3 text-sm font-black text-atlantia-wine transition hover:bg-atlantia-blush">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><path d="M7 10l5 5 5-5"/><path d="M12 15V3"/></svg>
                    Exportar resenas
                </button>
            </form>

            <div class="mt-4 overflow-hidden rounded-lg border border-atlantia-rose/15">
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <tbody class="divide-y divide-atlantia-rose/12">
                            @forelse ($resenas as $resena)
                                @php
                                    $cliente = $resena->cliente?->name ?? 'Cliente Atlantia';
                                    $initials = collect(explode(' ', $cliente))->filter()->take(2)->map(fn ($part) => \Illuminate\Support\Str::substr($part, 0, 1))->implode('');
                                    $image = $resena->producto?->getFirstMediaUrl('productos', 'thumbnail')
                                        ?: $resena->producto?->getFirstMediaUrl('productos')
                                        ?: ($resena->producto?->imagenPrincipal?->path ? \Illuminate\Support\Facades\Storage::url($resena->producto->imagenPrincipal->path) : null);
                                    $respondida = (bool) $resena->aprobada;
                                @endphp
                                <tr class="align-middle transition hover:bg-atlantia-cream/45">
                                    <td class="w-[250px] px-4 py-3">
                                        <div class="flex items-center gap-3">
                                            <span class="grid h-11 w-11 shrink-0 place-items-center rounded-full bg-atlantia-blush text-sm font-black text-atlantia-wine">{{ \Illuminate\Support\Str::upper($initials ?: 'CL') }}</span>
                                            <div class="min-w-0">
                                                <p class="truncate font-black text-atlantia-ink">{{ $cliente }}</p>
                                                <p class="mt-1 inline-flex items-center gap-1 text-xs text-emerald-700">
                                                    <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M20 6 9 17l-5-5"/></svg>
                                                    Cliente verificado
                                                </p>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="w-[320px] px-4 py-3">
                                        <div class="flex items-center gap-4 border-l border-atlantia-rose/15 pl-5">
                                            <div class="grid h-14 w-14 shrink-0 place-items-center overflow-hidden rounded-lg border border-atlantia-rose/15 bg-atlantia-cream">
                                                @if ($image)
                                                    <img src="{{ $image }}" alt="{{ $resena->producto?->nombre }}" class="h-full w-full object-cover">
                                                @else
                                                    <span class="text-xs font-black text-atlantia-wine">AS</span>
                                                @endif
                                            </div>
                                            <div class="min-w-0">
                                                <p class="truncate font-bold text-atlantia-ink">{{ $resena->producto?->nombre ?? 'Producto' }}</p>
                                                <p class="mt-1 truncate text-xs text-atlantia-ink/55">{{ $resena->producto?->categoria?->nombre ?? 'Catalogo' }}</p>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="min-w-[360px] px-4 py-3">
                                        <p class="text-lg leading-none text-amber-400">
                                            @for ($i = 1; $i <= 5; $i++)
                                                <span>{{ $i <= (int) $resena->calificacion ? '★' : '☆' }}</span>
                                            @endfor
                                        </p>
                                        <p class="mt-2 line-clamp-2 text-atlantia-ink/75">{{ $resena->contenido ?: ($resena->titulo ?: 'Sin comentario adicional.') }}</p>
                                    </td>
                                    <td class="w-[210px] px-4 py-3 text-right">
                                        <p class="text-xs text-atlantia-ink/55">{{ $resena->created_at?->translatedFormat('d M Y') ?? $resena->created_at?->format('d/m/Y') }}</p>
                                        @if ($respondida)
                                            <span class="mt-2 inline-flex items-center gap-1 rounded-full bg-emerald-50 px-3 py-1 text-xs font-black text-emerald-700 ring-1 ring-emerald-200">
                                                <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M20 6 9 17l-5-5"/></svg>
                                                Respondida
                                            </span>
                                        @else
                                            <button type="button" class="mt-2 rounded-lg border border-atlantia-rose/35 px-5 py-2 text-xs font-black text-atlantia-wine transition hover:bg-atlantia-blush">
                                                Responder
                                            </button>
                                        @endif
                                    </td>
                                    <td class="w-12 px-4 py-3 text-right">
                                        <button type="button" class="rounded-full p-2 text-atlantia-ink/45 transition hover:bg-atlantia-blush hover:text-atlantia-wine" aria-label="Mas opciones">
                                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><circle cx="12" cy="5" r="1.8"/><circle cx="12" cy="12" r="1.8"/><circle cx="12" cy="19" r="1.8"/></svg>
                                        </button>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td class="px-4 py-12 text-center text-atlantia-ink/55">Aun no hay resenas para tus productos.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="flex flex-col gap-3 border-t border-atlantia-rose/15 px-4 py-3 text-sm text-atlantia-ink/55 sm:flex-row sm:items-center sm:justify-between">
                    <p>Mostrando {{ $resenas->firstItem() ?? 0 }} a {{ $resenas->lastItem() ?? 0 }} de {{ $resenas->total() }} resenas</p>
                    {{ $resenas->links() }}
                </div>
            </div>
        </article>
    </section>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const form = document.querySelector('[data-review-filters]');
            form?.querySelectorAll('select').forEach((select) => {
                select.addEventListener('change', () => form.submit());
            });
        });
    </script>
@endsection
