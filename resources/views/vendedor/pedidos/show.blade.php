@extends('layouts.app')

@section('content')
    @php
        $estado = $pedido->estadoValor();
        $pago = $pedido->estadoPagoValor();
        $siguientes = \App\Services\Pedidos\PedidoVendedorService::siguientesEstados($pedido);
        $principal = collect($siguientes)->except('cancelado');
        $esRecoger = $pedido->tipo_entrega === 'recoger';
        $cancelado = in_array($estado, ['cancelado', 'rechazado'], true);
        $etiquetas = [
            'pendiente' => ['Por confirmar', 'bg-amber-50 text-amber-800 ring-amber-200'],
            'en_revision' => ['En revisión', 'bg-amber-50 text-amber-800 ring-amber-200'],
            'confirmado' => ['Confirmado', 'bg-sky-50 text-sky-700 ring-sky-200'],
            'preparando' => ['En preparación', 'bg-indigo-50 text-indigo-700 ring-indigo-200'],
            'listo_para_entrega' => ['Listo', 'bg-emerald-50 text-emerald-700 ring-emerald-200'],
            'en_ruta' => ['En camino', 'bg-teal-50 text-teal-700 ring-teal-200'],
            'entregado' => ['Entregado', 'bg-slate-100 text-slate-700 ring-slate-200'],
            'cancelado' => ['Cancelado', 'bg-rose-50 text-rose-700 ring-rose-200'],
            'rechazado' => ['Rechazado', 'bg-rose-50 text-rose-700 ring-rose-200'],
        ];
        $pagos = [
            'pagado' => ['Pagado', 'text-emerald-700'], 'aprobado' => ['Pagado', 'text-emerald-700'],
            'pendiente' => ['Por cobrar', 'text-amber-700'], 'validando' => ['Validando el pago', 'text-amber-700'],
            'rechazado' => ['Pago rechazado', 'text-rose-700'], 'anulado' => ['Pago anulado', 'text-rose-700'],
            'reembolsado' => ['Reembolsado', 'text-slate-600'],
        ];
        $metodos = ['tarjeta' => 'Tarjeta', 'transferencia' => 'Transferencia', 'efectivo' => 'Efectivo'];
        $entregas = ['domicilio' => 'A domicilio', 'recoger' => 'Recoge en tienda', 'programado' => 'Entrega programada'];

        /* Línea de tiempo: pasos del pedido y cuándo ocurrió cada uno. */
        $momentos = $pedido->estados->sortBy('created_at')->groupBy('estado')->map->first();
        $pasos = [
            ['pendiente', 'Recibido', $pedido->created_at],
            ['confirmado', 'Confirmado', $momentos['confirmado']->created_at ?? $pedido->confirmado_at],
            ['preparando', 'En preparación', $momentos['preparando']->created_at ?? null],
            ['listo_para_entrega', $esRecoger ? 'Listo para recoger' : 'Listo', $momentos['listo_para_entrega']->created_at ?? null],
        ];
        if (! $esRecoger) {
            $pasos[] = ['en_ruta', 'En camino', $momentos['en_ruta']->created_at ?? null];
        }
        $pasos[] = ['entregado', 'Entregado', $momentos['entregado']->created_at ?? null];
        $orden = array_column($pasos, 0);
        $posicion = array_search($estado === 'en_revision' ? 'pendiente' : $estado, $orden, true);

        $unidades = (int) $pedido->items->sum('cantidad');
        $promos = (float) ($pedido->descuento_promociones ?? 0);
        $tuVenta = max(0, (float) $pedido->subtotal - $promos);
        $direccion = $pedido->direccion;
        $telefono = $direccion?->telefono_contacto ?: $pedido->cliente?->phone;
        $payment = $pedido->payments->sortByDesc('id')->first();
    @endphp

    <section class="mx-auto max-w-[1180px] space-y-4 pb-10">
        <a href="{{ route('vendedor.pedidos.index') }}" class="inline-flex items-center gap-1.5 text-sm font-bold text-atlantia-wine hover:underline">
            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="m15 18-6-6 6-6"/></svg>
            Pedidos
        </a>

        {{-- Encabezado con el siguiente paso --}}
        <div class="flex flex-col gap-4 rounded-xl border border-atlantia-rose/15 bg-white p-4 shadow-[0_12px_32px_rgba(42,16,24,0.05)] sm:p-5 lg:flex-row lg:items-center lg:justify-between">
            <div class="min-w-0">
                <div class="flex flex-wrap items-center gap-2">
                    <h1 class="text-2xl font-black text-atlantia-ink">#{{ $pedido->numero_pedido }}</h1>
                    <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-bold ring-1 {{ $etiquetas[$estado][1] ?? 'bg-slate-100 text-slate-700 ring-slate-200' }}" data-estado-pedido>{{ $etiquetas[$estado][0] ?? ucfirst($estado) }}</span>
                    <span class="inline-flex rounded-full bg-[#f5efee] px-2.5 py-1 text-xs font-bold text-atlantia-ink/75">{{ $entregas[$pedido->tipo_entrega] ?? 'A domicilio' }}</span>
                </div>
                <p class="mt-1 text-sm text-atlantia-ink/60">
                    {{ $pedido->created_at?->isToday() ? 'Hoy' : $pedido->created_at?->format('d/m/Y') }} a las {{ $pedido->created_at?->format('h:i a') }}
                    · {{ $unidades }} {{ $unidades === 1 ? 'unidad' : 'unidades' }} · Q{{ number_format((float) $pedido->total, 2) }}
                </p>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                @if ($principal->isNotEmpty())
                    <form method="POST" action="{{ route('vendedor.pedidos.estado', $pedido) }}">
                        @csrf
                        @method('PATCH')
                        <input type="hidden" name="estado" value="{{ $principal->keys()->first() }}">
                        <button type="submit" class="h-10 rounded-lg bg-atlantia-wine px-5 text-sm font-bold text-white hover:bg-atlantia-wine-700" data-siguiente-paso>{{ $principal->first() }}</button>
                    </form>
                @elseif ($estado === 'listo_para_entrega')
                    <span class="rounded-lg bg-emerald-50 px-3 py-2 text-sm font-bold text-emerald-700">Esperando al repartidor</span>
                @endif
                <a href="{{ route('vendedor.pedidos.comanda', $pedido) }}" target="_blank" rel="noopener" class="inline-flex h-10 items-center gap-2 rounded-lg border border-atlantia-rose/25 px-4 text-sm font-bold text-atlantia-wine hover:bg-atlantia-blush">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M6 9V2h12v7M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><path d="M6 14h12v8H6z"/></svg>
                    Imprimir comanda
                </a>
                @if (isset($siguientes['cancelado']))
                    <form method="POST" action="{{ route('vendedor.pedidos.estado', $pedido) }}" data-confirm="¿Cancelar el pedido #{{ $pedido->numero_pedido }}? El cliente recibirá un aviso.">
                        @csrf
                        @method('PATCH')
                        <input type="hidden" name="estado" value="cancelado">
                        <button type="submit" class="h-10 rounded-lg px-3 text-sm font-bold text-rose-700 hover:bg-rose-50">Cancelar pedido</button>
                    </form>
                @endif
            </div>
        </div>

        @error('estado')
            <p class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-semibold text-rose-700">{{ $message }}</p>
        @enderror

        {{-- Línea de tiempo --}}
        @unless ($cancelado)
            <ol class="grid grid-cols-2 gap-2 rounded-xl border border-atlantia-rose/15 bg-white p-3 sm:flex sm:items-start sm:gap-0 sm:p-4" aria-label="Avance del pedido" data-avance>
                @foreach ($pasos as $indice => [$clave, $texto, $momento])
                    @php $hecho = $posicion !== false && $indice <= $posicion; $actual = $indice === $posicion; @endphp
                    <li class="flex items-center gap-2 sm:flex-1 sm:flex-col sm:gap-1.5 sm:text-center">
                        <div class="flex items-center sm:w-full">
                            <span @class(['hidden h-0.5 flex-1 sm:block', 'bg-atlantia-wine' => $hecho, 'bg-atlantia-rose/20' => ! $hecho, 'invisible' => $indice === 0])></span>
                            <span @class([
                                'grid h-7 w-7 shrink-0 place-items-center rounded-full text-xs font-black',
                                'bg-atlantia-wine text-white' => $hecho,
                                'ring-4 ring-atlantia-blush' => $actual,
                                'border-2 border-atlantia-rose/25 bg-white text-atlantia-ink/40' => ! $hecho,
                            ])>
                                @if ($hecho && ! $actual)
                                    <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m5 12 5 5 9-10"/></svg>
                                @else
                                    {{ $indice + 1 }}
                                @endif
                            </span>
                            <span @class(['hidden h-0.5 flex-1 sm:block', 'bg-atlantia-wine' => $posicion !== false && $indice < $posicion, 'bg-atlantia-rose/20' => ! ($posicion !== false && $indice < $posicion), 'invisible' => $loop->last])></span>
                        </div>
                        <div class="min-w-0 leading-tight">
                            <p @class(['text-xs font-bold', 'text-atlantia-ink' => $hecho, 'text-atlantia-ink/45' => ! $hecho])>{{ $texto }}</p>
                            <p class="text-[11px] text-atlantia-ink/45">{{ $hecho && $momento ? $momento->format('h:i a') : '' }}</p>
                        </div>
                    </li>
                @endforeach
            </ol>
        @else
            <p class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-semibold text-rose-700">
                Este pedido fue {{ $estado === 'rechazado' ? 'rechazado' : 'cancelado' }}{{ $pedido->cancelado_at ? ' el '.$pedido->cancelado_at->format('d/m/Y \a \l\a\s h:i a') : '' }}. No hay nada más que hacer.
            </p>
        @endunless

        <div class="grid gap-4 lg:grid-cols-[minmax(0,1fr)_20rem]">
            {{-- Productos --}}
            <article class="overflow-hidden rounded-xl border border-atlantia-rose/15 bg-white">
                <h2 class="border-b border-atlantia-rose/10 px-4 py-3 text-sm font-black text-atlantia-ink">Productos para preparar</h2>
                <ul class="divide-y divide-atlantia-rose/10">
                    @foreach ($pedido->items as $item)
                        <li class="flex items-center gap-3 px-4 py-3" data-item-pedido>
                            <span class="grid h-9 min-w-9 place-items-center rounded-lg bg-atlantia-blush px-2 text-sm font-black text-atlantia-wine">{{ $item->cantidad }}×</span>
                            <div class="min-w-0 flex-1">
                                <p class="truncate font-bold text-atlantia-ink">{{ $item->producto_nombre_snapshot }}</p>
                                <p class="text-xs text-atlantia-ink/50">
                                    Q{{ number_format((float) $item->precio_unitario_snapshot, 2) }} c/u
                                    @if ($item->producto_sku_snapshot) · SKU {{ $item->producto_sku_snapshot }} @endif
                                </p>
                            </div>
                            <p class="shrink-0 font-bold text-atlantia-ink">Q{{ number_format((float) $item->precio_unitario_snapshot * (int) $item->cantidad, 2) }}</p>
                        </li>
                    @endforeach
                </ul>

                @if ($pedido->notas)
                    <div class="mx-4 mb-3 rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-sm text-amber-900">
                        <p class="text-xs font-black">Nota del cliente</p>
                        <p class="mt-0.5 whitespace-pre-line">{{ $pedido->notas }}</p>
                    </div>
                @endif

                <dl class="space-y-1.5 border-t border-atlantia-rose/10 bg-[#fbf8f7] px-4 py-3 text-sm">
                    <div class="flex justify-between"><dt class="text-atlantia-ink/60">Productos</dt><dd class="font-semibold text-atlantia-ink">Q{{ number_format((float) $pedido->subtotal, 2) }}</dd></div>
                    @if ($promos > 0)
                        <div class="flex justify-between"><dt class="text-emerald-700">Tus promociones</dt><dd class="font-semibold text-emerald-700">- Q{{ number_format($promos, 2) }}</dd></div>
                    @endif
                    <div class="flex justify-between border-t border-atlantia-rose/15 pt-1.5"><dt class="font-black text-atlantia-ink">Tu venta</dt><dd class="font-black text-atlantia-ink" data-tu-venta>Q{{ number_format($tuVenta, 2) }}</dd></div>
                    @if ((float) $pedido->envio > 0)
                        <div class="flex justify-between text-xs"><dt class="text-atlantia-ink/50">Envío (lo cobra Atlantia, no es parte de tu venta)</dt><dd class="text-atlantia-ink/50">Q{{ number_format((float) $pedido->envio, 2) }}</dd></div>
                    @endif
                </dl>
            </article>

            <div class="space-y-4">
                {{-- Cliente y entrega --}}
                <article class="rounded-xl border border-atlantia-rose/15 bg-white p-4">
                    <h2 class="text-sm font-black text-atlantia-ink">Cliente</h2>
                    <p class="mt-2 font-bold text-atlantia-ink">{{ $direccion?->nombre_contacto ?: ($pedido->cliente?->name ?? 'Cliente') }}</p>
                    @if ($telefono)
                        <a href="tel:{{ preg_replace('/[^\d+]/', '', $telefono) }}" class="mt-1 inline-flex items-center gap-1.5 text-sm font-bold text-atlantia-wine hover:underline">
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round" aria-hidden="true"><path d="M5 4h4l2 5-2.5 1.5a11 11 0 0 0 5 5L15 13l5 2v4a1 1 0 0 1-1 1A16 16 0 0 1 4 5a1 1 0 0 1 1-1Z"/></svg>
                            {{ $telefono }}
                        </a>
                    @endif

                    <h3 class="mt-4 text-xs font-black text-atlantia-ink/60">{{ $esRecoger ? 'Recoge en tu tienda' : 'Dirección de entrega' }}</h3>
                    @if ($esRecoger)
                        <p class="mt-1 text-sm text-atlantia-ink/70">El cliente pasará por el pedido. Márcalo como entregado cuando se lo lleve.</p>
                    @elseif ($direccion)
                        <p class="mt-1 text-sm leading-6 text-atlantia-ink/75">
                            {{ $direccion->direccion_linea_1 }}@if ($direccion->zona_o_barrio), {{ $direccion->zona_o_barrio }}@endif<br>
                            {{ $direccion->municipio }}
                        </p>
                        @if ($direccion->referencia)
                            <p class="mt-1 text-xs text-atlantia-ink/55">Referencia: {{ $direccion->referencia }}</p>
                        @endif
                        <p class="mt-2 text-xs text-atlantia-ink/50">Un repartidor de Atlantia recoge el pedido en tu tienda y lo lleva.</p>
                    @endif
                </article>

                {{-- Pago --}}
                <article class="rounded-xl border border-atlantia-rose/15 bg-white p-4">
                    <h2 class="text-sm font-black text-atlantia-ink">Pago</h2>
                    <div class="mt-2 flex items-center justify-between gap-3 text-sm">
                        <span class="text-atlantia-ink/75">{{ $metodos[$pedido->metodoPagoValor()] ?? ucfirst($pedido->metodoPagoValor()) }}@if ($payment?->referencia_bancaria) · ****{{ substr($payment->referencia_bancaria, -4) }}@endif</span>
                        <span class="font-bold {{ $pagos[$pago][1] ?? 'text-atlantia-ink/60' }}">{{ $pagos[$pago][0] ?? ucfirst($pago) }}</span>
                    </div>
                    @if ($pedido->metodoPagoValor() === 'efectivo' && ! $esRecoger)
                        <p class="mt-2 text-xs text-atlantia-ink/55">El repartidor cobra en efectivo al entregar.</p>
                    @elseif ($pedido->metodoPagoValor() === 'efectivo')
                        <p class="mt-2 text-xs text-atlantia-ink/55">Cobra Q{{ number_format((float) $pedido->total, 2) }} cuando el cliente recoja.</p>
                    @endif
                </article>

                @include('vendedor.pedidos.partials.factura')

                {{-- Historial --}}
                @if ($pedido->estados->isNotEmpty())
                    <article class="rounded-xl border border-atlantia-rose/15 bg-white p-4">
                        <h2 class="text-sm font-black text-atlantia-ink">Historial</h2>
                        <ol class="mt-2 space-y-2 text-sm">
                            @foreach ($pedido->estados->sortByDesc('created_at') as $cambio)
                                @php $valorCambio = $cambio->estado instanceof \BackedEnum ? $cambio->estado->value : (string) $cambio->estado; @endphp
                                <li class="flex gap-2">
                                    <span class="mt-1.5 h-2 w-2 shrink-0 rounded-full bg-atlantia-rose/50"></span>
                                    <div class="min-w-0">
                                        <p class="font-semibold text-atlantia-ink">{{ $etiquetas[$valorCambio][0] ?? ucfirst($valorCambio) }}</p>
                                        <p class="text-xs text-atlantia-ink/50">{{ $cambio->created_at?->format('d/m/Y h:i a') }}@if ($cambio->usuario) · {{ \Illuminate\Support\Str::before(trim($cambio->usuario->name), ' ') }}@endif</p>
                                        @if ($cambio->notas)
                                            <p class="text-xs text-atlantia-ink/60">{{ $cambio->notas }}</p>
                                        @endif
                                    </div>
                                </li>
                            @endforeach
                        </ol>
                    </article>
                @endif
            </div>
        </div>
    </section>
@endsection
