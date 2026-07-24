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
                    wire:model.live="metodoPago"
                    @checked($metodoPago === $metodo)
                    class="mt-1 border-atlantia-rose text-atlantia-wine focus:ring-atlantia-rose"
                >
                    <span>
                    <span class="block font-bold text-atlantia-ink">
                        @if ($metodo === 'efectivo')
                            Efectivo
                        @elseif ($metodo === 'transferencia')
                            Transferencia bancaria al entregar
                        @else
                            Tarjeta con POS al entregar
                        @endif
                    </span>
                    <span class="mt-1 block text-sm leading-6 text-atlantia-ink/70">
                        @if ($metodo === 'efectivo')
                            Pagas cuando llegue el repartidor. Si necesitas cambio, indicanos para que billete.
                        @elseif ($metodo === 'transferencia')
                            La transferencia se realiza al momento de la entrega y el repartidor valida el comprobante.
                        @else
                            El repartidor llevara terminal POS para cobrar con tarjeta en la entrega.
                        @endif
                    </span>
                </span>
            </label>
        @endforeach
    </div>

    @if ($metodoPago === 'tarjeta')
        <div class="mt-4 rounded-lg border border-atlantia-rose/25 bg-atlantia-blush/35 p-4">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <p class="text-sm font-bold text-atlantia-ink">Cobro con POS en la entrega</p>
                    <p class="mt-1 text-xs leading-5 text-atlantia-ink/65">
                        El repartidor llevara un POS {{ config('atlantia.payments.pos.provider', 'bancario') }} para cobrar con tarjeta cuando llegue con el pedido.
                    </p>
                </div>
                <span class="rounded-full border border-emerald-500/25 bg-emerald-50 px-3 py-1 text-xs font-black text-emerald-700">
                    Cobro presencial
                </span>
            </div>
            @if (config('atlantia.payments.pos.support_phone'))
                <p class="mt-3 text-xs font-semibold text-atlantia-ink/60">
                    Soporte POS: {{ config('atlantia.payments.pos.support_phone') }}
                </p>
            @endif
        </div>
    @endif

    @if ($metodoPago === 'transferencia')
        <div class="mt-4 rounded-lg border border-atlantia-rose/25 bg-atlantia-blush/35 p-4">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <label for="referencia_bancaria" class="block text-sm font-bold text-atlantia-ink">
                        Referencia de transferencia <span class="font-normal text-atlantia-ink/45">(opcional)</span>
                    </label>
                    <p class="mt-1 text-xs leading-5 text-atlantia-ink/60">
                        Si ya la tienes, escribe el numero de boleta o los ultimos digitos. Si no, puedes completarla al momento de la entrega.
                    </p>
                </div>
                <span class="rounded-full border border-amber-500/25 bg-amber-50 px-3 py-1 text-xs font-black text-amber-700">
                    Cobro al entregar
                </span>
            </div>
            <div class="mt-3 rounded-lg border border-white/70 bg-white/80 px-4 py-3 text-xs leading-5 text-atlantia-ink/70">
                <p><span class="font-bold text-atlantia-ink">Banco:</span> {{ config('atlantia.payments.transfer.bank_name', 'Pendiente de configurar') }}</p>
                <p><span class="font-bold text-atlantia-ink">Cuenta:</span> {{ config('atlantia.payments.transfer.account_number', 'Pendiente de configurar') }}</p>
                <p><span class="font-bold text-atlantia-ink">Titular:</span> {{ config('atlantia.payments.transfer.account_name', 'Pendiente de configurar') }}</p>
            </div>
            <div class="relative mt-3">
                <input
                    id="referencia_bancaria"
                    name="referencia_bancaria"
                    type="text"
                    wire:model.live.debounce.250ms="referenciaTransferencia"
                    autocomplete="off"
                    placeholder="Ej. BANRURAL-8842 o transferencia 123456"
                    class="w-full rounded-md border border-atlantia-rose/30 bg-white px-4 py-3 pr-11 text-sm font-semibold text-atlantia-ink focus:border-atlantia-wine focus:ring-atlantia-rose"
                >
                <span class="absolute inset-y-0 right-3 flex items-center {{ $fieldIcon('referenciaTransferencia') }}" aria-hidden="true">
                    {!! $this->fieldState('referenciaTransferencia') === 'valid' ? '&#10003;' : ($this->fieldState('referenciaTransferencia') === 'invalid' ? '&#10005;' : '&bull;') !!}
                </span>
            </div>
            @error('referenciaTransferencia')
                <p class="mt-2 text-sm font-semibold text-red-700">{{ $message }}</p>
            @enderror
            @error('referencia_bancaria')
                <p class="mt-2 text-sm font-semibold text-red-700">{{ $message }}</p>
            @enderror
        </div>
    @endif

    @if ($metodoPago === 'efectivo')
        <div class="mt-4 rounded-lg border border-atlantia-rose/25 bg-atlantia-blush/35 p-4">
            <label class="flex items-start gap-3">
                <input
                    type="checkbox"
                    name="solicita_cambio"
                    value="1"
                    wire:model.live="solicitaCambio"
                    class="mt-1 rounded border-atlantia-rose text-atlantia-wine focus:ring-atlantia-rose"
                >
                <span>
                    <span class="block text-sm font-bold text-atlantia-ink">Necesito cambio</span>
                    <span class="block text-xs leading-5 text-atlantia-ink/60">
                        Indica para que billete necesitas cambio. Monto recomendado hasta Q {{ number_format((float) config('atlantia.payments.cash.max_change_bill', 500), 2) }}.
                    </span>
                </span>
            </label>

            @if ($solicitaCambio)
                <div class="relative mt-4">
                    <label for="cambio_para" class="block text-sm font-bold text-atlantia-ink">
                        Necesito cambio para
                    </label>
                    <input
                        id="cambio_para"
                        name="cambio_para"
                        type="number"
                        min="1"
                        step="0.01"
                        wire:model.live.debounce.250ms="cambioPara"
                        placeholder="Ej. 200.00"
                        class="mt-2 w-full rounded-md border border-atlantia-rose/30 bg-white px-4 py-3 text-sm font-semibold text-atlantia-ink focus:border-atlantia-wine focus:ring-atlantia-rose"
                    >
                    @error('cambioPara')
                        <p class="mt-2 text-sm font-semibold text-red-700">{{ $message }}</p>
                    @enderror
                    @error('cambio_para')
                        <p class="mt-2 text-sm font-semibold text-red-700">{{ $message }}</p>
                    @enderror
                </div>
            @endif
        </div>
    @endif
</section>
