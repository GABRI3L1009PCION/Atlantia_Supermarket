<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $title ?? 'Atlantia Supermarket' }}</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="min-h-screen bg-white text-slate-950 antialiased">
    @include('layouts.partials.header')

    <main id="contenido-principal" tabindex="-1">
        @include('layouts.partials.flash')

        {{ $slot ?? '' }}
        @yield('content')
    </main>

    @include('layouts.partials.footer')

    @livewireScripts
    @stack('scripts')
</body>
</html>
