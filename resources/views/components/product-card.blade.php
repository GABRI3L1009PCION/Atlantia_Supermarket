@props([
    'producto',
])

<article {{ $attributes->merge(['class' => 'rounded-lg border border-atlantia-rose/30 bg-white shadow-sm']) }}>
    <a href="{{ route('productos.show', $producto) }}" class="block">
        <div class="aspect-[4/3] overflow-hidden rounded-t-lg bg-atlantia-blush">
            @if ($producto->imagenPrincipal?->path)
                <img
                    src="{{ asset('storage/' . $producto->imagenPrincipal->path) }}"
                    alt="{{ $producto->nombre }}"
                    class="h-full w-full object-cover"
                >
            @else
                <div class="flex h-full items-center justify-center text-sm text-atlantia-ink/60">
                    Imagen no disponible
                </div>
            @endif
        </div>
    </a>

    <div class="p-4">
        <p class="text-xs font-semibold uppercase text-atlantia-wine">{{ $producto->vendor?->business_name }}</p>
        <h3 class="mt-1 line-clamp-2 text-base font-semibold text-atlantia-ink">{{ $producto->nombre }}</h3>
        <div class="mt-3 flex items-center justify-between">
            <x-price :amount="$producto->precio_oferta ?? $producto->precio_base" />
            <x-ui.badge :variant="$producto->inventario?->stock_actual > 0 ? 'success' : 'danger'">
                {{ $producto->inventario?->stock_actual > 0 ? 'Disponible' : 'Agotado' }}
            </x-ui.badge>
        </div>

        <form method="POST" action="{{ route('cliente.carrito.items.store') }}" class="mt-4">
            @csrf
            <input type="hidden" name="producto_id" value="{{ $producto->id }}">
            <input type="hidden" name="cantidad" value="1">
            <x-ui.button
                type="submit"
                class="w-full"
                :disabled="$producto->inventario?->stock_actual < 1"
            >
                Agregar al carrito
            </x-ui.button>
        </form>
    </div>
</article>
