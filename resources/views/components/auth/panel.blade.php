@props([
    'titulo',
    'subtitulo' => null,
    'etiqueta' => null,
])

{{--
    Pantallas de acceso con el estilo de Atlantia Finance: mitad izquierda en
    guinda profundo con el logo iluminado, mitad derecha clara con el formulario.
    Se usa en login, registro, recuperación, doble factor y creación de contraseña.
--}}
@php
    $logoPath = collect([
        'images/logo-atlantia-delivery.png',
        'images/logo-atlantia-delivery.svg',
        'images/logo.png',
        'images/atlantia-logo.svg',
    ])->first(fn (string $path): bool => file_exists(public_path($path))) ?? 'images/atlantia-logo.svg';
@endphp

<section class="grid flex-1 bg-[#f7f5f3] lg:grid-cols-[46%_54%]">
    {{-- Panel de marca --}}
    <aside class="relative hidden overflow-hidden bg-[radial-gradient(120%_90%_at_85%_100%,#6a0008_0%,transparent_60%),linear-gradient(160deg,#3a0004_0%,#4d0006_45%,#5e0008_100%)] px-12 py-8 text-white lg:flex lg:flex-col">
        <div class="pointer-events-none absolute -left-40 -top-40 h-[30rem] w-[30rem] rounded-full border border-white/[0.07]" aria-hidden="true"></div>
        <div class="pointer-events-none absolute -left-20 -top-24 h-[30rem] w-[30rem] rounded-full border border-white/[0.07]" aria-hidden="true"></div>

        <p class="relative z-10 text-sm font-bold uppercase tracking-[0.3em] text-white/90">Atlantia Systems</p>

        <div class="relative z-10 m-auto flex w-full max-w-md flex-col items-center text-center">
            <img
                src="{{ asset($logoPath) }}"
                alt="Atlantia Store"
                class="h-auto w-full max-w-[18rem] object-contain drop-shadow-[0_0_28px_rgba(255,70,90,0.35)] drop-shadow-[0_14px_30px_rgba(0,0,0,0.45)]"
            >
            <span class="mt-7 h-px w-24 bg-white/25" aria-hidden="true"></span>
            <p class="mt-6 text-sm font-semibold uppercase tracking-[0.24em] text-white">Tu súper y tus comercios, a domicilio</p>
            <p class="mt-3 text-[0.95rem] leading-7 text-white/70">Compra en los negocios de Puerto Barrios y Santo Tomás, paga seguro y sigue tu pedido en el mapa hasta tu puerta.</p>
        </div>

        <div class="relative z-10 grid grid-cols-3 gap-3 text-xs font-semibold text-white/85">
            <span class="flex items-center gap-2">
                <svg class="h-4 w-4 text-white/70" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M12 3 19 6v5c0 4.5-2.8 8.4-7 10-4.2-1.6-7-5.5-7-10V6l7-3Zm-3 9 2 2 4-5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>
                Pago seguro
            </span>
            <span class="flex items-center justify-center gap-2">
                <svg class="h-4 w-4 text-white/70" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M4 10h16v10H4V10Zm-1-5h18l-1 5H4L3 5Zm6 15v-5h6v5" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/></svg>
                Comercios locales
            </span>
            <span class="flex items-center justify-end gap-2">
                <svg class="h-4 w-4 text-white/70" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M3 15h12V6H3v9Zm12-6h3l3 3v3h-6V9ZM7 19a2 2 0 1 0 0-4 2 2 0 0 0 0 4Zm11 0a2 2 0 1 0 0-4 2 2 0 0 0 0 4Z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/></svg>
                A domicilio
            </span>
        </div>
    </aside>

    {{-- Formulario --}}
    <div class="flex px-5 pb-6 sm:px-10 lg:px-16 lg:py-6">
        <div class="mx-auto w-full max-w-[34rem] lg:m-auto">
            {{-- En celular el titulo va en una franja guinda que continua el header y la tarjeta se monta encima. --}}
            <div class="-mx-5 rounded-b-[2rem] bg-[radial-gradient(90%_120%_at_100%_0%,#7a0a14_0%,transparent_65%),linear-gradient(180deg,#4d0006_0%,#5e0008_100%)] px-6 pb-14 pt-6 sm:-mx-10 sm:px-10 lg:mx-0 lg:mb-4 lg:rounded-none lg:bg-none lg:p-0" data-auth-franja>
                @if ($etiqueta)
                    <span class="mb-3 inline-flex items-center gap-2 rounded-full bg-white/15 px-3 py-1 text-xs font-bold text-white lg:bg-[#4d0006]/[0.07] lg:text-[#4d0006]">
                        <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M20 6 9 17l-5-5" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"/></svg>
                        {{ $etiqueta }}
                    </span>
                @endif
                <h1 class="text-[1.75rem] font-bold tracking-tight text-white sm:text-3xl lg:text-[#1f1416]">{{ $titulo }}</h1>
                @if ($subtitulo)
                    <p class="mt-1.5 text-[0.95rem] leading-6 text-white/75 lg:mt-2 lg:text-[#5b5052]">{{ $subtitulo }}</p>
                @endif
            </div>

            <div class="auth-tarjeta relative -mt-10 rounded-2xl border border-[#e8e2df] bg-white p-5 shadow-[0_14px_34px_rgba(31,20,22,0.12)] lg:mt-0 lg:shadow-[0_10px_30px_rgba(31,20,22,0.06)]">
                {{ $slot }}
            </div>

            @isset($pie)
                {{ $pie }}
            @endisset
            <p class="mt-3 text-center text-xs leading-5 text-[#8a7f80]">Protegido con cifrado, control de sesiones y límite de intentos de acceso.</p>
        </div>
    </div>
</section>
