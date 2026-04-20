@props([
    'href',
    'active' => false,
])

@php
    $classes = $active
        ? 'bg-atlantia-wine text-white shadow-sm'
        : 'text-atlantia-ink/80 hover:bg-white/70 hover:text-atlantia-wine';
@endphp

<a href="{{ $href }}" {{ $attributes->merge(['class' => 'block rounded-md px-3 py-2.5 font-medium transition ' . $classes]) }}>
    {{ $slot }}
</a>
