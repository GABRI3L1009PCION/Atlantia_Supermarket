<section
    class="rounded-lg border border-atlantia-rose/20 bg-white p-5 shadow-sm sm:p-7"
    aria-labelledby="resumen-checkout-title"
>
    <h2 id="resumen-checkout-title" class="text-2xl font-bold text-atlantia-ink">
        Resumen del pedido
    </h2>
    <p class="mt-2 text-sm text-atlantia-ink/70">
        Revisa el total antes de confirmar. Cada vendedor emitira su propio DTE FEL.
    </p>

    @if ($grupos->isEmpty())
        <div class="mt-4">
            <x-ui.empty-state
                title="No hay productos para pagar"
                message="Agrega productos al carrito antes de finalizar tu compra."
            />
        </div>
    @else
        <div class="mt-5 space-y-4">
            @foreach ($grupos as $grupo)
                <article class="rounded-lg border border-slate-200 p-4">
                    <div class="flex items-center justify-between gap-4">
                        <div class="flex min-w-0 items-center gap-3">
                            @php
                                $iniciales = \Illuminate\Support\Str::upper(
                                    \Illuminate\Support\Str::substr($grupo['vendor']?->business_name ?? 'AS', 0, 2)
                                );
                            @endphp
                            <span
                                class="flex h-10 w-10 shrink-0 items-center justify-center rounded-md bg-atlantia-wine
                                    text-sm font-bold text-white"
                            >
                                {{ $iniciales }}
                            </span>
                            <div class="min-w-0">
                                <h3 class="truncate font-bold text-atlantia-ink">
                                    {{ $grupo['vendor']?->business_name ?? 'Atlantia Supermarket' }}
                                </h3>
                                <p class="text-xs font-semibold text-sky-700">Factura electronica FEL</p>
                            </div>
                        </div>
                        <span class="text-sm font-bold text-atlantia-wine">
                            Q {{ number_format($grupo['subtotal'], 2) }}
                        </span>
                    </div>

                    <ul class="mt-4 space-y-2 border-t border-slate-200 pt-3 text-sm text-atlantia-ink/75">
                        @foreach ($grupo['items'] as $item)
                            <li class="flex justify-between gap-3">
                                <span>{{ $item->cantidad }} x {{ $item->producto?->nombre }}</span>
                                <span class="font-semibold text-atlantia-ink">
                                    Q {{ number_format($item->cantidad * (float) $item->precio_unitario_snapshot, 2) }}
                                </span>
                            </li>
                        @endforeach
                    </ul>
                </article>
            @endforeach
        </div>

        <dl class="mt-6 space-y-4 border-t border-slate-200 pt-5 text-sm">
            <div class="flex justify-between">
                <dt class="text-atlantia-ink/65">Subtotal</dt>
                <dd class="font-semibold text-atlantia-ink">Q {{ number_format($subtotal, 2) }}</dd>
            </div>
            <div class="flex justify-between">
                <dt class="text-atlantia-ink/65">Envio estimado</dt>
                <dd class="font-semibold text-atlantia-ink">Q {{ number_format($envio, 2) }}</dd>
            </div>
            <div class="flex justify-between">
                <dt class="text-atlantia-ink/65">IVA incluido</dt>
                <dd class="font-semibold text-atlantia-ink">Q {{ number_format($impuestos, 2) }}</dd>
            </div>
            <div class="flex justify-between border-t border-atlantia-ink pt-4 text-base">
                <dt>
                    <span class="block font-bold text-atlantia-ink">Total a pagar</span>
                    <span class="text-xs text-atlantia-ink/55">IVA incluido</span>
                </dt>
                <dd class="text-3xl font-black text-atlantia-wine">Q {{ number_format($total, 2) }}</dd>
            </div>
        </dl>

        <input type="hidden" name="envio" value="{{ $envio }}">
        <input type="hidden" name="metodo_pago" value="{{ $metodoPago }}">

        <label class="mt-5 flex items-start gap-3 text-sm text-atlantia-ink/75">
            <input
                type="checkbox"
                name="acepta_terminos_checkout"
                value="1"
                @checked(old('acepta_terminos_checkout'))
                class="mt-1 rounded border-atlantia-rose text-atlantia-wine focus:ring-atlantia-rose"
            >
            <span>
                Acepto los
                <a href="#" class="font-bold text-sky-700 hover:underline">terminos y condiciones</a>,
                politica de entrega y facturacion FEL por vendedor.
            </span>
        </label>

        @error('acepta_terminos_checkout')
            <p class="mt-2 text-sm font-semibold text-red-700">{{ $message }}</p>
        @enderror

        <button
            type="submit"
            class="mt-5 flex w-full items-center justify-center rounded-md bg-atlantia-wine px-5 py-4 text-base
                font-black text-white shadow-lg shadow-atlantia-wine/20 transition hover:bg-atlantia-wine-700
                focus:outline-none focus:ring-2 focus:ring-atlantia-rose focus:ring-offset-2"
            wire:loading.attr="disabled"
        >
            <span wire:loading.remove>Confirmar y pagar Q {{ number_format($total, 2) }}</span>
            <span wire:loading>Procesando total...</span>
        </button>

        <p class="mt-4 text-center text-xs text-atlantia-ink/55">
            Pago seguro - Cifrado SSL - FEL certificado SAT
        </p>
    @endif
</section>
