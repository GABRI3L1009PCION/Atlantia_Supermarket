@extends('layouts.marketplace')

@section('content')
    <div class="min-h-full bg-[#fffafa]">
        <section class="relative overflow-hidden border-b border-atlantia-rose/10 bg-[linear-gradient(110deg,#fffafb_0%,#fff5f8_58%,#f8e7ee_100%)]">
            <div class="pointer-events-none absolute inset-y-0 right-0 hidden w-[55%] lg:block" aria-hidden="true">
                <svg class="h-full w-full" viewBox="0 0 700 200" fill="none" preserveAspectRatio="xMaxYMid meet">
                    <defs>
                        <linearGradient id="cat-bolsa" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="#b8313f"/><stop offset="1" stop-color="#8b1538"/></linearGradient>
                        <linearGradient id="cat-bolsa-2" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="#f3c6d4"/><stop offset="1" stop-color="#e59ab3"/></linearGradient>
                        <linearGradient id="cat-fade" x1="0" y1="0" x2="1" y2="0"><stop offset="0" stop-color="#fff6f6" stop-opacity="1"/><stop offset=".25" stop-color="#fff6f6" stop-opacity="0"/></linearGradient>
                    </defs>
                    {{-- Silueta del puerto: faro, palmeras y casas --}}
                    <g fill="#5e0008" opacity=".09">
                        <path d="M300 168h400v32H300z"/>
                        <path d="M560 168V92l6-10h14l6 10v76z"/><path d="M563 82h20l-10-16z"/><rect x="566" y="96" width="14" height="8" fill="#fff6f6"/>
                        <path d="M470 168v-34h34v34zM508 168v-48h44v48zM596 168v-30h40v30zM640 168v-42h50v42z"/>
                        <path d="M430 168c2-30 6-52 4-70" stroke="#5e0008" stroke-width="4" fill="none"/>
                        <path d="M434 98c-14-8-30-6-38 2 12-2 26 0 38-2Zm0 0c10-12 26-14 36-8-12 0-24 4-36 8Zm0 0c-4-14 2-26 12-30-6 10-8 20-12 30Zm0 0c-16 4-24 14-24 24 6-10 14-18 24-24Z"/>
                        <path d="M672 168c1-24 4-40 2-56" stroke="#5e0008" stroke-width="3.5" fill="none"/>
                        <path d="M674 112c-12-6-24-4-30 2 10-2 20 0 30-2Zm0 0c8-10 20-11 28-6-10 0-19 3-28 6Zm0 0c-3-11 2-20 10-24-5 8-7 16-10 24Z"/>
                    </g>
                    {{-- Pin de ubicacion --}}
                    <g transform="translate(628 22)">
                        <path d="M18 0C8 0 0 8 0 18c0 13 18 32 18 32s18-19 18-32C36 8 28 0 18 0Z" fill="#fff" stroke="#b8313f" stroke-width="3"/>
                        <circle cx="18" cy="18" r="6" fill="#b8313f"/>
                    </g>
                    {{-- Carrito --}}
                    <g stroke="#e3a6ba" stroke-width="4" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M262 74h22l18 78h96l14-58H296"/>
                        <path d="M300 108h108M306 128h96M330 94l6 58M362 94l2 58M392 94l-4 58"/>
                        <circle cx="316" cy="170" r="9" fill="#fff"/><circle cx="386" cy="170" r="9" fill="#fff"/>
                    </g>
                    {{-- Bolsas Atlantia --}}
                    <g>
                        <path d="M428 62c0-22 12-34 26-34s26 12 26 34" stroke="#a33559" stroke-width="6" fill="none" stroke-linecap="round"/>
                        <path d="M404 64h100l8 110H396z" fill="url(#cat-bolsa)"/>
                        <text x="454" y="140" text-anchor="middle" font-size="54" font-style="italic" font-weight="900" fill="#fff" fill-opacity=".85" font-family="Georgia, serif">A</text>
                        <path d="M528 104c0-14 8-22 18-22s18 8 18 22" stroke="#d987a1" stroke-width="5" fill="none" stroke-linecap="round"/>
                        <path d="M512 104h68l6 70h-80z" fill="url(#cat-bolsa-2)"/>
                        <text x="546" y="152" text-anchor="middle" font-size="36" font-style="italic" font-weight="900" fill="#fff" fill-opacity=".9" font-family="Georgia, serif">A</text>
                    </g>
                    <rect width="700" height="200" fill="url(#cat-fade)"/>
                </svg>
            </div>

            <div class="relative mx-auto w-full max-w-[1680px] px-4 py-7 sm:px-6 lg:px-8 lg:py-9">
                <div class="max-w-2xl">
                    <h1 class="text-3xl font-black leading-tight text-atlantia-ink sm:text-4xl">
                        Explora nuestras <span class="text-atlantia-wine">categorías</span>
                    </h1>
                    <p class="mt-2 text-sm font-semibold text-atlantia-ink/60 sm:text-base">
                        Encuentra productos de comercios verificados disponibles para {{ $zonaEntrega }}.
                    </p>
                    <span class="mt-4 block h-1 w-16 rounded-full bg-atlantia-wine" aria-hidden="true"></span>
                </div>
            </div>
        </section>

        <section class="mx-auto w-full max-w-[1680px] px-4 py-6 sm:px-6 lg:px-8 lg:py-8" aria-labelledby="categorias-disponibles">
            <div class="flex items-end justify-between gap-4">
                <div>
                    <h2 id="categorias-disponibles" class="text-lg font-black text-atlantia-ink sm:text-xl">Categorías disponibles</h2>
                    <p class="mt-1 text-xs font-semibold text-atlantia-ink/55">Selecciona una categoría para encontrar comercios y productos.</p>
                </div>
                <span class="hidden items-center gap-2 rounded-full bg-white px-4 py-2 text-xs font-black text-atlantia-wine shadow-[0_6px_18px_rgba(94,0,8,0.12)] ring-1 ring-atlantia-rose/15 sm:inline-flex">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M4 4h7v7H4V4ZM13 4h7v7h-7V4ZM4 13h7v7H4v-7ZM13 13h7v7h-7v-7Z" stroke="currentColor" stroke-width="2"/></svg>
                    {{ $categorias->count() }} categorías activas
                </span>
            </div>

            @if ($categorias->isEmpty())
                <div class="mt-5 rounded-lg border border-atlantia-rose/20 bg-white px-5 py-12 text-center shadow-sm">
                    <span class="mx-auto grid h-14 w-14 place-items-center rounded-full bg-atlantia-blush text-atlantia-wine">
                        <svg class="h-7 w-7" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M4 4h7v7H4V4ZM13 4h7v7h-7V4ZM4 13h7v7H4v-7ZM13 13h7v7h-7v-7Z" stroke="currentColor" stroke-width="1.7"/></svg>
                    </span>
                    <h3 class="mt-4 text-base font-black text-atlantia-ink">Aun no hay categorias disponibles</h3>
                    <p class="mt-1 text-sm font-semibold text-atlantia-ink/55">Las categorias activas apareceran aqui automaticamente.</p>
                </div>
            @else
                <div class="mt-5 grid grid-cols-2 gap-3 sm:grid-cols-3 sm:gap-4 lg:grid-cols-4 xl:grid-cols-6 wide:grid-cols-7">
                    @foreach ($categorias as $categoria)
                        @php
                            $clave = strtolower((string) $categoria->slug);
                            $imagenUrl = $categoria->imagenUrl();
                            $total = (int) $categoria->productos_publicados_total;
                        @endphp

                        <a
                            href="{{ route('catalogo.index', ['categoria_id' => $categoria->id]) }}#productos"
                            class="group relative flex flex-col overflow-hidden rounded-2xl bg-white shadow-[0_8px_24px_rgba(59,6,12,0.08)] ring-1 ring-atlantia-rose/10 transition duration-200 hover:-translate-y-1 hover:shadow-[0_18px_36px_rgba(59,6,12,0.14)] focus:outline-none focus:ring-2 focus:ring-atlantia-rose focus:ring-offset-2"
                            data-categoria="{{ $categoria->slug }}"
                        >
                            <div class="relative aspect-[4/3] overflow-hidden bg-white">
                                @if ($imagenUrl)
                                    <img src="{{ $imagenUrl }}" alt="" loading="lazy" decoding="async" class="h-full w-full object-contain p-3 pt-4 transition duration-300 group-hover:scale-105">
                                @else
                                    <div class="grid h-full w-full place-items-center bg-[radial-gradient(circle_at_45%_60%,#fbe3eb_0%,#fff7fa_45%,#ffffff_75%)] text-atlantia-wine/75">
                                        @include('cliente.categorias.partials.icono', ['clave' => $clave, 'clase' => 'h-14 w-14 transition duration-300 group-hover:scale-110 sm:h-16 sm:w-16'])
                                    </div>
                                @endif
                                {{-- Curva guinda de la esquina --}}
                                <svg class="pointer-events-none absolute right-0 top-0 h-[52%] w-[40%]" viewBox="0 0 100 100" preserveAspectRatio="none" aria-hidden="true">
                                    <path d="M14 0H100V78C93 44 64 10 14 0Z" fill="#5e0008"/>
                                    <path d="M14 0C64 10 93 44 100 78" stroke="#b8313f" stroke-width="3" fill="none" opacity=".55"/>
                                </svg>
                            </div>

                            <div class="flex flex-1 items-center gap-3 px-3 pb-4 pt-2 sm:px-4">
                                <span class="shrink-0 text-atlantia-wine">
                                    @include('cliente.categorias.partials.icono', ['clave' => $clave, 'clase' => 'h-7 w-7'])
                                </span>
                                <span class="min-w-0">
                                    <span class="block text-sm font-black leading-tight text-atlantia-ink sm:text-[15px]">{{ $categoria->nombre }}</span>
                                    <span class="mt-0.5 block text-xs font-semibold text-atlantia-ink/55">
                                        {{ $total > 0 ? $total.' '.($total === 1 ? 'producto' : 'productos') : 'Explorar categoría' }}
                                    </span>
                                </span>
                            </div>
                        </a>
                    @endforeach
                </div>
            @endif
        </section>

        <section class="mx-auto w-full max-w-[1680px] px-4 pb-8 sm:px-6 lg:px-8" aria-label="Beneficios de Atlantia Store">
            <div class="overflow-hidden rounded-xl bg-[linear-gradient(110deg,#3f0d1d_0%,#5e0008_55%,#a33559_100%)] px-4 py-4 text-white shadow-[0_10px_26px_rgba(59,6,12,0.16)] sm:px-6 sm:py-5 lg:grid lg:grid-cols-[1.45fr_repeat(4,1fr)] lg:items-center lg:gap-4 lg:px-8 lg:py-6">
                <div class="flex items-center gap-3 border-b border-white/15 pb-3 lg:border-b-0 lg:border-r lg:pb-0 lg:pr-6">
                    <span class="grid h-10 w-10 shrink-0 place-items-center rounded-lg border border-white/35 bg-white/10 lg:h-14 lg:w-14">
                        <svg class="h-6 w-6 lg:h-8 lg:w-8" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M3 15h12V6H3v9Zm12-6h3l3 3v3h-6V9ZM7 19a2 2 0 1 0 0-4 2 2 0 0 0 0 4Zm11 0a2 2 0 1 0 0-4 2 2 0 0 0 0 4Z" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round"/></svg>
                    </span>
                    <div>
                        <h2 class="text-base font-black lg:text-lg">Envío rápido y seguro</h2>
                        <p class="mt-0.5 hidden text-xs font-semibold leading-5 text-white/75 sm:block">Compra en comercios locales y recibe seguimiento de tu pedido.</p>
                    </div>
                </div>

                <div class="mt-3 grid grid-cols-2 gap-2 lg:contents">
                @foreach ([
                    ['title' => 'Comercios verificados', 'icon' => 'shield'],
                    ['title' => 'Pago seguro', 'icon' => 'lock'],
                    ['title' => 'Atención 24/7', 'icon' => 'support'],
                    ['title' => 'Garantía Atlantia', 'icon' => 'badge'],
                ] as $beneficio)
                    <div class="flex items-center gap-2 rounded-lg bg-white/5 px-2.5 py-2 sm:justify-center lg:flex-col lg:gap-3 lg:bg-transparent lg:p-0 lg:text-center">
                        <span class="grid h-8 w-8 shrink-0 place-items-center rounded-lg bg-white/10 text-white lg:h-10 lg:w-10">
                            @if ($beneficio['icon'] === 'shield')
                                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M12 3 19 6v5c0 4.5-2.8 8.4-7 10-4.2-1.6-7-5.5-7-10V6l7-3ZM9 12l2 2 4-5" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/></svg>
                            @elseif ($beneficio['icon'] === 'lock')
                                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M7 10V8a5 5 0 0 1 10 0v2M5 10h14v10H5V10Z" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round"/></svg>
                            @elseif ($beneficio['icon'] === 'support')
                                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M4 13v-2a8 8 0 0 1 16 0v2M4 13h3v6H5a1 1 0 0 1-1-1v-5ZM20 13h-3v6h2a1 1 0 0 0 1-1v-5Z" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round"/></svg>
                            @else
                                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="m12 3 2.2 2.2 3.1-.4.4 3.1L20 10l-2.3 2.2-.4 3.1-3.1-.4L12 17l-2.2-2.1-3.1.4-.4-3.1L4 10l2.3-2.1.4-3.1 3.1.4L12 3Zm-3 7 2 2 4-4" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/></svg>
                            @endif
                        </span>
                        <p class="text-[11px] font-black leading-4 sm:text-xs">{{ $beneficio['title'] }}</p>
                    </div>
                @endforeach
                </div>
            </div>
        </section>
    </div>
@endsection
