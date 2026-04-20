@props([
    'type' => 'button',
    'variant' => 'primary',
])

@php
    $classes = [
        'primary' => 'bg-emerald-700 text-white hover:bg-emerald-800 focus:ring-emerald-600',
        'secondary' => 'bg-white text-slate-800 ring-1 ring-slate-300 hover:bg-slate-50 focus:ring-slate-500',
        'danger' => 'bg-red-700 text-white hover:bg-red-800 focus:ring-red-600',
        'ghost' => 'bg-transparent text-slate-700 hover:bg-slate-100 focus:ring-slate-500',
    ][$variant] ?? 'bg-emerald-700 text-white hover:bg-emerald-800 focus:ring-emerald-600';
@endphp

<button
    type="{{ $type }}"
    {{ $attributes->merge([
        'class' => 'inline-flex items-center justify-center rounded-md px-4 py-2 text-sm font-semibold transition ' .
            'focus:outline-none focus:ring-2 focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-60 ' .
            $classes,
    ]) }}
>
    {{ $slot }}
</button>
