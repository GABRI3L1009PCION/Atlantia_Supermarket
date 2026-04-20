@props([
    'estado',
])

@php
    $variant = match ($estado) {
        'certificado' => 'success',
        'anulado', 'rechazado' => 'danger',
        'contingencia' => 'warning',
        default => 'info',
    };
@endphp

<x-ui.badge :variant="$variant">{{ ucfirst($estado) }}</x-ui.badge>
