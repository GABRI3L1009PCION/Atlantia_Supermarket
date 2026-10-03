@props([
    'items' => [],
    'contactHref' => '#contacto',
    'contactActive' => false,
])

<details class="group relative md:hidden">
    <summary
        class="flex h-9 w-9 cursor-pointer list-none items-center justify-center rounded-lg border border-atlantia-rose/25 bg-white text-atlantia-wine shadow-sm transition hover:bg-atlantia-blush group-open:bg-atlantia-blush sm:h-10 sm:w-10 [&::-webkit-details-marker]:hidden"
        aria-label="Abrir menu principal"
    >
        <span class="sr-only">Menu principal</span>
        <span class="relative block h-4 w-5">
            <span class="absolute left-0 top-0 h-0.5 w-5 rounded-full bg-current transition group-open:top-1.5 group-open:rotate-45"></span>
            <span class="absolute left-0 top-1.5 h-0.5 w-5 rounded-full bg-current transition group-open:opacity-0"></span>
            <span class="absolute left-0 top-3 h-0.5 w-5 rounded-full bg-current transition group-open:top-1.5 group-open:-rotate-45"></span>
        </span>
    </summary>

    <div
        id="nav-mobile-panel"
        class="absolute right-0 top-11 z-40 w-56 max-w-[calc(100vw-1.5rem)] rounded-xl border border-atlantia-rose/20 bg-white p-2 shadow-xl"
        role="dialog"
        aria-label="Menu principal"
    >
        <nav class="space-y-1" aria-label="Menu principal movil">
            @foreach ($items as $item)
                <a
                    href="{{ $item['href'] }}"
                    class="{{ !empty($item['active']) ? 'bg-atlantia-wine text-white' : 'text-atlantia-ink hover:bg-atlantia-blush' }} block rounded-lg px-3.5 py-2.5 text-sm font-bold"
                >
                    {{ $item['label'] }}
                </a>
            @endforeach

            <a
                href="{{ $contactHref }}"
                class="{{ $contactActive ? 'bg-atlantia-wine text-white' : 'text-atlantia-ink hover:bg-atlantia-blush' }} block rounded-lg px-3.5 py-2.5 text-sm font-bold"
            >
                Contacto
            </a>
        </nav>
    </div>
</details>
