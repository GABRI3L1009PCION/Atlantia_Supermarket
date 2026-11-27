@extends('layouts.marketplace')

@section('content')
    @php
        $pedido = $seguimiento['pedido'];
        $ruta = $seguimiento['ruta'];
        $estado = $seguimiento['estado'];
        $repartidorNombre = $seguimiento['repartidor_nombre'];
        $pasos = [
            ['clave' => 'confirmado', 'label' => 'Confirmado'],
            ['clave' => 'preparado', 'label' => 'Preparado'],
            ['clave' => 'en_camino', 'label' => 'En camino'],
            ['clave' => 'entregado', 'label' => 'Entregado'],
        ];
        $pasoActual = match ($estado) {
            'preparando', 'listo_para_entrega' => 1,
            'en_ruta' => 2,
            'entregado' => 3,
            default => 0,
        };
        $cancelado = in_array($estado, ['cancelado', 'rechazado'], true);
        $titulos = [
            0 => ['Pedido recibido', 'Estamos confirmando tu pedido.'],
            1 => ['Preparando tu pedido', 'Te avisaremos cuando el repartidor vaya en camino.'],
            2 => ['Tu pedido va en camino', 'Esperando la ubicación del repartidor…'],
            3 => ['Pedido entregado', '¡Gracias por comprar en Atlantia Store!'],
        ];
        [$tituloEstado, $detalleEstado] = $cancelado
            ? ['Pedido cancelado', 'Si tienes dudas, contáctanos.']
            : $titulos[$pasoActual];
        $mostrarCodigo = $ruta?->confirmation_code && $estado === 'en_ruta' && ! $ruta->delivered_code_confirmed_at;
        $mapData = [
            'liveUrl' => $liveUrl ?? route('cliente.pedidos.seguimiento.live', $pedido),
            'numero' => $pedido->numero_pedido,
            'estado' => $estado,
            'destino' => $seguimiento['destino'],
            'repartidor' => $seguimiento['repartidor'],
            'repartidorNombre' => $repartidorNombre,
            'mapa' => $seguimiento['mapa'],
        ];
    @endphp

    <link rel="stylesheet" href="{{ asset('vendor/leaflet/leaflet.css') }}">

    <section class="mx-auto w-full max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
        <div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
            <x-page-header
                title="Seguimiento en vivo"
                subtitle="Mira el avance de tu pedido y por dónde va el repartidor."
                class="mb-0"
            />

            <a
                href="{{ $detalleUrl ?? route('cliente.pedidos.show', $pedido) }}"
                class="inline-flex rounded-md border border-atlantia-rose/40 px-4 py-2 text-sm font-bold
                    text-atlantia-wine hover:bg-atlantia-blush"
            >
                Ver detalle del pedido
            </a>
        </div>

        @if (! empty($entregas))
            {{-- Pedido de varias tiendas: cada una llega por separado con su repartidor. --}}
            <nav class="mb-4 rounded-2xl border border-atlantia-rose/15 bg-white p-2 shadow-sm" aria-label="Entregas del pedido">
                <p class="px-2 pb-2 pt-1 text-xs font-bold text-atlantia-ink/60">Tu pedido llega en {{ count($entregas) }} entregas, una por tienda.</p>
                <div class="flex gap-2 overflow-x-auto">
                    @foreach ($entregas as $entregaTab)
                        <a href="{{ $entregaTab['url'] }}" @class([
                            'shrink-0 rounded-xl px-4 py-2 text-sm font-black transition',
                            'bg-atlantia-wine text-white' => $entregaTab['actual'],
                            'bg-atlantia-blush/60 text-atlantia-wine hover:bg-atlantia-blush' => ! $entregaTab['actual'],
                        ]) @if ($entregaTab['actual']) aria-current="page" @endif>
                            Entrega {{ $loop->iteration }} · {{ $entregaTab['nombre'] }}
                            <span class="ml-1 text-[11px] font-bold opacity-75">{{ ucfirst(str_replace('_', ' ', $entregaTab['estado'])) }}</span>
                        </a>
                    @endforeach
                </div>
            </nav>
        @endif

        <div class="grid gap-6 lg:grid-cols-[1fr_360px]">
            <section class="overflow-hidden rounded-2xl border border-atlantia-rose/20 bg-white shadow-sm">
                <div class="border-b border-atlantia-rose/15 px-5 pb-4 pt-5">
                    <p class="text-xs font-black uppercase tracking-wide text-atlantia-wine">Pedido {{ $pedido->numero_pedido }}</p>

                    <ol class="mt-4 grid grid-cols-4 gap-2" aria-label="Avance del pedido">
                        @foreach ($pasos as $indice => $paso)
                            @php
                                $hecho = ! $cancelado && ($indice < $pasoActual || ($indice === 3 && $pasoActual === 3));
                                $actual = ! $cancelado && $indice === $pasoActual && $indice !== 3;
                            @endphp
                            <li class="relative flex flex-col items-center gap-1.5 text-center">
                                @if (! $loop->first)
                                    <span
                                        @class([
                                            'absolute right-1/2 top-4 -z-0 mr-5 h-0.5 w-[calc(100%-2.5rem)]',
                                            'bg-emerald-500' => $indice <= $pasoActual && ! $cancelado,
                                            'bg-slate-200' => $indice > $pasoActual || $cancelado,
                                        ])
                                        aria-hidden="true"
                                    ></span>
                                @endif
                                <span
                                    @class([
                                        'relative z-10 grid h-8 w-8 place-items-center rounded-full text-sm font-black',
                                        'bg-emerald-600 text-white' => $hecho,
                                        'bg-atlantia-wine text-white ring-4 ring-atlantia-blush' => $actual,
                                        'bg-slate-200 text-slate-500' => ! $hecho && ! $actual,
                                    ])
                                >
                                    @if ($hecho)
                                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" aria-hidden="true"><path d="m5 12 5 5L20 7" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                    @else
                                        {{ $loop->iteration }}
                                    @endif
                                </span>
                                <span @class([
                                    'text-xs font-bold',
                                    'text-atlantia-wine' => $actual,
                                    'text-atlantia-ink/70' => ! $actual,
                                ])>{{ $paso['label'] }}</span>
                            </li>
                        @endforeach
                    </ol>
                </div>

                <div class="relative">
                    <div id="tracking-map" class="h-[460px] w-full bg-atlantia-blush sm:h-[520px]" aria-label="Mapa del recorrido"></div>

                    <button
                        type="button"
                        id="tracking-center"
                        class="absolute right-4 top-4 z-[500] grid h-10 w-10 place-items-center rounded-full bg-white text-atlantia-wine shadow-lg"
                        aria-label="Centrar el mapa"
                    >
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="12" cy="12" r="3"/><path d="M12 2v3M12 19v3M2 12h3M19 12h3" stroke-linecap="round"/></svg>
                    </button>

                    <div
                        id="tracking-card"
                        class="absolute bottom-4 left-4 right-4 z-[500] rounded-2xl bg-white/95 p-4 shadow-xl backdrop-blur sm:left-auto sm:w-96"
                    >
                        <p id="tracking-label" class="text-xs font-black uppercase tracking-wide text-atlantia-wine">{{ $tituloEstado }}</p>
                        <p id="tracking-eta" class="mt-1 text-2xl font-black text-atlantia-ink">
                            @if ($estado === 'en_ruta')
                                {{ $seguimiento['eta_minutos'] ? 'Llega en '.$seguimiento['eta_minutos'].' min' : 'Llega pronto' }}
                            @elseif ($pasoActual < 2 && ! $cancelado)
                                Entrega en 45 a 60 min
                            @else
                                {{ $tituloEstado }}
                            @endif
                        </p>
                        <p id="tracking-meta" class="mt-1 text-sm text-atlantia-ink/60">
                            @if ($estado === 'en_ruta' && $repartidorNombre)
                                {{ $repartidorNombre }} · Repartidor Atlantia
                            @else
                                {{ $detalleEstado }}
                            @endif
                        </p>
                        <div class="mt-3 h-1.5 overflow-hidden rounded-full bg-atlantia-blush" @if ($estado !== 'en_ruta') hidden @endif id="tracking-progress">
                            <div id="tracking-bar" class="h-full w-0 rounded-full bg-atlantia-wine transition-all duration-700"></div>
                        </div>
                        @if ($seguimiento['destino']['aproximado'] ?? false)
                            <p class="mt-3 rounded-lg bg-amber-50 px-3 py-2 text-xs font-bold text-amber-800">
                                Tu dirección no tiene ubicación exacta: el repartidor te llamará al llegar a tu zona.
                            </p>
                        @endif
                    </div>
                </div>
            </section>

            <aside class="space-y-6">
                @if ($mostrarCodigo)
                    <section class="rounded-2xl border border-atlantia-rose/20 bg-white p-5 shadow-sm">
                        <p class="text-xs font-black uppercase tracking-wide text-atlantia-wine">Código de entrega</p>
                        <h2 class="mt-1 text-xl font-black text-atlantia-ink">Dáselo al repartidor al recibir</h2>
                        <div class="mt-4 grid grid-cols-4 gap-2">
                            @foreach (str_split((string) $ruta->confirmation_code) as $digit)
                                <span class="grid h-16 place-items-center rounded-xl border border-atlantia-rose/25 bg-atlantia-blush text-3xl font-black text-atlantia-wine">
                                    {{ $digit }}
                                </span>
                            @endforeach
                        </div>
                        <p class="mt-4 rounded-lg bg-amber-50 p-3 text-sm font-bold leading-6 text-amber-800">
                            Entrega este código solo cuando tengas tu pedido en mano.
                        </p>
                    </section>
                @endif

                <section class="rounded-2xl border border-atlantia-rose/20 bg-white p-5 shadow-sm">
                    <h2 class="text-xl font-black text-atlantia-ink">Entrega</h2>
                    <dl class="mt-4 space-y-4 text-sm">
                        <div>
                            <dt class="font-black uppercase tracking-wide text-atlantia-ink/50">Destino</dt>
                            <dd class="mt-1 leading-6 text-atlantia-ink/75">
                                {{ $pedido->direccion?->direccion_linea_1 }}
                                <br>{{ $pedido->direccion?->municipio }}
                            </dd>
                        </div>
                        <div>
                            <dt class="font-black uppercase tracking-wide text-atlantia-ink/50">Repartidor</dt>
                            <dd class="mt-1 text-atlantia-ink/75">{{ $repartidorNombre ?? 'Pendiente de asignación' }}</dd>
                        </div>
                        <div>
                            <dt class="font-black uppercase tracking-wide text-atlantia-ink/50">Distancia restante</dt>
                            <dd id="tracking-distance" class="mt-1 text-atlantia-ink/75">
                                {{ $estado === 'en_ruta' ? 'Calculando…' : '—' }}
                            </dd>
                        </div>
                    </dl>
                </section>

                <section class="rounded-2xl bg-atlantia-blush p-5">
                    <h2 class="font-black text-atlantia-ink">¿Necesitas ayuda?</h2>
                    <p class="mt-2 text-sm leading-6 text-atlantia-ink/70">
                        Si el repartidor no avanza o necesitas actualizar una referencia,
                        @if (config('atlantia.support.whatsapp'))
                            <a href="https://wa.me/{{ \App\Services\Notificaciones\WhatsAppService::normalizarTelefono(config('atlantia.support.whatsapp')) }}" target="_blank" rel="noopener" class="font-bold text-atlantia-wine hover:underline">escríbenos por WhatsApp</a>
                            o llámanos al {{ config('atlantia.support.phone') }}.
                        @else
                            llámanos al {{ config('atlantia.support.phone') }}.
                        @endif
                    </p>
                </section>
            </aside>
        </div>
    </section>

    <script @nonce src="{{ asset('vendor/leaflet/leaflet.js') }}"></script>
    <script @nonce>
        (() => {
            const tracking = @json($mapData);
            const mapElement = document.getElementById('tracking-map');
            if (! mapElement || ! window.L) {
                return;
            }

            const ROUTE_COLOR = '#8b1538';
            const ROUTE_TIME_FACTOR = 1.2;
            const OFF_ROUTE_METERS = 60;
            const REROUTE_MIN_MS = 20000;
            const ARRIVED_METERS = 60;
            const $ = (id) => document.getElementById(id);
            const point = (p) => (p && Number.isFinite(Number(p.latitude)) && Number.isFinite(Number(p.longitude)))
                ? [Number(p.latitude), Number(p.longitude)]
                : null;
            const pin = (emoji, bg, size) => L.divIcon({
                className: '',
                iconSize: [size, size],
                iconAnchor: [size / 2, size / 2],
                html: `<div style="width:${size}px;height:${size}px;border-radius:50%;background:${bg};border:3px solid #fff;`
                    + `box-shadow:0 4px 12px rgba(0,0,0,.3);display:grid;place-items:center;font-size:${size / 2}px">${emoji}</div>`,
            });
            const formatKm = (m) => m >= 1000 ? `${(m / 1000).toFixed(1)} km` : `${Math.max(10, Math.round(m / 10) * 10)} m`;

            const destino = point(tracking.destino);
            const exacto = ! tracking.destino?.aproximado;
            const map = L.map(mapElement, { zoomControl: true, attributionControl: true }).setView(destino, 15);
            L.tileLayer(tracking.mapa.tiles_url, { maxZoom: 19, attribution: tracking.mapa.tiles_attribution }).addTo(map);

            const travelled = L.polyline([], { color: '#94a3b8', weight: 5, opacity: .7, dashArray: '2 9', lineCap: 'round' }).addTo(map);
            const pending = L.polyline([], { color: ROUTE_COLOR, weight: 6, opacity: .95, lineCap: 'round', lineJoin: 'round' }).addTo(map);
            if (exacto) {
                L.marker(destino, { icon: pin('🏠', '#15803d', 36), title: 'Tu dirección' }).addTo(map);
            }

            const view = { courier: null, marker: null, route: null, routedAt: 0, follow: true };
            const storageKey = `atlantia.seguimiento.${tracking.numero}`;
            const readInitial = () => {
                try { return JSON.parse(sessionStorage.getItem(storageKey) || 'null'); } catch (_) { return null; }
            };
            const saveInitial = (route) => {
                try { sessionStorage.setItem(storageKey, JSON.stringify({ path: route.path, total: route.total })); } catch (_) {}
            };

            const buildRoute = (path, duration) => {
                const cumulative = [0];
                for (let i = 1; i < path.length; i += 1) {
                    cumulative.push(cumulative[i - 1] + L.latLng(path[i - 1]).distanceTo(path[i]));
                }
                const total = cumulative[cumulative.length - 1];
                const nearest = (p) => {
                    let best = 0, bestDistance = Infinity;
                    path.forEach((q, i) => {
                        const d = L.latLng(q).distanceTo(p);
                        if (d < bestDistance) { bestDistance = d; best = i; }
                    });
                    return { index: best, distance: bestDistance };
                };
                return { path, cumulative, total, duration, nearest };
            };

            /* Encuadra la moto y lo que falta del recorrido; abajo se deja espacio para la tarjeta. */
            const fitAll = (animate = false) => {
                const bounds = L.latLngBounds([destino]);
                if (view.courier) bounds.extend(view.courier);
                pending.getLatLngs().forEach((p) => bounds.extend(p));
                map.fitBounds(bounds, {
                    paddingTopLeft: [48, 48],
                    paddingBottomRight: [48, window.innerWidth < 640 ? 200 : 170],
                    maxZoom: 17,
                    animate,
                });
            };

            const render = () => {
                if (tracking.estado !== 'en_ruta' || ! view.courier) {
                    return;
                }

                const leftDirect = L.latLng(view.courier).distanceTo(destino);
                let remaining = leftDirect;
                let minutes = null;

                if (view.route) {
                    const { index } = view.route.nearest(view.courier);
                    remaining = Math.max(0, view.route.total - view.route.cumulative[index]);
                    minutes = Math.max(1, Math.round((view.route.duration * (remaining / Math.max(view.route.total, 1))) / 60));
                    pending.setLatLngs(view.route.path.slice(index));
                }

                const initial = readInitial();
                if (initial?.path?.length) {
                    const first = buildRoute(initial.path, 0);
                    const near = first.nearest(view.courier);
                    if (near.distance < 80) {
                        travelled.setLatLngs(first.path.slice(0, near.index + 1));
                    }
                    $('tracking-bar').style.width = `${Math.min(100, Math.max(4, (1 - remaining / Math.max(initial.total, 1)) * 100))}%`;
                }

                const arrived = exacto && leftDirect < ARRIVED_METERS;
                $('tracking-card').classList.toggle('ring-2', arrived);
                $('tracking-card').classList.toggle('ring-emerald-500', arrived);
                $('tracking-label').textContent = arrived ? '¡Tu repartidor llegó!' : 'Tu pedido va en camino';
                $('tracking-eta').textContent = arrived ? 'Está en tu puerta' : (minutes ? `Llega en ${minutes} min` : 'Llega pronto');
                $('tracking-meta').textContent = arrived
                    ? 'Muestra tu código de entrega'
                    : [tracking.repartidorNombre, 'Repartidor Atlantia', exacto ? formatKm(remaining) : null].filter(Boolean).join(' · ');
                $('tracking-distance').textContent = exacto ? (arrived ? 'En tu puerta' : formatKm(remaining)) : 'Te llamará al llegar a tu zona';
            };

            const reroute = async (force = false) => {
                if (! view.courier || ! exacto) {
                    return;
                }
                /* Mientras el repartidor siga la ruta solo se recorta; se recalcula si se desvia. */
                const offRoute = view.route ? view.route.nearest(view.courier).distance > OFF_ROUTE_METERS : true;
                if (! force && (! offRoute || Date.now() - view.routedAt < REROUTE_MIN_MS)) {
                    return;
                }
                view.routedAt = Date.now();
                try {
                    const [lat, lng] = view.courier;
                    const url = `${tracking.mapa.routing_url}/route/v1/driving/${lng},${lat};${destino[1]},${destino[0]}?overview=full&geometries=geojson`;
                    const response = await fetch(url, { cache: 'no-store' });
                    const data = await response.json();
                    const route = data.routes?.[0];
                    if (! route?.geometry?.coordinates?.length) {
                        throw new Error('sin ruta');
                    }
                    const first = ! view.route;
                    view.route = buildRoute(route.geometry.coordinates.map(([x, y]) => [y, x]), route.duration * ROUTE_TIME_FACTOR);
                    if (! readInitial()) {
                        saveInitial(view.route);
                    }
                    render();
                    if (first) {
                        fitAll();
                    }
                } catch (_) {
                    if (! view.route) {
                        pending.setLatLngs([view.courier, destino]);
                    }
                }
            };

            const placeCourier = (p) => {
                const next = point(p);
                if (! next || tracking.estado !== 'en_ruta') {
                    return;
                }
                view.courier = next;
                if (! view.marker) {
                    view.marker = L.marker(next, { icon: pin('🛵', '#5e0008', 42), zIndexOffset: 1000, title: 'Repartidor' }).addTo(map);
                } else {
                    view.marker.setLatLng(next);
                }
                render();
                reroute(! view.route);
                if (view.follow && view.route) {
                    fitAll(true);
                }
            };

            map.on('dragstart', () => { view.follow = false; });
            $('tracking-center').addEventListener('click', () => { view.follow = true; fitAll(true); });

            placeCourier(tracking.repartidor);
            fitAll();

            if (['entregado', 'cancelado', 'rechazado'].includes(tracking.estado)) {
                return;
            }

            window.setInterval(async () => {
                try {
                    const response = await fetch(tracking.liveUrl, {
                        headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                    });
                    if (! response.ok) {
                        return;
                    }
                    const payload = await response.json();
                    const estado = payload.data?.pedido?.estado;
                    if (estado && estado !== tracking.estado) {
                        window.location.reload();
                        return;
                    }
                    placeCourier(payload.data?.repartidor);
                } catch (_) {
                    // Sin conexion: se reintenta en el siguiente ciclo.
                }
            }, 10000);
        })();
    </script>
@endsection
