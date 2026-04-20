@extends('layouts.marketplace')

@section('content')
    @php
        $categoriasRapidas = [
            ['label' => 'Todas las categorias', 'href' => route('catalogo.index')],
            ['label' => 'Alimentos frescos', 'href' => route('catalogo.index', ['q' => 'frescos'])],
            ['label' => 'Abarrotes', 'href' => route('catalogo.index', ['q' => 'abarrotes'])],
            ['label' => 'Limpieza', 'href' => route('catalogo.index', ['q' => 'limpieza'])],
            ['label' => 'Bebidas', 'href' => route('catalogo.index', ['q' => 'bebidas'])],
        ];
    @endphp

    <section class="bg-atlantia-blush py-4 shadow-inner">
        <form method="GET" class="mx-auto grid w-full max-w-xl grid-cols-[1fr_auto] px-4 sm:px-0">
            <label for="q" class="sr-only">Buscar productos</label>
            <input
                id="q"
                type="search"
                name="q"
                value="{{ request('q') }}"
                placeholder="Buscar en todo el supermercado..."
                class="h-12 rounded-l-lg border-0 bg-white px-4 text-base text-atlantia-ink placeholder:text-atlantia-ink/55 shadow-sm focus:outline-none focus:ring-2 focus:ring-atlantia-rose"
            >
            <button
                type="submit"
                class="h-12 rounded-r-lg bg-atlantia-wine px-6 text-base font-bold text-white shadow-sm hover:bg-atlantia-wine-700 focus:outline-none focus:ring-2 focus:ring-atlantia-rose focus:ring-offset-2"
            >
                <span class="inline-flex items-center gap-2">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                        <path d="M11 19C15.4 19 19 15.4 19 11C19 6.6 15.4 3 11 3C6.6 3 3 6.6 3 11C3 15.4 6.6 19 11 19Z" stroke="currentColor" stroke-width="2"/>
                        <path d="M20.5 20.5L16.7 16.7" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                    </svg>
                    Buscar
                </span>
            </button>
        </form>
    </section>

    <section id="categorias" class="relative border-b border-atlantia-rose/20 bg-white py-4 shadow-sm">
        <a
            href="{{ route('catalogo.index') }}"
            class="absolute left-4 top-1/2 hidden h-10 w-10 -translate-y-1/2 items-center justify-center rounded-full bg-atlantia-wine text-2xl font-bold text-white shadow-md sm:flex"
            aria-label="Categoria anterior"
        >
            &lsaquo;
        </a>
        <div class="mx-auto flex w-full max-w-4xl items-center justify-center gap-2 overflow-x-auto px-4">
            @foreach ($categoriasRapidas as $categoria)
                <a
                    href="{{ $categoria['href'] }}"
                    class="whitespace-nowrap rounded-md px-5 py-3 text-sm font-bold {{ $loop->first ? 'bg-atlantia-wine text-white' : 'bg-atlantia-blush text-atlantia-wine hover:bg-atlantia-rose/25' }}"
                >
                    {{ $categoria['label'] }}
                </a>
            @endforeach
        </div>
        <a
            href="{{ route('catalogo.index', ['page' => 2]) }}"
            class="absolute right-4 top-1/2 hidden h-10 w-10 -translate-y-1/2 items-center justify-center rounded-full bg-atlantia-wine text-2xl font-bold text-white shadow-md sm:flex"
            aria-label="Categoria siguiente"
        >
            &rsaquo;
        </a>
    </section>

    <section class="mx-auto min-h-[313px] w-full max-w-7xl px-4 py-14 sm:px-6 lg:px-8">
        <h1 class="text-center text-2xl font-bold text-atlantia-wine">
            Catalogo de productos
        </h1>

        @if ($catalogo->count() > 0)
            <div class="mt-8 grid gap-5 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                @foreach ($catalogo as $producto)
                    <x-product-card :producto="$producto" />
                @endforeach
            </div>

            <div class="mt-8">
                {{ $catalogo->links() }}
            </div>
        @else
            <p class="mt-20 text-center text-base text-atlantia-ink">
                No hay productos para mostrar.
            </p>
        @endif
    </section>
@endsection
