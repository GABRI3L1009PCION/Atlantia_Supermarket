<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $title ?? 'Atlantia Supermarket' }}</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles(['nonce' => request()->attributes->get('csp_nonce')])
</head>
<body class="flex min-h-screen flex-col bg-white text-atlantia-ink antialiased">
    @include('layouts.partials.header')

    <main id="contenido-principal" class="flex-1" tabindex="-1">
        @include('layouts.partials.flash')

        {{ $slot ?? '' }}
        @yield('content')
    </main>

    @include('layouts.partials.footer')
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

    <div class="fixed bottom-5 right-5 z-40 md:hidden">
        <livewire:carrito.icono-carrito />
    </div>

    @livewireScripts(['nonce' => request()->attributes->get('csp_nonce')])
    @include('layouts.partials.protect-submit')
    @stack('scripts')
</body>
</html>
