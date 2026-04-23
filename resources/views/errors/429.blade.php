@php
    $logoPath = file_exists(public_path('images/logo.png')) ? 'images/logo.png' : 'images/atlantia-logo.svg';
@endphp
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Demasiadas solicitudes | Atlantia Supermarket</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-atlantia-cream text-atlantia-ink">
    <main class="mx-auto flex min-h-screen max-w-3xl items-center px-4 py-10">
        <section class="w-full rounded-3xl border border-atlantia-rose/15 bg-white p-8 shadow-sm">
            <img src="{{ asset($logoPath) }}" alt="Atlantia Supermarket" class="h-16 w-auto">
            <p class="mt-6 text-sm font-black uppercase tracking-[0.2em] text-atlantia-rose">Error 429</p>
            <h1 class="mt-3 text-4xl font-black text-atlantia-ink">Vas demasiado rapido.</h1>
            <p class="mt-4 max-w-xl text-base leading-7 text-atlantia-ink/70">
                Espera un momento antes de volver a intentar la accion.
            </p>
            <p class="mt-6 text-sm font-semibold text-atlantia-ink/70">
                Puedes reintentar en <span class="font-black text-atlantia-wine" data-retry-countdown="30">30 segundos</span>.
            </p>
        </section>
    </main>
</body>
</html>
