@extends(auth()->user()?->isSuperAdmin() && request()->routeIs('admin.*') ? 'layouts.super-admin' : 'layouts.app')

@section('content')
    @php
        $campo = 'mt-1 h-10 w-full rounded-lg border border-atlantia-rose/30 bg-white px-3 text-sm outline-none transition focus:border-atlantia-wine focus:ring-2 focus:ring-atlantia-rose/20';
        $etiqueta = 'block text-xs font-bold text-atlantia-ink/70';
        $bloque = 'space-y-3 rounded-xl border border-atlantia-rose/15 p-4';
        $titulo = 'flex items-center gap-2 text-sm font-black text-atlantia-ink';
        $abrirFormulario = $errors->any();
    @endphp

    <section class="mx-auto max-w-7xl space-y-4 pb-10" x-data="{ nueva: @js($abrirFormulario) }">
        {{-- Encabezado --}}
        <header class="flex flex-wrap items-end justify-between gap-3">
            <div>
                <h1 class="text-3xl font-black tracking-tight text-atlantia-ink">Entregas externas</h1>
                <p class="mt-1 text-sm text-atlantia-ink/60">Envíos que haces para negocios o personas de fuera: un repartidor recoge en un lugar y entrega en otro.</p>
            </div>
            <button type="button" class="inline-flex items-center gap-2 rounded-xl bg-atlantia-wine px-4 py-2.5 text-sm font-black text-white transition hover:bg-atlantia-wine-700" @click="nueva = ! nueva" :aria-expanded="nueva">
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M12 5v14M5 12h14" stroke="currentColor" stroke-width="2.4" stroke-linecap="round"/></svg>
                Nueva entrega
            </button>
        </header>

        {{-- Formulario (se abre con "Nueva entrega") --}}
        <form method="POST" action="{{ route('admin.entregas-externas.store') }}" class="rounded-2xl border border-atlantia-rose/15 bg-white p-5 shadow-[0_10px_26px_rgba(59,6,12,0.05)] {{ $abrirFormulario ? '' : 'hidden' }}"
              :class="{ 'hidden': ! nueva }" data-protect-submit>
            @csrf
            <input type="hidden" name="source_channel" value="{{ old('source_channel', 'manual') }}">

            @if ($errors->any())
                <p class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-2.5 text-sm font-semibold text-red-800">Revisa los campos marcados.</p>
            @endif

            <div class="grid gap-4 lg:grid-cols-3">
                {{-- Recoger --}}
                <fieldset class="{{ $bloque }}">
                    <legend class="{{ $titulo }}"><span class="grid h-6 w-6 place-items-center rounded-full bg-atlantia-wine text-xs text-white">1</span> Recoger en</legend>
                    <label class="{{ $etiqueta }}">Negocio o persona que entrega
                        <input name="store_name" value="{{ old('store_name') }}" required maxlength="160" placeholder="Ej. Panadería del barrio" class="{{ $campo }}">
                    </label>
                    @error('store_name') <p class="text-xs font-bold text-red-600">{{ $message }}</p> @enderror
                    <label class="{{ $etiqueta }}">Dirección de recogida
                        <input name="pickup_address" value="{{ old('pickup_address') }}" required maxlength="500" placeholder="Calle, número y referencia" class="{{ $campo }}">
                    </label>
                    @error('pickup_address') <p class="text-xs font-bold text-red-600">{{ $message }}</p> @enderror
                    <label class="{{ $etiqueta }}">Teléfono del negocio
                        <input name="store_phone" value="{{ old('store_phone') }}" maxlength="40" placeholder="Opcional" class="{{ $campo }}">
                    </label>
                </fieldset>

                {{-- Entregar --}}
                <fieldset class="{{ $bloque }}">
                    <legend class="{{ $titulo }}"><span class="grid h-6 w-6 place-items-center rounded-full bg-atlantia-wine text-xs text-white">2</span> Entregar a</legend>
                    <label class="{{ $etiqueta }}">Nombre de quien recibe
                        <input name="customer_name" value="{{ old('customer_name') }}" required maxlength="160" class="{{ $campo }}">
                    </label>
                    @error('customer_name') <p class="text-xs font-bold text-red-600">{{ $message }}</p> @enderror
                    <label class="{{ $etiqueta }}">Dirección de entrega
                        <input name="delivery_address" value="{{ old('delivery_address') }}" required maxlength="500" placeholder="Calle, número y referencia" class="{{ $campo }}">
                    </label>
                    @error('delivery_address') <p class="text-xs font-bold text-red-600">{{ $message }}</p> @enderror
                    <label class="{{ $etiqueta }}">Teléfono de quien recibe
                        <input name="customer_phone" value="{{ old('customer_phone') }}" maxlength="40" placeholder="Para que el repartidor llame al llegar" class="{{ $campo }}">
                    </label>
                </fieldset>

                {{-- Cobro --}}
                <fieldset class="{{ $bloque }}">
                    <legend class="{{ $titulo }}"><span class="grid h-6 w-6 place-items-center rounded-full bg-atlantia-wine text-xs text-white">3</span> Cobro</legend>
                    <label class="{{ $etiqueta }}">¿Cómo paga el cliente?
                        <select name="payment_method" class="{{ $campo }}">
                            @foreach (['cash' => 'Efectivo al recibir', 'digital' => 'Ya pagó en línea', 'card' => 'Tarjeta', 'transfer' => 'Transferencia'] as $valor => $texto)
                                <option value="{{ $valor }}" @selected(old('payment_method', 'cash') === $valor)>{{ $texto }}</option>
                            @endforeach
                        </select>
                    </label>
                    <div class="grid grid-cols-2 gap-2">
                        <label class="{{ $etiqueta }}">Cobrar al cliente (Q)
                            <input name="amount_to_collect" type="number" min="0" step="0.01" value="{{ old('amount_to_collect', 0) }}" class="{{ $campo }}">
                        </label>
                        <label class="{{ $etiqueta }}">Pagar al negocio (Q)
                            <input name="amount_to_pay_store" type="number" min="0" step="0.01" value="{{ old('amount_to_pay_store', 0) }}" class="{{ $campo }}">
                        </label>
                    </div>
                    <label class="{{ $etiqueta }}">Propina (Q)
                        <input name="tip_amount" type="number" min="0" step="0.01" value="{{ old('tip_amount', 0) }}" class="{{ $campo }}">
                    </label>
                </fieldset>
            </div>

            {{-- Datos opcionales --}}
            <details class="mt-4 rounded-xl border border-atlantia-rose/15 px-4 py-3 text-sm" @if ($errors->hasAny(['pickup_latitude', 'pickup_longitude', 'delivery_latitude', 'delivery_longitude'])) open @endif>
                <summary class="cursor-pointer font-bold text-atlantia-wine">Más datos (opcional): referencia, notas y ubicación exacta</summary>
                <div class="mt-3 grid gap-3 md:grid-cols-2">
                    <label class="{{ $etiqueta }}">Referencia o número de pedido del negocio
                        <input name="external_reference" value="{{ old('external_reference') }}" maxlength="120" class="{{ $campo }}">
                    </label>
                    <label class="{{ $etiqueta }}">Nota para el repartidor
                        <input name="delivery_notes" value="{{ old('delivery_notes') }}" maxlength="1000" placeholder="Ej. casa verde, portón negro" class="{{ $campo }}">
                    </label>
                    <div>
                        <p class="{{ $etiqueta }}">Ubicación exacta de recogida</p>
                        <div class="grid grid-cols-2 gap-2">
                            <input name="pickup_latitude" value="{{ old('pickup_latitude') }}" inputmode="decimal" placeholder="Latitud, ej. 15.7278" aria-label="Latitud de recogida" class="{{ $campo }}">
                            <input name="pickup_longitude" value="{{ old('pickup_longitude') }}" inputmode="decimal" placeholder="Longitud, ej. -88.5944" aria-label="Longitud de recogida" class="{{ $campo }}">
                        </div>
                    </div>
                    <div>
                        <p class="{{ $etiqueta }}">Ubicación exacta de entrega</p>
                        <div class="grid grid-cols-2 gap-2">
                            <input name="delivery_latitude" value="{{ old('delivery_latitude') }}" inputmode="decimal" placeholder="Latitud" aria-label="Latitud de entrega" class="{{ $campo }}">
                            <input name="delivery_longitude" value="{{ old('delivery_longitude') }}" inputmode="decimal" placeholder="Longitud" aria-label="Longitud de entrega" class="{{ $campo }}">
                        </div>
                    </div>
                    <p class="text-xs text-atlantia-ink/55 md:col-span-2">Con la ubicación exacta el sistema calcula la distancia y el pago del repartidor. Puedes copiarla de Google Maps: mantén presionado el punto y copia los dos números.</p>
                </div>
            </details>

            <div class="mt-4 flex justify-end gap-2">
                <button type="button" class="h-10 rounded-lg px-4 text-sm font-bold text-atlantia-ink/60 hover:bg-atlantia-cream" @click="nueva = false">Cancelar</button>
                <button type="submit" class="h-10 rounded-lg bg-atlantia-wine px-5 text-sm font-black text-white hover:bg-atlantia-wine-700">Crear entrega</button>
            </div>
        </form>

        {{-- Lista --}}
        <div class="overflow-hidden rounded-2xl border border-atlantia-rose/15 bg-white shadow-[0_10px_26px_rgba(59,6,12,0.05)]">
            <form method="GET" class="flex gap-2 border-b border-atlantia-rose/10 p-3">
                <div class="relative flex-1">
                    <span class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-atlantia-ink/40">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M11 19a8 8 0 1 0 0-16 8 8 0 0 0 0 16ZM20.5 20.5l-3.8-3.8" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
                    </span>
                    <input type="search" name="q" value="{{ request('q') }}" placeholder="Buscar por negocio, cliente o referencia" aria-label="Buscar entrega" class="h-10 w-full rounded-lg border border-atlantia-rose/30 pl-9 pr-3 text-sm outline-none transition focus:border-atlantia-wine focus:ring-2 focus:ring-atlantia-rose/20">
                </div>
                <button type="submit" class="h-10 shrink-0 rounded-lg border border-atlantia-rose/30 px-4 text-sm font-black text-atlantia-wine hover:bg-atlantia-blush">Buscar</button>
            </form>

            <div class="divide-y divide-atlantia-rose/10">
                @forelse ($orders as $order)
                    @php [$estadoTexto, $estadoClase] = $order->estadoVisible(); @endphp
                    <div class="grid items-center gap-3 px-4 py-3 md:grid-cols-[minmax(0,1fr)_auto]">
                        <a href="{{ route('admin.entregas-externas.show', $order) }}" class="min-w-0">
                            <p class="flex flex-wrap items-center gap-2">
                                <span class="rounded-full px-2.5 py-0.5 text-xs font-black {{ $estadoClase }}">{{ $estadoTexto }}</span>
                                @if ($order->external_reference)
                                    <span class="text-xs text-atlantia-ink/45">{{ $order->external_reference }}</span>
                                @endif
                                <span class="text-xs text-atlantia-ink/45">· {{ $order->created_at?->diffForHumans() }}</span>
                            </p>
                            <p class="mt-1 truncate text-sm font-black text-atlantia-ink">{{ $order->store_name }} <span class="font-normal text-atlantia-ink/40">→</span> {{ $order->customer_name }}</p>
                            <p class="truncate text-xs text-atlantia-ink/55">{{ $order->delivery_address }}</p>
                        </a>
                        <form method="POST" action="{{ route('admin.entregas-externas.assign', $order) }}" class="flex shrink-0 gap-2">
                            @csrf
                            @method('PATCH')
                            <select name="repartidor_id" required aria-label="Repartidor" class="h-9 rounded-lg border border-atlantia-rose/30 px-2 text-sm">
                                <option value="">Elegir repartidor</option>
                                @foreach ($repartidores as $repartidor)
                                    <option value="{{ $repartidor->id }}" @selected((int) $order->repartidor_id === (int) $repartidor->id)>{{ $repartidor->name }}</option>
                                @endforeach
                            </select>
                            <button type="submit" class="h-9 rounded-lg bg-atlantia-wine px-3 text-xs font-black text-white hover:bg-atlantia-wine-700">Ofrecer</button>
                        </form>
                    </div>
                @empty
                    <div class="px-4 py-14 text-center">
                        <span class="mx-auto grid h-12 w-12 place-items-center rounded-full bg-atlantia-blush text-atlantia-wine">
                            <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M3 15h12V6H3v9Zm12-6h3l3 3v3h-6V9ZM7 19a2 2 0 1 0 0-4 2 2 0 0 0 0 4Zm11 0a2 2 0 1 0 0-4 2 2 0 0 0 0 4Z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/></svg>
                        </span>
                        <p class="mt-3 font-black text-atlantia-ink">{{ request('q') ? 'No hay entregas con esa búsqueda.' : 'Todavía no hay entregas externas.' }}</p>
                        <p class="mt-1 text-sm text-atlantia-ink/55">Cuando un negocio te pida un envío, regístralo con "Nueva entrega".</p>
                    </div>
                @endforelse
            </div>

            @if ($orders->hasPages())
                <div class="border-t border-atlantia-rose/10 px-4 py-3">{{ $orders->withQueryString()->links() }}</div>
            @endif
        </div>
    </section>
@endsection
