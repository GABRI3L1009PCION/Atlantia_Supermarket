@extends('layouts.app')

@section('content')
    @php
        $h = $proyeccion['horizonte'];
        $resumen = $proyeccion['resumen'];
        $filas = $proyeccion['filas'];
        $q = (string) request('q', '');
        $estados = [
            'agotado' => ['Agotado: repón ya', 'bg-rose-50 text-rose-700 ring-rose-200'],
            'pedir' => ['Pide más pronto', 'bg-amber-50 text-amber-800 ring-amber-200'],
            'atento' => ['Vigílalo', 'bg-sky-50 text-sky-700 ring-sky-200'],
            'bien' => ['Tienes suficiente', 'bg-emerald-50 text-emerald-700 ring-emerald-200'],
            'sin_ventas' => ['Sin ventas aún', 'bg-slate-100 text-slate-600 ring-slate-200'],
        ];
    @endphp

    <section class="mx-auto max-w-[1180px] space-y-4 pb-10">
        <div class="flex flex-wrap items-end justify-between gap-3">
            <div class="max-w-2xl">
                <h1 class="text-2xl font-black text-atlantia-ink">Predicción de demanda</h1>
                <p class="mt-0.5 text-sm text-atlantia-ink/60">Cuánto vas a vender y si te alcanzan tus existencias. Se calcula con tus ventas de los últimos 30 días{{ $resumen['con_modelo'] > 0 ? ' y el modelo de Atlantia' : '' }}.</p>
            </div>
            <div class="inline-flex rounded-lg bg-[#f5efee] p-1" role="group" aria-label="Periodo">
                @foreach (\App\Services\Ml\PrediccionDemandaService::HORIZONTES as $dias)
                    <a href="{{ route('vendedor.predicciones.index', array_filter(['horizonte' => $dias, 'q' => $q])) }}" @class([
                        'h-9 rounded-md px-3 text-sm font-bold leading-9 transition',
                        'bg-white text-atlantia-wine shadow-sm' => $h === $dias,
                        'text-atlantia-ink/60 hover:text-atlantia-wine' => $h !== $dias,
                    ]) @if ($h === $dias) aria-current="true" @endif>Próximos {{ $dias }} días</a>
                @endforeach
            </div>
        </div>

        <dl class="grid gap-3 sm:grid-cols-3" data-resumen-prediccion>
            <div class="rounded-xl border border-atlantia-rose/15 bg-white p-4">
                <dt class="text-xs font-bold uppercase tracking-wide text-atlantia-ink/55">Se espera vender</dt>
                <dd class="mt-0.5 text-2xl font-black text-atlantia-ink">{{ number_format($resumen['esperado']) }} <span class="text-sm font-semibold text-atlantia-ink/50">unidades</span></dd>
                <dd class="text-xs text-atlantia-ink/55">en los próximos {{ $h }} días · {{ $resumen['con_ventas'] }} de {{ $resumen['productos'] }} productos con ventas</dd>
            </div>
            <div @class(['rounded-xl border p-4', 'border-amber-200 bg-amber-50' => $resumen['pedir'] > 0, 'border-atlantia-rose/15 bg-white' => $resumen['pedir'] === 0])>
                <dt class="text-xs font-bold uppercase tracking-wide text-atlantia-ink/55">Por reponer pronto</dt>
                <dd class="mt-0.5 text-2xl font-black {{ $resumen['pedir'] > 0 ? 'text-amber-800' : 'text-atlantia-ink' }}">{{ $resumen['pedir'] }}</dd>
                <dd class="text-xs text-atlantia-ink/55">{{ $resumen['pedir'] > 0 ? 'no te alcanzan para estos días' : 'tus existencias alcanzan' }}</dd>
            </div>
            <div class="rounded-xl border border-atlantia-rose/15 bg-white p-4">
                <dt class="text-xs font-bold uppercase tracking-wide text-atlantia-ink/55">Sin ventas aún</dt>
                <dd class="mt-0.5 text-2xl font-black text-atlantia-ink">{{ $resumen['sin_ventas'] }}</dd>
                <dd class="text-xs text-atlantia-ink/55">no hay datos para predecirlos todavía</dd>
            </div>
        </dl>

        <article class="overflow-hidden rounded-xl border border-atlantia-rose/15 bg-white shadow-[0_12px_32px_rgba(42,16,24,0.05)]">
            <form method="GET" action="{{ route('vendedor.predicciones.index') }}" class="flex flex-wrap items-center gap-2 border-b border-atlantia-rose/10 p-3">
                <input type="hidden" name="horizonte" value="{{ $h }}">
                <label class="relative min-w-[14rem] flex-1">
                    <span class="sr-only">Buscar producto</span>
                    <svg class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-atlantia-ink/40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>
                    <input type="search" name="q" value="{{ $q }}" placeholder="Buscar producto o SKU" class="h-10 w-full rounded-lg border border-atlantia-rose/25 pl-9 pr-3 text-sm outline-none focus:border-atlantia-wine focus:ring-2 focus:ring-atlantia-blush">
                </label>
                <a href="{{ route('vendedor.inventario.index') }}" class="inline-flex h-10 items-center rounded-lg border border-atlantia-rose/25 px-4 text-sm font-bold text-atlantia-wine hover:bg-atlantia-blush">Ir a Inventario</a>
            </form>

            @if ($filas->isEmpty())
                <div class="px-6 py-14 text-center">
                    <p class="text-base font-black text-atlantia-ink">{{ $q !== '' ? 'No hay productos con esa búsqueda' : 'Aún no tienes productos activos' }}</p>
                    <p class="mt-1 text-sm text-atlantia-ink/60">Publica productos y, cuando empiecen a venderse, aquí verás cuánto necesitarás.</p>
                </div>
            @else
                <div class="hidden grid-cols-[minmax(0,1fr)_6rem_7rem_5.5rem_7rem_9.5rem] gap-3 border-b border-atlantia-rose/10 bg-[#fbf8f7] px-4 py-2 text-xs font-bold text-atlantia-ink/55 lg:grid">
                    <span>Producto</span>
                    <span class="text-right">Vendidas (30 días)</span>
                    <span class="text-right">Se espera ({{ $h }} días)</span>
                    <span class="text-right">Tienes</span>
                    <span class="text-right">Te alcanza para</span>
                    <span class="text-right">Qué hacer</span>
                </div>
                <ul class="divide-y divide-atlantia-rose/10">
                    @foreach ($filas as $fila)
                        @php
                            $producto = $fila['producto'];
                            $imagen = $producto->getFirstMediaUrl('productos', 'thumbnail') ?: $producto->getFirstMediaUrl('productos') ?: ($producto->imagenPrincipal?->path ? \Illuminate\Support\Facades\Storage::url($producto->imagenPrincipal->path) : null);
                        @endphp
                        <li class="grid grid-cols-2 items-center gap-x-3 gap-y-1.5 px-4 py-3 lg:grid-cols-[minmax(0,1fr)_6rem_7rem_5.5rem_7rem_9.5rem]" data-fila-prediccion data-estado="{{ $fila['estado'] }}">
                            <div class="col-span-2 flex min-w-0 items-center gap-3 lg:col-span-1">
                                <div class="grid h-10 w-10 shrink-0 place-items-center overflow-hidden rounded-lg bg-[#f8f4f3] p-1">
                                    @if ($imagen)<img src="{{ $imagen }}" alt="" class="h-full w-full object-contain mix-blend-multiply" loading="lazy">@else<span class="font-black text-atlantia-wine">{{ \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($producto->nombre, 0, 1)) }}</span>@endif
                                </div>
                                <div class="min-w-0">
                                    <p class="truncate font-bold text-atlantia-ink">{{ $producto->nombre }}</p>
                                    @if ($fila['fuente'] === 'modelo')
                                        <p class="text-[11px] text-atlantia-ink/45">Predicción del modelo de Atlantia</p>
                                    @endif
                                </div>
                            </div>
                            <p class="text-sm text-atlantia-ink/70 lg:text-right"><span class="text-xs text-atlantia-ink/45 lg:hidden">Vendidas en 30 días: </span>{{ $fila['vendidas_30'] }}</p>
                            <p class="text-sm font-bold text-atlantia-ink lg:text-right"><span class="text-xs font-normal text-atlantia-ink/45 lg:hidden">Se espera en {{ $h }} días: </span>{{ $fila['estado'] === 'sin_ventas' ? '—' : $fila['esperado'] }}</p>
                            <p class="text-sm text-atlantia-ink/70 lg:text-right"><span class="text-xs text-atlantia-ink/45 lg:hidden">Tienes: </span>{{ $fila['disponible'] }}</p>
                            <p class="text-sm text-atlantia-ink/70 lg:text-right">
                                <span class="text-xs text-atlantia-ink/45 lg:hidden">Te alcanza para: </span>
                                @if ($fila['alcanza_dias'] === null)
                                    —
                                @elseif ($fila['alcanza_dias'] > 365)
                                    más de un año
                                @else
                                    {{ $fila['alcanza_dias'] }} {{ $fila['alcanza_dias'] === 1 ? 'día' : 'días' }}
                                @endif
                            </p>
                            <div class="col-span-2 lg:col-span-1 lg:text-right">
                                <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-bold ring-1 {{ $estados[$fila['estado']][1] }}">{{ $estados[$fila['estado']][0] }}</span>
                                @if (in_array($fila['estado'], ['agotado', 'pedir'], true) && $fila['faltan'] > 0)
                                    <p class="mt-0.5 text-[11px] text-atlantia-ink/55">Te faltarían {{ $fila['faltan'] }}</p>
                                @endif
                            </div>
                        </li>
                    @endforeach
                </ul>
            @endif
        </article>

        <p class="text-xs text-atlantia-ink/50">Es una estimación: si vendes más o menos de lo normal, cambia. Mientras más ventas tengas, más acertada será.</p>
    </section>
@endsection
