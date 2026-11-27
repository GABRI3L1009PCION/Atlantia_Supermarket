@extends('layouts.marketplace', ['title' => 'Seguimiento de envío | Atlantia Store'])

@section('content')
    @php
        $pasos = ['Recibido', 'Repartidor asignado', 'En camino', 'Entregado'];
    @endphp
    <link rel="stylesheet" href="{{ asset('vendor/leaflet/leaflet.css') }}">

    <section class="mx-auto w-full max-w-4xl px-4 py-10 sm:px-6 lg:px-8">
        <p class="text-xs font-black uppercase tracking-[0.2em] text-atlantia-rose">Atlantia Envíos · {{ $envio->external_reference }}</p>
        <h1 class="mt-2 text-3xl font-black text-atlantia-ink">Seguimiento de tu envío</h1>
        <p class="mt-1 text-sm text-atlantia-ink/60">Guarda este enlace o compártelo con quien recibe.</p>

        @if ($cancelado)
            <p class="mt-6 rounded-xl bg-red-50 p-4 font-bold text-red-700">Este envío fue cancelado.</p>
        @else
            <ol class="mt-6 grid grid-cols-4 gap-2" data-envio-pasos>
                @foreach ($pasos as $indice => $etiqueta)
                    <li class="text-center">
                        <span @class([
                            'mx-auto grid h-9 w-9 place-items-center rounded-full text-sm font-black',
                            'bg-emerald-600 text-white' => $indice < $paso || $paso === 3,
                            'bg-atlantia-wine text-white' => $indice === $paso && $paso !== 3,
                            'bg-slate-200 text-slate-500' => $indice > $paso,
                        ]) data-paso="{{ $indice }}">{{ $indice < $paso || $paso === 3 ? '✓' : $loop->iteration }}</span>
                        <span class="mt-1 block text-xs font-bold text-atlantia-ink/70">{{ $etiqueta }}</span>
                    </li>
                @endforeach
            </ol>

            <div id="envio-mapa" class="mt-6 hidden h-80 w-full overflow-hidden rounded-2xl border border-atlantia-rose/20"></div>

            <div class="mt-6 grid gap-4 sm:grid-cols-2">
                <article class="rounded-2xl border border-atlantia-rose/20 bg-white p-5 shadow-sm">
                    <p class="text-xs font-black uppercase text-atlantia-wine">Recoger en</p>
                    <p class="mt-1 font-bold text-atlantia-ink">{{ $envio->pickup_address }}</p>
                    <p class="mt-3 text-xs font-black uppercase text-atlantia-wine">Entregar a</p>
                    <p class="mt-1 font-bold text-atlantia-ink">{{ $envio->customer_name }}</p>
                    <p class="text-sm text-atlantia-ink/65">{{ $envio->delivery_address }}</p>
                </article>
                <article class="rounded-2xl border border-atlantia-rose/20 bg-white p-5 shadow-sm">
                    <p class="text-xs font-black uppercase text-atlantia-wine">Código de entrega</p>
                    <p class="mt-2 font-mono text-3xl font-black tracking-[0.3em] text-atlantia-wine">{{ $envio->confirmation_code }}</p>
                    <p class="mt-1 text-xs text-atlantia-ink/60">Quien recibe se lo dice al repartidor al recibir el paquete.</p>
                    <p class="mt-3 text-sm font-bold text-atlantia-ink">Costo: Q {{ number_format((float) $envio->delivery_fee, 2) }}</p>
                </article>
            </div>
        @endif
    </section>

    <script @nonce src="{{ asset('vendor/leaflet/leaflet.js') }}"></script>
    <script @nonce>
        (() => {
            const url = @json(route('envios.live', $envio->uuid));
            const contenedor = document.getElementById('envio-mapa');
            if (!contenedor || !window.L) return;
            let mapa = null;
            let moto = null;
            const actualizar = async () => {
                try {
                    const datos = await (await fetch(url, { headers: { Accept: 'application/json' } })).json();
                    if (datos.estado === 'delivered' || datos.estado === 'cancelled') { location.reload(); return; }
                    if (!datos.repartidor) return;
                    const punto = [datos.repartidor.latitude, datos.repartidor.longitude];
                    if (!mapa) {
                        contenedor.classList.remove('hidden');
                        mapa = L.map(contenedor).setView(punto, 15);
                        L.tileLayer(@json(config('services.mapas.tiles_url')), { maxZoom: 19, attribution: @json(config('services.mapas.tiles_attribution')) }).addTo(mapa);
                        moto = L.marker(punto, { icon: L.divIcon({ className: '', iconSize: [40, 40], iconAnchor: [20, 20], html: '<div style="width:40px;height:40px;border-radius:50%;background:#5e0008;border:3px solid #fff;display:grid;place-items:center;font-size:20px">🛵</div>' }) }).addTo(mapa);
                    } else {
                        moto.setLatLng(punto);
                        mapa.panTo(punto);
                    }
                } catch (_) { /* sin conexion: se reintenta */ }
            };
            actualizar();
            setInterval(actualizar, 10000);
        })();
    </script>
@endsection
