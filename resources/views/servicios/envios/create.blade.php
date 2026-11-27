@extends('layouts.marketplace', ['title' => 'Atlantia Envíos | Atlantia Store'])

@section('content')
    @php
        $campo = 'mt-1 w-full rounded-lg border border-atlantia-rose/30 px-3 py-2.5 text-sm focus:border-atlantia-wine focus:ring-atlantia-rose';
        $etiqueta = 'text-xs font-black text-atlantia-ink';
    @endphp

    <section class="mx-auto w-full max-w-5xl px-4 py-10 sm:px-6 lg:px-8">
        <p class="text-xs font-black uppercase tracking-[0.2em] text-atlantia-rose">Atlantia Envíos</p>
        <h1 class="mt-2 text-3xl font-black text-atlantia-ink sm:text-4xl">Manda lo que necesites</h1>
        <p class="mt-2 max-w-2xl text-atlantia-ink/65">Paquetes, documentos o encargos dentro de Puerto Barrios y Santo Tomás. Lo recogemos y lo entregamos con nuestros repartidores, y lo sigues en el mapa.</p>

        <form method="POST" action="{{ route('envios.store') }}" class="mt-8 grid gap-6 lg:grid-cols-[1fr_320px]" data-envio-form data-tarifas='@json($tarifas)'>
            @csrf
            <div class="space-y-6">
                <fieldset class="rounded-2xl border border-atlantia-rose/20 bg-white p-5 shadow-sm">
                    <legend class="px-1 text-lg font-black text-atlantia-ink">1. ¿Dónde lo recogemos?</legend>
                    <div class="grid gap-3 sm:grid-cols-2">
                        <div><label class="{{ $etiqueta }}" for="remitente_nombre">Tu nombre</label><input id="remitente_nombre" name="remitente_nombre" value="{{ old('remitente_nombre', auth()->user()?->name) }}" required class="{{ $campo }}"></div>
                        <div><label class="{{ $etiqueta }}" for="remitente_telefono">Tu teléfono</label><input id="remitente_telefono" name="remitente_telefono" type="tel" value="{{ old('remitente_telefono') }}" placeholder="5555 1234" required class="{{ $campo }}"></div>
                        <div><label class="{{ $etiqueta }}" for="origen_municipio">Municipio</label>
                            <select id="origen_municipio" name="origen_municipio" class="{{ $campo }}" data-envio-calc>@foreach ($municipios as $m)<option value="{{ $m }}" @selected(old('origen_municipio') === $m)>{{ $m === 'Santo Tomas' ? 'Santo Tomás' : $m }}</option>@endforeach</select></div>
                        <div><label class="{{ $etiqueta }}" for="origen_direccion">Dirección</label><input id="origen_direccion" name="origen_direccion" value="{{ old('origen_direccion') }}" placeholder="Colonia, calle, casa" required class="{{ $campo }}"></div>
                        <div class="sm:col-span-2"><label class="{{ $etiqueta }}" for="notas_recogida">Indicaciones para recoger (opcional)</label><input id="notas_recogida" name="notas_recogida" value="{{ old('notas_recogida') }}" placeholder="Ej. portón verde, tocar dos veces" class="{{ $campo }}"></div>
                    </div>
                </fieldset>

                <fieldset class="rounded-2xl border border-atlantia-rose/20 bg-white p-5 shadow-sm">
                    <legend class="px-1 text-lg font-black text-atlantia-ink">2. ¿A quién se lo llevamos?</legend>
                    <div class="grid gap-3 sm:grid-cols-2">
                        <div><label class="{{ $etiqueta }}" for="destinatario_nombre">Nombre de quien recibe</label><input id="destinatario_nombre" name="destinatario_nombre" value="{{ old('destinatario_nombre') }}" required class="{{ $campo }}"></div>
                        <div><label class="{{ $etiqueta }}" for="destinatario_telefono">Teléfono de quien recibe</label><input id="destinatario_telefono" name="destinatario_telefono" type="tel" value="{{ old('destinatario_telefono') }}" placeholder="5555 1234" required class="{{ $campo }}"></div>
                        <div><label class="{{ $etiqueta }}" for="destino_municipio">Municipio</label>
                            <select id="destino_municipio" name="destino_municipio" class="{{ $campo }}" data-envio-calc>@foreach ($municipios as $m)<option value="{{ $m }}" @selected(old('destino_municipio') === $m)>{{ $m === 'Santo Tomas' ? 'Santo Tomás' : $m }}</option>@endforeach</select></div>
                        <div><label class="{{ $etiqueta }}" for="destino_direccion">Dirección</label><input id="destino_direccion" name="destino_direccion" value="{{ old('destino_direccion') }}" required class="{{ $campo }}"></div>
                        <div class="sm:col-span-2"><label class="{{ $etiqueta }}" for="notas_entrega">Indicaciones para entregar (opcional)</label><input id="notas_entrega" name="notas_entrega" value="{{ old('notas_entrega') }}" class="{{ $campo }}"></div>
                    </div>
                </fieldset>

                <fieldset class="rounded-2xl border border-atlantia-rose/20 bg-white p-5 shadow-sm">
                    <legend class="px-1 text-lg font-black text-atlantia-ink">3. ¿Qué mandas?</legend>
                    <label class="{{ $etiqueta }}" for="descripcion">Descripción</label>
                    <input id="descripcion" name="descripcion" value="{{ old('descripcion') }}" placeholder="Ej. sobre con documentos, regalo, llaves" required maxlength="160" class="{{ $campo }}">
                    <div class="mt-3 grid gap-2 sm:grid-cols-3">
                        @foreach ($tarifas['tamanos'] as $clave => $tamano)
                            <label class="cursor-pointer rounded-lg border border-atlantia-rose/25 p-3 text-sm has-[:checked]:border-atlantia-wine has-[:checked]:bg-atlantia-blush">
                                <input type="radio" name="tamano" value="{{ $clave }}" class="sr-only" data-envio-calc @checked(old('tamano', 'pequeno') === $clave)>
                                <span class="block font-black text-atlantia-ink">{{ $tamano['nombre'] }}</span>
                                <span class="text-xs text-atlantia-ink/60">{{ $tamano['recargo'] > 0 ? '+ Q '.number_format($tamano['recargo'], 0) : 'Sin recargo' }}</span>
                            </label>
                        @endforeach
                    </div>
                    @error('tamano') <p class="mt-1 text-xs font-bold text-red-600">{{ $message }}</p> @enderror
                </fieldset>
            </div>

            <aside class="h-fit rounded-2xl border border-atlantia-rose/20 bg-white p-5 shadow-sm lg:sticky lg:top-28">
                <p class="text-xs font-black uppercase text-atlantia-wine">Costo del envío</p>
                <p class="mt-1 text-4xl font-black text-atlantia-ink" data-envio-precio>Q --</p>
                <p class="mt-1 text-xs text-atlantia-ink/55">Pago en efectivo al repartidor.</p>

                <p class="mt-4 text-xs font-black text-atlantia-ink">¿Quién paga?</p>
                <div class="mt-1 grid gap-2">
                    <label class="flex items-center gap-2 rounded-lg border border-atlantia-rose/25 px-3 py-2 text-sm has-[:checked]:border-atlantia-wine"><input type="radio" name="quien_paga" value="remitente" @checked(old('quien_paga', 'remitente') === 'remitente')> Yo, al entregar el paquete</label>
                    <label class="flex items-center gap-2 rounded-lg border border-atlantia-rose/25 px-3 py-2 text-sm has-[:checked]:border-atlantia-wine"><input type="radio" name="quien_paga" value="destinatario" @checked(old('quien_paga') === 'destinatario')> Quien recibe</label>
                </div>

                <label class="mt-4 flex items-start gap-2 text-xs text-atlantia-ink/70">
                    <input type="checkbox" name="acepta" value="1" required class="mt-0.5">
                    El envío no contiene dinero en efectivo, armas, sustancias ilegales, animales ni artículos frágiles sin empaque.
                </label>
                @error('acepta') <p class="mt-1 text-xs font-bold text-red-600">{{ $message }}</p> @enderror
                @if ($errors->any() && ! $errors->has('acepta') && ! $errors->has('tamano'))
                    <p class="mt-2 text-xs font-bold text-red-600">{{ $errors->first() }}</p>
                @endif

                <button type="submit" class="mt-4 w-full rounded-xl bg-atlantia-wine px-5 py-3 text-sm font-black text-white hover:bg-atlantia-wine-700">Solicitar envío</button>
            </aside>
        </form>
    </section>

    <script @nonce>
        (() => {
            const form = document.querySelector('[data-envio-form]');
            const tarifas = JSON.parse(form.dataset.tarifas);
            const precio = form.querySelector('[data-envio-precio]');
            const calcular = () => {
                const origen = form.origen_municipio.value;
                const destino = form.destino_municipio.value;
                const tamano = form.querySelector('[name="tamano"]:checked')?.value ?? 'pequeno';
                const base = origen === destino
                    ? Number(tarifas.mismo_municipio[destino] ?? tarifas.mismo_municipio.default)
                    : Number(tarifas.entre_municipios);
                precio.textContent = `Q ${(base + Number(tarifas.tamanos[tamano]?.recargo ?? 0)).toFixed(2)}`;
            };
            form.addEventListener('change', calcular);
            calcular();
        })();
    </script>
@endsection
