@extends('layouts.marketplace')

@section('content')
    @php
        $pedido = $seguimiento['pedido'];
        $ruta = $seguimiento['ruta'];
        $ultima = $seguimiento['ultima_ubicacion'];
        $mapboxToken = $seguimiento['mapbox_token'];
        $mapData = [
            'token' => $mapboxToken,
            'liveUrl' => route('cliente.pedidos.seguimiento.live', $pedido),
            'pedido' => [
                'numero' => $pedido->numero_pedido,
                'estado' => $pedido->estado,
            ],
            'centro' => $seguimiento['centro'],
            'destino' => $seguimiento['destino'],
            'repartidor' => $seguimiento['repartidor'],
            'rutaPlanificada' => $seguimiento['ruta_planificada'],
            'rutaReal' => $seguimiento['ruta_real'],
        ];
    @endphp

    @if ($mapboxToken)
        <link href="https://api.mapbox.com/mapbox-gl-js/v3.9.4/mapbox-gl.css" rel="stylesheet">
    @endif

    <section class="mx-auto w-full max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
        <div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
            <x-page-header
                title="Seguimiento en vivo"
                subtitle="Mira el avance de tu pedido y la ubicacion del repartidor cuando este asignado."
                class="mb-0"
            />

            <a
                href="{{ route('cliente.pedidos.show', $pedido) }}"
                class="inline-flex rounded-md border border-atlantia-rose/40 px-4 py-2 text-sm font-bold
                    text-atlantia-wine hover:bg-atlantia-blush"
            >
                Ver detalle del pedido
            </a>
        </div>

        <div class="grid gap-6 lg:grid-cols-[1fr_360px]">
            <section class="overflow-hidden rounded-lg border border-atlantia-rose/20 bg-white shadow-sm">
                <div class="flex flex-col gap-3 border-b border-atlantia-rose/15 p-5 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <p class="text-xs font-black uppercase tracking-normal text-atlantia-wine">
                            Pedido {{ $pedido->numero_pedido }}
                        </p>
                        <h1 class="mt-1 text-2xl font-black text-atlantia-ink">
                            Ruta de entrega
                        </h1>
                    </div>

                    <span
                        id="tracking-status"
                        class="inline-flex rounded-full bg-emerald-100 px-4 py-2 text-xs font-black uppercase text-emerald-800"
                    >
                        {{ $ultima ? 'Ubicacion activa' : 'Esperando repartidor' }}
                    </span>
                </div>

                <div class="relative">
                    @if ($mapboxToken)
                        <div id="tracking-map" class="h-[520px] w-full bg-atlantia-blush"></div>
                    @else
                        <div class="flex h-[520px] items-center justify-center bg-atlantia-blush p-6 text-center">
                            <div class="max-w-md">
                                <h2 class="text-2xl font-black text-atlantia-ink">Mapa pendiente de configuracion</h2>
                                <p class="mt-3 text-sm leading-6 text-atlantia-ink/70">
                                    Agrega MAPBOX_TOKEN en tu archivo .env para activar el mapa en tiempo real.
                                </p>
                            </div>
                        </div>
                    @endif

                    <div
                        class="absolute bottom-4 left-4 right-4 rounded-lg bg-white/95 p-4 shadow-lg backdrop-blur
                            sm:left-auto sm:w-96"
                    >
                        <p class="text-xs font-black uppercase tracking-normal text-atlantia-wine">Entrega estimada</p>
                        <p class="mt-1 text-xl font-black text-atlantia-ink">
                            {{ $seguimiento['eta_minutos'] ? $seguimiento['eta_minutos'] . ' minutos' : '45 a 60 minutos' }}
                        </p>
                        <p id="tracking-updated" class="mt-2 text-xs text-atlantia-ink/60">
                            {{ $ultima?->timestamp_gps ? 'Actualizado ' . $ultima->timestamp_gps->diffForHumans() : 'Aun no hay senal GPS del repartidor.' }}
                        </p>
                    </div>
                </div>
            </section>

            <aside class="space-y-6">
                <section class="rounded-lg border border-atlantia-rose/20 bg-white p-5 shadow-sm">
                    <h2 class="text-xl font-black text-atlantia-ink">Estado del pedido</h2>

                    <div class="mt-5 space-y-4">
                        @foreach (['confirmado', 'preparando', 'en_ruta', 'entregado'] as $estadoPaso)
                            @php
                                $activo = $pedido->estado === $estadoPaso;
                                $completado = array_search($pedido->estado, ['confirmado', 'preparando', 'en_ruta', 'entregado'], true)
                                    >= array_search($estadoPaso, ['confirmado', 'preparando', 'en_ruta', 'entregado'], true);
                            @endphp
                            <div class="flex gap-3">
                                <span
                                    @class([
                                        'mt-1 flex h-8 w-8 shrink-0 items-center justify-center rounded-full text-xs font-black',
                                        'bg-atlantia-wine text-white' => $activo,
                                        'bg-emerald-600 text-white' => $completado && ! $activo,
                                        'bg-slate-200 text-slate-500' => ! $completado && ! $activo,
                                    ])
                                >
                                    {{ $loop->iteration }}
                                </span>
                                <div>
                                    <p class="font-bold text-atlantia-ink">
                                        {{ ucfirst(str_replace('_', ' ', $estadoPaso)) }}
                                    </p>
                                    <p class="text-sm text-atlantia-ink/60">
                                        {{ $activo ? 'Estado actual' : ($completado ? 'Completado' : 'Pendiente') }}
                                    </p>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </section>

                <section class="rounded-lg border border-atlantia-rose/20 bg-white p-5 shadow-sm">
                    <h2 class="text-xl font-black text-atlantia-ink">Entrega</h2>
                    <dl class="mt-4 space-y-4 text-sm">
                        <div>
                            <dt class="font-black uppercase tracking-normal text-atlantia-ink/50">Destino</dt>
                            <dd class="mt-1 leading-6 text-atlantia-ink/75">
                                {{ $pedido->direccion?->direccion_linea_1 }}
                                <br>{{ $pedido->direccion?->municipio }}
                            </dd>
                        </div>
                        <div>
                            <dt class="font-black uppercase tracking-normal text-atlantia-ink/50">Repartidor</dt>
                            <dd class="mt-1 text-atlantia-ink/75">
                                {{ $ruta?->repartidor?->name ?? 'Pendiente de asignacion' }}
                            </dd>
                        </div>
                        <div>
                            <dt class="font-black uppercase tracking-normal text-atlantia-ink/50">Distancia</dt>
                            <dd class="mt-1 text-atlantia-ink/75">
                                {{ $ruta?->distancia_km ? number_format((float) $ruta->distancia_km, 2) . ' km' : 'Calculando' }}
                            </dd>
                        </div>
                    </dl>
                </section>

                <section class="rounded-lg bg-atlantia-blush p-5">
                    <h2 class="font-black text-atlantia-ink">Necesitas ayuda?</h2>
                    <p class="mt-2 text-sm leading-6 text-atlantia-ink/70">
                        Si el repartidor no avanza o necesitas actualizar una referencia, contactanos al 2345-6789.
                    </p>
                </section>
            </aside>
        </div>
    </section>

    @if ($mapboxToken)
        <script src="https://api.mapbox.com/mapbox-gl-js/v3.9.4/mapbox-gl.js"></script>
        <script>
            (() => {
                const tracking = @json($mapData);
                const mapElement = document.getElementById('tracking-map');
                const statusElement = document.getElementById('tracking-status');
                const updatedElement = document.getElementById('tracking-updated');

                if (! mapElement || ! window.mapboxgl) {
                    return;
                }

                mapboxgl.accessToken = tracking.token;

                const lngLat = (point) => [Number(point.longitude), Number(point.latitude)];
                const lineFeature = (points) => ({
                    type: 'Feature',
                    geometry: {
                        type: 'LineString',
                        coordinates: points.map(lngLat),
                    },
                });

                const map = new mapboxgl.Map({
                    container: 'tracking-map',
                    style: 'mapbox://styles/mapbox/streets-v12',
                    center: lngLat(tracking.repartidor || tracking.centro || tracking.destino),
                    zoom: 13,
                });

                map.addControl(new mapboxgl.NavigationControl({ showCompass: false }), 'top-right');

                const destinoMarker = new mapboxgl.Marker({ color: '#7a1f3d' })
                    .setLngLat(lngLat(tracking.destino))
                    .setPopup(new mapboxgl.Popup().setHTML('<strong>Destino</strong><br>' + (tracking.destino.address || 'Entrega')))
                    .addTo(map);

                let repartidorMarker = null;

                const putCourier = (point) => {
                    if (! point) {
                        return;
                    }

                    if (! repartidorMarker) {
                        repartidorMarker = new mapboxgl.Marker({ color: '#059669' })
                            .setPopup(new mapboxgl.Popup().setHTML('<strong>Repartidor</strong><br>Ubicacion actual'))
                            .addTo(map);
                    }

                    repartidorMarker.setLngLat(lngLat(point));
                    statusElement.textContent = 'Ubicacion activa';
                };

                const upsertLine = (sourceId, layerId, points, color, width) => {
                    if (! points || points.length < 2) {
                        return;
                    }

                    const data = lineFeature(points);

                    if (map.getSource(sourceId)) {
                        map.getSource(sourceId).setData(data);
                        return;
                    }

                    map.addSource(sourceId, { type: 'geojson', data });
                    map.addLayer({
                        id: layerId,
                        type: 'line',
                        source: sourceId,
                        layout: {
                            'line-cap': 'round',
                            'line-join': 'round',
                        },
                        paint: {
                            'line-color': color,
                            'line-width': width,
                            'line-opacity': 0.9,
                        },
                    });
                };

                const fitToData = (data) => {
                    const points = [data.destino, data.repartidor, ...(data.rutaReal || []), ...(data.rutaPlanificada || [])]
                        .filter(Boolean);

                    if (points.length < 2) {
                        return;
                    }

                    const bounds = new mapboxgl.LngLatBounds();
                    points.forEach((point) => bounds.extend(lngLat(point)));
                    map.fitBounds(bounds, { padding: 80, maxZoom: 15, duration: 700 });
                };

                const applyTracking = (data, shouldFit = false) => {
                    putCourier(data.repartidor);
                    upsertLine('ruta-planificada', 'ruta-planificada-layer', data.rutaPlanificada, '#7a1f3d', 4);
                    upsertLine('ruta-real', 'ruta-real-layer', data.rutaReal, '#059669', 5);

                    if (data.actualizado_at) {
                        updatedElement.textContent = 'GPS actualizado: ' + new Date(data.actualizado_at).toLocaleTimeString();
                    }

                    if (shouldFit) {
                        fitToData(data);
                    }
                };

                map.on('load', () => applyTracking(tracking, true));

                window.setInterval(async () => {
                    try {
                        const response = await fetch(tracking.liveUrl, {
                            headers: {
                                'Accept': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest',
                            },
                        });

                        if (! response.ok) {
                            return;
                        }

                        const payload = await response.json();
                        applyTracking({
                            destino: payload.data.destino,
                            repartidor: payload.data.repartidor,
                            rutaPlanificada: payload.data.ruta_planificada,
                            rutaReal: payload.data.ruta_real,
                            actualizado_at: payload.data.actualizado_at,
                        });
                    } catch (error) {
                        statusElement.textContent = 'Reconectando';
                    }
                }, 10000);
            })();
        </script>
    @endif
@endsection
