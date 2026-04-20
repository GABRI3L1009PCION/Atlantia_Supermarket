@props([
    'score',
])

@php
    $variant = $score >= 0.8 ? 'danger' : ($score >= 0.5 ? 'warning' : 'success');
@endphp

<x-ui.badge :variant="$variant">
    Riesgo {{ number_format((float) $score * 100, 1) }}%
</x-ui.badge>
