<a
    href="{{ route('cliente.carrito.index') }}"
    class="relative inline-flex items-center gap-2 rounded-md px-3 py-2 text-sm font-semibold text-slate-700
        hover:bg-slate-50 hover:text-emerald-800"
    aria-label="Carrito con {{ $cantidad }} productos"
>
    <span aria-hidden="true">Carrito</span>
    <span
        class="inline-flex min-w-6 items-center justify-center rounded-full bg-emerald-700 px-2 py-1
            text-xs text-white"
    >
        {{ $cantidad }}
    </span>
    <span class="hidden text-xs text-slate-500 sm:inline">
        Q {{ number_format($total, 2) }}
    </span>
</a>
