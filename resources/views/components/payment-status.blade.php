@props([
    'estado',
])

@php
    $estado = $estado instanceof \BackedEnum ? $estado->value : $estado;
    $variant = match ($estado) {
        'pagado', 'aprobado' => 'success',
        'rechazado', 'fallido' => 'danger',
        'pendiente_revision' => 'warning',
        default => 'info',
    };
@endphp

<x-ui.badge :variant="$variant">{{ ucfirst(str_replace('_', ' ', $estado)) }}</x-ui.badge>
