@extends('layouts.app')

@section('content')
    <section class="mx-auto max-w-5xl py-2">
        <div class="rounded-2xl border border-atlantia-rose/20 bg-white p-6 shadow-sm">
            <x-page-header title="Gestionar producto" subtitle="Edita datos del producto, inventario y visibilidad en catalogo." />

            <div class="grid gap-6 xl:grid-cols-[1fr_280px]">
                <form method="POST" action="{{ route('admin.productos.update', $producto) }}" class="space-y-5">
                    @csrf
                    @method('PUT')

                    <div class="grid gap-4 md:grid-cols-2">
                        <div>
                            <label class="text-sm font-semibold text-atlantia-ink">Vendedor</label>
                            <select name="vendor_id" class="mt-1 w-full rounded-md border border-atlantia-rose/35 px-3 py-2" required>
                                @foreach ($vendors as $vendor)
                                    <option value="{{ $vendor->id }}" @selected($producto->vendor_id === $vendor->id)>{{ $vendor->business_name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="text-sm font-semibold text-atlantia-ink">Categoria</label>
                            <select name="categoria_id" class="mt-1 w-full rounded-md border border-atlantia-rose/35 px-3 py-2" required>
                                @foreach ($categorias as $categoria)
                                    <option value="{{ $categoria->id }}" @selected($producto->categoria_id === $categoria->id)>{{ $categoria->nombre }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="text-sm font-semibold text-atlantia-ink">SKU</label>
                            <input name="sku" type="text" value="{{ $producto->sku }}" class="mt-1 w-full rounded-md border border-atlantia-rose/35 px-3 py-2" required>
                        </div>
                        <div>
                            <label class="text-sm font-semibold text-atlantia-ink">Unidad</label>
                            <select name="unidad_medida" class="mt-1 w-full rounded-md border border-atlantia-rose/35 px-3 py-2" required>
                                @foreach (['unidad', 'libra', 'kilogramo', 'gramo', 'litro', 'mililitro', 'paquete'] as $unidad)
                                    <option value="{{ $unidad }}" @selected($producto->unidad_medida === $unidad)>{{ ucfirst($unidad) }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div>
                        <label class="text-sm font-semibold text-atlantia-ink">Nombre</label>
                        <input name="nombre" type="text" value="{{ $producto->nombre }}" class="mt-1 w-full rounded-md border border-atlantia-rose/35 px-3 py-2" required>
                    </div>

                    <div>
                        <label class="text-sm font-semibold text-atlantia-ink">Descripcion</label>
                        <textarea name="descripcion" rows="4" class="mt-1 w-full rounded-md border border-atlantia-rose/35 px-3 py-2">{{ $producto->descripcion }}</textarea>
                    </div>

                    <div class="grid gap-4 md:grid-cols-3">
                        <div>
                            <label class="text-sm font-semibold text-atlantia-ink">Precio base</label>
                            <input name="precio_base" type="number" step="0.01" min="0.01" value="{{ $producto->precio_base }}" class="mt-1 w-full rounded-md border border-atlantia-rose/35 px-3 py-2" required>
                        </div>
                        <div>
                            <label class="text-sm font-semibold text-atlantia-ink">Precio oferta</label>
                            <input name="precio_oferta" type="number" step="0.01" min="0" value="{{ $producto->precio_oferta }}" class="mt-1 w-full rounded-md border border-atlantia-rose/35 px-3 py-2">
                        </div>
                        <div>
                            <label class="text-sm font-semibold text-atlantia-ink">Peso gramos</label>
                            <input name="peso_gramos" type="number" min="0" value="{{ $producto->peso_gramos }}" class="mt-1 w-full rounded-md border border-atlantia-rose/35 px-3 py-2">
                        </div>
                    </div>

                    <div class="grid gap-4 md:grid-cols-3">
                        <div>
                            <label class="text-sm font-semibold text-atlantia-ink">Stock actual</label>
                            <input name="stock_actual" type="number" min="0" value="{{ $producto->inventario?->stock_actual ?? 0 }}" class="mt-1 w-full rounded-md border border-atlantia-rose/35 px-3 py-2" required>
                        </div>
                        <div>
                            <label class="text-sm font-semibold text-atlantia-ink">Stock minimo</label>
                            <input name="stock_minimo" type="number" min="0" value="{{ $producto->inventario?->stock_minimo ?? 0 }}" class="mt-1 w-full rounded-md border border-atlantia-rose/35 px-3 py-2">
                        </div>
                        <div>
                            <label class="text-sm font-semibold text-atlantia-ink">Stock maximo</label>
                            <input name="stock_maximo" type="number" min="0" value="{{ $producto->inventario?->stock_maximo ?? 0 }}" class="mt-1 w-full rounded-md border border-atlantia-rose/35 px-3 py-2">
                        </div>
                    </div>

                    <div class="flex flex-wrap gap-4 text-sm">
                        <label class="inline-flex items-center gap-2">
                            <input type="checkbox" name="is_active" value="1" @checked($producto->is_active) class="rounded border-atlantia-rose text-atlantia-wine">
                            <span>Activo</span>
                        </label>
                        <label class="inline-flex items-center gap-2">
                            <input type="checkbox" name="visible_catalogo" value="1" @checked($producto->visible_catalogo) class="rounded border-atlantia-rose text-atlantia-wine">
                            <span>Visible en catalogo</span>
                        </label>
                        <label class="inline-flex items-center gap-2">
                            <input type="checkbox" name="requiere_refrigeracion" value="1" @checked($producto->requiere_refrigeracion) class="rounded border-atlantia-rose text-atlantia-wine">
                            <span>Requiere refrigeracion</span>
                        </label>
                    </div>

                    <div class="flex flex-wrap gap-3">
                        <x-ui.button type="submit">Guardar cambios</x-ui.button>
                        <a href="{{ route('admin.productos.index') }}" class="inline-flex items-center rounded-md border border-atlantia-rose/35 px-4 py-2 text-sm font-semibold text-atlantia-wine hover:bg-atlantia-blush">
                            Volver
                        </a>
                    </div>
                </form>

                <aside class="space-y-4">
                    <div class="rounded-xl border border-atlantia-rose/20 bg-atlantia-cream p-4">
                        <p class="text-xs font-semibold uppercase text-atlantia-rose">Referencia</p>
                        <p class="mt-2 text-sm text-atlantia-ink/70">UUID publico</p>
                        <p class="text-sm font-semibold text-atlantia-ink">{{ $producto->uuid }}</p>
                        <p class="mt-3 text-sm text-atlantia-ink/70">Publicado</p>
                        <p class="text-sm font-semibold text-atlantia-ink">{{ optional($producto->publicado_at)->format('d/m/Y H:i') ?: 'No publicado' }}</p>
                    </div>

                    <form method="POST" action="{{ route('admin.productos.destroy', $producto) }}" class="rounded-xl border border-red-200 bg-red-50 p-4">
                        @csrf
                        @method('DELETE')
                        <p class="text-sm font-bold text-red-800">Eliminar producto</p>
                        <p class="mt-2 text-sm text-red-700/80">
                            Se ocultara del catalogo y quedara con eliminacion logica.
                        </p>
                        <button type="submit" class="mt-4 rounded-md bg-red-700 px-4 py-2 text-sm font-semibold text-white hover:bg-red-800">
                            Eliminar producto
                        </button>
                    </form>
                </aside>
            </div>
        </div>
    </section>
@endsection
