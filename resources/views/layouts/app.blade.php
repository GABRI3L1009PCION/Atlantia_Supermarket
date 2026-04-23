<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="csp-nonce" content="{{ request()->attributes->get('csp_nonce') }}">

    <title>{{ $title ?? 'Atlantia Supermarket' }}</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('styles')
    @livewireStyles(['nonce' => request()->attributes->get('csp_nonce')])
</head>
<body class="min-h-screen bg-[#fbf7f9] text-atlantia-ink antialiased">
    <div class="min-h-screen xl:grid xl:grid-cols-[18rem_1fr]">
        @include('layouts.partials.sidebar')

        <div class="min-w-0">
            @include('layouts.partials.app-header')

            <main id="contenido-principal" class="min-w-0 px-4 py-6 sm:px-6 lg:px-8" tabindex="-1">
                @include('layouts.partials.breadcrumbs')
                @include('layouts.partials.impersonation-banner')
                @include('layouts.partials.flash')

                {{ $slot ?? '' }}
                @yield('content')
            </main>
        </div>
    </div>

    @livewireScripts(['nonce' => request()->attributes->get('csp_nonce')])
    <x-toast />
    <div
        id="livewire-global-overlay"
        class="pointer-events-none fixed inset-0 z-[94] hidden items-center justify-center bg-slate-950/35 backdrop-blur-[1px]"
        role="status"
        aria-live="polite"
        aria-label="Cargando contenido"
    >
        <div class="rounded-xl bg-white px-5 py-4 text-sm font-bold text-atlantia-wine shadow-xl">
            Cargando...
        </div>
    </div>
    @include('layouts.partials.protect-submit')
    @stack('scripts')
</body>
</html>
