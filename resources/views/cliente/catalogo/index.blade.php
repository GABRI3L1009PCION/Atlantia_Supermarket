@extends('layouts.marketplace')

@section('content')
    @php
        $iconoCategoria = static function (?string $slug): string {
            return match ($slug) {
                'frutas-y-verduras', 'verduras-frescas', 'hierbas-y-aromaticas', 'ensaladas-y-brotes' => 'produce',
                'carnes-y-aves' => 'meat',
                'abarrotes-secos', 'despensa', 'hogar' => 'pantry',
                'panaderia' => 'bread',
                'lacteos' => 'dairy',
                'bebidas' => 'drinks',
                default => 'bag',
            };
        };
    @endphp

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
                            <span class="flex h-20 w-20 items-center justify-center rounded-full bg-white/90 text-atlantia-wine">
                                @switch($iconoCategoria($categoria['slug'] ?? null))
                                    @case('produce')
                                        <svg class="h-10 w-10" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                            <path d="M13 4C14.8 4 16 2.9 16.5 1.5C14.5 1.5 12.9 2.3 12 4" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                                            <path d="M12 7C8.1 7 5 10 5 13.8C5 17.8 8 21 12 21C16 21 19 17.8 19 13.8C19 10 15.9 7 12 7Z" stroke="currentColor" stroke-width="1.8"/>
                                            <path d="M8.5 12.5C9.4 11.6 10.5 11 12 11" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                                        </svg>
                                        @break
                                    @case('meat')
                                        <svg class="h-10 w-10" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                            <path d="M14.5 6C17.8 6 20 8.1 20 11C20 14.9 16.8 18 12.8 18H10.2C7.3 18 5 15.7 5 12.8C5 9.8 7.4 7.5 10.4 7.5C11 5.8 12.5 6 14.5 6Z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/>
                                            <circle cx="14.5" cy="11" r="1.6" stroke="currentColor" stroke-width="1.8"/>
                                        </svg>
                                        @break
                                    @case('pantry')
                                        <svg class="h-10 w-10" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                            <path d="M7 8.5C7 7.1 8.1 6 9.5 6H14.5C15.9 6 17 7.1 17 8.5V18H7V8.5Z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/>
                                            <path d="M10 6V4.8C10 4.1 10.6 3.5 11.3 3.5H12.7C13.4 3.5 14 4.1 14 4.8V6" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                                            <path d="M9.5 10H14.5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                                        </svg>
                                        @break
                                    @case('bread')
                                        <svg class="h-10 w-10" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                            <path d="M7 18H17C18.7 18 20 16.7 20 15C20 13.8 19.3 12.7 18.2 12.2C18.4 11.8 18.5 11.4 18.5 11C18.5 9.3 17.2 8 15.5 8C14.8 8 14.2 8.2 13.6 8.6C13 7.6 11.9 7 10.7 7C8.7 7 7 8.7 7 10.7C7 11 7 11.2 7.1 11.5C5.8 12 5 13.2 5 14.6C5 16.5 6.3 18 7 18Z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/>
                                        </svg>
                                        @break
                                    @case('dairy')
                                        <svg class="h-10 w-10" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                            <path d="M8 8L10 5H14L16 8V19H8V8Z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/>
                                            <path d="M10 12H14" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                                            <path d="M5 10.5C5 9.1 6.1 8 7.5 8H8V19H7.5C6.1 19 5 17.9 5 16.5V10.5Z" stroke="currentColor" stroke-width="1.8"/>
                                        </svg>
                                        @break
                                    @case('drinks')
                                        <svg class="h-10 w-10" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                            <path d="M8 5.5C8 4.7 8.7 4 9.5 4H14.5C15.3 4 16 4.7 16 5.5V7L15 9V19H9V9L8 7V5.5Z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/>
                                            <path d="M10 12H14" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                                        </svg>
                                        @break
                                    @default
                                        <svg class="h-10 w-10" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                            <path d="M7 9H17L16 19H8L7 9Z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/>
                                            <path d="M9.5 9V7.8C9.5 6.3 10.7 5 12.2 5H11.8C13.3 5 14.5 6.3 14.5 7.8V9" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                                        </svg>
                                @endswitch
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
