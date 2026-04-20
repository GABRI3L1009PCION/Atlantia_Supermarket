@props([
    'caption' => null,
])

<div class="overflow-hidden rounded-lg border border-slate-200 bg-white">
    <div class="overflow-x-auto">
        <table {{ $attributes->merge(['class' => 'min-w-full divide-y divide-slate-200 text-sm']) }}>
            @if ($caption)
                <caption class="sr-only">{{ $caption }}</caption>
            @endif

            {{ $slot }}
        </table>
    </div>
</div>
