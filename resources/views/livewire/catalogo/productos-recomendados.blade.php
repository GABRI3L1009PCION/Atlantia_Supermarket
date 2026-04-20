<section class="space-y-4" aria-labelledby="productos-recomendados-title">
    <header>
        <h2 id="productos-recomendados-title" class="text-xl font-bold text-slate-950">
            Recomendados para ti
        </h2>
        <p class="mt-1 text-sm text-slate-600">
            Seleccionados con base en compras locales y disponibilidad del catalogo.
        </p>
    </header>

    @if ($productos->isEmpty())
        <x-ui.empty-state
            title="Aun no hay recomendaciones"
            message="Cuando haya productos con suficiente informacion apareceran aqui."
        />
    @else
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            @foreach ($productos as $producto)
                <div wire:key="recomendado-{{ $producto->id }}" class="space-y-3">
                    <x-product-card :producto="$producto" />

                    <x-ui.button type="button" class="w-full" wire:click="agregarAlCarrito({{ $producto->id }})">
                        Agregar
                    </x-ui.button>
                </div>
            @endforeach
        </div>
    @endif
</section>
