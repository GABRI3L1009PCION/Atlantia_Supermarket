@extends('layouts.marketplace')

@section('content')
    @php
        $heroImage = 'https://images.unsplash.com/photo-1542838132-92c53300491e?auto=format&fit=crop&w=1600&q=80';
    @endphp

    <section class="relative overflow-hidden">
        <div class="absolute inset-0">
            <img src="{{ $heroImage }}" alt="Mercado local con frutas y verduras frescas" class="h-full w-full object-cover">
            <div class="absolute inset-0 bg-[linear-gradient(110deg,rgba(248,253,255,0.92),rgba(236,249,255,0.82),rgba(241,252,255,0.38))]"></div>
            <div class="absolute inset-0 bg-[radial-gradient(circle_at_18%_18%,rgba(115,232,244,0.28),transparent_26%),radial-gradient(circle_at_72%_30%,rgba(115,232,244,0.16),transparent_24%)]"></div>
        </div>

        <div class="relative mx-auto grid min-h-[430px] w-full max-w-7xl items-center gap-8 px-4 py-12 sm:px-6 lg:min-h-[500px] lg:grid-cols-[minmax(0,1.05fr)_minmax(340px,0.95fr)] lg:px-8">
            <div class="max-w-2xl">
                <p class="text-sm font-black uppercase tracking-[0.22em] text-atlantia-cyan-700">Atlantia Supermarket</p>
                <h1 class="mt-4 text-4xl font-black leading-tight text-atlantia-deep sm:text-5xl lg:text-6xl">
                    Tu supermercado local, listo para comprar sin perder tiempo.
                </h1>
                <p class="mt-5 max-w-xl text-base leading-7 text-atlantia-deep/72 sm:text-lg">
                    Compra frutas, despensa, bebidas y esenciales del hogar en una experiencia clara, fresca y hecha
                    para vender mejor.
                </p>

                <form action="{{ route('catalogo.index') }}" method="GET" class="glass-surface cyan-ring mt-8 max-w-xl rounded-full p-1.5">
                    <div class="flex items-center gap-2 rounded-full">
                        <input
                            type="search"
                            name="q"
                            value="{{ request('q', '') }}"
                            placeholder="Busca frutas, abarrotes, limpieza o tu marca favorita"
                            class="h-11 w-full rounded-full border-0 bg-transparent px-4 text-sm text-atlantia-deep placeholder:text-atlantia-deep/45 focus:outline-none focus:ring-0"
                        >
                        <button
                            type="submit"
                            class="inline-flex h-11 shrink-0 items-center justify-center rounded-full bg-atlantia-cyan-700 px-5 text-sm font-bold text-white transition hover:bg-atlantia-deep"
                        >
                            Buscar
                        </button>
                    </div>
                </form>

                <div class="mt-6 flex flex-wrap gap-3 text-sm font-semibold text-atlantia-deep/72">
                    <span class="rounded-full border border-atlantia-cyan/30 bg-white/70 px-4 py-2">Entrega local verificada</span>
                    <span class="rounded-full border border-atlantia-cyan/30 bg-white/70 px-4 py-2">Vendedores aprobados</span>
                    <span class="rounded-full border border-atlantia-cyan/30 bg-white/70 px-4 py-2">Compra rapida y segura</span>
                </div>
            </div>

            <div class="hidden lg:block">
                <div class="glass-surface relative overflow-hidden rounded-[8px] p-6">
                    <div class="absolute inset-x-8 top-6 h-20 rounded-full bg-atlantia-cyan/18 blur-3xl"></div>
                    <img
                        src="https://images.unsplash.com/photo-1579113800032-c38bd7635818?auto=format&fit=crop&w=900&q=80"
                        alt="Canasta con frutas y vegetales en supermercado"
                        class="relative mx-auto aspect-[4/3] w-full rounded-[8px] object-cover"
                    >
                    <div class="mt-5 grid grid-cols-3 gap-3">
                        <div class="rounded-[8px] border border-atlantia-cyan/20 bg-white/80 px-4 py-3 text-center">
                            <p class="text-xs font-black uppercase tracking-[0.18em] text-atlantia-cyan-700">Productos</p>
                            <p class="mt-2 text-2xl font-black text-atlantia-deep">{{ number_format($metricas['productos']) }}</p>
                        </div>
                        <div class="rounded-[8px] border border-atlantia-cyan/20 bg-white/80 px-4 py-3 text-center">
                            <p class="text-xs font-black uppercase tracking-[0.18em] text-atlantia-cyan-700">Categorias</p>
                            <p class="mt-2 text-2xl font-black text-atlantia-deep">{{ number_format($metricas['categorias']) }}</p>
                        </div>
                        <div class="rounded-[8px] border border-atlantia-cyan/20 bg-white/80 px-4 py-3 text-center">
                            <p class="text-xs font-black uppercase tracking-[0.18em] text-atlantia-cyan-700">Vendedores</p>
                            <p class="mt-2 text-2xl font-black text-atlantia-deep">{{ number_format($metricas['vendedores']) }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section id="categorias" class="border-b border-atlantia-cyan/15 bg-white/55 backdrop-blur-sm">
        <div class="mx-auto w-full max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
            <div class="mb-8 text-center">
                <p class="text-sm font-black uppercase tracking-[0.22em] text-atlantia-cyan-700">Compra por seccion</p>
                <h2 class="mt-3 text-3xl font-black tracking-tight text-atlantia-deep sm:text-4xl">Explora por categoria</h2>
            </div>

            <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6">
                @foreach ($categoriasDestacadas as $categoria)
                    <a href="{{ $categoria['href'] }}" class="group text-center">
                        <div class="mx-auto flex h-28 w-28 items-center justify-center overflow-hidden rounded-full border border-white/80 bg-white/82 shadow-[0_16px_36px_rgba(18,51,66,0.09)] ring-1 ring-atlantia-cyan/28 transition group-hover:-translate-y-1 group-hover:shadow-[0_20px_42px_rgba(18,51,66,0.12)]">
                            <img src="{{ $categoria['image'] }}" alt="{{ $categoria['nombre'] }}" class="h-full w-full object-cover">
                        </div>
                        <p class="mx-auto mt-4 max-w-[11ch] text-lg font-semibold leading-6 text-atlantia-deep">
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
