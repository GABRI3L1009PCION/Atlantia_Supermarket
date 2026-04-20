<section class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm" aria-labelledby="filtro-categorias-title">
    <h2 id="filtro-categorias-title" class="text-base font-semibold text-slate-950">
        Categorias
    </h2>

    <div class="mt-4 space-y-2">
        <button
            type="button"
            wire:click="seleccionarCategoria(null)"
            class="w-full rounded-md px-3 py-2 text-left text-sm font-medium"
            @class([
                'bg-emerald-50 text-emerald-800' => $categoriaSeleccionada === null,
                'text-slate-700 hover:bg-slate-50' => $categoriaSeleccionada !== null,
            ])
        >
            Todas las categorias
        </button>

        @foreach ($categorias as $categoria)
            <div wire:key="categoria-{{ $categoria->id }}">
                <button
                    type="button"
                    wire:click="seleccionarCategoria({{ $categoria->id }})"
                    class="w-full rounded-md px-3 py-2 text-left text-sm font-medium"
                    @class([
                        'bg-emerald-50 text-emerald-800' => $categoriaSeleccionada === $categoria->id,
                        'text-slate-700 hover:bg-slate-50' => $categoriaSeleccionada !== $categoria->id,
                    ])
                >
                    {{ $categoria->nombre }}
                </button>

                @if ($categoria->children->isNotEmpty())
                    <div class="ml-3 mt-1 space-y-1 border-l border-slate-200 pl-3">
                        @foreach ($categoria->children as $child)
                            <button
                                type="button"
                                wire:click="seleccionarCategoria({{ $child->id }})"
                                class="w-full rounded-md px-3 py-2 text-left text-sm"
                                @class([
                                    'bg-emerald-50 text-emerald-800' => $categoriaSeleccionada === $child->id,
                                    'text-slate-600 hover:bg-slate-50' => $categoriaSeleccionada !== $child->id,
                                ])
                            >
                                {{ $child->nombre }}
                            </button>
                        @endforeach
                    </div>
                @endif
            </div>
        @endforeach
    </div>
</section>
