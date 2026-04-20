<section class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm" aria-labelledby="metodo-pago-title">
    <h2 id="metodo-pago-title" class="text-lg font-semibold text-slate-950">Metodo de pago</h2>
    <p class="mt-1 text-sm text-slate-600">Elige como deseas pagar este pedido.</p>

    <div class="mt-4 grid gap-3">
        @foreach ($metodos as $metodo)
            <label
                class="flex cursor-pointer items-start gap-3 rounded-lg border p-4"
                @class([
                    'border-emerald-600 bg-emerald-50' => $metodoPago === $metodo,
                    'border-slate-200 bg-white' => $metodoPago !== $metodo,
                ])
            >
                <input
                    type="radio"
                    name="metodo_pago"
                    value="{{ $metodo }}"
                    wire:click="seleccionarMetodo('{{ $metodo }}')"
                    @checked($metodoPago === $metodo)
                    class="mt-1"
                >
                <span>
                    <span class="block font-semibold text-slate-950">
                        {{ ucfirst($metodo) }}
                    </span>
                    <span class="block text-sm text-slate-600">
                        @if ($metodo === 'efectivo')
                            Pagas al recibir tu pedido.
                        @elseif ($metodo === 'transferencia')
                            Un empleado validara tu comprobante antes de confirmar despacho.
                        @else
                            Pago por pasarela con contrato intercambiable.
                        @endif
                    </span>
                </span>
            </label>
        @endforeach
    </div>

    @if ($metodoPago === 'transferencia')
        <div class="mt-4">
            <x-ui.input
                label="Referencia de transferencia"
                name="referencia_transferencia"
                wire:model.live="referenciaTransferencia"
            />
        </div>
    @endif

    <label class="mt-4 flex items-start gap-3 text-sm text-slate-700">
        <input type="checkbox" name="acepta_terminos" wire:model.live="aceptaTerminos" class="mt-1">
        <span>Acepto las condiciones de compra, entrega y facturacion FEL por vendedor.</span>
    </label>

    @error('aceptaTerminos')
        <p class="mt-2 text-sm text-red-700">{{ $message }}</p>
    @enderror
</section>
