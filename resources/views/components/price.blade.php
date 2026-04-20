@props([
    'amount',
    'currency' => 'Q',
])

<span {{ $attributes->merge(['class' => 'font-bold text-slate-950']) }}>
    {{ $currency }} {{ number_format((float) $amount, 2) }}
</span>
