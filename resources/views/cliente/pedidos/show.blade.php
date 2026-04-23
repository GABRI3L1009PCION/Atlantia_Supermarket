@extends('layouts.marketplace')

@section('content')
    @php
        $clienteNombre = explode(' ', trim(auth()->user()?->name ?? 'cliente'))[0] ?: 'cliente';
        $payment = $pedido->payments->first();
        $fechaPedido = ($pedido->confirmado_at ?? $pedido->created_at)->format('d/m/Y h:i a');
        $estado = $pedido->estadoValor();
        $estadoPago = $pedido->estadoPagoValor();
        $items = $pedido->pedidosHijos->flatMap->items;
        $itemsCount = (int) $items->sum('cantidad');
        $seguimiento = [
            ['label' => 'Confirmado', 'time' => ($pedido->created_at)->format('h:i a'), 'state' => 'done'],
            ['label' => 'En preparacion', 'time' => in_array($estado, ['preparando', 'en_ruta', 'entregado'], true) ? 'En curso' : 'Pendiente', 'state' => in_array($estado, ['preparando', 'en_ruta', 'entregado'], true) ? 'active' : 'pending'],
            ['label' => 'En camino', 'time' => in_array($estado, ['en_ruta', 'entregado'], true) ? 'En ruta' : 'Pendiente', 'state' => in_array($estado, ['en_ruta', 'entregado'], true) ? 'active' : 'pending'],
            ['label' => 'Entregado', 'time' => $estado === 'entregado' ? 'Completado' : 'Pendiente', 'state' => $estado === 'entregado' ? 'done' : 'pending'],
        ];
        $dtes = $pedido->pedidosHijos->flatMap->dteFacturas;
    @endphp

    <section class="mx-auto w-full max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
        <div class="grid gap-6 lg:grid-cols-[1fr_380px]">
            <div class="space-y-6">
                <section class="rounded-lg border border-atlantia-rose/20 bg-white p-6 shadow-sm sm:p-8">
                    <div class="flex h-20 w-20 items-center justify-center rounded-full bg-emerald-600 text-3xl font-black text-white">
                        OK
                    </div>

                    <p class="mt-5 text-sm font-black uppercase tracking-normal text-emerald-700">
                        Pedido confirmado
                    </p>
                    <h1 class="mt-2 max-w-3xl text-3xl font-black leading-tight text-atlantia-ink sm:text-4xl">
                        Gracias, {{ $clienteNombre }}. Tu pedido esta en camino.
                    </h1>
                    <p class="mt-4 max-w-2xl text-base leading-7 text-atlantia-ink/70">
                        Te enviamos un correo a
                        <span class="font-bold text-atlantia-ink">{{ auth()->user()?->email }}</span>
                        con los detalles y tus facturas electronicas FEL cuando esten certificadas.
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

                    <div class="mt-8 rounded-lg border border-sky-200 bg-sky-50 p-5 sm:flex sm:items-center sm:justify-between">
                        <div>
                            <p class="text-xs font-black uppercase tracking-normal text-sky-700">Entrega estimada</p>
                            <p class="mt-2 text-2xl font-black text-atlantia-ink">Hoy entre 45 y 60 min</p>
                        </div>
                        <a
                            href="{{ route('cliente.pedidos.seguimiento', $pedido) }}"
                            class="mt-4 inline-flex rounded-md bg-atlantia-wine px-5 py-3 text-sm font-bold text-white
                                hover:bg-atlantia-wine-700 sm:mt-0"
                        >
                            Ver en mapa
                        </a>
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
                                $dte = $pedidoHijo->dteFacturas->first();
                                $certificado = $dte?->estado === 'certificado';
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
                                                {{ $certificado ? 'FEL certificado' : 'DTE se emitira al despachar' }}
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

                                @if ($certificado)
                                    <div class="mt-4 grid gap-3 border-t border-slate-200 pt-4 sm:grid-cols-3">
                                        <div class="text-sm">
                                            <span class="block text-atlantia-ink/55">No. autorizacion SAT</span>
                                            <span class="font-bold text-atlantia-wine">{{ $dte->uuid_sat }}</span>
                                        </div>
                                        <a
                                            href="{{ $dte->pdf_path ? asset('storage/' . $dte->pdf_path) : '#' }}"
                                            class="rounded-md border border-atlantia-rose/40 px-4 py-3 text-center text-sm font-bold
                                                text-atlantia-wine hover:bg-atlantia-blush"
                                        >
                                            Ver PDF
                                        </a>
                                        <button
                                            type="button"
                                            class="rounded-md border border-atlantia-rose/40 px-4 py-3 text-sm font-bold
                                                text-atlantia-wine"
                                            disabled
                                        >
                                            XML disponible
                                        </button>
                                    </div>
                                @else
                                    <p class="mt-4 rounded-md bg-amber-50 px-4 py-3 text-sm font-semibold text-amber-800">
                                        Este vendedor emitira FEL al momento de despachar. Te llegara por correo en unos minutos.
                                    </p>
                                @endif
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

                    <span
                        class="mt-5 inline-flex rounded-full bg-amber-100 px-4 py-2 text-xs font-black uppercase text-amber-800"
                    >
                        {{ $payment?->estadoValor() === 'aprobado' ? 'Pago confirmado' : 'Pago al entregar' }}
                    </span>

                    <div class="mt-6 grid gap-3">
                        <a
                            href="{{ route('cliente.pedidos.seguimiento', $pedido) }}"
                            class="rounded-md bg-atlantia-wine px-5 py-3 text-center text-sm font-black text-white
                                hover:bg-atlantia-wine-700"
                        >
                            Seguir mi pedido
                        </a>
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

                    <div class="mt-6 rounded-lg bg-atlantia-blush p-4">
                        <h3 class="font-black text-atlantia-ink">Necesitas ayuda?</h3>
                        <p class="mt-2 text-sm leading-6 text-atlantia-ink/70">
                            Contactanos al 2345-6789 o escribenos a ayuda@atlantia.gt.
                            Tiempo de respuesta menor a 15 min.
                        </p>
                    </div>
                </section>
            </aside>
        </div>
    </section>
@endsection
