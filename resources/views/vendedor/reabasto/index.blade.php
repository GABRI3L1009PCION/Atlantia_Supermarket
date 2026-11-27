@extends('layouts.app')

@section('content')
    @php
        $filas = $lista['filas'];
        $resumen = $lista['resumen'];
        $dias = \App\Services\Inventario\ReabastoInteligenteService::DIAS_COBERTURA;
        $q = (string) request('q', '');
        $urgencias = [
            'ya' => ['Pídelo ya', 'bg-rose-50 text-rose-700 ring-rose-200'],
            'pronto' => ['Esta semana', 'bg-amber-50 text-amber-800 ring-amber-200'],
            'cuando_puedas' => ['Cuando puedas', 'bg-sky-50 text-sky-700 ring-sky-200'],
        ];
    @endphp

    <section class="mx-auto max-w-[1100px] space-y-4 pb-10">
        <div class="flex flex-wrap items-end justify-between gap-3">
            <div class="max-w-2xl">
                <h1 class="text-2xl font-black text-atlantia-ink">Reabasto sugerido</h1>
                <p class="mt-0.5 text-sm text-atlantia-ink/60">Tu lista para pedir a tus proveedores: lo necesario para cubrir {{ $dias }} días de ventas más tu mínimo. Cuando te llegue, márcalo y se suma a tu inventario.</p>
            </div>
            <button type="button" data-print-page class="inline-flex h-10 items-center gap-2 rounded-lg border border-atlantia-rose/25 bg-white px-4 text-sm font-bold text-atlantia-wine hover:bg-atlantia-blush print:hidden" @disabled($filas->isEmpty())>
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M6 9V2h12v7M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2M6 14h12v8H6z"/></svg>
                Imprimir lista
            </button>
        </div>

        <dl class="grid gap-3 sm:grid-cols-3" data-resumen-reabasto>
            <div @class(['rounded-xl border p-4', 'border-rose-200 bg-rose-50' => $resumen['urgentes'] > 0, 'border-atlantia-rose/15 bg-white' => $resumen['urgentes'] === 0])>
                <dt class="text-xs font-bold uppercase tracking-wide text-atlantia-ink/55">Pedir ya</dt>
                <dd class="mt-0.5 text-2xl font-black {{ $resumen['urgentes'] > 0 ? 'text-rose-700' : 'text-atlantia-ink' }}">{{ $resumen['urgentes'] }}</dd>
                <dd class="text-xs text-atlantia-ink/55">agotados o para 3 días o menos</dd>
            </div>
            <div class="rounded-xl border border-atlantia-rose/15 bg-white p-4">
                <dt class="text-xs font-bold uppercase tracking-wide text-atlantia-ink/55">En la lista</dt>
                <dd class="mt-0.5 text-2xl font-black text-atlantia-ink">{{ $resumen['por_pedir'] }} <span class="text-sm font-semibold text-atlantia-ink/50">productos</span></dd>
                <dd class="text-xs text-atlantia-ink/55">{{ number_format($resumen['unidades']) }} unidades en total</dd>
            </div>
            <div class="rounded-xl border border-atlantia-rose/15 bg-white p-4">
                <dt class="text-xs font-bold uppercase tracking-wide text-atlantia-ink/55">Al día</dt>
                <dd class="mt-0.5 text-2xl font-black text-emerald-700">{{ $resumen['al_dia'] }}</dd>
                <dd class="text-xs text-atlantia-ink/55">productos que no necesitas pedir</dd>
            </div>
        </dl>

        <article class="overflow-hidden rounded-xl border border-atlantia-rose/15 bg-white shadow-[0_12px_32px_rgba(42,16,24,0.05)]">
            <form method="GET" action="{{ route('vendedor.reabasto.index') }}" class="flex flex-wrap items-center gap-2 border-b border-atlantia-rose/10 p-3 print:hidden">
                <label class="relative min-w-[14rem] flex-1">
                    <span class="sr-only">Buscar producto</span>
                    <svg class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-atlantia-ink/40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>
                    <input type="search" name="q" value="{{ $q }}" placeholder="Buscar producto o SKU" class="h-10 w-full rounded-lg border border-atlantia-rose/25 pl-9 pr-3 text-sm outline-none focus:border-atlantia-wine focus:ring-2 focus:ring-atlantia-blush">
                </label>
                <a href="{{ route('vendedor.predicciones.index') }}" class="inline-flex h-10 items-center rounded-lg border border-atlantia-rose/25 px-4 text-sm font-bold text-atlantia-wine hover:bg-atlantia-blush">Ver predicción</a>
            </form>

            @if ($filas->isEmpty())
                <div class="px-6 py-14 text-center">
                    <span class="mx-auto grid h-12 w-12 place-items-center rounded-full bg-emerald-50 text-emerald-600">
                        <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m5 12 5 5 9-10"/></svg>
                    </span>
                    <p class="mt-3 text-base font-black text-atlantia-ink">{{ $q !== '' ? 'Ese producto no necesita reabasto' : 'No necesitas pedir nada por ahora' }}</p>
                    <p class="mt-1 text-sm text-atlantia-ink/60">Tus existencias cubren los próximos {{ $dias }} días de ventas y están arriba de tu mínimo.</p>
                </div>
            @else
                <div class="hidden grid-cols-[minmax(0,1fr)_6rem_7rem_8rem_15rem] gap-3 border-b border-atlantia-rose/10 bg-[#fbf8f7] px-4 py-2 text-xs font-bold text-atlantia-ink/55 lg:grid">
                    <span>Producto</span>
                    <span class="text-right">Tienes</span>
                    <span class="text-right">Te alcanza para</span>
                    <span class="text-right">Pide</span>
                    <span class="text-right print:hidden">¿Ya te llegó?</span>
                </div>
                <ul class="divide-y divide-atlantia-rose/10">
                    @foreach ($filas as $fila)
                        @php $producto = $fila['producto']; @endphp
                        <li class="grid grid-cols-2 items-center gap-x-3 gap-y-2 px-4 py-3 lg:grid-cols-[minmax(0,1fr)_6rem_7rem_8rem_15rem]" data-fila-reabasto>
                            <div class="col-span-2 min-w-0 lg:col-span-1">
                                <div class="flex flex-wrap items-center gap-2">
                                    <p class="truncate font-bold text-atlantia-ink">{{ $producto->nombre }}</p>
                                    <span class="rounded-full px-2 py-0.5 text-[11px] font-bold ring-1 {{ $urgencias[$fila['urgencia']][1] }}">{{ $urgencias[$fila['urgencia']][0] }}</span>
                                </div>
                                <p class="text-xs text-atlantia-ink/50">
                                    {{ $fila['vendidas_30'] > 0 ? 'Vendiste '.$fila['vendidas_30'].' en 30 días' : 'Sin ventas aún: solo para llegar a tu mínimo' }} · mínimo {{ $fila['minimo'] }}
                                </p>
                            </div>
                            <p class="text-sm text-atlantia-ink/70 lg:text-right"><span class="text-xs text-atlantia-ink/45 lg:hidden">Tienes: </span>{{ $fila['disponible'] }}</p>
                            <p class="text-sm text-atlantia-ink/70 lg:text-right">
                                <span class="text-xs text-atlantia-ink/45 lg:hidden">Te alcanza para: </span>
                                @if ($fila['disponible'] <= 0)
                                    <span class="font-bold text-rose-700">Agotado</span>
                                @elseif ($fila['alcanza_dias'] === null)
                                    —
                                @else
                                    {{ $fila['alcanza_dias'] }} {{ $fila['alcanza_dias'] === 1 ? 'día' : 'días' }}
                                @endif
                            </p>
                            <p class="text-base font-black text-atlantia-wine lg:text-right"><span class="text-xs font-normal text-atlantia-ink/45 lg:hidden">Pide: </span>{{ $fila['sugerido'] }} <span class="text-xs font-semibold text-atlantia-ink/50">{{ $fila['sugerido'] === 1 ? 'unidad' : 'unidades' }}</span></p>
                            <form method="POST" action="{{ route('vendedor.reabasto.recibir', $producto->uuid) }}" class="col-span-2 flex items-center justify-end gap-2 lg:col-span-1 print:hidden">
                                @csrf
                                <label class="sr-only" for="recibido-{{ $producto->id }}">Unidades recibidas de {{ $producto->nombre }}</label>
                                <input id="recibido-{{ $producto->id }}" type="number" name="cantidad" min="1" value="{{ $fila['sugerido'] }}" class="h-9 w-20 rounded-lg border border-atlantia-rose/25 px-2 text-center text-sm font-bold outline-none focus:border-atlantia-wine focus:ring-2 focus:ring-atlantia-blush">
                                <button type="submit" class="h-9 whitespace-nowrap rounded-lg bg-atlantia-wine px-3 text-xs font-bold text-white hover:bg-atlantia-wine-700">Ya lo recibí</button>
                            </form>
                        </li>
                    @endforeach
                </ul>
            @endif
        </article>
        <p class="text-xs text-atlantia-ink/50">«Ya lo recibí» suma esas unidades a tu inventario y queda registrado en tus movimientos.</p>
    </section>
@endsection
