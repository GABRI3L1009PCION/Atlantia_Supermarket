@extends('layouts.marketplace')

@section('content')
    <div class="min-h-full bg-[#fffafb]">
        <section class="relative overflow-hidden border-b border-atlantia-rose/10 bg-[linear-gradient(110deg,#fffafb_0%,#fff5f8_58%,#f8e7ee_100%)]">
            <div class="pointer-events-none absolute inset-y-0 right-0 hidden w-1/2 opacity-35 lg:block" aria-hidden="true">
                <svg class="h-full w-full text-atlantia-rose" viewBox="0 0 640 170" fill="none" preserveAspectRatio="xMidYMid slice">
                    <path d="M12 144h610M54 144v-38h48v38m19 0V82h52v62m16 0v-25h36v25m26 0V94h54v50m18 0v-68h62v68m18 0v-42h48v42m27 0v-31h58v31" stroke="currentColor" stroke-width="2"/>
                    <path d="M120 82 147 55l27 27M323 76l31-36 31 36M492 113l28-24 27 24M563 48c0 26-25 47-25 47s-25-21-25-47a25 25 0 1 1 50 0Z" stroke="currentColor" stroke-width="2"/>
                    <circle cx="538" cy="48" r="8" stroke="currentColor" stroke-width="2"/>
                </svg>
            </div>

            <div class="relative mx-auto w-full max-w-7xl px-4 py-7 sm:px-6 lg:px-8 lg:py-9">
                <div class="max-w-2xl">
                    <div class="inline-flex items-center gap-2 text-[11px] font-black uppercase text-atlantia-wine">
                        <span class="grid h-7 w-7 place-items-center rounded-full bg-atlantia-blush">
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M4 4h7v7H4V4ZM13 4h7v7h-7V4ZM4 13h7v7H4v-7ZM13 13h7v7h-7v-7Z" stroke="currentColor" stroke-width="1.8"/></svg>
                        </span>
                        Marketplace Atlantia
                    </div>
                    <h1 class="mt-3 text-3xl font-black leading-tight text-atlantia-ink sm:text-4xl">
                        Explora nuestras <span class="text-atlantia-wine">categorias</span>
                    </h1>
                    <p class="mt-2 text-sm font-semibold text-atlantia-ink/60 sm:text-base">
                        Encuentra productos de comercios verificados disponibles para {{ $municipioActivo }}.
                    </p>
                </div>
            </div>
        </section>

        <section class="mx-auto w-full max-w-7xl px-4 py-6 sm:px-6 lg:px-8 lg:py-8" aria-labelledby="categorias-disponibles">
            <div class="flex items-end justify-between gap-4">
                <div>
                    <h2 id="categorias-disponibles" class="text-lg font-black text-atlantia-ink sm:text-xl">Categorias disponibles</h2>
                    <p class="mt-1 text-xs font-semibold text-atlantia-ink/55">Selecciona una categoria para encontrar comercios y productos.</p>
                </div>
                <span class="hidden rounded-full bg-atlantia-blush px-3 py-1.5 text-[10px] font-black text-atlantia-wine sm:inline-flex">
                    {{ $categorias->count() }} categorias activas
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
                <div class="mt-5 grid grid-cols-2 gap-3 sm:grid-cols-3 sm:gap-4 lg:grid-cols-4 xl:grid-cols-5">
                    @foreach ($categorias as $categoria)
                        @php
                            $categoryKey = strtolower((string) $categoria->slug);
                            $accentIndex = $loop->index % 6;
                            $accentStyles = [
                                ['circle' => 'border-atlantia-rose/25 bg-atlantia-blush/70 text-atlantia-wine shadow-[0_12px_28px_rgba(122,31,61,0.12)]', 'line' => 'bg-atlantia-wine'],
                                ['circle' => 'border-amber-200 bg-amber-50 text-amber-600 shadow-[0_12px_28px_rgba(217,119,6,0.10)]', 'line' => 'bg-amber-500'],
                                ['circle' => 'border-emerald-200 bg-emerald-50 text-emerald-600 shadow-[0_12px_28px_rgba(5,150,105,0.10)]', 'line' => 'bg-emerald-500'],
                                ['circle' => 'border-sky-200 bg-sky-50 text-sky-600 shadow-[0_12px_28px_rgba(2,132,199,0.10)]', 'line' => 'bg-sky-500'],
                                ['circle' => 'border-violet-200 bg-violet-50 text-violet-600 shadow-[0_12px_28px_rgba(124,58,237,0.10)]', 'line' => 'bg-violet-500'],
                                ['circle' => 'border-rose-200 bg-rose-50 text-rose-600 shadow-[0_12px_28px_rgba(225,29,72,0.10)]', 'line' => 'bg-rose-500'],
                            ][$accentIndex];
                        @endphp

                        <a
                            href="{{ route('comercios.index', ['categoria' => $categoria->id, 'municipio' => $municipioActivo]) }}"
                            class="group relative flex min-h-44 flex-col items-center justify-center overflow-hidden rounded-lg border border-atlantia-rose/15 bg-white px-3 py-5 text-center shadow-[0_8px_24px_rgba(63,13,29,0.06)] transition duration-200 hover:-translate-y-1 hover:border-atlantia-rose/35 hover:shadow-[0_16px_34px_rgba(63,13,29,0.12)] focus:outline-none focus:ring-2 focus:ring-atlantia-rose focus:ring-offset-2 sm:min-h-48"
                        >
                            <span class="absolute inset-x-0 top-0 h-0.5 {{ $accentStyles['line'] }} opacity-0 transition group-hover:opacity-100"></span>
                            <span class="grid h-20 w-20 place-items-center rounded-full border {{ $accentStyles['circle'] }} transition group-hover:scale-105 sm:h-24 sm:w-24">
                                @if (str_contains($categoryKey, 'abarrote') || str_contains($categoryKey, 'despensa'))
                                    <svg class="h-10 w-10 sm:h-12 sm:w-12" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M4 5h2l2 10h9l2-7H7M10 19.5a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0ZM19 19.5a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0Z" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                @elseif (str_contains($categoryKey, 'fruta') || str_contains($categoryKey, 'verdura'))
                                    <svg class="h-10 w-10 sm:h-12 sm:w-12" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M12 8c-4-3-8 0-8 5 0 4 3 8 6 8 1 0 1.4-.7 2-.7s1 .7 2 .7c3 0 6-4 6-8 0-5-4-8-8-5Zm0 0c0-3 2-5 5-5M12 7c-2-2-4-2-6-1" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                @elseif (str_contains($categoryKey, 'bebe'))
                                    <svg class="h-10 w-10 sm:h-12 sm:w-12" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M5 4h3l2 9h8l2-6H9M8 17a2 2 0 1 1-4 0 2 2 0 0 1 4 0Zm12 0a2 2 0 1 1-4 0 2 2 0 0 1 4 0ZM10 7h9" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                @elseif (str_contains($categoryKey, 'carne') || str_contains($categoryKey, 'marisco') || str_contains($categoryKey, 'pescado'))
                                    <svg class="h-10 w-10 sm:h-12 sm:w-12" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M4 12c3-5 8-7 13-4l3-3v6l-3-3c-5 7-10 5-13 4Zm0 0-2-3v6l2-3Zm9-2h.01" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                @elseif (str_contains($categoryKey, 'lacteo') || str_contains($categoryKey, 'huevo'))
                                    <svg class="h-10 w-10 sm:h-12 sm:w-12" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M8 3h5v4l2 3v10H6V10l2-3V3Zm0 5h5M18 8c-2 3-3 5-3 7a3 3 0 0 0 6 0c0-2-1-4-3-7Z" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round"/></svg>
                                @elseif (str_contains($categoryKey, 'bebida'))
                                    <svg class="h-10 w-10 sm:h-12 sm:w-12" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M9 3h6M10 3v4l-2 3v11h8V10l-2-3V3M8 12h8" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                @elseif (str_contains($categoryKey, 'congelado') || str_contains($categoryKey, 'refrigerado'))
                                    <svg class="h-10 w-10 sm:h-12 sm:w-12" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M12 2v20M4.2 6.5l15.6 11M4.2 17.5l15.6-11M9 4l3 3 3-3M9 20l3-3 3 3M4 10l4 2-4 2M20 10l-4 2 4 2" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                @elseif (str_contains($categoryKey, 'desayuno'))
                                    <svg class="h-10 w-10 sm:h-12 sm:w-12" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M4 8h13v6a5 5 0 0 1-5 5H9a5 5 0 0 1-5-5V8Zm13 2h2a3 3 0 0 1 0 6h-3M7 3v2m4-2v2m4-2v2" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                @elseif (str_contains($categoryKey, 'gourmet'))
                                    <svg class="h-10 w-10 sm:h-12 sm:w-12" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M6 3v7m-3-7v4a3 3 0 0 0 6 0V3M6 10v11M15 3v18m0-8c4-1 5-4 5-7V3h-5v10Z" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                @elseif (str_contains($categoryKey, 'panaderia') || str_contains($categoryKey, 'pasteleria'))
                                    <svg class="h-10 w-10 sm:h-12 sm:w-12" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M5 20c-2 0-3-1.5-3-3.5S3.5 13 5.5 13c0-2.3 1.7-4 4-4 .8-3 5-4 7-1.5 3 0 5.5 2.2 5.5 5.3 0 1.8-.8 3.2-2 4.2v3H5Zm4-7v3m4-4v4m4-5v5" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                @elseif (str_contains($categoryKey, 'papel') || str_contains($categoryKey, 'desechable'))
                                    <svg class="h-10 w-10 sm:h-12 sm:w-12" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M6 3h9a3 3 0 0 1 3 3v15H9a3 3 0 0 1-3-3V3Zm3 15a3 3 0 0 0 3 3M9 7h6M9 11h6" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                @elseif (str_contains($categoryKey, 'snack') || str_contains($categoryKey, 'dulce'))
                                    <svg class="h-10 w-10 sm:h-12 sm:w-12" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="m7 8-4-3v5l3 2-3 2v5l4-3m10-8 4-3v5l-3 2 3 2v5l-4-3M7 7h10v10H7V7Z" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                @elseif (str_contains($categoryKey, 'temporada') || str_contains($categoryKey, 'promocion'))
                                    <svg class="h-10 w-10 sm:h-12 sm:w-12" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M4 10h16v11H4V10Zm-1-5h18v5H3V5Zm9 0v16M7 5c-2-2-1-4 1-4 2.5 0 4 4 4 4m5 0c2-2 1-4-1-4-2.5 0-4 4-4 4" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"/></svg>
                                @elseif (str_contains($categoryKey, 'limpieza'))
                                    <svg class="h-10 w-10 sm:h-12 sm:w-12" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="m12 3 1.2 3.8L17 8l-3.8 1.2L12 13l-1.2-3.8L7 8l3.8-1.2L12 3ZM5 13l.8 2.2L8 16l-2.2.8L5 19l-.8-2.2L2 16l2.2-.8L5 13Zm13 1 1 2.9 3 1.1-3 1-1 3-1-3-3-1 3-1.1 1-2.9Z" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"/></svg>
                                @elseif (str_contains($categoryKey, 'higiene') || str_contains($categoryKey, 'cuidado'))
                                    <svg class="h-10 w-10 sm:h-12 sm:w-12" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M9 3h6v4l2 2v12H7V9l2-2V3Zm0 4h6M10 12h4M10 15h4" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                @elseif (str_contains($categoryKey, 'farmacia') || str_contains($categoryKey, 'salud') || str_contains($categoryKey, 'auxilio'))
                                    <svg class="h-10 w-10 sm:h-12 sm:w-12" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M9 3h6v6h6v6h-6v6H9v-6H3V9h6V3Z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/></svg>
                                @elseif (str_contains($categoryKey, 'mascota'))
                                    <svg class="h-10 w-10 sm:h-12 sm:w-12" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M8 11c-2 0-3-2-3-4 0-1.7 1-3 2.3-3C9 4 10 6 10 7.5 10 9.5 9 11 8 11Zm8 0c2 0 3-2 3-4 0-1.7-1-3-2.3-3C15 4 14 6 14 7.5c0 2 1 3.5 2 3.5Zm-4 0c-3 0-6 3-6 6 0 2 1.5 3 3 3 1.2 0 2-.7 3-.7s1.8.7 3 .7c1.5 0 3-1 3-3 0-3-3-6-6-6Z" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"/></svg>
                                @elseif (str_contains($categoryKey, 'local'))
                                    <svg class="h-10 w-10 sm:h-12 sm:w-12" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M12 21s7-6.1 7-12A7 7 0 1 0 5 9c0 5.9 7 12 7 12Zm0-9a3 3 0 1 1 0-6 3 3 0 0 1 0 6Z" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round"/></svg>
                                @else
                                    <svg class="h-10 w-10 sm:h-12 sm:w-12" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M4 4h7v7H4V4ZM13 4h7v7h-7V4ZM4 13h7v7H4v-7ZM13 13h7v7h-7v-7Z" stroke="currentColor" stroke-width="1.7"/></svg>
                                @endif
                            </span>

                            <h3 class="mt-4 line-clamp-2 text-sm font-black leading-tight text-atlantia-ink sm:text-base">{{ $categoria->nombre }}</h3>
                            <p class="mt-1 text-[10px] font-bold text-atlantia-ink/50 sm:text-xs">
                                @if ($categoria->productos_publicados_total > 0)
                                    {{ $categoria->productos_publicados_total }} {{ $categoria->productos_publicados_total === 1 ? 'producto' : 'productos' }}
                                @else
                                    Explorar categoria
                                @endif
                            </p>
                        </a>
                    @endforeach
                </div>
            @endif
        </section>

        <section class="mx-auto w-full max-w-7xl px-4 pb-8 sm:px-6 lg:px-8" aria-label="Beneficios de Atlantia Delivery">
            <div class="overflow-hidden rounded-lg bg-[linear-gradient(110deg,#3f0d1d_0%,#7a1f3d_55%,#a33559_100%)] px-5 py-6 text-white shadow-[0_14px_34px_rgba(63,13,29,0.18)] sm:px-7 lg:grid lg:grid-cols-[1.45fr_repeat(4,1fr)] lg:items-center lg:gap-4 lg:px-8">
                <div class="flex items-center gap-4 border-b border-white/15 pb-5 lg:border-b-0 lg:border-r lg:pb-0 lg:pr-6">
                    <span class="grid h-14 w-14 shrink-0 place-items-center rounded-lg border border-white/35 bg-white/10">
                        <svg class="h-8 w-8" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M3 15h12V6H3v9Zm12-6h3l3 3v3h-6V9ZM7 19a2 2 0 1 0 0-4 2 2 0 0 0 0 4Zm11 0a2 2 0 1 0 0-4 2 2 0 0 0 0 4Z" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round"/></svg>
                    </span>
                    <div>
                        <h2 class="text-lg font-black">Envio rapido y seguro</h2>
                        <p class="mt-1 text-xs font-semibold leading-5 text-white/75">Compra en comercios locales y recibe seguimiento de tu pedido.</p>
                    </div>
                </div>

                @foreach ([
                    ['title' => 'Comercios verificados', 'icon' => 'shield'],
                    ['title' => 'Pago seguro', 'icon' => 'lock'],
                    ['title' => 'Atencion 24/7', 'icon' => 'support'],
                    ['title' => 'Garantia Atlantia', 'icon' => 'badge'],
                ] as $beneficio)
                    <div class="flex items-center gap-3 border-b border-white/10 py-4 last:border-b-0 sm:justify-center lg:flex-col lg:border-b-0 lg:py-0 lg:text-center">
                        <span class="grid h-10 w-10 shrink-0 place-items-center rounded-lg bg-white/10 text-white">
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
                        <p class="text-xs font-black leading-4">{{ $beneficio['title'] }}</p>
                    </div>
                @endforeach
            </div>
        </section>
    </div>
@endsection
