@extends('layouts.app')

@section('content')
    @php
        $estado        = $pedido->estadoValor();
        $ruta          = $pedido->deliveryRoute;
        $direccion     = $pedido->direccion;
        $telefono      = $direccion?->telefono_contacto;
        $whatsapp      = $telefono ? preg_replace('/\D+/', '', $telefono) : null;
        $total         = (float) $pedido->total;
        $esEfectivo    = $pedido->metodoPagoValor() === 'efectivo';
        $cambio        = max(0, 100 - $total);

        $accepted      = $ruta?->aceptada_at !== null;
        $listoRecoger  = $estado === 'listo_para_entrega';
        $enRuta        = $estado === 'en_ruta';
        $entregado     = $estado === 'entregado';
        $cancelado     = $estado === 'cancelado';
        $preparando    = $estado === 'preparando';

        $pasoActual = match (true) {
            $entregado   => 3,
            $enRuta      => 2,
            $listoRecoger || ($preparando && $accepted) => 1,
            default      => 0,
        };

        $hasDestCoords = $direccion?->latitude && $direccion?->longitude;
    @endphp

    <section class="mx-auto w-full max-w-full pb-28 sm:max-w-lg md:max-w-2xl xl:max-w-4xl">
        <div class="overflow-hidden rounded-none bg-[#fbf7f9] shadow-sm sm:rounded-2xl sm:border sm:border-atlantia-rose/20">

            {{-- Header --}}
            <header class="bg-atlantia-wine px-4 pb-5 pt-5 text-white sm:px-6 md:px-8">
                <div class="flex items-center justify-between gap-4">
                    <div>
                        <a href="{{ route('repartidor.pedidos.index') }}"
                            class="mb-1 inline-flex items-center gap-1 text-xs font-black text-white/70 hover:text-white">
                            ← Mis pedidos
                        </a>
                        <h1 class="text-lg font-black leading-tight sm:text-xl">{{ $pedido->numero_pedido }}</h1>
                    </div>
                    <span class="rounded-full border border-white/20 bg-white/10 px-3 py-1.5 text-xs font-black">
                        {{ ucfirst(str_replace('_', ' ', $estado)) }}
                    </span>
                </div>

                {{-- Progreso --}}
                <div class="mt-5 grid grid-cols-4 gap-1 rounded-xl bg-white/10 p-3 text-center text-[10px] font-black sm:gap-3 sm:text-xs">
                    @foreach ([
                        ['label' => 'Asignado',  'paso' => 0],
                        ['label' => 'Aceptado',  'paso' => 1],
                        ['label' => 'En camino', 'paso' => 2],
                        ['label' => 'Entregado', 'paso' => 3],
                    ] as $step)
                        <div class="space-y-1.5 sm:space-y-2">
                            <span class="{{ $pasoActual > $step['paso'] ? 'bg-white text-atlantia-wine' : ($pasoActual === $step['paso'] ? 'border-2 border-white bg-transparent' : 'border border-white/30 bg-transparent') }} mx-auto grid h-8 w-8 place-items-center rounded-full text-sm sm:h-9 sm:w-9">
                                {{ $pasoActual > $step['paso'] ? '✓' : ($pasoActual === $step['paso'] ? '●' : '·') }}
                            </span>
                            <p class="{{ $pasoActual >= $step['paso'] ? 'text-white' : 'text-white/50' }} leading-tight">{{ $step['label'] }}</p>
                        </div>
                    @endforeach
                </div>
            </header>

            {{-- Mapa de ruta embebido (siempre visible si hay coords de destino) --}}
            @if ($hasDestCoords)
                <div
                    id="ruta-map-container"
                    class="relative bg-slate-100"
                    style="height: clamp(360px, 55vw, 520px)"
                    data-dest-lat="{{ $direccion->latitude }}"
                    data-dest-lng="{{ $direccion->longitude }}"
                    data-dest-label="{{ addslashes($direccion->nombre_contacto ?: $pedido->cliente?->name) }}"
                >
                    {{-- Loader --}}
                    <div id="ruta-map-loader" class="absolute inset-0 z-10 flex flex-col items-center justify-center gap-2 bg-slate-100">
                        <div class="h-8 w-8 animate-spin rounded-full border-4 border-atlantia-wine border-t-transparent"></div>
                        <p class="text-xs font-bold text-atlantia-ink/60">Cargando mapa...</p>
                    </div>

                    {{-- Canvas del mapa --}}
                    <div id="ruta-map" class="h-full w-full"></div>

                    {{-- Boton Iniciar ruta (centrado sobre el mapa) --}}
                    <div id="ruta-start-overlay" class="absolute inset-0 z-20 hidden flex-col items-center justify-center gap-3 bg-black/30 backdrop-blur-[2px]">
                        <div class="rounded-2xl bg-white px-6 py-5 text-center shadow-xl">
                            <p class="text-sm font-black text-atlantia-ink">Mapa listo</p>
                            <p class="mt-1 text-xs text-atlantia-ink/60">Presiona para ver tu ruta al destino</p>
                            <button
                                id="ruta-start-btn"
                                type="button"
                                class="mt-4 w-full rounded-xl bg-atlantia-wine px-6 py-3 text-sm font-black text-white active:scale-95"
                            >
                                Iniciar ruta
                            </button>
                        </div>
                    </div>

                </div>

                {{-- Panel de info (distancia / tiempo) — debajo del mapa, no lo tapa --}}
                <div id="ruta-info-panel" class="hidden border-b border-atlantia-rose/15 bg-white px-4 py-3">
                    <div class="flex items-center justify-between gap-3">
                        <div>
                            <p id="ruta-distance" class="text-2xl font-black text-atlantia-wine">—</p>
                            <p id="ruta-duration" class="text-sm text-atlantia-ink/60">calculando...</p>
                        </div>
                        <div class="text-right">
                            <p id="ruta-gps-accuracy" class="text-xs text-atlantia-ink/55">—</p>
                            <button id="ruta-refresh-btn" type="button"
                                class="mt-1 rounded-lg border border-atlantia-rose/30 px-4 py-2 text-sm font-black text-atlantia-wine active:scale-95">
                                Actualizar
                            </button>
                        </div>
                    </div>
                </div>
            @endif

            <div class="space-y-4 px-4 py-5 sm:px-6 md:px-8">

                {{-- Alerta de estado (solo un mensaje a la vez) --}}
                @if (session('success'))
                    <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-bold text-emerald-800">
                        {{ session('success') }}
                    </div>
                @elseif ($entregado)
                    <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-center">
                        <p class="text-2xl font-black text-emerald-700">Pedido entregado</p>
                        <p class="mt-1 text-sm text-emerald-600">Este pedido fue entregado exitosamente.</p>
                        @if ($ruta?->completada_at)
                            <p class="mt-1 text-xs text-emerald-500">
                                Completado {{ $ruta->completada_at->format('d/m/Y H:i') }}
                                @if ($ruta->tiempo_real_min)
                                    · {{ $ruta->tiempo_real_min }} min totales
                                @endif
                            </p>
                        @endif
                    </div>
                @elseif ($cancelado)
                    <div class="rounded-xl border border-red-200 bg-red-50 p-4 text-center">
                        <p class="text-lg font-black text-red-700">Pedido cancelado</p>
                        <p class="mt-1 text-sm text-red-500">Este pedido fue cancelado y no requiere entrega.</p>
                    </div>
                @elseif (! $accepted)
                    <div class="rounded-xl border border-sky-200 bg-sky-50 p-4">
                        <p class="text-sm font-black text-sky-800">Tienes una nueva entrega. Acepta para confirmar tu disponibilidad.</p>
                    </div>
                @elseif ($listoRecoger)
                    <div class="rounded-xl border-2 border-emerald-400 bg-emerald-50 p-4">
                        <p class="text-base font-black text-emerald-800">El pedido esta listo para recoger.</p>
                        <p class="mt-1 text-sm text-emerald-700">Dirígete al punto de venta, recoge el pedido y activa tu GPS antes de salir.</p>
                    </div>
                @elseif ($enRuta)
                    <div class="rounded-xl border-2 border-atlantia-wine bg-atlantia-blush/60 p-4">
                        <p class="text-base font-black text-atlantia-wine">En camino al cliente.</p>
                        <p class="mt-1 text-sm text-atlantia-ink/70">Mantén el tracking activo para que el sistema registre tu recorrido.</p>
                    </div>
                @elseif ($preparando && $accepted)
                    <div class="rounded-xl border border-amber-200 bg-amber-50 p-4">
                        <p class="text-sm font-black text-amber-800">Pedido aceptado — en preparacion.</p>
                        <p class="mt-1 text-sm text-amber-700">Espera la alerta de "listo para recoger" antes de ir al punto de venta.</p>
                    </div>
                @endif

                {{-- Panel GPS tracking en tiempo real (solo cuando en_ruta) --}}
                @if ($enRuta)
                    <div
                        id="gps-tracking-panel"
                        class="rounded-xl border-2 border-atlantia-wine bg-white p-4 shadow-sm"
                        data-pedido-id="{{ $pedido->id }}"
                        data-gps-endpoint="{{ route('repartidor.gps.store') }}"
                        data-csrf="{{ csrf_token() }}"
                    >
                        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                            <div>
                                <p class="text-xs font-black uppercase tracking-wider text-atlantia-ink/50">Compartir ubicacion</p>
                                <p id="gps-status-text" class="mt-1 text-sm font-bold text-atlantia-ink/70">Tracking inactivo</p>
                            </div>
                            <button id="gps-toggle-btn" type="button"
                                class="w-full rounded-lg bg-atlantia-wine px-4 py-3 text-sm font-black text-white transition active:scale-95 sm:w-auto sm:py-2.5">
                                Activar tracking
                            </button>
                        </div>
                        <div id="gps-error-display" class="mt-3 hidden rounded-md bg-red-50 px-3 py-2">
                            <p id="gps-error-text" class="text-xs font-bold text-red-700"></p>
                        </div>
                    </div>
                @endif

                {{-- Destino del cliente --}}
                <div class="rounded-xl border border-atlantia-rose/20 bg-white p-4 shadow-sm">
                    <p class="text-xs font-black uppercase tracking-[0.18em] text-atlantia-ink/50">Entregar a</p>
                    <h2 class="mt-2 text-xl font-black text-atlantia-ink">
                        {{ $direccion?->nombre_contacto ?: $pedido->cliente?->name }}
                    </h2>
                    <p class="mt-1 text-sm leading-6 text-atlantia-ink/70">
                        {{ $direccion?->direccion_linea_1 }}
                        @if ($direccion?->zona_o_barrio)
                            — {{ $direccion->zona_o_barrio }}
                        @endif
                        @if ($direccion?->municipio)
                            <br>{{ $direccion->municipio }}
                        @endif
                    </p>
                    @if ($direccion?->referencia)
                        <div class="mt-3 rounded-md border border-amber-200 bg-amber-50 px-3 py-2">
                            <p class="text-xs font-black text-amber-700">Referencia del cliente:</p>
                            <p class="text-sm text-amber-800">{{ $direccion->referencia }}</p>
                        </div>
                    @endif

                    @if ($telefono)
                        <div class="mt-4 grid grid-cols-2 gap-3 sm:grid-cols-[auto_auto] sm:justify-start">
                            <a href="tel:{{ $telefono }}"
                                class="flex items-center justify-center gap-2 rounded-lg border border-emerald-300 bg-white px-5 py-3.5 text-sm font-black text-emerald-700 sm:py-3">
                                📞 Llamar
                            </a>
                            <a href="{{ $whatsapp ? 'https://wa.me/502' . $whatsapp : '#' }}" target="_blank"
                                class="flex items-center justify-center gap-2 rounded-lg border border-emerald-300 bg-white px-5 py-3.5 text-sm font-black text-emerald-700 sm:py-3">
                                💬 WhatsApp
                            </a>
                        </div>
                    @endif
                </div>

                {{-- Nota del pedido --}}
                @if ($pedido->notas)
                    <div class="rounded-xl border border-amber-200 bg-amber-50 p-4">
                        <p class="text-xs font-black uppercase text-amber-700">Nota del cliente</p>
                        <p class="mt-1 text-sm text-amber-900">{{ $pedido->notas }}</p>
                    </div>
                @endif

                {{-- Items --}}
                <div class="rounded-xl border border-atlantia-rose/20 bg-white p-4 shadow-sm">
                    <p class="text-xs font-black uppercase tracking-[0.18em] text-atlantia-ink/50">Contenido del pedido</p>
                    <div class="mt-3 divide-y divide-atlantia-rose/10">
                        @forelse ($pedido->items as $item)
                            <div class="flex items-center justify-between gap-3 py-3">
                                <p class="font-bold text-atlantia-ink">
                                    {{ $item->producto_nombre_snapshot ?: $item->producto?->nombre ?? 'Producto' }}
                                </p>
                                <span class="inline-flex h-7 w-7 flex-shrink-0 items-center justify-center rounded-full bg-atlantia-blush text-xs font-black text-atlantia-wine">
                                    {{ $item->cantidad }}
                                </span>
                            </div>
                        @empty
                            <p class="py-3 text-sm text-atlantia-ink/55">Sin items registrados.</p>
                        @endforelse
                    </div>
                </div>

                {{-- Total / cobro --}}
                <div class="rounded-xl {{ $esEfectivo ? 'bg-emerald-600' : 'bg-atlantia-wine' }} p-5 text-white">
                    <p class="text-xs font-black uppercase tracking-[0.16em] text-white/75">
                        {{ $esEfectivo ? 'Cobrar en efectivo' : 'Pago ya registrado' }}
                    </p>
                    <p class="mt-1 text-4xl font-black">Q {{ number_format($total, 2) }}</p>
                    @if ($esEfectivo)
                        <p class="mt-1 text-sm text-white/70">Si paga con Q100 · Cambio sugerido: Q {{ number_format($cambio, 2) }}</p>
                    @else
                        <p class="mt-1 text-sm text-white/70">El cliente ya pago con {{ ucfirst($pedido->metodoPagoValor()) }}. No cobrar.</p>
                    @endif
                </div>

                {{-- Acciones según estado --}}
                @if (! $entregado && ! $cancelado)
                    <div class="space-y-3">
                        @if (! $accepted)
                            <form method="POST" action="{{ route('repartidor.pedidos.accept', $pedido->uuid) }}">
                                @csrf
                                @method('PATCH')
                                <button type="submit"
                                    class="w-full rounded-xl bg-atlantia-wine px-5 py-4 text-base font-black text-white shadow-sm active:scale-95">
                                    Aceptar entrega
                                </button>
                            </form>

                        @elseif ($listoRecoger)
                            <form method="POST" action="{{ route('repartidor.pedidos.pickup', $pedido->uuid) }}" id="pickup-form">
                                @csrf
                                @method('PATCH')
                                <input type="hidden" name="latitude_inicio" id="pickup-lat">
                                <input type="hidden" name="longitude_inicio" id="pickup-lng">

                                <div class="rounded-xl border-2 border-emerald-400 bg-white p-4">
                                    <p class="text-sm font-black text-atlantia-ink">Captura tu GPS antes de salir</p>
                                    <p class="mt-0.5 text-xs text-atlantia-ink/60">Se registra el punto de inicio de tu ruta.</p>
                                    <div class="mt-3 flex items-center gap-3">
                                        <button type="button" id="pickup-gps-btn"
                                            class="rounded-lg border border-emerald-400 bg-white px-4 py-2.5 text-sm font-black text-emerald-700 active:scale-95">
                                            Capturar mi GPS
                                        </button>
                                        <p id="pickup-gps-status" class="text-xs text-atlantia-ink/60">Aun no capturado.</p>
                                    </div>
                                </div>

                                <button type="submit" id="pickup-submit-btn" disabled
                                    class="mt-3 w-full rounded-xl bg-emerald-600 px-5 py-4 text-base font-black text-white shadow-sm transition disabled:cursor-not-allowed disabled:opacity-50 active:scale-95">
                                    Ya recogi el pedido — Iniciar entrega
                                </button>
                            </form>

                        @elseif ($enRuta)
                            <form method="POST" action="{{ route('repartidor.pedidos.deliver', $pedido->uuid) }}"
                                enctype="multipart/form-data">
                                @csrf
                                @method('PATCH')
                                <p class="text-xs font-black text-atlantia-ink">Confirmar entrega al cliente</p>
                                <label class="mt-2 block text-xs text-atlantia-ink/60">
                                    Foto de entrega <span class="text-atlantia-ink/40">(opcional)</span>
                                </label>
                                <input type="file" name="foto_entrega" accept="image/*" capture="environment"
                                    class="mt-1 mb-3 block w-full rounded-lg border border-atlantia-rose/25 bg-white p-3 text-sm">
                                <textarea name="notas" rows="2" placeholder="Nota de entrega (opcional)..."
                                    class="mb-3 block w-full rounded-lg border border-atlantia-rose/25 bg-white px-4 py-3 text-sm"></textarea>
                                <button type="submit"
                                    class="w-full rounded-xl bg-emerald-600 px-5 py-4 text-base font-black text-white shadow-sm active:scale-95">
                                    Confirmar entrega
                                </button>
                            </form>

                        @else
                            <button type="button" disabled
                                class="w-full rounded-xl bg-slate-200 px-5 py-4 text-base font-black text-slate-500 cursor-not-allowed">
                                Esperando que el pedido este listo
                            </button>
                        @endif

                        {{-- Reportar incidencia --}}
                        <form method="POST" action="{{ route('repartidor.incidencias.store', $pedido->uuid) }}"
                            class="grid gap-2 sm:grid-cols-[1fr_auto]">
                            @csrf
                            <input type="hidden" name="tipo" value="reparto">
                            <input type="text" name="descripcion"
                                placeholder="Reportar problema: cliente no responde, direccion confusa..."
                                class="rounded-lg border border-atlantia-rose/25 bg-white px-4 py-3 text-sm">
                            <button type="submit"
                                class="rounded-lg border border-red-200 bg-white px-4 py-3 text-sm font-black text-red-600 hover:bg-red-50">
                                Reportar
                            </button>
                        </form>
                    </div>
                @endif
            </div>
        </div>

        {{-- Bottom nav --}}
        <nav class="fixed inset-x-0 bottom-0 z-40 border-t border-atlantia-rose/15 bg-white px-4 py-2 pb-safe shadow-[0_-8px_24px_rgba(42,16,24,0.08)] xl:hidden">
            <div class="mx-auto grid max-w-2xl grid-cols-4 text-center text-xs font-black sm:text-sm">
                <a href="{{ route('repartidor.dashboard') }}" class="rounded-lg px-2 py-2.5 text-atlantia-ink/55">Entregas</a>
                <a href="{{ route('repartidor.rutas.index') }}" class="rounded-lg px-2 py-2.5 text-atlantia-ink/55">Ruta</a>
                <a href="{{ route('repartidor.pedidos.index') }}" class="rounded-lg px-2 py-2.5 text-atlantia-wine">Pedidos</a>
                <a href="#" class="rounded-lg px-2 py-2.5 text-atlantia-ink/55">Perfil</a>
            </div>
        </nav>
    </section>
@endsection

@push('scripts')
@if ($hasDestCoords)
<script
    @nonce
    async
    defer
    src="https://maps.googleapis.com/maps/api/js?key={{ config('services.google_maps.api_key') }}&libraries=geometry&callback=atlantiaRutaMapReady&loading=async"
></script>
@endif

<script @nonce>
(() => {
    /* ══════════════════════════════════════════════════════════════════
       ESTADO COMPARTIDO
       lastPosition se comparte entre tracking y mapa para que el mapa
       dibuje la ruta automáticamente cuando el tracking ya tiene GPS.
    ══════════════════════════════════════════════════════════════════ */
    let routeMap           = null;
    let repartidorMarker   = null;
    let directionsRenderer = null;
    let watchId            = null;
    let lastSentAt         = 0;
    let lastMapUpdateAt    = 0;
    let lastPosition       = null;   // última posición conocida (tracking o manual)
    let routeDrawn         = false;  // ¿se dibujó la ruta al menos una vez?
    const MIN_SERVER_MS    = 15000;
    const MIN_MAP_MS       = 4000;

    /* ── DOM ───────────────────────────────────────────────────────── */
    const mapContainer    = document.getElementById('ruta-map-container');
    const mapLoader       = document.getElementById('ruta-map-loader');
    const mapEl           = document.getElementById('ruta-map');
    const startOverlay    = document.getElementById('ruta-start-overlay');
    const startBtn        = document.getElementById('ruta-start-btn');
    const refreshBtn      = document.getElementById('ruta-refresh-btn');
    const infoPanel       = document.getElementById('ruta-info-panel');
    const distEl          = document.getElementById('ruta-distance');
    const durEl           = document.getElementById('ruta-duration');
    const accEl           = document.getElementById('ruta-gps-accuracy');
    const trackingPanel   = document.getElementById('gps-tracking-panel');
    const toggleBtn       = document.getElementById('gps-toggle-btn');
    const statusText      = document.getElementById('gps-status-text');
    const errorDisplay    = document.getElementById('gps-error-display');
    const errorText       = document.getElementById('gps-error-text');
    const pickupGpsBtn    = document.getElementById('pickup-gps-btn');
    const pickupLat       = document.getElementById('pickup-lat');
    const pickupLng       = document.getElementById('pickup-lng');
    const pickupStatus    = document.getElementById('pickup-gps-status');
    const pickupSubmitBtn = document.getElementById('pickup-submit-btn');

    const destLat   = mapContainer ? parseFloat(mapContainer.dataset.destLat)  : null;
    const destLng   = mapContainer ? parseFloat(mapContainer.dataset.destLng)  : null;
    const destLabel = mapContainer ? (mapContainer.dataset.destLabel || 'Destino') : 'Destino';

    /* ══════════════════════════════════════════════════════════════════
       MAPA — callback de Google Maps
    ══════════════════════════════════════════════════════════════════ */
    window.atlantiaRutaMapReady = () => {
        if (! mapEl || ! destLat || ! destLng) return;

        routeMap = new google.maps.Map(mapEl, {
            center: { lat: destLat, lng: destLng },
            zoom: 14,
            mapTypeId: 'roadmap',
            mapTypeControl: false,
            streetViewControl: false,
            fullscreenControl: false,
            zoomControl: true,
        });

        new google.maps.Marker({
            position: { lat: destLat, lng: destLng },
            map: routeMap,
            title: destLabel,
            icon: {
                path: google.maps.SymbolPath.CIRCLE,
                scale: 11,
                fillColor: '#7a1f3d',
                fillOpacity: 1,
                strokeColor: '#ffffff',
                strokeWeight: 2.5,
            },
        });

        directionsRenderer = new google.maps.DirectionsRenderer({
            map: routeMap,
            suppressMarkers: true,
            polylineOptions: { strokeColor: '#1d4ed8', strokeWeight: 5, strokeOpacity: 0.85 },
        });

        if (mapLoader) mapLoader.classList.add('hidden');

        /* Si el tracking ya entregó una posición antes de que el mapa
           cargara → dibujar ruta inmediatamente sin pedir nada */
        if (lastPosition) {
            if (startOverlay) startOverlay.style.display = 'none';
            lastMapUpdateAt = 0;
            drawRoute(lastPosition.coords.latitude, lastPosition.coords.longitude, lastPosition.coords.accuracy);
        } else {
            /* Sin posición aún → mostrar overlay "Iniciar ruta" */
            if (startOverlay) {
                startOverlay.style.display = 'flex';
                startOverlay.classList.remove('hidden');
            }
        }
    };

    /* ══════════════════════════════════════════════════════════════════
       DIBUJAR RUTA
    ══════════════════════════════════════════════════════════════════ */
    const drawRoute = (originLat, originLng, accuracy) => {
        if (! routeMap) return;

        const now = Date.now();
        if (now - lastMapUpdateAt < MIN_MAP_MS) return;
        lastMapUpdateAt = now;
        routeDrawn = true;

        const origin = { lat: originLat, lng: originLng };

        if (repartidorMarker) {
            repartidorMarker.setPosition(origin);
        } else {
            repartidorMarker = new google.maps.Marker({
                position: origin,
                map: routeMap,
                title: 'Tu ubicacion',
                icon: {
                    path: google.maps.SymbolPath.FORWARD_CLOSED_ARROW,
                    scale: 6,
                    fillColor: '#1d4ed8',
                    fillOpacity: 1,
                    strokeColor: '#ffffff',
                    strokeWeight: 2,
                    rotation: 0,
                },
                zIndex: 10,
            });
        }

        if (accEl && accuracy) accEl.textContent = `GPS ~${Math.round(accuracy)} m`;

        const directionsService = new google.maps.DirectionsService();
        directionsService.route(
            { origin, destination: { lat: destLat, lng: destLng }, travelMode: google.maps.TravelMode.DRIVING },
            (result, status) => {
                if (status === google.maps.DirectionsStatus.OK) {
                    directionsRenderer.setDirections(result);
                    const leg = result.routes[0]?.legs[0];
                    if (infoPanel) infoPanel.classList.remove('hidden');
                    if (distEl && leg?.distance) distEl.textContent = leg.distance.text;
                    if (durEl  && leg?.duration) durEl.textContent  = leg.duration.text + ' aprox.';
                    if (result.routes[0]?.bounds)
                        routeMap.fitBounds(result.routes[0].bounds, { top: 24, right: 24, bottom: 110, left: 24 });
                } else {
                    new google.maps.Polyline({
                        path: [origin, { lat: destLat, lng: destLng }],
                        map: routeMap,
                        strokeColor: '#1d4ed8', strokeWeight: 4, strokeOpacity: 0.7, geodesic: true,
                    });
                    const dist = google.maps.geometry.spherical.computeDistanceBetween(
                        new google.maps.LatLng(originLat, originLng),
                        new google.maps.LatLng(destLat, destLng)
                    );
                    if (infoPanel) infoPanel.classList.remove('hidden');
                    if (distEl) distEl.textContent = (dist / 1000).toFixed(1) + ' km';
                    if (durEl)  durEl.textContent  = 'Ruta aproximada (sin calles)';
                    routeMap.fitBounds(new google.maps.LatLngBounds(
                        new google.maps.LatLng(Math.min(originLat, destLat), Math.min(originLng, destLng)),
                        new google.maps.LatLng(Math.max(originLat, destLat), Math.max(originLng, destLng))
                    ), { top: 24, right: 24, bottom: 110, left: 24 });
                }
            }
        );
    };

    /* ══════════════════════════════════════════════════════════════════
       CAPTURAR GPS Y DIBUJAR (fallback manual si tracking no tiene pos.)
       ─ Si ya hay lastPosition (tracking activo) → usa esa posición.
       ─ Si no → pide GPS con getCurrentPosition (1 sola llamada, sin
         conflicto con el watchPosition del tracking).
    ══════════════════════════════════════════════════════════════════ */
    const captureAndDraw = (onSuccess) => {
        /* ── Caso A: ya tenemos posición del tracking → usarla directo ── */
        if (lastPosition && routeMap) {
            if (startOverlay) startOverlay.style.display = 'none';
            lastMapUpdateAt = 0;
            drawRoute(lastPosition.coords.latitude, lastPosition.coords.longitude, lastPosition.coords.accuracy);
            if (onSuccess) onSuccess(lastPosition);
            return;
        }

        /* ── Caso B: no hay tracking activo → pedir GPS manualmente ─── */
        if (! navigator.geolocation) {
            if (startBtn) startBtn.textContent = 'GPS no disponible';
            return;
        }

        if (startBtn) { startBtn.disabled = true; startBtn.textContent = 'Buscando GPS…'; }

        /* Usar getCurrentPosition (una sola vez, no interfiere con watchPosition) */
        navigator.geolocation.getCurrentPosition(
            (pos) => {
                lastPosition = pos;
                if (startOverlay) startOverlay.style.display = 'none';
                lastMapUpdateAt = 0;
                drawRoute(pos.coords.latitude, pos.coords.longitude, pos.coords.accuracy);
                if (onSuccess) onSuccess(pos);
                if (startBtn) { startBtn.disabled = false; startBtn.textContent = 'Actualizar ruta'; }
            },
            (err) => {
                if (startBtn) { startBtn.disabled = false; startBtn.textContent = 'Sin GPS — toca para reintentar'; }
                /* Mostrar ayuda específica para permisos denegados */
                if (err.code === 1) {
                    if (startBtn) startBtn.textContent = '⚙️ Activa ubicación en Configuración';
                }
            },
            { enableHighAccuracy: true, maximumAge: 10000, timeout: 15000 }
        );
    };

    startBtn?.addEventListener('click', () => captureAndDraw());
    refreshBtn?.addEventListener('click', () => { lastMapUpdateAt = 0; captureAndDraw(); });

    /* ══════════════════════════════════════════════════════════════════
       GPS PICKUP (listo_para_entrega)
    ══════════════════════════════════════════════════════════════════ */
    if (pickupGpsBtn) {
        pickupGpsBtn.addEventListener('click', () => {
            if (! navigator.geolocation) {
                if (pickupStatus) pickupStatus.textContent = 'GPS no disponible en este dispositivo.';
                return;
            }
            pickupGpsBtn.disabled = true;
            pickupGpsBtn.textContent = 'Buscando GPS…';
            if (pickupStatus) pickupStatus.textContent = 'Obteniendo ubicacion precisa...';

            navigator.geolocation.getCurrentPosition(
                (pos) => {
                    lastPosition = pos;
                    const lat = pos.coords.latitude.toFixed(8);
                    const lng = pos.coords.longitude.toFixed(8);
                    const acc = Math.round(pos.coords.accuracy || 0);

                    if (pickupLat) pickupLat.value = lat;
                    if (pickupLng) pickupLng.value = lng;

                    if (pickupStatus) {
                        pickupStatus.textContent = `GPS capturado · ~${acc} m de precision`;
                        pickupStatus.className = 'text-xs font-bold text-emerald-700';
                    }
                    pickupGpsBtn.textContent = 'Actualizar GPS';
                    pickupGpsBtn.disabled = false;
                    if (pickupSubmitBtn) pickupSubmitBtn.disabled = false;

                    if (routeMap) {
                        if (startOverlay) startOverlay.style.display = 'none';
                        lastMapUpdateAt = 0;
                        drawRoute(pos.coords.latitude, pos.coords.longitude, pos.coords.accuracy);
                    }
                },
                (err) => {
                    if (pickupStatus) {
                        pickupStatus.textContent = err.code === 1
                            ? 'Permiso denegado — activa ubicacion en Configuracion'
                            : 'No se pudo obtener GPS. Intenta de nuevo.';
                        pickupStatus.className = 'text-xs font-bold text-red-600';
                    }
                    pickupGpsBtn.textContent = 'Reintentar GPS';
                    pickupGpsBtn.disabled = false;
                },
                { enableHighAccuracy: true, maximumAge: 10000, timeout: 15000 }
            );
        });
    }

    /* ══════════════════════════════════════════════════════════════════
       GPS TRACKING EN TIEMPO REAL (en_ruta)
       onPosition recibe cada actualización del watchPosition y:
         1. Guarda lastPosition (compartido con captureAndDraw)
         2. Dibuja la ruta automáticamente en el mapa
         3. Envía coordenadas al servidor cada 15 s
    ══════════════════════════════════════════════════════════════════ */
    if (! trackingPanel) return;

    const pedidoId    = trackingPanel.dataset.pedidoId;
    const gpsEndpoint = trackingPanel.dataset.gpsEndpoint;
    const csrfToken   = trackingPanel.dataset.csrf;

    const showError = (msg) => {
        if (! errorText || ! errorDisplay) return;
        errorText.textContent = msg;
        errorDisplay.classList.remove('hidden');
        setTimeout(() => errorDisplay.classList.add('hidden'), 7000);
    };

    const onPosition = async (pos) => {
        /* Guardar posición compartida */
        lastPosition = pos;

        const lat = pos.coords.latitude;
        const lng = pos.coords.longitude;
        const acc = pos.coords.accuracy;
        const now = Date.now();

        /* Dibujar ruta automáticamente:
           - Si aún no se dibujó (routeDrawn = false) → dibujar aunque no hayan pasado 4 s
           - Si ya se dibujó → respetar throttle MIN_MAP_MS */
        if (routeMap) {
            const shouldDraw = !routeDrawn || (now - lastMapUpdateAt >= MIN_MAP_MS);
            if (shouldDraw) {
                if (startOverlay) startOverlay.style.display = 'none';
                lastMapUpdateAt = 0;
                drawRoute(lat, lng, acc);
            }
        }

        /* Enviar al servidor (throttle 15 s) */
        if (now - lastSentAt < MIN_SERVER_MS) return;
        lastSentAt = now;

        try {
            const res = await fetch(gpsEndpoint, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json',
                },
                body: JSON.stringify({
                    latitude: lat,
                    longitude: lng,
                    accuracy_meters: acc ? Math.round(acc * 100) / 100 : null,
                    estado: 'en_ruta',
                    pedido_id: pedidoId ? parseInt(pedidoId) : null,
                    timestamp_gps: new Date(pos.timestamp).toISOString(),
                }),
            });
            if (! res.ok) showError('Error al enviar GPS (' + res.status + ')');
        } catch {
            showError('Sin conexion. Se reintentara automaticamente.');
        }
    };

    const startTracking = () => {
        if (! navigator.geolocation) { showError('GPS no disponible en este dispositivo.'); return; }

        watchId = navigator.geolocation.watchPosition(
            onPosition,
            (err) => {
                if (err.code === 1) {
                    showError('GPS denegado — activa ubicacion en Configuracion del telefono.');
                } else {
                    showError('Error GPS: ' + err.message);
                }
            },
            { enableHighAccuracy: true, maximumAge: 5000, timeout: 20000 }
        );

        if (toggleBtn) {
            toggleBtn.textContent = 'Detener tracking';
            toggleBtn.classList.replace('bg-atlantia-wine', 'bg-slate-600');
        }
        if (statusText) {
            statusText.textContent = 'Tracking activo — actualizando mapa y servidor.';
            statusText.className = 'mt-1 text-sm font-bold text-emerald-700';
        }
    };

    const stopTracking = () => {
        if (watchId !== null) { navigator.geolocation.clearWatch(watchId); watchId = null; }
        if (toggleBtn) {
            toggleBtn.textContent = 'Activar tracking';
            toggleBtn.classList.replace('bg-slate-600', 'bg-atlantia-wine');
        }
        if (statusText) {
            statusText.textContent = 'Tracking inactivo.';
            statusText.className = 'mt-1 text-sm font-bold text-atlantia-ink/70';
        }
    };

    toggleBtn?.addEventListener('click', () => watchId !== null ? stopTracking() : startTracking());

    /* Auto-iniciar tracking cuando está en_ruta */
    startTracking();
})();
</script>
@endpush
