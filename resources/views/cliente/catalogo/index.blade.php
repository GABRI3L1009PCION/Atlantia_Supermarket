@extends('layouts.marketplace')

@section('content')
    @php
        $heroImage = 'https://images.unsplash.com/photo-1488459716781-31db52582fe9?auto=format&fit=crop&w=1600&q=80';
    @endphp

    <section class="relative overflow-hidden">
        <div class="absolute inset-0">
            <img src="{{ $heroImage }}" alt="Mercado local con frutas y verduras frescas" class="h-full w-full object-cover">
            <div class="absolute inset-0 bg-gradient-to-r from-atlantia-ink/65 via-atlantia-ink/35 to-transparent"></div>
        </div>

        <div class="relative mx-auto flex min-h-[420px] w-full max-w-7xl items-center px-4 py-12 sm:px-6 lg:min-h-[460px] lg:px-8">
            <div class="max-w-2xl text-white">
                <p class="text-sm font-black uppercase tracking-[0.22em] text-white/85">Atlantia Supermarket</p>
                <h1 class="mt-4 text-4xl font-black leading-tight sm:text-5xl lg:text-6xl">
                    Tu supermercado local, fresco y directo a tu puerta.
                </h1>
                <p class="mt-5 max-w-xl text-base leading-7 text-white/85 sm:text-lg">
                    Compra despensa, frescos y esenciales del hogar a vendedores locales con una experiencia clara,
                    visual y lista para convertir.
                </p>
                <div class="mt-8 flex flex-wrap gap-3">
                    <a href="#productos" class="inline-flex items-center justify-center rounded-md bg-white px-6 py-3 text-base font-bold text-atlantia-ink transition hover:bg-atlantia-cream">
                        Comprar ahora
                    </a>
                    <a href="#categorias" class="inline-flex items-center justify-center rounded-md border border-white/40 bg-white/10 px-6 py-3 text-base font-bold text-white transition hover:bg-white/20">
                        Explorar categorias
                    </a>
                </div>
            </div>
        </div>
    </section>

    <section id="categorias" class="border-b border-atlantia-rose/10 bg-atlantia-cream">
        <div class="mx-auto w-full max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
            <div class="mb-8 text-center">
                <p class="text-sm font-black uppercase tracking-[0.22em] text-atlantia-wine">Compra por seccion</p>
                <h2 class="mt-3 text-3xl font-black tracking-tight text-atlantia-ink sm:text-4xl">Explora por categoria</h2>
            </div>

            <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6">
                @foreach ($categoriasDestacadas as $categoria)
                    <a href="{{ $categoria['href'] }}" class="group text-center">
                        <div class="mx-auto flex h-28 w-28 items-center justify-center overflow-hidden rounded-full bg-white shadow-sm ring-1 ring-atlantia-rose/15 transition group-hover:-translate-y-1 group-hover:shadow-md">
                            <img src="{{ $categoria['image'] }}" alt="{{ $categoria['nombre'] }}" class="h-full w-full object-cover">
                        </div>
                        <p class="mx-auto mt-4 max-w-[11ch] text-lg font-semibold leading-6 text-atlantia-ink">
                            {{ $categoria['nombre'] }}
                        </p>
                    </a>
                @endforeach
            </div>
        </div>
    </section>

    @if ($destacados->isNotEmpty())
        <section class="bg-white">
            <div class="mx-auto w-full max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
                <div class="mb-6 flex items-end justify-between gap-4">
                    <div>
                        <p class="text-sm font-black uppercase tracking-[0.22em] text-atlantia-wine">Selecciones del dia</p>
                        <h2 class="mt-2 text-3xl font-black tracking-tight text-atlantia-ink">Destacados para comprar rapido</h2>
                    </div>
                    <a href="#productos" class="hidden text-sm font-bold text-atlantia-wine transition hover:text-atlantia-wine-700 sm:inline-flex">
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
