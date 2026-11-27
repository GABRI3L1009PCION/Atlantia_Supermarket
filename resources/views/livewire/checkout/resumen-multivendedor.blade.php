<section
    class="rounded-xl border border-atlantia-rose/20 bg-white p-4 shadow-xl shadow-atlantia-wine/10"
    aria-labelledby="resumen-checkout-title"
>
    <h2 id="resumen-checkout-title" class="text-lg font-black leading-tight text-atlantia-ink max-lg:sr-only">
        Resumen del pedido
    </h2>
    <p class="mt-1 text-[11px] leading-4 text-atlantia-ink/65">
        Revisa el total antes de confirmar. Cada vendedor emitirá su propia factura electrónica.
    </p>

    @if ($grupos->isEmpty())
        <div class="mt-4">
            <x-ui.empty-state
                title="No hay productos para pagar"
                message="Agrega productos al carrito antes de finalizar tu compra."
            />
        </div>
    @else
        @if ($totalEntregas > 1)
            <p class="mt-3 flex gap-2 rounded-lg bg-amber-50 px-3 py-2 text-xs font-semibold leading-4 text-amber-900" data-aviso-entregas>
                <svg class="h-4 w-4 shrink-0" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M3 15h12V6H3v9Zm12-6h3l3 3v3h-6V9ZM7 19a2 2 0 1 0 0-4 2 2 0 0 0 0 4Zm11 0a2 2 0 1 0 0-4 2 2 0 0 0 0 4Z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/></svg>
                Recibirás {{ $totalEntregas }} entregas: cada tienda envía lo suyo con su propio repartidor.
            </p>
        @endif
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
                                    {{ $grupo['vendor']?->business_name ?? 'Atlantia Store' }}
                                </h3>
                                <p class="text-xs font-semibold text-sky-700">Factura electrónica FEL</p>
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
                    @if ($totalEntregas > 0)
                        <p class="mt-2 flex justify-between gap-3 border-t border-dashed border-atlantia-rose/20 pt-2 text-xs" data-envio-tienda>
                            <span class="font-semibold text-atlantia-ink/65">🛵 Envío de esta tienda</span>
                            @if ($grupo['envio'] === null)
                                <span class="italic text-atlantia-ink/50">Con tu dirección</span>
                            @elseif ((float) $grupo['envio'] <= 0)
                                <span class="font-bold text-emerald-700">Gratis</span>
                            @else
                                <span class="font-bold text-atlantia-ink">Q {{ number_format((float) $grupo['envio'], 2) }}</span>
                            @endif
                        </p>
                    @endif
                </article>
            @endforeach
        </div>

        <dl class="mt-3 space-y-2 border-t border-slate-200 pt-3 text-xs">
            <div class="flex justify-between">
                <dt class="text-atlantia-ink/65">Subtotal</dt>
                <dd class="font-semibold text-atlantia-ink">Q {{ number_format($subtotal, 2) }}</dd>
            </div>
            <div class="flex justify-between">
                <dt class="text-atlantia-ink/65">Envío{{ $totalEntregas > 1 ? ' ('.$totalEntregas.' entregas)' : '' }} @if ($envioGratisMembresia)<span class="ml-1 rounded bg-emerald-100 px-1.5 py-0.5 text-[10px] font-black text-emerald-800">Membresía</span>@endif</dt>
                @if ($envioPendiente)
                    <dd class="text-right italic text-atlantia-ink/55" data-checkout-shipping="0" data-envio-pendiente>Se calcula con tu dirección</dd>
                @else
                    <dd class="font-semibold text-atlantia-ink" data-checkout-shipping="{{ number_format($envio, 2, '.', '') }}">Q {{ number_format($envio, 2) }}</dd>
                @endif
            </div>
            <div class="flex justify-between">
                <dt class="text-atlantia-ink/65">IVA incluido</dt>
                <dd class="font-semibold text-atlantia-ink">Q {{ number_format($impuestos, 2) }}</dd>
            </div>
            @foreach ($promociones as $promocion)
                <div class="flex justify-between gap-3" data-checkout-promocion>
                    <dt class="min-w-0 truncate text-emerald-700"><span class="mr-1 rounded bg-emerald-100 px-1.5 py-0.5 text-[10px] font-black">{{ $promocion['etiqueta'] }}</span>{{ $promocion['nombre'] }}</dt>
                    <dd class="shrink-0 font-semibold text-emerald-700">- Q {{ number_format($promocion['descuento'], 2) }}</dd>
                </div>
            @endforeach
            @if ($descuentoPrimeraCompra > 0)
                <div class="flex justify-between" data-checkout-primera-compra>
                    <dt class="text-emerald-700">Descuento primera compra</dt>
                    <dd class="font-semibold text-emerald-700">- Q {{ number_format($descuentoPrimeraCompra, 2) }}</dd>
                </div>
            @endif
            @if ($descuento > 0)
                <div class="flex justify-between">
                    <dt class="text-emerald-700">Descuento por cupón</dt>
                    <dd class="font-semibold text-emerald-700">- Q {{ number_format($descuento, 2) }}</dd>
                </div>
            @endif
            @if ($propina > 0)
                <div class="flex justify-between">
                    <dt class="text-atlantia-ink/65">Propina repartidor</dt>
                    <dd class="font-semibold text-atlantia-ink" data-checkout-propina>Q {{ number_format($propina, 2) }}</dd>
                </div>
            @endif
            @if ($saldoUsado > 0)
                <div class="flex justify-between">
                    <dt class="text-emerald-700">Saldo a favor</dt>
                    <dd class="font-semibold text-emerald-700" data-checkout-saldo>- Q {{ number_format($saldoUsado, 2) }}</dd>
                </div>
            @endif
            <div class="flex justify-between border-t border-atlantia-ink pt-2 text-sm">
                <dt>
                    <span class="block font-bold text-atlantia-ink">Total a pagar</span>
                    <span class="text-xs text-atlantia-ink/55">IVA incluido{{ $envioPendiente ? ' · más envío' : '' }}</span>
                </dt>
                <dd class="text-2xl font-black leading-tight text-atlantia-wine" data-checkout-total="{{ number_format($totalAPagar, 2, '.', '') }}">Q {{ number_format($totalAPagar, 2) }}</dd>
            </div>
        </dl>

        <div class="mt-3">
            <div class="flex gap-2">
                <input
                    type="text"
                    wire:model.live.debounce.400ms="couponCode"
                    placeholder="¿Tienes un cupón?"
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
        <input type="hidden" name="metodo_pago_resumen" value="{{ $metodoPago }}" data-checkout-payment-summary>
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
                <a href="{{ route('legal.terminos', ['from' => 'checkout']) }}" class="font-bold text-atlantia-wine underline decoration-atlantia-wine/40 underline-offset-2 hover:text-atlantia-wine-700">Términos y Condiciones</a>
                y la
                <a href="{{ route('legal.privacidad', ['from' => 'checkout']) }}" class="font-bold text-atlantia-wine underline decoration-atlantia-wine/40 underline-offset-2 hover:text-atlantia-wine-700">Política de Privacidad</a>
                de Atlantia Store.
            </span>
        </label>

        <p class="mt-2 hidden text-sm font-semibold text-red-700" data-terms-error>Marca la casilla para aceptar las condiciones de compra.</p>

        @error('acepta_terminos_checkout')
            <p class="mt-2 text-sm font-semibold text-red-700">{{ $message }}</p>
        @enderror

        @error('aceptaTerminos')
            <p class="mt-2 text-sm font-semibold text-red-700">{{ $message }}</p>
        @enderror

        <p class="mt-3 rounded-lg bg-atlantia-blush/55 px-3 py-2 text-center text-[11px] font-semibold text-atlantia-ink/55">
            Pago seguro - Cifrado SSL - FEL certificado SAT
        </p>
    @endif
</section>
