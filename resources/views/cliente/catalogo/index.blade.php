@extends('layouts.marketplace')

@section('content')
    @php
        $categoriasRapidas = [
            ['label' => 'Todo el supermercado', 'href' => route('catalogo.index')],
            ['label' => 'Frescos del dia', 'href' => route('catalogo.index', ['q' => 'frescos'])],
            ['label' => 'Despensa', 'href' => route('catalogo.index', ['q' => 'abarrotes'])],
            ['label' => 'Hogar y limpieza', 'href' => route('catalogo.index', ['q' => 'limpieza'])],
            ['label' => 'Bebidas frias', 'href' => route('catalogo.index', ['q' => 'bebidas'])],
        ];

        $beneficios = [
            'Entrega local con cobertura en Izabal',
            'Vendedores aprobados y catalogo verificado',
            'Compra por municipio, categoria y disponibilidad',
        ];
    @endphp

    <section class="border-b border-atlantia-rose/15 bg-white">
        <div class="mx-auto grid w-full max-w-7xl gap-8 px-4 py-8 sm:px-6 lg:grid-cols-[minmax(0,1.3fr)_minmax(300px,0.7fr)] lg:px-8 lg:py-10">
            <div class="space-y-6">
                <div class="flex flex-wrap items-center gap-2 text-xs font-semibold uppercase text-atlantia-wine">
                    <span class="rounded-full bg-atlantia-blush px-3 py-1">Compra local</span>
                    <span class="rounded-full bg-emerald-50 px-3 py-1 text-emerald-700">Entrega segura</span>
                    <span class="rounded-full bg-amber-50 px-3 py-1 text-amber-700">Stock actualizado</span>
                </div>

                <div class="max-w-3xl space-y-3">
                    <h1 class="max-w-4xl text-4xl font-black tracking-tight text-atlantia-ink sm:text-5xl">
                        Tu supermercado local, listo para comprar sin perder tiempo.
                    </h1>
                    <p class="max-w-2xl text-base leading-7 text-atlantia-ink/70 sm:text-lg">
                        Encuentra productos frescos, despensa y esenciales del hogar con filtros rapidos,
                        cobertura por municipio y vendedores verificados.
                    </p>
                </div>

                <form method="GET" action="{{ route('catalogo.index') }}" class="grid gap-3 sm:grid-cols-[minmax(0,1fr)_auto]">
                    <label for="q" class="sr-only">Buscar productos</label>
                    <div class="flex h-14 items-center gap-3 rounded-lg border border-atlantia-rose/25 bg-atlantia-cream px-4 shadow-sm">
                        <svg class="h-5 w-5 text-atlantia-wine" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                            <path d="M11 19C15.4 19 19 15.4 19 11C19 6.6 15.4 3 11 3C6.6 3 3 6.6 3 11C3 15.4 6.6 19 11 19Z" stroke="currentColor" stroke-width="2"/>
                            <path d="M20.5 20.5L16.7 16.7" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                        </svg>
                        <input
                            id="q"
                            type="search"
                            name="q"
                            value="{{ request('q') }}"
                            placeholder="Busca frutas, abarrotes, limpieza o tu marca favorita"
                            class="h-full w-full border-0 bg-transparent px-0 text-base text-atlantia-ink placeholder:text-atlantia-ink/45 focus:outline-none focus:ring-0"
                        >
                    </div>

                    <button
                        type="submit"
                        class="inline-flex h-14 items-center justify-center rounded-lg bg-atlantia-wine px-6 text-base font-bold text-white shadow-sm transition hover:bg-atlantia-wine-700 focus:outline-none focus:ring-2 focus:ring-atlantia-rose focus:ring-offset-2"
                    >
                        Buscar ahora
                    </button>
                </form>

                <div class="flex flex-wrap gap-2">
                    @foreach ($categoriasRapidas as $categoria)
                        <a
                            href="{{ $categoria['href'] }}"
                            class="rounded-md border border-atlantia-rose/15 bg-white px-4 py-2 text-sm font-semibold text-atlantia-ink transition hover:border-atlantia-wine hover:text-atlantia-wine"
                        >
                            {{ $categoria['label'] }}
                        </a>
                    @endforeach
                </div>
            </div>

            <aside class="grid gap-4 self-start sm:grid-cols-3 lg:grid-cols-1">
                <div class="rounded-lg border border-atlantia-rose/20 bg-atlantia-cream p-5">
                    <p class="text-xs font-bold uppercase text-atlantia-wine">Cobertura activa</p>
                    <p class="mt-2 text-3xl font-black text-atlantia-ink">{{ number_format($metricas['productos']) }}</p>
                    <p class="mt-1 text-sm text-atlantia-ink/65">productos publicados y listos para entrega.</p>
                </div>

                <div class="rounded-lg border border-slate-200 bg-white p-5">
                    <p class="text-xs font-bold uppercase text-atlantia-wine">Red comercial</p>
                    <p class="mt-2 text-3xl font-black text-atlantia-ink">{{ number_format($metricas['vendedores']) }}</p>
                    <p class="mt-1 text-sm text-atlantia-ink/65">vendedores locales aprobados.</p>
                </div>

                <div class="rounded-lg border border-slate-200 bg-white p-5">
                    <p class="text-xs font-bold uppercase text-atlantia-wine">Compra mejor</p>
                    <ul class="mt-3 space-y-3 text-sm text-atlantia-ink/75">
                        @foreach ($beneficios as $beneficio)
                            <li class="flex items-start gap-3">
                                <span class="mt-1 inline-flex h-2.5 w-2.5 rounded-full bg-emerald-500"></span>
                                <span>{{ $beneficio }}</span>
                            </li>
                        @endforeach
                    </ul>
                </div>
            </aside>
        </div>
    </section>

    <section id="categorias" class="border-b border-atlantia-rose/15 bg-atlantia-cream">
        <div class="mx-auto flex w-full max-w-7xl flex-wrap items-center gap-3 px-4 py-4 sm:px-6 lg:px-8">
            <span class="text-sm font-bold uppercase tracking-wide text-atlantia-wine">Explora por ritmo de compra</span>
            @foreach ($categoriasRapidas as $categoria)
                <a
                    href="{{ $categoria['href'] }}"
                    class="rounded-full bg-white px-4 py-2 text-sm font-semibold text-atlantia-ink shadow-sm ring-1 ring-atlantia-rose/15 transition hover:bg-atlantia-wine hover:text-white"
                >
                    {{ $categoria['label'] }}
                </a>
            @endforeach
        </div>
    </section>

    <section class="mx-auto w-full max-w-7xl px-4 py-10 sm:px-6 lg:px-8 lg:py-12">
        <livewire:catalogo.lista-productos :search="(string) request('q', '')" />
    </section>
@endsection
