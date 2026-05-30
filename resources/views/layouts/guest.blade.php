<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $title ?? 'Acceso Atlantia Supermarket' }}</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles(['nonce' => request()->attributes->get('csp_nonce')])
</head>
<body class="min-h-screen bg-atlantia-blush text-atlantia-ink antialiased">
    <main class="relative flex min-h-screen items-center justify-center overflow-hidden px-3 py-3 sm:px-4 sm:py-4 lg:px-6">
        <div class="absolute inset-0 -z-10 bg-[linear-gradient(135deg,#fff_0%,#fff7fa_38%,#f7dce8_100%)]"></div>
        <div class="absolute inset-x-0 bottom-0 -z-10 h-1/2 bg-[linear-gradient(140deg,transparent_0%,rgba(255,255,255,0.72)_38%,rgba(135,22,61,0.08)_100%)]"></div>

        <section class="{{ trim($__env->yieldContent('guestShellClass', 'w-full')) }} {{ trim($__env->yieldContent('guestMaxWidth', 'max-w-3xl')) }} rounded-2xl border border-atlantia-rose/25 bg-white/95 {{ trim($__env->yieldContent('guestPadding', 'px-5 py-7 sm:px-8 sm:py-9 lg:px-12 lg:py-10')) }} shadow-[0_24px_70px_rgba(135,22,61,0.16)] backdrop-blur">
            @php
                $logoPath = file_exists(public_path('images/logo.png')) ? 'images/logo.png' : 'images/atlantia-logo.svg';
            @endphp

            @hasSection('guestActions')
                <div class="mb-5">
                    @yield('guestActions')
                </div>
            @endif

            <a href="{{ route('home') }}" class="mx-auto block w-fit" aria-label="Atlantia Supermarket">
                <img
                    src="{{ asset($logoPath) }}"
                    alt="Atlantia Supermarket"
                    class="{{ trim($__env->yieldContent('guestLogoClass', 'h-12 w-auto sm:h-16')) }}"
                >
            </a>

            <div class="mt-4 sm:mt-5">
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
