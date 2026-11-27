@extends('layouts.app')

@section('content')
    @php
        $campo = 'mt-1 h-10 w-full rounded-lg border border-atlantia-rose/30 bg-white px-3 text-sm text-atlantia-ink outline-none focus:border-atlantia-wine focus:ring-2 focus:ring-atlantia-blush';
        $etiqueta = 'text-xs font-bold text-atlantia-ink/70';
        $q = fn ($v): string => 'Q'.rtrim(rtrim(number_format((float) $v, 2), '0'), '.');
    @endphp

    <section class="mx-auto max-w-[1000px] space-y-4 pb-10">
        <div class="flex flex-wrap items-end justify-between gap-3">
            <div class="max-w-2xl">
                <h1 class="text-2xl font-black text-atlantia-ink">Precios de planes</h1>
                <p class="mt-0.5 text-sm text-atlantia-ink/60">Lo que cambies aquí se ve de inmediato en “Vende en Atlantia”, en el panel de cada tienda y en los cobros. Las tiendas que ya tienen un plan pagan el precio nuevo desde su próxima mensualidad.</p>
            </div>
            <a href="{{ route('admin.servicios.index') }}" class="inline-flex h-10 items-center rounded-lg border border-atlantia-rose/30 px-4 text-sm font-bold text-atlantia-wine hover:bg-atlantia-blush">Ver cobros de planes</a>
        </div>

        {{-- Promoción de apertura --}}
        @php
            $ap = $errors->getBag('apertura');
            $apDias = $ap->any() ? old('dias') : ($apertura['dias'] ?? 30);
            $apHasta = $ap->any() ? old('hasta') : ($apertura['hasta'] ?? '');
            $apActiva = $ap->any() ? (bool) old('activa') : (bool) ($apertura['activa'] ?? false);
        @endphp
        <article id="apertura" class="rounded-xl border {{ $aperturaVigente ? 'border-emerald-300' : 'border-atlantia-rose/15' }} bg-white p-5">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div class="max-w-2xl">
                    <h2 class="text-base font-black text-atlantia-ink">Promoción de apertura</h2>
                    <p class="text-xs text-atlantia-ink/55">Cada tienda prueba gratis el plan que elija, una sola vez. En el plan Inicial son días sin la comisión de Atlantia. La tarifa de la pasarela (tarjeta) se cobra siempre. Al terminar, si no paga, pasa al plan Inicial sin cerrar. Las tiendas nuevas la reciben solas al ser aprobadas.</p>
                </div>
                @if ($aperturaVigente)
                    <span class="rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-bold text-emerald-700 ring-1 ring-emerald-200">Activa</span>
                @elseif ($apertura['activa'] ?? false)
                    <span class="rounded-full bg-amber-50 px-2.5 py-1 text-xs font-bold text-amber-800 ring-1 ring-amber-200">Terminó su fecha</span>
                @else
                    <span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-bold text-slate-600 ring-1 ring-slate-200">Apagada</span>
                @endif
            </div>
            <form method="POST" action="{{ route('admin.precios-planes.apertura') }}" class="mt-3 grid gap-3 sm:grid-cols-[auto_160px_200px_auto] sm:items-end" data-confirm="¿Guardar la promoción de apertura? Se ve de inmediato en la página para vendedores.">
                @csrf
                @method('PUT')
                <label class="inline-flex h-10 items-center gap-2 text-sm font-bold text-atlantia-ink">
                    <input type="hidden" name="activa" value="0">
                    <input type="checkbox" name="activa" value="1" @checked($apActiva) class="h-4 w-4 rounded border-atlantia-rose/40 text-atlantia-wine focus:ring-atlantia-blush">
                    Activar promoción
                </label>
                <label class="block">
                    <span class="{{ $etiqueta }}">Días gratis</span>
                    <input type="number" name="dias" min="1" max="90" required value="{{ $apDias }}" class="{{ $campo }}">
                </label>
                <label class="block">
                    <span class="{{ $etiqueta }}">Aceptar tiendas hasta (opcional)</span>
                    <input type="date" name="hasta" value="{{ $apHasta }}" min="{{ now()->toDateString() }}" class="{{ $campo }}">
                </label>
                <button type="submit" class="h-10 rounded-lg bg-atlantia-wine px-5 text-sm font-bold text-white hover:bg-atlantia-wine-700">Guardar</button>
            </form>
            @if ($ap->any())
                <p class="mt-1 text-xs font-bold text-red-600">{{ $ap->first() }}</p>
            @endif

            @if ($enPrueba->isNotEmpty())
                <div class="mt-4 overflow-x-auto rounded-lg border border-atlantia-rose/10">
                    <table class="w-full min-w-[560px] text-left text-sm">
                        <thead class="bg-atlantia-blush/40 text-xs text-atlantia-ink/60">
                            <tr><th class="px-3 py-2">Tienda</th><th class="px-3 py-2">Plan probado</th><th class="px-3 py-2">Gratis hasta</th><th class="px-3 py-2">Estado</th></tr>
                        </thead>
                        <tbody>
                            @foreach ($enPrueba as $tienda)
                                @php
                                    $pago = \App\Services\Monetizacion\PruebaAperturaService::pagoDespues($tienda);
                                @endphp
                                <tr class="border-t border-atlantia-rose/10" data-tienda-prueba>
                                    <td class="px-3 py-2"><a href="{{ route('admin.vendedores.show', $tienda) }}" class="font-bold text-atlantia-wine hover:underline">{{ $tienda->business_name }}</a></td>
                                    <td class="px-3 py-2">{{ $planes[$tienda->prueba_plan]['nombre'] ?? $tienda->prueba_plan }}</td>
                                    <td class="px-3 py-2">{{ $tienda->prueba_hasta?->format('d/m/Y') }}</td>
                                    <td class="px-3 py-2">
                                        @if ($pago)
                                            <span class="rounded-full bg-emerald-50 px-2 py-0.5 text-xs font-bold text-emerald-700">Pagó su plan</span>
                                        @elseif ($tienda->enPrueba())
                                            <span class="rounded-full bg-sky-50 px-2 py-0.5 text-xs font-bold text-sky-700">En prueba · {{ max(1, (int) ceil(now()->diffInHours($tienda->prueba_hasta) / 24)) }} días</span>
                                        @else
                                            <span class="rounded-full bg-slate-100 px-2 py-0.5 text-xs font-bold text-slate-600">Terminó sin pagar</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <p class="mt-3 text-xs text-atlantia-ink/55">Todavía ninguna tienda ha usado la prueba gratis.</p>
            @endif
        </article>

        @foreach ($planes as $clave => $plan)
            @php
                $esBase = $clave === $base;
                $cotizado = (bool) ($plan['cotizacion'] ?? false);
                $bolsa = $errors->getBag('plan_'.$clave);
                $abierto = $bolsa->any();
                $valor = fn (string $campoPlan) => $abierto ? old($campoPlan) : ($plan[$campoPlan] ?? null);
                $cambiado = in_array($clave, $cambiados, true);
            @endphp
            <details id="plan-{{ $clave }}" class="group rounded-xl border border-atlantia-rose/15 bg-white" @if ($abierto) open @endif data-plan="{{ $clave }}">
                <summary class="flex cursor-pointer list-none flex-wrap items-center justify-between gap-3 px-5 py-4 [&::-webkit-details-marker]:hidden">
                    <div class="min-w-0">
                        <p class="flex flex-wrap items-center gap-2 text-base font-black text-atlantia-ink">
                            {{ $plan['nombre'] }}
                            @if ($cambiado)<span class="rounded-full bg-sky-50 px-2 py-0.5 text-[11px] font-bold text-sky-700 ring-1 ring-sky-200">Precio cambiado</span>@endif
                            @if ($esBase)<span class="rounded-full bg-slate-100 px-2 py-0.5 text-[11px] font-bold text-slate-600">Plan gratis de entrada</span>@endif
                        </p>
                        <p class="text-xs text-atlantia-ink/55">
                            @if ($cotizado) Precio por cotización @else {{ $q($plan['precio'] ?? 0) }} al mes @endif
                            · {{ (float) ($plan['comision'] ?? 0) > 0 ? rtrim(rtrim(number_format((float) $plan['comision'], 2), '0'), '.').' % por venta' : 'sin comisión' }}
                            · {{ $plan['max_productos'] ? number_format((int) $plan['max_productos']).' productos' : 'productos acordados' }}
                            · {{ (int) ($tiendas[$clave] ?? 0) }} {{ (int) ($tiendas[$clave] ?? 0) === 1 ? 'tienda' : 'tiendas' }}
                        </p>
                    </div>
                    <span class="text-xs font-bold text-atlantia-wine group-open:hidden">Editar</span>
                    <span class="hidden text-xs font-bold text-atlantia-ink/50 group-open:inline">Cerrar</span>
                </summary>

                <form method="POST" action="{{ route('admin.precios-planes.plan', $clave) }}" class="grid gap-3 border-t border-atlantia-rose/10 px-5 py-4 sm:grid-cols-3"
                      data-confirm="¿Guardar los cambios del plan {{ $plan['nombre'] }}? Se ven de inmediato en la tienda.">
                    @csrf
                    @method('PUT')
                    <label class="block">
                        <span class="{{ $etiqueta }}">Precio al mes (Q, con IVA)</span>
                        @if ($esBase || $cotizado)
                            <input type="text" value="{{ $cotizado ? 'Por cotización' : 'Q0 (gratis)' }}" disabled class="{{ $campo }} bg-slate-50 text-atlantia-ink/50">
                        @else
                            <input type="number" name="precio" step="0.01" min="1" required value="{{ $valor('precio') }}" class="{{ $campo }}">
                        @endif
                    </label>
                    <label class="block">
                        <span class="{{ $etiqueta }}">Comisión por venta (%)</span>
                        <input type="number" name="comision" step="0.01" min="0" max="50" required value="{{ $valor('comision') }}" class="{{ $campo }}">
                    </label>
                    <label class="block">
                        <span class="{{ $etiqueta }}">Productos que permite</span>
                        @if ($cotizado)
                            <input type="text" value="Se acuerda con cada tienda" disabled class="{{ $campo }} bg-slate-50 text-atlantia-ink/50">
                        @else
                            <input type="number" name="max_productos" min="1" required value="{{ $valor('max_productos') }}" class="{{ $campo }}">
                        @endif
                    </label>
                    <label class="block">
                        <span class="{{ $etiqueta }}">Promociones activas</span>
                        <input type="number" name="max_promociones" min="1" value="{{ $valor('max_promociones') }}" placeholder="Vacío = ilimitadas" class="{{ $campo }}">
                    </label>
                    <label class="block">
                        <span class="{{ $etiqueta }}">Semanas de oferta destacada al mes</span>
                        <input type="number" name="destacados_incluidos" min="0" max="4" required value="{{ $valor('destacados_incluidos') ?? 0 }}" class="{{ $campo }}">
                    </label>
                    <label class="block">
                        <span class="{{ $etiqueta }}">Semanas de banner al mes</span>
                        <input type="number" name="banners_incluidos" min="0" max="4" required value="{{ $valor('banners_incluidos') ?? 0 }}" class="{{ $campo }}">
                    </label>
                    <label class="inline-flex items-center gap-2 text-sm text-atlantia-ink/80 sm:col-span-3">
                        <input type="hidden" name="carga_masiva" value="0">
                        <input type="checkbox" name="carga_masiva" value="1" @checked($valor('carga_masiva')) class="h-4 w-4 rounded border-atlantia-rose/40 text-atlantia-wine focus:ring-atlantia-blush">
                        Incluye carga masiva de productos con Excel
                    </label>
                    <label class="block sm:col-span-3">
                        <span class="{{ $etiqueta }}">Frase corta</span>
                        <input type="text" name="resumen" maxlength="200" required value="{{ $valor('resumen') }}" class="{{ $campo }}">
                    </label>
                    <label class="block sm:col-span-3">
                        <span class="{{ $etiqueta }}">Beneficios (uno por línea)</span>
                        <textarea name="beneficios" rows="5" maxlength="1500" required class="mt-1 w-full resize-y rounded-lg border border-atlantia-rose/30 px-3 py-2 text-sm outline-none focus:border-atlantia-wine focus:ring-2 focus:ring-atlantia-blush">{{ $abierto ? old('beneficios') : implode("\n", (array) ($plan['beneficios'] ?? [])) }}</textarea>
                        <span class="text-[11px] text-atlantia-ink/50">Las líneas con números (“Hasta 75 productos”, “3 promociones activas”, la comisión) se actualizan solas al guardar.</span>
                    </label>
                    @if ($bolsa->any())
                        <ul class="space-y-0.5 text-xs font-bold text-red-600 sm:col-span-3">
                            @foreach ($bolsa->all() as $error)<li>{{ $error }}</li>@endforeach
                        </ul>
                    @endif
                    <div class="flex flex-wrap items-center justify-end gap-2 sm:col-span-3">
                        <button type="submit" class="h-10 rounded-lg bg-atlantia-wine px-5 text-sm font-bold text-white hover:bg-atlantia-wine-700">Guardar plan</button>
                    </div>
                </form>
                @if ($cambiado)
                    <form method="POST" action="{{ route('admin.precios-planes.restaurar', $clave) }}" class="border-t border-atlantia-rose/10 px-5 py-3 text-right"
                          data-confirm="¿Volver el plan {{ $plan['nombre'] }} a sus valores originales ({{ ($fabrica[$clave]['precio'] ?? null) === null ? 'por cotización' : $q($fabrica[$clave]['precio']).' al mes' }})?">
                        @csrf
                        <button type="submit" class="text-xs font-bold text-atlantia-ink/60 hover:text-atlantia-wine hover:underline">Volver a los valores originales</button>
                    </form>
                @endif
            </details>
        @endforeach

        @php $pub = $errors->getBag('publicidad'); @endphp
        <article id="publicidad" class="rounded-xl border border-atlantia-rose/15 bg-white p-5">
            <h2 class="text-base font-black text-atlantia-ink">Precios de publicidad</h2>
            <p class="text-xs text-atlantia-ink/55">Lo que pagan las tiendas por anunciarse. Aplica a las compras nuevas.</p>
            <form method="POST" action="{{ route('admin.precios-planes.publicidad') }}" class="mt-3 space-y-3" data-confirm="¿Guardar los precios de publicidad?">
                @csrf
                @method('PUT')
                <div class="grid gap-3 sm:grid-cols-2">
                    <label class="block">
                        <span class="{{ $etiqueta }}">Oferta destacada (por semana)</span>
                        <input type="number" name="destacado_semana" step="0.01" min="1" required value="{{ $pub->any() ? old('destacado_semana') : $destacado }}" class="{{ $campo }}">
                    </label>
                    <label class="block">
                        <span class="{{ $etiqueta }}">Producto patrocinado en búsquedas (por semana)</span>
                        <input type="number" name="patrocinado_semana" step="0.01" min="1" required value="{{ $pub->any() ? old('patrocinado_semana') : $patrocinado }}" class="{{ $campo }}">
                    </label>
                </div>
                <div class="overflow-x-auto rounded-lg border border-atlantia-rose/10">
                    <table class="w-full min-w-[420px] text-left text-sm">
                        <thead class="bg-atlantia-blush/40 text-xs text-atlantia-ink/60"><tr><th class="px-3 py-2">Banner en la portada</th><th class="px-3 py-2">Por semana (Q)</th><th class="px-3 py-2">Por mes (Q)</th></tr></thead>
                        <tbody>
                            @foreach ($banners as $posicion => $precios)
                                <tr class="border-t border-atlantia-rose/10">
                                    <td class="px-3 py-2 font-bold text-atlantia-ink">Posición {{ $posicion }}{{ $posicion == 1 ? ' (la primera que se ve)' : '' }}</td>
                                    <td class="px-3 py-2"><input type="number" name="banners[{{ $posicion }}][semana]" step="0.01" min="1" required value="{{ $pub->any() ? old("banners.$posicion.semana") : $precios['semana'] }}" class="h-9 w-28 rounded-lg border border-atlantia-rose/30 px-2 text-sm"></td>
                                    <td class="px-3 py-2"><input type="number" name="banners[{{ $posicion }}][mes]" step="0.01" min="1" required value="{{ $pub->any() ? old("banners.$posicion.mes") : $precios['mes'] }}" class="h-9 w-28 rounded-lg border border-atlantia-rose/30 px-2 text-sm"></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @if ($pub->any())
                    <p class="text-xs font-bold text-red-600">{{ $pub->first() }}</p>
                @endif
                <div class="flex justify-end"><button type="submit" class="h-10 rounded-lg bg-atlantia-wine px-5 text-sm font-bold text-white hover:bg-atlantia-wine-700">Guardar precios de publicidad</button></div>
            </form>
        </article>
    </section>
@endsection
