@props(['number', 'title', 'subtitle' => null])

<div class="flex flex-col gap-3 sm:flex-row sm:items-center">
    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-atlantia-blush text-sm font-black text-atlantia-wine ring-1 ring-atlantia-rose/20">
        {{ $number }}
    </div>
    <div>
        <h2 class="text-lg font-black text-atlantia-ink">{{ $title }}</h2>
        @if ($subtitle)
            <p class="mt-1 text-sm text-atlantia-ink/60">{{ $subtitle }}</p>
        @endif
    </div>
</div>
