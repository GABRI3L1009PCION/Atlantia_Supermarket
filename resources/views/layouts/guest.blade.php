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
    <main class="flex min-h-screen items-center justify-center px-4 py-10">
        <section class="w-full max-w-md rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
            @php
                $logoPath = file_exists(public_path('images/logo.png')) ? 'images/logo.png' : 'images/atlantia-logo.svg';
            @endphp

            <a href="{{ route('home') }}" class="mx-auto block w-fit" aria-label="Atlantia Supermarket">
                <img
                    src="{{ asset($logoPath) }}"
                    alt="Atlantia Supermarket"
                    class="h-16 w-auto"
                >
            </a>

            <div class="mt-6">
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
