@props([
    'prediction',
])

<x-ui.card title="Prediccion de demanda" description="Horizonte de {{ $prediction->horizonte_dias }} dias">
    <div class="flex items-end justify-between gap-4">
        <div>
            <p class="text-3xl font-bold text-slate-950">{{ number_format((float) $prediction->valor_predicho, 0) }}</p>
            <p class="mt-1 text-sm text-slate-600">Unidades estimadas</p>
        </div>
        <x-ui.badge variant="info">{{ $prediction->fecha_prediccion?->format('d/m/Y') }}</x-ui.badge>
    </div>
</x-ui.card>
