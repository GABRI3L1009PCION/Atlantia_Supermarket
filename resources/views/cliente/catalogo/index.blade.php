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
                    <span class="flex h-28 w-28 items-center justify-center rounded-full border-[3px] border-atlantia-wine bg-white shadow-[0_14px_30px_rgba(135,22,61,0.08)] transition duration-200 group-hover:-translate-y-1 group-hover:shadow-[0_18px_34px_rgba(135,22,61,0.14)]">
                        <span class="flex h-[102px] w-[102px] items-center justify-center rounded-full border border-atlantia-wine/80 text-atlantia-wine">
                            <svg class="h-12 w-12" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                <path d="M12 4V20" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                                <path d="M4 12H20" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                                <path d="M6.5 6.5L17.5 17.5" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" opacity="0.55"/>
                                <path d="M17.5 6.5L6.5 17.5" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" opacity="0.55"/>
                            </svg>
                        </span>
                    </span>
                    <span class="mt-3 font-serif text-[13px] font-semibold uppercase tracking-[0.12em] text-atlantia-wine">
                        Todas las categorias
                    </span>
                </a>
                @foreach ($categoriasDestacadas as $categoria)
                    <a
                        href="{{ $categoria['href'] }}"
                        class="group flex min-w-[132px] snap-start flex-col items-center text-center"
                    >
                        <span class="flex h-28 w-28 items-center justify-center rounded-full border-[3px] border-atlantia-wine bg-white shadow-[0_14px_30px_rgba(135,22,61,0.08)] transition duration-200 group-hover:-translate-y-1 group-hover:shadow-[0_18px_34px_rgba(135,22,61,0.14)]">
                            <span class="relative flex h-[102px] w-[102px] items-center justify-center rounded-full border border-atlantia-wine/80 bg-white text-atlantia-wine">
                                <span class="absolute top-3 h-2 w-2 rounded-full bg-atlantia-wine"></span>
                                <span class="absolute bottom-4 h-px w-8 bg-amber-500/70"></span>
                                <span class="absolute bottom-[15px] h-2 w-2 rotate-45 border border-amber-500/80 bg-white"></span>
                                @switch($iconoCategoria($categoria['slug'] ?? null))
                                    @case('produce')
                                        <svg class="h-12 w-12" viewBox="0 0 64 64" fill="currentColor" aria-hidden="true">
                                            <path d="M33.6 12.5c4.4-3.9 9.3-5.2 13.7-4.2-1 4.8-3.8 8.6-8.8 10.8-1.2-2.2-2.8-4.5-4.9-6.6Z"/>
                                            <path d="M31.5 20.2c10.2 0 18.8 7.5 18.8 18.4 0 11.2-8.5 18.9-18.8 18.9-10.6 0-17.8-7.8-17.8-18.3 0-10.8 7.9-19 17.8-19Zm-6.8 12.7c0 6.3 4.9 10.2 10.4 10.2 3 0 5.7-.7 8.5-2.3-1.7 5.3-6.3 9.1-12.1 9.1-7 0-12.3-5.2-12.3-12.5 0-5.4 2.4-9.2 5.5-11.8-.1 2-.1 4.3 0 7.3Z"/>
                                        </svg>
                                        @break
                                    @case('meat')
                                        <svg class="h-12 w-12" viewBox="0 0 64 64" fill="currentColor" aria-hidden="true">
                                            <path d="M24.1 16.8c11.5 0 18.5 4.3 18.5 12.9 0 2.1-.5 3.7-1.4 5.2 4.7.6 9 4.7 9 10.3 0 8.2-6.8 14-15.5 14H21.6c-9.1 0-15.2-6.4-15.2-14.1 0-6.2 4.2-10.8 9.9-11.8.3-9.6 7.3-16.5 17.8-16.5Zm-1 9.2c-4.6 0-8 2.6-8.9 6.4 4.6.1 10.1 1.5 15.7 4.5 3.7-2.8 7.2-5.2 10.8-6.3-1.4-3.1-6.2-4.6-17.6-4.6Zm16.4 16.1c-3.7 0-7.9 1.8-12.5 5.2-5.3-3.1-10.2-4.7-13.8-4.7-2.3 0-4 1.3-4 3.5 0 3.7 4.1 6.5 11.3 6.5H35c7.7 0 11.7-2.4 11.7-6.5 0-2.3-1.8-4-7.2-4Z"/>
                                        </svg>
                                        @break
                                    @case('pantry')
                                        <svg class="h-12 w-12" viewBox="0 0 64 64" fill="currentColor" aria-hidden="true">
                                            <path d="M17 21.5c0-3.4 2.7-6.1 6.1-6.1h3.3v-2.8c0-2.8 2.3-5.1 5.1-5.1h1c2.8 0 5.1 2.3 5.1 5.1v2.8h3.3c3.4 0 6.1 2.7 6.1 6.1v29H17v-29Zm5.8.3v23.2h18.4V21.8H22.8Zm8.1-6.4h2.1v-2.5c0-.7-.6-1.3-1.3-1.3h-.5c-.7 0-1.3.6-1.3 1.3v2.5Z"/>
                                            <path d="M26.5 27.8h11v3h-11z"/>
                                        </svg>
                                        @break
                                    @case('bread')
                                        <svg class="h-12 w-12" viewBox="0 0 64 64" fill="currentColor" aria-hidden="true">
                                            <path d="M17.6 46.7h28.8c4.1 0 7.4-3.1 7.4-7 0-2.8-1.6-5.1-4.2-6.2.6-1.2.9-2.4.9-3.7 0-4.8-4-8.7-8.9-8.7-1.7 0-3.4.5-4.8 1.3-2.3-2.9-5.6-4.5-9.7-4.5-6.8 0-12.3 5.1-12.3 11.6 0 .8.1 1.6.2 2.4-3.7 1.2-6 4.4-6 8.6 0 3.7 3.4 6.2 8.6 6.2Zm7.5-12.1c.7-3.8 3.2-6.4 7.2-7.7 2.7-.9 5.8-.8 8.9.5-3.5 1.1-7.4 4.2-11.5 9.3-1.8-.8-3.4-1.4-4.6-2.1Z"/>
                                        </svg>
                                        @break
                                    @case('dairy')
                                        <svg class="h-12 w-12" viewBox="0 0 64 64" fill="currentColor" aria-hidden="true">
                                            <path d="M20 20.2 25.1 12h13.8l5.1 8.2v31H20v-31Zm5.9 2.6v22.5h12.2V22.8H25.9Zm3.2 8.4h6.1v3.2h-6.1z"/>
                                            <path d="M12 24.8c0-3.4 2.8-6.2 6.2-6.2H20v32.6h-1.8c-3.4 0-6.2-2.8-6.2-6.2V24.8Z"/>
                                        </svg>
                                        @break
                                    @case('drinks')
                                        <svg class="h-12 w-12" viewBox="0 0 64 64" fill="currentColor" aria-hidden="true">
                                            <path d="M22.3 13.5c0-2.3 1.9-4.2 4.2-4.2h11c2.3 0 4.2 1.9 4.2 4.2v3.4l-2.9 5.1v29.2H25.2V22l-2.9-5.1v-3.4Zm5.6 2v3.7l2.2 3.9v22h5.8v-22l2.2-3.9v-3.7H27.9Zm2.3 12.3h5.6v3.2h-5.6Z"/>
                                        </svg>
                                        @break
                                    @default
                                        <svg class="h-12 w-12" viewBox="0 0 64 64" fill="currentColor" aria-hidden="true">
                                            <path d="M20 23h24l-2.5 27.2H22.5L20 23Zm4.6 4.8 1.6 17.6h11.6l1.6-17.6H24.6Z"/>
                                            <path d="M25 22.8v-2.5c0-4.2 3.4-7.6 7.6-7.6h.8c4.2 0 7.6 3.4 7.6 7.6v2.5h-4v-2.2c0-2-1.6-3.6-3.6-3.6h-.8c-2 0-3.6 1.6-3.6 3.6v2.2h-4Z"/>
                                        </svg>
                                @endswitch
                            </span>
                        </span>
                        <span class="mt-3 font-serif text-[13px] font-semibold uppercase tracking-[0.12em] text-atlantia-wine">
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

    <section id="contacto" class="border-t border-atlantia-rose/15 bg-atlantia-blush/35 py-16 scroll-mt-6">
        <div class="mx-auto grid w-full max-w-7xl gap-8 px-4 sm:px-6 lg:grid-cols-[1.2fr_0.8fr] lg:px-8">
            <div class="space-y-4">
                <p class="text-sm font-extrabold uppercase tracking-[0.18em] text-atlantia-wine">Contacto Atlantia</p>
                <h2 class="text-3xl font-black text-atlantia-ink sm:text-4xl">
                    Estamos aqui para ayudarte con tus pedidos y cobertura actual.
                </h2>
                <p class="max-w-2xl text-base leading-7 text-atlantia-ink/75">
                    En esta primera etapa atendemos compras y entregas en Puerto Barrios y Santo Tomas de Castilla.
                    Si necesitas orientacion sobre disponibilidad, acceso a tu cuenta o seguimiento de tu compra,
                    puedes hacerlo directamente desde Atlantia.
                </p>

                <div class="flex flex-wrap gap-3 pt-2">
                    <a
                        href="{{ route('login') }}"
                        class="rounded-md bg-atlantia-wine px-5 py-3 text-sm font-bold text-white transition hover:bg-atlantia-wine-700"
                    >
                        Iniciar sesion
                    </a>
                    <a
                        href="{{ route('catalogo.index') }}"
                        class="rounded-md border border-atlantia-rose/30 bg-white px-5 py-3 text-sm font-bold text-atlantia-wine transition hover:bg-atlantia-blush"
                    >
                        Seguir comprando
                    </a>
                </div>
            </div>

            <div class="grid gap-4">
                <article class="rounded-2xl border border-atlantia-rose/20 bg-white p-5 shadow-sm">
                    <p class="text-sm font-extrabold uppercase tracking-[0.14em] text-atlantia-wine">Cobertura actual</p>
                    <p class="mt-3 text-lg font-bold text-atlantia-ink">Puerto Barrios y Santo Tomas de Castilla</p>
                    <p class="mt-2 text-sm leading-6 text-atlantia-ink/70">
                        Seguimos creciendo paso a paso para ampliar Atlantia al resto de Izabal en futuras actualizaciones.
                    </p>
                </article>

                <article class="rounded-2xl border border-atlantia-rose/20 bg-white p-5 shadow-sm">
                    <p class="text-sm font-extrabold uppercase tracking-[0.14em] text-atlantia-wine">Soporte en plataforma</p>
                    <p class="mt-3 text-lg font-bold text-atlantia-ink">Accede a tu cuenta para revisar pedidos, direcciones y avisos</p>
                    <p class="mt-2 text-sm leading-6 text-atlantia-ink/70">
                        Desde tu sesion puedes dar seguimiento a tus compras y mantener tus datos de entrega siempre al dia.
                    </p>
                </article>
            </div>
        </div>
    </section>
@endsection

@push('scripts')
    <script nonce="{{ request()->attributes->get('csp_nonce') }}">
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
