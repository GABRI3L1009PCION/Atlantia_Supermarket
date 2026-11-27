{{--
    Plantilla común de las páginas de error con el estilo de las pantallas de acceso:
    panel guinda con el logo y el mensaje claro a la derecha.
    Secciones: titulo-pagina, codigo, titulo, mensaje, acciones (opcional).
--}}
@php
    $logoPath = collect(['images/logo-atlantia-delivery.png', 'images/logo.png', 'images/atlantia-logo.svg'])
        ->first(fn (string $ruta): bool => file_exists(public_path($ruta))) ?? 'images/atlantia-logo.svg';
@endphp
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    @include('layouts.partials.favicon')
    <title>@yield('titulo-pagina') | Atlantia Store</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-[#f7f5f3] text-[#1f1416] antialiased">
    <main class="grid min-h-screen lg:grid-cols-[42%_58%]">
        {{-- Panel de marca --}}
        <aside class="relative flex items-center justify-center overflow-hidden bg-[radial-gradient(120%_90%_at_85%_100%,#6a0008_0%,transparent_60%),linear-gradient(160deg,#3a0004_0%,#4d0006_45%,#5e0008_100%)] px-8 py-6 lg:py-10">
            <div class="pointer-events-none absolute -left-40 -top-40 hidden h-[30rem] w-[30rem] rounded-full border border-white/[0.07] lg:block" aria-hidden="true"></div>
            <a href="{{ route('home') }}" class="relative z-10">
                <img src="{{ asset($logoPath) }}" alt="Atlantia Store" class="h-16 w-auto object-contain drop-shadow-[0_0_24px_rgba(255,70,90,0.35)] lg:h-auto lg:w-full lg:max-w-[18rem]">
            </a>
        </aside>

        {{-- Mensaje --}}
        <section class="flex px-6 py-10 sm:px-12 lg:px-20">
            <div class="m-auto w-full max-w-xl">
                <span class="inline-flex rounded-full bg-[#4d0006]/[0.07] px-3 py-1 text-xs font-bold uppercase tracking-[0.2em] text-[#4d0006]">Error @yield('codigo')</span>
                <h1 class="mt-4 text-3xl font-bold tracking-tight sm:text-4xl">@yield('titulo')</h1>
                <p class="mt-3 text-base leading-7 text-[#5b5052]">@yield('mensaje')</p>

                @hasSection('acciones')
                    <div class="mt-8">@yield('acciones')</div>
                @endif
            </div>
        </section>
    </main>
</body>
</html>
