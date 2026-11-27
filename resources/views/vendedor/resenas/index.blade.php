@extends('layouts.app')

@section('content')
    @php
        $filtros = [
            'q' => (string) request('q', ''),
            'rating' => (string) request('rating', ''),
            'orden' => (string) request('orden', 'recientes'),
        ];
        $url = fn (array $cambios): string => route('vendedor.resenas.index', array_filter([...$filtros, ...$cambios], fn ($v) => $v !== '' && $v !== null));
        $total = (int) $dashboard['total'];
        $maximo = max(1, ...array_values($dashboard['distribucion']));
        $nombreCorto = function (?string $nombre): string {
            $partes = preg_split('/\s+/', trim((string) $nombre)) ?: [];
            $inicial = isset($partes[1]) ? ' '.mb_substr($partes[1], 0, 1).'.' : '';

            return ($partes[0] ?? '') !== '' ? $partes[0].$inicial : 'Cliente';
        };
    @endphp

    <section class="mx-auto max-w-[1100px] space-y-4 pb-10">
        <div>
            <h1 class="text-2xl font-black text-atlantia-ink">Reseñas</h1>
            <p class="mt-0.5 text-sm text-atlantia-ink/60">Lo que opinan los clientes que compraron tus productos.</p>
        </div>

        {{-- Resumen real --}}
        <article class="grid gap-4 rounded-xl border border-atlantia-rose/15 bg-white p-4 sm:grid-cols-[auto_minmax(0,1fr)_auto] sm:items-center" data-resumen-resenas>
            <div class="text-center sm:pr-4">
                <p class="text-4xl font-black leading-none text-atlantia-ink">{{ $total > 0 ? number_format((float) $dashboard['promedio'], 1) : '—' }}</p>
                <p class="mt-1 text-lg leading-none text-amber-400" aria-label="{{ number_format((float) $dashboard['promedio'], 1) }} de 5 estrellas">
                    @for ($i = 1; $i <= 5; $i++)<span>{{ $i <= round((float) $dashboard['promedio']) ? '★' : '☆' }}</span>@endfor
                </p>
                <p class="mt-1 text-xs text-atlantia-ink/55">{{ $total }} {{ $total === 1 ? 'reseña publicada' : 'reseñas publicadas' }}</p>
            </div>
            <div class="space-y-1">
                @foreach ($dashboard['distribucion'] as $estrellas => $cuenta)
                    <a href="{{ $url(['rating' => $filtros['rating'] === (string) $estrellas ? '' : $estrellas]) }}" class="flex items-center gap-2 rounded px-1 text-xs hover:bg-[#fbf8f7]">
                        <span class="w-10 shrink-0 font-bold text-atlantia-ink/70">{{ $estrellas }} ★</span>
                        <span class="h-2 flex-1 overflow-hidden rounded-full bg-[#f1e9e8]"><span class="block h-full rounded-full bg-amber-400" style="width: {{ $total > 0 ? round($cuenta / $maximo * 100) : 0 }}%"></span></span>
                        <span class="w-6 shrink-0 text-right text-atlantia-ink/55">{{ $cuenta }}</span>
                    </a>
                @endforeach
            </div>
            <div class="flex gap-2 sm:flex-col">
                <p class="rounded-lg bg-[#fbf8f7] px-3 py-2 text-xs"><span class="block text-atlantia-ink/55">Productos con 4.5 o más</span><span class="text-base font-black text-atlantia-ink">{{ $dashboard['productos_mejor_valorados'] }}</span></p>
                @if ($dashboard['en_revision'] > 0)
                    <p class="rounded-lg bg-amber-50 px-3 py-2 text-xs text-amber-900" title="Atlantia revisa cada reseña antes de publicarla"><span class="block">En revisión</span><span class="text-base font-black">{{ $dashboard['en_revision'] }}</span></p>
                @endif
            </div>
        </article>

        <article class="overflow-hidden rounded-xl border border-atlantia-rose/15 bg-white shadow-[0_12px_32px_rgba(42,16,24,0.05)]">
            <form method="GET" action="{{ route('vendedor.resenas.index') }}" class="flex flex-wrap items-center gap-2 border-b border-atlantia-rose/10 p-3" autocomplete="off" data-review-filters>
                @if ($filtros['rating'] !== '') <input type="hidden" name="rating" value="{{ $filtros['rating'] }}"> @endif
                <label class="relative min-w-[14rem] flex-1">
                    <span class="sr-only">Buscar reseña</span>
                    <svg class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-atlantia-ink/40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>
                    <input type="search" name="q" value="{{ $filtros['q'] }}" placeholder="Buscar por producto, cliente o comentario" class="h-10 w-full rounded-lg border border-atlantia-rose/25 pl-9 pr-3 text-sm outline-none focus:border-atlantia-wine focus:ring-2 focus:ring-atlantia-blush">
                </label>
                <select name="orden" aria-label="Ordenar" class="h-10 rounded-lg border border-atlantia-rose/25 bg-white px-3 text-sm outline-none focus:border-atlantia-wine" data-auto-enviar>
                    <option value="recientes" @selected($filtros['orden'] === 'recientes')>Más recientes</option>
                    <option value="mejor_calificadas" @selected($filtros['orden'] === 'mejor_calificadas')>Mejor calificadas</option>
                    <option value="menor_calificadas" @selected($filtros['orden'] === 'menor_calificadas')>Peor calificadas</option>
                </select>
                @if ($filtros['rating'] !== '' || $filtros['q'] !== '')
                    <a href="{{ route('vendedor.resenas.index') }}" class="px-1 text-sm font-bold text-atlantia-wine hover:underline">Quitar filtros{{ $filtros['rating'] !== '' ? ' ('.$filtros['rating'].' ★)' : '' }}</a>
                @endif
            </form>

            @if ($resenas->isEmpty())
                <div class="px-6 py-14 text-center">
                    <p class="text-3xl text-amber-300" aria-hidden="true">★</p>
                    @if ($filtros['rating'] !== '' || $filtros['q'] !== '')
                        <p class="mt-2 text-base font-black text-atlantia-ink">No hay reseñas con ese filtro</p>
                    @else
                        <p class="mt-2 text-base font-black text-atlantia-ink">Aún no tienes reseñas</p>
                        <p class="mt-1 text-sm text-atlantia-ink/60">Cuando un cliente reciba su pedido podrá calificar tus productos, y sus opiniones aparecerán aquí.</p>
                    @endif
                </div>
            @else
                <ul class="divide-y divide-atlantia-rose/10">
                    @foreach ($resenas as $resena)
                        @php
                            $imagen = $resena->producto?->getFirstMediaUrl('productos', 'thumbnail')
                                ?: $resena->producto?->getFirstMediaUrl('productos')
                                ?: ($resena->producto?->imagenPrincipal?->path ? \Illuminate\Support\Facades\Storage::url($resena->producto->imagenPrincipal->path) : null);
                        @endphp
                        <li class="grid gap-3 px-4 py-4 sm:grid-cols-[minmax(0,1fr)_14rem]" data-resena>
                            <div class="min-w-0">
                                <div class="flex flex-wrap items-center gap-2">
                                    <span class="text-base leading-none text-amber-400" aria-label="{{ (int) $resena->calificacion }} de 5 estrellas">@for ($i = 1; $i <= 5; $i++){{ $i <= (int) $resena->calificacion ? '★' : '☆' }}@endfor</span>
                                    @if ($resena->titulo)<span class="font-bold text-atlantia-ink">{{ $resena->titulo }}</span>@endif
                                    @unless ($resena->aprobada)
                                        <span class="rounded-full bg-amber-50 px-2 py-0.5 text-[11px] font-bold text-amber-800 ring-1 ring-amber-200">En revisión</span>
                                    @endunless
                                </div>
                                <p class="mt-1.5 text-sm leading-6 text-atlantia-ink/75">{{ $resena->contenido ?: 'El cliente solo dejó su calificación.' }}</p>
                                <p class="mt-1.5 text-xs text-atlantia-ink/50">{{ $nombreCorto($resena->cliente?->name) }} · compra verificada · {{ $resena->created_at?->format('d/m/Y') }}</p>

                                @if ($resena->aprobada)
                                    @if ($resena->respuesta_vendedor)
                                        <div class="mt-3 rounded-lg border-l-4 border-atlantia-wine bg-[#fbf7f6] px-3 py-2" data-respuesta>
                                            <p class="text-xs font-black text-atlantia-wine">Tu respuesta · {{ $resena->respondida_at?->format('d/m/Y') }}</p>
                                            <p class="mt-0.5 text-sm text-atlantia-ink/75">{{ $resena->respuesta_vendedor }}</p>
                                        </div>
                                    @endif
                                    <details class="group mt-2" @if ($errors->has('respuesta') && old('resena') === $resena->uuid) open @endif>
                                        <summary class="inline-flex cursor-pointer list-none items-center gap-1.5 rounded-lg px-2 py-1 text-xs font-bold text-atlantia-wine hover:bg-atlantia-blush [&::-webkit-details-marker]:hidden">
                                            <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M9 14 4 9l5-5"/><path d="M4 9h11a5 5 0 0 1 5 5v6"/></svg>
                                            {{ $resena->respuesta_vendedor ? 'Editar respuesta' : 'Responder' }}
                                        </summary>
                                        <form method="POST" action="{{ route('vendedor.resenas.responder', $resena) }}" class="mt-2 space-y-2">
                                            @csrf
                                            <input type="hidden" name="resena" value="{{ $resena->uuid }}">
                                            <textarea name="respuesta" rows="2" maxlength="600" required placeholder="Gracias por tu compra… (la verán todos en la página del producto)" class="w-full resize-none rounded-lg border border-atlantia-rose/30 px-3 py-2 text-sm outline-none focus:border-atlantia-wine focus:ring-2 focus:ring-atlantia-blush">{{ old('resena') === $resena->uuid ? old('respuesta') : $resena->respuesta_vendedor }}</textarea>
                                            @if ($errors->has('respuesta') && old('resena') === $resena->uuid)
                                                <p class="text-xs font-bold text-red-600">{{ $errors->first('respuesta') }}</p>
                                            @endif
                                            <div class="flex items-center justify-end gap-2">
                                                @if ($resena->respuesta_vendedor)
                                                    <button type="submit" form="quitar-respuesta-{{ $resena->uuid }}" class="h-8 rounded-lg px-3 text-xs font-bold text-red-700 hover:bg-red-50">Quitar respuesta</button>
                                                @endif
                                                <button type="submit" class="h-8 rounded-lg bg-atlantia-wine px-4 text-xs font-bold text-white hover:bg-atlantia-wine-700">Publicar respuesta</button>
                                            </div>
                                        </form>
                                        @if ($resena->respuesta_vendedor)
                                            <form id="quitar-respuesta-{{ $resena->uuid }}" method="POST" action="{{ route('vendedor.resenas.respuesta.destroy', $resena) }}" class="hidden" data-confirm="¿Quitar tu respuesta a esta reseña?">
                                                @csrf
                                                @method('DELETE')
                                            </form>
                                        @endif
                                    </details>
                                @else
                                    <p class="mt-2 text-xs text-atlantia-ink/45">Podrás responder cuando Atlantia la publique.</p>
                                @endif
                            </div>
                            <div class="flex items-center gap-3 sm:border-l sm:border-atlantia-rose/10 sm:pl-4">
                                <div class="grid h-12 w-12 shrink-0 place-items-center overflow-hidden rounded-lg bg-[#f8f4f3] p-1">
                                    @if ($imagen)
                                        <img src="{{ $imagen }}" alt="" class="h-full w-full object-contain mix-blend-multiply" loading="lazy">
                                    @else
                                        <span class="font-black text-atlantia-wine">{{ \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($resena->producto?->nombre ?? 'P', 0, 1)) }}</span>
                                    @endif
                                </div>
                                <p class="min-w-0 text-sm font-bold leading-snug text-atlantia-ink line-clamp-2">{{ $resena->producto?->nombre ?? 'Producto' }}</p>
                            </div>
                        </li>
                    @endforeach
                </ul>
                @if ($resenas->hasPages())
                    <div class="border-t border-atlantia-rose/10 px-4 py-3">{{ $resenas->links() }}</div>
                @endif
            @endif
        </article>
    </section>

    <script @nonce>
        document.querySelectorAll('[data-auto-enviar]').forEach((control) => control.addEventListener('change', () => control.form?.submit()));
    </script>
@endsection
