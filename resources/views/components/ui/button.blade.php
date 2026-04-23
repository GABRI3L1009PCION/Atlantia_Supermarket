@props([
    'type' => 'button',
    'variant' => 'primary',
])

@php
    $classes = [
        'primary' => 'bg-atlantia-cyan-700 text-white hover:bg-atlantia-deep focus:ring-atlantia-cyan',
        'secondary' => 'bg-white/90 text-atlantia-deep ring-1 ring-atlantia-cyan/35 hover:bg-atlantia-frost focus:ring-atlantia-cyan',
        'danger' => 'bg-red-700 text-white hover:bg-red-800 focus:ring-red-600',
        'ghost' => 'bg-transparent text-atlantia-deep hover:bg-atlantia-frost focus:ring-atlantia-cyan',
    ][$variant] ?? 'bg-atlantia-cyan-700 text-white hover:bg-atlantia-deep focus:ring-atlantia-cyan';
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
