@extends('layouts.app')

@section('content')
    @php
        $user = auth()->user();
        $puedeCrear = $user?->can('create', App\Models\Producto::class);
        $inputBase = 'mt-1 w-full rounded-md border bg-white px-3 py-2 text-sm text-atlantia-ink outline-none transition focus:border-atlantia-wine focus:ring-2 focus:ring-atlantia-rose/20 disabled:cursor-not-allowed disabled:bg-atlantia-cream disabled:text-atlantia-ink/45';
        $inputNormal = 'border-atlantia-rose/30';
        $inputError = 'border-rose-500 bg-rose-50';
        $errorText = 'mt-1 text-xs font-semibold text-rose-700';
        $showCreateModal = $errors->any();
        /* Pasos de las ventanas de crear y editar producto. */
        $pasosProducto = [
            'producto' => ['1', 'Producto'],
            'precio' => ['2', 'Precio y cantidad'],
            'fotos' => ['3', 'Fotos'],
            'publicar' => ['4', 'Publicar'],
        ];
        $filterValues = [
            'q' => (string) request('q', ''),
            'categoria_id' => (string) request('categoria_id', ''),
            'estado' => (string) request('estado', ''),
            'stock' => (string) request('stock', ''),
            'orden' => (string) request('orden', 'recientes'),
            'per_page' => (int) request('per_page', $productos->perPage()),
        ];
        $activeFilters = collect([
            'Busqueda: ' . $filterValues['q'] => $filterValues['q'],
            'Categoria activa' => $filterValues['categoria_id'],
            'Estado: ' . $filterValues['estado'] => $filterValues['estado'],
            'Stock: ' . $filterValues['stock'] => $filterValues['stock'],
        ])->filter();
    @endphp

    <section class="mx-auto max-w-[1280px] space-y-4 pb-10">
        <div class="flex flex-wrap items-end justify-between gap-3">
            <div>
                <h1 class="text-2xl font-black text-atlantia-ink">Productos</h1>
                <p class="mt-0.5 text-sm text-atlantia-ink/60">{{ $vendor?->business_name ?? $user?->name }} · {{ $productos->total() }} {{ $productos->total() === 1 ? 'producto' : 'productos' }}{{ $activeFilters->isNotEmpty() ? ' con estos filtros' : '' }}</p>
            </div>
            <div class="flex flex-wrap gap-2">
                <a href="{{ route('vendedor.productos.importar') }}" class="inline-flex h-10 items-center gap-2 rounded-lg border border-atlantia-rose/25 bg-white px-4 text-sm font-bold text-atlantia-wine hover:bg-atlantia-blush">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M12 21V9m0 0-4 4m4-4 4 4M4 3h16"/></svg>
                    Carga masiva
                </a>
                <button
                    type="button"
                    class="inline-flex h-10 items-center gap-2 rounded-lg bg-atlantia-wine px-4 text-sm font-bold text-white hover:bg-atlantia-wine-700 disabled:cursor-not-allowed disabled:opacity-55"
                    data-open-create-product-modal
                    @disabled(! $puedeCrear)
                >
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" aria-hidden="true"><path d="M12 5v14M5 12h14"/></svg>
                    Crear producto
                </button>
            </div>
        </div>

        @if ($vendor === null)
            <article class="rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900">
                <p class="font-black">Tu cuenta aún no tiene perfil de vendedor.</p>
                <p class="mt-1">Para crear productos se necesita un perfil de tienda aprobado.</p>
            </article>
        @elseif (! $puedeCrear)
            <article class="rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900">
                <p class="font-black">Tu tienda está pendiente de aprobación.</p>
                <p class="mt-1">Puedes revisar el panel; podrás publicar productos cuando Atlantia apruebe tu tienda.</p>
            </article>
        @endif

        <article class="overflow-hidden rounded-xl border border-atlantia-rose/15 bg-white shadow-[0_12px_32px_rgba(42,16,24,0.05)]">
            <form method="GET" action="{{ route('vendedor.productos.index') }}" class="flex flex-wrap items-center gap-2 border-b border-atlantia-rose/10 p-3" autocomplete="off" data-vendor-product-filters>
                <label class="relative min-w-[14rem] flex-1">
                    <span class="sr-only">Buscar producto</span>
                    <svg class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-atlantia-ink/40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>
                    <input type="search" name="q" value="{{ $filterValues['q'] }}" placeholder="Nombre, SKU o código de barras" class="h-10 w-full rounded-lg border border-atlantia-rose/25 pl-9 pr-3 text-sm outline-none focus:border-atlantia-wine focus:ring-2 focus:ring-atlantia-blush">
                </label>
                <select name="categoria_id" aria-label="Categoría" class="h-10 min-w-0 grow basis-[calc(50%-0.25rem)] rounded-lg border border-atlantia-rose/25 bg-white px-2 text-sm outline-none focus:border-atlantia-wine sm:grow-0 sm:basis-auto sm:px-3" data-vendor-product-filter>
                    <option value="">Todas las categorías</option>
                    @foreach ($categorias as $categoria)
                        <option value="{{ $categoria->id }}" @selected($filterValues['categoria_id'] === (string) $categoria->id)>{{ $categoria->nombre }}</option>
                    @endforeach
                </select>
                <select name="estado" aria-label="Estado" class="h-10 min-w-0 grow basis-[calc(50%-0.25rem)] rounded-lg border border-atlantia-rose/25 bg-white px-2 text-sm outline-none focus:border-atlantia-wine sm:grow-0 sm:basis-auto sm:px-3" data-vendor-product-filter>
                    <option value="">Todos los estados</option>
                    <option value="visible" @selected($filterValues['estado'] === 'visible')>En la tienda</option>
                    <option value="oculto" @selected($filterValues['estado'] === 'oculto')>Ocultos</option>
                    <option value="activo" @selected($filterValues['estado'] === 'activo')>Activos</option>
                    <option value="inactivo" @selected($filterValues['estado'] === 'inactivo')>Desactivados</option>
                </select>
                <select name="stock" aria-label="Existencias" class="h-10 min-w-0 grow basis-[calc(50%-0.25rem)] rounded-lg border border-atlantia-rose/25 bg-white px-2 text-sm outline-none focus:border-atlantia-wine sm:grow-0 sm:basis-auto sm:px-3" data-vendor-product-filter>
                    <option value="">Todas las existencias</option>
                    <option value="disponible" @selected($filterValues['stock'] === 'disponible')>Con existencias</option>
                    <option value="bajo" @selected($filterValues['stock'] === 'bajo')>Quedan pocos</option>
                    <option value="agotado" @selected($filterValues['stock'] === 'agotado')>Agotados</option>
                </select>
                <select name="orden" aria-label="Ordenar" class="h-10 min-w-0 grow basis-[calc(50%-0.25rem)] rounded-lg border border-atlantia-rose/25 bg-white px-2 text-sm outline-none focus:border-atlantia-wine sm:grow-0 sm:basis-auto sm:px-3" data-vendor-product-filter>
                    <option value="recientes" @selected($filterValues['orden'] === 'recientes')>Más recientes</option>
                    <option value="nombre" @selected($filterValues['orden'] === 'nombre')>Nombre A–Z</option>
                    <option value="precio_asc" @selected($filterValues['orden'] === 'precio_asc')>Menor precio</option>
                    <option value="precio_desc" @selected($filterValues['orden'] === 'precio_desc')>Mayor precio</option>
                    <option value="stock_asc" @selected($filterValues['orden'] === 'stock_asc')>Menos existencias</option>
                </select>
                @if ($activeFilters->isNotEmpty())
                    <a href="{{ route('vendedor.productos.index') }}" class="px-1 text-sm font-bold text-atlantia-wine hover:underline">Quitar filtros</a>
                @endif
            </form>

            <div>
                <div class="divide-y divide-atlantia-rose/10" data-lista-productos>
                    @forelse ($productos as $producto)
                        @php
                            $imageUrl = $producto->getFirstMediaUrl('productos', 'thumbnail')
                                ?: $producto->getFirstMediaUrl('productos')
                                ?: ($producto->imagenPrincipal?->path ? \Illuminate\Support\Facades\Storage::url($producto->imagenPrincipal->path) : null);
                            $stockActual = (int) ($producto->inventario?->stock_actual ?? 0);
                            $hasOffer = $producto->precio_oferta !== null && (float) $producto->precio_oferta > 0;
                            $displayPrice = $hasOffer ? $producto->precio_oferta : $producto->precio_base;
                        @endphp

                        @php
                            $reservado = (int) ($producto->inventario?->stock_reservado ?? 0);
                            $disponible = max(0, $stockActual - $reservado);
                            $minimo = max(1, (int) ($producto->inventario?->stock_minimo ?? 5));
                            $etiquetaDatos = [
                                'data-product-name' => $producto->nombre,
                                'data-product-sku' => $producto->sku,
                                'data-product-barcode' => $producto->codigo_barras ?: $producto->sku,
                                'data-product-price' => 'Q'.number_format((float) $displayPrice, 2),
                                'data-product-vendor' => $vendor?->business_name ?? $user?->name,
                                'data-product-image' => $imageUrl,
                            ];
                        @endphp
                        <div class="grid grid-cols-[3.5rem_minmax(0,1fr)_auto] items-center gap-3 px-4 py-3 transition hover:bg-[#fdfafa] md:grid-cols-[3.5rem_minmax(0,1fr)_7rem_8rem_8rem_auto]" data-producto-fila="{{ $producto->uuid }}">
                            <div class="relative grid h-14 w-14 place-items-center overflow-hidden rounded-lg bg-[#f8f4f3] p-1">
                                @if ($imageUrl)
                                    <img src="{{ $imageUrl }}" alt="{{ $producto->nombre }}" class="h-full w-full object-contain mix-blend-multiply" loading="lazy">
                                @else
                                    <span class="text-lg font-black text-atlantia-wine">{{ \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($producto->nombre ?? 'P', 0, 1)) }}</span>
                                @endif
                                @if ($producto->requiere_refrigeracion)
                                    <span class="absolute bottom-0.5 right-0.5 grid h-5 w-5 place-items-center rounded-full bg-sky-50 text-sky-600 ring-1 ring-sky-200" title="Requiere refrigeración" aria-label="Requiere refrigeración">
                                        <svg class="h-3 w-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="M12 2v20M2 12h20m-15-7 14 14M19 5 5 19"/></svg>
                                    </span>
                                @endif
                            </div>

                            <div class="min-w-0">
                                <p class="line-clamp-2 font-bold leading-snug text-atlantia-ink md:line-clamp-1">{{ $producto->nombre }}</p>
                                <p class="truncate text-xs text-atlantia-ink/50">{{ $producto->categoria?->nombre ?? 'Sin categoría' }} · <span class="font-mono">{{ $producto->codigo_barras ?: $producto->sku }}</span></p>
                                <p class="mt-0.5 text-xs md:hidden">
                                    <span class="font-black text-atlantia-wine">Q{{ number_format((float) $displayPrice, 2) }}</span>
                                    · <span @class(['font-bold', 'text-rose-700' => $disponible <= 0, 'text-amber-700' => $disponible > 0 && $disponible <= $minimo, 'text-emerald-700' => $disponible > $minimo])>{{ $disponible <= 0 ? 'Agotado' : $disponible.' disponibles' }}</span>
                                </p>
                            </div>

                            <div class="hidden md:block">
                                <p class="font-black text-atlantia-ink">Q{{ number_format((float) $displayPrice, 2) }}</p>
                                @if ($hasOffer)
                                    <p class="text-xs text-atlantia-ink/40 line-through">Q{{ number_format((float) $producto->precio_base, 2) }}</p>
                                @endif
                            </div>

                            <div class="hidden md:block">
                                <p @class(['text-sm font-bold', 'text-rose-700' => $disponible <= 0, 'text-amber-700' => $disponible > 0 && $disponible <= $minimo, 'text-emerald-700' => $disponible > $minimo])>
                                    {{ $disponible <= 0 ? 'Agotado' : ($disponible <= $minimo ? 'Quedan '.$disponible : $disponible.' disponibles') }}
                                </p>
                                @if ($reservado > 0)
                                    <p class="text-xs text-atlantia-ink/45">{{ $reservado }} en pedidos</p>
                                @endif
                            </div>

                            <div class="hidden md:block">
                                <span @class([
                                    'inline-flex rounded-full px-2.5 py-1 text-xs font-bold ring-1',
                                    'bg-emerald-50 text-emerald-700 ring-emerald-200' => $producto->is_active && $producto->visible_catalogo,
                                    'bg-amber-50 text-amber-800 ring-amber-200' => $producto->is_active && ! $producto->visible_catalogo,
                                    'bg-slate-100 text-slate-600 ring-slate-200' => ! $producto->is_active,
                                ]) data-estado-producto>{{ ! $producto->is_active ? 'Desactivado' : ($producto->visible_catalogo ? 'En la tienda' : 'Oculto') }}</span>
                            </div>

                            <div class="flex items-center justify-end gap-1.5">
                                <button type="button" class="h-9 rounded-lg border border-atlantia-rose/25 px-3 text-xs font-bold text-atlantia-wine hover:bg-atlantia-blush" data-open-edit-product-modal="{{ $producto->uuid }}">Editar</button>
                                <details class="relative" data-close-outside>
                                    <summary class="grid h-9 w-9 cursor-pointer list-none place-items-center rounded-lg text-atlantia-ink/55 hover:bg-atlantia-blush hover:text-atlantia-wine [&::-webkit-details-marker]:hidden" aria-label="Más opciones de {{ $producto->nombre }}" title="Más opciones">
                                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><circle cx="12" cy="5" r="1.8"/><circle cx="12" cy="12" r="1.8"/><circle cx="12" cy="19" r="1.8"/></svg>
                                    </summary>
                                    <div class="absolute right-0 top-full z-30 mt-1 w-64 rounded-xl border border-atlantia-rose/15 bg-white p-1.5 shadow-[0_18px_40px_rgba(42,16,24,0.18)]">
                                        <button type="button" class="flex h-9 w-full items-center gap-2 rounded-lg px-3 text-left text-sm font-semibold text-atlantia-ink/80 hover:bg-atlantia-blush hover:text-atlantia-wine" data-print-product-label data-label-type="barcode" @foreach ($etiquetaDatos as $atributo => $valor) {{ $atributo }}="{{ $valor }}" @endforeach>
                                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M4 6v12M7 6v12M10 6v12M14 6v12M17 6v12M20 6v12"/></svg>
                                            Imprimir código de barras
                                        </button>
                                        <button type="button" class="flex h-9 w-full items-center gap-2 rounded-lg px-3 text-left text-sm font-semibold text-atlantia-ink/80 hover:bg-atlantia-blush hover:text-atlantia-wine" data-print-product-label data-label-type="price" @foreach ($etiquetaDatos as $atributo => $valor) {{ $atributo }}="{{ $valor }}" @endforeach>
                                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linejoin="round" aria-hidden="true"><path d="M3 12V4h8l10 10-8 8L3 12Zm5-4h.01"/></svg>
                                            Imprimir etiqueta de precio
                                        </button>
                                        <form method="POST" action="{{ route('vendedor.productos.destroy', $producto->uuid) }}" data-confirm="¿Retirar «{{ $producto->nombre }}» de tu tienda? Los clientes ya no lo verán." class="border-t border-atlantia-rose/10 pt-1">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="flex h-9 w-full items-center gap-2 rounded-lg px-3 text-left text-sm font-semibold text-rose-700 hover:bg-rose-50">
                                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="M3 6h18M8 6V4h8v2M19 6l-1 14H6L5 6"/></svg>
                                                Retirar de la tienda
                                            </button>
                                        </form>
                                    </div>
                                </details>
                            </div>
                        </div>

                        @php
                            $stockActual = (int) ($producto->inventario?->stock_actual ?? 0);
                            $stockReservado = (int) ($producto->inventario?->stock_reservado ?? 0);
                        @endphp
                        <div
                            class="fixed inset-0 z-[72] hidden items-center justify-center bg-slate-950/55 p-3 backdrop-blur-sm"
                            data-edit-product-modal="{{ $producto->uuid }}"
                            role="dialog"
                            aria-modal="true"
                            aria-labelledby="edit-product-title-{{ $producto->uuid }}"
                            x-data="{ paso: 'producto', orden: ['producto', 'precio', 'fotos', 'publicar'], mover(d) { const i = this.orden.indexOf(this.paso) + d; if (i >= 0 && i < this.orden.length) this.paso = this.orden[i]; } }"
                        >
                            <div class="flex max-h-[calc(100vh-1.5rem)] w-full max-w-3xl flex-col overflow-hidden rounded-2xl border border-atlantia-rose/20 bg-white shadow-[0_28px_90px_rgba(33,25,32,0.28)]">
                                {{-- Encabezado --}}
                                <div class="flex items-center justify-between gap-3 border-b border-atlantia-rose/10 px-5 py-3.5">
                                    <div class="min-w-0">
                                        <h2 id="edit-product-title-{{ $producto->uuid }}" class="text-xl font-black text-atlantia-ink">Editar producto</h2>
                                        <p class="truncate text-sm text-atlantia-ink/55">{{ $producto->nombre }}</p>
                                    </div>
                                    <button type="button" class="rounded-lg p-2 text-atlantia-ink/50 transition hover:bg-atlantia-blush hover:text-atlantia-wine" data-close-edit-product-modal aria-label="Cerrar">
                                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="m6 6 12 12M18 6 6 18"/></svg>
                                    </button>
                                </div>

                                {{-- Pasos --}}
                                <nav class="grid grid-cols-4 gap-1 border-b border-atlantia-rose/10 bg-atlantia-blush/20 px-3 py-2" aria-label="Secciones del producto">
                                    @foreach ($pasosProducto as $clave => [$numero, $texto])
                                        <button type="button" x-on:click="paso = @js($clave)"
                                            @class(['flex items-center justify-center gap-2 rounded-lg px-2 py-2 text-sm font-bold transition', 'bg-white text-atlantia-wine shadow-sm' => $loop->first, 'text-atlantia-ink/55 hover:bg-white/60' => ! $loop->first])
                                            :class="paso === @js($clave) ? 'bg-white text-atlantia-wine shadow-sm' : 'text-atlantia-ink/55 hover:bg-white/60'">
                                            <span class="grid h-5 w-5 shrink-0 place-items-center rounded-full text-[11px] font-black" :class="paso === @js($clave) ? 'bg-atlantia-wine text-white' : 'bg-atlantia-ink/10'">{{ $numero }}</span>
                                            <span class="hidden sm:inline">{{ $texto }}</span>
                                        </button>
                                    @endforeach
                                </nav>

                                <form method="POST" action="{{ route('vendedor.productos.update', $producto->uuid) }}" enctype="multipart/form-data" class="flex min-h-0 flex-1 flex-col"
                                    x-on:invalid.capture="const panel = $event.target.closest('[data-paso]'); if (panel) paso = panel.dataset.paso">
                                    @csrf
                                    @method('PUT')
                                    <input type="hidden" name="precio_sin_iva" value="0">
                                    <div class="min-h-0 flex-1 overflow-y-auto px-5 py-4">

                                        {{-- 1. Producto --}}
                                        <div data-paso="producto" class="space-y-3" :class="{ 'hidden': paso !== 'producto' }">
                                            <div class="grid gap-3 sm:grid-cols-2">
                                                <label class="block sm:col-span-2">
                                                    <span class="text-xs font-bold text-atlantia-ink">Nombre del producto <span class="text-atlantia-wine">*</span></span>
                                                    <input name="nombre" value="{{ $producto->nombre }}" class="{{ $inputBase }} {{ $inputNormal }}" required minlength="3" maxlength="180">
                                                </label>
                                                <label class="block">
                                                    <span class="text-xs font-bold text-atlantia-ink">Categoría <span class="text-atlantia-wine">*</span></span>
                                                    <select name="categoria_id" class="{{ $inputBase }} {{ $inputNormal }}" required>
                                                        @foreach ($categorias as $categoria)
                                                            <option value="{{ $categoria->id }}" @selected((int) $producto->categoria_id === (int) $categoria->id)>{{ $categoria->nombre }}</option>
                                                        @endforeach
                                                    </select>
                                                </label>
                                                <label class="block">
                                                    <span class="text-xs font-bold text-atlantia-ink">Se vende por <span class="text-atlantia-wine">*</span></span>
                                                    <select name="unidad_medida" class="{{ $inputBase }} {{ $inputNormal }}" required>
                                                        @foreach (['unidad' => 'Unidad', 'libra' => 'Libra', 'kilogramo' => 'Kilogramo', 'gramo' => 'Gramo', 'litro' => 'Litro', 'mililitro' => 'Mililitro', 'paquete' => 'Paquete'] as $valor => $texto)
                                                            <option value="{{ $valor }}" @selected($producto->unidad_medida === $valor)>{{ $texto }}</option>
                                                        @endforeach
                                                    </select>
                                                </label>
                                                <label class="block sm:col-span-2">
                                                    <span class="text-xs font-bold text-atlantia-ink">Descripción <span class="font-normal text-atlantia-ink/45">(opcional)</span></span>
                                                    <textarea name="descripcion" rows="3" maxlength="5000" class="{{ $inputBase }} {{ $inputNormal }}" placeholder="Cuéntale al cliente cómo es: tamaño, sabor, marca, para qué sirve...">{{ $producto->descripcion }}</textarea>
                                                </label>
                                            </div>
                                            <div class="grid gap-3 rounded-xl bg-atlantia-blush/25 p-3 sm:grid-cols-2">
                                                <label class="block">
                                                    <span class="text-[11px] font-bold text-atlantia-ink/60">Código interno</span>
                                                    {{-- Si no tiene código, no se envía el campo: así no bloquea guardar otros cambios. --}}
                                                    <input @if (filled($producto->sku)) name="sku" required @else disabled placeholder="Sin código interno" @endif value="{{ $producto->sku }}" class="{{ $inputBase }} {{ $inputNormal }} bg-white/70 font-mono text-xs font-black uppercase tracking-wide text-atlantia-wine">
                                                </label>
                                                <label class="block">
                                                    <span class="text-[11px] font-bold text-atlantia-ink/60">Código de barras</span>
                                                    <input @if (filled($producto->codigo_barras)) name="codigo_barras" required @else disabled placeholder="Sin código de barras" @endif value="{{ $producto->codigo_barras }}" class="{{ $inputBase }} {{ $inputNormal }} bg-white/70 font-mono text-xs font-black tracking-[0.14em] text-atlantia-ink/70">
                                                </label>
                                            </div>
                                        </div>

                                        {{-- 2. Precio y cantidad --}}
                                        <div data-paso="precio" class="hidden space-y-3" :class="{ 'hidden': paso !== 'precio' }">
                                            <div class="grid gap-3 sm:grid-cols-2">
                                                <label class="block">
                                                    <span class="text-xs font-bold text-atlantia-ink">Precio de venta <span class="text-atlantia-wine">*</span> <span class="font-normal text-atlantia-ink/45">(lo que paga el cliente, con IVA)</span></span>
                                                    <span class="relative mt-1 block"><span class="pointer-events-none absolute inset-y-0 left-3 flex items-center text-sm font-black text-atlantia-ink/45">Q</span><input name="precio_base" inputmode="decimal" value="{{ number_format((float) $producto->precio_base, 2, '.', '') }}" class="{{ $inputBase }} {{ $inputNormal }} !mt-0 pl-8" data-iva-input data-iva-modo="final" required></span>
                                                    <span class="mt-1 block text-[11px] font-bold text-emerald-700" data-iva-output>Escribe el precio para ver el desglose</span>
                                                </label>
                                                <label class="block">
                                                    <span class="text-xs font-bold text-atlantia-ink">Precio de oferta <span class="font-normal text-atlantia-ink/45">(opcional, con IVA)</span></span>
                                                    <span class="relative mt-1 block"><span class="pointer-events-none absolute inset-y-0 left-3 flex items-center text-sm font-black text-atlantia-ink/45">Q</span><input name="precio_oferta" inputmode="decimal" value="{{ $producto->precio_oferta !== null ? number_format((float) $producto->precio_oferta, 2, '.', '') : '' }}" placeholder="Vacío si no hay oferta" class="{{ $inputBase }} {{ $inputNormal }} !mt-0 pl-8" data-iva-input data-iva-modo="final"></span>
                                                    <span class="mt-1 block text-[11px] font-bold text-emerald-700" data-iva-output data-iva-empty="Sin oferta">Sin oferta</span>
                                                </label>
                                            </div>
                                            <div class="grid gap-3 sm:grid-cols-3">
                                                <label class="block">
                                                    <span class="text-xs font-bold text-atlantia-ink">¿Cuántos tienes?</span>
                                                    <input name="stock_actual" type="number" min="0" value="{{ $stockActual }}" class="{{ $inputBase }} {{ $inputNormal }}">
                                                </label>
                                                <label class="block">
                                                    <span class="text-xs font-bold text-atlantia-ink">Avisarme cuando queden</span>
                                                    <input name="stock_minimo" type="number" min="0" value="{{ (int) ($producto->inventario?->stock_minimo ?? 5) }}" class="{{ $inputBase }} {{ $inputNormal }}">
                                                </label>
                                                <label class="block">
                                                    <span class="text-xs font-bold text-atlantia-ink">Máximo en bodega <span class="font-normal text-atlantia-ink/45">(opcional)</span></span>
                                                    <input name="stock_maximo" type="number" min="0" value="{{ (int) ($producto->inventario?->stock_maximo ?? 100) }}" class="{{ $inputBase }} {{ $inputNormal }}">
                                                </label>
                                            </div>
                                            @if ($stockReservado > 0)
                                                <p class="rounded-xl bg-sky-50 px-4 py-2.5 text-xs text-sky-900">
                                                    <strong>{{ $stockReservado }}</strong> {{ $stockReservado === 1 ? 'está apartado' : 'están apartados' }} en pedidos sin entregar. Puedes vender <strong>{{ max(0, $stockActual - $stockReservado) }}</strong> más.
                                                </p>
                                            @endif
                                            <label class="block sm:max-w-xs">
                                                <span class="text-xs font-bold text-atlantia-ink">Peso en gramos <span class="font-normal text-atlantia-ink/45">(opcional, ayuda al repartidor)</span></span>
                                                <input name="peso_gramos" type="number" min="1" value="{{ $producto->peso_gramos }}" placeholder="Ej. 454" class="{{ $inputBase }} {{ $inputNormal }}">
                                            </label>
                                        </div>

                                        {{-- 3. Fotos --}}
                                        <div data-paso="fotos" class="hidden" :class="{ 'hidden': paso !== 'fotos' }">
                                            <div class="space-y-3">
                                                @if ($producto->imagenes->isNotEmpty())
                                                    <div>
                                                        <p class="mb-2 text-xs font-bold text-atlantia-ink/60">Fotos actuales</p>
                                                        <div class="grid grid-cols-[repeat(auto-fill,minmax(88px,1fr))] gap-2">
                                                            @foreach ($producto->imagenes as $imagen)
                                                                <div class="overflow-hidden rounded-lg border border-atlantia-rose/15 bg-atlantia-cream">
                                                                    <img src="{{ \Illuminate\Support\Facades\Storage::url($imagen->path) }}" alt="{{ $imagen->alt_text }}" class="h-20 w-full object-cover">
                                                                </div>
                                                            @endforeach
                                                        </div>
                                                    </div>
                                                @endif
                                                <label class="flex cursor-pointer flex-col items-center justify-center rounded-xl border-2 border-dashed border-atlantia-rose/40 bg-atlantia-blush/15 px-4 py-6 text-center transition hover:border-atlantia-wine hover:bg-atlantia-blush/35">
                                                    <svg class="h-7 w-7 text-atlantia-wine" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 16V4"/><path d="m7 9 5-5 5 5"/><path d="M4 20h16"/></svg>
                                                    <span class="mt-2 text-sm font-black text-atlantia-wine">{{ $producto->imagenes->isNotEmpty() ? 'Agregar más fotos' : 'Toca aquí para subir fotos' }}</span>
                                                    <span class="mt-1 text-xs text-atlantia-ink/55">Hasta 8 fotos (JPG, PNG o WEBP, máximo 5 MB cada una).</span>
                                                    <input name="imagenes[]" type="file" accept="image/png,image/jpeg,image/webp" multiple class="sr-only" data-product-image-input>
                                                </label>
                                                <div class="hidden grid-cols-[repeat(auto-fill,minmax(96px,1fr))] gap-2" data-product-image-preview></div>
                                            </div>
                                        </div>

                                        {{-- 4. Publicar --}}
                                        <div data-paso="publicar" class="hidden space-y-2" :class="{ 'hidden': paso !== 'publicar' }">
                                            @foreach ([
                                                ['is_active', 'Producto activo', 'Puedes venderlo. Desactívalo si ya no lo tienes.', $producto->is_active],
                                                ['visible_catalogo', 'Mostrar en la tienda', 'Los clientes lo ven en tu tienda y en el catálogo.', $producto->visible_catalogo],
                                                ['requiere_refrigeracion', 'Necesita refrigeración', 'El repartidor lo llevará con cuidado de la temperatura.', $producto->requiere_refrigeracion],
                                            ] as [$campo, $titulo, $ayuda, $marcado])
                                                <label class="flex cursor-pointer items-center gap-3 rounded-xl border border-atlantia-rose/15 px-4 py-3 transition hover:bg-atlantia-blush/20">
                                                    <input type="hidden" name="{{ $campo }}" value="0">
                                                    <input type="checkbox" name="{{ $campo }}" value="1" @checked($marcado) class="peer sr-only">
                                                    <span class="flex h-5 w-9 shrink-0 items-center rounded-full bg-slate-200 p-0.5 transition peer-checked:bg-atlantia-wine peer-checked:[&>span]:translate-x-4">
                                                        <span class="h-4 w-4 rounded-full bg-white shadow transition"></span>
                                                    </span>
                                                    <span class="min-w-0">
                                                        <span class="block text-sm font-black text-atlantia-ink">{{ $titulo }}</span>
                                                        <span class="block text-xs text-atlantia-ink/55">{{ $ayuda }}</span>
                                                    </span>
                                                </label>
                                            @endforeach
                                        </div>
                                    </div>

                                    {{-- Botones siempre visibles --}}
                                    <div class="flex items-center justify-between gap-2 border-t border-atlantia-rose/10 bg-white px-5 py-3">
                                        <button type="button" class="rounded-lg px-3 py-2 text-sm font-black text-atlantia-ink/55 hover:bg-atlantia-blush/40 hover:text-atlantia-wine" data-close-edit-product-modal>Cancelar</button>
                                        <div class="flex items-center gap-2">
                                            <button type="button" x-on:click="mover(-1)" x-show="paso !== 'producto'" class="rounded-lg border border-atlantia-rose/30 bg-white px-4 py-2 text-sm font-black text-atlantia-wine hover:bg-atlantia-blush">← Anterior</button>
                                            <button type="button" x-on:click="mover(1)" x-show="paso !== 'publicar'" class="rounded-lg border border-atlantia-rose/30 bg-white px-4 py-2 text-sm font-black text-atlantia-wine hover:bg-atlantia-blush">Siguiente →</button>
                                            <button type="submit" class="rounded-lg bg-atlantia-wine px-5 py-2 text-sm font-black text-white shadow-sm transition hover:bg-atlantia-wine-700">Guardar cambios</button>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                    @empty
                        <div class="px-6 py-14 text-center">
                            @if ($activeFilters->isNotEmpty())
                                <p class="text-base font-black text-atlantia-ink">No hay productos con estos filtros</p>
                                <p class="mt-1 text-sm text-atlantia-ink/60">Prueba con otra búsqueda o <a href="{{ route('vendedor.productos.index') }}" class="font-bold text-atlantia-wine hover:underline">quita los filtros</a>.</p>
                            @else
                                <p class="text-base font-black text-atlantia-ink">Aún no tienes productos</p>
                                <p class="mt-1 text-sm text-atlantia-ink/60">Crea el primero o súbelos todos de una vez con la carga masiva.</p>
                                <button type="button" class="mt-4 h-10 rounded-lg bg-atlantia-wine px-5 text-sm font-bold text-white hover:bg-atlantia-wine-700" data-open-create-product-modal @disabled(! $puedeCrear)>Crear producto</button>
                            @endif
                        </div>
                    @endforelse
                </div>

                @if ($productos->hasPages())
                    <div class="border-t border-atlantia-rose/10 px-4 py-3">
                        {{ $productos->links() }}
                    </div>
                @endif
            </div>
        </article>
    </section>

    @php
        /* Si el servidor devolvió errores, se abre la pestaña donde está el primero. */
        $pasoInicial = match (true) {
            $errors->hasAny(['categoria_id', 'nombre', 'sku', 'codigo_barras', 'descripcion', 'unidad_medida']) => 'producto',
            $errors->hasAny(['precio_base', 'precio_oferta', 'stock_actual', 'stock_minimo', 'stock_maximo', 'peso_gramos']) => 'precio',
            $errors->hasAny(['imagenes', 'imagenes.*']) => 'fotos',
            default => 'producto',
        };
    @endphp

    <div
        class="{{ $showCreateModal ? 'flex' : 'hidden' }} fixed inset-0 z-[70] items-center justify-center bg-slate-950/55 p-3 backdrop-blur-sm"
        data-create-product-modal
        role="dialog"
        aria-modal="true"
        aria-labelledby="create-product-title"
        x-data="{ paso: @js($pasoInicial), orden: @js(array_keys($pasosProducto)), mover(d) { const i = this.orden.indexOf(this.paso) + d; if (i >= 0 && i < this.orden.length) this.paso = this.orden[i]; } }"
    >
        <div class="flex max-h-[calc(100vh-1.5rem)] w-full max-w-3xl flex-col overflow-hidden rounded-2xl border border-atlantia-rose/20 bg-white shadow-[0_28px_90px_rgba(33,25,32,0.28)]">
            {{-- Encabezado --}}
            <div class="flex items-center justify-between gap-3 border-b border-atlantia-rose/10 px-5 py-3.5">
                <div class="min-w-0">
                    <h2 id="create-product-title" class="text-xl font-black text-atlantia-ink">Nuevo producto</h2>
                    <p class="truncate text-sm text-atlantia-ink/55" data-product-modal-subtitle>Nuevo producto</p>
                </div>
                <button type="button" class="rounded-lg p-2 text-atlantia-ink/50 transition hover:bg-atlantia-blush hover:text-atlantia-wine" data-close-create-product-modal aria-label="Cerrar">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="m6 6 12 12M18 6 6 18"/></svg>
                </button>
            </div>

            {{-- Pasos --}}
            <nav class="grid grid-cols-4 gap-1 border-b border-atlantia-rose/10 bg-atlantia-blush/20 px-3 py-2" aria-label="Secciones del producto">
                @foreach ($pasosProducto as $clave => [$numero, $texto])
                    <button type="button" x-on:click="paso = @js($clave)"
                        @class(['flex items-center justify-center gap-2 rounded-lg px-2 py-2 text-sm font-bold transition', 'bg-white text-atlantia-wine shadow-sm' => $clave === $pasoInicial, 'text-atlantia-ink/55 hover:bg-white/60' => $clave !== $pasoInicial])
                        :class="paso === @js($clave) ? 'bg-white text-atlantia-wine shadow-sm' : 'text-atlantia-ink/55 hover:bg-white/60'">
                        <span class="grid h-5 w-5 shrink-0 place-items-center rounded-full text-[11px] font-black" :class="paso === @js($clave) ? 'bg-atlantia-wine text-white' : 'bg-atlantia-ink/10'">{{ $numero }}</span>
                        <span class="hidden sm:inline">{{ $texto }}</span>
                    </button>
                @endforeach
            </nav>

            <form method="POST" action="{{ route('vendedor.productos.store') }}" enctype="multipart/form-data" class="flex min-h-0 flex-1 flex-col"
                x-on:invalid.capture="const panel = $event.target.closest('[data-paso]'); if (panel) paso = panel.dataset.paso"
                x-on:keydown.enter="if (paso !== 'publicar' && $event.target.tagName !== 'TEXTAREA') { $event.preventDefault(); mover(1); }">
                @csrf
                <input type="hidden" name="precio_sin_iva" value="0">
                <fieldset @disabled(! $puedeCrear) class="min-h-0 flex-1 overflow-y-auto px-5 py-4">

                    {{-- 1. Producto --}}
                    <div data-paso="producto" @class(['space-y-3', 'hidden' => $pasoInicial !== 'producto']) :class="{ 'hidden': paso !== 'producto' }">
                        <div class="grid gap-3 sm:grid-cols-2">
                            <label class="block sm:col-span-2">
                                <span class="text-xs font-bold text-atlantia-ink">Nombre del producto <span class="text-atlantia-wine">*</span></span>
                                <input name="nombre" value="{{ old('nombre') }}" placeholder="Ej. Arroz blanco 1 libra" class="{{ $inputBase }} {{ $errors->has('nombre') ? $inputError : $inputNormal }}" data-product-name required minlength="3" maxlength="180">
                                @error('nombre') <p class="{{ $errorText }}">{{ $message }}</p> @enderror
                            </label>
                            <label class="block">
                                <span class="text-xs font-bold text-atlantia-ink">Categoría <span class="text-atlantia-wine">*</span></span>
                                <select name="categoria_id" class="{{ $inputBase }} {{ $errors->has('categoria_id') ? $inputError : $inputNormal }}" required>
                                    <option value="">Elige una categoría</option>
                                    @foreach ($categorias as $categoria)
                                        <option value="{{ $categoria->id }}" @selected((string) old('categoria_id') === (string) $categoria->id)>{{ $categoria->nombre }}</option>
                                    @endforeach
                                </select>
                                @error('categoria_id') <p class="{{ $errorText }}">{{ $message }}</p> @enderror
                            </label>
                            <label class="block">
                                <span class="text-xs font-bold text-atlantia-ink">Se vende por <span class="text-atlantia-wine">*</span></span>
                                <select name="unidad_medida" class="{{ $inputBase }} {{ $inputNormal }}" required>
                                    @foreach (['unidad' => 'Unidad', 'libra' => 'Libra', 'kilogramo' => 'Kilogramo', 'gramo' => 'Gramo', 'litro' => 'Litro', 'mililitro' => 'Mililitro', 'paquete' => 'Paquete'] as $valor => $texto)
                                        <option value="{{ $valor }}" @selected(old('unidad_medida', 'unidad') === $valor)>{{ $texto }}</option>
                                    @endforeach
                                </select>
                            </label>
                            <label class="block sm:col-span-2">
                                <span class="text-xs font-bold text-atlantia-ink">Descripción <span class="font-normal text-atlantia-ink/45">(opcional)</span></span>
                                <textarea name="descripcion" rows="3" maxlength="5000" class="{{ $inputBase }} {{ $errors->has('descripcion') ? $inputError : $inputNormal }}" placeholder="Cuéntale al cliente cómo es: tamaño, sabor, marca, para qué sirve...">{{ old('descripcion') }}</textarea>
                                @error('descripcion') <p class="{{ $errorText }}">{{ $message }}</p> @enderror
                            </label>
                        </div>
                        <div class="grid gap-3 rounded-xl bg-atlantia-blush/25 p-3 sm:grid-cols-2">
                            <label class="block">
                                <span class="text-[11px] font-bold text-atlantia-ink/60">Código interno (se crea solo)</span>
                                <input name="sku" value="{{ old('sku') }}" placeholder="Se crea al escribir el nombre" class="{{ $inputBase }} {{ $errors->has('sku') ? $inputError : $inputNormal }} bg-white/70 font-mono text-xs font-black uppercase tracking-wide text-atlantia-wine" data-product-sku required readonly>
                                @error('sku') <p class="{{ $errorText }}">{{ $message }}</p> @enderror
                            </label>
                            <label class="block">
                                <span class="text-[11px] font-bold text-atlantia-ink/60">Código de barras (se crea solo)</span>
                                <input name="codigo_barras" value="{{ old('codigo_barras') }}" placeholder="Se crea al escribir el nombre" class="{{ $inputBase }} {{ $errors->has('codigo_barras') ? $inputError : $inputNormal }} bg-white/70 font-mono text-xs font-black tracking-[0.14em] text-atlantia-ink/70" data-product-barcode required readonly>
                                @error('codigo_barras') <p class="{{ $errorText }}">{{ $message }}</p> @enderror
                            </label>
                        </div>
                    </div>

                    {{-- 2. Precio y cantidad --}}
                    <div data-paso="precio" @class(['space-y-3', 'hidden' => $pasoInicial !== 'precio']) :class="{ 'hidden': paso !== 'precio' }">
                        <div class="grid gap-3 sm:grid-cols-2">
                            <label class="block">
                                <span class="text-xs font-bold text-atlantia-ink">Precio de venta <span class="text-atlantia-wine">*</span> <span class="font-normal text-atlantia-ink/45">(lo que paga el cliente, con IVA)</span></span>
                                <span class="relative mt-1 block"><span class="pointer-events-none absolute inset-y-0 left-3 flex items-center text-sm font-black text-atlantia-ink/45">Q</span><input name="precio_base" inputmode="decimal" value="{{ old('precio_base') }}" placeholder="149.00" class="{{ $inputBase }} {{ $errors->has('precio_base') ? $inputError : $inputNormal }} !mt-0 pl-8" data-price-base data-iva-input data-iva-modo="final" required></span>
                                <span class="mt-1 block text-[11px] font-bold text-emerald-700" data-iva-output>Escribe el precio para ver el desglose</span>
                                @error('precio_base') <p class="{{ $errorText }}">{{ $message }}</p> @enderror
                            </label>
                            <label class="block">
                                <span class="text-xs font-bold text-atlantia-ink">Precio de oferta <span class="font-normal text-atlantia-ink/45">(opcional, con IVA)</span></span>
                                <span class="relative mt-1 block"><span class="pointer-events-none absolute inset-y-0 left-3 flex items-center text-sm font-black text-atlantia-ink/45">Q</span><input name="precio_oferta" inputmode="decimal" value="{{ old('precio_oferta') }}" placeholder="Vacío si no hay oferta" class="{{ $inputBase }} {{ $errors->has('precio_oferta') ? $inputError : $inputNormal }} !mt-0 pl-8" data-iva-input data-iva-modo="final"></span>
                                <span class="mt-1 block text-[11px] font-bold text-emerald-700" data-iva-output data-iva-empty="Sin oferta">Sin oferta</span>
                                @error('precio_oferta') <p class="{{ $errorText }}">{{ $message }}</p> @enderror
                            </label>
                        </div>
                        <div class="grid gap-3 sm:grid-cols-3">
                            <label class="block">
                                <span class="text-xs font-bold text-atlantia-ink">¿Cuántos tienes? <span class="text-atlantia-wine">*</span></span>
                                <input name="stock_actual" type="number" min="0" value="{{ old('stock_actual', 0) }}" class="{{ $inputBase }} {{ $errors->has('stock_actual') ? $inputError : $inputNormal }}" required>
                                @error('stock_actual') <p class="{{ $errorText }}">{{ $message }}</p> @enderror
                            </label>
                            <label class="block">
                                <span class="text-xs font-bold text-atlantia-ink">Avisarme cuando queden <span class="text-atlantia-wine">*</span></span>
                                <input name="stock_minimo" type="number" min="0" value="{{ old('stock_minimo', 5) }}" class="{{ $inputBase }} {{ $errors->has('stock_minimo') ? $inputError : $inputNormal }}" required>
                                @error('stock_minimo') <p class="{{ $errorText }}">{{ $message }}</p> @enderror
                            </label>
                            <label class="block">
                                <span class="text-xs font-bold text-atlantia-ink">Máximo en bodega <span class="font-normal text-atlantia-ink/45">(opcional)</span></span>
                                <input name="stock_maximo" type="number" min="0" value="{{ old('stock_maximo', 100) }}" class="{{ $inputBase }} {{ $errors->has('stock_maximo') ? $inputError : $inputNormal }}">
                                @error('stock_maximo') <p class="{{ $errorText }}">{{ $message }}</p> @enderror
                            </label>
                        </div>
                        <label class="block sm:max-w-xs">
                            <span class="text-xs font-bold text-atlantia-ink">Peso en gramos <span class="font-normal text-atlantia-ink/45">(opcional, ayuda al repartidor)</span></span>
                            <input name="peso_gramos" type="number" min="1" value="{{ old('peso_gramos') }}" placeholder="Ej. 454" class="{{ $inputBase }} {{ $errors->has('peso_gramos') ? $inputError : $inputNormal }}">
                            @error('peso_gramos') <p class="{{ $errorText }}">{{ $message }}</p> @enderror
                        </label>
                    </div>

                    {{-- 3. Fotos --}}
                    <div data-paso="fotos" @class(['hidden' => $pasoInicial !== 'fotos']) :class="{ 'hidden': paso !== 'fotos' }">
                        <div class="space-y-3">
                            <label class="flex cursor-pointer flex-col items-center justify-center rounded-xl border-2 border-dashed border-atlantia-rose/40 bg-atlantia-blush/15 px-4 py-8 text-center transition hover:border-atlantia-wine hover:bg-atlantia-blush/35">
                                <svg class="h-8 w-8 text-atlantia-wine" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 16V4"/><path d="m7 9 5-5 5 5"/><path d="M4 20h16"/></svg>
                                <span class="mt-2 text-sm font-black text-atlantia-wine">Toca aquí para subir fotos</span>
                                <span class="mt-1 text-xs text-atlantia-ink/55">Hasta 8 fotos (JPG, PNG o WEBP, máximo 5 MB cada una). La primera será la principal.</span>
                                <input name="imagenes[]" type="file" accept="image/png,image/jpeg,image/webp" multiple class="sr-only" data-product-image-input>
                            </label>
                            <div class="hidden grid-cols-[repeat(auto-fill,minmax(96px,1fr))] gap-2" data-product-image-preview></div>
                        </div>
                        @error('imagenes') <p class="{{ $errorText }}">{{ $message }}</p> @enderror
                        @error('imagenes.*') <p class="{{ $errorText }}">{{ $message }}</p> @enderror
                    </div>

                    {{-- 4. Publicar --}}
                    <div data-paso="publicar" @class(['space-y-2', 'hidden' => $pasoInicial !== 'publicar']) :class="{ 'hidden': paso !== 'publicar' }">
                        @foreach ([
                            ['is_active', 'Producto activo', 'Puedes venderlo. Desactívalo si ya no lo tienes.', old('is_active', true)],
                            ['visible_catalogo', 'Mostrar en la tienda', 'Los clientes lo ven en tu tienda y en el catálogo.', old('visible_catalogo', true)],
                            ['requiere_refrigeracion', 'Necesita refrigeración', 'El repartidor lo llevará con cuidado de la temperatura.', old('requiere_refrigeracion', false)],
                        ] as [$campo, $titulo, $ayuda, $marcado])
                            <label class="flex cursor-pointer items-center gap-3 rounded-xl border border-atlantia-rose/15 px-4 py-3 transition hover:bg-atlantia-blush/20">
                                <input type="hidden" name="{{ $campo }}" value="0">
                                <input type="checkbox" name="{{ $campo }}" value="1" @checked($marcado) class="peer sr-only">
                                <span class="flex h-5 w-9 shrink-0 items-center rounded-full bg-slate-200 p-0.5 transition peer-checked:bg-atlantia-wine peer-checked:[&>span]:translate-x-4">
                                    <span class="h-4 w-4 rounded-full bg-white shadow transition"></span>
                                </span>
                                <span class="min-w-0">
                                    <span class="block text-sm font-black text-atlantia-ink">{{ $titulo }}</span>
                                    <span class="block text-xs text-atlantia-ink/55">{{ $ayuda }}</span>
                                </span>
                            </label>
                        @endforeach
                        <p class="rounded-xl bg-emerald-50 px-4 py-3 text-xs text-emerald-900">
                            Al guardar, el producto queda en tu catálogo. Puedes cambiar el precio, la cantidad o las fotos cuando quieras.
                        </p>
                    </div>
                </fieldset>

                {{-- Botones: se avanza paso a paso y se guarda al final --}}
                <div class="flex items-center justify-between gap-2 border-t border-atlantia-rose/10 bg-white px-5 py-3">
                    <button type="button" class="rounded-lg px-3 py-2 text-sm font-black text-atlantia-ink/55 hover:bg-atlantia-blush/40 hover:text-atlantia-wine" data-close-create-product-modal>Cancelar</button>
                    <div class="flex items-center gap-2">
                        <button type="button" x-on:click="mover(-1)" @class(['rounded-lg border border-atlantia-rose/30 bg-white px-4 py-2 text-sm font-black text-atlantia-wine hover:bg-atlantia-blush', 'hidden' => $pasoInicial === 'producto']) :class="{ 'hidden': paso === 'producto' }">← Anterior</button>
                        <button type="button" x-on:click="mover(1)" @class(['rounded-lg bg-atlantia-wine px-5 py-2 text-sm font-black text-white shadow-sm transition hover:bg-atlantia-wine-700', 'hidden' => $pasoInicial === 'publicar']) :class="{ 'hidden': paso === 'publicar' }">Siguiente →</button>
                        <button type="submit" @class(['rounded-lg bg-atlantia-wine px-5 py-2 text-sm font-black text-white shadow-sm transition hover:bg-atlantia-wine-700 disabled:cursor-not-allowed disabled:opacity-55', 'hidden' => $pasoInicial !== 'publicar']) :class="{ 'hidden': paso !== 'publicar' }" @disabled(! $puedeCrear)>Guardar producto</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
    <script nonce="{{ request()->attributes->get('csp_nonce') }}">
        (() => {
            const modal = document.querySelector('[data-create-product-modal]');

            const openModal = () => {
                modal?.classList.remove('hidden');
                modal?.classList.add('flex');
                document.documentElement.classList.add('overflow-hidden');
            };

            const closeModal = () => {
                modal?.classList.add('hidden');
                modal?.classList.remove('flex');
                document.documentElement.classList.remove('overflow-hidden');
            };

            document.querySelectorAll('[data-open-create-product-modal]').forEach((button) => {
                button.addEventListener('click', openModal);
            });

            document.querySelectorAll('[data-close-create-product-modal]').forEach((button) => {
                button.addEventListener('click', closeModal);
            });

            document.querySelectorAll('[data-open-edit-product-modal]').forEach((button) => {
                button.addEventListener('click', () => {
                    const modal = document.querySelector(`[data-edit-product-modal="${button.dataset.openEditProductModal}"]`);

                    modal?.classList.remove('hidden');
                    modal?.classList.add('flex');
                    document.documentElement.classList.add('overflow-hidden');
                });
            });

            document.querySelectorAll('[data-close-edit-product-modal]').forEach((button) => {
                button.addEventListener('click', () => {
                    const modal = button.closest('[data-edit-product-modal]');

                    modal?.classList.add('hidden');
                    modal?.classList.remove('flex');
                    document.documentElement.classList.remove('overflow-hidden');
                });
            });

            document.querySelectorAll('[data-edit-product-modal]').forEach((editModal) => {
                editModal.addEventListener('click', (event) => {
                    if (event.target === editModal) {
                        editModal.classList.add('hidden');
                        editModal.classList.remove('flex');
                        document.documentElement.classList.remove('overflow-hidden');
                    }
                });
            });

            modal?.addEventListener('click', (event) => {
                if (event.target === modal) {
                    closeModal();
                }
            });

            document.addEventListener('keydown', (event) => {
                if (event.key === 'Escape') {
                    closeModal();
                    document.querySelectorAll('[data-edit-product-modal]').forEach((editModal) => {
                        editModal.classList.add('hidden');
                        editModal.classList.remove('flex');
                    });
                    document.documentElement.classList.remove('overflow-hidden');
                }
            });

            document.querySelectorAll('[data-vendor-product-filter]').forEach((control) => {
                control.addEventListener('change', () => control.form?.requestSubmit());
            });

            const normalizeName = (value) => String(value ?? '')
                .normalize('NFD')
                .replace(/[\u0300-\u036f]/g, '')
                .replace(/[^a-zA-Z0-9]+/g, ' ')
                .trim();

            const skuFromName = (value) => {
                const normalized = normalizeName(value)
                    .toUpperCase()
                    .split(/\s+/)
                    .filter(Boolean)
                    .slice(0, 3)
                    .join('-');

                return normalized ? `TAL-${normalized}` : '';
            };

            const ean13CheckDigit = (body) => {
                const sum = body.split('').reduce((total, digit, index) => {
                    return total + (Number(digit) * (index % 2 === 0 ? 1 : 3));
                }, 0);

                return String((10 - (sum % 10)) % 10);
            };

            const barcodeFromName = (value) => {
                const normalized = normalizeName(value).toUpperCase();

                if (!normalized) {
                    return '';
                }

                let hash = 0;

                for (let index = 0; index < normalized.length; index += 1) {
                    hash = ((hash * 31) + normalized.charCodeAt(index)) >>> 0;
                }

                const body = `750${String(hash).padStart(9, '0').slice(-9)}`;

                return `${body}${ean13CheckDigit(body)}`;
            };

            document.querySelectorAll('[data-product-name]').forEach((nameInput) => {
                const form = nameInput.closest('form');
                const skuInput = form?.querySelector('[data-product-sku]');
                const barcodeInput = form?.querySelector('[data-product-barcode]');
                const subtitle = document.querySelector('[data-product-modal-subtitle]');
                const syncCodes = () => {
                    if (skuInput && !skuInput.dataset.touchedManually) {
                        skuInput.value = skuFromName(nameInput.value);
                    }

                    if (barcodeInput && !barcodeInput.dataset.touchedManually) {
                        barcodeInput.value = barcodeFromName(nameInput.value);
                    }

                    if (subtitle) {
                        subtitle.textContent = nameInput.value.trim() || 'Nuevo producto';
                    }
                };

                skuInput?.addEventListener('input', () => {
                    skuInput.dataset.touchedManually = 'true';
                });
                barcodeInput?.addEventListener('input', () => {
                    barcodeInput.dataset.touchedManually = 'true';
                });
                nameInput.addEventListener('input', syncCodes);
                syncCodes();
            });

            document.querySelectorAll('input[name="stock_actual"]').forEach((stockInput) => {
                const form = stockInput.closest('form');
                const availableInput = form?.querySelector('[data-stock-available]');
                const syncStock = () => {
                    if (availableInput) {
                        availableInput.value = stockInput.value || '0';
                    }
                };

                stockInput.addEventListener('input', syncStock);
                syncStock();
            });

            document.querySelectorAll('[data-product-image-input]').forEach((input) => {
                const preview = input.closest('div')?.querySelector('[data-product-image-preview]');

                input.addEventListener('change', () => {
                    if (!preview) {
                        return;
                    }

                    preview.innerHTML = '';
                    const files = Array.from(input.files ?? []).slice(0, 8);

                    preview.classList.toggle('hidden', files.length === 0);
                    preview.classList.toggle('grid', files.length > 0);

                    files.forEach((file) => {
                        const card = document.createElement('div');
                        card.className = 'overflow-hidden rounded-lg border border-atlantia-rose/20 bg-white shadow-sm';

                        const image = document.createElement('img');
                        image.className = 'h-24 w-full bg-atlantia-cream object-contain p-2';
                        image.alt = file.name;
                        image.src = URL.createObjectURL(file);
                        image.addEventListener('load', () => URL.revokeObjectURL(image.src), { once: true });

                        const name = document.createElement('p');
                        name.className = 'truncate px-2 py-2 text-[11px] font-bold text-atlantia-ink/70';
                        name.textContent = file.name;

                        card.append(image, name);
                        preview.append(card);
                    });
                });
            });

            const escapeHtml = (value) => String(value ?? '')
                .replaceAll('&', '&amp;')
                .replaceAll('<', '&lt;')
                .replaceAll('>', '&gt;')
                .replaceAll('"', '&quot;')
                .replaceAll("'", '&#039;');

            const barcodeBars = (value, height = 42) => {
                const digits = String(value || '7500000000000').replace(/\D/g, '') || '7500000000000';
                const patterns = ['212222', '222122', '222221', '121223', '121322', '131222', '122213', '122312', '132212', '221213'];
                const sequence = `111${digits.split('').map((digit) => patterns[Number(digit) % 10]).join('')}111`;

                return sequence.split('').map((width, index) => {
                    const isBar = index % 2 === 0;
                    const color = isBar ? '#1f1117' : 'transparent';

                    return `<span style="display:inline-block;width:${Number(width)}px;height:${height}px;background:${color}"></span>`;
                }).join('');
            };

            const labelMarkup = (product, isPrice, compact = false) => `
                <article class="label ${isPrice ? 'price-label' : 'barcode-label'} ${compact ? 'compact' : ''}">
                    ${product.image && isPrice ? `<img src="${escapeHtml(product.image)}" alt="">` : ''}
                    <h1>${escapeHtml(product.name)}</h1>
                    <p class="vendor">${escapeHtml(product.vendor)}</p>
                    ${isPrice ? `<p class="price">${escapeHtml(product.price)}</p>` : ''}
                    <div class="barcode">${barcodeBars(product.barcode, isPrice ? 34 : 42)}</div>
                    <p class="code">${escapeHtml(product.barcode)}</p>
                    <p class="sku">SKU: ${escapeHtml(product.sku)}</p>
                    ${!isPrice ? `<p class="mini-price">${escapeHtml(product.price)}</p>` : ''}
                </article>
            `;

            document.querySelectorAll('[data-print-product-label]').forEach((button) => {
                button.addEventListener('click', () => {
                    const mode = button.dataset.labelType;
                    const product = {
                        name: button.dataset.productName,
                        sku: button.dataset.productSku,
                        barcode: button.dataset.productBarcode,
                        price: button.dataset.productPrice,
                        vendor: button.dataset.productVendor,
                        image: button.dataset.productImage,
                    };
                    const isPrice = mode === 'price';
                    const copies = isPrice ? 12 : 30;
                    const labels = Array.from({ length: copies }, () => labelMarkup(product, isPrice, !isPrice)).join('');
                    const popup = window.open('', '_blank', 'width=980,height=720');

                    if (!popup) {
                        return;
                    }

                    popup.document.write(`
                        <!doctype html>
                        <html>
                            <head>
                                @include('layouts.partials.favicon')
                                <title>${isPrice ? 'Etiqueta de precio' : 'Codigo de barras'} - ${escapeHtml(product.name)}</title>
                                <style>
                                    * { box-sizing: border-box; }
                                    @page { size: letter; margin: 10mm; }
                                    body { margin: 0; background: #f7f2f4; font-family: Arial, sans-serif; color: #2a0f12; }
                                    .toolbar {
                                        position: sticky;
                                        top: 0;
                                        z-index: 2;
                                        display: flex;
                                        align-items: center;
                                        justify-content: space-between;
                                        gap: 16px;
                                        padding: 14px 18px;
                                        border-bottom: 1px solid #ecd5d7;
                                        background: #fff;
                                        box-shadow: 0 8px 24px rgba(42, 16, 24, .08);
                                    }
                                    .toolbar h2 { margin: 0; font-size: 16px; }
                                    .toolbar p { margin: 3px 0 0; color: #6d5560; font-size: 12px; }
                                    .actions { display: flex; gap: 8px; }
                                    button {
                                        border: 1px solid #d9a9b9;
                                        border-radius: 8px;
                                        background: #fff;
                                        color: #5e0008;
                                        cursor: pointer;
                                        font-weight: 900;
                                        padding: 10px 16px;
                                    }
                                    button.primary { border-color: #5e0008; background: #5e0008; color: #fff; }
                                    .sheet {
                                        width: 216mm;
                                        min-height: 279mm;
                                        margin: 18px auto;
                                        background: #fff;
                                        padding: 10mm;
                                        box-shadow: 0 12px 36px rgba(42, 16, 24, .12);
                                    }
                                    .grid {
                                        display: grid;
                                        grid-template-columns: repeat(${isPrice ? 3 : 3}, 1fr);
                                        gap: ${isPrice ? '7mm' : '4mm'};
                                    }
                                    .label {
                                        break-inside: avoid;
                                        border: 1px dashed #d9a9b9;
                                        border-radius: 8px;
                                        min-height: ${isPrice ? '56mm' : '29mm'};
                                        padding: ${isPrice ? '5mm' : '3mm'};
                                        text-align: center;
                                        display: flex;
                                        flex-direction: column;
                                        align-items: center;
                                        justify-content: center;
                                    }
                                    .label img { width: 24mm; height: 18mm; object-fit: contain; margin-bottom: 2mm; }
                                    .label h1 { margin: 0; max-width: 100%; font-size: ${isPrice ? '13px' : '10px'}; line-height: 1.15; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
                                    .vendor { margin: 1.5mm 0 0; max-width: 100%; color: #6d5560; font-size: ${isPrice ? '10px' : '8px'}; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
                                    .price { margin: 2mm 0; color: #5e0008; font-size: 24px; font-weight: 900; line-height: 1; }
                                    .mini-price { margin: 1.5mm 0 0; color: #5e0008; font-size: 12px; font-weight: 900; }
                                    .barcode { margin-top: 2mm; line-height: 0; white-space: nowrap; transform-origin: center; transform: scaleX(${isPrice ? '.72' : '.62'}); }
                                    .code { margin: 1.5mm 0 0; font-family: "Courier New", monospace; font-size: ${isPrice ? '10px' : '8px'}; font-weight: 900; letter-spacing: .08em; }
                                    .sku { margin: .5mm 0 0; color: #6d5560; font-size: ${isPrice ? '9px' : '7px'}; }
                                    @media print {
                                        body { background: #fff; }
                                        .toolbar { display: none; }
                                        .sheet { width: auto; min-height: auto; margin: 0; padding: 0; box-shadow: none; }
                                        .grid { gap: ${isPrice ? '6mm' : '3mm'}; }
                                    }
                                </style>
                            </head>
                            <body>
                                <header class="toolbar">
                                    <div>
                                        <h2>${isPrice ? 'Hoja de etiquetas de precio' : 'Hoja de codigos de barras'}</h2>
                                        <p>${escapeHtml(product.name)} · ${copies} etiquetas listas para imprimir</p>
                                    </div>
                                    <div class="actions">
                                        <button data-popup-close>Cerrar</button>
                                        <button class="primary" data-popup-print>Imprimir</button>
                                    </div>
                                </header>
                                <div class="sheet">
                                    <div class="grid">${labels}</div>
                                </div>
                            </body>
                        </html>
                    `);
                    popup.document.close();
                    /* Los scripts dentro de la ventana los bloquea la CSP: se controlan desde aqui. */
                    popup.document.querySelector('[data-popup-close]')?.addEventListener('click', () => popup.close());
                    popup.document.querySelector('[data-popup-print]')?.addEventListener('click', () => popup.print());
                    setTimeout(() => { popup.focus(); popup.print(); }, 350);
                });
            });
        })();
    </script>
@endpush
