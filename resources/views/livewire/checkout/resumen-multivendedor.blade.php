<section class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm" aria-labelledby="resumen-checkout-title">
    <h2 id="resumen-checkout-title" class="text-lg font-semibold text-slate-950">
        Resumen por vendedor
    </h2>
    <p class="mt-1 text-sm text-slate-600">
        Cada vendedor emitira su propio DTE FEL cuando el pedido sea confirmado.
    </p>

    @if ($grupos->isEmpty())
        <div class="mt-4">
            <x-ui.empty-state
                title="No hay productos para pagar"
                message="Agrega productos al carrito antes de finalizar tu compra."
            />
        </div>
    @else
        <div class="mt-4 space-y-4">
            @foreach ($grupos as $grupo)
                <article class="rounded-lg border border-slate-200 p-4">
                    <div class="flex items-center justify-between gap-4">
                        <h3 class="font-semibold text-slate-950">
                            {{ $grupo['vendor']?->business_name ?? 'Vendedor local' }}
                        </h3>
                        <span class="text-sm font-semibold text-slate-700">
                            Q {{ number_format($grupo['subtotal'], 2) }}
                        </span>
                    </div>

                    <ul class="mt-3 space-y-2 text-sm text-slate-600">
                        @foreach ($grupo['items'] as $item)
                            <li class="flex justify-between gap-3">
                                <span>{{ $item->cantidad }} x {{ $item->producto?->nombre }}</span>
                                <span>
                                    Q {{ number_format($item->cantidad * (float) $item->precio_unitario_snapshot, 2) }}
                                </span>
                            </li>
                        @endforeach
                    </ul>
                </article>
            @endforeach
        </div>

        <dl class="mt-5 space-y-3 border-t border-slate-200 pt-4 text-sm">
            <div class="flex justify-between">
                <dt class="text-slate-600">Subtotal</dt>
                <dd class="font-medium text-slate-950">Q {{ number_format($subtotal, 2) }}</dd>
            </div>
            <div class="flex justify-between">
                <dt class="text-slate-600">Envio estimado</dt>
                <dd class="font-medium text-slate-950">Q {{ number_format($envio, 2) }}</dd>
            </div>
            <div class="flex justify-between">
                <dt class="text-slate-600">IVA estimado</dt>
                <dd class="font-medium text-slate-950">Q {{ number_format($impuestos, 2) }}</dd>
            </div>
            <div class="flex justify-between border-t border-slate-200 pt-3 text-base">
                <dt class="font-semibold text-slate-950">Total</dt>
                <dd class="font-bold text-emerald-800">Q {{ number_format($total, 2) }}</dd>
            </div>
        </dl>

        <input type="hidden" name="envio" value="{{ $envio }}">
        <input type="hidden" name="metodo_pago" value="{{ $metodoPago }}">
    @endif
</section>
