@props([
    'title',
    'subtitle' => null,
])

<header {{ $attributes->merge(['class' => 'mb-6']) }}>
    <h1 class="text-2xl font-bold text-atlantia-ink sm:text-3xl">{{ $title }}</h1>
    @if ($subtitle)
        <p class="mt-2 max-w-3xl text-sm text-atlantia-ink/70">{{ $subtitle }}</p>
    @endif
</header>

