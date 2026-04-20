@props([
    'subtotal' => 0,
    'envio' => 0,
    'total' => 0,
])

<section {{ $attributes->merge(['class' => 'rounded-lg border border-slate-200 bg-white p-5 shadow-sm']) }}>
    <h2 class="text-lg font-semibold text-slate-950">Resumen de compra</h2>

    <dl class="mt-4 space-y-3 text-sm">
        <div class="flex justify-between">
            <dt class="text-slate-600">Subtotal</dt>
            <dd class="font-medium text-slate-950">Q {{ number_format((float) $subtotal, 2) }}</dd>
        </div>
        <div class="flex justify-between">
            <dt class="text-slate-600">Envio</dt>
            <dd class="font-medium text-slate-950">Q {{ number_format((float) $envio, 2) }}</dd>
        </div>
        <div class="border-t border-slate-200 pt-3">
            <div class="flex justify-between text-base">
                <dt class="font-semibold text-slate-950">Total</dt>
                <dd class="font-bold text-atlantia-wine">Q {{ number_format((float) $total, 2) }}</dd>
            </div>
        </div>
    </dl>
</section>
