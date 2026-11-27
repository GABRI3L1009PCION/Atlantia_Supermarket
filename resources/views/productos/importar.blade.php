@extends(auth()->user()?->isSuperAdmin() && request()->routeIs('admin.*') ? 'layouts.super-admin' : 'layouts.app')

@section('content')
    @php
        $ayuda = [
            'sku' => ['Código único del producto en la tienda. Si ya existe, se actualiza.', 'siempre'],
            'nombre' => ['Nombre que verá el cliente.', 'nuevo'],
            'categoria' => ['Nombre de una categoría que ya exista, por ejemplo Abarrotes.', 'nuevo'],
            'precio' => ['Precio final que paga el cliente (con IVA), por ejemplo 24.65.', 'nuevo'],
            'oferta' => ['Precio de oferta final (con IVA). Vacío para quitar la oferta.', false],
            'existencias' => ['Cantidad disponible.', false],
            'unidad_medida' => ['unidad, libra, kilogramo, gramo, litro, mililitro o paquete.', false],
            'codigo_barras' => ['Código de barras del empaque.', false],
            'descripcion' => ['Texto que aparece en la ficha del producto.', false],
            'peso_gramos' => ['Peso en gramos.', false],
            'visible' => ['"si" para mostrarlo en la tienda, "no" para ocultarlo.', false],
        ];
        $volver = $esAdmin ? route('admin.productos.index') : route('vendedor.productos.index');
        $paso = 'grid h-7 w-7 shrink-0 place-items-center rounded-full bg-atlantia-wine text-xs font-black text-white';
    @endphp

    <section class="mx-auto max-w-5xl space-y-4 pb-10">
        {{-- Encabezado --}}
        <header class="flex flex-wrap items-end justify-between gap-3">
            <div>
                <a href="{{ $volver }}" class="inline-flex items-center gap-1 text-sm font-bold text-atlantia-wine hover:underline">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M15 6l-6 6 6 6" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    Productos
                </a>
                <h1 class="mt-1 text-3xl font-black tracking-tight text-atlantia-ink">Carga masiva</h1>
                <p class="mt-1 text-sm text-atlantia-ink/60">Sube cientos de productos de una vez, o actualiza precios y existencias, con un archivo de Excel.</p>
            </div>
        </header>

        @unless ($permitido)
            <div class="flex flex-wrap items-center justify-between gap-3 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900" data-aviso-plan>
                <p><strong>Tu plan actual no incluye la carga masiva.</strong> Está disponible desde el plan Profesional. Mientras tanto puedes crear tus productos uno por uno.</p>
                <a href="{{ route('vendedor.plan.index') }}" class="inline-flex h-9 items-center rounded-lg bg-atlantia-wine px-4 text-xs font-bold text-white hover:bg-atlantia-wine-700">Ver planes</a>
            </div>
        @endunless

        {{-- Resultado de la última importación --}}
        @if ($resultado)
            <article class="rounded-2xl border border-atlantia-rose/15 bg-white p-5 shadow-[0_10px_26px_rgba(59,6,12,0.05)]" data-resultado-importacion>
                <h2 class="text-lg font-black text-atlantia-ink">Resultado · {{ $resultado['tienda'] }}</h2>
                <div class="mt-3 grid gap-3 sm:grid-cols-3">
                    <p class="rounded-xl bg-emerald-50 p-3 text-sm font-bold text-emerald-800"><span class="block text-2xl font-black">{{ $resultado['creados'] }}</span>productos nuevos</p>
                    <p class="rounded-xl bg-sky-50 p-3 text-sm font-bold text-sky-800"><span class="block text-2xl font-black">{{ $resultado['actualizados'] }}</span>actualizados</p>
                    <p @class(['rounded-xl p-3 text-sm font-bold', 'bg-red-50 text-red-700' => count($resultado['errores']) > 0, 'bg-slate-50 text-slate-600' => count($resultado['errores']) === 0])><span class="block text-2xl font-black">{{ count($resultado['errores']) }}</span>filas con error</p>
                </div>
                @if (count($resultado['errores']) > 0)
                    <ul class="mt-4 max-h-64 list-disc space-y-1 overflow-auto rounded-lg bg-red-50/60 p-4 pl-8 text-sm text-red-800">
                        @foreach ($resultado['errores'] as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                @endif
            </article>
        @endif

        {{-- Pasos --}}
        <form method="POST" action="{{ $accion }}" enctype="multipart/form-data" class="overflow-hidden rounded-2xl border border-atlantia-rose/15 bg-white shadow-[0_10px_26px_rgba(59,6,12,0.05)]" data-protect-submit>
            @csrf
            <div class="grid divide-y divide-atlantia-rose/10 md:grid-cols-2 md:divide-x md:divide-y-0">
                <div class="space-y-3 p-5">
                    <p class="flex items-center gap-2.5 text-sm font-black text-atlantia-ink"><span class="{{ $paso }}">1</span> Descarga la plantilla</p>
                    <p class="text-sm text-atlantia-ink/60">Trae las columnas listas y dos productos de ejemplo.</p>
                    <a href="{{ $plantilla }}" class="inline-flex items-center gap-2 rounded-xl border border-atlantia-rose/25 px-4 py-2.5 text-sm font-black text-atlantia-wine transition hover:bg-atlantia-blush">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M12 3v12m0 0-4-4m4 4 4-4M4 21h16"/></svg>
                        Descargar plantilla
                    </a>
                </div>
                <div class="space-y-3 p-5">
                    <p class="flex items-center gap-2.5 text-sm font-black text-atlantia-ink"><span class="{{ $paso }}">2</span> Llénala en Excel</p>
                    <p class="text-sm text-atlantia-ink/60">Una fila por producto. Al terminar usa <strong class="text-atlantia-ink">Archivo → Guardar como</strong> y elige <strong class="text-atlantia-ink">CSV UTF-8</strong>.</p>
                    <p class="rounded-lg bg-atlantia-blush/50 px-3 py-2 text-xs text-atlantia-ink/70">Para cambiar solo precios o existencias, basta con la columna <code class="font-bold">sku</code> y la que quieras cambiar.</p>
                </div>
            </div>

            <div class="space-y-4 border-t border-atlantia-rose/10 bg-atlantia-cream/40 p-5">
                <p class="flex items-center gap-2.5 text-sm font-black text-atlantia-ink"><span class="{{ $paso }}">3</span> Sube el archivo</p>

                <div @class(['grid gap-3', 'md:grid-cols-[minmax(0,16rem)_1fr]' => $esAdmin])>
                    @if ($esAdmin)
                        <label class="block">
                            <span class="mb-1 block text-xs font-bold text-atlantia-ink/60">¿A qué tienda van?</span>
                            <select id="vendor_id" name="vendor_id" required class="h-11 w-full rounded-lg border border-atlantia-rose/30 bg-white px-3 text-sm focus:border-atlantia-wine focus:ring-2 focus:ring-atlantia-rose/20">
                                <option value="">Elige la tienda</option>
                                @foreach ($vendors as $vendor)
                                    <option value="{{ $vendor->id }}" @selected(old('vendor_id') == $vendor->id)>{{ $vendor->business_name }}</option>
                                @endforeach
                            </select>
                            @error('vendor_id') <span class="mt-1 block text-xs font-bold text-red-600">{{ $message }}</span> @enderror
                        </label>
                    @endif

                    <label class="block cursor-pointer" x-data="{ nombre: '' }">
                        <span class="mb-1 block text-xs font-bold text-atlantia-ink/60">Archivo .csv (máximo 5 MB)</span>
                        <span class="flex min-h-11 items-center gap-3 rounded-lg border-2 border-dashed border-atlantia-rose/40 bg-white px-4 py-2.5 transition hover:border-atlantia-wine">
                            <svg class="h-5 w-5 shrink-0 text-atlantia-wine" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M12 15V3M8 7l4-4 4 4M4 15v4a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-4" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                            <span class="min-w-0 flex-1 truncate text-sm" :class="nombre ? 'font-bold text-atlantia-ink' : 'text-atlantia-ink/55'" x-text="nombre || 'Haz clic para elegir el archivo'">Haz clic para elegir el archivo</span>
                            <span class="shrink-0 rounded-md bg-atlantia-blush px-2.5 py-1 text-xs font-black text-atlantia-wine">Elegir</span>
                        </span>
                        <input id="archivo" name="archivo" type="file" accept=".csv,text/csv" required class="sr-only" @change="nombre = $event.target.files[0]?.name || ''">
                        @error('archivo') <span class="mt-1 block text-xs font-bold text-red-600">{{ $message }}</span> @enderror
                    </label>
                </div>

                <div class="flex justify-end">
                    <button type="submit" class="inline-flex items-center gap-2 rounded-xl bg-atlantia-wine px-6 py-3 text-sm font-black text-white transition hover:bg-atlantia-wine-700 disabled:cursor-not-allowed disabled:opacity-50" @disabled(! $permitido)>Importar productos</button>
                </div>
            </div>
        </form>

        {{-- Guía de columnas --}}
        <details class="group overflow-hidden rounded-2xl border border-atlantia-rose/15 bg-white">
            <summary class="flex cursor-pointer list-none items-center justify-between gap-3 px-5 py-4 text-sm font-black text-atlantia-ink">
                Qué va en cada columna
                <svg class="h-4 w-4 text-atlantia-wine transition group-open:rotate-180" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="m6 9 6 6 6-6" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/></svg>
            </summary>
            <div class="overflow-x-auto border-t border-atlantia-rose/10">
                <table class="w-full text-sm">
                    <tbody class="divide-y divide-atlantia-rose/10">
                        @foreach ($columnas as $columna)
                            @php [$texto, $obligatoria] = $ayuda[$columna] ?? ['', false]; @endphp
                            <tr>
                                <td class="w-48 px-5 py-2.5 font-mono text-xs font-bold text-atlantia-wine">{{ $columna }}</td>
                                <td class="px-3 py-2.5 text-atlantia-ink/70">{{ $texto }}</td>
                                <td class="px-5 py-2.5 text-right">
                                    <span @class(['whitespace-nowrap rounded-full px-2 py-0.5 text-[11px] font-black', 'bg-atlantia-wine text-white' => $obligatoria === 'siempre', 'bg-atlantia-blush text-atlantia-wine' => $obligatoria === 'nuevo', 'bg-slate-100 text-slate-500' => ! $obligatoria])>{{ match ($obligatoria) { 'siempre' => 'Obligatoria', 'nuevo' => 'Obligatoria en nuevos', default => 'Opcional' } }}</span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </details>
    </section>
@endsection
