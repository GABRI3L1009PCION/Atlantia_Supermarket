@extends('layouts.marketplace')

@section('content')
    @php
        $heroCards = collect($heroBanners)->values();
    @endphp

    <section class="border-b border-atlantia-rose/15 bg-atlantia-blush py-3">
        <div class="mx-auto w-full px-4">
            <livewire:catalogo.barra-busqueda :search="(string) request('q', '')" />
        </div>
    </section>

    <section
        x-data="{
            page: 0,
            totalPages: 1,
            itemsPerPage: 1,
            timer: null,
            setup() {
                this.updateMetrics();
                window.addEventListener('resize', () => this.updateMetrics());
                this.start();
            },
            updateMetrics() {
                this.itemsPerPage = window.innerWidth >= 1024 ? 3 : window.innerWidth >= 640 ? 2 : 1;
                this.totalPages = Math.max(1, Math.ceil({{ $heroCards->count() }} / this.itemsPerPage));
                if (this.page >= this.totalPages) this.page = this.totalPages - 1;
            },
            start() {
                if (this.totalPages < 2) return;
                this.stop();
                this.timer = setInterval(() => this.next(), 5000);
            },
            stop() {
                if (this.timer) clearInterval(this.timer);
            },
            next() {
                this.page = (this.page + 1) % this.totalPages;
            },
            prev() {
                this.page = (this.page - 1 + this.totalPages) % this.totalPages;
            },
            goTo(index) {
                this.page = index;
            }
        }"
        x-init="setup()"
        @mouseenter="stop()"
        @mouseleave="start()"
        class="overflow-hidden bg-white px-4 py-4 sm:px-6 sm:py-5"
    >
        <div class="mx-auto flex w-full max-w-6xl flex-col justify-center">
            <div class="grid w-full grid-cols-1 items-center sm:grid-cols-[auto_1fr_auto] sm:gap-3">
                <button
                    type="button"
                    @click="prev()"
                    class="hidden h-10 w-10 items-center justify-center rounded-full border border-[#e4c37d] bg-white text-[1.7rem] leading-none text-[#d6a94a] shadow-sm transition hover:bg-[#fff7e6] sm:flex"
                    aria-label="Banner anterior"
                >
                    &lsaquo;
                </button>

                <div class="overflow-hidden">
                    <div
                        class="flex transition-transform duration-500 ease-out"
                        :style="`transform: translateX(-${page * 100}%);`"
                    >
                        @foreach ($heroCards as $card)
                            <div class="min-w-full sm:min-w-[50%] sm:px-1 lg:min-w-[33.333333%]">
                                <article class="relative h-[142px] overflow-hidden rounded-[18px] border border-[#f1e5d7] bg-[#120d08] shadow-[0_16px_30px_rgba(30,16,9,0.18)] sm:h-[168px] sm:rounded-[20px] lg:h-[188px]">
                                    <div class="absolute inset-0 flex items-center justify-center overflow-hidden">
                                        <img
                                            src="{{ $card['desktop_image'] }}"
                                            alt="Banner promocional {{ $card['name'] }}"
                                            class="hidden h-full w-full object-contain object-center md:block"
                                        >
                                        <img
                                            src="{{ $card['mobile_image'] }}"
                                            alt="Banner promocional {{ $card['name'] }}"
                                            class="h-full w-full object-cover object-center md:hidden"
                                        >
                                    </div>
                                    <div class="pointer-events-none absolute inset-0 bg-[linear-gradient(180deg,rgba(255,255,255,0.02)_0%,rgba(0,0,0,0.06)_100%)]"></div>
                                </article>
                            </div>
                        @endforeach
                    </div>
                </div>

                <button
                    type="button"
                    @click="next()"
                    class="hidden h-10 w-10 items-center justify-center rounded-full border border-[#e4c37d] bg-white text-[1.7rem] leading-none text-[#d6a94a] shadow-sm transition hover:bg-[#fff7e6] sm:flex"
                    aria-label="Siguiente banner"
                >
                    &rsaquo;
                </button>
            </div>

            @if ($heroCards->count() > 1)
                <div class="mt-4 flex items-center justify-center gap-2">
                    <template x-for="index in totalPages" :key="index">
                        <button
                            type="button"
                            @click="goTo(index - 1)"
                            class="h-2.5 w-2.5 rounded-full transition"
                            :class="page === index - 1 ? 'bg-[#e3b34d]' : 'bg-[#e6e1df]'"
                            :aria-label="`Ir a la pagina ${index} del carrusel`"
                        ></button>
                    </template>
                </div>
            @endif
        </div>
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
                            <span class="flex h-[102px] w-[102px] items-center justify-center overflow-hidden rounded-full border border-atlantia-wine/80 bg-white p-3 text-atlantia-wine">
                                @if ($categoria['image'])
                                    <img
                                        src="{{ $categoria['image'] }}"
                                        alt="{{ $categoria['nombre'] }}"
                                        class="h-full w-full object-contain mix-blend-multiply"
                                        loading="lazy"
                                    >
                                @else
                                    <span class="text-2xl font-black">
                                        {{ \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($categoria['nombre'], 0, 2)) }}
                                    </span>
                                @endif
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

    <section class="mx-auto w-full max-w-7xl px-4 py-10 sm:px-6 lg:px-8 lg:py-12">
        <livewire:catalogo.lista-productos :search="(string) request('q', '')" />
    </section>

    <!-- Modal de detalle de producto -->
    <livewire:catalogo.producto-modal-detalle />

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
