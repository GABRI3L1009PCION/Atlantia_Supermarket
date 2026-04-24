@extends('layouts.marketplace')

@section('content')
    <section class="bg-atlantia-blush py-4 shadow-inner">
        <form method="GET" class="mx-auto grid w-full max-w-xl grid-cols-[1fr_auto] px-4 sm:px-0">
            <label for="q" class="sr-only">Buscar productos</label>
            <input
                id="q"
                type="search"
                name="q"
                value="{{ request('q') }}"
                placeholder="Buscar en todo el supermercado..."
                class="h-12 rounded-l-lg border-0 bg-white px-4 text-base text-atlantia-ink placeholder:text-atlantia-ink/55 shadow-sm focus:outline-none focus:ring-2 focus:ring-atlantia-rose"
            >
            <button
                type="submit"
                class="h-12 rounded-r-lg bg-atlantia-wine px-6 text-base font-bold text-white shadow-sm hover:bg-atlantia-wine-700 focus:outline-none focus:ring-2 focus:ring-atlantia-rose focus:ring-offset-2"
            >
                <span class="inline-flex items-center gap-2">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                        <path d="M11 19C15.4 19 19 15.4 19 11C19 6.6 15.4 3 11 3C6.6 3 3 6.6 3 11C3 15.4 6.6 19 11 19Z" stroke="currentColor" stroke-width="2"/>
                        <path d="M20.5 20.5L16.7 16.7" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                    </svg>
                    Buscar
                </span>
            </button>
        </form>
    </section>

    <section id="categorias" class="relative border-b border-atlantia-rose/15 bg-white py-6 shadow-sm">
        <button
            type="button"
            class="absolute left-4 top-1/2 z-10 hidden h-11 w-11 -translate-y-1/2 items-center justify-center rounded-full bg-atlantia-wine text-2xl font-bold text-white shadow-md transition hover:bg-atlantia-wine-700 sm:flex"
            aria-label="Categoria anterior"
            data-categorias-prev
        >
            &lsaquo;
        </button>
        <div class="mx-auto w-full max-w-6xl px-6 sm:px-14">
            <h2 class="mb-5 text-lg font-bold text-atlantia-ink">Explora por Categoria</h2>
            <div
                class="flex snap-x snap-mandatory gap-5 overflow-x-auto scroll-smooth pb-3 [scrollbar-width:none] [&::-webkit-scrollbar]:hidden"
                data-categorias-track
            >
                <a
                    href="{{ route('catalogo.index') }}#productos"
                    class="group flex min-w-[132px] snap-start flex-col items-center text-center"
                >
                    <span class="flex h-24 w-24 items-center justify-center rounded-full border border-atlantia-rose/20 bg-[radial-gradient(circle_at_center,_rgba(255,255,255,0.98)_0%,_rgba(248,234,239,0.95)_68%,_rgba(244,214,223,0.92)_100%)] shadow-[0_12px_30px_rgba(135,22,61,0.10)] transition duration-200 group-hover:-translate-y-1 group-hover:shadow-[0_16px_36px_rgba(135,22,61,0.16)]">
                        <span class="rounded-full bg-white/85 px-3 py-2 text-[11px] font-extrabold uppercase tracking-[0.18em] text-atlantia-wine">
                            Todo
                        </span>
                    </span>
                    <span class="mt-3 text-xs font-semibold uppercase tracking-[0.12em] text-atlantia-ink">
                        Todas las categorias
                    </span>
                </a>
                @foreach ($categoriasDestacadas as $categoria)
                    <a
                        href="{{ $categoria['href'] }}"
                        class="group flex min-w-[132px] snap-start flex-col items-center text-center"
                    >
                        <span class="flex h-24 w-24 items-center justify-center rounded-full border border-sky-100 bg-[radial-gradient(circle_at_center,_rgba(255,255,255,0.98)_0%,_rgba(235,250,252,0.96)_70%,_rgba(215,244,247,0.92)_100%)] shadow-[0_14px_30px_rgba(72,203,218,0.15)] ring-1 ring-sky-100/80 transition duration-200 group-hover:-translate-y-1 group-hover:shadow-[0_18px_38px_rgba(72,203,218,0.24)]">
                            <span class="flex h-20 w-20 items-center justify-center overflow-hidden rounded-full bg-white/85">
                                <img
                                    src="{{ $categoria['image'] }}"
                                    alt="{{ $categoria['nombre'] }}"
                                    class="h-full w-full object-cover"
                                    loading="lazy"
                                >
                            </span>
                        </span>
                        <span class="mt-3 text-xs font-semibold uppercase tracking-[0.1em] text-atlantia-ink">
                            {{ $categoria['nombre'] }}
                        </span>
                    </a>
                @endforeach
            </div>
        </div>
        <button
            type="button"
            class="absolute right-4 top-1/2 z-10 hidden h-11 w-11 -translate-y-1/2 items-center justify-center rounded-full bg-atlantia-wine text-2xl font-bold text-white shadow-md transition hover:bg-atlantia-wine-700 sm:flex"
            aria-label="Categoria siguiente"
            data-categorias-next
        >
            &rsaquo;
        </button>
    </section>

    <section class="mx-auto min-h-[313px] w-full max-w-7xl px-4 py-14 sm:px-6 lg:px-8">
        <livewire:catalogo.lista-productos :search="(string) request('q', '')" />
    </section>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const track = document.querySelector('[data-categorias-track]');
            const previous = document.querySelector('[data-categorias-prev]');
            const next = document.querySelector('[data-categorias-next]');

            if (! track || ! previous || ! next) {
                return;
            }

            const amount = () => Math.max(track.clientWidth * 0.72, 220);

            previous.addEventListener('click', () => {
                track.scrollBy({ left: -amount(), behavior: 'smooth' });
            });

            next.addEventListener('click', () => {
                track.scrollBy({ left: amount(), behavior: 'smooth' });
            });
        });
    </script>
@endpush
