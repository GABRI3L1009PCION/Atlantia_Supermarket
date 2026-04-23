<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
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
                @include('layouts.partials.flash')

                {{ $slot ?? '' }}
                @yield('content')
            </main>
        </div>
    </div>

    @livewireScripts(['nonce' => request()->attributes->get('csp_nonce')])
    @stack('scripts')
</body>
</html>
