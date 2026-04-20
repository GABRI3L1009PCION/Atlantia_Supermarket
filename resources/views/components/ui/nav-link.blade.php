@props([
    'href',
    'active' => false,
])

@php
    $classes = $active
        ? 'bg-emerald-50 text-emerald-800'
        : 'text-slate-700 hover:bg-slate-50 hover:text-emerald-700';
@endphp

<a href="{{ $href }}" {{ $attributes->merge(['class' => 'block rounded-md px-3 py-2 font-medium ' . $classes]) }}>
    {{ $slot }}
</a>
