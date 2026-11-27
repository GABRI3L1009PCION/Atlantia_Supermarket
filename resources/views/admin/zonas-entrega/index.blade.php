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

    <section class="mx-auto max-w-7xl pb-10 text-atlantia-ink">
        <div class="space-y-4">
            {{-- Encabezado con el estado de cobertura del checkout --}}
            <header class="flex flex-wrap items-end justify-between gap-3">
                <div>
                    <h1 class="text-3xl font-black tracking-tight text-atlantia-ink">Zonas de entrega</h1>
                    <p class="mt-1 text-sm text-atlantia-ink/60">Dónde entregas, cuánto cuesta el envío y cuánto tarda en cada colonia de Izabal.</p>
                    <div class="mt-2 flex flex-wrap items-center gap-2 text-xs font-bold">
                        <span class="text-atlantia-ink/55">Los clientes pueden comprar en:</span>
                        @foreach ($municipiosOperativos as $municipio)
                            @php
                                $activoCheckout = $municipiosActivosCheckout->contains($municipio);
                            @endphp
                            <span @class([
                                'inline-flex items-center gap-1.5 rounded-full px-2.5 py-1',
                                'bg-emerald-50 text-emerald-800' => $activoCheckout,
                                'bg-red-50 text-red-700' => ! $activoCheckout,
                            ])>
                                <span @class(['h-1.5 w-1.5 rounded-full', 'bg-emerald-500' => $activoCheckout, 'bg-red-500' => ! $activoCheckout])></span>
                                {{ $municipio }}{{ $activoCheckout ? '' : ' (sin zona activa)' }}
                            </span>
                        @endforeach
                    </div>
                </div>
                <button
                    type="button"
                    data-open-create-zone
                    class="inline-flex items-center gap-2 rounded-xl bg-atlantia-wine px-4 py-2.5 text-sm font-black text-white transition hover:bg-atlantia-wine-700"
                >
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M12 5v14M5 12h14" stroke="currentColor" stroke-width="2.4" stroke-linecap="round"/></svg>
                    Nueva zona
                </button>
            </header>

            @if ($municipiosPendientesCheckout->isNotEmpty())
                <p class="rounded-xl border border-red-200 bg-red-50 px-4 py-2.5 text-sm font-bold text-red-700">
                    Los clientes de {{ $municipiosPendientesCheckout->join(', ', ' y ') }} no pueden terminar su compra: falta crear o activar una zona ahí.
                </p>
            @endif

            {{-- Resumen --}}
            <div class="grid grid-cols-2 gap-3 lg:grid-cols-4">
                @foreach ([
                    ['Zonas activas', number_format($activeZones), 'reciben pedidos'],
                    ['Cobertura', number_format($coverage, 0).' km²', 'área aproximada'],
                    ['Pedidos del mes', number_format($ordersInZones), 'dentro de las zonas'],
                    ['Envío promedio', 'Q '.number_format($averageCost, 2), 'por pedido'],
                ] as [$etiqueta, $valor, $nota])
                    <article class="rounded-2xl border border-atlantia-rose/15 bg-white px-4 py-3 shadow-[0_8px_20px_rgba(59,6,12,0.04)]">
                        <p class="text-[11px] font-black uppercase tracking-wide text-atlantia-ink/50">{{ $etiqueta }}</p>
                        <p class="mt-0.5 text-2xl font-black text-atlantia-ink">{{ $valor }}</p>
                        <p class="text-xs text-atlantia-ink/50">{{ $nota }}</p>
                    </article>
                @endforeach
            </div>

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

                @php
                    $campoZona = 'mt-1 h-9 w-full rounded-lg border border-atlantia-rose/30 bg-white px-3 text-sm outline-none focus:border-atlantia-wine focus:ring-2 focus:ring-atlantia-rose/20';
                    $etiquetaZona = 'block text-[11px] font-bold text-atlantia-ink/60';
                @endphp
                <section class="grid items-start gap-4 lg:grid-cols-[minmax(0,1.15fr)_minmax(0,1fr)]">
                    {{-- Mapa (fijo al bajar) --}}
                    <section class="overflow-hidden rounded-2xl border border-atlantia-rose/15 bg-white shadow-[0_10px_26px_rgba(59,6,12,0.05)] lg:sticky lg:top-24">
                        <div class="border-b border-atlantia-rose/10 p-3">
                            <div class="relative">
                                <span class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-atlantia-ink/40">
                                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M11 19a8 8 0 1 0 0-16 8 8 0 0 0 0 16ZM20.5 20.5l-3.8-3.8" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
                                </span>
                                <label for="delivery-zone-search" class="sr-only">Buscar zona en el mapa</label>
                                <input
                                    id="delivery-zone-search"
                                    type="search"
                                    autocomplete="off"
                                    placeholder="Buscar zona o colonia en el mapa (ej. BANVI)"
                                    class="h-10 w-full rounded-lg border border-atlantia-rose/30 bg-white pl-9 pr-24 text-sm text-atlantia-ink outline-none transition placeholder:text-atlantia-ink/40 focus:border-atlantia-wine focus:ring-2 focus:ring-atlantia-rose/20"
                                >
                                <button
                                    type="button"
                                    id="delivery-zone-search-clear"
                                    class="absolute right-1.5 top-1/2 hidden -translate-y-1/2 rounded-md px-2.5 py-1 text-xs font-black text-atlantia-wine hover:bg-atlantia-blush"
                                >
                                    Limpiar
                                </button>
                            </div>

                            <div id="delivery-zone-search-results" class="mt-2 hidden overflow-hidden rounded-lg border border-atlantia-rose/25 bg-white shadow-sm"></div>

                            <article id="delivery-zone-selected" class="mt-2 hidden rounded-xl border border-atlantia-rose/20 bg-atlantia-cream/40 p-3">
                                <div class="flex items-start justify-between gap-3">
                                    <div class="min-w-0">
                                        <div class="flex flex-wrap items-center gap-2">
                                            <h3 data-selected-zone-name class="text-sm font-black text-atlantia-ink"></h3>
                                            <span data-selected-zone-status class="rounded-md px-2 py-0.5 text-[10px] font-black uppercase"></span>
                                        </div>
                                        <p data-selected-zone-code class="text-[11px] font-bold uppercase tracking-wide text-atlantia-rose"></p>
                                        <p data-selected-zone-description class="mt-1 line-clamp-2 text-xs text-atlantia-ink/65"></p>
                                    </div>
                                    <button
                                        type="button"
                                        id="delivery-zone-edit-selected"
                                        class="shrink-0 rounded-lg bg-atlantia-wine px-3 py-1.5 text-xs font-black text-white transition hover:bg-atlantia-wine-700"
                                    >
                                        Editar área
                                    </button>
                                </div>
                                <dl class="mt-2 grid grid-cols-4 gap-2 text-center text-xs">
                                    <div><dt class="text-atlantia-ink/50">Municipio</dt><dd data-selected-zone-municipality class="font-black text-atlantia-ink"></dd></div>
                                    <div><dt class="text-atlantia-ink/50">Envío</dt><dd data-selected-zone-cost class="font-black text-atlantia-ink"></dd></div>
                                    <div><dt class="text-atlantia-ink/50">Tiempo</dt><dd data-selected-zone-time class="font-black text-atlantia-ink"></dd></div>
                                    <div><dt class="text-atlantia-ink/50">Colonias</dt><dd data-selected-zone-areas class="font-black text-atlantia-ink"></dd></div>
                                </dl>
                            </article>
                        </div>

                        <div
                            id="coverage-map-panel"
                            data-coverage-map-panel
                            class="relative bg-atlantia-blush"
                            style="display: block; height: 560px; min-height: 380px; width: 100%; overflow: hidden;"
                        >
                            @if ($googleMapsKey)
                                <div
                                    id="delivery-zones-map"
                                    class="w-full bg-atlantia-blush"
                                    style="display: block; height: 560px; min-height: 380px; width: 100%;"
                                ></div>
                            @endif

                            <div
                                id="delivery-zones-map-status"
                                class="absolute inset-0 z-20 flex items-center justify-center bg-atlantia-blush text-center"
                            >
                                <div class="rounded-lg bg-white/95 px-5 py-4 shadow-lg">
                                    @if ($googleMapsKey)
                                        <p class="text-sm font-black text-atlantia-wine">Cargando mapa…</p>
                                        <p class="mt-1 text-xs text-atlantia-ink/60">Puerto Barrios y Santo Tomás de Castilla</p>
                                    @else
                                        <p class="text-sm font-black text-red-700">Google Maps no está configurado</p>
                                        <p class="mt-1 text-xs text-atlantia-ink/60">Agrega GOOGLE_MAPS_API_KEY en .env y limpia la caché.</p>
                                    @endif
                                </div>
                            </div>

                            <div class="pointer-events-none absolute left-3 top-3 z-10 rounded-lg bg-white/95 px-3 py-1.5 text-xs font-black text-atlantia-wine shadow">
                                {{ $zonasMapa->count() }} zonas en el mapa
                            </div>
                        </div>
                    </section>

                    {{-- Lista de zonas --}}
                    <div class="overflow-hidden rounded-2xl border border-atlantia-rose/15 bg-white shadow-[0_10px_26px_rgba(59,6,12,0.05)]">
                        <form method="GET" action="{{ route('admin.zonas-entrega.index') }}" class="flex items-center gap-2 border-b border-atlantia-rose/10 p-3">
                            <p class="shrink-0 text-sm font-black text-atlantia-ink">{{ $zonas->total() }} zonas</p>
                            <input
                                name="q"
                                type="search"
                                value="{{ request('q') }}"
                                placeholder="Filtrar lista"
                                aria-label="Filtrar zonas"
                                class="h-9 min-w-0 flex-1 rounded-lg border border-atlantia-rose/30 px-3 text-sm outline-none focus:border-atlantia-wine focus:ring-2 focus:ring-atlantia-rose/20"
                            >
                            <button type="submit" class="h-9 shrink-0 rounded-lg border border-atlantia-rose/30 px-3 text-xs font-black text-atlantia-wine hover:bg-atlantia-blush">Filtrar</button>
                        </form>

                        <div class="divide-y divide-atlantia-rose/10 lg:max-h-[calc(100vh-15rem)] lg:overflow-y-auto">
                            @forelse ($zonas as $zona)
                                @php
                                    $metadata = $zona->poligono_geojson['metadata'] ?? [];
                                    $barrios = $metadata['barrios'] ?? [];
                                    $time = (int) ($metadata['tiempo_estimado_min'] ?? 45);
                                    $tienePoligono = ! empty($zona->poligono_geojson['features']);
                                @endphp

                                <article id="zone-card-{{ $zona->id }}" class="px-4 py-3 transition hover:bg-atlantia-cream/40">
                                    <div class="flex items-start gap-3">
                                        <span @class(['mt-0.5 grid h-8 w-8 shrink-0 place-items-center rounded-lg', 'bg-atlantia-wine text-white' => $zona->activa, 'bg-slate-100 text-slate-400' => ! $zona->activa])>
                                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M12 21s7-6.2 7-12.1A7 7 0 0 0 5 8.9C5 14.8 12 21 12 21Z" fill="currentColor"/><circle cx="12" cy="9" r="2.5" fill="white"/></svg>
                                        </span>
                                        <div class="min-w-0 flex-1">
                                            <p class="flex flex-wrap items-center gap-2">
                                                <span class="font-black text-atlantia-ink">{{ $zona->nombre }}</span>
                                                @unless ($zona->activa)
                                                    <span class="rounded-full bg-slate-100 px-2 py-0.5 text-[10px] font-black text-slate-600">Inactiva</span>
                                                @endunless
                                            </p>
                                            <p class="text-xs text-atlantia-ink/60">
                                                {{ $zona->municipio }} · <strong class="text-atlantia-ink">Q {{ number_format((float) $zona->costo_base, 2) }}</strong> · {{ $time }} min
                                                @if (count($barrios) > 0)
                                                    · <span title="{{ implode(', ', $barrios) }}">{{ count($barrios) }} {{ count($barrios) === 1 ? 'colonia' : 'colonias' }}</span>
                                                @endif
                                            </p>
                                        </div>
                                        <form method="POST" action="{{ route('admin.zonas-entrega.destroy', $zona) }}" class="shrink-0">
                                            @csrf
                                            @method('DELETE')
                                            <button
                                                type="submit"
                                                class="rounded-lg px-2.5 py-1.5 text-xs font-black text-atlantia-ink/50 transition hover:bg-red-50 hover:text-red-700"
                                                data-confirm="¿Eliminar la zona {{ $zona->nombre }}? Los clientes de esa área ya no podrán pedir."
                                            >
                                                Eliminar
                                            </button>
                                        </form>
                                    </div>

                                    <div class="mt-2 pl-11">
                                        <details class="group w-full">
                                            <summary class="inline-flex cursor-pointer list-none items-center gap-1.5 rounded-lg border border-atlantia-rose/30 px-3 py-1.5 text-xs font-black text-atlantia-wine transition hover:bg-atlantia-blush [&::-webkit-details-marker]:hidden">
                                                Editar
                                                <svg class="h-3 w-3 transition group-open:rotate-180" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="m6 9 6 6 6-6" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                            </summary>
                                            <form
                                                method="POST"
                                                action="{{ route('admin.zonas-entrega.update', $zona) }}"
                                                class="mt-3 grid gap-3 rounded-xl border border-atlantia-rose/20 bg-atlantia-cream/30 p-3"
                                            >
                                                @csrf
                                                @method('PUT')
                                                <div class="grid gap-2 sm:grid-cols-2">
                                                    <label class="{{ $etiquetaZona }}">Nombre<input name="nombre" value="{{ $zona->nombre }}" class="{{ $campoZona }}"></label>
                                                    <label class="{{ $etiquetaZona }}">Código interno<input name="slug" value="{{ $zona->slug }}" class="{{ $campoZona }}"></label>
                                                </div>
                                                <label class="{{ $etiquetaZona }}">Descripción<textarea name="descripcion" rows="2" class="mt-1 w-full rounded-lg border border-atlantia-rose/30 bg-white px-3 py-2 text-sm">{{ $zona->descripcion }}</textarea></label>
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
                                                <div class="grid gap-2 sm:grid-cols-3">
                                                    <label class="{{ $etiquetaZona }}">Municipio
                                                        <select name="municipio" class="{{ $campoZona }}">
                                                            @foreach ($municipios as $municipio)
                                                                <option value="{{ $municipio }}" @selected($zona->municipio === $municipio)>{{ $municipio }}</option>
                                                            @endforeach
                                                        </select>
                                                    </label>
                                                    <label class="{{ $etiquetaZona }}">Envío (Q)<input name="costo_base" type="number" step="0.01" value="{{ $zona->costo_base }}" class="{{ $campoZona }}"></label>
                                                    <label class="{{ $etiquetaZona }}">Tiempo (min)<input name="tiempo_estimado_min" type="number" value="{{ $time }}" class="{{ $campoZona }}"></label>
                                                </div>
                                                <label class="{{ $etiquetaZona }}">Colonias (separadas por coma)<input name="barrios" value="{{ implode(', ', $barrios) }}" class="{{ $campoZona }}"></label>
                                                <div class="grid gap-2 sm:grid-cols-2">
                                                    <label class="{{ $etiquetaZona }}">Latitud del centro
                                                        <input id="edit-lat-{{ $zona->id }}" name="latitude_centro" type="number" step="0.00000001" value="{{ $zona->latitude_centro }}" placeholder="Se calcula con el área" class="{{ $campoZona }}">
                                                    </label>
                                                    <label class="{{ $etiquetaZona }}">Longitud del centro
                                                        <input id="edit-lng-{{ $zona->id }}" name="longitude_centro" type="number" step="0.00000001" value="{{ $zona->longitude_centro }}" placeholder="Se calcula con el área" class="{{ $campoZona }}">
                                                    </label>
                                                </div>

                                                @if ($googleMapsKey)
                                                    <input
                                                        type="hidden"
                                                        name="poligono_features"
                                                        id="edit-polygon-{{ $zona->id }}"
                                                        value="{{ json_encode($zona->poligono_geojson['features'] ?? []) }}"
                                                    >
                                                    <div class="rounded-lg border border-atlantia-rose/20 bg-white p-3">
                                                        <div class="flex items-center justify-between gap-3">
                                                            <p class="text-xs font-bold {{ $tienePoligono ? 'text-emerald-700' : 'text-atlantia-ink/60' }}">
                                                                {{ $tienePoligono ? 'Área dibujada en el mapa' : 'Sin área dibujada: se usa el punto central y las colonias' }}
                                                            </p>
                                                            <div class="flex gap-2">
                                                                <button
                                                                    type="button"
                                                                    id="clear-polygon-{{ $zona->id }}"
                                                                    class="{{ $tienePoligono ? '' : 'hidden' }} rounded-md border border-red-200 px-2 py-1 text-xs font-black text-red-700 hover:bg-red-50"
                                                                >
                                                                    Borrar área
                                                                </button>
                                                                <button
                                                                    type="button"
                                                                    data-toggle-edit-map="{{ $zona->id }}"
                                                                    class="rounded-md border border-atlantia-rose/35 bg-white px-2 py-1 text-xs font-black text-atlantia-wine hover:bg-atlantia-blush"
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

                                                <div class="flex flex-wrap items-center justify-between gap-2">
                                                    <input type="hidden" name="activa" value="0">
                                                    <label class="inline-flex items-center gap-2 text-sm font-bold text-atlantia-ink">
                                                        <input type="checkbox" name="activa" value="1" @checked($zona->activa) class="rounded border-atlantia-rose/40 text-atlantia-wine">
                                                        Zona activa (recibe pedidos)
                                                    </label>
                                                    <button type="submit" class="rounded-lg bg-atlantia-wine px-4 py-2 text-sm font-black text-white hover:bg-atlantia-wine-700">Guardar cambios</button>
                                                </div>
                                            </form>
                                        </details>
                                    </div>

                                </article>
                            @empty
                                <div class="px-4 py-12 text-center">
                                    <p class="font-black text-atlantia-ink">{{ request('q') ? 'No hay zonas con ese filtro.' : 'Todavía no hay zonas.' }}</p>
                                    <p class="mt-1 text-sm text-atlantia-ink/55">Crea una con "Nueva zona" para que los clientes de esa área puedan comprar.</p>
                                </div>
                            @endforelse
                        </div>

                        @if ($zonas->hasPages())
                            <div class="border-t border-atlantia-rose/10 px-4 py-3">{{ $zonas->withQueryString()->links() }}</div>
                        @endif
                    </div>
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
                    { fill: '#5e0008', stroke: '#5a0f2d' },
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
                    const color = zoneColor || { fill: '#5e0008', stroke: '#5a0f2d' };
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
