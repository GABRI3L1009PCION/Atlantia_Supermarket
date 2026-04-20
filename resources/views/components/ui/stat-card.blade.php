@props([
    'label',
    'value',
    'hint' => null,
])

<section {{ $attributes->merge(['class' => 'rounded-lg border border-slate-200 bg-white p-5 shadow-sm']) }}>
    <p class="text-sm font-medium text-slate-600">{{ $label }}</p>
    <p class="mt-2 text-2xl font-bold text-slate-950">{{ $value }}</p>

    @if ($hint)
        <p class="mt-1 text-sm text-slate-500">{{ $hint }}</p>
    @endif
</section>
