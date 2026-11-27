@extends('layouts.app')

@section('content')
    @php
        $porcentaje = rtrim(rtrim(number_format((float) ($pasarela['porcentaje'] ?? 0), 2), '0'), '.');
        $fijo = number_format((float) ($pasarela['fijo'] ?? 0), 2);
        $disponible = $resumen['disponible'];
        $minimo = (float) config('monetizacion.pago_minimo_vendedor', 0);
        $pestana = in_array(request('tab'), ['cobrar', 'camino', 'solicitudes'], true) ? request('tab') : 'cobrar';
        $pestanas = [
            'cobrar' => ['Por cobrar', $ventas->count()],
            'camino' => ['En camino', $enCamino->count()],
            'solicitudes' => ['Mis solicitudes', $solicitudes->count()],
        ];
        $metodo = fn ($venta): string => match ((string) ($venta->payment?->metodo?->value ?? $venta->payment?->metodo)) {
            'tarjeta' => 'Tarjeta', 'transferencia' => 'Transferencia', 'efectivo' => 'Efectivo', default => '—',
        };
    @endphp

    <section class="mx-auto max-w-[1100px] space-y-4 pb-10">
        <div>
            <h1 class="text-2xl font-black text-atlantia-ink">Ventas y pagos</h1>
            <p class="mt-0.5 text-sm text-atlantia-ink/60">Lo que vendes entra a la cuenta de Atlantia y te lo transferimos según la frecuencia que elijas. También puedes pedirlo cuando quieras.</p>
        </div>

        {{-- Cada cuánto recibe su dinero --}}
        @php
            $tiendaPagos = auth()->user()->vendor;
            $frecuenciaActual = $tiendaPagos?->payment_frequency ?: 'semanal';
            $retenidas = app(\App\Services\Vendedores\LiquidacionesVendedorService::class)->totales(app(\App\Services\Vendedores\LiquidacionesVendedorService::class)->retenidasQuery($tiendaPagos)->get());
        @endphp
        <article class="rounded-xl border border-atlantia-rose/15 bg-white p-4" data-frecuencia-pago>
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div>
                    <h2 class="text-base font-black text-atlantia-ink">¿Cada cuánto quieres recibir tu dinero?</h2>
                    <p class="text-xs text-atlantia-ink/55">Sin ningún costo. Preparamos tu pago solo con tus ventas entregadas y te lo transferimos a tu cuenta.</p>
                </div>
            </div>
            <form method="POST" action="{{ route('vendedor.pagos.frecuencia') }}" class="mt-3 grid gap-2 sm:grid-cols-4" data-storefront-filters>
                @csrf
                @method('PUT')
                @foreach (\App\Services\Vendedores\LiquidacionesVendedorService::FRECUENCIAS as $clave => $texto)
                    <label @class(['flex cursor-pointer items-center gap-2 rounded-lg border px-3 py-2.5 text-sm', 'border-atlantia-wine bg-atlantia-blush/50 font-bold text-atlantia-wine' => $frecuenciaActual === $clave, 'border-atlantia-rose/20 text-atlantia-ink' => $frecuenciaActual !== $clave])>
                        <input type="radio" name="frecuencia" value="{{ $clave }}" @checked($frecuenciaActual === $clave) class="h-4 w-4 text-atlantia-wine">
                        {{ $texto }}
                    </label>
                @endforeach
                <noscript><button type="submit" class="h-10 rounded-lg bg-atlantia-wine px-4 text-sm font-bold text-white">Guardar</button></noscript>
            </form>
            @if ($tiendaPagos?->pagos_retenidos_at)
                <p class="mt-3 rounded-lg bg-amber-50 px-3 py-2 text-xs font-semibold text-amber-900">Tus pagos están en revisión por Atlantia{{ $tiendaPagos->motivo_retencion ? ': '.$tiendaPagos->motivo_retencion : '' }}. Escríbenos a soporte si tienes dudas.</p>
            @elseif ($retenidas['ventas'] > 0)
                <p class="mt-3 rounded-lg bg-sky-50 px-3 py-2 text-xs text-sky-900" data-retencion-nueva>
                    Como tu tienda es nueva, el dinero de cada venta entregada se libera {{ (int) config('atlantia.vendedores.dias_retencion_nueva', 7) }} días después de la entrega (solo durante tus primeras {{ intdiv((int) config('atlantia.vendedores.dias_tienda_nueva', 14), 7) }} semanas).
                    Ahora tienes Q{{ number_format($retenidas['neto'], 2) }} por liberar.
                </p>
            @endif
        </article>

        {{-- Saldos --}}
        <div class="grid gap-3 md:grid-cols-[1.3fr_1fr_1fr]">
            <article class="rounded-xl bg-[linear-gradient(140deg,#3a0004_0%,#5e0008_60%,#6e000a_100%)] p-4 text-white shadow-lg">
                <p class="text-xs font-bold uppercase tracking-wide text-white/70">Disponible para cobrar</p>
                <p class="mt-0.5 text-3xl font-black" data-saldo-disponible>Q {{ number_format($disponible['neto'], 2) }}</p>
                <p class="text-xs text-white/70">{{ $disponible['ventas'] }} {{ $disponible['ventas'] === 1 ? 'venta entregada' : 'ventas entregadas' }} · ya con comisiones restadas</p>
                @if (! $tieneCuenta)
                    <a href="{{ route('vendedor.perfil-fiscal.edit') }}" class="mt-3 block rounded-lg bg-white/15 px-3 py-2 text-center text-sm font-bold text-white ring-1 ring-white/30 hover:bg-white/25">
                        Agrega tu cuenta bancaria para poder cobrar
                    </a>
                @elseif ($disponible['neto'] > 0 && $disponible['neto'] >= $minimo)
                    <form method="POST" action="{{ route('vendedor.pagos.solicitar') }}" class="mt-3" data-protect-submit>
                        @csrf
                        <button type="submit" class="w-full rounded-lg bg-white px-3 py-2 text-sm font-black text-atlantia-wine hover:bg-atlantia-blush">
                            Solicitar mi pago de Q {{ number_format($disponible['neto'], 2) }}
                        </button>
                    </form>
                @elseif ($minimo > 0)
                    <p class="mt-3 text-xs text-white/70">Puedes solicitar tu pago desde Q {{ number_format($minimo, 2) }}.</p>
                @endif
                @error('pago') <p class="mt-2 text-xs font-bold text-amber-200">{{ $message }}</p> @enderror
            </article>
            <article class="rounded-xl border border-atlantia-rose/15 bg-white p-4">
                <p class="text-xs font-bold uppercase tracking-wide text-atlantia-ink/55">En camino</p>
                <p class="mt-0.5 text-2xl font-black text-atlantia-ink">Q {{ number_format($resumen['en_camino']['neto'], 2) }}</p>
                <p class="text-xs text-atlantia-ink/55">{{ $resumen['en_camino']['ventas'] }} {{ $resumen['en_camino']['ventas'] === 1 ? 'pedido' : 'pedidos' }} sin entregar. Se suman cuando el cliente los recibe.</p>
            </article>
            <article class="rounded-xl border border-atlantia-rose/15 bg-white p-4">
                <p class="text-xs font-bold uppercase tracking-wide text-atlantia-ink/55">Ya pagado</p>
                <p class="mt-0.5 text-2xl font-black text-emerald-700">Q {{ number_format($resumen['pagado'], 2) }}</p>
                <p class="text-xs text-atlantia-ink/55">@if ($resumen['en_proceso'] > 0)Q {{ number_format($resumen['en_proceso'], 2) }} en proceso. @endif Total recibido en tu cuenta.</p>
            </article>
        </div>

        {{-- Cómo se calcula: plegado para no ocupar espacio --}}
        <details class="group rounded-xl border border-sky-200 bg-sky-50 text-sm text-sky-950">
            <summary class="flex cursor-pointer list-none items-center justify-between gap-3 px-4 py-2.5 font-bold [&::-webkit-details-marker]:hidden">
                ¿Cómo se calcula lo que recibes?
                <svg class="h-4 w-4 transition group-open:rotate-180" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="m6 9 6 6 6-6"/></svg>
            </summary>
            <ul class="list-disc space-y-0.5 px-4 pb-3 pl-9 leading-6">
                <li>Recibes el valor de tus productos. <strong>El envío lo paga el cliente</strong> y es del repartidor; no se te descuenta.</li>
                <li><strong>Comisión de tu plan:</strong> el porcentaje de tu plan sobre cada venta (0 % en los planes con mensualidad).</li>
                <li><strong>Tarifa de la pasarela, solo si el cliente pagó con tarjeta:</strong> {{ $porcentaje }} % de esa venta + Q {{ $fijo }} <strong>por cada pago</strong>. Se calcula <strong>venta por venta</strong>, no sobre tu total. Si en un mismo pago el cliente compró en varias tiendas, los Q {{ $fijo }} se dividen entre ellas.</li>
                <li>Las ventas en efectivo o transferencia no pagan tarifa de pasarela.</li>
            </ul>
        </details>

        <article class="overflow-hidden rounded-xl border border-atlantia-rose/15 bg-white shadow-[0_12px_32px_rgba(42,16,24,0.05)]">
            <nav class="flex gap-1 overflow-x-auto border-b border-atlantia-rose/10 px-2" aria-label="Secciones">
                @foreach ($pestanas as $clave => [$texto, $cuenta])
                    <a href="{{ route('vendedor.pagos.index', ['tab' => $clave]) }}" @class([
                        'inline-flex shrink-0 items-center gap-1.5 border-b-2 px-3 py-3 text-sm font-bold transition',
                        'border-atlantia-wine text-atlantia-wine' => $pestana === $clave,
                        'border-transparent text-atlantia-ink/55 hover:text-atlantia-wine' => $pestana !== $clave,
                    ]) @if ($pestana === $clave) aria-current="page" @endif>
                        {{ $texto }} <span class="text-xs opacity-70">{{ $cuenta }}</span>
                    </a>
                @endforeach
            </nav>

            @if ($pestana === 'solicitudes')
                <div class="divide-y divide-atlantia-rose/10">
                    @forelse ($solicitudes as $solicitud)
                        <a href="{{ route('vendedor.pagos.show', $solicitud) }}" class="flex flex-wrap items-center justify-between gap-3 px-4 py-3 hover:bg-[#fdfafa]">
                            <span>
                                <span class="block font-bold text-atlantia-ink">{{ $solicitud->numero }}</span>
                                <span class="text-xs text-atlantia-ink/55">{{ $solicitud->ventas }} {{ (int) $solicitud->ventas === 1 ? 'venta' : 'ventas' }} · solicitada el {{ $solicitud->solicitada_at?->format('d/m/Y') }}{{ $solicitud->referencia_pago ? ' · Ref. '.$solicitud->referencia_pago : '' }}</span>
                            </span>
                            <span class="flex items-center gap-3">
                                <span @class([
                                    'rounded-full px-2.5 py-1 text-xs font-bold',
                                    'bg-amber-100 text-amber-800' => $solicitud->estado === 'solicitada',
                                    'bg-emerald-100 text-emerald-800' => $solicitud->estado === 'pagada',
                                    'bg-red-100 text-red-700' => $solicitud->estado === 'rechazada',
                                ])>{{ $solicitud->etiquetaEstado() }}</span>
                                <span class="font-black text-atlantia-wine">Q {{ number_format((float) $solicitud->monto_neto, 2) }}</span>
                            </span>
                        </a>
                    @empty
                        <p class="px-4 py-10 text-center text-sm text-atlantia-ink/55">Aún no has solicitado pagos. Cuando tengas saldo disponible, toca «Solicitar mi pago».</p>
                    @endforelse
                </div>
            @else
                @php
                    $lista = $pestana === 'camino' ? $enCamino : $ventas;
                    $totales = $pestana === 'camino' ? $resumen['en_camino'] : $disponible;
                @endphp
                @if ($lista->isEmpty())
                    <p class="px-4 py-10 text-center text-sm text-atlantia-ink/55">
                        {{ $pestana === 'camino' ? 'No tienes pedidos pagados esperando entrega.' : 'No tienes ventas entregadas pendientes de pago.' }}
                    </p>
                @else
                    <div class="overflow-x-auto">
                        <table class="w-full min-w-[680px] text-left text-sm">
                            <thead class="bg-[#fbf8f7] text-xs font-bold text-atlantia-ink/55">
                                <tr>
                                    <th class="px-4 py-2.5">Pedido</th>
                                    <th class="px-4 py-2.5">Pago</th>
                                    <th class="px-4 py-2.5 text-right">Venta</th>
                                    <th class="px-4 py-2.5 text-right">Comisión plan</th>
                                    <th class="px-4 py-2.5 text-right">Tarifa tarjeta</th>
                                    <th class="px-4 py-2.5 text-right">Recibes</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-atlantia-rose/10">
                                @foreach ($lista as $venta)
                                    <tr class="align-top" data-venta>
                                        <td class="px-4 py-3">
                                            @if ($venta->pedido)
                                                <a href="{{ route('vendedor.pedidos.show', $venta->pedido) }}" class="font-bold text-atlantia-ink hover:text-atlantia-wine">#{{ $venta->pedido->numero_pedido }}</a>
                                            @else
                                                <span class="font-bold text-atlantia-ink">—</span>
                                            @endif
                                            <p class="text-xs text-atlantia-ink/50">{{ $venta->pedido?->updated_at?->format('d/m/Y') }}</p>
                                        </td>
                                        <td class="px-4 py-3 text-atlantia-ink/75">{{ $metodo($venta) }}</td>
                                        <td class="px-4 py-3 text-right font-semibold">Q {{ number_format((float) $venta->monto_bruto, 2) }}</td>
                                        <td class="px-4 py-3 text-right text-atlantia-ink/70">− Q {{ number_format((float) $venta->comision_atlantia, 2) }}</td>
                                        <td class="px-4 py-3 text-right text-atlantia-ink/70">
                                            − Q {{ number_format((float) $venta->comision_pasarela, 2) }}
                                            @if ((float) $venta->comision_pasarela > 0)
                                                <span class="block text-[11px] text-atlantia-ink/45">{{ $servicio->detalleTarifa($venta) }}</span>
                                            @endif
                                        </td>
                                        <td class="px-4 py-3 text-right font-black text-atlantia-wine">Q {{ number_format((float) $venta->monto_neto_vendedor, 2) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot class="border-t-2 border-atlantia-rose/15 bg-[#fbf8f7] font-black">
                                <tr>
                                    <td class="px-4 py-3" colspan="2">Total ({{ $totales['ventas'] }} {{ $totales['ventas'] === 1 ? 'venta' : 'ventas' }})</td>
                                    <td class="px-4 py-3 text-right">Q {{ number_format($totales['bruto'], 2) }}</td>
                                    <td class="px-4 py-3 text-right">− Q {{ number_format($totales['plataforma'], 2) }}</td>
                                    <td class="px-4 py-3 text-right">− Q {{ number_format($totales['pasarela'], 2) }}</td>
                                    <td class="px-4 py-3 text-right text-atlantia-wine">Q {{ number_format($totales['neto'], 2) }}</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                @endif
            @endif
        </article>
    </section>
@endsection
