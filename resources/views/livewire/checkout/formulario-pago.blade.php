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

    <div
        class="mt-5 overflow-hidden rounded-lg border border-atlantia-wine/20 bg-[#14100f] p-1 shadow-2xl shadow-atlantia-wine/15 {{ $metodoPago === 'tarjeta' ? '' : 'hidden' }}"
        data-stripe-card-panel
    >
        <input type="hidden" name="card_token" data-stripe-payment-method>

        <div class="rounded-md border border-white/10 bg-gradient-to-br from-[#fffaf3] via-white to-[#f7eee7] p-5 sm:p-6">
            <div class="mb-5 flex items-start justify-between gap-4 border-b border-atlantia-wine/10 pb-4">
                <div
                    class="flex h-10 w-10 items-center justify-center rounded-full border border-atlantia-wine/20 bg-atlantia-wine text-sm font-black text-white shadow-lg shadow-atlantia-wine/25"
                    aria-hidden="true"
                >
                    S
                </div>
                <div class="min-w-0 flex-1">
                    <p class="text-xs font-black uppercase tracking-[0.16em] text-atlantia-wine/70">Stripe</p>
                    <h3 class="mt-1 text-lg font-black text-atlantia-ink">Pago seguro con tarjeta</h3>
                </div>
                <span class="rounded-full border border-emerald-600/20 bg-emerald-50 px-3 py-1 text-xs font-black text-emerald-700">
                    Seguro
                </span>
            </div>

            <div class="grid gap-4">
                <div>
                    <label for="card_holder_name" class="text-sm font-black text-atlantia-ink">
                        Nombre del titular de la tarjeta
                    </label>
                    <input
                        id="card_holder_name"
                        type="text"
                        autocomplete="cc-name"
                        data-stripe-cardholder-name
                        required
                        placeholder="Como aparece en tu tarjeta"
                        value="{{ old('razon_social', auth()->user()?->name) }}"
                        class="mt-2 w-full rounded-md border border-atlantia-rose/30 bg-white px-4 py-3 text-sm font-semibold text-atlantia-ink shadow-lg shadow-atlantia-wine/5 focus:border-atlantia-wine focus:ring-atlantia-rose"
                    >
                </div>

                <div
                    class="grid gap-4"
                    data-stripe-card-elements
                >
                    <div>
                        <label for="stripe-card-number-element" class="text-sm font-black text-atlantia-ink">
                            Numero de tarjeta
                        </label>
                        <div
                            id="stripe-card-number-element"
                            data-stripe-card-number-element
                            wire:ignore
                            class="mt-2 rounded-md border border-atlantia-rose/30 bg-white px-4 py-4 shadow-lg shadow-atlantia-wine/5"
                        ></div>
                    </div>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label for="stripe-card-expiry-element" class="text-sm font-black text-atlantia-ink">
                                Fecha de vencimiento
                            </label>
                            <div
                                id="stripe-card-expiry-element"
                                data-stripe-card-expiry-element
                                wire:ignore
                                class="mt-2 rounded-md border border-atlantia-rose/30 bg-white px-4 py-4 shadow-lg shadow-atlantia-wine/5"
                            ></div>
                        </div>

                        <div>
                            <label for="stripe-card-cvc-element" class="text-sm font-black text-atlantia-ink">
                                Codigo CVC
                            </label>
                            <div
                                id="stripe-card-cvc-element"
                                data-stripe-card-cvc-element
                                wire:ignore
                                class="mt-2 rounded-md border border-atlantia-rose/30 bg-white px-4 py-4 shadow-lg shadow-atlantia-wine/5"
                            ></div>
                        </div>
                    </div>
                </div>
            </div>

            <p class="mt-4 hidden rounded-md border border-red-200 bg-red-50 px-4 py-3 text-sm font-semibold text-red-700" data-stripe-payment-errors></p>
            <p class="mt-4 text-xs font-semibold text-atlantia-ink/55">
                Tus datos se cifran y se validan directamente en Stripe antes de confirmar el pedido.
            </p>

            @error('card_token')
                <p class="mt-3 text-sm font-semibold text-red-700">{{ $message }}</p>
            @enderror
        </div>
    </div>

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
