@extends('layouts.marketplace')

@section('content')
    <section class="border-b border-atlantia-rose/15 bg-atlantia-blush/70">
        <div class="mx-auto grid w-full max-w-7xl gap-5 px-4 py-6 sm:px-6 lg:grid-cols-[0.9fr_1.1fr] lg:px-8">
            <div>
                <p class="text-xs font-black uppercase text-atlantia-wine">Marketplace Atlantia</p>
                <h1 class="mt-2 text-3xl font-black leading-tight text-atlantia-ink sm:text-4xl">Comercios y tiendas cerca de ti</h1>
                <p class="mt-3 max-w-2xl text-sm leading-6 text-atlantia-ink/70">
                    Atlantia Delivery reune comercios verificados en una sola plataforma. Atlantia Supermarket queda como
                    comercio oficial junto con restaurantes, farmacias, tiendas y emprendedores aprobados.
                </p>
            </div>

            <form action="{{ route('comercios.index') }}" method="GET" class="grid gap-3 rounded-2xl border border-atlantia-rose/15 bg-white p-4 shadow-sm sm:grid-cols-[1fr_180px_180px_auto] sm:items-end">
                <label class="block text-sm font-bold text-atlantia-ink">
                    Buscar
                    <input
                        type="search"
                        name="q"
                        value="{{ $filters['q'] }}"
                        placeholder="Restaurante, tienda o producto"
                        class="mt-1 h-11 w-full rounded-md border border-atlantia-rose/25 px-3 text-sm outline-none focus:border-atlantia-wine focus:ring-2 focus:ring-atlantia-blush"
                    >
                </label>

                <label class="block text-sm font-bold text-atlantia-ink">
                    Municipio
                    <select name="municipio" class="mt-1 h-11 w-full rounded-md border border-atlantia-rose/25 px-3 text-sm outline-none focus:border-atlantia-wine focus:ring-2 focus:ring-atlantia-blush">
                        <option value="">Todos</option>
                        @foreach ($municipios as $municipio)
                            <option value="{{ $municipio }}" @selected($filters['municipio'] === $municipio)>{{ $municipio }}</option>
                        @endforeach
                    </select>
                </label>

                <label class="block text-sm font-bold text-atlantia-ink">
                    Categoria
                    <select name="categoria" class="mt-1 h-11 w-full rounded-md border border-atlantia-rose/25 px-3 text-sm outline-none focus:border-atlantia-wine focus:ring-2 focus:ring-atlantia-blush">
                        <option value="">Todas</option>
                        @foreach ($categorias as $categoria)
                            <option value="{{ $categoria->id }}" @selected((int) $filters['categoria'] === $categoria->id)>{{ $categoria->nombre }}</option>
                        @endforeach
                    </select>
                </label>

                <button class="h-11 rounded-md bg-atlantia-wine px-5 text-sm font-black text-white shadow-sm hover:bg-atlantia-wine-700">
                    Buscar
                </button>
            </form>
        </div>
    </section>

    <section class="mx-auto w-full max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
        <div class="mb-5 flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <h2 class="text-2xl font-black text-atlantia-ink">Negocios disponibles</h2>
                <p class="mt-1 text-sm text-atlantia-ink/60">{{ number_format($vendors->total()) }} comercios con productos publicados.</p>
            </div>
            <a href="{{ route('catalogo.index') }}" class="text-sm font-black text-atlantia-wine hover:underline">Ver todos los productos</a>
        </div>

        @if ($vendors->isEmpty())
            <div class="rounded-2xl border border-dashed border-atlantia-rose/25 bg-white p-10 text-center">
                <h3 class="text-lg font-black text-atlantia-ink">No encontramos comercios con esos filtros</h3>
                <p class="mt-2 text-sm text-atlantia-ink/60">Prueba con otro municipio, categoria o nombre de negocio.</p>
            </div>
        @else
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($vendors as $vendor)
                    @php
                        $rating = $ratings->get($vendor->id, ['rating' => null, 'total' => 0]);
                        $initials = \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($vendor->business_name, 0, 2));
                    @endphp
                    <a href="{{ route('comercios.show', ['vendor' => $vendor->slug]) }}" class="group overflow-hidden rounded-2xl border border-atlantia-rose/15 bg-white shadow-sm transition hover:-translate-y-0.5 hover:shadow-lg">
                        <div class="relative h-36 bg-atlantia-blush">
                            @if ($vendor->cover_url)
                                <img src="{{ $vendor->cover_url }}" alt="{{ $vendor->business_name }}" class="h-full w-full object-cover" loading="lazy">
                                <div class="absolute inset-0 bg-gradient-to-t from-black/35 to-transparent"></div>
                            @else
                                <div class="h-full w-full bg-[linear-gradient(135deg,#fff7fa_0%,#f0d3dd_100%)]"></div>
                            @endif
                            <div class="absolute bottom-3 left-4 flex items-center gap-3">
                                <span class="grid h-14 w-14 place-items-center overflow-hidden rounded-xl border-2 border-white bg-white text-lg font-black text-atlantia-wine shadow-md">
                                    @if ($vendor->logo_url)
                                        <img src="{{ $vendor->logo_url }}" alt="{{ $vendor->business_name }}" class="h-full w-full object-cover" loading="lazy">
                                    @else
                                        {{ $initials }}
                                    @endif
                                </span>
                                <span class="rounded-full bg-white/95 px-3 py-1 text-xs font-black text-atlantia-wine shadow-sm">
                                    {{ $vendor->business_category ?: 'Comercio local' }}
                                </span>
                            </div>
                        </div>

                        <div class="space-y-3 p-4">
                            <div>
                                <h3 class="truncate text-lg font-black text-atlantia-ink group-hover:text-atlantia-wine">{{ $vendor->business_name }}</h3>
                                <p class="mt-1 line-clamp-2 min-h-10 text-sm leading-5 text-atlantia-ink/60">{{ $vendor->descripcion ?: 'Productos disponibles para entrega por Atlantia.' }}</p>
                            </div>

                            <div class="grid grid-cols-3 gap-2 text-center text-xs">
                                <span class="rounded-lg bg-atlantia-blush/70 p-2">
                                    <span class="block font-black text-atlantia-ink">{{ $rating['rating'] ? number_format((float) $rating['rating'], 1) : 'Nuevo' }}</span>
                                    <span class="text-atlantia-ink/55">Rating</span>
                                </span>
                                <span class="rounded-lg bg-atlantia-blush/70 p-2">
                                    <span class="block font-black text-atlantia-ink">{{ number_format((int) $vendor->productos_publicados_count) }}</span>
                                    <span class="text-atlantia-ink/55">Productos</span>
                                </span>
                                <span class="rounded-lg bg-atlantia-blush/70 p-2">
                                    <span class="block truncate font-black text-atlantia-ink">{{ $vendor->municipio ?: 'Cerca' }}</span>
                                    <span class="text-atlantia-ink/55">Zona</span>
                                </span>
                            </div>
                        </div>
                    </a>
                @endforeach
            </div>

            <div class="mt-6">
                {{ $vendors->links() }}
            </div>
        @endif
    </section>
@endsection
