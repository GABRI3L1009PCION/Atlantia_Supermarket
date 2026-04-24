@props([
    'type' => 'button',
    'variant' => 'primary',
])

@php
    $classes = [
        'primary' => 'bg-atlantia-wine text-white hover:bg-atlantia-wine-700 focus:ring-atlantia-rose',
        'secondary' => 'bg-white text-atlantia-ink ring-1 ring-atlantia-rose/40 hover:bg-atlantia-blush focus:ring-atlantia-rose',
        'danger' => 'bg-red-700 text-white hover:bg-red-800 focus:ring-red-600',
        'ghost' => 'bg-transparent text-atlantia-ink hover:bg-atlantia-blush focus:ring-atlantia-rose',
    ][$variant] ?? 'bg-atlantia-wine text-white hover:bg-atlantia-wine-700 focus:ring-atlantia-rose';
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
