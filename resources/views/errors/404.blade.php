@php
    $logoPath = file_exists(public_path('images/logo.png')) ? 'images/logo.png' : 'images/atlantia-logo.svg';
@endphp
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Pagina no encontrada | Atlantia Supermarket</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-atlantia-cream text-atlantia-ink">
    <main class="mx-auto flex min-h-screen max-w-4xl items-center px-4 py-10">
        <section class="w-full rounded-3xl border border-atlantia-rose/15 bg-white p-8 shadow-sm">
            <img src="{{ asset($logoPath) }}" alt="Atlantia Supermarket" class="h-16 w-auto">
            <p class="mt-6 text-sm font-black uppercase tracking-[0.2em] text-atlantia-rose">Error 404</p>
            <h1 class="mt-3 text-4xl font-black text-atlantia-ink">No encontramos la pagina que buscas.</h1>
            <p class="mt-4 max-w-2xl text-base leading-7 text-atlantia-ink/70">
                Puede que el enlace haya cambiado o que la pagina ya no este disponible.
            </p>
            <form method="GET" action="{{ route('catalogo.index') }}" class="mt-8 grid gap-3 sm:grid-cols-[1fr_auto]">
                <label for="buscar-producto" class="sr-only">Buscar en Atlantia</label>
                <input
                    id="buscar-producto"
                    type="search"
                    name="q"
                    placeholder="Buscar productos, categorias o vendedores..."
                    class="w-full rounded-lg border border-atlantia-rose/20 px-4 py-3 text-sm focus:border-atlantia-wine focus:ring-atlantia-rose"
                >
                <button type="submit" class="rounded-lg bg-atlantia-wine px-5 py-3 text-sm font-black text-white">Buscar</button>
            </form>
        </section>
    </main>
</body>
</html>
