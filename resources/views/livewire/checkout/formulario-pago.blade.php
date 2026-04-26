<section
    class="rounded-lg border border-atlantia-rose/20 bg-white p-5 shadow-sm sm:p-7"
    aria-labelledby="metodo-pago-title"
>
    @php
        $fieldIcon = function (string $field): string {
            return match ($this->fieldState($field)) {
                'valid' => 'text-emerald-600',
                'invalid' => 'text-rose-600',
                default => 'text-slate-300',
            };
        };
    @endphp

    <h2 id="metodo-pago-title" class="flex items-center gap-3 text-2xl font-bold text-atlantia-ink">
        <span
            class="flex h-9 w-9 items-center justify-center rounded-md bg-atlantia-wine text-base text-white"
        >
            4
        </span>
        Metodo de pago
    </h2>
    <p class="mt-2 text-sm text-atlantia-ink/70">
        Elige como deseas pagar este pedido.
    </p>

    @error('metodo_pago')
        <p class="mt-4 rounded-md bg-red-50 px-4 py-3 text-sm font-semibold text-red-700">
            {{ $message }}
        </p>
    @enderror

    <div class="mt-5 grid gap-3">
        @foreach ($metodos as $metodo)
            <label
                class="flex cursor-pointer items-start gap-4 rounded-lg border-2 p-5 transition hover:border-atlantia-wine/60"
                @class([
                    'border-atlantia-wine bg-atlantia-blush' => $metodoPago === $metodo,
                    'border-slate-200 bg-white' => $metodoPago !== $metodo,
                ])
            >
                <input
                    type="radio"
                    name="metodo_pago"
                    value="{{ $metodo }}"
                    wire:click="seleccionarMetodo('{{ $metodo }}')"
                    @checked($metodoPago === $metodo)
                    class="mt-1 border-atlantia-rose text-atlantia-wine focus:ring-atlantia-rose"
                >
                <span>
                    <span class="block font-bold text-atlantia-ink">
                        @if ($metodo === 'efectivo')
                            Efectivo
                        @elseif ($metodo === 'transferencia')
                            Transferencia bancaria
                        @else
                            Tarjeta de credito / debito
                        @endif
                    </span>
                    <span class="mt-1 block text-sm leading-6 text-atlantia-ink/70">
                        @if ($metodo === 'efectivo')
                            Pagas al recibir tu pedido. El repartidor lleva cambio hasta Q 500.
                        @elseif ($metodo === 'transferencia')
                            Un empleado validara tu comprobante antes de confirmar despacho.
                        @else
                            Pago seguro via pasarela. Se procesa al confirmar el pedido.
                        @endif
                    </span>
                </span>
            </label>
        @endforeach
    </div>

    @if ($metodoPago === 'tarjeta')
        <div class="mt-5 rounded-lg border border-atlantia-rose/25 bg-atlantia-cream p-5" data-stripe-card-panel>
            <input type="hidden" name="card_token" data-stripe-payment-method>

            <div class="grid gap-4">
                <div>
                    <label for="card_holder_name" class="text-sm font-bold text-atlantia-ink">
                        Nombre en la tarjeta
                    </label>
                    <input
                        id="card_holder_name"
                        type="text"
                        autocomplete="cc-name"
                        data-stripe-cardholder-name
                        placeholder="Como aparece en tu tarjeta"
                        class="mt-2 w-full rounded-md border border-atlantia-rose/30 px-4 py-3 text-sm
                            focus:border-atlantia-wine focus:ring-atlantia-rose"
                    >
                </div>

                <div>
                    <label for="stripe-card-element" class="text-sm font-bold text-atlantia-ink">
                        Datos de la tarjeta
                    </label>
                    <div
                        id="stripe-card-element"
                        data-stripe-card-element
                        class="mt-2 rounded-md border border-atlantia-rose/30 bg-white px-4 py-3 shadow-sm"
                    ></div>
                    <p class="mt-2 hidden text-sm font-semibold text-red-700" data-stripe-card-errors></p>
                </div>
            </div>

            <p class="mt-4 text-xs text-atlantia-ink/60">
                Los datos de tarjeta se tokenizan directamente con Stripe antes de enviar el pedido.
            </p>

            @error('card_token')
                <p class="mt-3 text-sm font-semibold text-red-700">{{ $message }}</p>
            @enderror
        </div>
    @endif

    @if ($metodoPago === 'transferencia')
        <div class="mt-4">
            <label for="referencia_bancaria" class="block text-sm font-bold text-atlantia-ink">
                Referencia de transferencia
            </label>
            <div class="relative">
                <input
                    id="referencia_bancaria"
                    name="referencia_bancaria"
                    type="text"
                    wire:model.live.debounce.250ms="referenciaTransferencia"
                    class="mt-2 w-full rounded-md border border-atlantia-rose/30 px-4 py-3 pr-11 text-sm focus:border-atlantia-wine focus:ring-atlantia-rose"
                >
                <span class="absolute inset-y-0 right-3 top-2 flex items-center {{ $fieldIcon('referenciaTransferencia') }}" aria-hidden="true">
                    {!! $this->fieldState('referenciaTransferencia') === 'valid' ? '&#10003;' : ($this->fieldState('referenciaTransferencia') === 'invalid' ? '&#10005;' : '&bull;') !!}
                </span>
            </div>
            @error('referenciaTransferencia')
                <p class="mt-2 text-sm font-semibold text-red-700">{{ $message }}</p>
            @enderror
        </div>
    @endif
</section>
