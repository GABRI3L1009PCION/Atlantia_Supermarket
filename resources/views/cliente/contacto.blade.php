@extends('layouts.marketplace', ['title' => 'Contacto | Atlantia Supermarket'])

@section('content')
    @php
        $isClient = auth()->check() && auth()->user()?->hasRole('cliente');
        $isVendor = auth()->check() && auth()->user()?->hasRole('vendedor');
        $supportHref = $isClient ? route('cliente.pedidos.index') : route('login');
        $vendorHref = $isVendor ? route('vendedor.dashboard') : route('vendedor.solicitar.create');
    @endphp

    <section class="bg-[#fff8fb] px-4 py-8 sm:px-6 lg:px-8">
        <div class="mx-auto max-w-7xl space-y-4">
            <header class="grid gap-8 lg:grid-cols-[minmax(0,1fr)_460px] lg:items-center">
                <div>
                    <p class="text-sm font-black uppercase tracking-[0.08em] text-atlantia-rose">Contacto Atlantia</p>
                    <h1 class="mt-3 text-3xl font-black leading-tight text-atlantia-ink sm:text-4xl">
                        &iquest;C&oacute;mo podemos ayudarte?
                    </h1>
                    <p class="mt-3 text-base leading-7 text-atlantia-ink/65">
                        Elige la opci&oacute;n que mejor se adapte a tu situaci&oacute;n.
                    </p>
                </div>

                <div class="relative hidden min-h-[150px] items-center justify-center lg:flex" aria-hidden="true">
                    <div class="absolute inset-x-6 bottom-3 h-20 rounded-full bg-atlantia-blush/70 blur-sm"></div>
                    <div class="relative flex items-center gap-7">
                        <div class="grid h-16 w-20 place-items-center rounded-2xl bg-gradient-to-br from-atlantia-rose to-atlantia-wine text-white shadow-xl shadow-atlantia-rose/25">
                            <svg class="h-10 w-12" viewBox="0 0 64 44" fill="none">
                                <path d="M9 12h46M9 28h34" stroke="currentColor" stroke-width="6" stroke-linecap="round"/>
                                <path d="M18 39 27 28" stroke="currentColor" stroke-width="5" stroke-linecap="round"/>
                            </svg>
                        </div>
                        <div class="grid h-24 w-32 place-items-center rounded-2xl bg-white text-atlantia-rose shadow-xl shadow-atlantia-rose/10 ring-1 ring-atlantia-rose/10">
                            <svg class="h-16 w-24" viewBox="0 0 96 64" fill="none">
                                <rect x="8" y="12" width="80" height="46" rx="7" fill="white" stroke="currentColor" stroke-width="2"/>
                                <path d="M10 17 48 42 86 17" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"/>
                                <circle cx="80" cy="18" r="7" fill="currentColor"/>
                            </svg>
                        </div>
                        <div class="text-atlantia-ink">
                            <svg class="h-32 w-36" viewBox="0 0 144 128" fill="none">
                                <path d="M35 66C35 31 58 14 72 14s37 17 37 52" stroke="#34202a" stroke-width="7" stroke-linecap="round"/>
                                <rect x="18" y="58" width="28" height="43" rx="14" fill="#8d1b48"/>
                                <rect x="98" y="58" width="28" height="43" rx="14" fill="#d36a91"/>
                                <path d="M109 97c-5 16-17 21-34 21" stroke="#34202a" stroke-width="6" stroke-linecap="round"/>
                                <circle cx="72" cy="118" r="5" fill="#34202a"/>
                            </svg>
                        </div>
                    </div>
                </div>
            </header>

            <div class="grid gap-5 lg:grid-cols-2">
                <section class="rounded-xl border border-atlantia-rose/20 bg-white p-5 shadow-sm sm:p-6">
                    <div class="flex gap-4">
                        <span class="grid h-14 w-14 shrink-0 place-items-center rounded-xl bg-atlantia-blush text-atlantia-wine">
                            <svg class="h-8 w-8" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="M4 13a8 8 0 0 1 16 0" />
                                <path d="M4 13v3a2 2 0 0 0 2 2h1v-6H6a2 2 0 0 0-2 2Z" />
                                <path d="M20 13v3a2 2 0 0 1-2 2h-1v-6h1a2 2 0 0 1 2 2Z" />
                                <path d="M16 18c0 2-2 3-4 3" />
                            </svg>
                        </span>
                        <div>
                            <h2 class="text-2xl font-black text-atlantia-ink">Soporte para clientes</h2>
                            <p class="mt-2 max-w-xl text-sm leading-6 text-atlantia-ink/65">
                                &iquest;Dudas sobre un pedido, entrega o tu cuenta? Aqu&iacute; encontrar&aacute;s respuestas a las preguntas m&aacute;s comunes.
                            </p>
                        </div>
                    </div>

                    <div class="mt-5 space-y-3">
                        <article class="flex items-center gap-4 rounded-lg border border-atlantia-rose/20 bg-white px-4 py-4">
                            <span class="grid h-11 w-11 shrink-0 place-items-center rounded-full bg-atlantia-blush text-atlantia-wine">
                                <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <path d="m21 16-9 5-9-5" />
                                    <path d="m21 12-9 5-9-5" />
                                    <path d="m12 3 9 5-9 5-9-5 9-5Z" />
                                </svg>
                            </span>
                            <div>
                                <h3 class="font-black text-atlantia-ink">Pedidos y entregas</h3>
                                <p class="text-sm text-atlantia-ink/60">Consulta y seguimiento de pedidos</p>
                            </div>
                        </article>

                        <article class="flex items-center gap-4 rounded-lg border border-atlantia-rose/20 bg-white px-4 py-4">
                            <span class="grid h-11 w-11 shrink-0 place-items-center rounded-full bg-emerald-50 text-emerald-700">
                                <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10Z" />
                                    <path d="m9 12 2 2 4-5" />
                                </svg>
                            </span>
                            <div>
                                <h3 class="font-black text-atlantia-ink">Cuenta y seguridad</h3>
                                <p class="text-sm text-atlantia-ink/60">Datos, permisos y seguridad</p>
                            </div>
                        </article>
                    </div>

                    <div class="mt-5 grid gap-3 sm:grid-cols-2">
                        <a href="{{ $supportHref }}" class="inline-flex h-12 items-center justify-center gap-3 rounded-lg bg-atlantia-wine px-5 text-sm font-black text-white shadow-lg shadow-atlantia-wine/15 transition hover:bg-atlantia-wine/90">
                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2" />
                                <circle cx="12" cy="7" r="4" />
                            </svg>
                            {!! $isClient ? 'Ver mis pedidos' : 'Iniciar sesi&oacute;n' !!}
                        </a>
                        <a href="{{ route('catalogo.index') }}" class="inline-flex h-12 items-center justify-center gap-3 rounded-lg border border-atlantia-rose/35 bg-white px-5 text-sm font-black text-atlantia-wine transition hover:bg-atlantia-blush">
                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4Z" />
                                <path d="M3 6h18" />
                                <path d="M16 10a4 4 0 0 1-8 0" />
                            </svg>
                            Seguir comprando
                        </a>
                    </div>
                </section>

                <section class="rounded-xl border border-atlantia-rose/20 bg-white p-5 shadow-sm sm:p-6">
                    <div class="flex gap-4">
                        <span class="grid h-14 w-14 shrink-0 place-items-center rounded-xl bg-atlantia-blush text-atlantia-wine">
                            <svg class="h-8 w-8" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="M4 10h16l-1-5H5l-1 5Z" />
                                <path d="M6 10v10h12V10" />
                                <path d="M9 20v-6h6v6" />
                            </svg>
                        </span>
                        <div>
                            <h2 class="text-2xl font-black text-atlantia-ink">Oportunidad de vender</h2>
                            <p class="mt-2 max-w-xl text-sm leading-6 text-atlantia-ink/65">
                                Conecta tu negocio con miles de clientes. Vende tus productos facturando de forma independiente.
                            </p>
                        </div>
                    </div>

                    <h3 class="mt-6 text-sm font-black text-atlantia-ink">Beneficios principales</h3>
                    <div class="mt-3 grid gap-4 sm:grid-cols-2">
                        <article class="flex gap-4 rounded-lg border border-atlantia-rose/20 bg-white p-4">
                            <span class="grid h-12 w-12 shrink-0 place-items-center rounded-full bg-atlantia-blush text-atlantia-wine">
                                <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <path d="M2 12s4-7 10-7 10 7 10 7-4 7-10 7S2 12 2 12Z" />
                                    <circle cx="12" cy="12" r="3" />
                                </svg>
                            </span>
                            <div>
                                <h4 class="font-black text-atlantia-ink">Visibilidad</h4>
                                <p class="mt-3 text-sm leading-6 text-atlantia-ink/60">
                                    Llega a clientes de toda la regi&oacute;n y haz crecer tu negocio.
                                </p>
                            </div>
                        </article>

                        <article class="flex gap-4 rounded-lg border border-atlantia-rose/20 bg-white p-4">
                            <span class="grid h-12 w-12 shrink-0 place-items-center rounded-full bg-emerald-50 text-emerald-700">
                                <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <circle cx="12" cy="12" r="9" />
                                    <path d="M12 7v10" />
                                    <path d="M15 9.5c-.6-.8-1.6-1.3-3-1.3-1.8 0-3 .9-3 2.3s1.2 2 3 2.3c1.8.3 3 .9 3 2.3s-1.2 2.5-3.2 2.5c-1.3 0-2.5-.4-3.3-1.3" />
                                </svg>
                            </span>
                            <div>
                                <h4 class="font-black text-atlantia-ink">Comisi&oacute;n justa</h4>
                                <p class="mt-3 text-sm leading-6 text-atlantia-ink/60">
                                    Pagos claros, puntuales y con comisiones competitivas.
                                </p>
                            </div>
                        </article>
                    </div>

                    <a href="{{ $vendorHref }}" class="mt-4 inline-flex h-12 w-full items-center justify-center gap-3 rounded-lg bg-atlantia-wine px-5 text-sm font-black text-white shadow-lg shadow-atlantia-wine/15 transition hover:bg-atlantia-wine/90">
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M4 10h16l-1-5H5l-1 5Z" />
                            <path d="M6 10v10h12V10" />
                        </svg>
                        {!! $isVendor ? 'Ir a mi panel de vendedor' : 'Reg&iacute;strate como vendedor' !!}
                    </a>
                </section>
            </div>

            <section class="rounded-xl border border-atlantia-rose/20 bg-white p-5 shadow-sm sm:p-6">
                <h2 class="text-xl font-black text-atlantia-ink">Preguntas frecuentes</h2>

                <div class="mt-4 flex gap-6 border-b border-atlantia-rose/15 text-sm font-semibold">
                    <span class="border-b-2 border-atlantia-wine px-4 pb-3 text-atlantia-wine">Antes de registrarte</span>
                    <span class="px-4 pb-3 text-atlantia-ink/60">Despu&eacute;s de registrarte</span>
                </div>

                <div class="mt-3 grid gap-3 lg:grid-cols-2">
                    @foreach ([
                        ['q' => '&iquest;Qu&eacute; documentos necesito?', 'a' => 'DPI, NIT o pasaporte vigente, datos de contacto y una cuenta bancaria a tu nombre.'],
                        ['q' => '&iquest;C&oacute;mo funcionan los pagos?', 'a' => 'Los pagos se liquidan seg&uacute;n tu configuraci&oacute;n y las ventas confirmadas en el sistema.'],
                        ['q' => '&iquest;Puedo vender desde casa?', 'a' => 'S&iacute;, siempre que puedas preparar, entregar y respaldar tus productos correctamente.'],
                        ['q' => '&iquest;Cu&aacute;l es la comisi&oacute;n?', 'a' => 'La comisi&oacute;n se define seg&uacute;n el tipo de vendedor, categor&iacute;a y acuerdo activo.'],
                        ['q' => '&iquest;Cu&aacute;ndo me responden?', 'a' => 'El equipo revisa las solicitudes y te responde cuando la informaci&oacute;n est&eacute; completa.'],
                        ['q' => '&iquest;Si tengo problema?', 'a' => 'Puedes contactar soporte para que revisen tu caso y te indiquen el siguiente paso.'],
                    ] as $faq)
                        <details class="group rounded-lg border border-atlantia-rose/20 bg-white px-4 py-3">
                            <summary class="flex cursor-pointer list-none items-center justify-between gap-4 text-sm font-black text-atlantia-ink">
                                <span>{!! $faq['q'] !!}</span>
                                <svg class="h-4 w-4 shrink-0 transition group-open:rotate-180" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <path d="m6 9 6 6 6-6" />
                                </svg>
                            </summary>
                            <p class="mt-3 text-sm leading-6 text-atlantia-ink/60">{!! $faq['a'] !!}</p>
                        </details>
                    @endforeach
                </div>
            </section>

            <section class="flex flex-col gap-4 rounded-xl border border-atlantia-rose/20 bg-atlantia-blush/45 p-5 shadow-sm sm:flex-row sm:items-center sm:justify-between">
                <div class="flex items-center gap-4">
                    <span class="grid h-10 w-10 shrink-0 place-items-center rounded-full bg-white text-atlantia-wine">
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M21 15a4 4 0 0 1-4 4H8l-5 3V7a4 4 0 0 1 4-4h10a4 4 0 0 1 4 4Z" />
                        </svg>
                    </span>
                    <div>
                        <h2 class="font-black text-atlantia-ink">&iquest;No encontraste lo que buscabas?</h2>
                        <p class="text-sm text-atlantia-ink/65">Nuestro equipo est&aacute; listo para ayudarte.</p>
                    </div>
                </div>
                <a href="mailto:ayuda@atlantia.gt" class="inline-flex h-11 items-center justify-center gap-3 rounded-lg border border-atlantia-rose/40 bg-white px-5 text-sm font-black text-atlantia-wine transition hover:bg-atlantia-blush">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M21 15a4 4 0 0 1-4 4H8l-5 3V7a4 4 0 0 1 4-4h10a4 4 0 0 1 4 4Z" />
                    </svg>
                    Contactar soporte
                </a>
            </section>
        </div>
    </section>
@endsection
