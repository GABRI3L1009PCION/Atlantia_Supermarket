{{-- Detalle venta por venta de una solicitud de pago. Recibe $liquidacion y $servicio. --}}
<div class="grid gap-3 sm:grid-cols-4">
    @foreach ([
        ['Ventas', 'Q '.number_format((float) $liquidacion->monto_bruto, 2), 'text-atlantia-ink'],
        ['Comisión plan', '− Q '.number_format((float) $liquidacion->comision_plataforma, 2), 'text-atlantia-ink/70'],
        ['Tarifa pasarela', '− Q '.number_format((float) $liquidacion->comision_pasarela, 2), 'text-atlantia-ink/70'],
        ['Total a recibir', 'Q '.number_format((float) $liquidacion->monto_neto, 2), 'text-atlantia-wine'],
    ] as [$etiqueta, $valor, $color])
        <div class="rounded-xl border border-atlantia-rose/15 bg-white p-4">
            <p class="text-xs font-black uppercase tracking-wide text-atlantia-ink/50">{{ $etiqueta }}</p>
            <p class="mt-1 text-xl font-black {{ $color }}">{{ $valor }}</p>
        </div>
    @endforeach
</div>

<div class="mt-4 overflow-x-auto rounded-2xl border border-atlantia-rose/15 bg-white">
    <table class="w-full min-w-[720px] text-left text-sm">
        <thead class="bg-atlantia-blush/40 text-xs uppercase text-atlantia-ink/60">
            <tr>
                <th class="px-4 py-2.5">Pedido</th>
                <th class="px-4 py-2.5">Pago</th>
                <th class="px-4 py-2.5 text-right">Venta</th>
                <th class="px-4 py-2.5 text-right">Comisión plan</th>
                <th class="px-4 py-2.5 text-right">Tarifa pasarela</th>
                <th class="px-4 py-2.5 text-right">Recibe</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($liquidacion->splits as $venta)
                <tr class="border-t border-atlantia-rose/10 align-top">
                    <td class="px-4 py-3">
                        <p class="font-bold text-atlantia-ink">{{ $venta->pedido?->numero_pedido ?? '—' }}</p>
                        <p class="text-xs text-atlantia-ink/50">{{ $venta->pedido?->updated_at?->format('d/m/Y') }}</p>
                    </td>
                    <td class="px-4 py-3 text-xs font-semibold">{{ ucfirst((string) ($venta->payment?->metodo?->value ?? $venta->payment?->metodo)) }}</td>
                    <td class="px-4 py-3 text-right font-semibold">Q {{ number_format((float) $venta->monto_bruto, 2) }}</td>
                    <td class="px-4 py-3 text-right text-atlantia-ink/70">− Q {{ number_format((float) $venta->comision_atlantia, 2) }}</td>
                    <td class="px-4 py-3 text-right text-atlantia-ink/70">
                        − Q {{ number_format((float) $venta->comision_pasarela, 2) }}
                        <span class="block text-[11px] text-atlantia-ink/45">{{ $servicio->detalleTarifa($venta) }}</span>
                    </td>
                    <td class="px-4 py-3 text-right font-black text-atlantia-wine">Q {{ number_format((float) $venta->monto_neto_vendedor, 2) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
