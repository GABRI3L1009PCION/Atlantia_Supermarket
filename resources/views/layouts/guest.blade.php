<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $title ?? 'Acceso Atlantia Supermarket' }}</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="min-h-screen bg-atlantia-blush text-atlantia-ink antialiased">
    <main class="flex min-h-screen items-center justify-center px-4 py-10">
        <section class="w-full max-w-md rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
            <a href="{{ route('home') }}" class="block text-center text-2xl font-bold text-atlantia-wine">
                Atlantia Supermarket
            </a>

            <div class="mt-6">
                @include('layouts.partials.flash')

                {{ $slot ?? '' }}
                @yield('content')
            </div>
        </section>
    </main>

    @livewireScripts
    @stack('scripts')
</body>
</html>
