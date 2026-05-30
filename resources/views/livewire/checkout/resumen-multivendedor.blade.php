<section
    class="rounded-xl border border-atlantia-rose/20 bg-white p-4 shadow-xl shadow-atlantia-wine/10"
    aria-labelledby="resumen-checkout-title"
>
    <h2 id="resumen-checkout-title" class="text-lg font-black leading-tight text-atlantia-ink">
        Resumen del pedido
    </h2>
    <p class="mt-1 text-[11px] leading-4 text-atlantia-ink/65">
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
        <div class="mt-3 space-y-2">
            @foreach ($grupos as $grupo)
                <article class="rounded-lg border border-atlantia-rose/20 p-2.5">
                    <div class="flex items-center justify-between gap-4">
                        <div class="flex min-w-0 items-center gap-3">
                            @php
                                $iniciales = \Illuminate\Support\Str::upper(
                                    \Illuminate\Support\Str::substr($grupo['vendor']?->business_name ?? 'AS', 0, 2)
                                );
                            @endphp
                            <span
                                class="flex h-9 w-9 shrink-0 items-center justify-center rounded-md bg-atlantia-wine text-xs font-black text-white"
                            >
                                {{ $iniciales }}
                            </span>
                            <div class="min-w-0">
                                <h3 class="truncate text-sm font-black text-atlantia-ink">
                                    {{ $grupo['vendor']?->business_name ?? 'Atlantia Supermarket' }}
                                </h3>
                                <p class="text-xs font-semibold text-sky-700">Factura electronica FEL</p>
                            </div>
                        </div>
                        <span class="text-sm font-bold text-atlantia-wine">
                            Q {{ number_format($grupo['subtotal'], 2) }}
                        </span>
                    </div>

                    <ul class="mt-2 space-y-1 border-t border-atlantia-rose/15 pt-2 text-xs text-atlantia-ink/75">
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

        <dl class="mt-3 space-y-2 border-t border-slate-200 pt-3 text-xs">
            <div class="flex justify-between">
                <dt class="text-atlantia-ink/65">Subtotal</dt>
                <dd class="font-semibold text-atlantia-ink">Q {{ number_format($subtotal, 2) }}</dd>
            </div>
            <div class="flex justify-between">
                <dt class="text-atlantia-ink/65">Envio estimado</dt>
                <dd class="font-semibold text-atlantia-ink" data-checkout-shipping="{{ number_format($envio, 2, '.', '') }}">Q {{ number_format($envio, 2) }}</dd>
            </div>
            <div class="flex justify-between">
                <dt class="text-atlantia-ink/65">IVA incluido</dt>
                <dd class="font-semibold text-atlantia-ink">Q {{ number_format($impuestos, 2) }}</dd>
            </div>
            <div class="flex justify-between">
                <dt class="text-emerald-700">Descuento</dt>
                <dd class="font-semibold text-emerald-700">- Q {{ number_format($descuento, 2) }}</dd>
            </div>
            <div class="flex justify-between border-t border-atlantia-ink pt-2 text-sm">
                <dt>
                    <span class="block font-bold text-atlantia-ink">Total a pagar</span>
                    <span class="text-xs text-atlantia-ink/55">IVA incluido</span>
                </dt>
                <dd class="text-2xl font-black leading-tight text-atlantia-wine" data-checkout-total="{{ number_format($total, 2, '.', '') }}">Q {{ number_format($total, 2) }}</dd>
            </div>
        </dl>

        <div class="mt-3">
            <div class="flex gap-2">
                <input
                    type="text"
                    wire:model.live.debounce.400ms="couponCode"
                    placeholder="Tienes otro cupon?"
                    class="w-full rounded-md border border-atlantia-rose/30 px-3 py-2 text-xs
                        focus:border-atlantia-wine focus:ring-atlantia-rose"
                >
                <button
                    type="button"
                    wire:click="aplicarCupon"
                    wire:loading.attr="disabled"
                    class="rounded-md bg-atlantia-wine px-4 py-2 text-xs font-black text-white"
                >
                    <span wire:loading.remove wire:target="aplicarCupon">Aplicar</span>
                    <span wire:loading wire:target="aplicarCupon">Validando...</span>
                </button>
            </div>

            @if ($couponState['mensaje'])
                <p class="mt-3 rounded-md px-4 py-3 text-sm font-semibold
                    {{ $couponState['valido'] ? 'bg-emerald-50 text-emerald-700' : 'bg-amber-50 text-amber-700' }}">
                    {{ $couponState['mensaje'] }}
                </p>
            @endif

            @if ($couponState['valido'])
                <button
                    type="button"
                    wire:click="quitarCupon"
                    class="mt-3 text-sm font-bold text-atlantia-wine hover:underline"
                >
                    Quitar cupon
                </button>
            @endif
        </div>

        @if ($puntos)
            <div class="mt-4 rounded-lg bg-sky-50 p-4 text-sm text-sky-900">
                <p class="font-bold">Puntos Atlantia</p>
                <p class="mt-1">Saldo actual: {{ number_format((int) $puntos->puntos_actuales) }} puntos.</p>
                <p class="mt-1">Esta compra te otorgara aproximadamente {{ $puntosProximos }} puntos nuevos.</p>
            </div>
        @endif

        <input type="hidden" name="envio" value="{{ $envio }}">
        <input type="hidden" name="metodo_pago" value="{{ $metodoPago }}">
        <input type="hidden" name="coupon_code" value="{{ $couponCode }}">

        <label class="mt-3 flex items-start gap-2 text-[11px] leading-4 text-atlantia-ink/75">
            <input
                type="checkbox"
                name="acepta_terminos_checkout"
                value="1"
                wire:model.live="aceptaTerminos"
                @checked(old('acepta_terminos_checkout'))
                class="mt-1 rounded border-atlantia-rose text-atlantia-wine focus:ring-atlantia-rose"
            >
            <span>
                Acepto los
                <button type="button" class="font-bold text-sky-700 underline decoration-sky-700/40 underline-offset-2 hover:text-atlantia-wine" data-checkout-terms-open>
                    terminos y condiciones
                </button>,
                politica de entrega y facturacion FEL por vendedor.
            </span>
        </label>

        @error('acepta_terminos_checkout')
            <p class="mt-2 text-sm font-semibold text-red-700">{{ $message }}</p>
        @enderror

        @error('aceptaTerminos')
            <p class="mt-2 text-sm font-semibold text-red-700">{{ $message }}</p>
        @enderror

        <p class="mt-3 rounded-lg bg-atlantia-blush/55 px-3 py-2 text-center text-[11px] font-semibold text-atlantia-ink/55">
            Pago seguro - Cifrado SSL - FEL certificado SAT
        </p>

        <div
            class="fixed inset-0 z-[96] hidden items-center justify-center bg-slate-950/55 px-4 py-6 backdrop-blur-sm"
            data-checkout-terms-modal
            aria-hidden="true"
        >
            <div class="max-h-[88vh] w-full max-w-2xl overflow-hidden rounded-2xl bg-white shadow-2xl shadow-slate-950/25" role="dialog" aria-modal="true" aria-labelledby="checkout-terms-title">
                <div class="flex items-start justify-between gap-4 border-b border-atlantia-rose/15 px-5 py-4">
                    <div>
                        <p class="text-xs font-black uppercase tracking-[0.12em] text-atlantia-rose">Atlantia Supermarket</p>
                        <h2 id="checkout-terms-title" class="mt-1 text-xl font-black text-atlantia-ink">Terminos y condiciones de compra</h2>
                    </div>
                    <button type="button" class="rounded-lg border border-atlantia-rose/30 px-3 py-2 text-sm font-black text-atlantia-wine hover:bg-atlantia-blush" data-checkout-terms-close>
                        Cerrar
                    </button>
                </div>

                <div class="max-h-[62vh] overflow-y-auto px-5 py-4 text-sm leading-6 text-atlantia-ink/70">
                    <section class="rounded-xl border border-atlantia-rose/15 bg-atlantia-blush/25 p-4">
                        <h3 class="font-black text-atlantia-ink">1. Confirmacion del pedido</h3>
                        <p class="mt-1">Al confirmar la compra, autorizas a Atlantia y a los vendedores participantes a preparar, facturar y coordinar la entrega de los productos seleccionados.</p>
                    </section>

                    <section class="mt-3 rounded-xl border border-atlantia-rose/15 p-4">
                        <h3 class="font-black text-atlantia-ink">2. Entrega y cobertura</h3>
                        <p class="mt-1">La direccion y ubicacion GPS se usan para validar cobertura, calcular envio y facilitar la entrega. Los tiempos pueden variar por disponibilidad, clima, trafico o volumen operativo.</p>
                    </section>

                    <section class="mt-3 rounded-xl border border-atlantia-rose/15 p-4">
                        <h3 class="font-black text-atlantia-ink">3. Pagos, cupones y totales</h3>
                        <p class="mt-1">El total puede incluir subtotal, envio, impuestos y descuentos validos. Los cupones se aplican solo si cumplen sus reglas de vigencia, monto minimo, uso y productos participantes.</p>
                    </section>

                    <section class="mt-3 rounded-xl border border-atlantia-rose/15 p-4">
                        <h3 class="font-black text-atlantia-ink">4. Facturacion FEL por vendedor</h3>
                        <p class="mt-1">Cada vendedor emite su propio documento fiscal o comprobante correspondiente. En modo emulado, el comprobante sirve como respaldo interno hasta conectar con un certificador FEL real.</p>
                    </section>

                    <section class="mt-3 rounded-xl border border-atlantia-rose/15 p-4">
                        <h3 class="font-black text-atlantia-ink">5. Cambios, anulaciones y soporte</h3>
                        <p class="mt-1">Si necesitas corregir datos, reportar un problema de entrega o solicitar soporte, Atlantia revisara el caso con base en el estado del pedido y las politicas operativas vigentes.</p>
                    </section>
                </div>

                <div class="flex flex-col gap-3 border-t border-atlantia-rose/15 bg-atlantia-cream px-5 py-4 sm:flex-row sm:justify-end">
                    <button type="button" class="rounded-lg border border-atlantia-rose/35 px-5 py-2.5 text-sm font-black text-atlantia-wine hover:bg-white" data-checkout-terms-close>
                        Volver al checkout
                    </button>
                    <button type="button" class="rounded-lg bg-atlantia-wine px-5 py-2.5 text-sm font-black text-white shadow-lg shadow-atlantia-wine/15 hover:bg-atlantia-wine/90" data-checkout-terms-accept>
                        Entendido
                    </button>
                </div>
            </div>
        </div>
    @endif
</section>
