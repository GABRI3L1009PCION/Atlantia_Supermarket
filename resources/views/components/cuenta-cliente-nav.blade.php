{{-- Barra de secciones de la cuenta del cliente: aparece arriba de cada página de "Mi cuenta". --}}
@auth
    @if (\App\Support\MenuCuenta::panelDe(auth()->user()) === null)
        <div class="border-b border-atlantia-rose/15 bg-white" data-cuenta-cliente-nav>
            <div class="mx-auto flex w-full max-w-7xl items-center gap-4 px-4 sm:px-6 lg:px-8">
                <div class="hidden shrink-0 items-center gap-2.5 py-3 md:flex">
                    <span class="grid h-9 w-9 place-items-center rounded-full bg-atlantia-wine text-xs font-black text-white">{{ \App\Support\MenuCuenta::iniciales(auth()->user()->name) }}</span>
                    <div class="leading-tight">
                        <p class="text-[11px] font-semibold text-atlantia-ink/50">Mi cuenta</p>
                        <p class="max-w-[10rem] truncate text-sm font-black text-atlantia-ink">{{ \Illuminate\Support\Str::before(trim(auth()->user()->name), ' ') }}</p>
                    </div>
                </div>
                <span class="hidden h-8 w-px bg-atlantia-rose/20 md:block" aria-hidden="true"></span>
                <nav class="-mb-px flex min-w-0 flex-1 gap-1 overflow-x-auto [scrollbar-width:none] [&::-webkit-scrollbar]:hidden" aria-label="Secciones de mi cuenta">
                    @foreach (\App\Support\MenuCuenta::cliente() as $opcion)
                        @php $activa = request()->routeIs(...$opcion['activa']); @endphp
                        <a href="{{ route($opcion['ruta']) }}" @class([
                            'inline-flex shrink-0 items-center gap-2 border-b-2 px-3 py-3.5 text-sm font-bold transition',
                            'border-atlantia-wine text-atlantia-wine' => $activa,
                            'border-transparent text-atlantia-ink/60 hover:text-atlantia-wine' => ! $activa,
                        ]) @if ($activa) aria-current="page" @endif>
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="{{ $opcion['icono'] }}" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>
                            {{ $opcion['texto'] }}
                        </a>
                    @endforeach
                </nav>
            </div>
        </div>
    @endif
@endauth
