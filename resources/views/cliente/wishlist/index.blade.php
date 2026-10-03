@extends('layouts.marketplace')

@section('content')
    <section class="mx-auto w-full max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <x-page-header
                title="Mis favoritos"
                subtitle="Guarda productos para volver a verlos y agregarlos luego al carrito."
            />

            @if ($productos->isNotEmpty())
                <form method="POST" action="{{ route('cliente.wishlist.add-all') }}">
                    @csrf
                    <x-ui.button type="submit">Agregar todo al carrito</x-ui.button>
                </form>
            @endif
        </div>

        @if ($productos->isEmpty())
            <div class="mt-8">
                <x-ui.empty-state
                    title="Tus favoritos aun estan vacios"
                    message="Explora el catalogo y guarda productos para tenerlos siempre a mano, incluso sin iniciar sesion."
                />
            </div>
        @else
            <div class="mt-8 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                @foreach ($productos as $producto)
                    @if ($producto)
                        <div class="space-y-3">
                            <x-product-card :producto="$producto" />
                            <div class="flex gap-3">
                                <form method="POST" action="{{ route('cliente.carrito.items.store') }}" class="flex-1">
                                    @csrf
                                    <input type="hidden" name="producto_id" value="{{ $producto->id }}">
                                    <input type="hidden" name="cantidad" value="1">
                                    <x-ui.button type="submit" class="w-full">Agregar al carrito</x-ui.button>
                                </form>
                                <form method="POST" action="{{ route('cliente.wishlist.toggle', $producto) }}">
                                    @csrf
                                    <x-ui.button type="submit" variant="secondary">Quitar</x-ui.button>
                                </form>
                            </div>
                        </div>
                    @endif
                @endforeach
            </div>

            <div class="mt-8">
                {{ $productos->links() }}
            </div>
        @endif
    </section>
@endsection
