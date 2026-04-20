@props([
    'suggestion',
])

@php
    $variant = match ($suggestion->urgencia) {
        'alta', 'critica' => 'danger',
        'media' => 'warning',
        default => 'info',
    };
@endphp

<x-ui.card>
    <div class="flex items-start justify-between gap-4">
        <div>
            <h3 class="font-semibold text-slate-950">{{ $suggestion->producto?->nombre }}</h3>
            <p class="mt-1 text-sm text-slate-600">
                Stock sugerido: {{ $suggestion->stock_sugerido }} unidades
            </p>
        </div>
        <x-ui.badge :variant="$variant">{{ ucfirst($suggestion->urgencia) }}</x-ui.badge>
    </div>
</x-ui.card>
