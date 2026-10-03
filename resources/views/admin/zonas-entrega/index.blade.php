@extends(auth()->user()?->isSuperAdmin() && request()->routeIs('admin.*') ? 'layouts.super-admin' : 'layouts.app')

@section('content')
    @php
        $collection = $zonas->getCollection();
        $mapCollection = $zonasBusqueda ?? $collection;
        $activeZones = $mapCollection->where('activa', true)->count();
        $averageCost = $mapCollection->avg(fn ($zona) => (float) $zona->costo_base) ?? 0;
        $coverage = max(0, $activeZones * 31.75);
        $ordersInZones = \App\Models\Pedido::query()->where('created_at', '>=', now()->subDays(30))->count();
        $municipiosOperativos = ['Puerto Barrios', 'Santo Tomas'];
        $municipiosActivosCheckout = ($zonasActivas ?? $collection->where('activa', true))
            ->pluck('municipio')
            ->map(fn ($municipio) => str_contains((string) $municipio, 'Santo Tom') ? 'Santo Tomas' : $municipio)
            ->unique()
            ->values();
        $municipiosPendientesCheckout = collect($municipiosOperativos)
            ->reject(fn ($municipio) => $municipiosActivosCheckout->contains($municipio))
            ->values();
        $municipios = ['Puerto Barrios', 'Santo Tomas', 'Morales', 'Los Amates', 'Livingston', 'El Estor'];
        $dias = [
            'lun' => 'Lun',
            'mar' => 'Mar',
            'mie' => 'Mie',
            'jue' => 'Jue',
            'vie' => 'Vie',
            'sab' => 'Sab',
            'dom' => 'Dom',
        ];
        $googleMapsKey = config('services.google_maps.api_key');
        $googleMapsMapId = config('services.google_maps.map_id');
        $defaultMapCenter = [
            'latitude' => config('services.google_maps.default_lat', 15.7309),
            'longitude' => config('services.google_maps.default_lng', -88.5944),
            'zoom' => config('services.google_maps.default_zoom', 13),
        ];
        $municipioCentros = [
            'Puerto Barrios' => ['latitude' => 15.7309, 'longitude' => -88.5944],
            'Santo Tomas' => ['latitude' => 15.6906, 'longitude' => -88.6229],
            'Morales' => ['latitude' => 15.4769, 'longitude' => -88.8166],
            'Los Amates' => ['latitude' => 15.2558, 'longitude' => -89.0964],
            'Livingston' => ['latitude' => 15.8277, 'longitude' => -88.7501],
            'El Estor' => ['latitude' => 15.5333, 'longitude' => -89.3500],
        ];
        $municipiosMapaOperativos = collect($municipiosOperativos)
            ->map(fn ($municipio) => [
                'nombre' => $municipio,
                'latitude' => $municipioCentros[$municipio]['latitude'],
                'longitude' => $municipioCentros[$municipio]['longitude'],
                'label' => $municipio === 'Puerto Barrios' ? 'PB' : 'ST',
            ])
            ->values();
        $zonasMapa = $mapCollection->map(function ($zona) use ($municipioCentros) {
            $fallback = $municipioCentros[$zona->municipio] ?? $municipioCentros['Puerto Barrios'];
            $metadata = $zona->poligono_geojson['metadata'] ?? [];
            $hasCoordinates = $zona->latitude_centro !== null && $zona->longitude_centro !== null;

            return [
                'id' => $zona->id,
                'nombre' => $zona->nombre,
                'municipio' => $zona->municipio,
                'slug' => $zona->slug,
                'descripcion' => $zona->descripcion,
                'activa' => (bool) $zona->activa,
                'costo' => (float) $zona->costo_base,
                'tiempo' => (int) ($metadata['tiempo_estimado_min'] ?? 45),
                'capacidad' => (int) ($metadata['capacidad_diaria'] ?? 60),
                'envioGratisDesde' => $metadata['envio_gratis_desde'] ?? null,
                'barrios' => $metadata['barrios'] ?? [],
                'latitude' => (float) ($zona->latitude_centro ?? $fallback['latitude']),
                'longitude' => (float) ($zona->longitude_centro ?? $fallback['longitude']),
                'hasCoordinates' => $hasCoordinates,
                'coordinateLabel' => $hasCoordinates ? 'Centro exacto' : 'Centro referencial por municipio',
                'features' => $zona->poligono_geojson['features'] ?? [],
            ];
        })->values();
    @endphp

    <section class="-mx-4 -my-6 bg-atlantia-cream/35 px-4 py-6 text-atlantia-ink sm:-mx-6 sm:px-6 lg:-mx-8 lg:px-8">
        <div class="mx-auto max-w-7xl space-y-5">
            <div class="flex flex-col gap-5 lg:flex-row lg:items-end lg:justify-between">
                <div>
                    <p class="text-xs font-black uppercase tracking-[0.18em] text-atlantia-rose">Atlantia Supermarket</p>
                    <h1 class="mt-2 text-5xl font-black leading-tight text-atlantia-ink">Zonas de entrega</h1>
                    <p class="mt-3 text-base text-atlantia-ink/70">
                        Define la cobertura operativa, costos y horarios por area geografica en Izabal.
                    </p>
                </div>

                <div class="flex flex-wrap gap-3">
                    <button
                        type="button"
                        class="inline-flex items-center justify-center gap-2 rounded-md border border-atlantia-rose/35 bg-white px-5 py-3 text-sm font-black text-atlantia-wine shadow-sm transition hover:bg-atlantia-blush"
                    >
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M12 3V15" stroke="currentColor" stroke-width="2" stroke-linecap="round"/><path d="M7 10L12 15L17 10" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><path d="M5 20H19" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
                        Exportar zonas
                    </button>
                    <button
                        type="button"
                        data-open-create-zone
                        class="inline-flex items-center justify-center gap-2 rounded-md bg-atlantia-wine px-5 py-3 text-sm font-black text-white shadow-sm transition hover:bg-atlantia-wine-700"
                    >
                        <span class="text-xl leading-none">+</span>
                        Nueva zona
                    </button>
                </div>
            </div>

            <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                <article class="rounded-xl border border-atlantia-rose/20 bg-white p-5 shadow-sm">
                    <div class="flex items-center gap-4">
                        <span class="grid h-14 w-14 place-items-center rounded-xl bg-atlantia-blush text-atlantia-wine">
                            <svg class="h-7 w-7" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M12 21C12 21 19 14.8 19 8.9C19 5.1 15.9 2 12 2C8.1 2 5 5.1 5 8.9C5 14.8 12 21 12 21Z" stroke="currentColor" stroke-width="2"/><path d="M12 11.5C13.4 11.5 14.5 10.4 14.5 9C14.5 7.6 13.4 6.5 12 6.5C10.6 6.5 9.5 7.6 9.5 9C9.5 10.4 10.6 11.5 12 11.5Z" stroke="currentColor" stroke-width="2"/></svg>
                        </span>
                        <div>
                            <p class="text-xs font-black uppercase tracking-wide text-atlantia-ink/50">Zonas activas</p>
                            <p class="mt-1 text-2xl font-black text-atlantia-ink">{{ $activeZones }}</p>
                            <p class="mt-1 text-xs font-bold text-emerald-700">Listas para checkout</p>
                        </div>
                    </div>
                </article>
                <article class="rounded-xl border border-atlantia-rose/20 bg-white p-5 shadow-sm">
                    <div class="flex items-center gap-4">
                        <span class="grid h-14 w-14 place-items-center rounded-xl bg-atlantia-blush text-atlantia-wine">
                            <svg class="h-7 w-7" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M9 18L3 21V6L9 3M9 18L15 21M9 18V3M15 21L21 18V3L15 6M15 21V6M15 6L9 3" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/></svg>
                        </span>
                        <div>
                            <p class="text-xs font-black uppercase tracking-wide text-atlantia-ink/50">Cobertura total</p>
                            <p class="mt-1 text-2xl font-black text-atlantia-ink">{{ number_format($coverage, 0) }} km2</p>
                            <p class="mt-1 text-xs font-bold text-atlantia-ink/60">Izabal</p>
                        </div>
                    </div>
                </article>
                <article class="rounded-xl border border-atlantia-rose/20 bg-white p-5 shadow-sm">
                    <div class="flex items-center gap-4">
                        <span class="grid h-14 w-14 place-items-center rounded-xl bg-atlantia-blush text-atlantia-wine">
                            <svg class="h-7 w-7" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M7 8V6C7 3.8 8.8 2 11 2H13C15.2 2 17 3.8 17 6V8" stroke="currentColor" stroke-width="2"/><path d="M5 8H19L18 21H6L5 8Z" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/></svg>
                        </span>
                        <div>
                            <p class="text-xs font-black uppercase tracking-wide text-atlantia-ink/50">Pedidos en zonas</p>
                            <p class="mt-1 text-2xl font-black text-atlantia-ink">{{ $ordersInZones }}</p>
                            <p class="mt-1 text-xs font-bold text-emerald-700">Operacion mensual</p>
                        </div>
                    </div>
                </article>
                <article class="rounded-xl border border-atlantia-rose/20 bg-white p-5 shadow-sm">
                    <div class="flex items-center gap-4">
                        <span class="grid h-14 w-14 place-items-center rounded-xl bg-atlantia-blush text-atlantia-wine">
                            <svg class="h-7 w-7" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M12 3C7 3 3 5.5 3 8.5C3 11.5 7 14 12 14C17 14 21 11.5 21 8.5C21 5.5 17 3 12 3Z" stroke="currentColor" stroke-width="2"/><path d="M3 8.5V15.5C3 18.5 7 21 12 21C17 21 21 18.5 21 15.5V8.5" stroke="currentColor" stroke-width="2"/><path d="M12 6V11" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
                        </span>
                        <div>
                            <p class="text-xs font-black uppercase tracking-wide text-atlantia-ink/50">Costo promedio envio</p>
                            <p class="mt-1 text-2xl font-black text-atlantia-ink">Q {{ number_format($averageCost, 2) }}</p>
                            <p class="mt-1 text-xs font-bold text-atlantia-ink/60">Por pedido</p>
                        </div>
                    </div>
                </article>
            </div>

            <section class="rounded-xl border border-atlantia-rose/20 bg-white p-5 shadow-sm">
                <div class="grid gap-5 lg:grid-cols-[1fr_auto] lg:items-center">
                    <div class="flex gap-4">
                        <span class="grid h-16 w-16 shrink-0 place-items-center rounded-full bg-atlantia-wine text-white">
                            <svg class="h-8 w-8" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M4 6H20L18 18H7L4 6Z" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/><path d="M9 20C9.6 20 10 19.6 10 19C10 18.4 9.6 18 9 18C8.4 18 8 18.4 8 19C8 19.6 8.4 20 9 20Z" stroke="currentColor" stroke-width="2"/><path d="M17 20C17.6 20 18 19.6 18 19C18 18.4 17.6 18 17 18C16.4 18 16 18.4 16 19C16 19.6 16.4 20 17 20Z" stroke="currentColor" stroke-width="2"/></svg>
                        </span>
                        <div>
                            <p class="text-xs font-black uppercase tracking-wide text-atlantia-ink/55">Cobertura de checkout</p>
                            <h2 class="mt-1 text-xl font-black text-atlantia-ink">Fase actual: Puerto Barrios y Santo Tomas</h2>
                            <p class="mt-2 max-w-3xl text-sm leading-6 text-atlantia-ink/65">
                            El cliente solo podra finalizar compras en municipios con una zona activa. Los demas municipios
                            quedan listos para administracion futura, pero no pasan checkout todavia.
                            </p>
                        </div>
                    </div>

                    <div class="grid gap-2 sm:min-w-64">
                        @foreach ($municipiosOperativos as $municipio)
                            @php
                                $activoCheckout = $municipiosActivosCheckout->contains($municipio);
                            @endphp
                            <span
                                @class([
                                    'inline-flex items-center gap-2 rounded-md px-4 py-2 text-sm font-black',
                                    'bg-emerald-100 text-emerald-800' => $activoCheckout,
                                    'bg-red-50 text-red-700' => ! $activoCheckout,
                                ])
                            >
                                <span class="grid h-5 w-5 place-items-center rounded-full bg-white/70">{{ $activoCheckout ? 'ok' : '!' }}</span>
                                {{ $municipio }}: {{ $activoCheckout ? 'Activo' : 'Pendiente' }}
                            </span>
                        @endforeach
                    </div>
                </div>

                @if ($municipiosPendientesCheckout->isNotEmpty())
                    <div class="mt-4 rounded-md border border-red-100 bg-red-50 px-4 py-3 text-sm font-bold text-red-700">
                        Falta crear o activar zona para:
                        {{ $municipiosPendientesCheckout->join(', ', ' y ') }}.
                    </div>
                @endif
            </section>

            <div class="space-y-6">
                <form
                    id="crear-zona"
                    method="POST"
                    action="{{ route('admin.zonas-entrega.store') }}"
                    data-create-zone-panel
                    class="{{ $errors->any() ? '' : 'hidden' }} rounded-lg border border-atlantia-rose/20 bg-white shadow-sm"
                >
                    @csrf

                    <div class="flex items-center justify-between gap-4 border-b border-atlantia-rose/15 px-6 py-5">
                        <div>
                            <h2 class="text-2xl font-black text-atlantia-wine">Crear zona de entrega</h2>
                            <p class="mt-1 text-sm font-bold uppercase tracking-normal text-atlantia-ink/45">Borrador</p>
                        </div>
                        <button
                            type="button"
                            data-close-create-zone
                            class="rounded-md border border-atlantia-rose/35 bg-white px-3 py-2 text-xs font-black text-atlantia-wine hover:bg-atlantia-blush"
                        >
                            Ocultar
                        </button>
                    </div>

                    <div class="grid gap-5 p-6 lg:grid-cols-2">
                        <div>
                            <label class="text-sm font-black text-atlantia-ink">Nombre de la zona</label>
                            <input
                                name="nombre"
                                type="text"
                                value="{{ old('nombre') }}"
                                placeholder="Puerto Barrios Centro"
                                class="mt-2 w-full rounded-md border border-atlantia-rose/30 px-4 py-3 text-sm
                                    focus:border-atlantia-wine focus:ring-atlantia-rose"
                                required
                            >
                            <p class="mt-1 text-xs text-atlantia-ink/55">Nombre visible en checkout para el cliente.</p>
                            @error('nombre')
                                <p class="mt-2 rounded-md bg-red-50 px-3 py-2 text-sm font-bold text-red-700">
                                    {{ $message }}
                                </p>
                            @enderror
                        </div>

                        <div>
                            <label class="text-sm font-black text-atlantia-ink">
                                Codigo interno <span class="font-normal text-atlantia-ink/45">(auto-generado)</span>
                            </label>
                            <div class="mt-2 flex overflow-hidden rounded-md border border-atlantia-rose/30">
                                <span class="bg-atlantia-blush px-4 py-3 text-sm font-black text-atlantia-wine">ZONA-</span>
                                <input
                                    name="slug"
                                    type="text"
                                    value="{{ old('slug') }}"
                                    placeholder="pb-centro"
                                    class="w-full border-0 px-4 py-3 text-sm focus:ring-0"
                                >
                            </div>
                            <p class="mt-1 text-xs text-atlantia-ink/55">Usado en reportes y facturacion operativa.</p>
                            @error('slug')
                                <p class="mt-2 rounded-md bg-red-50 px-3 py-2 text-sm font-bold text-red-700">
                                    {{ $message }}
                                </p>
                            @enderror
                        </div>

                        <div>
                            <label class="text-sm font-black text-atlantia-ink">Descripcion</label>
                            <textarea
                                name="descripcion"
                                rows="3"
                                placeholder="Centro historico y comercial. Incluye barrios 1, 2 y zona del muelle."
                                class="mt-2 w-full rounded-md border border-atlantia-rose/30 px-4 py-3 text-sm
                                    focus:border-atlantia-wine focus:ring-atlantia-rose"
                            >{{ old('descripcion') }}</textarea>
                        </div>

                        <div>
                            <label class="text-sm font-black text-atlantia-ink">Municipio principal</label>
                            <select
                                name="municipio"
                                class="mt-2 w-full rounded-md border border-atlantia-rose/30 px-4 py-3 text-sm
                                    focus:border-atlantia-wine focus:ring-atlantia-rose"
                                required
                            >
                                @foreach ($municipios as $municipio)
                                    <option value="{{ $municipio }}" @selected(old('municipio') === $municipio)>
                                        {{ $municipio }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="text-sm font-black text-atlantia-ink">Colonias / barrios cubiertos</label>
                            <input
                                name="barrios"
                                type="text"
                                value="{{ old('barrios') }}"
                                placeholder="Barrio 1, Barrio 2, Zona del Muelle, San Manuel"
                                class="mt-2 w-full rounded-md border border-atlantia-rose/30 px-4 py-3 text-sm
                                    focus:border-atlantia-wine focus:ring-atlantia-rose"
                            >
                            <p class="mt-1 text-xs text-atlantia-ink/55">Separa cada barrio con coma.</p>
                        </div>

                        <div class="grid gap-4 md:grid-cols-2">
                            <label>
                                <span class="text-sm font-black text-atlantia-ink">Costo base de envio</span>
                                <div class="mt-2 flex overflow-hidden rounded-md border border-atlantia-rose/30">
                                    <span class="bg-atlantia-blush px-4 py-3 text-sm font-black text-atlantia-wine">Q</span>
                                    <input
                                        name="costo_base"
                                        type="number"
                                        step="0.01"
                                        min="0"
                                        value="{{ old('costo_base', '15.00') }}"
                                        class="w-full border-0 px-4 py-3 text-sm focus:ring-0"
                                        required
                                    >
                                </div>
                            </label>
                            <label>
                                <span class="text-sm font-black text-atlantia-ink">Envio gratis desde</span>
                                <div class="mt-2 flex overflow-hidden rounded-md border border-atlantia-rose/30">
                                    <span class="bg-atlantia-blush px-4 py-3 text-sm font-black text-atlantia-wine">Q</span>
                                    <input
                                        name="envio_gratis_desde"
                                        type="number"
                                        step="0.01"
                                        min="0"
                                        value="{{ old('envio_gratis_desde', '250.00') }}"
                                        class="w-full border-0 px-4 py-3 text-sm focus:ring-0"
                                    >
                                </div>
                            </label>
                        </div>

                        <div class="grid gap-4 md:grid-cols-2">
                            <label>
                                <span class="text-sm font-black text-atlantia-ink">Tiempo estimado</span>
                                <div class="mt-2 flex overflow-hidden rounded-md border border-atlantia-rose/30">
                                    <span class="bg-atlantia-blush px-4 py-3 text-sm font-black text-atlantia-wine">min</span>
                                    <input
                                        name="tiempo_estimado_min"
                                        type="number"
                                        min="10"
                                        max="240"
                                        value="{{ old('tiempo_estimado_min', 45) }}"
                                        class="w-full border-0 px-4 py-3 text-sm focus:ring-0"
                                    >
                                </div>
                            </label>
                            <label>
                                <span class="text-sm font-black text-atlantia-ink">Capacidad diaria</span>
                                <div class="mt-2 flex overflow-hidden rounded-md border border-atlantia-rose/30">
                                    <span class="bg-atlantia-blush px-4 py-3 text-sm font-black text-atlantia-wine">#</span>
                                    <input
                                        name="capacidad_diaria"
                                        type="number"
                                        min="1"
                                        value="{{ old('capacidad_diaria', 80) }}"
                                        class="w-full border-0 px-4 py-3 text-sm focus:ring-0"
                                    >
                                </div>
                            </label>
                        </div>

                        <div>
                            <p class="text-sm font-black text-atlantia-ink">Dias de operacion</p>
                            <div class="mt-2 grid grid-cols-4 gap-2 sm:grid-cols-7">
                                @foreach ($dias as $value => $label)
                                    <label class="checkout-window">
                                        <input
                                            type="checkbox"
                                            name="dias_operacion[]"
                                            value="{{ $value }}"
                                            @checked(in_array($value, old('dias_operacion', ['lun', 'mar', 'mie', 'jue', 'vie', 'sab']), true))
                                            class="sr-only"
                                        >
                                        {{ $label }}
                                    </label>
                                @endforeach
                            </div>
                        </div>

                        <div class="grid gap-4 md:grid-cols-2">
                            <label>
                                <span class="text-sm font-black text-atlantia-ink">Apertura</span>
                                <input
                                    name="hora_apertura"
                                    type="time"
                                    value="{{ old('hora_apertura', '08:00') }}"
                                    class="mt-2 w-full rounded-md border border-atlantia-rose/30 px-4 py-3 text-sm
                                        focus:border-atlantia-wine focus:ring-atlantia-rose"
                                >
                            </label>
                            <label>
                                <span class="text-sm font-black text-atlantia-ink">Cierre</span>
                                <input
                                    name="hora_cierre"
                                    type="time"
                                    value="{{ old('hora_cierre', '20:00') }}"
                                    class="mt-2 w-full rounded-md border border-atlantia-rose/30 px-4 py-3 text-sm
                                        focus:border-atlantia-wine focus:ring-atlantia-rose"
                                >
                            </label>
                        </div>

                        <div class="rounded-lg border border-atlantia-rose/25 bg-atlantia-cream/60 p-4 lg:col-span-2">
                            <div class="flex items-start gap-3">
                                <span class="grid h-10 w-10 shrink-0 place-items-center rounded-full bg-atlantia-blush text-atlantia-wine">
                                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M12 21C12 21 19 14.8 19 8.9C19 5.1 15.9 2 12 2C8.1 2 5 5.1 5 8.9C5 14.8 12 21 12 21Z" stroke="currentColor" stroke-width="2"/><path d="M12 11.5C13.4 11.5 14.5 10.4 14.5 9C14.5 7.6 13.4 6.5 12 6.5C10.6 6.5 9.5 7.6 9.5 9C9.5 10.4 10.6 11.5 12 11.5Z" stroke="currentColor" stroke-width="2"/></svg>
                                </span>
                                <div>
                                    <p class="text-sm font-black text-atlantia-wine">Cobertura definida por colonia o barrio</p>
                                    <p class="mt-1 text-xs leading-5 text-atlantia-ink/60">
                                        No necesitas dibujar un mapa. Describe claramente el sector cubierto; al entregar, el repartidor usara la direccion y la ubicacion compartida por el cliente.
                                    </p>
                                </div>
                            </div>
                        </div>

                        <div class="space-y-4 border-t border-slate-200 pt-5">
                            <label class="flex items-center justify-between gap-4">
                                <span>
                                    <span class="block font-black text-atlantia-ink">Zona activa</span>
                                    <span class="text-sm text-atlantia-ink/60">Se mostrara en el checkout de clientes.</span>
                                </span>
                                <input type="checkbox" name="activa" value="1" checked class="h-5 w-5 rounded border-atlantia-rose text-atlantia-wine">
                            </label>
                            <label class="flex items-center justify-between gap-4">
                                <span>
                                    <span class="block font-black text-atlantia-ink">Aceptar pedidos programados</span>
                                    <span class="text-sm text-atlantia-ink/60">Permite elegir fecha y hora futuras.</span>
                                </span>
                                <input type="checkbox" name="acepta_programados" value="1" checked class="h-5 w-5 rounded border-atlantia-rose text-atlantia-wine">
                            </label>
                            <label class="flex items-center justify-between gap-4">
                                <span>
                                    <span class="block font-black text-atlantia-ink">Cobro por peso / volumen</span>
                                    <span class="text-sm text-atlantia-ink/60">Costo variable segun tamano del pedido.</span>
                                </span>
                                <input type="checkbox" name="cobro_peso_volumen" value="1" class="h-5 w-5 rounded border-atlantia-rose text-atlantia-wine">
                            </label>
                        </div>

                        <div class="flex flex-col gap-3 border-t border-slate-200 pt-5 sm:flex-row sm:justify-end lg:col-span-2">
                            <button
                                type="reset"
                                class="rounded-md border border-atlantia-rose/35 px-5 py-3 text-sm font-black
                                    text-atlantia-ink hover:bg-atlantia-blush"
                            >
                                Cancelar
                            </button>
                            <button
                                type="submit"
                                class="rounded-md bg-atlantia-wine px-5 py-3 text-sm font-black text-white
                                    hover:bg-atlantia-wine-700"
                            >
                                Crear zona
                            </button>
                        </div>
                    </div>
                </form>

                <section class="grid gap-5 lg:grid-cols-2">
                    <div class="order-2 rounded-xl border border-atlantia-rose/20 bg-white p-5 shadow-sm lg:col-span-2">
                        <div class="grid gap-4 lg:grid-cols-[1fr_auto] lg:items-center">
                            <div>
                                <h2 class="text-xl font-black text-atlantia-ink">
                                    Zonas registradas ({{ $zonas->total() }})
                                </h2>
                                <p class="mt-1 text-sm text-atlantia-ink/60">Cobertura operativa actual.</p>
                            </div>
                            <form method="GET" action="{{ route('admin.zonas-entrega.index') }}" class="grid gap-2 sm:grid-cols-[minmax(220px,320px)_auto]">
                                <label class="relative block">
                                    <span class="pointer-events-none absolute left-4 top-1/2 -translate-y-1/2 text-atlantia-ink/40">
                                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M11 19C15.4 19 19 15.4 19 11C19 6.6 15.4 3 11 3C6.6 3 3 6.6 3 11C3 15.4 6.6 19 11 19Z" stroke="currentColor" stroke-width="2"/><path d="M20.5 20.5L16.7 16.7" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
                                    </span>
                                    <input
                                        name="q"
                                        type="search"
                                        value="{{ request('q') }}"
                                        placeholder="Buscar zona..."
                                        class="w-full rounded-md border border-atlantia-rose/30 py-3 pl-12 pr-4 text-sm outline-none focus:border-atlantia-wine focus:ring-2 focus:ring-atlantia-rose/20"
                                    >
                                </label>
                                <button type="submit" class="inline-flex items-center justify-center gap-2 rounded-md border border-atlantia-rose/30 bg-white px-4 py-3 text-sm font-black text-atlantia-wine hover:bg-atlantia-blush">
                                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M4 6H20M7 12H17M10 18H14" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
                                    Filtrar
                                </button>
                            </form>
                        </div>

                        <div class="mt-5 grid gap-5">
                            @forelse ($zonas as $zona)
                                @php
                                    $metadata = $zona->poligono_geojson['metadata'] ?? [];
                                    $barrios = $metadata['barrios'] ?? [];
                                    $capacity = (int) ($metadata['capacidad_diaria'] ?? 60);
                                    $ordersToday = 0;
                                    $time = (int) ($metadata['tiempo_estimado_min'] ?? 45);
                                @endphp

                                <article
                                    id="zone-card-{{ $zona->id }}"
                                    @class([
                                        'flex h-full flex-col rounded-xl border bg-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:border-atlantia-rose/45 hover:shadow-md sm:p-6',
                                        'border-atlantia-rose/30' => $loop->first,
                                        'border-slate-200' => ! $loop->first,
                                    ])
                                >
                                    <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                                        <div class="flex min-w-0 items-center gap-4">
                                            <span class="grid h-14 w-14 shrink-0 place-items-center rounded-full bg-atlantia-wine text-white shadow-sm">
                                                <svg class="h-7 w-7" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M12 21C12 21 19 14.8 19 8.9C19 5.1 15.9 2 12 2C8.1 2 5 5.1 5 8.9C5 14.8 12 21 12 21Z" fill="currentColor"/><path d="M12 11.5C13.4 11.5 14.5 10.4 14.5 9C14.5 7.6 13.4 6.5 12 6.5C10.6 6.5 9.5 7.6 9.5 9C9.5 10.4 10.6 11.5 12 11.5Z" fill="white"/></svg>
                                            </span>
                                            <div class="min-w-0">
                                                <h3 class="text-xl font-black leading-tight text-atlantia-ink">{{ $zona->nombre }}</h3>
                                                <p class="mt-1 break-all text-xs font-bold uppercase tracking-wide text-atlantia-ink/45">
                                                    ZONA-{{ \Illuminate\Support\Str::upper($zona->slug) }}
                                                </p>
                                            </div>
                                        </div>

                                        <span
                                            @class([
                                                'inline-flex shrink-0 items-center gap-1.5 rounded-md border px-2.5 py-1.5 text-xs font-black uppercase',
                                                'border-emerald-200 bg-emerald-50 text-emerald-700' => $zona->activa,
                                                'bg-slate-100 text-slate-600' => ! $zona->activa,
                                            ])
                                        >
                                            @if ($zona->activa)
                                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M20 6L9 17L4 12" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                            @endif
                                            {{ $zona->activa ? 'Activa' : 'Inactiva' }}
                                        </span>
                                    </div>

                                    <div class="my-5 border-t border-slate-200"></div>

                                    <p class="text-sm leading-6 text-atlantia-ink/70">
                                        {{ $zona->descripcion ?: 'Zona de entrega disponible para las colonias y sectores registrados por administracion.' }}
                                    </p>

                                    <div class="mt-4 space-y-2">
                                        @forelse (array_slice($barrios, 0, 3) as $barrio)
                                            <div class="flex items-start gap-2 rounded-lg border border-atlantia-rose/20 bg-atlantia-cream/50 px-3 py-2.5">
                                                <span class="mt-0.5 grid h-5 w-5 shrink-0 place-items-center rounded-full bg-atlantia-wine text-[11px] font-black text-white">i</span>
                                                <span class="text-xs font-bold leading-5 text-atlantia-wine">
                                                    {{ $barrio }}
                                                </span>
                                            </div>
                                        @empty
                                            <div class="flex items-start gap-2 rounded-lg border border-atlantia-rose/20 bg-atlantia-cream/50 px-3 py-2.5">
                                                <span class="mt-0.5 grid h-5 w-5 shrink-0 place-items-center rounded-full bg-atlantia-wine text-[11px] font-black text-white">i</span>
                                                <span class="text-xs font-bold leading-5 text-atlantia-wine">Sin colonias o barrios detallados.</span>
                                            </div>
                                        @endforelse
                                    </div>

                                    <dl class="mt-5 grid grid-cols-2 border-y border-slate-200 py-4 sm:grid-cols-4">
                                        <div class="border-r border-slate-200 px-2 text-center">
                                            <dt class="text-[11px] font-black uppercase tracking-wide text-atlantia-ink/45">Envio</dt>
                                            <dd class="mt-2">
                                                <svg class="mx-auto h-5 w-5 text-atlantia-wine" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M4 7H20V18H4V7Z" stroke="currentColor" stroke-width="2"/><path d="M16 11H20V15H16C14.9 15 14 14.1 14 13C14 11.9 14.9 11 16 11Z" stroke="currentColor" stroke-width="2"/></svg>
                                                <span class="mt-1 block text-sm font-black text-atlantia-ink">Q {{ number_format((float) $zona->costo_base, 2) }}</span>
                                            </dd>
                                        </div>
                                        <div class="px-2 text-center sm:border-r sm:border-slate-200">
                                            <dt class="text-[11px] font-black uppercase tracking-wide text-atlantia-ink/45">Tiempo</dt>
                                            <dd class="mt-2">
                                                <svg class="mx-auto h-5 w-5 text-atlantia-wine" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M12 21C17 21 21 17 21 12C21 7 17 3 12 3C7 3 3 7 3 12C3 17 7 21 12 21Z" stroke="currentColor" stroke-width="2"/><path d="M12 7V12L15 15" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
                                                <span class="mt-1 block text-sm font-black text-atlantia-ink">{{ $time }} min</span>
                                            </dd>
                                        </div>
                                        <div class="mt-4 border-r border-slate-200 px-2 text-center sm:mt-0">
                                            <dt class="text-[11px] font-black uppercase tracking-wide text-atlantia-ink/45">Municipio</dt>
                                            <dd class="mt-2">
                                                <svg class="mx-auto h-5 w-5 text-atlantia-wine" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M4 6L9 3L15 6L20 3V18L15 21L9 18L4 21V6Z" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/><path d="M9 3V18M15 6V21" stroke="currentColor" stroke-width="2"/><path d="M15 10C15 12 12 14 12 14C12 14 9 12 9 10C9 8.3 10.3 7 12 7C13.7 7 15 8.3 15 10Z" fill="white" stroke="currentColor"/></svg>
                                                <span class="mt-1 block truncate text-sm font-black text-atlantia-ink">{{ $zona->municipio }}</span>
                                            </dd>
                                        </div>
                                        <div class="mt-4 px-2 text-center sm:mt-0">
                                            <dt class="text-[11px] font-black uppercase tracking-wide text-atlantia-ink/45">Pedidos hoy</dt>
                                            <dd class="mt-2">
                                                <svg class="mx-auto h-5 w-5 text-atlantia-wine" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M7 8V6C7 3.8 8.8 2 11 2H13C15.2 2 17 3.8 17 6V8" stroke="currentColor" stroke-width="2"/><path d="M5 8H19L18 21H6L5 8Z" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/></svg>
                                                <span class="mt-1 block text-sm font-black text-atlantia-ink">{{ $ordersToday }} / {{ $capacity }}</span>
                                            </dd>
                                        </div>
                                    </dl>

                                    <div class="mt-auto flex flex-wrap gap-2 pt-5">
                                        <details class="group">
                                            <summary
                                                class="inline-flex cursor-pointer list-none items-center gap-2 rounded-md border border-atlantia-rose/45 bg-white px-4 py-2.5 text-xs font-black text-atlantia-wine transition hover:bg-atlantia-blush [&::-webkit-details-marker]:hidden"
                                            >
                                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M12 20H21" stroke="currentColor" stroke-width="2" stroke-linecap="round"/><path d="M16.5 3.5C17.3 2.7 18.7 2.7 19.5 3.5L20.5 4.5C21.3 5.3 21.3 6.7 20.5 7.5L8 20H4V16L16.5 3.5Z" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/></svg>
                                                Editar
                                            </summary>
                                            <form
                                                method="POST"
                                                action="{{ route('admin.zonas-entrega.update', $zona) }}"
                                                class="mt-4 grid gap-4 rounded-lg border border-atlantia-rose/20 bg-white p-4"
                                            >
                                                @csrf
                                                @method('PUT')
                                                <input name="nombre" value="{{ $zona->nombre }}" class="rounded-md border border-atlantia-rose/30 px-3 py-2">
                                                <input name="slug" value="{{ $zona->slug }}" class="rounded-md border border-atlantia-rose/30 px-3 py-2">
                                                <textarea name="descripcion" rows="2" class="rounded-md border border-atlantia-rose/30 px-3 py-2">{{ $zona->descripcion }}</textarea>
                                                <input type="hidden" name="capacidad_diaria" value="{{ $metadata['capacidad_diaria'] ?? 60 }}">
                                                <input type="hidden" name="envio_gratis_desde" value="{{ $metadata['envio_gratis_desde'] ?? '' }}">
                                                <input type="hidden" name="hora_apertura" value="{{ $metadata['hora_apertura'] ?? '' }}">
                                                <input type="hidden" name="hora_cierre" value="{{ $metadata['hora_cierre'] ?? '' }}">
                                                @foreach (($metadata['dias_operacion'] ?? []) as $diaOperacion)
                                                    <input type="hidden" name="dias_operacion[]" value="{{ $diaOperacion }}">
                                                @endforeach
                                                @if ($metadata['acepta_programados'] ?? false)
                                                    <input type="hidden" name="acepta_programados" value="1">
                                                @endif
                                                @if ($metadata['cobro_peso_volumen'] ?? false)
                                                    <input type="hidden" name="cobro_peso_volumen" value="1">
                                                @endif
                                                <div class="grid gap-3 sm:grid-cols-3">
                                                    <select name="municipio" class="rounded-md border border-atlantia-rose/30 px-3 py-2">
                                                        @foreach ($municipios as $municipio)
                                                            <option value="{{ $municipio }}" @selected($zona->municipio === $municipio)>{{ $municipio }}</option>
                                                        @endforeach
                                                    </select>
                                                    <input name="costo_base" type="number" step="0.01" value="{{ $zona->costo_base }}" class="rounded-md border border-atlantia-rose/30 px-3 py-2">
                                                    <input name="tiempo_estimado_min" type="number" value="{{ $time }}" class="rounded-md border border-atlantia-rose/30 px-3 py-2">
                                                </div>
                                                <div class="grid gap-3 sm:grid-cols-2">
                                                    <input
                                                        id="edit-lat-{{ $zona->id }}"
                                                        name="latitude_centro"
                                                        type="number"
                                                        step="0.00000001"
                                                        value="{{ $zona->latitude_centro }}"
                                                        class="rounded-md border border-atlantia-rose/30 px-3 py-2"
                                                        placeholder="Latitud (auto desde poligono)"
                                                    >
                                                    <input
                                                        id="edit-lng-{{ $zona->id }}"
                                                        name="longitude_centro"
                                                        type="number"
                                                        step="0.00000001"
                                                        value="{{ $zona->longitude_centro }}"
                                                        class="rounded-md border border-atlantia-rose/30 px-3 py-2"
                                                        placeholder="Longitud (auto desde poligono)"
                                                    >
                                                </div>
                                                <input name="barrios" value="{{ implode(', ', $barrios) }}" class="rounded-md border border-atlantia-rose/30 px-3 py-2">

                                                @if ($googleMapsKey)
                                                    <input
                                                        type="hidden"
                                                        name="poligono_features"
                                                        id="edit-polygon-{{ $zona->id }}"
                                                        value="{{ json_encode($zona->poligono_geojson['features'] ?? []) }}"
                                                    >
                                                    <div class="rounded-lg border border-atlantia-wine/25 bg-atlantia-cream/60 p-3">
                                                        <div class="flex items-center justify-between gap-3">
                                                            <div>
                                                                <p class="text-xs font-black text-atlantia-wine">Area de cobertura en mapa</p>
                                                                @if (! empty($zona->poligono_geojson['features']))
                                                                    <p class="mt-0.5 text-xs text-emerald-700 font-bold">Poligono guardado</p>
                                                                @else
                                                                    <p class="mt-0.5 text-xs text-amber-600 font-bold">Sin poligono — dibuja el area exacta</p>
                                                                @endif
                                                            </div>
                                                            <div class="flex gap-2">
                                                                <button
                                                                    type="button"
                                                                    id="clear-polygon-{{ $zona->id }}"
                                                                    class="{{ empty($zona->poligono_geojson['features']) ? 'hidden' : '' }} rounded border border-red-200 px-2 py-1 text-xs font-black text-red-700 hover:bg-red-50"
                                                                >
                                                                    Limpiar
                                                                </button>
                                                                <button
                                                                    type="button"
                                                                    data-toggle-edit-map="{{ $zona->id }}"
                                                                    class="rounded border border-atlantia-rose/35 bg-white px-2 py-1 text-xs font-black text-atlantia-wine hover:bg-atlantia-blush"
                                                                >
                                                                    Abrir mapa
                                                                </button>
                                                            </div>
                                                        </div>
                                                        <div
                                                            id="edit-zone-map-{{ $zona->id }}"
                                                            class="mt-3 hidden overflow-hidden rounded-lg border border-atlantia-rose/25 bg-atlantia-blush"
                                                            style="height: 320px;"
                                                            data-zone-id="{{ $zona->id }}"
                                                            data-zone-lat="{{ $zona->latitude_centro }}"
                                                            data-zone-lng="{{ $zona->longitude_centro }}"
                                                        ></div>
                                                    </div>
                                                @endif

                                                <input type="hidden" name="activa" value="0">
                                                <label class="inline-flex items-center gap-2 text-sm font-bold text-atlantia-ink">
                                                    <input type="checkbox" name="activa" value="1" @checked($zona->activa)>
                                                    Activa
                                                </label>
                                                <button type="submit" class="rounded-md bg-atlantia-wine px-4 py-2 text-sm font-black text-white">
                                                    Guardar cambios
                                                </button>
                                            </form>
                                        </details>

                                        <form method="POST" action="{{ route('admin.zonas-entrega.destroy', $zona) }}">
                                            @csrf
                                            @method('DELETE')
                                            <button
                                                type="submit"
                                                class="inline-flex items-center gap-2 rounded-md border border-red-200 px-4 py-2.5 text-xs font-black text-red-700 transition hover:bg-red-50"
                                                onclick="return confirm('Eliminar esta zona de entrega?');"
                                            >
                                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M4 7H20" stroke="currentColor" stroke-width="2" stroke-linecap="round"/><path d="M9 7V4H15V7M18 7L17 20H7L6 7" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/><path d="M10 11V16M14 11V16" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
                                                Eliminar
                                            </button>
                                        </form>
                                    </div>
                                </article>
                            @empty
                                <div class="rounded-lg border border-atlantia-rose/20 bg-atlantia-cream px-4 py-8 text-center text-atlantia-ink/60 lg:col-span-2">
                                    No hay zonas registradas.
                                </div>
                            @endforelse
                        </div>

                        <div class="mt-5">{{ $zonas->links() }}</div>
                    </div>

                    <section class="order-1 rounded-xl border border-atlantia-rose/20 bg-white p-5 shadow-sm lg:col-span-2">
                        <div class="grid gap-4 lg:grid-cols-[1fr_auto] lg:items-start">
                            <div>
                                <p class="text-xs font-black uppercase tracking-wide text-atlantia-wine">Cobertura geografica</p>
                                <h2 class="mt-1 text-xl font-black text-atlantia-ink">Mapa de cobertura</h2>
                                <p class="mt-1 text-sm text-atlantia-ink/60">Revisa Puerto Barrios, Santo Tomas y los centros exactos de cada zona.</p>
                            </div>
                            <span class="rounded-full bg-atlantia-blush px-4 py-2 text-xs font-black text-atlantia-wine">{{ $zonasMapa->count() }} zonas visibles</span>
                        </div>

                        <div class="mt-4 rounded-xl border border-atlantia-rose/25 bg-atlantia-cream/35 p-4">
                            <div class="flex flex-col gap-3 lg:flex-row lg:items-end lg:justify-between">
                                <div>
                                    <label for="delivery-zone-search" class="text-sm font-black text-atlantia-ink">
                                        Buscar zona guardada
                                    </label>
                                    <p class="mt-1 text-xs text-atlantia-ink/60">
                                        Escribe el nombre, colonia o codigo interno para localizar su cobertura.
                                    </p>
                                </div>
                                <button
                                    type="button"
                                    id="delivery-zone-search-clear"
                                    class="hidden rounded-md border border-atlantia-rose/30 bg-white px-4 py-2 text-xs font-black text-atlantia-wine transition hover:bg-atlantia-blush"
                                >
                                    Limpiar busqueda
                                </button>
                            </div>

                            <div class="relative mt-3">
                                <span class="pointer-events-none absolute left-4 top-1/2 -translate-y-1/2 text-atlantia-wine/60">
                                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M11 19C15.4 19 19 15.4 19 11C19 6.6 15.4 3 11 3C6.6 3 3 6.6 3 11C3 15.4 6.6 19 11 19Z" stroke="currentColor" stroke-width="2"/><path d="M20.5 20.5L16.7 16.7" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
                                </span>
                                <input
                                    id="delivery-zone-search"
                                    type="search"
                                    autocomplete="off"
                                    placeholder="Ej. BANVI I o stc-banvi-i"
                                    class="w-full rounded-md border border-atlantia-rose/35 bg-white py-3 pl-12 pr-4 text-sm font-bold text-atlantia-ink outline-none transition placeholder:font-normal placeholder:text-atlantia-ink/35 focus:border-atlantia-wine focus:ring-2 focus:ring-atlantia-rose/20"
                                >
                            </div>

                            <div id="delivery-zone-search-results" class="mt-2 hidden overflow-hidden rounded-lg border border-atlantia-rose/25 bg-white shadow-sm"></div>

                            <article id="delivery-zone-selected" class="mt-3 hidden rounded-lg border border-atlantia-rose/25 bg-white p-4 shadow-sm">
                                <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                                    <div class="min-w-0">
                                        <div class="flex flex-wrap items-center gap-2">
                                            <h3 data-selected-zone-name class="text-base font-black text-atlantia-ink"></h3>
                                            <span data-selected-zone-status class="rounded-md px-2 py-1 text-xs font-black uppercase"></span>
                                        </div>
                                        <p data-selected-zone-code class="mt-1 text-xs font-black uppercase tracking-wide text-atlantia-rose"></p>
                                        <p data-selected-zone-description class="mt-2 text-sm text-atlantia-ink/65"></p>
                                    </div>
                                    <button
                                        type="button"
                                        id="delivery-zone-edit-selected"
                                        class="inline-flex shrink-0 items-center justify-center gap-2 rounded-md bg-atlantia-wine px-4 py-2.5 text-sm font-black text-white shadow-sm transition hover:bg-atlantia-wine-700"
                                    >
                                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M12 20H21" stroke="currentColor" stroke-width="2" stroke-linecap="round"/><path d="M16.5 3.5C17.3 2.7 18.7 2.7 19.5 3.5L20.5 4.5C21.3 5.3 21.3 6.7 20.5 7.5L8 20H4V16L16.5 3.5Z" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/></svg>
                                        Editar poligono
                                    </button>
                                </div>
                                <dl class="mt-4 grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                                    <div class="rounded-lg bg-atlantia-cream/55 p-3">
                                        <dt class="text-xs font-black uppercase text-atlantia-ink/45">Municipio</dt>
                                        <dd data-selected-zone-municipality class="mt-1 text-sm font-black text-atlantia-ink"></dd>
                                    </div>
                                    <div class="rounded-lg bg-atlantia-cream/55 p-3">
                                        <dt class="text-xs font-black uppercase text-atlantia-ink/45">Costo base</dt>
                                        <dd data-selected-zone-cost class="mt-1 text-sm font-black text-atlantia-ink"></dd>
                                    </div>
                                    <div class="rounded-lg bg-atlantia-cream/55 p-3">
                                        <dt class="text-xs font-black uppercase text-atlantia-ink/45">Tiempo estimado</dt>
                                        <dd data-selected-zone-time class="mt-1 text-sm font-black text-atlantia-ink"></dd>
                                    </div>
                                    <div class="rounded-lg bg-atlantia-cream/55 p-3">
                                        <dt class="text-xs font-black uppercase text-atlantia-ink/45">Cobertura</dt>
                                        <dd data-selected-zone-areas class="mt-1 text-sm font-black text-atlantia-ink"></dd>
                                    </div>
                                </dl>
                            </article>
                        </div>

                        <div class="mt-4 overflow-hidden rounded-xl border border-atlantia-rose/25 bg-atlantia-blush/35">
                            <div class="flex items-center justify-between gap-4 bg-white px-4 py-3 text-sm font-black text-atlantia-ink shadow-sm">
                                <span class="inline-flex items-center gap-2">
                                    <svg class="h-4 w-4 text-atlantia-wine" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M9 18L3 21V6L9 3M9 18L15 21M9 18V3M15 21L21 18V3L15 6M15 21V6M15 6L9 3" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/></svg>
                                    Mapa de cobertura
                                </span>
                                <span class="text-xs font-bold text-atlantia-ink/55">Izabal, Guatemala</span>
                            </div>

                            <div
                                id="coverage-map-panel"
                                data-coverage-map-panel
                                class="relative rounded-b-lg border-t border-atlantia-rose/25 bg-atlantia-blush"
                                style="display: block; height: 340px; min-height: 340px; width: 100%; overflow: hidden;"
                            >
                                @if ($googleMapsKey)
                                    <div
                                        id="delivery-zones-map"
                                        class="w-full bg-atlantia-blush"
                                        style="display: block; height: 340px; min-height: 340px; width: 100%;"
                                    ></div>
                                @endif

                                <div
                                    id="delivery-zones-map-status"
                                    class="absolute inset-0 z-20 flex items-center justify-center bg-atlantia-blush text-center"
                                    style="min-height: 340px;"
                                >
                                    <div class="rounded-lg bg-white/95 px-5 py-4 shadow-lg">
                                        @if ($googleMapsKey)
                                            <p class="text-sm font-black text-atlantia-wine">Cargando mapa de cobertura...</p>
                                            <p class="mt-1 text-xs text-atlantia-ink/60">Puerto Barrios y Santo Tomas de Castilla</p>
                                        @else
                                            <p class="text-sm font-black text-red-700">Google Maps no esta configurado</p>
                                            <p class="mt-1 text-xs text-atlantia-ink/60">Agrega GOOGLE_MAPS_API_KEY en .env y limpia cache.</p>
                                        @endif
                                    </div>
                                </div>

                                <div class="pointer-events-none absolute left-4 top-4 z-10 rounded-lg bg-white/95 p-3 shadow-lg backdrop-blur">
                                    <p class="text-xs font-black uppercase tracking-normal text-atlantia-wine">
                                        {{ $zonasMapa->count() }} zonas visibles
                                    </p>
                                    <p class="mt-1 text-xs text-atlantia-ink/60">
                                        Puerto Barrios, Santo Tomas y zonas creadas.
                                    </p>
                                </div>
                                <div class="pointer-events-none absolute right-4 top-4 z-10 rounded-lg bg-white/95 px-3 py-2 text-xs font-bold text-atlantia-ink/60 shadow-lg">Izabal, Guatemala</div>
                            </div>
                        </div>
                    </section>
                </section>
            </div>
        </div>
    </section>

    <script @nonce>
        (() => {
            const openButton = document.querySelector('[data-open-create-zone]');
            const closeButton = document.querySelector('[data-close-create-zone]');
            const panel = document.querySelector('[data-create-zone-panel]');

            openButton?.addEventListener('click', () => {
                panel?.classList.remove('hidden');
                panel?.scrollIntoView({ behavior: 'smooth', block: 'start' });
            });

            closeButton?.addEventListener('click', () => {
                panel?.classList.add('hidden');
            });

            window.setTimeout(() => {
                const status = document.getElementById('delivery-zones-map-status');

                if (! status || status.classList.contains('hidden') || window.google?.maps) {
                    return;
                }

                status.innerHTML = `
                    <div class="rounded-lg bg-white/95 px-5 py-4 shadow-lg">
                        <p class="text-sm font-black text-red-700">Google Maps no cargo</p>
                        <p class="mt-1 text-xs text-atlantia-ink/60">Revisa la API key, la facturacion y que Maps JavaScript API este habilitada.</p>
                    </div>
                `;
            }, 9000);
        })();
    </script>

    @if ($googleMapsKey)
        <script @nonce>
            window.setAtlantiaDeliveryMapStatus = (message, detail = '', isError = false) => {
                const status = document.getElementById('delivery-zones-map-status');

                if (! status) {
                    return;
                }

                status.classList.remove('hidden');
                status.innerHTML = `
                    <div class="rounded-lg bg-white/95 px-5 py-4 shadow-lg">
                        <p class="text-sm font-black ${isError ? 'text-red-700' : 'text-atlantia-wine'}">${message}</p>
                        ${detail ? `<p class="mt-1 text-xs text-atlantia-ink/60">${detail}</p>` : ''}
                    </div>
                `;
            };

            window.gm_authFailure = () => {
                window.setAtlantiaDeliveryMapStatus(
                    'Google Maps no pudo cargar',
                    'Revisa que GOOGLE_MAPS_API_KEY sea valida, que Maps JavaScript API este habilitada y que el dominio local este permitido.',
                    true
                );
            };

            window.initAtlantiaDeliveryMaps = () => {
                const zones = @json($zonasMapa);
                const operationalCenters = @json($municipiosMapaOperativos);
                const defaultCenter = {
                    lat: Number(@json($defaultMapCenter['latitude'])),
                    lng: Number(@json($defaultMapCenter['longitude'])),
                };
                const defaultZoom = Number(@json($defaultMapCenter['zoom']));
                const googleMapId = @json($googleMapsMapId);
                const mapElement = document.getElementById('delivery-zones-map');
                const mapStatusElement = document.getElementById('delivery-zones-map-status');
                const zoneSearchInput = document.getElementById('delivery-zone-search');
                const zoneSearchResults = document.getElementById('delivery-zone-search-results');
                const zoneSearchClear = document.getElementById('delivery-zone-search-clear');
                const selectedZonePanel = document.getElementById('delivery-zone-selected');
                const selectedZoneEditButton = document.getElementById('delivery-zone-edit-selected');
                const shouldRenderCoverageMap = Boolean(mapElement);

                if (! window.google?.maps) {
                    window.setAtlantiaDeliveryMapStatus(
                        'Google Maps no esta disponible',
                        'Vuelve a cargar la pagina o revisa la configuracion de la API.',
                        true
                    );

                    return;
                }


                const zoneColors = [
                    { fill: '#7a1f3d', stroke: '#5a0f2d' },
                    { fill: '#0891b2', stroke: '#065f7a' },
                    { fill: '#d97706', stroke: '#a35505' },
                    { fill: '#059669', stroke: '#047554' },
                    { fill: '#7c3aed', stroke: '#5b21b6' },
                    { fill: '#dc2626', stroke: '#b91c1c' },
                    { fill: '#2563eb', stroke: '#1d4ed8' },
                    { fill: '#db2777', stroke: '#be185d' },
                    { fill: '#ea580c', stroke: '#c2410c' },
                    { fill: '#4f46e5', stroke: '#4338ca' },
                ];

                const getZoneColor = (index) => zoneColors[index % zoneColors.length];

                const initialCenter = defaultCenter;

                let map = null;
                let selectedZone = null;
                const zonePolygons = window.atlantiaCoverageZonePolygons || new Map();
                const zoneMarkers = window.atlantiaCoverageZoneMarkers || new Map();
                const zoneInfoWindows = window.atlantiaCoverageZoneInfoWindows || new Map();
                window.atlantiaCoverageZonePolygons = zonePolygons;
                window.atlantiaCoverageZoneMarkers = zoneMarkers;
                window.atlantiaCoverageZoneInfoWindows = zoneInfoWindows;

                const hideMapStatus = () => {
                    mapStatusElement?.classList.add('hidden');
                };
                const reuseExistingCoverageMap = shouldRenderCoverageMap
                    && window.atlantiaCoverageMapInitialized
                    && window.atlantiaCoverageMap;

                if (reuseExistingCoverageMap) {
                    map = window.atlantiaCoverageMap;
                    google.maps.event.trigger(map, 'resize');
                } else if (shouldRenderCoverageMap) {
                    const mapOptions = {
                        center: initialCenter,
                        zoom: defaultZoom,
                        mapTypeId: 'roadmap',
                        mapTypeControl: true,
                        streetViewControl: false,
                        fullscreenControl: true,
                    };

                    if (googleMapId) {
                        mapOptions.mapId = googleMapId;
                    }

                    map = new google.maps.Map(mapElement, mapOptions);

                    google.maps.event.addListenerOnce(map, 'idle', hideMapStatus);
                    google.maps.event.addListenerOnce(map, 'tilesloaded', hideMapStatus);

                    window.setTimeout(() => {
                        if (! mapStatusElement || mapStatusElement.classList.contains('hidden')) {
                            return;
                        }

                        window.setAtlantiaDeliveryMapStatus(
                            'El mapa esta tardando en cargar',
                            'Si el fondo no aparece, revisa restricciones de la API o conexion de red.',
                            true
                        );
                    }, 8000);

                    window.atlantiaCoverageMap = map;
                    window.atlantiaCoverageMapInitialized = true;
                }

                const normalizeSearch = (value) => String(value || '')
                    .normalize('NFD')
                    .replace(/[\u0300-\u036f]/g, '')
                    .toLowerCase()
                    .trim();

                const setSelectedText = (selector, text) => {
                    const element = selectedZonePanel?.querySelector(selector);

                    if (element) {
                        element.textContent = text;
                    }
                };

                const rememberPolygon = (zoneId, polygon) => {
                    const key = String(zoneId);
                    const polygons = zonePolygons.get(key) || [];
                    polygons.push(polygon);
                    zonePolygons.set(key, polygons);
                };

                const renderSelectedZone = (zone) => {
                    selectedZonePanel?.classList.remove('hidden');
                    zoneSearchClear?.classList.remove('hidden');
                    setSelectedText('[data-selected-zone-name]', zone.nombre);
                    setSelectedText('[data-selected-zone-code]', `Codigo interno: ${zone.slug}`);
                    setSelectedText(
                        '[data-selected-zone-description]',
                        zone.descripcion || 'Zona operativa guardada sin descripcion adicional.'
                    );
                    setSelectedText('[data-selected-zone-municipality]', zone.municipio);
                    setSelectedText('[data-selected-zone-cost]', `Q ${Number(zone.costo).toFixed(2)}`);
                    setSelectedText('[data-selected-zone-time]', `${zone.tiempo} min`);
                    setSelectedText(
                        '[data-selected-zone-areas]',
                        zone.barrios.length ? zone.barrios.join(', ') : 'Poligono de cobertura'
                    );

                    const status = selectedZonePanel?.querySelector('[data-selected-zone-status]');
                    if (status) {
                        status.textContent = zone.activa ? 'Activa' : 'Inactiva';
                        status.className = zone.activa
                            ? 'rounded-md bg-emerald-100 px-2 py-1 text-xs font-black uppercase text-emerald-800'
                            : 'rounded-md bg-slate-100 px-2 py-1 text-xs font-black uppercase text-slate-600';
                    }
                };

                const fitZoneArea = (zone) => {
                    if (! map) {
                        return;
                    }

                    const polygons = zonePolygons.get(String(zone.id)) || [];
                    const bounds = new google.maps.LatLngBounds();
                    let hasPolygonCoordinates = false;

                    polygons.forEach((polygon) => {
                        polygon.getPath().forEach((point) => {
                            bounds.extend(point);
                            hasPolygonCoordinates = true;
                        });
                    });

                    if (hasPolygonCoordinates) {
                        map.fitBounds(bounds, 76);
                    } else {
                        map.panTo({ lat: Number(zone.latitude), lng: Number(zone.longitude) });
                        map.setZoom(15);
                    }

                    const marker = zoneMarkers.get(String(zone.id));
                    const infoWindow = zoneInfoWindows.get(String(zone.id));
                    if (marker && infoWindow) {
                        infoWindow.open({ anchor: marker, map });
                    }
                };

                const selectZone = (zone) => {
                    selectedZone = zone;
                    if (zoneSearchInput) {
                        zoneSearchInput.value = zone.nombre;
                    }
                    zoneSearchResults?.classList.add('hidden');
                    renderSelectedZone(zone);
                    fitZoneArea(zone);
                };

                const matchingZones = (query) => {
                    const normalizedQuery = normalizeSearch(query);

                    if (! normalizedQuery) {
                        return [];
                    }

                    return zones.filter((zone) => normalizeSearch([
                        zone.nombre,
                        zone.slug,
                        zone.municipio,
                        zone.descripcion,
                        ...(zone.barrios || []),
                    ].join(' ')).includes(normalizedQuery));
                };

                const renderSearchResults = (matches, query) => {
                    if (! zoneSearchResults) {
                        return;
                    }

                    zoneSearchResults.replaceChildren();
                    zoneSearchResults.classList.remove('hidden');

                    if (matches.length === 0) {
                        const emptyState = document.createElement('p');
                        emptyState.className = 'px-4 py-3 text-sm font-bold text-atlantia-ink/65';
                        emptyState.textContent = 'No se encontró ninguna zona con ese nombre';
                        zoneSearchResults.appendChild(emptyState);
                        return;
                    }

                    matches.slice(0, 7).forEach((zone) => {
                        const button = document.createElement('button');
                        button.type = 'button';
                        button.className = 'flex w-full items-center justify-between gap-3 border-b border-atlantia-rose/15 px-4 py-3 text-left transition last:border-b-0 hover:bg-atlantia-blush/60';

                        const summary = document.createElement('span');
                        summary.className = 'min-w-0';

                        const name = document.createElement('span');
                        name.className = 'block truncate text-sm font-black text-atlantia-ink';
                        name.textContent = zone.nombre;

                        const detail = document.createElement('span');
                        detail.className = 'mt-0.5 block truncate text-xs font-bold text-atlantia-ink/50';
                        detail.textContent = `${zone.slug} · ${zone.municipio}`;

                        const status = document.createElement('span');
                        status.className = zone.activa
                            ? 'shrink-0 rounded-md bg-emerald-100 px-2 py-1 text-xs font-black uppercase text-emerald-800'
                            : 'shrink-0 rounded-md bg-slate-100 px-2 py-1 text-xs font-black uppercase text-slate-600';
                        status.textContent = zone.activa ? 'Activa' : 'Inactiva';

                        summary.append(name, detail);
                        button.append(summary, status);
                        button.addEventListener('click', () => selectZone(zone));
                        zoneSearchResults.appendChild(button);
                    });
                };

                const initZoneSearch = () => {
                    if (! zoneSearchInput) {
                        return;
                    }

                    zoneSearchInput.addEventListener('input', () => {
                        const query = zoneSearchInput.value.trim();
                        selectedZone = null;
                        selectedZonePanel?.classList.add('hidden');
                        zoneSearchClear?.classList.toggle('hidden', ! query);

                        if (! query) {
                            zoneSearchResults?.classList.add('hidden');
                            return;
                        }

                        renderSearchResults(matchingZones(query), query);
                    });

                    zoneSearchInput.addEventListener('keydown', (event) => {
                        if (event.key !== 'Enter') {
                            return;
                        }

                        event.preventDefault();
                        const firstMatch = matchingZones(zoneSearchInput.value)[0];
                        if (firstMatch) {
                            selectZone(firstMatch);
                        } else {
                            renderSearchResults([], zoneSearchInput.value);
                        }
                    });

                    zoneSearchClear?.addEventListener('click', () => {
                        selectedZone = null;
                        zoneSearchInput.value = '';
                        zoneSearchClear.classList.add('hidden');
                        zoneSearchResults?.classList.add('hidden');
                        selectedZonePanel?.classList.add('hidden');
                        fitCoverageArea();
                        zoneSearchInput.focus();
                    });

                    selectedZoneEditButton?.addEventListener('click', () => {
                        if (! selectedZone) {
                            return;
                        }

                        const zoneCard = document.getElementById(`zone-card-${selectedZone.id}`);
                        if (! zoneCard) {
                            const url = new URL(@json(route('admin.zonas-entrega.index')), window.location.origin);
                            url.searchParams.set('q', selectedZone.slug);
                            url.hash = `zone-card-${selectedZone.id}`;
                            window.location.assign(url.toString());
                            return;
                        }

                        const details = zoneCard.querySelector('details');
                        if (details) {
                            details.open = true;
                        }

                        window.requestAnimationFrame(() => {
                            const editMapButton = zoneCard.querySelector(`[data-toggle-edit-map="${selectedZone.id}"]`);
                            const editMap = document.getElementById(`edit-zone-map-${selectedZone.id}`);

                            if (editMapButton && editMap?.classList.contains('hidden')) {
                                editMapButton.click();
                            }

                            zoneCard.scrollIntoView({ behavior: 'smooth', block: 'center' });
                        });
                    });
                };

                const addPolygons = () => {
                    zones.forEach((zone, zoneIndex) => {
                        const color = getZoneColor(zoneIndex);

                        (zone.features || []).forEach((feature) => {
                            const geometry = feature.geometry || {};

                            if (geometry.type !== 'Polygon') {
                                return;
                            }

                            const paths = (geometry.coordinates?.[0] || []).map((point) => ({
                                lat: Number(point[1]),
                                lng: Number(point[0]),
                            }));

                            if (paths.length < 3) {
                                return;
                            }

                            const polygon = new google.maps.Polygon({
                                paths,
                                strokeColor: color.stroke,
                                strokeOpacity: 0.9,
                                strokeWeight: 2.5,
                                fillColor: color.fill,
                                fillOpacity: 0.22,
                                map,
                            });
                            rememberPolygon(zone.id, polygon);

                            const infoWindow = new google.maps.InfoWindow({
                                content: `
                                    <div style="padding:4px 0">
                                        <strong>${zone.nombre}</strong><br>
                                        ${zone.municipio}<br>
                                        Envio: Q ${Number(zone.costo).toFixed(2)} &middot; ${zone.tiempo} min
                                        ${zone.barrios.length ? '<br><small>' + zone.barrios.join(', ') + '</small>' : ''}
                                    </div>
                                `,
                            });

                            polygon.addListener('click', (event) => {
                                infoWindow.setPosition(event.latLng);
                                infoWindow.open(map);
                            });
                        });
                    });
                };

                const fitCoverageArea = () => {
                    if (! map) {
                        return;
                    }

                    const bounds = new google.maps.LatLngBounds();
                    operationalCenters.forEach((center) => bounds.extend({
                        lat: Number(center.latitude),
                        lng: Number(center.longitude),
                    }));
                    zones.forEach((zone) => bounds.extend({
                        lat: Number(zone.latitude),
                        lng: Number(zone.longitude),
                    }));
                    map.fitBounds(bounds, 70);
                };

                const calculateCentroid = (coordinates) => {
                    const n = coordinates.length - 1;
                    let latSum = 0;
                    let lngSum = 0;
                    for (let i = 0; i < n; i++) {
                        lngSum += coordinates[i][0];
                        latSum += coordinates[i][1];
                    }
                    return { lat: latSum / n, lng: lngSum / n };
                };

                const polygonToGeoJsonFeature = (polygon) => {
                    const path = polygon.getPath().getArray();
                    const coordinates = path.map((pt) => [pt.lng(), pt.lat()]);
                    coordinates.push(coordinates[0]);
                    return {
                        type: 'Feature',
                        geometry: { type: 'Polygon', coordinates: [coordinates] },
                        properties: {},
                    };
                };

                const initDrawingMap = (mapEl, featuresInput, latInput, lngInput, existingFeatures, initialCenter, zoneColor) => {
                    const color = zoneColor || { fill: '#7a1f3d', stroke: '#5a0f2d' };
                    const center = initialCenter || defaultCenter;

                    const dmapOptions = {
                        center,
                        zoom: 15,
                        mapTypeId: 'hybrid',
                        streetViewControl: false,
                        mapTypeControl: false,
                        fullscreenControl: false,
                    };

                    if (googleMapId) dmapOptions.mapId = googleMapId;

                    const dmap = new google.maps.Map(mapEl, dmapOptions);
                    let currentPolygon = null;

                    const setPolygonData = (polygon) => {
                        const feature = polygonToGeoJsonFeature(polygon);
                        const centroid = calculateCentroid(feature.geometry.coordinates[0]);
                        if (latInput) latInput.value = centroid.lat.toFixed(8);
                        if (lngInput) lngInput.value = centroid.lng.toFixed(8);
                        if (featuresInput) featuresInput.value = JSON.stringify([feature]);
                    };

                    const attachEditListeners = (polygon) => {
                        polygon.getPath().addListener('set_at', () => setPolygonData(polygon));
                        polygon.getPath().addListener('insert_at', () => setPolygonData(polygon));
                        polygon.addListener('dragend', () => setPolygonData(polygon));
                    };

                    const clearPolygon = () => {
                        if (currentPolygon) {
                            currentPolygon.setMap(null);
                            currentPolygon = null;
                        }
                        if (featuresInput) featuresInput.value = '';
                        drawingManager.setDrawingMode(google.maps.drawing.OverlayType.POLYGON);
                    };

                    if (existingFeatures && existingFeatures.length > 0) {
                        const firstFeature = existingFeatures.find((f) => f?.geometry?.type === 'Polygon');
                        if (firstFeature) {
                            const coords = firstFeature.geometry.coordinates[0] || [];
                            const paths = coords.slice(0, -1).map(([lng, lat]) => ({ lat, lng }));
                            if (paths.length >= 3) {
                                currentPolygon = new google.maps.Polygon({
                                    paths,
                                    strokeColor: color.stroke,
                                    strokeOpacity: 0.9,
                                    strokeWeight: 2.5,
                                    fillColor: color.fill,
                                    fillOpacity: 0.22,
                                    editable: true,
                                    draggable: true,
                                    map: dmap,
                                });
                                attachEditListeners(currentPolygon);
                                const bounds = new google.maps.LatLngBounds();
                                paths.forEach((p) => bounds.extend(p));
                                dmap.fitBounds(bounds, 40);
                            }
                        }
                    }

                    const drawingManager = new google.maps.drawing.DrawingManager({
                        drawingMode: currentPolygon ? null : google.maps.drawing.OverlayType.POLYGON,
                        drawingControl: true,
                        drawingControlOptions: {
                            position: google.maps.ControlPosition.TOP_CENTER,
                            drawingModes: [google.maps.drawing.OverlayType.POLYGON],
                        },
                        polygonOptions: {
                            strokeColor: color.stroke,
                            strokeOpacity: 0.9,
                            strokeWeight: 2.5,
                            fillColor: color.fill,
                            fillOpacity: 0.22,
                            editable: true,
                            draggable: true,
                        },
                    });
                    drawingManager.setMap(dmap);

                    google.maps.event.addListener(drawingManager, 'polygoncomplete', (polygon) => {
                        if (currentPolygon) currentPolygon.setMap(null);
                        currentPolygon = polygon;
                        drawingManager.setDrawingMode(null);
                        setPolygonData(polygon);
                        attachEditListeners(polygon);
                    });

                    return { map: dmap, clear: clearPolygon };
                };

                const initEditMaps = () => {
                    document.querySelectorAll('[data-toggle-edit-map]').forEach((btn) => {
                        const zoneId = btn.dataset.toggleEditMap;
                        const mapEl = document.getElementById(`edit-zone-map-${zoneId}`);
                        const featuresInput = document.getElementById(`edit-polygon-${zoneId}`);
                        const latInput = document.getElementById(`edit-lat-${zoneId}`);
                        const lngInput = document.getElementById(`edit-lng-${zoneId}`);
                        const clearBtn = document.getElementById(`clear-polygon-${zoneId}`);

                        if (! mapEl) return;

                        const zoneIndex = zones.findIndex((z) => String(z.id) === String(zoneId));
                        const color = getZoneColor(zoneIndex >= 0 ? zoneIndex : 0);

                        btn.addEventListener('click', () => {
                            const isHidden = mapEl.classList.contains('hidden');
                            mapEl.classList.toggle('hidden', ! isHidden);
                            btn.textContent = isHidden ? 'Cerrar mapa' : 'Abrir mapa';

                            if (isHidden && ! mapEl._mapInitialized) {
                                mapEl._mapInitialized = true;

                                let existingFeatures = [];
                                try {
                                    existingFeatures = featuresInput ? JSON.parse(featuresInput.value || '[]') : [];
                                } catch (e) {
                                    existingFeatures = [];
                                }

                                const zoneLat = Number(mapEl.dataset.zoneLat);
                                const zoneLng = Number(mapEl.dataset.zoneLng);
                                const zoneCenter = (zoneLat && zoneLng)
                                    ? { lat: zoneLat, lng: zoneLng }
                                    : defaultCenter;

                                const { clear } = initDrawingMap(
                                    mapEl,
                                    featuresInput,
                                    latInput,
                                    lngInput,
                                    existingFeatures,
                                    zoneCenter,
                                    color
                                );

                                clearBtn?.addEventListener('click', () => {
                                    clear();
                                    if (featuresInput) featuresInput.value = '[]';
                                    clearBtn.classList.add('hidden');
                                });

                                if (featuresInput) {
                                    featuresInput.addEventListener('change', () => {
                                        const hasPolygon = featuresInput.value && featuresInput.value !== '[]';
                                        clearBtn?.classList.toggle('hidden', ! hasPolygon);
                                    });
                                }

                                google.maps.event.trigger(mapEl._gmap || {}, 'resize');
                            }
                        });
                    });
                };

                if (! shouldRenderCoverageMap) {
                    initEditMaps();
                    initZoneSearch();
                    return;
                }

                if (reuseExistingCoverageMap) {
                    fitCoverageArea();
                    initEditMaps();
                    initZoneSearch();
                    return;
                }

                addPolygons();

                operationalCenters.forEach((center) => {
                    const marker = new google.maps.Marker({
                        position: { lat: Number(center.latitude), lng: Number(center.longitude) },
                        map,
                        title: center.nombre,
                        icon: {
                            path: google.maps.SymbolPath.CIRCLE,
                            scale: 10,
                            fillColor: '#0891b2',
                            fillOpacity: 1,
                            strokeColor: '#ffffff',
                            strokeWeight: 3,
                        },
                        label: {
                            text: center.label,
                            color: '#ffffff',
                            fontWeight: '800',
                        },
                    });

                    const infoWindow = new google.maps.InfoWindow({
                        content: `<strong>${center.nombre}</strong><br>Municipio operativo actual`,
                    });

                    marker.addListener('click', () => infoWindow.open({ anchor: marker, map }));
                });

                zones.forEach((zone, zoneIndex) => {
                    const color = getZoneColor(zoneIndex);

                    const marker = new google.maps.Marker({
                        position: { lat: Number(zone.latitude), lng: Number(zone.longitude) },
                        map,
                        title: zone.nombre,
                        icon: {
                            path: google.maps.SymbolPath.CIRCLE,
                            scale: 13,
                            fillColor: zone.activa ? color.fill : '#64748b',
                            fillOpacity: zone.hasCoordinates ? 1 : 0.65,
                            strokeColor: '#ffffff',
                            strokeWeight: 3,
                        },
                        label: {
                            text: zone.nombre.slice(0, 2).toUpperCase(),
                            color: '#ffffff',
                            fontWeight: '800',
                        },
                    });

                    const infoWindow = new google.maps.InfoWindow({
                        content: `
                            <div style="padding:4px 0">
                                <strong>${zone.nombre}</strong><br>
                                ${zone.municipio}<br>
                                Envio: Q ${Number(zone.costo).toFixed(2)}<br>
                                Tiempo: ${zone.tiempo} min<br>
                                ${zone.barrios.length ? '<small>Barrios: ' + zone.barrios.join(', ') + '</small><br>' : ''}
                                <small>${zone.coordinateLabel}</small>
                            </div>
                        `,
                    });

                    marker.addListener('click', () => infoWindow.open({ anchor: marker, map }));
                    zoneMarkers.set(String(zone.id), marker);
                    zoneInfoWindows.set(String(zone.id), infoWindow);
                });

                fitCoverageArea();
                initEditMaps();
                initZoneSearch();
            };
        </script>
        <script
            @nonce
            async
            defer
            src="https://maps.googleapis.com/maps/api/js?key={{ urlencode($googleMapsKey) }}&libraries=geometry,drawing&loading=async&callback=initAtlantiaDeliveryMaps"
        ></script>
    @endif
@endsection
