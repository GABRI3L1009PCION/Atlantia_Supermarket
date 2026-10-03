<footer class="hidden bg-[linear-gradient(100deg,#4a071c_0%,#7a1238_48%,#4a071c_100%)] text-white lg:block">
    @if (request()->routeIs('comercios.index', 'categorias.index', 'cliente.pedidos.index'))
        <div class="mx-auto hidden w-full max-w-7xl grid-cols-[1.05fr_1fr_1fr_1fr_1.2fr] items-center gap-5 px-8 py-4 lg:grid">
            <div class="flex items-center gap-3">
                <span class="grid h-10 w-10 place-items-center rounded-full border-2 border-white/80 text-lg font-black">A</span>
                <div class="leading-none">
                    <p class="text-base font-black">Atlantia</p>
                    <p class="mt-1 text-[8px] font-black uppercase text-white/70">Delivery</p>
                </div>
            </div>

            @foreach ([
                ['title' => 'Comercios verificados', 'text' => 'Locales aprobados y confiables', 'icon' => 'shield'],
                ['title' => 'Pago seguro', 'text' => 'Tus opciones estan protegidas', 'icon' => 'lock'],
                ['title' => 'Atencion 24/7', 'text' => 'Estamos para ayudarte siempre', 'icon' => 'support'],
            ] as $footerBenefit)
                <div class="flex min-w-0 items-center gap-3">
                    <span class="grid h-9 w-9 shrink-0 place-items-center rounded-full bg-white/10 text-white">
                        @if ($footerBenefit['icon'] === 'shield')
                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M12 3 19 6v5c0 4.5-2.8 8.4-7 10-4.2-1.6-7-5.5-7-10V6l7-3ZM9 12l2 2 4-5" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/></svg>
                        @elseif ($footerBenefit['icon'] === 'lock')
                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M7 10V8a5 5 0 0 1 10 0v2M5 10h14v10H5V10Z" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round"/></svg>
                        @else
                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M4 13v-2a8 8 0 0 1 16 0v2M4 13h3v6H5a1 1 0 0 1-1-1v-5ZM20 13h-3v6h2a1 1 0 0 0 1-1v-5ZM17 19c0 2-2 2-5 2" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/></svg>
                        @endif
                    </span>
                    <div class="min-w-0">
                        <p class="truncate text-[10px] font-black">{{ $footerBenefit['title'] }}</p>
                        <p class="mt-0.5 truncate text-[8px] font-semibold text-white/65">{{ $footerBenefit['text'] }}</p>
                    </div>
                </div>
            @endforeach

            <p class="text-right text-[9px] font-semibold text-white/75">
                &copy; {{ now()->year }} Atlantia Delivery.<br>Todos los derechos reservados.
            </p>
        </div>
    @endif

    <div class="{{ request()->routeIs('comercios.index', 'categorias.index', 'cliente.pedidos.index') ? 'lg:hidden' : '' }} mx-auto flex w-full max-w-7xl flex-col items-center justify-center gap-1 px-4 py-5 pb-safe text-center text-xs leading-6 sm:px-6 sm:text-sm lg:px-8">
        <p class="max-w-full">
            <span class="font-bold">Atlantia Delivery</span>
            <span>&copy; {{ now()->year }}. Todos los derechos reservados.</span>
        </p>
        <p class="text-white/80">Santo Tomas de Castilla y Puerto Barrios, Izabal.</p>
    </div>
</footer>
