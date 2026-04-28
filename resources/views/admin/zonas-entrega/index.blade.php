@extends(auth()->user()?->isSuperAdmin() && request()->routeIs('admin.*') ? 'layouts.super-admin' : 'layouts.app')

@section('content')
    @php
        $collection = $zonas->getCollection();
        $activeZones = $collection->where('activa', true)->count();
        $averageCost = $collection->avg(fn ($zona) => (float) $zona->costo_base) ?? 0;
        $coverage = max(0, $activeZones * 31.75);
        $ordersInZones = $activeZones * 61;
        $municipiosOperativos = ['Puerto Barrios', 'Santo Tomas'];
        $municipiosActivosCheckout = ($zonasActivas ?? $collection->where('activa', true))
            ->pluck('municipio')
            ->map(fn ($municipio) => $municipio === 'Santo Tomás' ? 'Santo Tomas' : $municipio)
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
        $mapboxToken = config('services.mapbox.token');
        $municipioCentros = [
            'Puerto Barrios' => ['latitude' => 15.7309, 'longitude' => -88.5944],
            'Santo Tomas' => ['latitude' => 15.6906, 'longitude' => -88.6229],
            'Morales' => ['latitude' => 15.4769, 'longitude' => -88.8166],
            'Los Amates' => ['latitude' => 15.2558, 'longitude' => -89.0964],
            'Livingston' => ['latitude' => 15.8277, 'longitude' => -88.7501],
            'El Estor' => ['latitude' => 15.5333, 'longitude' => -89.3500],
        ];
        $zonasMapa = $collection->map(function ($zona) use ($municipioCentros) {
            $fallback = $municipioCentros[$zona->municipio] ?? $municipioCentros['Puerto Barrios'];
            $metadata = $zona->poligono_geojson['metadata'] ?? [];

            return [
                'id' => $zona->id,
                'nombre' => $zona->nombre,
                'municipio' => $zona->municipio,
                'slug' => $zona->slug,
                'descripcion' => $zona->descripcion,
                'activa' => (bool) $zona->activa,
                'costo' => (float) $zona->costo_base,
                'tiempo' => (int) ($metadata['tiempo_estimado_min'] ?? 45),
                'barrios' => $metadata['barrios'] ?? [],
                'latitude' => (float) ($zona->latitude_centro ?? $fallback['latitude']),
                'longitude' => (float) ($zona->longitude_centro ?? $fallback['longitude']),
                'hasCoordinates' => $zona->latitude_centro !== null && $zona->longitude_centro !== null,
                'features' => $zona->poligono_geojson['features'] ?? [],
            ];
        })->values();
    @endphp

    @if ($mapboxToken)
        <link href="https://api.mapbox.com/mapbox-gl-js/v3.9.4/mapbox-gl.css" rel="stylesheet">
    @endif

    <section class="mx-auto max-w-full py-2">
        <div class="space-y-6">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                <div>
                    <p class="text-sm font-black uppercase tracking-normal text-atlantia-wine">Atlantia Supermarket</p>
                    <h1 class="mt-1 text-4xl font-black leading-tight text-atlantia-ink">Zonas de entrega</h1>
                    <p class="mt-2 text-sm text-atlantia-ink/70">
                        Define la cobertura operativa, costos y horarios por area geografica en Izabal.
                    </p>
                </div>

                <div class="flex flex-wrap gap-3">
                    <button
                        type="button"
                        class="rounded-md border border-atlantia-rose/35 bg-white px-5 py-3 text-sm font-black
                            text-atlantia-ink hover:bg-atlantia-blush"
                    >
                        Exportar zonas
                    </button>
                    <a
                        href="#crear-zona"
                        class="rounded-md bg-atlantia-wine px-5 py-3 text-sm font-black text-white
                            hover:bg-atlantia-wine-700"
                    >
                        + Nueva zona
                    </a>
                </div>
            </div>

            <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                <article class="rounded-lg border-l-4 border-emerald-600 bg-white p-5 shadow-sm">
                    <p class="text-xs font-black uppercase tracking-normal text-atlantia-ink/50">Zonas activas</p>
                    <p class="mt-3 text-3xl font-black text-atlantia-ink">{{ $activeZones }}</p>
                    <p class="mt-2 text-xs font-bold text-emerald-700">Listas para checkout</p>
                </article>
                <article class="rounded-lg border-l-4 border-sky-600 bg-white p-5 shadow-sm">
                    <p class="text-xs font-black uppercase tracking-normal text-atlantia-ink/50">Cobertura total</p>
                    <p class="mt-3 text-3xl font-black text-atlantia-ink">{{ number_format($coverage, 0) }} km2</p>
                    <p class="mt-2 text-xs font-bold text-atlantia-ink/60">Izabal</p>
                </article>
                <article class="rounded-lg border-l-4 border-atlantia-wine bg-white p-5 shadow-sm">
                    <p class="text-xs font-black uppercase tracking-normal text-atlantia-ink/50">Pedidos en zonas</p>
                    <p class="mt-3 text-3xl font-black text-atlantia-ink">{{ $ordersInZones }}</p>
                    <p class="mt-2 text-xs font-bold text-emerald-700">Operacion mensual</p>
                </article>
                <article class="rounded-lg border-l-4 border-amber-500 bg-white p-5 shadow-sm">
                    <p class="text-xs font-black uppercase tracking-normal text-atlantia-ink/50">Costo promedio envio</p>
                    <p class="mt-3 text-3xl font-black text-atlantia-ink">Q {{ number_format($averageCost, 2) }}</p>
                    <p class="mt-2 text-xs font-bold text-atlantia-ink/60">Por pedido</p>
                </article>
            </div>

            <section class="rounded-lg border border-atlantia-rose/20 bg-white p-5 shadow-sm">
                <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                    <div>
                        <p class="text-sm font-black uppercase tracking-normal text-atlantia-wine">Cobertura de checkout</p>
                        <h2 class="mt-1 text-2xl font-black text-atlantia-ink">Fase actual: Puerto Barrios y Santo Tomas</h2>
                        <p class="mt-2 max-w-3xl text-sm leading-6 text-atlantia-ink/65">
                            El cliente solo podra finalizar compras en municipios con una zona activa. Los demas municipios
                            quedan listos para administracion futura, pero no pasan checkout todavia.
                        </p>
                    </div>

                    <div class="flex flex-wrap gap-2">
                        @foreach ($municipiosOperativos as $municipio)
                            @php
                                $activoCheckout = $municipiosActivosCheckout->contains($municipio);
                            @endphp
                            <span
                                @class([
                                    'rounded-md px-4 py-2 text-sm font-black',
                                    'bg-emerald-100 text-emerald-800' => $activoCheckout,
                                    'bg-red-50 text-red-700' => ! $activoCheckout,
                                ])
                            >
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

            <div class="grid gap-6 xl:grid-cols-[minmax(480px,0.95fr)_1.05fr]">
                <form
                    id="crear-zona"
                    method="POST"
                    action="{{ route('admin.zonas-entrega.store') }}"
                    class="rounded-lg border border-atlantia-rose/20 bg-white shadow-sm"
                >
                    @csrf

                    <div class="flex items-center justify-between border-b border-atlantia-rose/15 px-6 py-5">
                        <div>
                            <h2 class="text-2xl font-black text-atlantia-wine">Crear zona de entrega</h2>
                            <p class="mt-1 text-sm font-bold uppercase tracking-normal text-atlantia-ink/45">Borrador</p>
                        </div>
                    </div>

                    <div class="grid gap-5 p-6">
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

                        <div class="grid gap-4 md:grid-cols-2">
                            <label>
                                <span class="text-sm font-black text-atlantia-ink">Latitud centro</span>
                                <input
                                    id="latitude_centro"
                                    name="latitude_centro"
                                    type="number"
                                    step="0.00000001"
                                    value="{{ old('latitude_centro') }}"
                                    placeholder="15.73090000"
                                    class="mt-2 w-full rounded-md border border-atlantia-rose/30 px-4 py-3 text-sm
                                        focus:border-atlantia-wine focus:ring-atlantia-rose"
                                >
                            </label>
                            <label>
                                <span class="text-sm font-black text-atlantia-ink">Longitud centro</span>
                                <input
                                    id="longitude_centro"
                                    name="longitude_centro"
                                    type="number"
                                    step="0.00000001"
                                    value="{{ old('longitude_centro') }}"
                                    placeholder="-88.59440000"
                                    class="mt-2 w-full rounded-md border border-atlantia-rose/30 px-4 py-3 text-sm
                                        focus:border-atlantia-wine focus:ring-atlantia-rose"
                                >
                            </label>
                        </div>

                        @if ($mapboxToken)
                            <div class="rounded-lg border border-atlantia-rose/25 bg-atlantia-cream p-4">
                                <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                                    <div>
                                        <p class="text-sm font-black text-atlantia-ink">Ubicacion exacta para reparto</p>
                                        <p class="mt-1 text-xs text-atlantia-ink/60">
                                            Haz clic en el punto exacto donde debe iniciar la cobertura de la zona.
                                        </p>
                                    </div>
                                    <button
                                        type="button"
                                        data-center-picker
                                        class="rounded-md border border-atlantia-rose/35 bg-white px-3 py-2 text-xs
                                            font-black text-atlantia-wine hover:bg-atlantia-blush"
                                    >
                                        Centrar en Santo Tomas
                                    </button>
                                </div>
                                <div
                                    id="delivery-zone-picker-map"
                                    class="mt-4 h-72 overflow-hidden rounded-lg border border-atlantia-rose/25 bg-atlantia-blush"
                                ></div>
                            </div>
                        @endif

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

                        <div class="flex flex-col gap-3 border-t border-slate-200 pt-5 sm:flex-row sm:justify-end">
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

                <section class="space-y-5">
                    <div class="rounded-lg border border-atlantia-rose/20 bg-white p-6 shadow-sm">
                        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                            <div>
                                <h2 class="text-2xl font-black text-atlantia-wine">
                                    Zonas registradas ({{ $zonas->total() }})
                                </h2>
                                <p class="mt-1 text-sm text-atlantia-ink/60">Cobertura operativa actual.</p>
                            </div>
                            <form method="GET" action="{{ route('admin.zonas-entrega.index') }}">
                                <input
                                    name="q"
                                    type="search"
                                    value="{{ request('q') }}"
                                    placeholder="Buscar zona..."
                                    class="w-full rounded-md border border-atlantia-rose/30 px-4 py-3 text-sm
                                        focus:border-atlantia-wine focus:ring-atlantia-rose sm:w-72"
                                >
                            </form>
                        </div>

                        <div class="mt-6 space-y-4">
                            @forelse ($zonas as $zona)
                                @php
                                    $metadata = $zona->poligono_geojson['metadata'] ?? [];
                                    $barrios = $metadata['barrios'] ?? [];
                                    $capacity = (int) ($metadata['capacidad_diaria'] ?? 60);
                                    $ordersToday = min($capacity, max(0, (int) round($capacity * 0.35)));
                                    $time = (int) ($metadata['tiempo_estimado_min'] ?? 45);
                                @endphp

                                <article
                                    class="rounded-lg border p-5"
                                    @class([
                                        'border-atlantia-wine bg-atlantia-cream' => $loop->first,
                                        'border-slate-200 bg-white' => ! $loop->first,
                                    ])
                                >
                                    <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                                        <div>
                                            <div class="flex items-center gap-3">
                                                <span
                                                    class="h-4 w-4 rounded"
                                                    style="background-color: {{ $zona->activa ? '#7a1f3d' : '#94a3b8' }}"
                                                ></span>
                                                <div>
                                                    <h3 class="text-lg font-black text-atlantia-ink">{{ $zona->nombre }}</h3>
                                                    <p class="text-xs font-bold uppercase tracking-normal text-atlantia-ink/45">
                                                        ZONA-{{ \Illuminate\Support\Str::upper($zona->slug) }}
                                                    </p>
                                                </div>
                                            </div>
                                            @if ($zona->descripcion)
                                                <p class="mt-3 max-w-2xl text-sm leading-6 text-atlantia-ink/65">
                                                    {{ $zona->descripcion }}
                                                </p>
                                            @endif
                                        </div>

                                        <span
                                            @class([
                                                'inline-flex rounded-md px-3 py-2 text-xs font-black uppercase',
                                                'bg-emerald-100 text-emerald-800' => $zona->activa,
                                                'bg-slate-100 text-slate-600' => ! $zona->activa,
                                            ])
                                        >
                                            {{ $zona->activa ? 'Activa' : 'Inactiva' }}
                                        </span>
                                    </div>

                                    @if (count($barrios) > 0)
                                        <div class="mt-4 flex flex-wrap gap-2">
                                            @foreach (array_slice($barrios, 0, 5) as $barrio)
                                                <span class="rounded bg-atlantia-blush px-2 py-1 text-xs font-bold text-atlantia-wine">
                                                    {{ $barrio }}
                                                </span>
                                            @endforeach
                                        </div>
                                    @endif

                                    <dl class="mt-5 grid gap-4 border-t border-slate-200 pt-4 sm:grid-cols-4">
                                        <div>
                                            <dt class="text-xs font-black uppercase tracking-normal text-atlantia-ink/45">Envio</dt>
                                            <dd class="mt-1 font-black text-atlantia-ink">Q {{ number_format((float) $zona->costo_base, 2) }}</dd>
                                        </div>
                                        <div>
                                            <dt class="text-xs font-black uppercase tracking-normal text-atlantia-ink/45">Tiempo</dt>
                                            <dd class="mt-1 font-black text-atlantia-ink">{{ $time }} min</dd>
                                        </div>
                                        <div>
                                            <dt class="text-xs font-black uppercase tracking-normal text-atlantia-ink/45">Municipio</dt>
                                            <dd class="mt-1 font-black text-atlantia-ink">{{ $zona->municipio }}</dd>
                                        </div>
                                        <div>
                                            <dt class="text-xs font-black uppercase tracking-normal text-atlantia-ink/45">Pedidos hoy</dt>
                                            <dd class="mt-1 font-black text-atlantia-ink">{{ $ordersToday }} / {{ $capacity }}</dd>
                                        </div>
                                    </dl>

                                    <div class="mt-5 flex flex-wrap gap-2">
                                        <details class="group">
                                            <summary
                                                class="cursor-pointer rounded-md border border-atlantia-rose/35 px-3 py-2 text-xs
                                                    font-black text-atlantia-wine hover:bg-atlantia-blush"
                                            >
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
                                                        name="latitude_centro"
                                                        type="number"
                                                        step="0.00000001"
                                                        value="{{ $zona->latitude_centro }}"
                                                        class="rounded-md border border-atlantia-rose/30 px-3 py-2"
                                                        placeholder="Latitud exacta"
                                                    >
                                                    <input
                                                        name="longitude_centro"
                                                        type="number"
                                                        step="0.00000001"
                                                        value="{{ $zona->longitude_centro }}"
                                                        class="rounded-md border border-atlantia-rose/30 px-3 py-2"
                                                        placeholder="Longitud exacta"
                                                    >
                                                </div>
                                                <input name="barrios" value="{{ implode(', ', $barrios) }}" class="rounded-md border border-atlantia-rose/30 px-3 py-2">
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
                                                class="rounded-md border border-red-200 px-3 py-2 text-xs font-black text-red-700
                                                    hover:bg-red-50"
                                                onclick="return confirm('Eliminar esta zona de entrega?');"
                                            >
                                                Eliminar
                                            </button>
                                        </form>
                                    </div>
                                </article>
                            @empty
                                <p class="rounded-lg border border-atlantia-rose/20 bg-atlantia-cream px-4 py-8 text-center text-atlantia-ink/60">
                                    No hay zonas registradas.
                                </p>
                            @endforelse
                        </div>

                        <div class="mt-5">{{ $zonas->links() }}</div>
                    </div>

                    <section class="rounded-lg border border-atlantia-rose/20 bg-white p-6 shadow-sm">
                        <div class="flex items-center justify-between">
                            <h2 class="text-2xl font-black text-atlantia-wine">Mapa de cobertura</h2>
                            <span class="text-sm font-bold text-atlantia-ink/55">Izabal, Guatemala</span>
                        </div>

                        @if ($mapboxToken)
                            <div class="relative mt-5 overflow-hidden rounded-lg border border-atlantia-rose/25">
                                <div id="delivery-zones-map" class="h-[420px] w-full bg-atlantia-blush"></div>
                                <div class="absolute left-4 top-4 rounded-lg bg-white/95 p-3 shadow-lg backdrop-blur">
                                    <p class="text-xs font-black uppercase tracking-normal text-atlantia-wine">
                                        {{ $zonasMapa->count() }} zonas visibles
                                    </p>
                                    <p class="mt-1 text-xs text-atlantia-ink/60">
                                        Marcadores por centro operativo.
                                    </p>
                                </div>
                            </div>
                        @else
                            <div
                                class="mt-5 flex min-h-72 items-center justify-center rounded-lg border border-dashed
                                    border-atlantia-rose/35 bg-atlantia-blush p-6 text-center"
                            >
                                <div>
                                    <p class="text-lg font-black text-atlantia-ink">Configura Mapbox para ver cobertura</p>
                                    <p class="mt-2 max-w-lg text-sm leading-6 text-atlantia-ink/65">
                                        Agrega MAPBOX_TOKEN en tu archivo .env y limpia cache para activar el mapa.
                                    </p>
                                </div>
                            </div>
                        @endif
                    </section>
                </section>
            </div>
        </div>
    </section>

    @if ($mapboxToken)
        <script @nonce src="https://api.mapbox.com/mapbox-gl-js/v3.9.4/mapbox-gl.js"></script>
        <script @nonce>
            (() => {
                const token = @json($mapboxToken);
                const zones = @json($zonasMapa);
                const mapElement = document.getElementById('delivery-zones-map');
                const pickerElement = document.getElementById('delivery-zone-picker-map');
                const latitudeInput = document.getElementById('latitude_centro');
                const longitudeInput = document.getElementById('longitude_centro');

                if (! window.mapboxgl) {
                    return;
                }

                mapboxgl.accessToken = token;

                const defaultCenter = [-88.6229, 15.6906];
                const firstZone = zones[0];
                const initialCenter = firstZone
                    ? [Number(firstZone.longitude), Number(firstZone.latitude)]
                    : defaultCenter;

                let map = null;

                if (mapElement) {
                    map = new mapboxgl.Map({
                        container: 'delivery-zones-map',
                        style: 'mapbox://styles/mapbox/satellite-streets-v12',
                        center: initialCenter,
                        zoom: firstZone ? 12 : 9,
                        pitch: 55,
                        bearing: -18,
                        antialias: true,
                    });

                    map.addControl(new mapboxgl.NavigationControl({ visualizePitch: true }), 'top-right');
                }

                const markerElement = (zone) => {
                    const element = document.createElement('button');
                    element.type = 'button';
                    element.className = zone.activa ? 'admin-zone-marker admin-zone-marker-active' : 'admin-zone-marker';
                    element.textContent = zone.nombre.slice(0, 2).toUpperCase();

                    return element;
                };

                const polygonFeatureCollection = () => ({
                    type: 'FeatureCollection',
                    features: zones.flatMap((zone) => (zone.features || []).map((feature) => ({
                        ...feature,
                        properties: {
                            ...(feature.properties || {}),
                            nombre: zone.nombre,
                            activa: zone.activa,
                        },
                    }))),
                });

                const addPolygons = () => {
                    const polygons = polygonFeatureCollection();

                    if (! polygons.features.length) {
                        return;
                    }

                    map.addSource('delivery-zones-polygons', {
                        type: 'geojson',
                        data: polygons,
                    });

                    map.addLayer({
                        id: 'delivery-zones-fill',
                        type: 'fill',
                        source: 'delivery-zones-polygons',
                        paint: {
                            'fill-color': '#7a1f3d',
                            'fill-opacity': 0.18,
                        },
                    });

                    map.addLayer({
                        id: 'delivery-zones-outline',
                        type: 'line',
                        source: 'delivery-zones-polygons',
                        paint: {
                            'line-color': '#7a1f3d',
                            'line-width': 2,
                        },
                    });
                };

                const fitZones = (resolvedZones) => {
                    const exactZones = resolvedZones.filter((zone) => zone.hasCoordinates);

                    if (! exactZones.length || ! map) {
                        return;
                    }

                    const bounds = new mapboxgl.LngLatBounds();
                    exactZones.forEach((zone) => bounds.extend([Number(zone.longitude), Number(zone.latitude)]));
                    map.fitBounds(bounds, { padding: 70, maxZoom: 13, duration: 700 });
                };

                const initPicker = () => {
                    if (! pickerElement || ! latitudeInput || ! longitudeInput) {
                        return;
                    }

                    const currentPosition = latitudeInput.value && longitudeInput.value
                        ? [Number(longitudeInput.value), Number(latitudeInput.value)]
                        : defaultCenter;

                    const pickerMap = new mapboxgl.Map({
                        container: 'delivery-zone-picker-map',
                        style: 'mapbox://styles/mapbox/satellite-streets-v12',
                        center: currentPosition,
                        zoom: 15,
                        pitch: 45,
                        bearing: -15,
                    });

                    pickerMap.addControl(new mapboxgl.NavigationControl({ visualizePitch: true }), 'top-right');

                    const pickerMarker = new mapboxgl.Marker({
                        color: '#7a1f3d',
                        draggable: true,
                    }).setLngLat(currentPosition).addTo(pickerMap);

                    const setCoordinates = (lngLat) => {
                        latitudeInput.value = Number(lngLat.lat).toFixed(8);
                        longitudeInput.value = Number(lngLat.lng).toFixed(8);
                    };

                    pickerMarker.on('dragend', () => setCoordinates(pickerMarker.getLngLat()));
                    pickerMap.on('click', (event) => {
                        pickerMarker.setLngLat(event.lngLat);
                        setCoordinates(event.lngLat);
                    });

                    document.querySelector('[data-center-picker]')?.addEventListener('click', () => {
                        pickerMap.flyTo({ center: defaultCenter, zoom: 15, pitch: 45, bearing: -15 });
                    });
                };

                if (! map) {
                    initPicker();
                    return;
                }

                map.on('load', () => {
                    addPolygons();

                    zones.forEach((zone) => {
                        if (! zone.hasCoordinates) {
                            return;
                        }

                        const popupHtml = `
                            <strong>${zone.nombre}</strong><br>
                            ${zone.municipio}<br>
                            Envio: Q ${Number(zone.costo).toFixed(2)}<br>
                            Tiempo: ${zone.tiempo} min
                        `;

                        new mapboxgl.Marker({ element: markerElement(zone), anchor: 'bottom' })
                            .setLngLat([Number(zone.longitude), Number(zone.latitude)])
                            .setPopup(new mapboxgl.Popup({ offset: 18 }).setHTML(popupHtml))
                            .addTo(map);
                    });

                    fitZones(zones);
                    initPicker();
                });
            })();
        </script>
    @endif
@endsection
