@extends('layouts.marketplace')

@section('content')
    <section class="bg-atlantia-blush">
        <div class="mx-auto w-full max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
            <p class="text-sm font-semibold uppercase tracking-normal text-atlantia-wine">
                Izabal compra local
            </p>
            <h1 class="mt-2 max-w-3xl text-3xl font-bold text-atlantia-ink sm:text-4xl">
                Productos frescos y de diario cerca de tu casa.
            </h1>
            <p class="mt-3 max-w-2xl text-base text-atlantia-ink/75">
                Compra a vendedores de Puerto Barrios, Santo Tomas de Castilla y municipios cercanos.
            </p>
        </div>
    </section>

    <section class="mx-auto w-full max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
        <form method="GET" class="grid gap-3 rounded-lg border border-atlantia-rose/30 bg-white p-4 sm:grid-cols-[1fr_auto]">
            <label for="q" class="sr-only">Buscar productos</label>
            <input
                id="q"
                type="search"
                name="q"
                value="{{ request('q') }}"
                placeholder="Buscar productos, marcas o categorias"
                class="rounded-md border border-atlantia-rose/40 px-4 py-3 text-sm text-atlantia-ink placeholder:text-atlantia-ink/45 focus:border-atlantia-wine focus:ring-atlantia-rose"
            >
            <button
                type="submit"
                class="rounded-md bg-atlantia-wine px-5 py-3 text-sm font-semibold text-white hover:bg-atlantia-wine-700 focus:outline-none focus:ring-2 focus:ring-atlantia-rose focus:ring-offset-2"
            >
                Buscar
            </button>
        </form>

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
            <div class="mt-8 rounded-lg border border-atlantia-rose/30 bg-white p-8 text-center">
                <h2 class="text-lg font-semibold text-atlantia-ink">No encontramos productos con esos filtros.</h2>
                <p class="mt-2 text-sm text-atlantia-ink/70">
                    Prueba con arroz, mariscos, frutas, abarrotes o productos de limpieza.
                </p>
            </div>
        @endif
    </section>
@endsection
