<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    @include('layouts.partials.favicon')
    <title>{{ $title ?? 'Acceso Atlantia Store' }}</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles(['nonce' => request()->attributes->get('csp_nonce')])
</head>
<body class="min-h-screen overflow-x-hidden bg-atlantia-blush text-atlantia-ink antialiased">
    @php
        /* Mismo logo que el header de la tienda. */
        $logoPath = collect([
            'images/logo-atlantia-delivery.png',
            'images/logo-atlantia-delivery.svg',
            'images/logo.png',
            'images/atlantia-logo.svg',
        ])->first(fn (string $path): bool => file_exists(public_path($path))) ?? 'images/atlantia-logo.svg';

        /* Si la vista define su propio contenedor, se usa tal cual y no se mezcla con el estilo por defecto. */
        $shellClass = trim($__env->yieldContent('guestShellClass', ''));
        $shellClass = $shellClass !== ''
            ? $shellClass
            : 'w-full rounded-2xl border border-atlantia-rose/25 bg-white/95 shadow-[0_24px_70px_rgba(127,18,32,0.16)] backdrop-blur';
    @endphp

    {{-- En pantallas bajas el contenido se desplaza en lugar de recortarse. --}}
    <main class="relative flex min-h-screen min-h-[100dvh] items-start justify-center px-3 py-4 sm:items-center sm:px-4 sm:py-6 lg:px-6">
        <div class="pointer-events-none fixed inset-0 -z-10 bg-[linear-gradient(135deg,#fff_0%,#fff7fa_38%,#f7dce8_100%)]"></div>
        <div class="pointer-events-none fixed inset-x-0 bottom-0 -z-10 h-1/2 bg-[linear-gradient(140deg,transparent_0%,rgba(255,255,255,0.72)_38%,rgba(127,18,32,0.08)_100%)]"></div>

        <section class="{{ $shellClass }} {{ trim($__env->yieldContent('guestMaxWidth', 'max-w-3xl')) }} min-w-0 {{ trim($__env->yieldContent('guestPadding', 'px-5 py-7 sm:px-8 sm:py-9 lg:px-12 lg:py-10')) }}">
            @hasSection('guestActions')
                <div class="mb-5">
                    @yield('guestActions')
                </div>
            @endif

            <a href="{{ route('home') }}" class="mx-auto block w-fit {{ trim($__env->yieldContent('guestLogoClass')) === 'hidden' ? 'hidden' : '' }}" aria-label="Atlantia Store">
                <img
                    src="{{ asset($logoPath) }}"
                    alt="Atlantia Store"
                    class="{{ trim($__env->yieldContent('guestLogoClass', 'h-12 w-auto max-w-full object-contain sm:h-16')) }}"
                >
            </a>

            <div class="{{ trim($__env->yieldContent('guestLogoClass')) === 'hidden' ? '' : 'mt-4 sm:mt-5' }}">
                @include('layouts.partials.flash')

                {{ $slot ?? '' }}
                @yield('content')
            </div>
        </section>
    </main>

    @livewireScripts(['nonce' => request()->attributes->get('csp_nonce')])
    <x-toast />
    @include('layouts.partials.protect-submit')
    @stack('scripts')
</body>
</html>
