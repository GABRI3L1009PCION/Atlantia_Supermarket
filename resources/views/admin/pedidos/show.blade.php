@extends(auth()->user()?->isSuperAdmin() && request()->routeIs('admin.*') ? 'layouts.super-admin' : 'layouts.app')

@section('content')
    <section class="mx-auto max-w-full py-2">
        <div class="grid gap-6 xl:grid-cols-[1.1fr_0.9fr]">
            <div class="space-y-6">
                <div class="rounded-2xl border border-atlantia-rose/20 bg-white p-6 shadow-sm">
                    <x-page-header
                        :title="'Pedido ' . $pedido->numero_pedido"
                        :subtitle="'Control administrativo del flujo comercial y logistico.'"
                    />

                    <div class="mt-6 grid gap-4 md:grid-cols-4">
                        <div class="rounded-xl border border-atlantia-rose/20 bg-atlantia-cream p-4">
                            <p class="text-sm text-atlantia-ink/55">Cliente</p>
                            <p class="mt-2 font-semibold text-atlantia-ink">{{ $pedido->cliente?->name }}</p>
                            <p class="text-sm text-atlantia-ink/55">{{ $pedido->cliente?->email }}</p>
                        </div>
                        <div class="rounded-xl border border-atlantia-rose/20 bg-atlantia-cream p-4">
                            <p class="text-sm text-atlantia-ink/55">Vendedor</p>
                            <p class="mt-2 font-semibold text-atlantia-ink">{{ $pedido->vendor?->business_name ?? 'Consolidado Atlantia' }}</p>
                            <p class="text-sm text-atlantia-ink/55">{{ ucfirst($pedido->metodoPagoValor()) }}</p>
                        </div>
                        <div class="rounded-xl border border-atlantia-rose/20 bg-atlantia-cream p-4">
                            <p class="text-sm text-atlantia-ink/55">Total</p>
                            <p class="mt-2 text-2xl font-bold text-atlantia-wine">Q{{ number_format((float) $pedido->total, 2) }}</p>
                            <p class="text-sm text-atlantia-ink/55">Pago {{ str_replace('_', ' ', $pedido->estadoPagoValor()) }}</p>
                        </div>
                        <div class="rounded-xl border border-atlantia-rose/20 bg-atlantia-cream p-4">
                            <p class="text-sm text-atlantia-ink/55">Entrega</p>
                            <p class="mt-2 font-semibold text-atlantia-ink">{{ ucfirst(str_replace('_', ' ', $pedido->estadoValor())) }}</p>
                            <p class="text-sm text-atlantia-ink/55">{{ $pedido->deliveryRoute?->repartidor?->name ?? 'Sin asignar' }}</p>
                        </div>
                    </div>
                </div>

                <div class="rounded-2xl border border-atlantia-rose/20 bg-white p-6 shadow-sm">
                    <h2 class="text-lg font-bold text-atlantia-wine">Items del pedido</h2>

                    <div class="mt-4 overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <thead>
                                <tr class="border-b border-atlantia-rose/20 text-left text-atlantia-ink/55">
                                    <th class="pb-3">Producto</th>
                                    <th class="pb-3">Cantidad</th>
                                    <th class="pb-3">Precio</th>
                                    <th class="pb-3">Subtotal</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-atlantia-rose/15">
                                @foreach ($pedido->items as $item)
                                    <tr>
                                        <td class="py-3">
                                            <p class="font-semibold text-atlantia-ink">{{ $item->producto?->nombre }}</p>
                                            <p class="text-xs text-atlantia-ink/55">{{ $item->producto?->sku }}</p>
                                        </td>
                                        <td class="py-3 text-atlantia-ink/70">{{ $item->cantidad }}</td>
                                        <td class="py-3 text-atlantia-ink/70">Q{{ number_format((float) $item->precio_unitario_snapshot, 2) }}</td>
                                        <td class="py-3 font-semibold text-atlantia-ink">Q{{ number_format((float) $item->subtotal, 2) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="grid gap-6 lg:grid-cols-2">
                    <div class="rounded-2xl border border-atlantia-rose/20 bg-white p-6 shadow-sm">
                        <h2 class="text-lg font-bold text-atlantia-wine">Pagos y split</h2>
                        <div class="mt-4 space-y-3">
                            @forelse ($pedido->payments as $payment)
                                <div class="rounded-xl border border-atlantia-rose/20 bg-atlantia-cream p-4">
                                    <div class="flex items-center justify-between gap-3">
                                        <div>
                                            <p class="font-semibold text-atlantia-ink">{{ ucfirst($payment->metodoValor()) }}</p>
                                            <p class="text-sm text-atlantia-ink/55">{{ str_replace('_', ' ', $payment->estadoValor()) }}</p>
                                        </div>
                                        <p class="font-bold text-atlantia-wine">Q{{ number_format((float) $payment->monto, 2) }}</p>
                                    </div>

                                    @if ($payment->splits->isNotEmpty())
                                        <div class="mt-3 space-y-2 text-sm">
                                            @foreach ($payment->splits as $split)
                                                <div class="flex items-center justify-between rounded-md bg-white px-3 py-2">
                                                    <span class="text-atlantia-ink">{{ $split->vendor?->business_name }}</span>
                                                    <span class="font-semibold text-atlantia-wine">
                                                        Q{{ number_format((float) $split->monto_neto_vendedor, 2) }}
                                                    </span>
                                                </div>
                                            @endforeach
                                        </div>
                                    @endif
                                </div>
                            @empty
                                <p class="text-sm text-atlantia-ink/60">No hay pagos registrados para este pedido.</p>
                            @endforelse
                        </div>
                    </div>

                    <div class="rounded-2xl border border-atlantia-rose/20 bg-white p-6 shadow-sm">
                        <h2 class="text-lg font-bold text-atlantia-wine">Historial</h2>
                        <div class="mt-4 space-y-3">
                            @forelse ($pedido->estados as $estado)
                                <div class="rounded-xl border border-atlantia-rose/20 bg-atlantia-cream p-4">
                                    <div class="flex items-center justify-between gap-3">
                                        <p class="font-semibold text-atlantia-ink">{{ ucfirst(str_replace('_', ' ', $estado->estado)) }}</p>
                                        <span class="text-xs text-atlantia-ink/55">{{ $estado->created_at?->format('d/m/Y H:i') }}</span>
                                    </div>
                                    <p class="mt-2 text-sm text-atlantia-ink/70">{{ $estado->notas ?: 'Sin observaciones.' }}</p>
                                    <p class="mt-2 text-xs text-atlantia-ink/55">Registrado por {{ $estado->usuario?->name ?? 'Sistema' }}</p>
                                </div>
                            @empty
                                <p class="text-sm text-atlantia-ink/60">Este pedido aun no tiene historial operativo.</p>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>

            <aside class="space-y-6">
                <div class="rounded-2xl border border-atlantia-rose/20 bg-white p-6 shadow-sm">
                    <h2 class="text-lg font-bold text-atlantia-wine">Actualizar operacion</h2>

                    <form method="POST" action="{{ route('admin.pedidos.update', $pedido->uuid) }}" class="mt-4 space-y-4">
                        @csrf
                        @method('PUT')

                        <div>
                            <label class="text-sm font-semibold text-atlantia-ink">Estado del pedido</label>
                            <select name="estado" class="mt-1 w-full rounded-md border border-atlantia-rose/35 px-3 py-2" required>
                                @foreach (['pendiente', 'confirmado', 'preparando', 'en_ruta', 'entregado', 'cancelado'] as $estado)
                                    <option value="{{ $estado }}" @selected($pedido->estadoValor() === $estado)>{{ ucfirst(str_replace('_', ' ', $estado)) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="text-sm font-semibold text-atlantia-ink">Estado de pago</label>
                            <select name="estado_pago" class="mt-1 w-full rounded-md border border-atlantia-rose/35 px-3 py-2" required>
                                @foreach (['pendiente', 'validando', 'pagado', 'rechazado', 'reembolsado'] as $estadoPago)
                                    <option value="{{ $estadoPago }}" @selected($pedido->estadoPagoValor() === $estadoPago)>{{ ucfirst($estadoPago) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="text-sm font-semibold text-atlantia-ink">Repartidor asignado</label>
                            <select name="repartidor_id" class="mt-1 w-full rounded-md border border-atlantia-rose/35 px-3 py-2">
                                <option value="">Sin asignar</option>
                                @foreach ($repartidores as $repartidor)
                                    <option value="{{ $repartidor->id }}" @selected((int) $pedido->deliveryRoute?->repartidor_id === (int) $repartidor->id)>
                                        {{ $repartidor->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="text-sm font-semibold text-atlantia-ink">Notas internas</label>
                            <textarea name="notas" rows="3" class="mt-1 w-full rounded-md border border-atlantia-rose/35 px-3 py-2">{{ $pedido->notas }}</textarea>
                        </div>
                        <div>
                            <label class="text-sm font-semibold text-atlantia-ink">Nota para historial</label>
                            <textarea name="notas_historial" rows="2" class="mt-1 w-full rounded-md border border-atlantia-rose/35 px-3 py-2" placeholder="Ejemplo: pedido validado por equipo de operaciones."></textarea>
                        </div>

                        <x-ui.button type="submit" class="w-full">Guardar cambios</x-ui.button>
                    </form>
                </div>

                <div class="rounded-2xl border border-atlantia-rose/20 bg-white p-6 shadow-sm">
                    <h2 class="text-lg font-bold text-atlantia-wine">Entrega</h2>
                    <dl class="mt-4 space-y-3 text-sm">
                        <div>
                            <dt class="text-atlantia-ink/55">Direccion</dt>
                            <dd class="font-semibold text-atlantia-ink">{{ $pedido->direccion?->direccion_linea_1 ?? 'Sin direccion registrada' }}</dd>
                        </div>
                        <div>
                            <dt class="text-atlantia-ink/55">Municipio</dt>
                            <dd class="font-semibold text-atlantia-ink">{{ $pedido->direccion?->municipio ?? 'Sin municipio' }}</dd>
                        </div>
                        <div>
                            <dt class="text-atlantia-ink/55">ETA</dt>
                            <dd class="font-semibold text-atlantia-ink">{{ $pedido->deliveryRoute?->tiempo_estimado_min ? $pedido->deliveryRoute->tiempo_estimado_min . ' min' : 'Pendiente' }}</dd>
                        </div>
                    </dl>
                </div>

                <div class="rounded-2xl border border-atlantia-rose/20 bg-white p-6 shadow-sm">
                    <h2 class="text-lg font-bold text-atlantia-wine">Documentos fiscales</h2>
                    <div class="mt-4 space-y-3">
                        @forelse ($pedido->dteFacturas as $dte)
                            <div class="rounded-xl border border-atlantia-rose/20 bg-atlantia-cream p-4">
                                <p class="font-semibold text-atlantia-ink">{{ $dte->numero_dte ?? $dte->uuid }}</p>
                                <p class="text-sm text-atlantia-ink/55">{{ $dte->estado }} · {{ $dte->vendor?->business_name }}</p>
                            </div>
                        @empty
                            <p class="text-sm text-atlantia-ink/60">No hay DTE emitido para este pedido.</p>
                        @endforelse
                    </div>
                </div>
            </aside>
        </div>
    </section>
@endsection
