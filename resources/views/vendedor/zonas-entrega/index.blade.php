@extends('layouts.app')

@section('content')
    @php
        $zonasListado = collect($zonas['zonas'] ?? []);
        $resumen = $zonas['resumen'] ?? ['disponibles' => 0, 'activas' => 0];
        $porMunicipio = $zonasListado->groupBy(fn (array $zona): string => $zona['municipio'] ?: 'Otros', true)->sortKeys();
    @endphp

    <section class="mx-auto max-w-[1100px] space-y-4 pb-24">
        <form method="POST" action="{{ route('vendedor.zonas-entrega.sync') }}" id="vendor-delivery-zones-form" data-zonas-form>
            @csrf
            @method('PUT')

            <div class="flex flex-wrap items-end justify-between gap-3">
                <div class="max-w-2xl">
                    <h1 class="text-2xl font-black text-atlantia-ink">Zonas de entrega</h1>
                    <p class="mt-0.5 text-sm text-atlantia-ink/60">Activa los lugares donde entregas. Un cliente solo puede comprarte si su dirección está en una zona activa.</p>
                </div>
                <p class="rounded-full bg-white px-3 py-1.5 text-sm font-bold text-atlantia-ink ring-1 ring-atlantia-rose/15" data-zonas-resumen>
                    <span data-zonas-activas>{{ $resumen['activas'] }}</span> de {{ $resumen['disponibles'] }} zonas activas
                </p>
            </div>

            @if ($resumen['activas'] === 0 && $zonasListado->isNotEmpty())
                <p class="mt-3 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900" data-aviso-sin-zonas>
                    <strong>Tu tienda no tiene zonas activas.</strong> Los clientes podrán ver tus productos, pero no recibirlos a domicilio. Activa al menos una zona.
                </p>
            @endif

            <article class="mt-4 overflow-hidden rounded-xl border border-atlantia-rose/15 bg-white shadow-[0_12px_32px_rgba(42,16,24,0.05)]">
                <div class="flex flex-wrap items-center gap-2 border-b border-atlantia-rose/10 p-3">
                    <label class="relative min-w-[14rem] flex-1">
                        <span class="sr-only">Buscar zona</span>
                        <svg class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-atlantia-ink/40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>
                        <input type="search" id="delivery-zone-search" placeholder="Buscar barrio, colonia o municipio" class="h-10 w-full rounded-lg border border-atlantia-rose/25 pl-9 pr-3 text-sm outline-none focus:border-atlantia-wine focus:ring-2 focus:ring-atlantia-blush">
                    </label>
                    <div class="inline-flex rounded-lg bg-[#f5efee] p-1" role="group" aria-label="Mostrar">
                        @foreach (['all' => 'Todas', 'active' => 'Activas', 'inactive' => 'Sin activar'] as $valor => $texto)
                            <button type="button" data-zone-filter="{{ $valor }}" @class([
                                'h-8 rounded-md px-3 text-xs font-bold transition',
                                'bg-white text-atlantia-wine shadow-sm' => $valor === 'all',
                                'text-atlantia-ink/60 hover:text-atlantia-wine' => $valor !== 'all',
                            ]) aria-pressed="{{ $valor === 'all' ? 'true' : 'false' }}">{{ $texto }}</button>
                        @endforeach
                    </div>
                </div>

                @forelse ($porMunicipio as $municipio => $zonasMunicipio)
                    <div data-zone-group>
                        <div class="flex items-center justify-between gap-3 border-b border-atlantia-rose/10 bg-[#fbf8f7] px-4 py-2">
                            <p class="text-xs font-black uppercase tracking-wide text-atlantia-ink/60">{{ $municipio }} <span class="font-semibold normal-case tracking-normal text-atlantia-ink/40">· {{ $zonasMunicipio->count() }} {{ $zonasMunicipio->count() === 1 ? 'zona' : 'zonas' }}</span></p>
                            <div class="flex gap-3 text-xs font-bold">
                                <button type="button" class="text-atlantia-wine hover:underline" data-zone-bulk="1">Activar todas</button>
                                <button type="button" class="text-atlantia-ink/50 hover:text-atlantia-wine hover:underline" data-zone-bulk="0">Quitar todas</button>
                            </div>
                        </div>

                        <ul class="divide-y divide-atlantia-rose/10">
                            @foreach ($zonasMunicipio as $index => $zona)
                                <li
                                    @class(['grid grid-cols-[auto_minmax(0,1fr)] items-center gap-x-3 gap-y-2 px-4 py-3 transition sm:grid-cols-[auto_minmax(0,1fr)_auto_auto]', 'bg-atlantia-blush/30' => $zona['activa']])
                                    data-zone-row
                                    data-zone-name="{{ \Illuminate\Support\Str::lower($zona['nombre'].' '.$zona['municipio']) }}"
                                    data-zone-active="{{ $zona['activa'] ? '1' : '0' }}"
                                >
                                    <input type="hidden" name="zonas[{{ $index }}][delivery_zone_id]" value="{{ $zona['id'] }}">
                                    <input type="hidden" name="zonas[{{ $index }}][activa]" value="0">

                                    <label class="relative inline-flex cursor-pointer items-center" title="Activar o quitar esta zona">
                                        <input type="checkbox" name="zonas[{{ $index }}][activa]" value="1" class="peer sr-only" @checked($zona['activa']) data-zone-toggle aria-label="Entregar en {{ $zona['nombre'] }}">
                                        <span class="h-6 w-11 rounded-full bg-slate-200 transition peer-checked:bg-atlantia-wine peer-focus-visible:ring-2 peer-focus-visible:ring-atlantia-rose"></span>
                                        <span class="absolute left-0.5 h-5 w-5 rounded-full bg-white shadow transition peer-checked:translate-x-5"></span>
                                    </label>

                                    <div class="min-w-0">
                                        <p class="truncate font-bold text-atlantia-ink">{{ $zona['nombre'] }}</p>
                                        @if ($zona['descripcion'])
                                            <p class="truncate text-xs text-atlantia-ink/50">{{ $zona['descripcion'] }}</p>
                                        @endif
                                    </div>

                                    <label class="col-start-2 flex items-center gap-2 sm:col-start-auto" data-zone-field>
                                        <span class="text-xs text-atlantia-ink/55">Envío</span>
                                        <span class="relative">
                                            <span class="pointer-events-none absolute inset-y-0 left-2.5 flex items-center text-sm font-bold text-atlantia-ink/45">Q</span>
                                            <input name="zonas[{{ $index }}][costo_override]" value="{{ number_format((float) $zona['costo_envio'], 2, '.', '') }}" type="number" step="0.01" min="0" inputmode="decimal" class="h-9 w-24 rounded-lg border border-atlantia-rose/25 pl-6 pr-2 text-sm font-bold text-atlantia-ink outline-none focus:border-atlantia-wine focus:ring-2 focus:ring-atlantia-blush" aria-label="Costo de envío en {{ $zona['nombre'] }}">
                                        </span>
                                        @if (abs((float) $zona['costo_envio'] - (float) $zona['costo_base']) > 0.004)
                                            <span class="hidden text-[11px] text-atlantia-ink/45 lg:inline" title="Precio sugerido por Atlantia para esta zona">Sugerido Q{{ number_format((float) $zona['costo_base'], 2) }}</span>
                                        @endif
                                    </label>

                                    <label class="col-start-2 flex items-center gap-2 sm:col-start-auto" data-zone-field>
                                        <span class="text-xs text-atlantia-ink/55">Llega en</span>
                                        <input name="zonas[{{ $index }}][tiempo_estimado_min]" value="{{ (int) $zona['tiempo_estimado_min'] }}" type="number" min="5" max="240" inputmode="numeric" class="h-9 w-16 rounded-lg border border-atlantia-rose/25 px-2 text-center text-sm font-bold text-atlantia-ink outline-none focus:border-atlantia-wine focus:ring-2 focus:ring-atlantia-blush" aria-label="Minutos de entrega en {{ $zona['nombre'] }}">
                                        <span class="text-xs text-atlantia-ink/55">min</span>
                                    </label>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @empty
                    <p class="px-6 py-14 text-center text-sm text-atlantia-ink/60">Atlantia todavía no ha configurado zonas de entrega. Te avisaremos cuando estén disponibles.</p>
                @endforelse

                <p class="hidden px-6 py-10 text-center text-sm text-atlantia-ink/60" data-zone-vacio>No hay zonas con esa búsqueda.</p>
            </article>

            {{-- Barra fija para guardar --}}
            <div class="fixed inset-x-0 bottom-0 z-40 border-t border-atlantia-rose/15 bg-white/95 backdrop-blur xl:left-72">
                <div class="mx-auto flex max-w-[1100px] items-center justify-between gap-3 px-4 py-3">
                    <p class="text-sm text-atlantia-ink/60" data-zonas-cambios>Los cambios se aplican al guardar.</p>
                    <button type="submit" class="inline-flex h-10 items-center gap-2 rounded-lg bg-atlantia-wine px-5 text-sm font-bold text-white hover:bg-atlantia-wine-700">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2Z"/><path d="M17 21v-8H7v8"/><path d="M7 3v5h8"/></svg>
                        Guardar cambios
                    </button>
                </div>
            </div>
        </form>
    </section>

    <script @nonce>
        (() => {
            const form = document.querySelector('[data-zonas-form]');
            if (!form) return;

            const filas = [...form.querySelectorAll('[data-zone-row]')];
            const grupos = [...form.querySelectorAll('[data-zone-group]')];
            const buscar = document.getElementById('delivery-zone-search');
            const botonesFiltro = [...form.querySelectorAll('[data-zone-filter]')];
            const contador = form.querySelector('[data-zonas-activas]');
            const aviso = form.querySelector('[data-zonas-cambios]');
            const vacio = form.querySelector('[data-zone-vacio]');
            let filtro = 'all';
            let cambios = false;

            const pintar = (fila) => {
                const activa = fila.querySelector('[data-zone-toggle]').checked;
                fila.dataset.zoneActive = activa ? '1' : '0';
                fila.classList.toggle('bg-atlantia-blush/30', activa);
                fila.querySelectorAll('[data-zone-field]').forEach((campo) => campo.classList.toggle('opacity-50', !activa));
            };

            const filtrar = () => {
                const termino = (buscar?.value || '').trim().toLowerCase();
                let visibles = 0;
                filas.forEach((fila) => {
                    const texto = !termino || (fila.dataset.zoneName || '').includes(termino);
                    const estado = filtro === 'all'
                        || (filtro === 'active' && fila.dataset.zoneActive === '1')
                        || (filtro === 'inactive' && fila.dataset.zoneActive === '0');
                    fila.classList.toggle('hidden', !(texto && estado));
                    if (texto && estado) visibles += 1;
                });
                grupos.forEach((grupo) => grupo.classList.toggle('hidden', !grupo.querySelector('[data-zone-row]:not(.hidden)')));
                vacio?.classList.toggle('hidden', visibles > 0 || filas.length === 0);
            };

            const marcarCambio = () => {
                cambios = true;
                const activas = filas.filter((fila) => fila.dataset.zoneActive === '1').length;
                if (contador) contador.textContent = activas;
                document.querySelector('[data-aviso-sin-zonas]')?.classList.toggle('hidden', activas > 0);
                if (aviso) {
                    aviso.textContent = 'Tienes cambios sin guardar.';
                    aviso.className = 'text-sm font-bold text-amber-700';
                }
            };

            filas.forEach((fila) => {
                pintar(fila);
                fila.querySelector('[data-zone-toggle]').addEventListener('change', () => { pintar(fila); marcarCambio(); filtrar(); });
                fila.querySelectorAll('input[type="number"]').forEach((campo) => campo.addEventListener('input', marcarCambio));
            });

            form.querySelectorAll('[data-zone-bulk]').forEach((boton) => {
                boton.addEventListener('click', () => {
                    const activar = boton.dataset.zoneBulk === '1';
                    boton.closest('[data-zone-group]').querySelectorAll('[data-zone-row]:not(.hidden)').forEach((fila) => {
                        fila.querySelector('[data-zone-toggle]').checked = activar;
                        pintar(fila);
                    });
                    marcarCambio();
                    filtrar();
                });
            });

            buscar?.addEventListener('input', filtrar);
            botonesFiltro.forEach((boton) => boton.addEventListener('click', () => {
                filtro = boton.dataset.zoneFilter || 'all';
                botonesFiltro.forEach((item) => {
                    const activo = item === boton;
                    item.setAttribute('aria-pressed', activo ? 'true' : 'false');
                    item.className = 'h-8 rounded-md px-3 text-xs font-bold transition ' + (activo ? 'bg-white text-atlantia-wine shadow-sm' : 'text-atlantia-ink/60 hover:text-atlantia-wine');
                });
                filtrar();
            }));

            form.addEventListener('submit', () => { cambios = false; });
            window.addEventListener('beforeunload', (evento) => {
                if (!cambios) return;
                evento.preventDefault();
                evento.returnValue = '';
            });
        })();
    </script>
@endsection
