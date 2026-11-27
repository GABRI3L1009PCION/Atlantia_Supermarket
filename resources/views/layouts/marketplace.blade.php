<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    @include('layouts.partials.favicon')
    <title>{{ $title ?? 'Atlantia Store' }}</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles(['nonce' => request()->attributes->get('csp_nonce')])
</head>
@php
    /* Pantallas de acceso: ocupan justo el alto disponible bajo el navbar, sin footer. */
    $pantallaAcceso = request()->routeIs('login', 'login.*', 'register', 'register.*', 'password.*', 'two-factor.*', 'verification.*', 'vendedor.acceso.*', 'acceso.*');
@endphp
<body class="flex min-h-screen flex-col overflow-x-hidden bg-white text-atlantia-ink antialiased">
    @include('layouts.partials.header')

    <main id="contenido-principal" @class(['flex-1 pb-20 lg:pb-0', 'flex flex-col' => $pantallaAcceso]) tabindex="-1">
        <div class="px-4 sm:px-6 lg:px-8">@include('layouts.partials.aviso-horario')</div>
        @if (request()->routeIs(...collect(\App\Support\MenuCuenta::cliente())->pluck('activa')->flatten()->all()))
            <x-cuenta-cliente-nav />
        @endif
        @include('layouts.partials.flash')

        {{ $slot ?? '' }}
        @yield('content')
    </main>

    @unless ($pantallaAcceso)
        @include('layouts.partials.footer')
    @endunless
    @include('layouts.partials.mobile-bottom-nav')
    @include('layouts.partials.whatsapp-soporte')
    @include('layouts.partials.instalar-app')
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

    @livewireScripts(['nonce' => request()->attributes->get('csp_nonce')])
    @include('layouts.partials.protect-submit')
    @stack('scripts')
</body>
</html>
