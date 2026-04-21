@extends(auth()->user()?->isSuperAdmin() && request()->routeIs('admin.*') ? 'layouts.super-admin' : 'layouts.app')

@section('content')
    <section class="mx-auto max-w-full py-2">
        <div class="rounded-2xl border border-atlantia-rose/20 bg-white p-6 shadow-sm">
            <x-page-header title="Productos" subtitle="Administra el catalogo multivendedor y el inventario inicial." />

            <div class="grid gap-6 xl:grid-cols-[430px_1fr]">
                <form
                    method="POST"
                    action="{{ route('admin.productos.store') }}"
                    enctype="multipart/form-data"
                    class="rounded-xl border border-atlantia-rose/20 bg-atlantia-cream p-5"
                >
                    @csrf
                    <h2 class="text-lg font-bold text-atlantia-wine">Crear producto</h2>

                    <div class="mt-4 grid gap-4">
                        <div>
                            <label class="text-sm font-semibold text-atlantia-ink">Quien vende este producto</label>
                            <select name="owner_type" class="mt-1 w-full rounded-md border border-atlantia-rose/35 px-3 py-2" required>
                                <option value="atlantia" @selected(old('owner_type', 'atlantia') === 'atlantia')>
                                    Atlantia Supermarket - producto propio
                                </option>
                                <option value="vendor" @selected(old('owner_type') === 'vendor')>
                                    Vendedor local externo
                                </option>
                            </select>
                            <p class="mt-1 text-xs text-atlantia-ink/55">
                                Usa Atlantia para inventario propio del supermercado. Usa vendedor local para productos de terceros.
                            </p>
                        </div>
                        <div>
                            <label class="text-sm font-semibold text-atlantia-ink">Vendedor local</label>
                            <select name="vendor_id" class="mt-1 w-full rounded-md border border-atlantia-rose/35 px-3 py-2">
                                <option value="">No aplica si el producto es de Atlantia</option>
                                @foreach ($vendors as $vendor)
                                    <option value="{{ $vendor->id }}" @selected((string) old('vendor_id') === (string) $vendor->id)>
                                        {{ $vendor->business_name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="text-sm font-semibold text-atlantia-ink">Categoria</label>
                            <select name="categoria_id" class="mt-1 w-full rounded-md border border-atlantia-rose/35 px-3 py-2" required>
                                @foreach ($categorias as $categoria)
                                    <option value="{{ $categoria->id }}">{{ $categoria->nombre }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="grid gap-4 md:grid-cols-2">
                            <div>
                                <label class="text-sm font-semibold text-atlantia-ink">SKU</label>
                                <input name="sku" type="text" class="mt-1 w-full rounded-md border border-atlantia-rose/35 px-3 py-2" required>
                            </div>
                            <div>
                                <label class="text-sm font-semibold text-atlantia-ink">Unidad</label>
                                <select name="unidad_medida" class="mt-1 w-full rounded-md border border-atlantia-rose/35 px-3 py-2" required>
                                    @foreach (['unidad', 'libra', 'kilogramo', 'gramo', 'litro', 'mililitro', 'paquete'] as $unidad)
                                        <option value="{{ $unidad }}">{{ ucfirst($unidad) }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div>
                            <label class="text-sm font-semibold text-atlantia-ink">Nombre</label>
                            <input name="nombre" type="text" class="mt-1 w-full rounded-md border border-atlantia-rose/35 px-3 py-2" required>
                        </div>
                        <div>
                            <label class="text-sm font-semibold text-atlantia-ink">Descripcion</label>
                            <textarea name="descripcion" rows="3" class="mt-1 w-full rounded-md border border-atlantia-rose/35 px-3 py-2"></textarea>
                        </div>
                        <div class="grid gap-4 md:grid-cols-3">
                            <div>
                                <label class="text-sm font-semibold text-atlantia-ink">Precio base</label>
                                <input name="precio_base" type="number" step="0.01" min="0.01" class="mt-1 w-full rounded-md border border-atlantia-rose/35 px-3 py-2" required>
                            </div>
                            <div>
                                <label class="text-sm font-semibold text-atlantia-ink">Oferta</label>
                                <input name="precio_oferta" type="number" step="0.01" min="0" class="mt-1 w-full rounded-md border border-atlantia-rose/35 px-3 py-2">
                            </div>
                            <div>
                                <label class="text-sm font-semibold text-atlantia-ink">Peso gramos</label>
                                <input name="peso_gramos" type="number" min="0" class="mt-1 w-full rounded-md border border-atlantia-rose/35 px-3 py-2">
                            </div>
                        </div>
                        <div class="grid gap-4 md:grid-cols-3">
                            <div>
                                <label class="text-sm font-semibold text-atlantia-ink">Stock actual</label>
                                <input name="stock_actual" type="number" min="0" value="0" class="mt-1 w-full rounded-md border border-atlantia-rose/35 px-3 py-2" required>
                            </div>
                            <div>
                                <label class="text-sm font-semibold text-atlantia-ink">Stock minimo</label>
                                <input name="stock_minimo" type="number" min="0" value="0" class="mt-1 w-full rounded-md border border-atlantia-rose/35 px-3 py-2">
                            </div>
                            <div>
                                <label class="text-sm font-semibold text-atlantia-ink">Stock maximo</label>
                                <input name="stock_maximo" type="number" min="0" value="0" class="mt-1 w-full rounded-md border border-atlantia-rose/35 px-3 py-2">
                            </div>
                        </div>
                    </div>

                    <div class="mt-4 flex flex-wrap gap-4 text-sm">
                        <label class="inline-flex items-center gap-2">
                            <input type="checkbox" name="is_active" value="1" checked class="rounded border-atlantia-rose text-atlantia-wine">
                            <span>Activo</span>
                        </label>
                        <label class="inline-flex items-center gap-2">
                            <input type="checkbox" name="visible_catalogo" value="1" checked class="rounded border-atlantia-rose text-atlantia-wine">
                            <span>Visible en catalogo</span>
                        </label>
                        <label class="inline-flex items-center gap-2">
                            <input type="checkbox" name="requiere_refrigeracion" value="1" class="rounded border-atlantia-rose text-atlantia-wine">
                            <span>Requiere refrigeracion</span>
                        </label>
                    </div>

                    <div class="mt-4">
                        <label class="text-sm font-semibold text-atlantia-ink">Imagenes del producto</label>
                        <input
                            name="imagenes[]"
                            type="file"
                            accept="image/png,image/jpeg,image/webp"
                            multiple
                            class="mt-1 w-full rounded-md border border-atlantia-rose/35 px-3 py-2"
                        >
                        <p class="mt-1 text-xs text-atlantia-ink/55">
                            Puedes agregar hasta 8 imagenes JPG, PNG o WEBP. Maximo 5 MB cada una.
                        </p>
                        @error('imagenes')
                            <p class="mt-1 text-sm font-semibold text-rose-700">{{ $message }}</p>
                        @enderror
                        @error('imagenes.*')
                            <p class="mt-1 text-sm font-semibold text-rose-700">{{ $message }}</p>
                        @enderror
                    </div>

                    <x-ui.button type="submit" class="mt-5 w-full">Guardar producto</x-ui.button>
                </form>

                <div class="rounded-xl border border-atlantia-rose/20 bg-white p-5">
                    <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                        <h2 class="text-lg font-bold text-atlantia-wine">Catalogo registrado</h2>
                        <form method="GET" class="flex gap-2">
                            <input type="search" name="q" value="{{ request('q') }}" placeholder="Buscar producto o SKU" class="w-72 rounded-md border border-atlantia-rose/35 px-3 py-2">
                            <x-ui.button type="submit" variant="secondary">Buscar</x-ui.button>
                        </form>
                    </div>

                    <div class="mt-5 overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <thead>
                                <tr class="border-b border-atlantia-rose/20 text-left text-atlantia-ink/55">
                                    <th class="pb-3">Producto</th>
                                    <th class="pb-3">Vendedor</th>
                                    <th class="pb-3">Precio</th>
                                    <th class="pb-3">Stock</th>
                                    <th class="pb-3">Estado</th>
                                    <th class="pb-3 text-right">Acciones</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-atlantia-rose/15">
                                @forelse ($productos as $producto)
                                    <tr>
                                        <td class="py-3">
                                            <p class="font-semibold text-atlantia-ink">{{ $producto->nombre }}</p>
                                            <p class="text-xs text-atlantia-ink/50">{{ $producto->sku }}</p>
                                        </td>
                                        <td class="py-3 text-atlantia-ink/70">
                                            {{ $producto->vendor?->business_name }}
                                            @if ($producto->vendor?->slug === 'atlantia-supermarket')
                                                <span class="ml-2 rounded-md bg-emerald-50 px-2 py-1 text-xs font-bold text-emerald-700">
                                                    Propio
                                                </span>
                                            @endif
                                        </td>
                                        <td class="py-3 text-atlantia-ink/70">Q{{ number_format((float) ($producto->precio_oferta ?? $producto->precio_base), 2) }}</td>
                                        <td class="py-3 text-atlantia-ink/70">{{ $producto->inventario?->stock_actual ?? 0 }}</td>
                                        <td class="py-3">
                                            <span class="rounded-md bg-atlantia-blush px-3 py-1 text-xs font-bold text-atlantia-wine">
                                                {{ $producto->is_active ? 'Activo' : 'Inactivo' }}
                                            </span>
                                        </td>
                                        <td class="py-3 text-right">
                                            <a href="{{ route('admin.productos.show', $producto) }}" class="font-semibold text-atlantia-wine hover:underline">Gestionar</a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="py-6 text-center text-atlantia-ink/60">No hay productos registrados.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-4">{{ $productos->links() }}</div>
                </div>
            </div>
        </div>
    </section>
@endsection
