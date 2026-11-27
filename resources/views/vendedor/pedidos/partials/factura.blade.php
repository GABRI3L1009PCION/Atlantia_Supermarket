@php
    $perfilFiscal = $pedido->vendor?->fiscalProfile;
    $modoFactura = $perfilFiscal?->modoFacturacion() ?? 'manual_sat';
    $facturaPedido = $pedido->dteFacturas()->where('estado', 'certificado')->latest('id')->first();
    $datosFactura = \App\Services\Fel\FacturaManualService::datosCliente($pedido);
    $bolsaFactura = $errors->getBag('factura');
    $campoFactura = 'mt-1 h-10 w-full rounded-lg border border-atlantia-rose/30 bg-white px-3 text-sm text-atlantia-ink outline-none focus:border-atlantia-wine focus:ring-2 focus:ring-atlantia-blush';
    $textoTodo = collect($datosFactura)->map(fn ($v, $k) => $k.': '.$v)->implode("\n")
        ."\n\nProductos:\n".$pedido->items->map(fn ($i) => (float) $i->cantidad.' x '.$i->producto_nombre_snapshot.' - Q'.number_format((float) $i->subtotal - (float) $i->descuento, 2))->implode("\n");
@endphp

<article class="rounded-xl border border-atlantia-rose/15 bg-white p-4" data-factura-pedido>
    <div class="flex items-center justify-between gap-2">
        <h2 class="text-sm font-black text-atlantia-ink">Factura</h2>
        <a href="{{ route('pedidos.comprobante', $pedido) }}" target="_blank" rel="noopener" class="text-xs font-bold text-atlantia-wine hover:underline">Ver comprobante</a>
    </div>

    @if ($facturaPedido)
        <div class="mt-2 rounded-lg bg-emerald-50 px-3 py-2 text-sm text-emerald-900" data-factura-registrada>
            <p class="font-black">Factura {{ $facturaPedido->serie ? $facturaPedido->serie.'-' : '' }}{{ $facturaPedido->numero ?? $facturaPedido->numero_dte }}</p>
            @if ($facturaPedido->uuid_sat)
                <p class="break-all font-mono text-[11px]">{{ $facturaPedido->uuid_sat }}</p>
            @endif
            <p class="text-xs">Q{{ number_format((float) $facturaPedido->monto_total, 2) }} · {{ $facturaPedido->fecha_certificacion?->format('d/m/Y') }}{{ $facturaPedido->origen === 'manual' ? ' · registrada por ti' : '' }}</p>
        </div>
        <div class="mt-2 flex flex-wrap gap-2">
            @if ($facturaPedido->pdf_path)
                <a href="{{ route('dte.pdf', $facturaPedido) }}" target="_blank" rel="noopener" class="h-9 rounded-lg border border-atlantia-rose/25 px-3 text-xs font-bold leading-9 text-atlantia-wine hover:bg-atlantia-blush">Ver PDF</a>
            @endif
            @if ($facturaPedido->origen === 'manual')
                <form method="POST" action="{{ route('vendedor.pedidos.factura.destroy', $pedido) }}" data-confirm="¿Quitar esta factura del pedido? Hazlo solo si registraste un dato mal; después puedes registrarla de nuevo.">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="h-9 rounded-lg px-3 text-xs font-bold text-atlantia-ink/55 hover:text-rose-700 hover:underline">Corregir</button>
                </form>
            @endif
        </div>
    @else
        <p class="mt-1 text-xs text-atlantia-ink/60">
            {{ $modoFactura === 'manual_sat' ? 'Emite la factura en la SAT con estos datos y regístrala aquí.' : 'Si tu factura no se emitió sola, puedes emitirla en la SAT y registrarla aquí.' }}
        </p>

        {{-- Datos del cliente para copiar en la SAT --}}
        <dl class="mt-3 divide-y divide-atlantia-rose/10 rounded-lg border border-atlantia-rose/15 text-sm" data-datos-factura>
            @foreach ($datosFactura as $etiqueta => $valor)
                <div class="flex items-center justify-between gap-2 px-3 py-1.5">
                    <div class="min-w-0">
                        <dt class="text-[11px] font-bold text-atlantia-ink/50">{{ $etiqueta }}</dt>
                        <dd class="truncate font-semibold text-atlantia-ink">{{ $valor !== '' ? $valor : '—' }}</dd>
                    </div>
                    @if ($valor !== '')
                        <button type="button" class="shrink-0 rounded-md px-2 py-1 text-[11px] font-bold text-atlantia-wine hover:bg-atlantia-blush" data-copiar="{{ $valor }}" aria-label="Copiar {{ $etiqueta }}">Copiar</button>
                    @endif
                </div>
            @endforeach
        </dl>
        <button type="button" class="mt-2 w-full rounded-lg border border-atlantia-rose/25 px-3 py-2 text-xs font-bold text-atlantia-wine hover:bg-atlantia-blush" data-copiar="{{ $textoTodo }}">Copiar todo (datos y productos)</button>

        {{-- Registrar la factura emitida --}}
        <details class="mt-3 rounded-lg border border-atlantia-rose/15" @if ($bolsaFactura->any()) open @endif>
            <summary class="cursor-pointer list-none px-3 py-2 text-sm font-black text-atlantia-wine [&::-webkit-details-marker]:hidden">Registrar factura emitida</summary>
            <form method="POST" action="{{ route('vendedor.pedidos.factura.store', $pedido) }}" enctype="multipart/form-data" class="space-y-2 border-t border-atlantia-rose/10 px-3 py-3">
                @csrf
                <div class="grid grid-cols-2 gap-2">
                    <label class="block">
                        <span class="text-[11px] font-bold text-atlantia-ink/70">Serie</span>
                        <input name="serie" value="{{ old('serie') }}" required maxlength="40" placeholder="8F2A1C3D" class="{{ $campoFactura }} uppercase">
                    </label>
                    <label class="block">
                        <span class="text-[11px] font-bold text-atlantia-ink/70">Número</span>
                        <input name="numero" value="{{ old('numero') }}" required maxlength="20" inputmode="numeric" placeholder="1234567890" class="{{ $campoFactura }}">
                    </label>
                </div>
                <label class="block">
                    <span class="text-[11px] font-bold text-atlantia-ink/70">Número de autorización (UUID de la SAT)</span>
                    <input name="autorizacion" value="{{ old('autorizacion') }}" required maxlength="36" placeholder="XXXXXXXX-XXXX-XXXX-XXXX-XXXXXXXXXXXX" class="{{ $campoFactura }} font-mono text-xs uppercase">
                </label>
                <div class="grid grid-cols-2 gap-2">
                    <label class="block">
                        <span class="text-[11px] font-bold text-atlantia-ink/70">Fecha de emisión</span>
                        <input type="date" name="fecha" value="{{ old('fecha', now()->toDateString()) }}" max="{{ now()->toDateString() }}" required class="{{ $campoFactura }}">
                    </label>
                    <label class="block">
                        <span class="text-[11px] font-bold text-atlantia-ink/70">Total facturado (Q)</span>
                        <input type="number" name="monto_total" step="0.01" min="0.01" value="{{ old('monto_total', \App\Services\Fel\FacturaManualService::montoSugerido($pedido)) }}" required class="{{ $campoFactura }}">
                    </label>
                </div>
                <label class="block">
                    <span class="text-[11px] font-bold text-atlantia-ink/70">PDF de la factura (opcional, recomendado)</span>
                    <input type="file" name="pdf" accept="application/pdf" class="mt-1 block w-full text-xs text-atlantia-ink/70 file:mr-2 file:rounded-md file:border-0 file:bg-atlantia-blush file:px-3 file:py-1.5 file:text-xs file:font-bold file:text-atlantia-wine">
                </label>
                @if ($bolsaFactura->any())
                    <ul class="space-y-0.5 text-xs font-bold text-red-600">
                        @foreach ($bolsaFactura->all() as $mensaje)<li>{{ $mensaje }}</li>@endforeach
                    </ul>
                @endif
                <button type="submit" class="h-10 w-full rounded-lg bg-atlantia-wine text-sm font-bold text-white hover:bg-atlantia-wine-700">Guardar factura</button>
            </form>
        </details>
    @endif
</article>

@once
    <script nonce="{{ request()->attributes->get('csp_nonce') }}">
        document.addEventListener('click', async (evento) => {
            const boton = evento.target.closest('[data-copiar]');
            if (!boton) return;
            const texto = boton.getAttribute('data-copiar');
            try {
                await navigator.clipboard.writeText(texto);
            } catch (e) {
                const area = document.createElement('textarea');
                area.value = texto;
                document.body.appendChild(area);
                area.select();
                document.execCommand('copy');
                area.remove();
            }
            const original = boton.textContent;
            boton.textContent = '¡Copiado!';
            setTimeout(() => { boton.textContent = original; }, 1500);
        });
    </script>
@endonce
