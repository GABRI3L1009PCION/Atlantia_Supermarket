<a
    href="{{ route('cliente.carrito.index') }}"
    class="relative flex h-11 w-11 items-center justify-center rounded-md border border-atlantia-wine/18 bg-white text-atlantia-wine transition hover:border-atlantia-wine/35 hover:bg-atlantia-blush"
    aria-label="Carrito con {{ $cantidad }} productos"
>
    <svg class="h-7 w-7" viewBox="0 0 24 24" fill="none" aria-hidden="true">
        <path
            d="M4 5H6L8.1 15.2C8.3 16.2 9.2 17 10.3 17H17.8C18.8 17 19.6 16.4 19.9 15.5L21 9H7"
            stroke="currentColor"
            stroke-width="2"
            stroke-linecap="round"
            stroke-linejoin="round"
        />
        <path
            d="M10 21C10.6 21 11 20.6 11 20C11 19.4 10.6 19 10 19C9.4 19 9 19.4 9 20C9 20.6 9.4 21 10 21Z"
            fill="currentColor"
        />
        <path
            d="M18 21C18.6 21 19 20.6 19 20C19 19.4 18.6 19 18 19C17.4 19 17 19.4 17 20C17 20.6 17.4 21 18 21Z"
            fill="currentColor"
        />
    </svg>
    <span
        class="absolute -right-1 -top-1 min-w-5 rounded-full bg-emerald-700 px-1 text-center text-xs font-bold text-white"
    >
        {{ $cantidad }}
    </span>
    <span class="sr-only">
        Q {{ number_format($total, 2) }}
    </span>
</a>
