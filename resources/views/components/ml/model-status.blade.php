@props([
    'model',
])

@php
    $variant = match ($model->estado) {
        'production' => 'success',
        'staging', 'training' => 'warning',
        'archived' => 'neutral',
        default => 'info',
    };
@endphp

<div
    {{ $attributes->merge([
        'class' => 'flex items-center justify-between rounded-lg border border-slate-200 bg-white p-4',
    ]) }}
>
    <div>
        <p class="font-semibold text-slate-950">{{ $model->nombre_modelo }}</p>
        <p class="text-sm text-slate-600">Version {{ $model->version }}</p>
    </div>
    <x-ui.badge :variant="$variant">{{ ucfirst($model->estado) }}</x-ui.badge>
</div>
