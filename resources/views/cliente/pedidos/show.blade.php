@extends('layouts.marketplace')

@section('content')
    @php
        $clienteNombre = explode(' ', trim(auth()->user()?->name ?? $pedido->direccion?->nombre_contacto ?? 'cliente'))[0] ?: 'cliente';
        $contactEmail = $guestContactEmail ?? auth()->user()?->email ?? $pedido->cliente?->email;
        $payment = $pedido->payments->sortByDesc('id')->first();
        $esperaPagoEnLinea = \App\Services\Pedidos\CheckoutService::esperaPagoEnLinea($payment);
        $pagoCancelado = in_array($pedido->estadoPagoValor(), ['rechazado', 'anulado'], true);
        $fechaPedido = ($pedido->confirmado_at ?? $pedido->created_at)->format('d/m/Y h:i a');
        $estado = $pedido->estadoValor();
        $estadoPago = $pedido->estadoPagoValor();
        $items = $pedido->pedidosHijos->flatMap->items;
        $itemsCount = (int) $items->sum('cantidad');
        $seguimientoUrl = request()->routeIs('cliente.pedidos.guest-show')
            ? route('cliente.pedidos.guest-seguimiento', $pedido)
            : route('cliente.pedidos.seguimiento', $pedido);
        $seguimiento = [
            ['label' => in_array($estado, ['pendiente', 'en_revision'], true) ? 'Recibido' : 'Confirmado', 'time' => ($pedido->created_at)->format('h:i a'), 'state' => 'done'],
            ['label' => 'En preparacion', 'time' => in_array($estado, ['preparando', 'en_ruta', 'entregado'], true) ? 'En curso' : 'Pendiente', 'state' => in_array($estado, ['preparando', 'en_ruta', 'entregado'], true) ? 'active' : 'pending'],
            ['label' => 'En camino', 'time' => in_array($estado, ['en_ruta', 'entregado'], true) ? 'En ruta' : 'Pendiente', 'state' => in_array($estado, ['en_ruta', 'entregado'], true) ? 'active' : 'pending'],
            ['label' => 'Entregado', 'time' => $estado === 'entregado' ? 'Completado' : 'Pendiente', 'state' => $estado === 'entregado' ? 'done' : 'pending'],
        ];
        $dtes = $pedido->pedidosHijos->flatMap->dteFacturas;
        /* Encabezado según el estado real del pedido. */
        [$icono, $colorIcono, $etiquetaEstado, $colorEtiqueta, $titulo] = match ($estado) {
            'pendiente', 'en_revision' => ['✓', 'bg-amber-500', 'Pedido recibido', 'text-amber-700', "Gracias, {$clienteNombre}. Recibimos tu pedido y la tienda lo confirmará en unos minutos."],
            'confirmado' => ['✓', 'bg-emerald-600', 'Pedido confirmado', 'text-emerald-700', "Gracias, {$clienteNombre}. La tienda confirmó tu pedido."],
            'preparando' => ['✓', 'bg-emerald-600', 'En preparación', 'text-emerald-700', "{$clienteNombre}, la tienda está preparando tu pedido."],
            'listo_para_entrega' => ['✓', 'bg-emerald-600', 'Listo', 'text-emerald-700', $pedido->tipo_entrega === 'recoger' ? "{$clienteNombre}, tu pedido está listo para recoger." : "{$clienteNombre}, tu pedido está listo y pronto sale con el repartidor."],
            'en_ruta' => ['➜', 'bg-emerald-600', 'En camino', 'text-emerald-700', "{$clienteNombre}, tu pedido va en camino."],
            'entregado' => ['✓', 'bg-emerald-600', 'Entregado', 'text-emerald-700', "¡Listo, {$clienteNombre}! Tu pedido fue entregado."],
            'cancelado', 'rechazado' => ['✕', 'bg-rose-600', 'Pedido cancelado', 'text-rose-700', 'Este pedido fue cancelado.'],
            default => ['✓', 'bg-emerald-600', 'Pedido confirmado', 'text-emerald-700', "Gracias, {$clienteNombre}."],
        };
        $tiendasCanceladas = $estado !== 'cancelado'
            ? $pedido->pedidosHijos->filter(fn ($h) => $h->estadoValor() === 'cancelado')->map(fn ($h) => $h->vendor?->business_name)->filter()->values()
            : collect();
        $pagoReembolsado = (float) (($payment?->pasarela_payload ?? [])['refund_total'] ?? 0);
    @endphp

    <section class="mx-auto w-full max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
        <div class="grid gap-6 lg:grid-cols-[1fr_380px]">
            <div class="space-y-6">
                @if ($esperaPagoEnLinea)
                    <section class="rounded-lg border border-amber-300 bg-amber-50 p-6 shadow-sm sm:p-8">
                        <p class="text-sm font-black uppercase tracking-normal text-amber-800">Pago pendiente</p>
                        <h2 class="mt-2 text-2xl font-black text-atlantia-ink">Completa el pago con tarjeta para confirmar tu pedido</h2>
                        <p class="mt-3 text-sm leading-6 text-atlantia-ink/70">
                            Si ya pagaste, la confirmacion puede tardar unos minutos. Si no completas el pago antes de que venza el enlace, el pedido se cancela automaticamente.
                        </p>
                        <a
                            href="{{ route('cliente.pagos.recurrente.pagar', $payment->uuid) }}"
                            class="mt-5 inline-flex rounded-md bg-atlantia-wine px-5 py-3 text-sm font-bold text-white hover:bg-atlantia-wine/90"
                        >
                            Pagar ahora con Recurrente
                        </a>
                    </section>
                @endif

                @if (! in_array($estado, ['entregado', 'cancelado', 'rechazado'], true))
                    <x-activar-avisos data-pedido="{{ $pedido->uuid }}" />
                @endif

                <section class="rounded-lg border border-atlantia-rose/20 bg-white p-6 shadow-sm sm:p-8">
                    @if ($esperaPagoEnLinea || $pagoCancelado)
                    <div class="flex h-20 w-20 items-center justify-center rounded-full {{ $pagoCancelado ? 'bg-rose-600' : 'bg-amber-500' }} text-3xl font-black text-white">
                        {{ $pagoCancelado ? 'X' : '...' }}
                    </div>
                    <p class="mt-5 text-sm font-black uppercase tracking-normal {{ $pagoCancelado ? 'text-rose-700' : 'text-amber-700' }}">
                        {{ $pagoCancelado ? 'Pedido cancelado' : 'Esperando pago' }}
                    </p>
                    <h1 class="mt-2 max-w-3xl text-3xl font-black leading-tight text-atlantia-ink sm:text-4xl">
                        {{ $pagoCancelado ? 'El pago no se completo.' : 'Tu pedido se confirmara al recibir el pago.' }}
                    </h1>
                    @else
                    <div class="flex h-20 w-20 items-center justify-center rounded-full {{ $colorIcono }} text-3xl font-black text-white" aria-hidden="true">
                        {{ $icono }}
                    </div>

                    <p class="mt-5 text-sm font-black uppercase tracking-normal {{ $colorEtiqueta }}" data-estado-pedido="{{ $estado }}">
                        {{ $etiquetaEstado }}
                    </p>
                    <h1 class="mt-2 max-w-3xl text-3xl font-black leading-tight text-atlantia-ink sm:text-4xl">
                        {{ $titulo }}
                    </h1>
                    @if ($tiendasCanceladas->isNotEmpty())
                        <p class="mt-3 rounded-lg bg-amber-50 px-4 py-2 text-sm font-semibold text-amber-900" data-parte-cancelada>
                            {{ $tiendasCanceladas->join(', ', ' y ') }} no {{ $tiendasCanceladas->count() === 1 ? 'pudo' : 'pudieron' }} entregar su parte; lo demás sigue en camino.
                        </p>
                    @endif
                    @if ($pagoReembolsado > 0)
                        <p class="mt-3 rounded-lg bg-emerald-50 px-4 py-2 text-sm font-semibold text-emerald-900" data-reembolso>
                            Te devolvemos Q{{ number_format($pagoReembolsado, 2) }} a tu tarjeta en un plazo {{ \App\Services\Pedidos\CancelacionService::PLAZO_DEVOLUCION }}.
                        </p>
                    @endif
                    @endif
                    <p class="mt-4 max-w-2xl text-base leading-7 text-atlantia-ink/70">
                        Te enviamos un correo a
                        <span class="font-bold text-atlantia-ink">{{ $contactEmail }}</span>
                        con los detalles de tu compra. Aquí puedes descargar tu comprobante de compra y, cuando la tienda la emita, tu factura.
                    </p>

                    <div class="mt-6 grid gap-4 rounded-lg border border-slate-200 p-5 sm:grid-cols-3">
                        <div>
                            <p class="text-xs font-black uppercase tracking-normal text-atlantia-ink/50">No. de pedido</p>
                            <p class="mt-2 break-all font-black text-atlantia-wine">{{ $pedido->numero_pedido }}</p>
                        </div>
                        <div>
                            <p class="text-xs font-black uppercase tracking-normal text-atlantia-ink/50">Fecha</p>
                            <p class="mt-2 font-bold text-atlantia-ink">{{ $fechaPedido }}</p>
                        </div>
                        <div>
                            <p class="text-xs font-black uppercase tracking-normal text-atlantia-ink/50">Metodo de pago</p>
                            <p class="mt-2 font-bold text-atlantia-ink">
                                {{ ucfirst(str_replace('_', ' ', $pedido->metodoPagoValor())) }}
                            </p>
                        </div>
                    </div>
                </section>

                <section class="rounded-lg border border-atlantia-rose/20 bg-white p-6 shadow-sm sm:p-8">
                    <h2 class="text-2xl font-black text-atlantia-ink">Seguimiento del pedido</h2>
                    <p class="mt-2 text-sm text-atlantia-ink/70">Estado actualizado en tiempo real.</p>

                    <div class="mt-6 grid gap-4 sm:grid-cols-4">
                        @foreach ($seguimiento as $paso)
                            <div class="text-center">
                                <span
                                    @class([
                                        'mx-auto flex h-12 w-12 items-center justify-center rounded-full border-2 text-sm font-black',
                                        'border-emerald-600 bg-emerald-600 text-white' => $paso['state'] === 'done',
                                        'border-atlantia-wine bg-atlantia-blush text-atlantia-wine' => $paso['state'] === 'active',
                                        'border-slate-200 bg-white text-slate-400' => $paso['state'] === 'pending',
                                    ])
                                >
                                    {{ $loop->iteration }}
                                </span>
                                <p class="mt-3 font-bold text-atlantia-ink">{{ $paso['label'] }}</p>
                                <p class="mt-1 text-sm text-atlantia-ink/55">{{ $paso['time'] }}</p>
                            </div>
                        @endforeach
                    </div>

                    @unless (in_array($estado, ['cancelado', 'rechazado', 'entregado'], true))
                    <div class="mt-8 rounded-lg border border-sky-200 bg-sky-50 p-5 sm:flex sm:items-center sm:justify-between">
                        <div>
                            <p class="text-xs font-black uppercase tracking-normal text-sky-700">Entrega estimada</p>
                            <p class="mt-2 text-2xl font-black text-atlantia-ink">Hoy entre 45 y 60 min</p>
                        </div>
                        <a
                            href="{{ $seguimientoUrl }}"
                            class="mt-4 inline-flex rounded-md bg-atlantia-wine px-5 py-3 text-sm font-bold text-white
                                hover:bg-atlantia-wine-700 sm:mt-0"
                        >
                            Ver en mapa
                        </a>
                    </div>
                    @endunless

                    <div class="mt-8 rounded-lg border border-atlantia-rose/20 bg-white p-5">
                        <h3 class="text-lg font-black text-atlantia-ink">Timeline operativo</h3>
                        <div class="mt-4 space-y-4">
                            @forelse ($pedido->historialEstados->sortBy('created_at') as $movimiento)
                                <div class="flex gap-4">
                                    <div class="mt-1 h-3 w-3 shrink-0 rounded-full bg-atlantia-wine"></div>
                                    <div>
                                        <p class="font-bold text-atlantia-ink">
                                            {{ ucfirst(str_replace('_', ' ', $movimiento->estado_nuevo)) }}
                                        </p>
                                        <p class="text-sm text-atlantia-ink/60">
                                            {{ $movimiento->created_at?->format('d/m/Y h:i a') }}
                                            · {{ $movimiento->usuario?->name ?? 'Sistema Atlantia' }}
                                        </p>
                                        @if ($movimiento->nota)
                                            <p class="mt-1 text-sm text-atlantia-ink/75">{{ $movimiento->nota }}</p>
                                        @endif
                                    </div>
                                </div>
                            @empty
                                <p class="text-sm text-atlantia-ink/60">Aun no hay movimientos historicos registrados.</p>
                            @endforelse
                        </div>
                    </div>
                </section>

                <section class="rounded-lg border border-atlantia-rose/20 bg-white p-6 shadow-sm sm:p-8">
                    <h2 class="text-2xl font-black text-atlantia-ink">Detalles de entrega y pago</h2>

                    <div class="mt-6 grid gap-6 sm:grid-cols-2">
                        <div>
                            <p class="text-xs font-black uppercase tracking-normal text-atlantia-ink/50">
                                Direccion de entrega
                            </p>
                            <p class="mt-3 font-bold text-atlantia-ink">
                                {{ $pedido->direccion?->nombre_contacto ?? auth()->user()?->name }}
                            </p>
                            <p class="mt-1 text-sm leading-6 text-atlantia-ink/75">
                                {{ $pedido->direccion?->direccion_linea_1 }}
                                @if ($pedido->direccion?->zona_o_barrio)
                                    <br>{{ $pedido->direccion->zona_o_barrio }}
                                @endif
                                <br>{{ $pedido->direccion?->municipio }}
                                @if ($pedido->direccion?->telefono_contacto)
                                    - {{ $pedido->direccion->telefono_contacto }}
                                @endif
                            </p>
                        </div>

                        <div>
                            <p class="text-xs font-black uppercase tracking-normal text-atlantia-ink/50">
                                Instrucciones para el repartidor
                            </p>
                            <p class="mt-3 text-sm leading-6 text-atlantia-ink/75">
                                {{ $pedido->notas ?: 'Sin instrucciones adicionales.' }}
                            </p>

                            <p class="mt-5 text-xs font-black uppercase tracking-normal text-atlantia-ink/50">
                                Metodo de pago
                            </p>
                            <p class="mt-3 font-bold text-atlantia-ink">
                                {{ ucfirst($pedido->metodoPagoValor()) }}
                                <span class="font-normal text-atlantia-ink/60">
                                    - {{ ucfirst($estadoPago) }}
                                </span>
                            </p>
                        </div>
                    </div>
                </section>

                <section class="rounded-lg border border-atlantia-rose/20 bg-white p-6 shadow-sm sm:p-8">
                    <h2 class="text-2xl font-black text-atlantia-ink">Facturacion electronica</h2>
                    <p class="mt-2 text-sm leading-6 text-atlantia-ink/70">
                        Cada vendedor emite su propio DTE certificado por SAT. Te avisaremos por correo cuando todos
                        esten listos.
                    </p>

                    <div class="mt-6 space-y-4">
                        @foreach ($pedido->pedidosHijos as $pedidoHijo)
                            @php
                                $dte = $pedidoHijo->dteFacturas->firstWhere('estado', 'certificado') ?? $pedidoHijo->dteFacturas->first();
                                $certificado = $dte?->estado === 'certificado';
                                $facturaManual = $pedidoHijo->vendor?->fiscalProfile?->modo_facturacion === 'manual_sat';
                            @endphp

                            <article class="rounded-lg border border-slate-200 p-5">
                                <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                                    <div class="flex gap-3">
                                        <span
                                            class="flex h-11 w-11 shrink-0 items-center justify-center rounded-md bg-atlantia-wine
                                                text-sm font-black text-white"
                                        >
                                            {{ \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($pedidoHijo->vendor?->business_name ?? 'AS', 0, 2)) }}
                                        </span>
                                        <div>
                                            <h3 class="font-black text-atlantia-ink">
                                                {{ $pedidoHijo->vendor?->business_name ?? 'Atlantia Supermarket' }}
                                            </h3>
                                            <p class="mt-1 text-sm text-atlantia-ink/60">
                                                {{ $certificado ? 'Factura disponible' : ($pedidoHijo->estadoValor() === 'cancelado' ? 'Pedido cancelado' : 'Comprobante de compra disponible') }}
                                            </p>
                                        </div>
                                    </div>

                                    <div class="text-left sm:text-right">
                                        <span
                                            @class([
                                                'inline-flex rounded-md px-3 py-2 text-xs font-black uppercase',
                                                'bg-emerald-100 text-emerald-800' => $certificado,
                                                'bg-amber-100 text-amber-800' => ! $certificado,
                                            ])
                                        >
                                            {{ $certificado ? 'Certificado' : 'Pendiente' }}
                                        </span>
                                        <p class="mt-2 font-black text-atlantia-wine">
                                            Q {{ number_format((float) $pedidoHijo->total, 2) }}
                                        </p>
                                    </div>
                                </div>

                                <ul class="mt-4 divide-y divide-slate-200 border-t border-slate-200 text-sm">
                                    @foreach ($pedidoHijo->items as $item)
                                        <li class="flex justify-between gap-4 py-3">
                                            <span>{{ $item->cantidad }} x {{ $item->producto_nombre_snapshot }}</span>
                                            <span class="font-bold text-atlantia-ink">Q {{ number_format((float) $item->subtotal, 2) }}</span>
                                        </li>
                                    @endforeach
                                </ul>

                                <div class="mt-4 flex flex-col gap-3 border-t border-slate-200 pt-4 sm:flex-row sm:items-center sm:justify-between" data-documentos-tienda>
                                    <div class="text-sm">
                                        @if ($certificado)
                                            <span class="block text-atlantia-ink/55">Factura {{ $dte->serie ? $dte->serie.'-' : '' }}{{ $dte->numero ?? $dte->numero_dte }}</span>
                                            @if ($dte->uuid_sat)
                                                <span class="block break-all font-mono text-xs font-bold text-atlantia-wine">Autorización SAT: {{ $dte->uuid_sat }}</span>
                                            @endif
                                        @elseif ($pedidoHijo->estadoValor() !== 'cancelado')
                                            <span class="block text-atlantia-ink/60">¿Necesitas factura? Pídela a la tienda con tu NIT; cuando la emita aparecerá aquí.</span>
                                        @endif
                                    </div>
                                    <div class="flex shrink-0 flex-wrap gap-2">
                                        @if ($pedidoHijo->vendor?->slug)
                                            <a href="{{ route('comercios.show', $pedidoHijo->vendor->slug) }}?denunciar=1&pedido={{ $pedidoHijo->uuid }}#denunciar" class="inline-flex items-center px-2 py-2.5 text-xs font-bold text-atlantia-ink/50 hover:text-rose-700 hover:underline" data-denunciar-desde-pedido>Denunciar tienda</a>
                                        @endif
                                        <a href="{{ route('pedidos.comprobante', $pedidoHijo) }}" target="_blank" rel="noopener"
                                           class="inline-flex items-center gap-2 rounded-md border border-atlantia-rose/40 px-4 py-2.5 text-sm font-bold text-atlantia-wine hover:bg-atlantia-blush" data-comprobante>
                                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8Z"/><path d="M14 2v6h6M8 13h8M8 17h5"/></svg>
                                            Comprobante de compra
                                        </a>
                                        @if ($certificado && $dte->pdf_path)
                                            <a href="{{ route('dte.pdf', $dte) }}" target="_blank" rel="noopener"
                                               class="inline-flex items-center gap-2 rounded-md bg-atlantia-wine px-4 py-2.5 text-sm font-bold text-white hover:bg-atlantia-wine-700" data-ver-factura>
                                                Ver factura
                                            </a>
                                        @endif
                                    </div>
                                </div>
                            </article>
                        @endforeach
                    </div>
                </section>
            </div>

            <aside class="lg:sticky lg:top-6 lg:h-fit">
                <section class="rounded-lg border border-atlantia-rose/20 bg-white p-6 shadow-sm">
                    <h2 class="text-2xl font-black text-atlantia-ink">Resumen de la compra</h2>

                    <dl class="mt-6 space-y-4 text-sm">
                        <div class="flex justify-between">
                            <dt class="text-atlantia-ink/65">Subtotal ({{ $itemsCount }} articulos)</dt>
                            <dd class="font-bold text-atlantia-ink">Q {{ number_format((float) $pedido->subtotal, 2) }}</dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-atlantia-ink/65">Envio</dt>
                            <dd class="font-bold text-atlantia-ink">Q {{ number_format((float) $pedido->envio, 2) }}</dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-atlantia-ink/65">IVA incluido</dt>
                            <dd class="font-bold text-atlantia-ink">Q {{ number_format((float) $pedido->impuestos, 2) }}</dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-emerald-700">Descuento</dt>
                            <dd class="font-bold text-emerald-700">- Q {{ number_format((float) $pedido->descuento, 2) }}</dd>
                        </div>
                        <div class="flex justify-between border-t border-atlantia-ink pt-4">
                            <dt>
                                <span class="block font-black text-atlantia-ink">Total</span>
                                <span class="text-xs text-atlantia-ink/55">IVA incluido</span>
                            </dt>
                            <dd class="text-3xl font-black text-atlantia-wine">Q {{ number_format((float) $pedido->total, 2) }}</dd>
                        </div>
                    </dl>

                    @php
                        [$textoPago, $colorPago] = match (true) {
                            $payment?->estadoValor() === 'reembolsado' => ['Pago devuelto', 'bg-emerald-100 text-emerald-800'],
                            in_array($payment?->estadoValor(), ['anulado', 'rechazado'], true) => ['Pago anulado', 'bg-slate-100 text-slate-700'],
                            in_array($payment?->estadoValor(), ['aprobado', 'pagado'], true) => ['Pago confirmado', 'bg-amber-100 text-amber-800'],
                            default => ['Pago al entregar', 'bg-amber-100 text-amber-800'],
                        };
                    @endphp
                    <span
                        class="mt-5 inline-flex rounded-full px-4 py-2 text-xs font-black uppercase {{ $colorPago }}"
                    >
                        {{ $textoPago }}
                    </span>

                    <div class="mt-6 grid gap-3">
                        @php
                            $sePuedeCancelar = auth()->check() && $pedido->pedido_padre_id === null && (int) $pedido->cliente_id === (int) auth()->id()
                                && ! in_array($estado, ['entregado', 'cancelado'], true);
                            $razonNoCancelable = $sePuedeCancelar ? \App\Services\Pedidos\CancelacionService::motivoNoCancelable($pedido) : null;
                        @endphp
                        @if ($sePuedeCancelar && $razonNoCancelable === null)
                            <details class="rounded-md border border-rose-200" @if ($errors->has('motivo') || $errors->has('pedido')) open @endif data-cancelar-pedido>
                                <summary class="cursor-pointer list-none px-4 py-3 text-center text-sm font-black text-rose-700 [&::-webkit-details-marker]:hidden">Cancelar pedido</summary>
                                <form method="POST" action="{{ route('cliente.pedidos.cancelar', $pedido) }}" class="space-y-2 border-t border-rose-100 px-4 py-3" data-confirm="¿Cancelar este pedido? Esta acción no se puede deshacer.">
                                    @csrf
                                    <p class="text-xs text-atlantia-ink/60">Puedes cancelarlo mientras la tienda no lo acepte.{{ ($payment?->metodo?->value ?? $payment?->metodo) === 'tarjeta' && in_array($payment?->estadoValor(), ['aprobado', 'pagado'], true) ? ' Te devolvemos el pago a tu tarjeta en un plazo '.\App\Services\Pedidos\CancelacionService::PLAZO_DEVOLUCION.'.' : '' }}</p>
                                    <select name="motivo" required class="w-full rounded-md border border-atlantia-rose/30 px-3 py-2 text-sm">
                                        <option value="">¿Por qué lo cancelas?</option>
                                        @foreach (\App\Http\Controllers\Cliente\PedidoController::MOTIVOS_CANCELACION as $clave => $texto)
                                            <option value="{{ $clave }}" @selected(old('motivo') === $clave)>{{ $texto }}</option>
                                        @endforeach
                                    </select>
                                    @error('motivo') <p class="text-xs font-bold text-red-600">{{ $message }}</p> @enderror
                                    @error('pedido') <p class="text-xs font-bold text-red-600">{{ $message }}</p> @enderror
                                    <button type="submit" class="w-full rounded-md bg-rose-700 px-4 py-2.5 text-sm font-black text-white hover:bg-rose-800">Sí, cancelar pedido</button>
                                </form>
                            </details>
                        @elseif ($sePuedeCancelar)
                            <p class="rounded-md bg-slate-50 px-3 py-2 text-xs text-atlantia-ink/60" data-no-cancelable>{{ $razonNoCancelable }} <a href="{{ route('contacto') }}#escribenos" class="font-bold text-atlantia-wine hover:underline">Escríbenos</a></p>
                        @endif
                        @if ($estado === 'entregado' && auth()->check() && $pedido->pedido_padre_id === null)
                            @php
                                $calificador = app(\App\Services\Repartidores\CalificacionRepartidorService::class);
                                $calificacionRep = $calificador->calificacionDe($pedido);
                                $repartidorPedido = $calificacionRep ? null : $calificador->repartidorDe($pedido);
                            @endphp
                            @if ($calificacionRep)
                                <p class="rounded-md bg-atlantia-blush/60 px-3 py-2 text-center text-xs font-bold text-atlantia-ink" data-repartidor-calificado>
                                    Calificaste al repartidor con <span class="text-amber-500">{{ str_repeat('★', $calificacionRep->estrellas) }}</span>{{ str_repeat('☆', 5 - $calificacionRep->estrellas) }}
                                </p>
                            @elseif ($repartidorPedido && (int) $pedido->cliente_id === (int) auth()->id())
                                <form method="POST" action="{{ route('cliente.pedidos.calificar-repartidor', $pedido) }}" class="rounded-md border border-atlantia-rose/25 p-3" data-calificar-repartidor
                                      x-data="{ estrellas: {{ (int) old('estrellas', 0) }}, sobre: 0 }">
                                    @csrf
                                    <p class="text-xs font-black text-atlantia-ink">¿Cómo te atendió {{ \Illuminate\Support\Str::of($repartidorPedido->name)->before(' ') }}, tu repartidor?</p>
                                    <div class="mt-2 flex justify-center gap-1" role="radiogroup" aria-label="Calificación del repartidor" x-on:mouseleave="sobre = 0">
                                        @for ($i = 1; $i <= 5; $i++)
                                            <label class="cursor-pointer text-3xl leading-none transition" x-on:mouseenter="sobre = {{ $i }}"
                                                   x-bind:class="(sobre || estrellas) >= {{ $i }} ? 'text-amber-400' : 'text-atlantia-ink/20'">
                                                <input type="radio" name="estrellas" value="{{ $i }}" class="sr-only" x-model.number="estrellas" required aria-label="{{ $i }} {{ $i === 1 ? 'estrella' : 'estrellas' }}">★
                                            </label>
                                        @endfor
                                    </div>
                                    <textarea name="comentario" rows="2" maxlength="300" placeholder="Comentario (opcional)" class="mt-2 w-full resize-none rounded-md border border-atlantia-rose/30 px-3 py-2 text-sm">{{ old('comentario') }}</textarea>
                                    @error('estrellas') <p class="text-xs font-bold text-red-600">{{ $message }}</p> @enderror
                                    <button type="submit" class="mt-2 w-full rounded-md border border-atlantia-wine px-3 py-2 text-xs font-black text-atlantia-wine hover:bg-atlantia-blush disabled:opacity-50" x-bind:disabled="! estrellas">Enviar calificación</button>
                                </form>
                            @endif
                        @endif
                        @if ($estado === 'entregado' && auth()->check())
                            <form method="POST" action="{{ route('cliente.pedidos.repetir', $pedido) }}">
                                @csrf
                                <button type="submit" class="w-full rounded-md bg-atlantia-wine px-5 py-3 text-center text-sm font-black text-white hover:bg-atlantia-wine-700" data-volver-a-pedir>
                                    Volver a pedir
                                </button>
                            </form>
                            @php
                                $recurrente = \App\Models\PedidoRecurrente::query()->where('pedido_origen_id', $pedido->id)->where('activo', true)->first();
                            @endphp
                            <form method="POST" action="{{ route('cliente.pedidos.recurrente', $pedido) }}" class="rounded-md border border-atlantia-rose/25 p-3" data-pedido-recurrente>
                                @csrf
                                <label for="dia_semana" class="block text-xs font-black text-atlantia-ink">Repetir este pedido cada semana</label>
                                <div class="mt-2 flex gap-2">
                                    <select id="dia_semana" name="dia_semana" class="min-w-0 flex-1 rounded-md border border-atlantia-rose/30 px-2 py-2 text-sm">
                                        <option value="">No repetir</option>
                                        @foreach (\App\Models\PedidoRecurrente::DIAS as $numeroDia => $dia)
                                            <option value="{{ $numeroDia }}" @selected($recurrente?->dia_semana === $numeroDia)>Cada {{ $dia }}</option>
                                        @endforeach
                                    </select>
                                    <button type="submit" class="rounded-md border border-atlantia-wine px-3 py-2 text-xs font-black text-atlantia-wine hover:bg-atlantia-blush">Guardar</button>
                                </div>
                                <p class="mt-1 text-[11px] text-atlantia-ink/55">Ese día te dejamos el pedido listo en el carrito y te avisamos. Tú confirmas.</p>
                            </form>
                        @elseif (! in_array($estado, ['cancelado', 'rechazado'], true))
                            <a
                                href="{{ $seguimientoUrl }}"
                                class="rounded-md bg-atlantia-wine px-5 py-3 text-center text-sm font-black text-white
                                    hover:bg-atlantia-wine-700"
                            >
                                Seguir mi pedido
                            </a>
                        @endif
                        <a
                            href="{{ route('catalogo.index') }}"
                            class="rounded-md border border-atlantia-rose/40 px-5 py-3 text-center text-sm font-black
                                text-atlantia-wine hover:bg-atlantia-blush"
                        >
                            Seguir comprando
                        </a>
                        @can('create', [\App\Models\Devolucion::class, $pedido])
                            <a
                                href="{{ route('cliente.devoluciones.create', $pedido) }}"
                                class="rounded-md border border-atlantia-rose/40 px-5 py-3 text-center text-sm font-black
                                    text-atlantia-wine hover:bg-atlantia-blush"
                            >
                                Solicitar devolucion
                            </a>
                        @endcan
                    </div>

                    @php
                        $telSoporte = config('atlantia.support.phone');
                        $correoSoporte = config('atlantia.support.email');
                        $waSoporte = \App\Services\Notificaciones\WhatsAppService::normalizarTelefono(config('atlantia.support.whatsapp'));
                    @endphp
                    <div class="mt-6 rounded-lg bg-atlantia-blush p-4" data-ayuda-pedido>
                        <h3 class="font-black text-atlantia-ink">¿Necesitas ayuda?</h3>
                        <p class="mt-2 text-sm leading-6 text-atlantia-ink/70">
                            @if ($telSoporte)
                                Llámanos al <a href="tel:{{ preg_replace('/[^0-9+]/', '', $telSoporte) }}" class="font-bold text-atlantia-wine hover:underline">{{ $telSoporte }}</a>
                            @endif
                            @if ($waSoporte)
                                · <a href="https://wa.me/{{ $waSoporte }}?text={{ rawurlencode('Hola, necesito ayuda con mi pedido #'.$pedido->numero_pedido) }}" target="_blank" rel="noopener" class="font-bold text-atlantia-wine hover:underline">WhatsApp</a>
                            @endif
                            @if ($correoSoporte)
                                · <a href="mailto:{{ $correoSoporte }}" class="font-bold text-atlantia-wine hover:underline">{{ $correoSoporte }}</a>
                            @endif
                        </p>
                    </div>
                </section>
            </aside>
        </div>
    </section>
@endsection
