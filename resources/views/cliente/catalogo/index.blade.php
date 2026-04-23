@extends('layouts.marketplace')

@section('content')
    @php
        $heroImage = 'https://images.unsplash.com/photo-1604719312566-8912e9227c6a?auto=format&fit=crop&w=1600&q=80';
        $basketImage = 'https://images.unsplash.com/photo-1579113800032-c38bd7635818?auto=format&fit=crop&w=1200&q=80';
    @endphp

    <section class="relative overflow-hidden pt-4">
        <div class="absolute inset-0">
            <img src="{{ $heroImage }}" alt="Interior de supermercado" class="h-full w-full object-cover">
            <div class="absolute inset-0 bg-[linear-gradient(90deg,rgba(249,254,255,0.86),rgba(239,250,255,0.70),rgba(233,247,252,0.44))]"></div>
            <div class="absolute inset-0 bg-[radial-gradient(circle_at_18%_18%,rgba(255,255,255,0.75),transparent_24%),radial-gradient(circle_at_72%_24%,rgba(115,232,244,0.22),transparent_25%)]"></div>
        </div>

        <div class="relative mx-auto grid min-h-[280px] w-full max-w-7xl items-center gap-4 px-4 py-8 sm:px-6 lg:min-h-[320px] lg:grid-cols-[minmax(0,1fr)_minmax(320px,0.9fr)] lg:px-8 lg:py-10">
            <div class="max-w-xl pl-2 sm:pl-6">
                <h1 class="max-w-[14ch] text-3xl font-medium leading-tight text-black/85 sm:text-4xl lg:text-[3.35rem]">
                    Tu supermercado local, listo para comprar sin perder tiempo.
                </h1>

                <form action="{{ route('catalogo.index') }}" method="GET" class="mt-5 max-w-md rounded-full border border-atlantia-cyan/60 bg-white/72 p-1.5 shadow-[0_8px_22px_rgba(18,51,66,0.08)] backdrop-blur-sm">
                    <div class="flex items-center gap-2 rounded-full">
                        <input
                            type="search"
                            name="q"
                            value="{{ request('q', '') }}"
                            placeholder="Busca frutas, abarrotes, limpieza o tu marca favorita"
                            class="h-8 w-full rounded-full border-0 bg-transparent px-3 text-[11px] text-atlantia-deep placeholder:text-atlantia-deep/45 focus:outline-none focus:ring-0 sm:text-xs"
                        >
                        <button type="submit" class="sr-only">Buscar</button>
                    </div>
                </form>
            </div>

            <div class="relative hidden h-full min-h-[260px] lg:block">
                <div class="absolute inset-x-10 bottom-2 h-8 rounded-full border border-atlantia-cyan/25 bg-atlantia-cyan/8 blur-sm"></div>
                <img
                    src="{{ $basketImage }}"
                    alt="Canasta con frutas y vegetales"
                    class="absolute bottom-0 right-0 h-[290px] w-auto object-contain drop-shadow-[0_18px_30px_rgba(18,51,66,0.18)]"
                >
            </div>
        </div>
    </section>

    <section id="categorias" class="border-b border-atlantia-cyan/10 bg-white/88">
        <div class="mx-auto w-full max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
            <div class="mb-5">
                <h2 class="text-2xl font-medium tracking-tight text-black/85">Explora por Categoria</h2>
            </div>

            <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6">
                @foreach ($categoriasDestacadas as $categoria)
                    <a href="{{ $categoria['href'] }}" class="group text-center">
                        <div class="mx-auto flex h-20 w-20 items-center justify-center overflow-hidden rounded-full border border-atlantia-cyan/18 bg-[radial-gradient(circle,rgba(255,255,255,0.98),rgba(232,251,255,0.86))] shadow-[0_10px_24px_rgba(18,51,66,0.10)] ring-1 ring-atlantia-cyan/16 transition group-hover:-translate-y-1 group-hover:shadow-[0_16px_30px_rgba(18,51,66,0.12)]">
                            <img src="{{ $categoria['image'] }}" alt="{{ $categoria['nombre'] }}" class="h-12 w-12 object-cover opacity-80 mix-blend-multiply">
                        </div>
                        <p class="mx-auto mt-3 max-w-[13ch] text-sm font-medium uppercase leading-5 text-black/75">
                            {{ $categoria['nombre'] }}
                        </p>
                    </a>
                @endforeach
            </div>
        </div>
    </section>

    @if ($destacados->isNotEmpty())
        <section class="bg-transparent">
            <div class="mx-auto w-full max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
                <div class="mb-6 flex items-end justify-between gap-4">
                    <div>
                        <p class="text-sm font-black uppercase tracking-[0.22em] text-atlantia-cyan-700">Selecciones del dia</p>
                        <h2 class="mt-2 text-3xl font-black tracking-tight text-atlantia-deep">Destacados para comprar rapido</h2>
                    </div>
                    <a href="#productos" class="hidden text-sm font-bold text-atlantia-cyan-700 transition hover:text-atlantia-deep sm:inline-flex">
                        Ver todo el catalogo
                    </a>
                </div>

                <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-5">
                    @foreach ($destacados as $producto)
                        <x-product-card :producto="$producto" />
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    <section id="productos" class="mx-auto w-full max-w-7xl px-4 py-10 sm:px-6 lg:px-8 lg:py-12">
        <livewire:catalogo.lista-productos :search="(string) request('q', '')" />
    </section>
@endsection
