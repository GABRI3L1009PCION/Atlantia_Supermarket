@props([
    'variant' => 'neutral',
])

@php
    $classes = [
        'success' => 'bg-emerald-100 text-emerald-800',
        'warning' => 'bg-amber-100 text-amber-900',
        'danger' => 'bg-red-100 text-red-800',
        'info' => 'bg-atlantia-frost text-atlantia-deep ring-1 ring-atlantia-cyan/30',
        'neutral' => 'bg-white/90 text-atlantia-deep ring-1 ring-atlantia-cyan/20',
    ][$variant] ?? 'bg-white/90 text-atlantia-deep ring-1 ring-atlantia-cyan/20';
@endphp

<span
    {{ $attributes->merge([
        'class' => 'inline-flex items-center rounded-md px-2 py-1 text-xs font-semibold ' . $classes,
    ]) }}
>
    {{ $slot }}
</span>
