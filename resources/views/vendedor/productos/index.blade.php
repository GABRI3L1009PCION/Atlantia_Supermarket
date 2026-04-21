@extends('layouts.app')

@section('content')
    @php
        $puedeCrear = auth()->user()?->can('create', App\Models\Producto::class);
        $modeloCobro = match (true) {
            $vendor?->monthly_rent > 0 && $vendor?->commission_percentage > 0 => 'Renta mensual + comision',
            $vendor?->monthly_rent > 0 => 'Renta mensual',
            $vendor?->commission_percentage > 0 => 'Comision sobre ventas',
            default => 'Pendiente de configurar',
        };
        $inputBase = 'mt-1 w-full rounded-md border px-3 py-2';
        $inputNormal = 'border-atlantia-rose/35';
        $inputError = 'border-rose-500 bg-rose-50';
    @endphp

    <section class="mx-auto max-w-7xl space-y-6 pb-10">
        <div class="flex flex-col gap-4 border-b border-atlantia-rose/15 pb-5 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <p class="text-sm font-black uppercase tracking-wide text-atlantia-rose">Atlantia Supermarket</p>
                <h1 class="mt-2 text-3xl font-black tracking-tight text-atlantia-ink">Mis productos</h1>
                <p class="mt-1 text-sm text-atlantia-ink/65">
                    Administra tu catalogo como vendedor local independiente dentro de Atlantia.
                </p>
            </div>

            <div class="rounded-lg border border-atlantia-rose/20 bg-white px-4 py-3 text-sm shadow-sm">
                <p class="font-black text-atlantia-ink">{{ $vendor?->business_name ?? auth()->user()->name }}</p>
                <p class="text-atlantia-ink/60">{{ $modeloCobro }}</p>
            </div>
        </div>

        @if ($vendor === null)
            <article class="rounded-lg border border-amber-200 bg-amber-50 p-5 text-amber-900">
                <h2 class="text-lg font-black">Tu cuenta aun no tiene perfil de vendedor</h2>
                <p class="mt-2 text-sm">
                    Para crear productos se necesita un perfil comercial con datos de tienda, modelo de cobro y
                    configuracion fiscal. Solicita al administrador activar tu puesto de venta.
                </p>
            </article>
        @elseif (! $puedeCrear)
            <article class="rounded-lg border border-amber-200 bg-amber-50 p-5 text-amber-900">
                <h2 class="text-lg font-black">Tu puesto de venta esta pendiente de aprobacion</h2>
                <p class="mt-2 text-sm">
                    Puedes revisar el panel, pero la publicacion de productos se habilita cuando Atlantia aprueba tu
                    perfil comercial. Esto protege a clientes, vendedores y facturacion FEL.
                </p>
            </article>
        @endif

        <div class="grid gap-6 xl:grid-cols-[440px_1fr]">
            <form
                method="POST"
                action="{{ route('vendedor.productos.store') }}"
                enctype="multipart/form-data"
                class="rounded-lg border border-atlantia-rose/20 bg-white p-5 shadow-sm"
            >
                @csrf

                <div class="flex items-start justify-between gap-4">
                    <div>
                        <h2 class="text-xl font-black text-atlantia-ink">Crear producto</h2>
                        <p class="mt-1 text-sm text-atlantia-ink/60">El producto queda asociado solo a tu tienda.</p>
                    </div>
                    <span class="rounded-md bg-atlantia-blush px-3 py-1 text-xs font-black text-atlantia-wine">
                        Vendedor local
                    </span>
                </div>

                <fieldset @disabled(! $puedeCrear) class="mt-5 space-y-4">
                    <div>
                        <label class="text-sm font-bold text-atlantia-ink">Categoria</label>
                        <select
                            name="categoria_id"
                            class="{{ $inputBase }} {{ $errors->has('categoria_id') ? $inputError : $inputNormal }}"
                            required
                        >
                            <option value="">Selecciona una categoria</option>
                            @foreach ($categorias as $categoria)
                                <option value="{{ $categoria->id }}" @selected((string) old('categoria_id') === (string) $categoria->id)>
                                    {{ $categoria->nombre }}
                                </option>
                            @endforeach
                        </select>
                        @error('categoria_id')
                            <p class="mt-1 text-sm font-semibold text-rose-700">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label class="text-sm font-bold text-atlantia-ink">SKU</label>
                            <input
                                name="sku"
                                value="{{ old('sku') }}"
                                placeholder="ATL-FRIJOL-001"
                                class="{{ $inputBase }} {{ $errors->has('sku') ? $inputError : $inputNormal }}"
                                required
                            >
                            @error('sku')
                                <p class="mt-1 text-sm font-semibold text-rose-700">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label class="text-sm font-bold text-atlantia-ink">Unidad</label>
                            <select name="unidad_medida" class="{{ $inputBase }} {{ $inputNormal }}" required>
                                @foreach (['unidad', 'libra', 'kilogramo', 'gramo', 'litro', 'mililitro', 'paquete'] as $unidad)
                                    <option value="{{ $unidad }}" @selected(old('unidad_medida', 'unidad') === $unidad)>
                                        {{ ucfirst($unidad) }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div>
                        <label class="text-sm font-bold text-atlantia-ink">Nombre del producto</label>
                        <input
                            name="nombre"
                            value="{{ old('nombre') }}"
                            placeholder="Ej. Camaron fresco del Atlantico 1 lb"
                            class="{{ $inputBase }} {{ $errors->has('nombre') ? $inputError : $inputNormal }}"
                            required
                        >
                        @error('nombre')
                            <p class="mt-1 text-sm font-semibold text-rose-700">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="text-sm font-bold text-atlantia-ink">Descripcion</label>
                        <textarea
                            name="descripcion"
                            rows="3"
                            class="{{ $inputBase }} {{ $errors->has('descripcion') ? $inputError : $inputNormal }}"
                            placeholder="Describe origen, presentacion, calidad y recomendaciones."
                        >{{ old('descripcion') }}</textarea>
                        @error('descripcion')
                            <p class="mt-1 text-sm font-semibold text-rose-700">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label class="text-sm font-bold text-atlantia-ink">Precio base Q</label>
                            <input
                                name="precio_base"
                                inputmode="decimal"
                                value="{{ old('precio_base') }}"
                                placeholder="25.00"
                                class="{{ $inputBase }} {{ $errors->has('precio_base') ? $inputError : $inputNormal }}"
                                required
                            >
                            @error('precio_base')
                                <p class="mt-1 text-sm font-semibold text-rose-700">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label class="text-sm font-bold text-atlantia-ink">Precio oferta Q</label>
                            <input
                                name="precio_oferta"
                                inputmode="decimal"
                                value="{{ old('precio_oferta') }}"
                                placeholder="Opcional"
                                class="{{ $inputBase }} {{ $errors->has('precio_oferta') ? $inputError : $inputNormal }}"
                            >
                            @error('precio_oferta')
                                <p class="mt-1 text-sm font-semibold text-rose-700">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <div class="grid gap-4 sm:grid-cols-3">
                        <div>
                            <label class="text-sm font-bold text-atlantia-ink">Stock inicial</label>
                            <input name="stock_actual" type="number" min="0" value="{{ old('stock_actual', 0) }}" class="{{ $inputBase }} {{ $errors->has('stock_actual') ? $inputError : $inputNormal }}" required>
                        </div>
                        <div>
                            <label class="text-sm font-bold text-atlantia-ink">Stock minimo</label>
                            <input name="stock_minimo" type="number" min="0" value="{{ old('stock_minimo', 5) }}" class="{{ $inputBase }} {{ $errors->has('stock_minimo') ? $inputError : $inputNormal }}" required>
                        </div>
                        <div>
                            <label class="text-sm font-bold text-atlantia-ink">Stock maximo</label>
                            <input name="stock_maximo" type="number" min="0" value="{{ old('stock_maximo') }}" class="{{ $inputBase }} {{ $errors->has('stock_maximo') ? $inputError : $inputNormal }}">
                        </div>
                    </div>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <label class="flex items-center gap-3 rounded-lg border border-atlantia-rose/20 bg-atlantia-cream px-3 py-3 text-sm font-bold text-atlantia-ink">
                            <input type="checkbox" name="visible_catalogo" value="1" @checked(old('visible_catalogo', true))>
                            Visible en catalogo
                        </label>
                        <label class="flex items-center gap-3 rounded-lg border border-atlantia-rose/20 bg-atlantia-cream px-3 py-3 text-sm font-bold text-atlantia-ink">
                            <input type="checkbox" name="requiere_refrigeracion" value="1" @checked(old('requiere_refrigeracion'))>
                            Requiere refrigeracion
                        </label>
                    </div>

                    <div>
                        <label class="text-sm font-bold text-atlantia-ink">Imagenes</label>
                        <input
                            name="imagenes[]"
                            type="file"
                            accept="image/png,image/jpeg,image/webp"
                            multiple
                            class="{{ $inputBase }} {{ $errors->has('imagenes') ? $inputError : $inputNormal }}"
                        >
                        <p class="mt-1 text-xs text-atlantia-ink/55">Hasta 8 imagenes JPG, PNG o WEBP. Maximo 5 MB cada una.</p>
                        @error('imagenes')
                            <p class="mt-1 text-sm font-semibold text-rose-700">{{ $message }}</p>
                        @enderror
                    </div>

                    <button
                        type="submit"
                        class="w-full rounded-lg bg-atlantia-wine px-5 py-3 text-sm font-black text-white transition hover:bg-atlantia-wine-700 disabled:cursor-not-allowed disabled:opacity-55"
                        @disabled(! $puedeCrear)
                    >
                        Publicar producto
                    </button>
                </fieldset>
            </form>

            <article class="rounded-lg border border-atlantia-rose/20 bg-white p-5 shadow-sm">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h2 class="text-xl font-black text-atlantia-ink">Catalogo propio</h2>
                        <p class="mt-1 text-sm text-atlantia-ink/60">
                            Productos vendidos, facturados y gestionados por tu negocio.
                        </p>
                    </div>
                    <span class="rounded-md bg-atlantia-blush px-3 py-1 text-xs font-black text-atlantia-wine">
                        {{ $productos->total() }} productos
                    </span>
                </div>

                <div class="mt-5 overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead>
                            <tr class="border-b border-atlantia-rose/20 text-left text-atlantia-ink/55">
                                <th class="pb-3">Producto</th>
                                <th class="pb-3">Categoria</th>
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
                                        <p class="font-black text-atlantia-ink">{{ $producto->nombre }}</p>
                                        <p class="text-xs text-atlantia-ink/55">{{ $producto->sku }}</p>
                                    </td>
                                    <td class="py-3 text-atlantia-ink/70">{{ $producto->categoria?->nombre ?? 'Sin categoria' }}</td>
                                    <td class="py-3 font-black text-atlantia-ink">
                                        Q {{ number_format((float) ($producto->precio_oferta ?? $producto->precio_base), 2) }}
                                    </td>
                                    <td class="py-3 text-atlantia-ink/70">
                                        {{ number_format($producto->inventario?->stock_actual ?? 0) }}
                                    </td>
                                    <td class="py-3">
                                        <span class="rounded-md bg-atlantia-blush px-3 py-1 text-xs font-black text-atlantia-wine">
                                            {{ $producto->visible_catalogo ? 'Visible' : 'Oculto' }}
                                        </span>
                                    </td>
                                    <td class="py-3 text-right">
                                        <form method="POST" action="{{ route('vendedor.productos.destroy', $producto->uuid) }}" onsubmit="return confirm('Deseas retirar este producto del catalogo?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="font-bold text-atlantia-wine hover:underline">
                                                Retirar
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="py-8 text-center text-atlantia-ink/60">
                                        Aun no tienes productos. Crea el primero para activar tu catalogo dentro de Atlantia.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="mt-4">
                    {{ $productos->links() }}
                </div>
            </article>
        </div>
    </section>
@endsection
