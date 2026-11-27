@extends('layouts.app')

@section('content')
    @php
        $resumen = $dashboard['summary'];
        $ultima = $dashboard['last_update'];
        $chips = [
            'todos' => ['Todos', $resumen['productos'], 'text-atlantia-ink'],
            'pocas' => ['Quedan pocas', $resumen['pocas'], 'text-amber-700'],
            'agotados' => ['Agotados', $resumen['agotados'], 'text-rose-700'],
        ];
        $campo = 'h-9 w-full rounded-lg border border-atlantia-rose/25 bg-white px-2 text-center text-sm font-bold text-atlantia-ink outline-none focus:border-atlantia-wine focus:ring-2 focus:ring-atlantia-blush';
    @endphp

    <section class="mx-auto max-w-[1280px] space-y-4 pb-10">
        <div class="flex flex-wrap items-end justify-between gap-3">
            <div>
                <h1 class="text-2xl font-black text-atlantia-ink">Inventario</h1>
                <p class="mt-0.5 text-sm text-atlantia-ink/60">
                    {{ $vendor?->business_name }} · Cambia tus existencias sin abrir cada producto.
                    @if ($ultima) <span class="text-atlantia-ink/45">Último cambio: {{ $ultima->format('d/m/Y h:i a') }}</span> @endif
                </p>
            </div>
            <dl class="flex flex-wrap gap-2 text-sm" data-resumen-inventario>
                <div class="rounded-xl border border-atlantia-rose/15 bg-white px-3 py-2"><dt class="text-xs text-atlantia-ink/55">Para vender</dt><dd class="font-black text-atlantia-ink">{{ number_format($resumen['disponibles']) }} unidades</dd></div>
                <div class="rounded-xl border border-atlantia-rose/15 bg-white px-3 py-2" title="Unidades separadas en pedidos que aún no se entregan"><dt class="text-xs text-atlantia-ink/55">Apartadas en pedidos</dt><dd class="font-black text-atlantia-ink">{{ number_format($resumen['apartadas']) }}</dd></div>
            </dl>
        </div>

        <div class="grid gap-4 xl:grid-cols-[minmax(0,1fr)_20rem]">
            <article class="overflow-hidden rounded-xl border border-atlantia-rose/15 bg-white shadow-[0_12px_32px_rgba(42,16,24,0.05)]" data-inventario>
                <div class="flex flex-wrap items-center gap-2 border-b border-atlantia-rose/10 p-3">
                    <label class="relative min-w-[12rem] flex-1">
                        <span class="sr-only">Buscar producto</span>
                        <svg class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-atlantia-ink/40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>
                        <input type="search" placeholder="Buscar producto o SKU" class="h-10 w-full rounded-lg border border-atlantia-rose/25 pl-9 pr-3 text-sm outline-none focus:border-atlantia-wine focus:ring-2 focus:ring-atlantia-blush" data-buscar-inventario>
                    </label>
                    <div class="inline-flex rounded-lg bg-[#f5efee] p-1" role="group" aria-label="Mostrar">
                        @foreach ($chips as $clave => [$texto, $cuenta, $color])
                            <button type="button" data-filtro-inventario="{{ $clave }}" @class([
                                'h-8 rounded-md px-3 text-xs font-bold transition',
                                'bg-white text-atlantia-wine shadow-sm' => $clave === 'todos',
                                'text-atlantia-ink/60 hover:text-atlantia-wine' => $clave !== 'todos',
                            ])>{{ $texto }} <span class="{{ $cuenta > 0 && $clave !== 'todos' ? $color : 'opacity-60' }}">{{ $cuenta }}</span></button>
                        @endforeach
                    </div>
                </div>

                @if ($inventario->isEmpty())
                    <div class="px-6 py-14 text-center">
                        <p class="text-base font-black text-atlantia-ink">Aún no tienes productos</p>
                        <p class="mt-1 text-sm text-atlantia-ink/60">Crea productos y aquí podrás controlar cuántas unidades te quedan.</p>
                        <a href="{{ route('vendedor.productos.index') }}" class="mt-4 inline-flex h-10 items-center rounded-lg bg-atlantia-wine px-5 text-sm font-bold text-white hover:bg-atlantia-wine-700">Ir a Productos</a>
                    </div>
                @else
                    <div class="hidden grid-cols-[3rem_minmax(0,1fr)_7rem_6rem_6rem_6.5rem] gap-3 border-b border-atlantia-rose/10 bg-[#fbf8f7] px-4 py-2 text-xs font-bold text-atlantia-ink/55 lg:grid">
                        <span></span><span>Producto</span>
                        <span class="text-center" title="Unidades que tienes en total">Tienes</span>
                        <span class="text-center" title="Te avisamos cuando queden estas o menos">Avisarme con</span>
                        <span class="text-center" title="Opcional: lo máximo que guardas">Máximo</span>
                        <span></span>
                    </div>
                    <div class="divide-y divide-atlantia-rose/10">
                        @foreach ($inventario as $item)
                            @php
                                $producto = $item->producto;
                                $imagen = $producto?->getFirstMediaUrl('productos', 'thumbnail') ?: $producto?->getFirstMediaUrl('productos') ?: ($producto?->imagenPrincipal?->path ? \Illuminate\Support\Facades\Storage::url($producto->imagenPrincipal->path) : null);
                                $disponible = max(0, (int) $item->stock_actual - (int) $item->stock_reservado);
                                $estado = $disponible <= 0 ? 'agotados' : ($disponible <= (int) $item->stock_minimo ? 'pocas' : 'ok');
                            @endphp
                            <form method="POST" action="{{ route('vendedor.inventario.update', $producto->uuid) }}"
                                  class="grid grid-cols-[3rem_minmax(0,1fr)] items-center gap-x-3 gap-y-2 px-4 py-3 lg:grid-cols-[3rem_minmax(0,1fr)_7rem_6rem_6rem_6.5rem]"
                                  data-fila-inventario data-estado="{{ $estado }}" data-nombre="{{ \Illuminate\Support\Str::lower(($producto?->nombre ?? '').' '.($producto?->sku ?? '')) }}">
                                @csrf
                                @method('PUT')
                                <div class="grid h-12 w-12 place-items-center overflow-hidden rounded-lg bg-[#f8f4f3] p-1">
                                    @if ($imagen)
                                        <img src="{{ $imagen }}" alt="" class="h-full w-full object-contain mix-blend-multiply" loading="lazy">
                                    @else
                                        <span class="font-black text-atlantia-wine">{{ \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($producto?->nombre ?? 'P', 0, 1)) }}</span>
                                    @endif
                                </div>
                                <div class="min-w-0">
                                    <p class="truncate font-bold text-atlantia-ink">{{ $producto?->nombre }}</p>
                                    <p class="truncate text-xs">
                                        <span @class(['font-bold', 'text-rose-700' => $estado === 'agotados', 'text-amber-700' => $estado === 'pocas', 'text-emerald-700' => $estado === 'ok'])>
                                            {{ $estado === 'agotados' ? 'Agotado' : $disponible.' para vender' }}
                                        </span>
                                        @if ($item->stock_reservado > 0)
                                            <span class="text-atlantia-ink/50">· {{ $item->stock_reservado }} apartadas</span>
                                        @endif
                                        <span class="font-mono text-atlantia-ink/40">· {{ $producto?->sku }}</span>
                                    </p>
                                </div>

                                <label class="col-start-2 flex items-center gap-2 lg:col-start-auto lg:block">
                                    <span class="w-24 text-xs text-atlantia-ink/55 lg:sr-only">Tienes</span>
                                    <span class="flex items-center">
                                        <button type="button" class="grid h-9 w-7 place-items-center rounded-l-lg border border-r-0 border-atlantia-rose/25 text-atlantia-wine hover:bg-atlantia-blush" data-paso="-1" aria-label="Restar una">−</button>
                                        <input name="stock_actual" type="number" min="{{ (int) $item->stock_reservado }}" value="{{ $item->stock_actual }}" class="{{ $campo }} rounded-none px-1" data-original="{{ $item->stock_actual }}">
                                        <button type="button" class="grid h-9 w-7 place-items-center rounded-r-lg border border-l-0 border-atlantia-rose/25 text-atlantia-wine hover:bg-atlantia-blush" data-paso="1" aria-label="Sumar una">+</button>
                                    </span>
                                </label>
                                <label class="col-start-2 flex items-center gap-2 lg:col-start-auto lg:block">
                                    <span class="w-24 text-xs text-atlantia-ink/55 lg:sr-only">Avisarme con</span>
                                    <input name="stock_minimo" type="number" min="0" value="{{ $item->stock_minimo }}" class="{{ $campo }}" data-original="{{ $item->stock_minimo }}">
                                </label>
                                <label class="col-start-2 flex items-center gap-2 lg:col-start-auto lg:block">
                                    <span class="w-24 text-xs text-atlantia-ink/55 lg:sr-only">Máximo</span>
                                    <input name="stock_maximo" type="number" min="0" value="{{ $item->stock_maximo }}" placeholder="—" class="{{ $campo }}" data-original="{{ $item->stock_maximo }}">
                                </label>
                                <div class="col-start-2 lg:col-start-auto">
                                    <button type="submit" class="h-9 w-full rounded-lg border border-atlantia-rose/25 px-3 text-xs font-bold text-atlantia-ink/40 transition" disabled data-guardar-fila>Guardar</button>
                                </div>
                            </form>
                        @endforeach
                    </div>
                    <p class="hidden px-6 py-10 text-center text-sm text-atlantia-ink/60" data-inventario-vacio>No hay productos con ese filtro.</p>
                    @if ($inventario->hasPages())
                        <div class="border-t border-atlantia-rose/10 px-4 py-3">{{ $inventario->links() }}</div>
                    @endif
                @endif
            </article>

            <aside class="rounded-xl border border-atlantia-rose/15 bg-white p-4 xl:self-start" data-movimientos>
                <h2 class="text-sm font-black text-atlantia-ink">Movimientos recientes</h2>
                <p class="text-xs text-atlantia-ink/50">Lo que cambiaste y lo que vendiste.</p>
                <ul class="mt-3 space-y-2.5">
                    @forelse ($dashboard['movements'] as $movimiento)
                        @php $sube = $movimiento['delta'] >= 0; @endphp
                        <li class="flex items-center gap-3">
                            <span class="grid h-8 w-8 shrink-0 place-items-center rounded-full {{ $sube ? 'bg-emerald-50 text-emerald-600' : 'bg-rose-50 text-rose-600' }}">
                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" aria-hidden="true">
                                    @if ($sube)<path d="M12 19V5M5 12l7-7 7 7"/>@else<path d="M12 5v14M19 12l-7 7-7-7"/>@endif
                                </svg>
                            </span>
                            <div class="min-w-0 flex-1">
                                <p class="truncate text-sm font-bold text-atlantia-ink">{{ $movimiento['product'] }}</p>
                                <p class="truncate text-xs text-atlantia-ink/50">{{ $movimiento['type'] }} · {{ $movimiento['date']?->format('d/m h:i a') }}</p>
                            </div>
                            <span class="shrink-0 text-sm font-black {{ $sube ? 'text-emerald-700' : 'text-rose-700' }}">{{ $sube ? '+' : '' }}{{ $movimiento['delta'] }}</span>
                        </li>
                    @empty
                        <li class="rounded-lg bg-[#fbf8f7] px-3 py-4 text-center text-sm text-atlantia-ink/55">Todavía no hay movimientos.</li>
                    @endforelse
                </ul>
            </aside>
        </div>
    </section>

    <script @nonce>
        (() => {
            const lista = document.querySelector('[data-inventario]');
            if (!lista) return;
            const filas = [...lista.querySelectorAll('[data-fila-inventario]')];
            const buscar = lista.querySelector('[data-buscar-inventario]');
            const botones = [...lista.querySelectorAll('[data-filtro-inventario]')];
            const vacio = lista.querySelector('[data-inventario-vacio]');
            let filtro = 'todos';

            const revisarCambios = (fila) => {
                const cambio = [...fila.querySelectorAll('input[data-original]')].some((campo) => campo.value !== (campo.dataset.original ?? ''));
                const boton = fila.querySelector('[data-guardar-fila]');
                boton.disabled = !cambio;
                boton.className = 'h-9 w-full rounded-lg px-3 text-xs font-bold transition ' + (cambio ? 'bg-atlantia-wine text-white hover:bg-atlantia-wine-700' : 'border border-atlantia-rose/25 text-atlantia-ink/40');
            };

            const filtrar = () => {
                const termino = (buscar?.value || '').trim().toLowerCase();
                let visibles = 0;
                filas.forEach((fila) => {
                    const ok = (!termino || fila.dataset.nombre.includes(termino)) && (filtro === 'todos' || fila.dataset.estado === filtro);
                    fila.classList.toggle('hidden', !ok);
                    if (ok) visibles += 1;
                });
                vacio?.classList.toggle('hidden', visibles > 0);
            };

            filas.forEach((fila) => {
                fila.addEventListener('input', () => revisarCambios(fila));
                fila.querySelectorAll('[data-paso]').forEach((boton) => boton.addEventListener('click', () => {
                    const campo = fila.querySelector('input[name="stock_actual"]');
                    const minimo = Number(campo.min || 0);
                    campo.value = Math.max(minimo, (Number(campo.value) || 0) + Number(boton.dataset.paso));
                    revisarCambios(fila);
                }));
            });

            buscar?.addEventListener('input', filtrar);
            botones.forEach((boton) => boton.addEventListener('click', () => {
                filtro = boton.dataset.filtroInventario;
                botones.forEach((item) => {
                    item.className = 'h-8 rounded-md px-3 text-xs font-bold transition ' + (item === boton ? 'bg-white text-atlantia-wine shadow-sm' : 'text-atlantia-ink/60 hover:text-atlantia-wine');
                });
                filtrar();
            }));
        })();
    </script>
@endsection
