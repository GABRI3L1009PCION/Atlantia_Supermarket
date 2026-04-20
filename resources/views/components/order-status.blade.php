@props([
    'estado',
])

@php
    $variant = match ($estado) {
        'entregado', 'completado' => 'success',
        'cancelado', 'rechazado' => 'danger',
        'en_ruta', 'preparando' => 'info',
        default => 'warning',
    };
@endphp

<x-ui.badge :variant="$variant">{{ ucfirst(str_replace('_', ' ', $estado)) }}</x-ui.badge>
