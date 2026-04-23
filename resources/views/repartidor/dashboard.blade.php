@extends('layouts.app')

@php
    $preloadMapboxToken = config('services.mapbox.token') ?: env('MAPBOX_TOKEN');
@endphp

@if ($preloadMapboxToken)
    @push('styles')
        <link href="https://api.mapbox.com/mapbox-gl-js/v3.9.4/mapbox-gl.css" rel="stylesheet">
    @endpush
@endif

@section('content')
    @php
        $overview = $metrics['overview'];
        $rutaActual = $metrics['ruta_actual'];
        $pedidoActual = $rutaActual?->pedido;
        $proximas = $metrics['proximas_entregas']->reject(fn ($ruta) => $rutaActual && $ruta->id === $rutaActual->id);
        $mapboxToken = config('services.mapbox.token') ?: env('MAPBOX_TOKEN');
        $direccion = $pedidoActual?->direccion;
        $telefono = $direccion?->telefono_contacto;
        $telefonoWhatsApp = $telefono ? preg_replace('/\D+/', '', $telefono) : null;
        $items = $pedidoActual?->items ?? collect();
        $total = (float) ($pedidoActual?->total ?? 0);
        $cambioSugerido = max(0, 20 - $total);
        $destino = $direccion && $direccion->latitude && $direccion->longitude
            ? ['latitude' => (float) $direccion->latitude, 'longitude' => (float) $direccion->longitude]
            : null;
        $geometry = $rutaActual?->ruta_planificada['geometry'] ?? null;
        $accepted = $rutaActual?->aceptada_at !== null;
        $readyToPickup = $pedidoActual?->estadoValor() === 'listo_para_entrega';
        $inRoute = $pedidoActual?->estadoValor() === 'en_ruta';
        $pendingPickup = $accepted && ! $readyToPickup && ! $inRoute;
        $statusBadge = match (true) {
            $inRoute => ['label' => 'EN CAMINO', 'class' => 'bg-atlantia-wine text-white'],
            $readyToPickup => ['label' => 'LISTO PARA RECOGER', 'class' => 'bg-emerald-600 text-white'],
            $accepted => ['label' => 'ACEPTADO', 'class' => 'bg-amber-100 text-amber-800'],
            default => ['label' => 'ASIGNADO', 'class' => 'bg-atlantia-blush text-atlantia-wine'],
        };
    @endphp

    <section class="mx-auto max-w-[470px] pb-24 xl:max-w-6xl">
        <div class="overflow-hidden rounded-none bg-[#fbf7f9] shadow-sm xl:rounded-2xl xl:border xl:border-atlantia-rose/20">
            <header class="bg-atlantia-wine px-5 pb-6 pt-5 text-white xl:px-8">
                <div class="flex items-center justify-between gap-4">
                    <div class="flex items-center gap-3">
                        <div class="grid h-12 w-12 place-items-center rounded-full bg-white text-lg font-black text-atlantia-wine">
                            {{ Str::of(auth()->user()->name)->substr(0, 1)->upper() }}
                        </div>
                        <div>
                            <p class="text-xs font-black uppercase tracking-[0.18em] text-white/70">
                                Buenas {{ now()->hour < 12 ? 'dias' : (now()->hour < 18 ? 'tardes' : 'noches') }}
                            </p>
                            <h1 class="text-lg font-black leading-tight">{{ auth()->user()->name }}</h1>
                        </div>
                    </div>

                    <span class="inline-flex items-center gap-2 rounded-full border border-white/20 bg-white/10 px-4 py-2 text-xs font-black">
                        <span class="h-3 w-3 rounded-full bg-emerald-400"></span>
                        En linea
                    </span>
                </div>

                <div class="mt-5 grid grid-cols-3 gap-3">
                    <div class="rounded-lg border border-white/20 bg-white/10 p-3 text-center">
                        <p class="text-xs font-black uppercase tracking-widest text-white/70">Entregas</p>
                        <p class="mt-1 text-3xl font-black">{{ number_format($overview['entregas_hoy']) }}</p>
                        <p class="text-[11px] text-white/70">hoy</p>
                    </div>
                    <div class="rounded-lg border border-white/20 bg-white/10 p-3 text-center">
                        <p class="text-xs font-black uppercase tracking-widest text-white/70">Ganancia</p>
                        <p class="mt-1 text-3xl font-black">Q {{ number_format($overview['entregas_hoy'] * 25, 2) }}</p>
                        <p class="text-[11px] text-white/70">estimada</p>
                    </div>
                    <div class="rounded-lg border border-white/20 bg-white/10 p-3 text-center">
                        <p class="text-xs font-black uppercase tracking-widest text-white/70">Km</p>
                        <p class="mt-1 text-3xl font-black">
                            {{ number_format((float) ($rutaActual?->distancia_km ?? 0), 1) }}
                        </p>
                        <p class="text-[11px] text-white/70">actuales</p>
                    </div>
                </div>
            </header>

            <div class="space-y-6 px-5 py-5 xl:grid xl:grid-cols-[1.1fr_0.9fr] xl:gap-6 xl:space-y-0 xl:px-8">
                <div class="space-y-6">
                    <article class="rounded-xl bg-emerald-600 p-4 text-white shadow-sm">
                        <div class="flex items-center justify-between gap-4">
                            <div>
                                <p class="text-xs font-black uppercase tracking-[0.16em] text-white/75">Efectivo acumulado</p>
                                <p class="mt-1 text-3xl font-black">Q {{ number_format($overview['entregas_hoy'] * 25, 2) }}</p>
                            </div>
                            <button class="rounded-lg border border-white/30 px-4 py-2 text-xs font-black" type="button">
                                Depositar
                            </button>
                        </div>
                    </article>

                    <article class="rounded-xl border-2 border-atlantia-wine bg-white shadow-sm">
                        <div class="flex items-start justify-between gap-4 border-b border-atlantia-rose/20 p-4">
                            <div>
                                <h2 class="text-2xl font-black text-atlantia-ink">Entrega actual</h2>
                                <p class="mt-1 text-sm text-atlantia-ink/60">
                                    {{ $pedidoActual ? 'Pedido activo asignado a tu ruta.' : 'No tienes entregas activas por ahora.' }}
                                </p>
                            </div>
                            @if ($pedidoActual)
                                <div class="text-right">
                                    <span class="{{ $statusBadge['class'] }} rounded-full px-3 py-1 text-[11px] font-black">
                                        {{ $statusBadge['label'] }}
                                    </span>
                                    <p class="mt-2 text-xs font-bold text-atlantia-ink/60">{{ $pedidoActual->numero_pedido }}</p>
                                </div>
                            @endif
                        </div>

                        @if ($pedidoActual)
                            <div class="relative h-48 overflow-hidden bg-sky-50">
                                @if ($mapboxToken && $destino)
                                    <div
                                        id="courier-current-map"
                                        class="h-full w-full"
                                        data-token="{{ $mapboxToken }}"
                                        data-destino='@json($destino)'
                                        data-geometry='@json($geometry)'
                                    ></div>
                                @else
                                    <div class="absolute inset-0 bg-[linear-gradient(135deg,#e8f7ff_25%,#d8f0fb_25%,#d8f0fb_50%,#e8f7ff_50%,#e8f7ff_75%,#d8f0fb_75%,#d8f0fb_100%)] bg-[length:56px_56px]"></div>
                                    <div class="absolute left-8 right-8 top-20 border-t-4 border-dashed border-atlantia-wine"></div>
                                    <span class="absolute right-10 top-12 rounded-md bg-atlantia-wine px-4 py-2 text-xs font-black text-white">
                                        Destino
                                    </span>
                                @endif

                                <div class="absolute bottom-4 left-4 right-4 rounded-lg bg-white p-3 shadow">
                                    <div class="flex items-center justify-between gap-3">
                                        <div>
                                            <p class="text-2xl font-black text-atlantia-wine">
                                                {{ number_format((float) $rutaActual->distancia_km, 1) }}
                                            </p>
                                            <p class="text-xs font-bold text-atlantia-ink/60">
                                                km · llegas en {{ $rutaActual->tiempo_estimado_min ?? 45 }} min
                                            </p>
                                        </div>
                                        @if ($destino)
                                            <a
                                                href="https://www.google.com/maps/dir/?api=1&destination={{ $destino['latitude'] }},{{ $destino['longitude'] }}"
                                                target="_blank"
                                                class="rounded-lg bg-atlantia-wine px-5 py-3 text-sm font-black text-white"
                                            >
                                                Navegar
                                            </a>
                                        @endif
                                    </div>
                                </div>
                            </div>

                            <div class="space-y-4 p-4">
                                @if ($pendingPickup)
                                    <div class="rounded-lg border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900">
                                        Pedido aceptado. Espera la alerta de "listo para recoger" antes de pasar al punto de venta.
                                    </div>
                                @endif

                                @if ($readyToPickup)
                                    <div class="rounded-lg border border-emerald-200 bg-emerald-50 p-4 text-sm font-bold text-emerald-800">
                                        El pedido ya esta listo. Pasa a recogerlo y marca "Recogido" al tenerlo contigo.
                                    </div>
                                @endif

                                <div class="rounded-lg bg-atlantia-blush/60 p-4">
                                    <p class="text-xs font-black uppercase tracking-[0.18em] text-atlantia-ink/50">Entregar a</p>
                                    <h3 class="mt-2 text-lg font-black text-atlantia-ink">
                                        {{ $direccion?->nombre_contacto ?: $pedidoActual->cliente?->name }}
                                    </h3>
                                    <p class="mt-1 text-sm leading-6 text-atlantia-ink/70">
                                        {{ $direccion?->direccion_linea_1 }}
                                        @if ($direccion?->direccion_linea_2)
                                            , {{ $direccion->direccion_linea_2 }}
                                        @endif
                                        · {{ $direccion?->municipio }}
                                    </p>
                                </div>

                                <div class="grid grid-cols-2 gap-3">
                                    <a href="tel:{{ $telefono }}" class="rounded-lg border border-emerald-300 bg-white p-3 text-center">
                                        <p class="text-xs font-black uppercase text-atlantia-ink/50">Llamar</p>
                                        <p class="mt-1 font-black text-atlantia-ink">{{ $telefono ?: 'Sin telefono' }}</p>
                                    </a>
                                    <a
                                        href="{{ $telefonoWhatsApp ? 'https://wa.me/502' . $telefonoWhatsApp : '#' }}"
                                        target="_blank"
                                        class="rounded-lg border border-emerald-300 bg-white p-3 text-center"
                                    >
                                        <p class="text-xs font-black uppercase text-atlantia-ink/50">WhatsApp</p>
                                        <p class="mt-1 font-black text-atlantia-ink">Mensaje</p>
                                    </a>
                                </div>

                                @if ($pedidoActual->notas)
                                    <div class="rounded-lg border border-amber-200 bg-amber-50 p-4">
                                        <p class="text-sm font-bold text-amber-900">Nota del cliente: {{ $pedidoActual->notas }}</p>
                                    </div>
                                @endif

                                <div class="rounded-lg bg-white p-4 shadow-sm">
                                    <div class="flex items-center justify-between">
                                        <p class="text-xs font-black uppercase tracking-[0.14em] text-atlantia-ink/55">
                                            Contenido del pedido
                                        </p>
                                        <a href="{{ route('repartidor.pedidos.show', $pedidoActual) }}" class="text-xs font-black text-atlantia-wine">
                                            Ver todo
                                        </a>
                                    </div>
                                    <div class="mt-3 space-y-2">
                                        @foreach ($items->take(4) as $item)
                                            <div class="flex items-center justify-between gap-3 text-sm">
                                                <span>{{ $item->cantidad }}x {{ $item->producto?->nombre }}</span>
                                                <span class="font-black text-atlantia-ink">Q {{ number_format((float) $item->subtotal, 2) }}</span>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>

                                <div class="rounded-xl bg-emerald-600 p-4 text-white">
                                    <p class="text-xs font-black uppercase tracking-[0.16em] text-white/75">
                                        {{ $pedidoActual->metodoPagoValor() === 'efectivo' ? 'Cobrar en efectivo' : 'Pago registrado' }}
                                    </p>
                                    <div class="mt-1 flex items-end justify-between gap-3">
                                        <p class="text-4xl font-black">Q {{ number_format($total, 2) }}</p>
                                        @if ($pedidoActual->metodoPagoValor() === 'efectivo')
                                            <p class="text-right text-xs font-bold text-white/80">
                                                Si paga con Q20.00<br>Cambio: Q {{ number_format($cambioSugerido, 2) }}
                                            </p>
                                        @endif
                                    </div>
                                </div>

                                <div class="grid grid-cols-4 gap-2 rounded-xl bg-atlantia-cream p-3 text-center text-[11px] font-black">
                                    @foreach ([
                                        ['label' => 'Asignado', 'done' => true],
                                        ['label' => 'Aceptado', 'done' => $accepted],
                                        ['label' => 'En camino', 'done' => $inRoute],
                                        ['label' => 'Entregado', 'done' => false],
                                    ] as $step)
                                        <div class="space-y-2">
                                            <span class="{{ $step['done'] ? 'bg-emerald-600 text-white' : 'bg-white text-atlantia-wine' }} mx-auto grid h-8 w-8 place-items-center rounded-full border border-atlantia-rose/20">
                                                {{ $step['done'] ? '✓' : '·' }}
                                            </span>
                                            <p class="text-atlantia-ink/80">{{ $step['label'] }}</p>
                                        </div>
                                    @endforeach
                                </div>

                                <div class="grid gap-3">
                                    @if (! $accepted)
                                        <form method="POST" action="{{ route('repartidor.pedidos.accept', $pedidoActual) }}">
                                            @csrf
                                            @method('PATCH')
                                            <button class="w-full rounded-lg bg-atlantia-wine px-5 py-4 text-base font-black text-white" type="submit">
                                                Aceptar pedido
                                            </button>
                                        </form>
                                    @elseif ($readyToPickup)
                                        <form method="POST" action="{{ route('repartidor.pedidos.pickup', $pedidoActual) }}">
                                            @csrf
                                            @method('PATCH')
                                            <button class="w-full rounded-lg bg-emerald-600 px-5 py-4 text-base font-black text-white" type="submit">
                                                Marcar como recogido
                                            </button>
                                        </form>
                                    @elseif ($inRoute)
                                        <form method="POST" action="{{ route('repartidor.pedidos.deliver', $pedidoActual) }}" enctype="multipart/form-data">
                                            @csrf
                                            @method('PATCH')
                                            <input class="mb-3 block w-full rounded-lg border border-atlantia-rose/25 bg-white p-3 text-sm" name="foto_entrega" type="file" accept="image/*">
                                            <button class="w-full rounded-lg bg-emerald-600 px-5 py-4 text-base font-black text-white" type="submit">
                                                Marcar como entregado
                                            </button>
                                        </form>
                                    @else
                                        <button class="w-full rounded-lg bg-slate-200 px-5 py-4 text-base font-black text-slate-500" disabled type="button">
                                            Esperando preparacion
                                        </button>
                                    @endif

                                    <form method="POST" action="{{ route('repartidor.incidencias.store', $pedidoActual) }}" class="grid gap-2 sm:grid-cols-[1fr_auto]">
                                        @csrf
                                        <input type="hidden" name="tipo" value="reparto">
                                        <input
                                            class="rounded-lg border border-atlantia-rose/25 bg-white px-4 py-3 text-sm"
                                            name="descripcion"
                                            placeholder="Reportar problema: cliente no responde, direccion confusa..."
                                        >
                                        <button class="rounded-lg border border-red-200 px-4 py-3 text-sm font-black text-red-600" type="submit">
                                            Reportar
                                        </button>
                                    </form>
                                </div>
                            </div>
                        @else
                            <div class="p-8 text-center">
                                <p class="text-lg font-black text-atlantia-ink">Estas libre por ahora.</p>
                                <p class="mt-2 text-sm text-atlantia-ink/60">
                                    Cuando administracion te asigne una entrega aparecera aqui y recibiras una notificacion interna.
                                </p>
                            </div>
                        @endif
                    </article>
                </div>

                <aside class="space-y-6">
                    <article class="rounded-xl border border-atlantia-rose/20 bg-white p-4 shadow-sm">
                        <div class="flex items-center justify-between">
                            <h2 class="text-2xl font-black text-atlantia-ink">Siguientes entregas</h2>
                            <span class="text-sm font-bold text-atlantia-ink/60">{{ $proximas->count() }} pedidos</span>
                        </div>

                        <div class="mt-4 space-y-3">
                            @forelse ($proximas as $index => $ruta)
                                <a
                                    href="{{ route('repartidor.pedidos.show', $ruta->pedido) }}"
                                    class="block rounded-lg border border-atlantia-rose/15 bg-white p-4 shadow-sm transition hover:border-atlantia-wine"
                                >
                                    <div class="flex items-center justify-between gap-3">
                                        <div class="flex items-center gap-3">
                                            <span class="grid h-10 w-10 place-items-center rounded-lg bg-atlantia-blush font-black text-atlantia-wine">
                                                {{ $index + 2 }}
                                            </span>
                                            <div>
                                                <p class="font-black text-atlantia-ink">{{ $ruta->pedido?->cliente?->name }}</p>
                                                <p class="text-xs text-atlantia-ink/55">
                                                    {{ $ruta->pedido?->direccion?->zona_o_barrio ?: $ruta->pedido?->direccion?->municipio }}
                                                    · {{ number_format((float) $ruta->distancia_km, 1) }} km
                                                </p>
                                            </div>
                                        </div>
                                        <div class="text-right">
                                            <p class="font-black text-atlantia-wine">Q {{ number_format((float) $ruta->pedido?->total, 2) }}</p>
                                            <p class="mt-1 rounded bg-sky-50 px-2 py-1 text-[10px] font-black uppercase text-sky-700">
                                                {{ $ruta->pedido?->metodoPagoValor() }}
                                            </p>
                                        </div>
                                    </div>
                                </a>
                            @empty
                                <div class="rounded-lg border border-dashed border-atlantia-rose/30 p-6 text-center text-sm text-atlantia-ink/60">
                                    No hay mas entregas pendientes en tu cola.
                                </div>
                            @endforelse
                        </div>
                    </article>
                </aside>
            </div>
        </div>

        <nav class="fixed inset-x-0 bottom-0 z-30 border-t border-atlantia-rose/15 bg-white px-4 py-2 shadow-[0_-8px_24px_rgba(42,16,24,0.08)] xl:hidden">
            <div class="mx-auto grid max-w-[470px] grid-cols-4 text-center text-xs font-black">
                <a href="{{ route('repartidor.dashboard') }}" class="rounded-lg px-2 py-2 text-atlantia-wine">Entregas</a>
                <a href="{{ route('repartidor.rutas.index') }}" class="rounded-lg px-2 py-2 text-atlantia-ink/55">Ruta</a>
                <a href="{{ route('repartidor.pedidos.index') }}" class="rounded-lg px-2 py-2 text-atlantia-ink/55">Pedidos</a>
                <a href="#" class="rounded-lg px-2 py-2 text-atlantia-ink/55">Perfil</a>
            </div>
        </nav>
    </section>

    @if ($mapboxToken && $destino)
        @push('scripts')
            <script @nonce src="https://api.mapbox.com/mapbox-gl-js/v3.9.4/mapbox-gl.js"></script>
            <script @nonce>
                (() => {
                    const element = document.getElementById('courier-current-map');

                    if (! element || ! window.mapboxgl) {
                        return;
                    }

                    const destino = JSON.parse(element.dataset.destino);
                    const geometry = JSON.parse(element.dataset.geometry || 'null');

                    window.mapboxgl.accessToken = element.dataset.token;

                    const map = new window.mapboxgl.Map({
                        container: element,
                        style: 'mapbox://styles/mapbox/satellite-streets-v12',
                        center: [destino.longitude, destino.latitude],
                        zoom: 14,
                        pitch: 50,
                        bearing: -20,
                    });

                    map.addControl(new window.mapboxgl.NavigationControl({ visualizePitch: true }), 'top-right');

                    map.on('load', () => {
                        new window.mapboxgl.Marker({ color: '#7a1f3d' })
                            .setLngLat([destino.longitude, destino.latitude])
                            .setPopup(new window.mapboxgl.Popup().setHTML('<strong>Destino</strong>'))
                            .addTo(map);

                        if (geometry?.coordinates?.length) {
                            map.addSource('route', {
                                type: 'geojson',
                                data: { type: 'Feature', geometry },
                            });

                            map.addLayer({
                                id: 'route-line',
                                type: 'line',
                                source: 'route',
                                paint: {
                                    'line-color': '#7a1f3d',
                                    'line-width': 5,
                                    'line-opacity': 0.9,
                                },
                            });

                            const bounds = new window.mapboxgl.LngLatBounds();
                            geometry.coordinates.forEach((coordinate) => bounds.extend(coordinate));
                            map.fitBounds(bounds, { padding: 48, maxZoom: 16 });
                        }
                    });
                })();
            </script>
        @endpush
    @endif
@endsection
