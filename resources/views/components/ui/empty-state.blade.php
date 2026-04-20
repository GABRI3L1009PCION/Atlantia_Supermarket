@props([
    'title',
    'message',
])

<div
    {{ $attributes->merge([
        'class' => 'rounded-lg border border-dashed border-slate-300 bg-white px-6 py-10 text-center',
    ]) }}
>
    <h2 class="text-base font-semibold text-slate-950">{{ $title }}</h2>
    <p class="mx-auto mt-2 max-w-md text-sm text-slate-600">{{ $message }}</p>

    @if (trim($slot) !== '')
        <div class="mt-5">{{ $slot }}</div>
    @endif
</div>
