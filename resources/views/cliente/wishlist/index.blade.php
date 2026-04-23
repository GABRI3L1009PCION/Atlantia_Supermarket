@extends('layouts.marketplace')

@section('content')
    <section class="mx-auto w-full max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <x-page-header
                title="Mi lista de deseos"
                subtitle="Guarda productos para volver a verlos y agregarlos luego al carrito."
            />

            @if ($items->isNotEmpty())
                <form method="POST" action="{{ route('cliente.wishlist.add-all') }}">
                    @csrf
                    <x-ui.button type="submit">Agregar todo al carrito</x-ui.button>
                </form>
            @endif
        </div>

        @if ($items->isEmpty())
            <div class="mt-8">
                <x-ui.empty-state
                    title="Tu wishlist aun esta vacia"
                    message="Explora el catalogo y guarda tus productos favoritos para tenerlos a mano."
                />
            </div>
        @else
            <div class="mt-8 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                @foreach ($items as $item)
                    @if ($item->producto)
                        <div class="space-y-3">
                            <x-product-card :producto="$item->producto" />
                            <div class="flex gap-3">
                                <form method="POST" action="{{ route('cliente.carrito.items.store') }}" class="flex-1">
                                    @csrf
                                    <input type="hidden" name="producto_id" value="{{ $item->producto->id }}">
                                    <input type="hidden" name="cantidad" value="1">
                                    <x-ui.button type="submit" class="w-full">Agregar al carrito</x-ui.button>
                                </form>
                                <form method="POST" action="{{ route('cliente.wishlist.toggle', $item->producto) }}">
                                    @csrf
                                    <x-ui.button type="submit" variant="secondary">Quitar</x-ui.button>
                                </form>
                            </div>
                        </div>
                    @endif
                @endforeach
            </div>

            <div class="mt-8">
                {{ $items->links() }}
            </div>
        @endif
    </section>
@endsection
