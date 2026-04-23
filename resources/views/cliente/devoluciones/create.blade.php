@extends('layouts.marketplace')

@section('content')
    <section class="mx-auto w-full max-w-4xl px-4 py-8 sm:px-6 lg:px-8">
        <x-page-header
            title="Solicitar devolucion"
            :subtitle="'Pedido ' . $pedido->numero_pedido"
        />

        <div class="grid gap-6 lg:grid-cols-[1fr_320px]">
            <form
                method="POST"
                action="{{ route('cliente.devoluciones.store', $pedido) }}"
                enctype="multipart/form-data"
                class="rounded-lg border border-atlantia-rose/20 bg-white p-6 shadow-sm"
                data-protect-submit
            >
                @csrf

                <div class="space-y-5">
                    <div>
                        <label for="motivo" class="text-sm font-bold text-atlantia-ink">Motivo</label>
                        <select
                            id="motivo"
                            name="motivo"
                            class="mt-2 w-full rounded-md border border-atlantia-rose/35 px-4 py-3 text-sm"
                            required
                        >
                            <option value="">Selecciona una opcion</option>
                            <option value="producto_defectuoso" @selected(old('motivo') === 'producto_defectuoso')>
                                Producto defectuoso
                            </option>
                            <option value="no_llego" @selected(old('motivo') === 'no_llego')>No llego mi pedido</option>
                            <option value="incorrecto" @selected(old('motivo') === 'incorrecto')>
                                Producto incorrecto
                            </option>
                            <option value="otro" @selected(old('motivo') === 'otro')>Otro motivo</option>
                        </select>
                        @error('motivo')
                            <p class="mt-2 text-sm font-semibold text-red-700">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="descripcion" class="text-sm font-bold text-atlantia-ink">Descripcion del problema</label>
                        <textarea
                            id="descripcion"
                            name="descripcion"
                            rows="6"
                            class="mt-2 w-full rounded-md border border-atlantia-rose/35 px-4 py-3 text-sm"
                            placeholder="Cuentanos que ocurrio, que producto fue afectado y como podemos ayudarte."
                            required
                        >{{ old('descripcion') }}</textarea>
                        @error('descripcion')
                            <p class="mt-2 text-sm font-semibold text-red-700">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="foto_evidencia" class="text-sm font-bold text-atlantia-ink">
                            Foto de evidencia
                            <span class="font-normal text-atlantia-ink/55">(opcional)</span>
                        </label>
                        <input
                            id="foto_evidencia"
                            name="foto_evidencia"
                            type="file"
                            accept="image/jpeg,image/png,image/webp"
                            class="mt-2 w-full rounded-md border border-atlantia-rose/35 px-4 py-3 text-sm"
                        >
                        @error('foto_evidencia')
                            <p class="mt-2 text-sm font-semibold text-red-700">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="rounded-md bg-atlantia-blush px-4 py-3 text-sm leading-6 text-atlantia-ink/75">
                        Las devoluciones pueden solicitarse dentro de los 7 dias posteriores a la entrega. Si se aprueba,
                        restauraremos el inventario y procesaremos el reembolso segun el metodo de pago usado.
                    </div>

                    <x-ui.button type="submit" class="w-full">Enviar solicitud</x-ui.button>
                </div>
            </form>

            <aside class="rounded-lg border border-atlantia-rose/20 bg-white p-6 shadow-sm lg:h-fit">
                <h2 class="text-xl font-black text-atlantia-ink">Resumen del pedido</h2>
                <p class="mt-2 text-sm text-atlantia-ink/60">{{ $pedido->created_at?->format('d/m/Y H:i') }}</p>

                <ul class="mt-5 divide-y divide-atlantia-rose/15 text-sm">
                    @foreach ($pedido->items as $item)
                        <li class="flex justify-between gap-4 py-3">
                            <span>{{ $item->cantidad }} x {{ $item->producto_nombre_snapshot }}</span>
                            <span class="font-bold text-atlantia-wine">
                                Q {{ number_format((float) $item->subtotal, 2) }}
                            </span>
                        </li>
                    @endforeach
                </ul>

                <div class="mt-5 border-t border-atlantia-rose/20 pt-4">
                    <p class="flex justify-between text-sm">
                        <span>Total pagado</span>
                        <strong>Q {{ number_format((float) $pedido->total, 2) }}</strong>
                    </p>
                </div>
            </aside>
        </div>
    </section>
@endsection
