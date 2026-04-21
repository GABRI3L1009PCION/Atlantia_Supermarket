@props([
    'label',
    'value',
    'hint' => null,
])

<section {{ $attributes->merge(['class' => 'rounded-lg border border-atlantia-rose/20 bg-white p-5 shadow-sm']) }}>
    <p class="text-sm font-medium text-atlantia-ink/60">{{ $label }}</p>
    <p class="mt-2 text-2xl font-bold text-atlantia-ink">{{ $value }}</p>

    @if ($hint)
        <p class="mt-1 text-sm text-atlantia-ink/50">{{ $hint }}</p>
    @endif
</section>
