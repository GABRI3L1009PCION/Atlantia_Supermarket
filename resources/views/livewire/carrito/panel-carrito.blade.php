<section class="rounded-lg border border-slate-200 bg-white shadow-sm" aria-labelledby="panel-carrito-title">
    <header class="flex items-center justify-between border-b border-slate-200 p-4">
        <div>
            <h2 id="panel-carrito-title" class="text-lg font-semibold text-slate-950">
                Carrito
            </h2>
            <p class="mt-1 text-sm text-slate-600">
                {{ $cantidadTotal }} unidades seleccionadas
            </p>
        </div>

        @if ($items->isNotEmpty())
            <button type="button" class="text-sm font-semibold text-red-700" wire:click="vaciarCarrito">
                Vaciar
            </button>
        @endif
    </header>

    @if ($items->isEmpty())
        <div class="p-4">
            <x-ui.empty-state
                title="Tu carrito esta vacio"
                message="Agrega productos locales para preparar tu pedido."
            >
                <a href="{{ route('catalogo.index') }}" class="text-sm font-semibold text-emerald-800">
                    Ir al catalogo
                </a>
            </x-ui.empty-state>
        </div>
    @else
        <div class="divide-y divide-slate-200">
            @foreach ($items as $item)
                <article wire:key="carrito-item-{{ $item->id }}" class="flex gap-4 p-4">
                    <div class="h-20 w-20 shrink-0 overflow-hidden rounded-md bg-slate-100">
                        @if ($item->producto?->imagenPrincipal?->path)
                            <img
                                src="{{ asset('storage/' . $item->producto->imagenPrincipal->path) }}"
                                alt="{{ $item->producto->nombre }}"
                                class="h-full w-full object-cover"
                            >
                        @else
                            <div class="flex h-full items-center justify-center text-xs text-slate-500">
                                Sin imagen
                            </div>
                        @endif
                    </div>

                    <div class="min-w-0 flex-1">
                        <h3 class="truncate font-semibold text-slate-950">{{ $item->producto?->nombre }}</h3>
                        <p class="mt-1 text-sm text-slate-600">{{ $item->producto?->vendor?->business_name }}</p>
                        <p class="mt-2 text-sm font-semibold text-slate-950">
                            Q {{ number_format((float) $item->precio_unitario_snapshot, 2) }}
                        </p>

                        <div class="mt-3 flex items-center gap-2">
                            <button
                                type="button"
                                class="rounded-md border border-slate-300 px-2 py-1 text-sm"
                                wire:click="disminuir({{ $item->id }})"
                                aria-label="Disminuir cantidad"
                            >
                                -
                            </button>
                            <input
                                type="number"
                                min="1"
                                max="99"
                                value="{{ $item->cantidad }}"
                                wire:change="actualizarCantidad({{ $item->id }}, $event.target.value)"
                                class="w-16 rounded-md border border-slate-300 px-2 py-1 text-center text-sm"
                                aria-label="Cantidad de {{ $item->producto?->nombre }}"
                            >
                            <button
                                type="button"
                                class="rounded-md border border-slate-300 px-2 py-1 text-sm"
                                wire:click="incrementar({{ $item->id }})"
                                aria-label="Aumentar cantidad"
                            >
                                +
                            </button>
                            <button
                                type="button"
                                class="ml-auto text-sm font-semibold text-red-700"
                                wire:click="eliminarItem({{ $item->id }})"
                            >
                                Quitar
                            </button>
                        </div>
                    </div>
                </article>
            @endforeach
        </div>

        <footer class="border-t border-slate-200 p-4">
            <div class="flex items-center justify-between">
                <span class="text-sm font-medium text-slate-600">Subtotal</span>
                <span class="text-lg font-bold text-slate-950">Q {{ number_format($subtotal, 2) }}</span>
            </div>
            <a
                href="{{ route('cliente.checkout.create') }}"
                class="mt-4 inline-flex w-full items-center justify-center rounded-md bg-emerald-700 px-4 py-2
                    text-sm font-semibold text-white hover:bg-emerald-800"
            >
                Continuar al pago
            </a>
        </footer>
    @endif
</section>
