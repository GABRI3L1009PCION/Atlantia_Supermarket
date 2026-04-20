@props([
    'title' => null,
    'description' => null,
])

<section {{ $attributes->merge(['class' => 'rounded-lg border border-slate-200 bg-white p-5 shadow-sm']) }}>
    @if ($title || $description)
        <header class="mb-4">
            @if ($title)
                <h2 class="text-lg font-semibold text-slate-950">{{ $title }}</h2>
            @endif

            @if ($description)
                <p class="mt-1 text-sm text-slate-600">{{ $description }}</p>
            @endif
        </header>
    @endif

    {{ $slot }}
</section>
