@props([
    'score',
])

@php
    $variant = $score >= 0.7 ? 'danger' : ($score >= 0.4 ? 'warning' : 'success');
@endphp

<div {{ $attributes->merge(['class' => 'rounded-lg border border-slate-200 bg-white p-4']) }}>
    <div class="flex items-center justify-between">
        <p class="text-sm font-medium text-slate-700">Drift del modelo</p>
        <x-ui.badge :variant="$variant">{{ number_format((float) $score, 2) }}</x-ui.badge>
    </div>
    <div class="mt-3 h-2 rounded-full bg-slate-100">
        <div class="h-2 rounded-full bg-emerald-600" style="width: {{ min((float) $score * 100, 100) }}%"></div>
    </div>
</div>
