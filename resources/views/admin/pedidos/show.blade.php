@extends(auth()->user()?->isSuperAdmin() && request()->routeIs('admin.*') ? 'layouts.super-admin' : 'layouts.app')

@section('content')
    @php
        $estadoActual = $pedido->estadoValor();
        $estadoPagoActual = $pedido->estadoPagoValor();
        $clienteNombre = $pedido->cliente?->name ?? $pedido->facturacion_nombre ?? 'Cliente invitado';
        $clienteEmail = $pedido->cliente?->email ?? $pedido->facturacion_email ?? 'Sin correo registrado';
        $vendedorNombre = $pedido->vendor?->business_name ?? 'Atlantia Supermarket';
        $repartidorNombre = $pedido->deliveryRoute?->repartidor?->name ?? 'Sin asignar';
        $estadoPedidoOptions = [
            'pendiente' => 'Pendiente',
            'confirmado' => 'Confirmado',
            'preparando' => 'En preparacion',
            'listo_para_entrega' => 'Listo para recoger',
            'en_ruta' => 'En camino',
            'entregado' => 'Entregado',
            'cancelado' => 'Cancelado',
        ];
        $estadoPagoOptions = [
            'pendiente' => 'Pendiente',
            'validando' => 'Validando',
            'pagado' => 'Pagado',
            'rechazado' => 'Rechazado',
            'reembolsado' => 'Reembolsado',
        ];
        $timeline = [
            'pendiente' => ['label' => 'Pendiente', 'hint' => 'El pedido esta esperando revision.'],
            'confirmado' => ['label' => 'Confirmado', 'hint' => 'El pedido fue confirmado por el equipo.'],
            'preparando' => ['label' => 'En preparacion', 'hint' => 'Los productos estan siendo preparados.'],
            'en_ruta' => ['label' => 'En camino', 'hint' => 'El pedido esta en ruta de entrega.'],
            'entregado' => ['label' => 'Entregado', 'hint' => 'El pedido fue entregado al cliente.'],
        ];
        $timelineKeys = array_keys($timeline);
        $timelineIndex = array_search($estadoActual, $timelineKeys, true);
        $timelineIndex = $timelineIndex === false
            ? ($estadoActual === 'listo_para_entrega' ? 2 : -1)
            : $timelineIndex;
        $estadoBadge = match ($estadoActual) {
            'entregado' => 'border-emerald-200 bg-emerald-50 text-emerald-700',
            'cancelado', 'rechazado' => 'border-rose-200 bg-rose-50 text-rose-700',
            'en_ruta' => 'border-sky-200 bg-sky-50 text-sky-700',
            default => 'border-atlantia-rose/25 bg-atlantia-cream text-atlantia-wine',
        };
        $estadoPagoBadge = match ($estadoPagoActual) {
            'pagado', 'aprobado' => 'border-emerald-200 bg-emerald-50 text-emerald-700',
            'rechazado', 'reembolsado' => 'border-rose-200 bg-rose-50 text-rose-700',
            default => 'border-amber-200 bg-amber-50 text-amber-700',
        };
    @endphp

    <section class="mx-auto max-w-[1280px] py-2">
        <div class="grid gap-4 xl:grid-cols-[minmax(0,1.35fr)_minmax(360px,0.85fr)]">
            <div class="space-y-4">
                <div class="rounded-2xl border border-atlantia-rose/20 bg-white p-5 shadow-sm">
                    <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                        <div>
                            <p class="text-xs font-black uppercase tracking-[0.18em] text-atlantia-rose">Atlantia Supermarket</p>
                            <h1 class="mt-2 text-3xl font-black leading-tight text-atlantia-ink">
                                Pedido {{ $pedido->numero_pedido }}
                            </h1>
                            <p class="mt-1 text-sm text-atlantia-ink/65">Control administrativo del flujo comercial y logistico.</p>
                        </div>

                        <span class="inline-flex items-center gap-2 self-start rounded-xl border px-4 py-3 text-sm font-black {{ $estadoBadge }}">
                            <span class="h-2.5 w-2.5 rounded-full bg-current"></span>
                            {{ $estadoPedidoOptions[$estadoActual] ?? ucfirst(str_replace('_', ' ', $estadoActual)) }}
                        </span>
                    </div>

                    <div class="mt-5 grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                        <div class="rounded-xl border border-atlantia-rose/20 bg-atlantia-cream/60 p-4">
                            <div class="flex items-start gap-3">
                                <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-white text-atlantia-wine shadow-sm">
                                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21a8 8 0 0 0-16 0"/><circle cx="12" cy="7" r="4"/></svg>
                                </span>
                                <div class="min-w-0">
                                    <p class="text-xs font-bold text-atlantia-ink/50">Cliente</p>
                                    <p class="mt-1 truncate font-black text-atlantia-ink">{{ $clienteNombre }}</p>
                                    <p class="truncate text-xs text-atlantia-ink/55">{{ $clienteEmail }}</p>
                                </div>
                            </div>
                        </div>
                        <div class="rounded-xl border border-atlantia-rose/20 bg-atlantia-cream/60 p-4">
                            <div class="flex items-start gap-3">
                                <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-white text-atlantia-wine shadow-sm">
                                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9.5 12 4l9 5.5"/><path d="M5 10v9h14v-9"/><path d="M9 19v-5h6v5"/></svg>
                                </span>
                                <div class="min-w-0">
                                    <p class="text-xs font-bold text-atlantia-ink/50">Vendedor</p>
                                    <p class="mt-1 truncate font-black text-atlantia-ink">{{ $vendedorNombre }}</p>
                                    <p class="truncate text-xs text-atlantia-ink/55">{{ ucfirst($pedido->metodoPagoValor()) }}</p>
                                </div>
                            </div>
                        </div>
                        <div class="rounded-xl border border-atlantia-rose/20 bg-atlantia-cream/60 p-4">
                            <div class="flex items-start gap-3">
                                <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-white text-atlantia-wine shadow-sm">
                                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="2" x2="12" y2="22"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7H14a3.5 3.5 0 0 1 0 7H6"/></svg>
                                </span>
                                <div>
                                    <p class="text-xs font-bold text-atlantia-ink/50">Total</p>
                                    <p class="mt-1 text-2xl font-black text-atlantia-wine">Q{{ number_format((float) $pedido->total, 2) }}</p>
                                    <p class="text-xs text-atlantia-ink/55">Pago {{ str_replace('_', ' ', $estadoPagoActual) }}</p>
                                </div>
                            </div>
                        </div>
                        <div class="rounded-xl border border-atlantia-rose/20 bg-atlantia-cream/60 p-4">
                            <div class="flex items-start gap-3">
                                <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-white text-atlantia-wine shadow-sm">
                                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10 17h4V5H2v12h3"/><path d="M14 17h1"/><path d="M22 17h-3"/><path d="M14 8h4l4 4v5"/><circle cx="7.5" cy="17.5" r="2.5"/><circle cx="16.5" cy="17.5" r="2.5"/></svg>
                                </span>
                                <div class="min-w-0">
                                    <p class="text-xs font-bold text-atlantia-ink/50">Entrega</p>
                                    <p class="mt-1 truncate font-black text-atlantia-ink">{{ $estadoPedidoOptions[$estadoActual] ?? ucfirst($estadoActual) }}</p>
                                    <p class="truncate text-xs text-atlantia-ink/55">{{ $repartidorNombre }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="rounded-2xl border border-atlantia-rose/20 bg-white p-5 shadow-sm">
                    <div class="mb-4 flex items-center gap-3">
                        <span class="flex h-9 w-9 items-center justify-center rounded-full bg-atlantia-cream text-atlantia-wine">
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4Z"/><path d="M3 6h18"/><path d="M16 10a4 4 0 0 1-8 0"/></svg>
                        </span>
                        <h2 class="text-lg font-black text-atlantia-wine">Items del pedido</h2>
                    </div>

                    <div class="overflow-hidden rounded-xl border border-atlantia-rose/15">
                        <table class="min-w-full text-sm">
                            <thead class="bg-atlantia-cream/50">
                                <tr class="text-left text-xs font-black uppercase tracking-wide text-atlantia-ink/55">
                                    <th class="px-4 py-3">Producto</th>
                                    <th class="px-4 py-3 text-center">Cantidad</th>
                                    <th class="px-4 py-3 text-right">Precio</th>
                                    <th class="px-4 py-3 text-right">Subtotal</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-atlantia-rose/15 bg-white">
                                @forelse ($pedido->items as $item)
                                    @php
                                        $producto = $item->producto;
                                        $imageUrl = $producto?->getFirstMediaUrl('productos', 'thumbnail')
                                            ?: ($producto?->imagenPrincipal?->path ? \Illuminate\Support\Facades\Storage::url($producto->imagenPrincipal->path) : null);
                                    @endphp
                                    <tr>
                                        <td class="px-4 py-3">
                                            <div class="flex items-center gap-3">
                                                <div class="flex h-11 w-11 shrink-0 items-center justify-center overflow-hidden rounded-lg border border-atlantia-rose/15 bg-atlantia-cream/60">
                                                    @if ($imageUrl)
                                                        <img src="{{ $imageUrl }}" alt="{{ $item->producto_nombre_snapshot ?: $producto?->nombre }}" class="h-full w-full object-contain">
                                                    @else
                                                        <svg class="h-5 w-5 text-atlantia-wine/60" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m21 16-4 4-4-4"/><path d="M17 20V4"/><path d="m3 8 4-4 4 4"/><path d="M7 4v16"/></svg>
                                                    @endif
                                                </div>
                                                <div class="min-w-0">
                                                    <p class="truncate font-black text-atlantia-ink">
                                                        {{ $item->producto_nombre_snapshot ?: $producto?->nombre ?? 'Producto eliminado' }}
                                                    </p>
                                                    <p class="truncate text-xs text-atlantia-ink/50">
                                                        {{ $item->producto_sku_snapshot ?: $producto?->sku ?? 'Sin SKU' }}
                                                    </p>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-4 py-3 text-center font-semibold text-atlantia-ink/75">{{ $item->cantidad }}</td>
                                        <td class="px-4 py-3 text-right font-semibold text-atlantia-ink/75">Q{{ number_format((float) $item->precio_unitario_snapshot, 2) }}</td>
                                        <td class="px-4 py-3 text-right font-black text-atlantia-ink">Q{{ number_format((float) $item->subtotal, 2) }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="px-4 py-8 text-center text-sm text-atlantia-ink/55">
                                            Sin items registrados en este pedido.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="grid gap-4 lg:grid-cols-3">
                    <div class="rounded-2xl border border-atlantia-rose/20 bg-white p-5 shadow-sm">
                        <div class="mb-3 flex items-center gap-3">
                            <span class="flex h-8 w-8 items-center justify-center rounded-full bg-atlantia-cream text-atlantia-wine">
                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="5" width="20" height="14" rx="2"/><line x1="2" y1="10" x2="22" y2="10"/></svg>
                            </span>
                            <h2 class="font-black text-atlantia-wine">Pagos y split</h2>
                        </div>
                        <div class="space-y-2">
                            @forelse ($pedido->payments as $payment)
                                <div class="rounded-xl border border-atlantia-rose/15 bg-atlantia-cream/45 p-3">
                                    <div class="flex items-center justify-between gap-3">
                                        <div>
                                            <p class="font-black text-atlantia-ink">{{ ucfirst($payment->metodoValor()) }}</p>
                                            <p class="text-xs text-atlantia-ink/55">{{ str_replace('_', ' ', $payment->estadoValor()) }}</p>
                                        </div>
                                        <p class="font-black text-atlantia-wine">Q{{ number_format((float) $payment->monto, 2) }}</p>
                                    </div>
                                </div>
                            @empty
                                <p class="text-sm text-atlantia-ink/60">No hay pagos registrados para este pedido.</p>
                            @endforelse
                        </div>
                    </div>

                    <div class="rounded-2xl border border-atlantia-rose/20 bg-white p-5 shadow-sm">
                        <div class="mb-3 flex items-center gap-3">
                            <span class="flex h-8 w-8 items-center justify-center rounded-full bg-atlantia-cream text-atlantia-wine">
                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 3v18h18"/><path d="m19 9-5 5-4-4-3 3"/></svg>
                            </span>
                            <h2 class="font-black text-atlantia-wine">Historial</h2>
                        </div>
                        <div class="max-h-44 space-y-2 overflow-y-auto pr-1">
                            @forelse ($pedido->historialEstados as $estado)
                                <div class="rounded-xl border border-atlantia-rose/15 bg-atlantia-cream/45 p-3">
                                    <p class="font-black text-atlantia-ink">{{ ucfirst(str_replace('_', ' ', $estado->estado_nuevo)) }}</p>
                                    <p class="mt-1 text-xs text-atlantia-ink/60">{{ $estado->nota ?: 'Sin observaciones.' }}</p>
                                    <p class="mt-1 text-[11px] text-atlantia-ink/45">{{ $estado->created_at?->format('d/m/Y H:i') }}</p>
                                </div>
                            @empty
                                <p class="text-sm text-atlantia-ink/60">Este pedido aun no tiene historial operativo.</p>
                            @endforelse
                        </div>
                    </div>

                    <div class="rounded-2xl border border-atlantia-rose/20 bg-white p-5 shadow-sm">
                        <div class="mb-3 flex items-center gap-3">
                            <span class="flex h-8 w-8 items-center justify-center rounded-full bg-atlantia-cream text-atlantia-wine">
                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10 17h4V5H2v12h3"/><path d="M14 8h4l4 4v5"/><circle cx="7.5" cy="17.5" r="2.5"/><circle cx="16.5" cy="17.5" r="2.5"/></svg>
                            </span>
                            <h2 class="font-black text-atlantia-wine">Entrega</h2>
                        </div>
                        <dl class="space-y-2 text-sm">
                            <div>
                                <dt class="text-xs font-bold text-atlantia-ink/45">Direccion</dt>
                                <dd class="font-semibold text-atlantia-ink">{{ $pedido->direccion?->direccion_linea_1 ?? 'Sin direccion registrada' }}</dd>
                            </div>
                            @if ($pedido->direccion?->referencia)
                                <div>
                                    <dt class="text-xs font-bold text-atlantia-ink/45">Referencia</dt>
                                    <dd class="font-semibold text-atlantia-ink">{{ $pedido->direccion->referencia }}</dd>
                                </div>
                            @endif
                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <dt class="text-xs font-bold text-atlantia-ink/45">Municipio</dt>
                                    <dd class="font-semibold text-atlantia-ink">{{ $pedido->direccion?->municipio ?? 'Sin municipio' }}</dd>
                                </div>
                                <div>
                                    <dt class="text-xs font-bold text-atlantia-ink/45">ETA</dt>
                                    <dd>
                                        <span class="inline-flex rounded-full bg-amber-100 px-2 py-1 text-xs font-black text-amber-700">
                                            {{ $pedido->deliveryRoute?->tiempo_estimado_min ? $pedido->deliveryRoute->tiempo_estimado_min . ' min' : 'Pendiente' }}
                                        </span>
                                    </dd>
                                </div>
                            </div>
                        </dl>
                    </div>
                </div>
            </div>

            <aside class="space-y-4 xl:sticky xl:top-24 xl:self-start">
                <div class="rounded-2xl border border-atlantia-rose/20 bg-white p-5 shadow-sm">
                    <div class="flex items-center justify-between gap-3">
                        <h2 class="text-lg font-black text-atlantia-wine">Gestion del estado del pedido</h2>
                        <a href="#historial-pedido" class="inline-flex items-center gap-2 rounded-lg border border-atlantia-rose/25 px-3 py-2 text-xs font-black text-atlantia-wine hover:bg-atlantia-cream">
                            <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 12a9 9 0 1 0 3-6.7"/><path d="M3 3v6h6"/></svg>
                            Ver historial
                        </a>
                    </div>

                    <div class="mt-5 space-y-3">
                        @foreach ($timeline as $key => $step)
                            @php
                                $index = array_search($key, $timelineKeys, true);
                                $isCurrent = $key === $estadoActual || ($estadoActual === 'listo_para_entrega' && $key === 'preparando');
                                $isDone = $timelineIndex >= 0 && $index < $timelineIndex;
                            @endphp
                            <div class="flex gap-3">
                                <div class="flex flex-col items-center">
                                    <span class="flex h-7 w-7 items-center justify-center rounded-full border text-xs font-black {{ $isCurrent ? 'border-atlantia-wine bg-atlantia-wine text-white' : ($isDone ? 'border-emerald-500 bg-emerald-500 text-white' : 'border-atlantia-rose/25 bg-white text-atlantia-ink/50') }}">
                                        {{ $index + 1 }}
                                    </span>
                                    @if (! $loop->last)
                                        <span class="mt-1 h-8 w-px {{ $isDone ? 'bg-emerald-400' : 'bg-atlantia-rose/20' }}"></span>
                                    @endif
                                </div>
                                <div class="min-w-0 flex-1 rounded-xl px-3 py-2 {{ $isCurrent ? 'bg-atlantia-cream' : '' }}">
                                    <div class="flex items-center justify-between gap-2">
                                        <p class="font-black text-atlantia-ink">{{ $step['label'] }}</p>
                                        @if ($isCurrent)
                                            <span class="rounded-full bg-white px-2 py-0.5 text-[11px] font-black text-atlantia-wine">Actual</span>
                                        @endif
                                    </div>
                                    <p class="mt-0.5 text-xs text-atlantia-ink/60">{{ $step['hint'] }}</p>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

                <form method="POST" action="{{ route('admin.pedidos.update', $pedido->uuid) }}" class="rounded-2xl border border-atlantia-rose/20 bg-white p-5 shadow-sm" data-order-admin-form>
                    @csrf
                    @method('PUT')

                    <div class="grid gap-3 sm:grid-cols-2">
                        <div>
                            <label class="text-xs font-black uppercase tracking-wide text-atlantia-ink/55">Estado de pago</label>
                            <select name="estado_pago" class="mt-1 w-full rounded-lg border border-atlantia-rose/30 bg-white px-3 py-2.5 text-sm font-semibold text-atlantia-ink focus:border-atlantia-wine focus:outline-none">
                                @foreach ($estadoPagoOptions as $valor => $etiqueta)
                                    <option value="{{ $valor }}" @selected($estadoPagoActual === $valor)>{{ $etiqueta }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="text-xs font-black uppercase tracking-wide text-atlantia-ink/55">Repartidor asignado</label>
                            <select name="repartidor_id" class="mt-1 w-full rounded-lg border border-atlantia-rose/30 bg-white px-3 py-2.5 text-sm font-semibold text-atlantia-ink focus:border-atlantia-wine focus:outline-none">
                                <option value="">Sin asignar</option>
                                @foreach ($repartidores as $repartidor)
                                    <option value="{{ $repartidor->id }}" @selected((int) $pedido->deliveryRoute?->repartidor_id === (int) $repartidor->id)>
                                        {{ $repartidor->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <input type="hidden" name="estado" value="{{ $estadoActual }}" data-order-status-input>

                    <div class="mt-4">
                        <p class="text-xs font-black uppercase tracking-wide text-atlantia-ink/55">Acciones rapidas</p>
                        <div class="mt-2 grid grid-cols-3 gap-2">
                            @foreach ([
                                'confirmado' => ['label' => 'Confirmar pedido', 'icon' => 'check'],
                                'preparando' => ['label' => 'En preparacion', 'icon' => 'box'],
                                'en_ruta' => ['label' => 'En camino', 'icon' => 'truck'],
                            ] as $valor => $accion)
                                <button type="button" data-order-status="{{ $valor }}" class="inline-flex min-h-10 items-center justify-center gap-2 rounded-lg border border-atlantia-rose/25 px-3 py-2 text-xs font-black text-atlantia-wine transition hover:bg-atlantia-cream">
                                    @if ($accion['icon'] === 'check')
                                        <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m20 6-11 11-5-5"/></svg>
                                    @elseif ($accion['icon'] === 'box')
                                        <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m21 8-9-5-9 5 9 5 9-5Z"/><path d="M3 8v8l9 5 9-5V8"/><path d="M12 13v8"/></svg>
                                    @else
                                        <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10 17h4V5H2v12h3"/><path d="M14 8h4l4 4v5"/><circle cx="7.5" cy="17.5" r="2.5"/><circle cx="16.5" cy="17.5" r="2.5"/></svg>
                                    @endif
                                    {{ $accion['label'] }}
                                </button>
                            @endforeach
                        </div>
                    </div>

                    <div class="mt-4 grid gap-3 sm:grid-cols-2">
                        <div>
                            <label class="text-xs font-black uppercase tracking-wide text-atlantia-ink/55">Notas internas</label>
                            <textarea name="notas" rows="4" class="mt-1 w-full resize-none rounded-lg border border-atlantia-rose/30 px-3 py-2 text-sm text-atlantia-ink focus:border-atlantia-wine focus:outline-none">{{ $pedido->notas }}</textarea>
                        </div>
                        <div>
                            <label class="text-xs font-black uppercase tracking-wide text-atlantia-ink/55">Nota para historial</label>
                            <textarea name="notas_historial" rows="4" class="mt-1 w-full resize-none rounded-lg border border-atlantia-rose/30 px-3 py-2 text-sm text-atlantia-ink focus:border-atlantia-wine focus:outline-none" placeholder="Ejemplo: pedido validado por equipo de operaciones."></textarea>
                        </div>
                    </div>

                    <button type="submit" class="mt-4 inline-flex w-full items-center justify-center gap-2 rounded-lg bg-atlantia-wine px-4 py-3 text-sm font-black text-white shadow-sm transition hover:bg-atlantia-wine/90">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2Z"/><path d="M17 21v-8H7v8"/><path d="M7 3v5h8"/></svg>
                        Guardar cambios
                    </button>
                </form>

                <div id="historial-pedido" class="rounded-2xl border border-atlantia-rose/20 bg-white p-5 shadow-sm">
                    <div class="mb-3 flex items-center gap-3">
                        <span class="flex h-8 w-8 items-center justify-center rounded-full bg-atlantia-cream text-atlantia-wine">
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8Z"/><path d="M14 2v6h6"/><path d="M16 13H8"/><path d="M16 17H8"/><path d="M10 9H8"/></svg>
                        </span>
                        <h2 class="font-black text-atlantia-wine">Documentos fiscales</h2>
                    </div>
                    <div class="space-y-2">
                        @forelse ($pedido->dteFacturas as $dte)
                            <div class="rounded-xl border border-atlantia-rose/15 bg-atlantia-cream/45 p-3">
                                <p class="font-black text-atlantia-ink">{{ $dte->numero_dte ?? $dte->uuid }}</p>
                                <p class="text-xs text-atlantia-ink/55">{{ $dte->estado }} - {{ $dte->vendor?->business_name ?? $vendedorNombre }}</p>
                            </div>
                        @empty
                            <p class="text-sm text-atlantia-ink/60">No hay DTE emitido para este pedido.</p>
                        @endforelse
                    </div>
                </div>
            </aside>
        </div>
    </section>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const form = document.querySelector('[data-order-admin-form]');
            if (!form) {
                return;
            }

            const statusInput = form.querySelector('[data-order-status-input]');
            form.querySelectorAll('[data-order-status]').forEach((button) => {
                button.addEventListener('click', () => {
                    statusInput.value = button.dataset.orderStatus;
                    form.querySelectorAll('[data-order-status]').forEach((item) => {
                        item.classList.remove('bg-atlantia-wine', 'text-white');
                    });
                    button.classList.add('bg-atlantia-wine', 'text-white');
                });
            });
        });
    </script>
@endsection
