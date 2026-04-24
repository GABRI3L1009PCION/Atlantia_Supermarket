@props([
    'variant' => 'neutral',
])

@php
    $classes = [
        'success' => 'bg-emerald-100 text-emerald-800',
        'warning' => 'bg-amber-100 text-amber-900',
        'danger' => 'bg-red-100 text-red-800',
        'info' => 'bg-sky-100 text-sky-800',
        'neutral' => 'bg-slate-100 text-slate-700',
    ][$variant] ?? 'bg-slate-100 text-slate-700';
@endphp

<span
    {{ $attributes->merge([
        'class' => 'inline-flex items-center rounded-md px-2 py-1 text-xs font-semibold ' . $classes,
    ]) }}
>
    {{ $slot }}
</span>
