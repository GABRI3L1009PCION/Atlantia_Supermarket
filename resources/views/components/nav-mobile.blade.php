@props([
    'items' => [],
    'contactHref' => '#contacto',
])

<div class="md:hidden" x-data="{ open: false }">
    <button
        type="button"
        class="inline-flex h-11 w-11 items-center justify-center rounded-md border border-atlantia-rose/20 bg-atlantia-cream text-atlantia-wine shadow-sm"
        aria-label="Abrir menu principal"
        :aria-expanded="open ? 'true' : 'false'"
        aria-controls="nav-mobile-panel"
        @click="open = !open"
    >
        <svg x-show="!open" class="h-6 w-6" viewBox="0 0 24 24" fill="none" aria-hidden="true">
            <path d="M4 7H20" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
            <path d="M4 12H20" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
            <path d="M4 17H20" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
        </svg>
        <svg x-show="open" x-cloak class="h-6 w-6" viewBox="0 0 24 24" fill="none" aria-hidden="true">
            <path d="M6 6L18 18" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
            <path d="M18 6L6 18" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
        </svg>
    </button>

    <div
        id="nav-mobile-panel"
        x-show="open"
        x-cloak
        @click.outside="open = false"
        class="absolute inset-x-4 top-[5.7rem] z-40 rounded-lg border border-atlantia-rose/20 bg-white p-4 shadow-xl"
        role="dialog"
        aria-modal="true"
        aria-label="Menu principal"
    >
        <nav class="space-y-2" aria-label="Menu principal movil">
            @foreach ($items as $item)
                <a
                    href="{{ $item['href'] }}"
                    class="{{ !empty($item['active']) ? 'bg-atlantia-wine text-white' : 'text-atlantia-ink hover:bg-atlantia-blush' }} block rounded-md px-4 py-3 text-sm font-bold"
                    @click="open = false"
                >
                    {{ $item['label'] }}
                </a>
            @endforeach

            <a
                href="{{ $contactHref }}"
                class="block rounded-md px-4 py-3 text-sm font-bold text-atlantia-ink hover:bg-atlantia-blush"
                @click="open = false"
            >
                Contacto
            </a>
        </nav>
    </div>
</div>
