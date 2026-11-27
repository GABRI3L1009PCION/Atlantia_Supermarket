@extends('layouts.app')

@section('content')
    <section class="mx-auto max-w-6xl space-y-5 pb-10">
        <div>
            <h1 class="text-3xl font-black leading-tight text-atlantia-ink sm:text-4xl">Promociones</h1>
            <p class="mt-2 text-sm leading-6 text-atlantia-ink/62">
                2x1, “lleva 3 paga 2” y ofertas relámpago por horario. Se aplican solas en el carrito del cliente.
                {{ $esAdmin ? 'Las que creas aquí son de Atlantia Store y solo aplican a sus productos; cada tienda maneja las suyas.' : 'Solo aplican a tus productos y el descuento sale de tu venta.' }}
            </p>
        </div>

        <details class="group rounded-2xl border border-atlantia-rose/20 bg-white shadow-sm" @if ($errors->any()) open @endif>
            <summary class="flex cursor-pointer list-none items-center justify-between gap-3 px-5 py-4 [&::-webkit-details-marker]:hidden">
                <span class="text-lg font-black text-atlantia-ink">Nueva promoción</span>
                <span class="rounded-lg bg-atlantia-wine px-3 py-1.5 text-xs font-bold text-white group-open:hidden">Crear</span>
                <span class="hidden text-sm font-bold text-atlantia-ink/50 group-open:inline">Cerrar ✕</span>
            </summary>

        <form method="POST" action="{{ route($prefijo.'.promociones.store') }}" class="border-t border-atlantia-rose/10 p-5" x-data="{ tipo: '{{ old('tipo', '2x1') }}' }">
            @csrf

            <div class="mt-4 grid gap-4 md:grid-cols-2">
                <div>
                    <label for="nombre" class="text-xs font-black text-atlantia-ink">Nombre</label>
                    <input id="nombre" name="nombre" value="{{ old('nombre') }}" required maxlength="120" placeholder="Ej. 2x1 en jugos" class="mt-1 w-full rounded-lg border border-atlantia-rose/30 px-3 py-2 text-sm">
                    @error('nombre') <p class="mt-1 text-xs font-bold text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <span class="text-xs font-black text-atlantia-ink">Tipo</span>
                    <div class="mt-1 grid grid-cols-3 gap-2">
                        @foreach (['2x1' => '2x1', '3x2' => 'Lleva 3 paga 2', 'porcentaje' => '% descuento'] as $valor => $etiqueta)
                            <label class="cursor-pointer rounded-lg border border-atlantia-rose/25 px-2 py-2 text-center text-sm font-bold has-[:checked]:border-atlantia-wine has-[:checked]:bg-atlantia-blush has-[:checked]:text-atlantia-wine">
                                <input type="radio" name="tipo" value="{{ $valor }}" x-model="tipo" class="sr-only">{{ $etiqueta }}
                            </label>
                        @endforeach
                    </div>
                </div>
                <div x-show="tipo === 'porcentaje'" x-cloak>
                    <label for="porcentaje" class="text-xs font-black text-atlantia-ink">Porcentaje de descuento</label>
                    <input id="porcentaje" name="porcentaje" type="number" min="1" max="90" step="1" value="{{ old('porcentaje') }}" class="mt-1 w-full rounded-lg border border-atlantia-rose/30 px-3 py-2 text-sm">
                    @error('porcentaje') <p class="mt-1 text-xs font-bold text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="producto_id" class="text-xs font-black text-atlantia-ink">Producto <span class="font-semibold text-atlantia-ink/50">(o deja vacío y elige categoría)</span></label>
                    <select id="producto_id" name="producto_id" class="mt-1 w-full rounded-lg border border-atlantia-rose/30 px-3 py-2 text-sm">
                        <option value="">Todos / por categoría</option>
                        @foreach ($productos as $producto)
                            <option value="{{ $producto->id }}" @selected(old('producto_id') == $producto->id)>{{ $producto->nombre }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="categoria_id" class="text-xs font-black text-atlantia-ink">Categoría</label>
                    <select id="categoria_id" name="categoria_id" class="mt-1 w-full rounded-lg border border-atlantia-rose/30 px-3 py-2 text-sm">
                        <option value="">{{ $esAdmin ? 'Todas' : 'Toda mi tienda' }}</option>
                        @foreach ($categorias as $categoria)
                            <option value="{{ $categoria->id }}" @selected(old('categoria_id') == $categoria->id)>{{ $categoria->nombre }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label for="inicia_at" class="text-xs font-black text-atlantia-ink">Desde</label>
                        <input id="inicia_at" name="inicia_at" type="datetime-local" value="{{ old('inicia_at') }}" class="mt-1 w-full rounded-lg border border-atlantia-rose/30 px-3 py-2 text-sm">
                    </div>
                    <div>
                        <label for="termina_at" class="text-xs font-black text-atlantia-ink">Hasta</label>
                        <input id="termina_at" name="termina_at" type="datetime-local" value="{{ old('termina_at') }}" class="mt-1 w-full rounded-lg border border-atlantia-rose/30 px-3 py-2 text-sm">
                        @error('termina_at') <p class="mt-1 text-xs font-bold text-red-600">{{ $message }}</p> @enderror
                    </div>
                </div>
                <div>
                    <span class="text-xs font-black text-atlantia-ink">Oferta relámpago <span class="font-semibold text-atlantia-ink/50">(opcional: solo en este horario cada día)</span></span>
                    <div class="mt-1 grid grid-cols-2 gap-3">
                        <input name="hora_inicio" type="time" value="{{ old('hora_inicio') }}" aria-label="Hora de inicio" class="w-full rounded-lg border border-atlantia-rose/30 px-3 py-2 text-sm">
                        <input name="hora_fin" type="time" value="{{ old('hora_fin') }}" aria-label="Hora de fin" class="w-full rounded-lg border border-atlantia-rose/30 px-3 py-2 text-sm">
                    </div>
                    @error('hora_fin') <p class="mt-1 text-xs font-bold text-red-600">{{ $message }}</p> @enderror
                </div>
            </div>

            <button type="submit" class="mt-5 rounded-lg bg-atlantia-wine px-5 py-3 text-sm font-black text-white hover:bg-atlantia-wine-700">Crear promoción</button>
        </form>
        </details>

        <div class="overflow-hidden rounded-2xl border border-atlantia-rose/20 bg-white shadow-sm">
            <table class="w-full text-left text-sm">
                <thead class="bg-atlantia-blush/50 text-xs uppercase text-atlantia-ink/60">
                    <tr>
                        <th class="px-4 py-3">Promoción</th>
                        <th class="px-4 py-3">Aplica a</th>
                        <th class="px-4 py-3">Vigencia</th>
                        <th class="px-4 py-3 text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($promociones as $promocion)
                        <tr class="border-t border-atlantia-rose/10" data-promocion>
                            <td class="px-4 py-3">
                                <span class="mr-2 rounded bg-emerald-100 px-2 py-0.5 text-xs font-black text-emerald-800">{{ $promocion->etiqueta() }}</span>
                                <span class="font-bold text-atlantia-ink">{{ $promocion->nombre }}</span>
                                @unless ($promocion->activa) <span class="ml-2 text-xs font-bold text-slate-500">(pausada)</span> @endunless
                            </td>
                            <td class="px-4 py-3 text-atlantia-ink/70">
                                {{ $promocion->producto?->nombre ?? $promocion->categoria?->nombre ?? ($promocion->vendor?->business_name ? 'Toda la tienda' : 'Toda la plataforma') }}
                                @if ($esAdmin && $promocion->vendor) <span class="block text-xs">{{ $promocion->vendor->business_name }}</span> @endif
                            </td>
                            <td class="px-4 py-3 text-xs text-atlantia-ink/70">
                                {{ $promocion->inicia_at?->format('d/m/Y') ?? 'Desde ya' }} – {{ $promocion->termina_at?->format('d/m/Y') ?? 'sin fin' }}
                                @if ($promocion->esRelampago())
                                    <span class="block font-bold text-amber-700">⚡ {{ substr($promocion->hora_inicio, 0, 5) }} a {{ substr($promocion->hora_fin, 0, 5) }}</span>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex justify-end gap-2">
                                    <form method="POST" action="{{ route($prefijo.'.promociones.toggle', $promocion) }}">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit" class="rounded-md border border-atlantia-rose/30 px-3 py-1.5 text-xs font-black text-atlantia-wine">{{ $promocion->activa ? 'Pausar' : 'Activar' }}</button>
                                    </form>
                                    <form method="POST" action="{{ route($prefijo.'.promociones.destroy', $promocion) }}" data-confirm="¿Eliminar la promoción «{{ $promocion->nombre }}»?">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="rounded-md border border-red-200 px-3 py-1.5 text-xs font-black text-red-700">Eliminar</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="px-4 py-6 text-center text-atlantia-ink/60">Aún no hay promociones.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        {{ $promociones->links() }}
    </section>
@endsection
