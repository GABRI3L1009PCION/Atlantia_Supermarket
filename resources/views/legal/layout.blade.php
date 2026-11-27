{{--
    Plantilla comun de los documentos legales.
    Variables: $titulo, $resumen, $secciones (id => titulo), $documento ('terminos' | 'privacidad').
    Con ?from=checkout muestra la miga "Checkout" y el boton "Volver al checkout".
--}}
@extends('layouts.marketplace')

@section('content')
    @php
        $legal = config('atlantia.legal');
        $vigencia = \Illuminate\Support\Carbon::parse($legal['vigencia'] ?? now())->locale('es')->translatedFormat('j \d\e F \d\e Y');
        $desdeCheckout = request('from') === 'checkout';
        $esTerminos = $documento === 'terminos';
        $otro = $esTerminos
            ? ['ruta' => 'legal.privacidad', 'texto' => 'Ver Política de Privacidad']
            : ['ruta' => 'legal.terminos', 'texto' => 'Ver Términos y Condiciones'];
        $volver = $desdeCheckout
            ? ['url' => route('cliente.checkout.create'), 'texto' => 'Volver al checkout']
            : ['url' => route('home'), 'texto' => 'Volver a la tienda'];
        $secciones['contacto-legal'] = 'Contacto';
    @endphp

    <section class="relative overflow-hidden bg-[linear-gradient(180deg,#ffffff_0%,#fbeef3_20rem,#fdf8fa_100%)] px-3 pb-14 pt-3 sm:px-6 lg:px-8">
        <div class="mx-auto max-w-7xl">
            {{-- Migas de pan --}}
            <nav class="flex items-center gap-2 text-sm text-atlantia-ink/65" aria-label="Ruta">
                <a href="{{ route('home') }}" class="text-atlantia-ink/60 hover:text-atlantia-wine" aria-label="Inicio"><x-legal.icon name="home" class="h-4 w-4" /></a>
                <x-legal.icon name="chevron-right" class="h-3.5 w-3.5 text-atlantia-ink/40" />
                @if ($desdeCheckout)
                    <a href="{{ route('cliente.checkout.create') }}" class="hover:text-atlantia-wine">Checkout</a>
                    <x-legal.icon name="chevron-right" class="h-3.5 w-3.5 text-atlantia-ink/40" />
                @endif
                <span class="font-black text-atlantia-wine">{{ $esTerminos ? 'Términos y Condiciones' : 'Política de Privacidad' }}</span>
            </nav>

            {{-- Encabezado --}}
            <header class="relative mt-3 overflow-hidden rounded-2xl border border-atlantia-rose/15 bg-white p-4 shadow-[0_18px_44px_rgba(51,5,9,0.07)] sm:p-6">
                <div class="pointer-events-none absolute -right-16 -top-24 h-56 w-80 rounded-full bg-atlantia-blush/60" aria-hidden="true"></div>
                <div class="pointer-events-none absolute -right-4 -top-32 h-56 w-56 rounded-full bg-atlantia-blush/80" aria-hidden="true"></div>

                <div class="relative grid gap-4 lg:grid-cols-[auto_minmax(0,1fr)_auto] lg:items-center lg:gap-8">
                    <div class="grid grid-cols-[96px_minmax(0,1fr)] items-start gap-4 sm:grid-cols-[150px_minmax(0,1fr)] lg:contents">
                        {{-- Icono del documento --}}
                        <div class="flex items-center gap-4 lg:gap-8">
                            <div class="relative grid h-24 w-24 shrink-0 place-items-center rounded-full bg-atlantia-blush/80 sm:h-36 sm:w-36">
                                <x-legal.icon name="doc" class="h-14 w-14 text-atlantia-wine sm:h-20 sm:w-20" />
                                <span class="absolute bottom-4 right-3 grid h-9 w-9 place-items-center rounded-full bg-atlantia-wine text-white ring-4 ring-white sm:bottom-6 sm:right-5 sm:h-11 sm:w-11">
                                    <x-legal.icon :name="$esTerminos ? 'shield' : 'lock'" class="h-5 w-5 sm:h-6 sm:w-6" />
                                </span>
                            </div>
                            <span class="hidden h-32 w-px bg-atlantia-rose/30 lg:block" aria-hidden="true"></span>
                        </div>

                        <div class="min-w-0 border-l border-atlantia-rose/25 pl-4 lg:border-0 lg:pl-0">
                            <p class="text-xs font-black uppercase tracking-[0.06em] text-atlantia-wine">Documento legal</p>
                            <h1 class="mt-1 text-2xl font-black leading-tight text-atlantia-ink sm:text-4xl">{{ $titulo }}</h1>
                            <p class="mt-2 max-w-3xl text-sm leading-6 text-atlantia-ink/75 sm:text-base">{{ $resumen }}</p>

                            <div class="mt-3 hidden flex-wrap gap-2 lg:flex">
                                @include('legal.partials.chips')
                            </div>
                        </div>
                    </div>

                    <div class="flex flex-wrap justify-center gap-2 lg:hidden">
                        @include('legal.partials.chips', ['soloEtiquetas' => true])
                    </div>

                    <div class="grid gap-2 sm:grid-cols-2 lg:flex lg:flex-col lg:items-end lg:self-end">
                        <a href="{{ route($otro['ruta'], $desdeCheckout ? ['from' => 'checkout'] : []) }}" class="inline-flex h-11 items-center justify-center gap-2 rounded-full border border-atlantia-rose/40 bg-white px-5 text-sm font-black text-atlantia-wine transition hover:bg-atlantia-blush lg:hidden">
                            <x-legal.icon name="external" class="h-4 w-4" />
                            {{ $otro['texto'] }}
                        </a>
                        <a href="{{ $volver['url'] }}" class="inline-flex h-11 items-center justify-center gap-2 rounded-full bg-atlantia-wine px-6 text-sm font-black text-white shadow-[0_10px_24px_rgba(107,15,47,0.28)] transition hover:bg-atlantia-wine-700">
                            <x-legal.icon name="arrow-left" class="h-4 w-4" />
                            {{ $volver['texto'] }}
                        </a>
                    </div>
                </div>
            </header>

            <div class="mt-4 grid gap-4 lg:mt-6 lg:grid-cols-[300px_minmax(0,1fr)] lg:gap-6">
                {{-- Indice: desplegable en celular, fijo al lado en computadora --}}
                <details class="group h-fit rounded-2xl border border-atlantia-rose/15 bg-white shadow-sm lg:sticky lg:top-28" open data-legal-toc>
                    <summary class="flex cursor-pointer list-none items-center gap-3 px-4 py-3 [&::-webkit-details-marker]:hidden lg:pointer-events-none">
                        <span class="grid h-9 w-9 place-items-center rounded-xl bg-atlantia-blush text-atlantia-wine"><x-legal.icon name="list" class="h-5 w-5" /></span>
                        <span class="flex-1 text-lg font-black text-atlantia-ink">Contenido</span>
                        <x-legal.icon name="chevron" class="h-5 w-5 text-atlantia-ink/60 transition group-open:rotate-180 lg:hidden" />
                    </summary>
                    <ol class="max-h-[60vh] space-y-0.5 overflow-y-auto px-2 pb-3 lg:max-h-[calc(100vh-12rem)]">
                        @foreach ($secciones as $id => $nombre)
                            <li>
                                <a href="#{{ $id }}" data-toc-link="{{ $id }}" class="group/link flex items-center gap-3 rounded-lg px-2 py-1.5 text-sm text-atlantia-ink/80 transition hover:bg-atlantia-blush/50 hover:text-atlantia-wine data-[active]:bg-atlantia-blush/70 data-[active]:font-black data-[active]:text-atlantia-wine">
                                    <span class="grid h-7 w-7 shrink-0 place-items-center rounded-full bg-atlantia-blush/70 text-xs font-black text-atlantia-wine group-data-[active]/link:bg-white">{{ $loop->iteration }}</span>
                                    <span class="flex-1">{{ $nombre }}</span>
                                    <x-legal.icon name="chevron-right" class="h-4 w-4 text-atlantia-ink/40 lg:hidden" />
                                </a>
                            </li>
                        @endforeach
                    </ol>
                </details>

                {{-- Documento --}}
                <article class="space-y-3 lg:space-y-0 lg:divide-y lg:divide-atlantia-rose/15 lg:rounded-2xl lg:border lg:border-atlantia-rose/15 lg:bg-white lg:px-8 lg:py-3 lg:shadow-sm">
                    @yield('documento')

                    @php
                        $contactoLineas = [];
                        $contactoLineas[] = '<strong>Titular:</strong> '.e($legal['razon_social'] ?: $legal['nombre_comercial']);
                        if ($legal['nit']) $contactoLineas[] = '<strong>NIT:</strong> '.e($legal['nit']);
                        $contactoLineas[] = '<strong>Domicilio:</strong> '.e($legal['domicilio']);
                        $correoContacto = ! $esTerminos && $legal['email_privacidad'] ? $legal['email_privacidad'] : $legal['email'];
                        if ($correoContacto) $contactoLineas[] = '<strong>Correo:</strong> <a href="mailto:'.e($correoContacto).'">'.e($correoContacto).'</a>';
                        if (config('atlantia.support.phone')) $contactoLineas[] = '<strong>Teléfono de soporte:</strong> '.e(config('atlantia.support.phone')).' ('.e(config('atlantia.support.hours')).')';
                    @endphp
                    <x-legal.section id="contacto-legal" :n="count($secciones)" title="Contacto" icon="mail"
                        callout="<strong>Contáctanos</strong> también desde la sección <a href='{{ route('contacto') }}'>Contacto</a> de la plataforma.">
                        <p>Para consultas sobre este documento, reclamos o solicitudes sobre tus datos personales:</p>
                        <ul>
                            @foreach ($contactoLineas as $linea)
                                <li>{!! $linea !!}</li>
                            @endforeach
                        </ul>
                    </x-legal.section>
                </article>
            </div>
        </div>
    </section>
@endsection

@push('scripts')
    <script @nonce>
        (() => {
            const links = [...document.querySelectorAll('[data-toc-link]')];
            const toc = document.querySelector('[data-legal-toc]');
            if (!links.length) return;

            /* En celular el indice inicia abierto pero se cierra al elegir una seccion. */
            const mobile = window.matchMedia('(max-width: 1023px)');
            links.forEach((link) => link.addEventListener('click', () => {
                if (mobile.matches && toc) toc.open = false;
            }));

            const setActive = (id) => links.forEach((link) => link.toggleAttribute('data-active', link.dataset.tocLink === id));
            setActive(links[0].dataset.tocLink);

            const observer = new IntersectionObserver((entries) => {
                const visible = entries.filter((entry) => entry.isIntersecting).sort((a, b) => a.boundingClientRect.top - b.boundingClientRect.top);
                if (visible[0]) setActive(visible[0].target.id);
            }, { rootMargin: '-120px 0px -60% 0px' });

            document.querySelectorAll('[data-legal-section]').forEach((section) => observer.observe(section));
        })();
    </script>
@endpush
