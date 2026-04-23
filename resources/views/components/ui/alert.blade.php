@props([
    'type' => 'info',
    'message' => null,
])

@php
    $classes = [
        'success' => 'border-emerald-200 bg-emerald-50 text-emerald-900',
        'error' => 'border-red-200 bg-red-50 text-red-900',
        'warning' => 'border-amber-200 bg-amber-50 text-amber-900',
        'info' => 'border-sky-200 bg-sky-50 text-sky-900',
    ][$type] ?? 'border-slate-200 bg-slate-50 text-slate-900';
@endphp

<div {{ $attributes->merge(['class' => 'mb-4 rounded-lg border px-4 py-3 text-sm ' . $classes]) }} role="alert">
    {{ $message ?? $slot }}
</div>
