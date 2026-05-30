<section
    class="rounded-lg border border-atlantia-rose/20 bg-white p-5 shadow-sm sm:p-7"
    aria-labelledby="direccion-title"
>
    <header class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
        <div>
            <h2 id="direccion-title" class="flex items-center gap-3 text-2xl font-bold text-atlantia-ink">
                <span class="flex h-9 w-9 items-center justify-center rounded-md bg-atlantia-wine text-base text-white">
                    1
                </span>
                Direccion de entrega
            </h2>
            <p class="mt-2 text-sm text-atlantia-ink/70">
                Selecciona donde quieres recibir tu pedido.
            </p>
        </div>

        <a
            href="{{ route('cliente.direcciones.index') }}"
            class="inline-flex rounded-md px-3 py-2 text-sm font-bold text-atlantia-wine hover:bg-atlantia-blush"
        >
            Administrar
        </a>
    </header>

    @if ($direcciones->isEmpty() && ! $mostrandoFormulario)
        <div class="mt-4 rounded-lg border-2 border-dashed border-atlantia-rose/30 p-6 text-center">
            <p class="text-sm font-bold text-atlantia-ink/60">No tienes direcciones guardadas.</p>
            <p class="mt-1 text-xs text-atlantia-ink/45">Agrega una antes de finalizar tu compra.</p>
            <button
                type="button"
                wire:click="abrirFormulario"
                class="mt-4 rounded-md bg-atlantia-wine px-5 py-2.5 text-sm font-black text-white hover:bg-atlantia-wine-700"
            >
                + Agregar direccion de entrega
            </button>
        </div>
    @endif

    @if (! $mostrandoFormulario && $direcciones->isNotEmpty())
        @error('direccion_id')
            <p class="mt-4 rounded-md bg-red-50 px-4 py-3 text-sm font-semibold text-red-700">
                {{ $message }}
            </p>
        @enderror

        <div class="mt-5 grid gap-3">
            @foreach ($direcciones as $direccion)
                @php
                    $coverage = $coverageStates[$direccion->id] ?? ['covered' => false, 'message' => 'Sin cobertura activa.'];
                    $icon = match($direccion->alias) {
                        'Trabajo' => '💼',
                        'Regalo' => '🎁',
                        'Otro' => '📍',
                        default => '🏠',
                    };
                @endphp
                <label
                    wire:key="checkout-direccion-{{ $direccion->id }}"
                    class="relative rounded-lg border-2 p-5 transition hover:border-atlantia-wine/60"
                    @class([
                        'cursor-pointer' => $coverage['covered'],
                        'cursor-not-allowed opacity-75' => ! $coverage['covered'],
                        'border-atlantia-wine bg-atlantia-blush' => $direccionId === $direccion->id,
                        'border-slate-200 bg-white' => $direccionId !== $direccion->id,
                    ])
                >
                    <div class="flex gap-4">
                        <input
                            type="radio"
                            name="direccion_id"
                            value="{{ $direccion->id }}"
                            wire:click="seleccionarDireccion({{ $direccion->id }})"
                            wire:loading.attr="disabled"
                            wire:target="seleccionarDireccion({{ $direccion->id }})"
                            @disabled(! $coverage['covered'])
                            @checked($direccionId === $direccion->id)
                            class="mt-1 border-atlantia-rose text-atlantia-wine focus:ring-atlantia-rose"
                        >

                        <div class="min-w-0 flex-1">
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="text-base">{{ $icon }}</span>
                                <span class="rounded bg-white px-2 py-1 text-xs font-bold uppercase text-atlantia-wine">
                                    {{ $direccion->alias }}
                                </span>
                                @if ($direccion->es_principal)
                                    <span class="rounded bg-emerald-100 px-2 py-1 text-xs font-bold text-emerald-800">
                                        Principal
                                    </span>
                                @endif
                                @if ($coverage['covered'])
                                    <span class="rounded bg-emerald-100 px-2 py-1 text-xs font-bold text-emerald-800">
                                        En zona
                                    </span>
                                @else
                                    <span class="rounded bg-red-50 px-2 py-1 text-xs font-bold text-red-700">
                                        Fuera de zona
                                    </span>
                                @endif
                            </div>
                            <p class="mt-3 font-bold text-atlantia-ink">
                                {{ $direccion->nombre_contacto ?: auth()->user()?->name }}
                            </p>
                            <p class="mt-1 text-sm leading-6 text-atlantia-ink/70">
                                {{ $direccion->direccion_linea_1 }}
                                @if ($direccion->zona_o_barrio)
                                    — {{ $direccion->zona_o_barrio }}
                                @endif
                                <br>{{ $direccion->municipio }}
                                @if ($direccion->telefono_contacto)
                                    &middot; {{ $direccion->telefono_contacto }}
                                @endif
                            </p>
                            <p
                                @class([
                                    'mt-3 rounded-md px-3 py-2 text-xs font-bold',
                                    'bg-emerald-50 text-emerald-800' => $coverage['covered'],
                                    'bg-red-50 text-red-700' => ! $coverage['covered'],
                                ])
                            >
                                {{ $coverage['message'] }}
                            </p>
                        </div>
                    </div>

                    @if (! $direccion->es_principal)
                        <button
                            type="button"
                            class="mt-3 text-sm font-semibold text-atlantia-wine hover:underline"
                            wire:click.prevent="marcarPrincipal({{ $direccion->id }})"
                            wire:loading.attr="disabled"
                            wire:target="marcarPrincipal({{ $direccion->id }})"
                        >
                            <span wire:loading.remove wire:target="marcarPrincipal({{ $direccion->id }})">Usar como principal</span>
                            <span wire:loading wire:target="marcarPrincipal({{ $direccion->id }})">Actualizando...</span>
                        </button>
                    @endif
                </label>
            @endforeach

            <button
                type="button"
                wire:click="abrirFormulario"
                class="flex min-h-20 items-center justify-center gap-2 rounded-lg border-2 border-dashed border-slate-300
                    bg-white px-4 py-5 text-center text-sm font-bold text-atlantia-ink/65 hover:border-atlantia-wine
                    hover:text-atlantia-wine transition"
            >
                <span class="text-lg">+</span>
                Enviar a otra direccion (trabajo, regalo, etc.)
            </button>
        </div>
    @endif

    {{-- Formulario inline para nueva direccion --}}
    @if ($mostrandoFormulario)
        <div class="mt-5 rounded-lg border-2 border-atlantia-wine/30 bg-atlantia-cream/40 p-5">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <h3 class="font-black text-atlantia-ink">Nueva direccion de entrega</h3>
                    <p class="mt-0.5 text-xs text-atlantia-ink/55">Se guardara en tu cuenta para uso futuro.</p>
                </div>
                <button
                    type="button"
                    wire:click="cerrarFormulario"
                    class="rounded-md border border-atlantia-rose/30 px-3 py-1.5 text-xs font-black text-atlantia-ink hover:bg-atlantia-blush"
                >
                    Cancelar
                </button>
            </div>

            {{-- Tipo de direccion --}}
            <div class="mt-5">
                <p class="text-sm font-black text-atlantia-ink">Tipo de envio</p>
                <div class="mt-2 grid grid-cols-2 gap-2 sm:grid-cols-4">
                    @foreach ([
                        ['alias' => 'Casa',    'icon' => '🏠', 'label' => 'Mi casa'],
                        ['alias' => 'Trabajo', 'icon' => '💼', 'label' => 'Trabajo'],
                        ['alias' => 'Regalo',  'icon' => '🎁', 'label' => 'Regalo'],
                        ['alias' => 'Otro',    'icon' => '📍', 'label' => 'Otro lugar'],
                    ] as $opcion)
                        <button
                            type="button"
                            wire:click="setFormAlias('{{ $opcion['alias'] }}')"
                            @class([
                                'flex flex-col items-center gap-1 rounded-lg border-2 px-3 py-3 text-sm font-bold transition',
                                'border-atlantia-wine bg-white text-atlantia-wine shadow-sm' => $formAlias === $opcion['alias'],
                                'border-slate-200 bg-white text-atlantia-ink/60 hover:border-atlantia-rose/50' => $formAlias !== $opcion['alias'],
                            ])
                        >
                            <span class="text-xl">{{ $opcion['icon'] }}</span>
                            {{ $opcion['label'] }}
                        </button>
                    @endforeach
                </div>
                @if ($formAlias === 'Regalo')
                    <p class="mt-2 rounded-md bg-amber-50 px-3 py-2 text-xs font-bold text-amber-700">
                        Escribe el nombre y telefono de quien recibe el regalo abajo.
                    </p>
                @endif
            </div>

            <div class="mt-5 grid gap-4 sm:grid-cols-2">
                {{-- Nombre de quien recibe --}}
                <div>
                    <label class="text-sm font-black text-atlantia-ink">
                        {{ $formAlias === 'Regalo' ? 'Nombre de quien recibe el regalo' : 'Nombre de contacto' }}
                    </label>
                    <input
                        type="text"
                        wire:model="formNombreContacto"
                        placeholder="{{ $formAlias === 'Regalo' ? 'Nombre del destinatario' : 'Tu nombre completo' }}"
                        class="mt-2 w-full rounded-md border border-atlantia-rose/30 px-4 py-3 text-sm focus:border-atlantia-wine focus:ring-atlantia-rose"
                    >
                    @error('formNombreContacto')
                        <p class="mt-1 text-xs font-bold text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Telefono --}}
                <div>
                    <label class="text-sm font-black text-atlantia-ink">
                        Telefono de entrega
                    </label>
                    <input
                        type="tel"
                        wire:model="formTelefono"
                        placeholder="55551234"
                        maxlength="12"
                        class="mt-2 w-full rounded-md border border-atlantia-rose/30 px-4 py-3 text-sm focus:border-atlantia-wine focus:ring-atlantia-rose"
                    >
                    @error('formTelefono')
                        <p class="mt-1 text-xs font-bold text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Municipio --}}
                <div>
                    <label class="text-sm font-black text-atlantia-ink">Municipio</label>
                    <select
                        wire:model="formMunicipio"
                        class="mt-2 w-full rounded-md border border-atlantia-rose/30 px-4 py-3 text-sm focus:border-atlantia-wine focus:ring-atlantia-rose"
                    >
                        @foreach ($municipios as $municipio)
                            <option value="{{ $municipio }}">{{ $municipio }}</option>
                        @endforeach
                    </select>
                    @error('formMunicipio')
                        <p class="mt-1 text-xs font-bold text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Zona o barrio --}}
                <div>
                    <label class="text-sm font-black text-atlantia-ink">
                        Colonia / barrio
                        <span class="font-normal text-atlantia-ink/45">(opcional)</span>
                    </label>
                    <input
                        type="text"
                        wire:model="formZonaBarrio"
                        placeholder="Colonia Quebrada Seca, Barrio 1..."
                        class="mt-2 w-full rounded-md border border-atlantia-rose/30 px-4 py-3 text-sm focus:border-atlantia-wine focus:ring-atlantia-rose"
                    >
                </div>

                {{-- Direccion completa --}}
                <div class="sm:col-span-2">
                    <label class="text-sm font-black text-atlantia-ink">Direccion completa</label>
                    <input
                        type="text"
                        wire:model="formDireccion"
                        placeholder="Calle principal, casa #12, frente al parque..."
                        class="mt-2 w-full rounded-md border border-atlantia-rose/30 px-4 py-3 text-sm focus:border-atlantia-wine focus:ring-atlantia-rose"
                    >
                    @error('formDireccion')
                        <p class="mt-1 text-xs font-bold text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Referencia --}}
                <div class="sm:col-span-2">
                    <label class="text-sm font-black text-atlantia-ink">
                        Referencia para el repartidor
                        <span class="font-normal text-atlantia-ink/45">(opcional)</span>
                    </label>
                    <input
                        type="text"
                        wire:model="formReferencia"
                        placeholder="Casa azul con portón negro, frente a la iglesia..."
                        class="mt-2 w-full rounded-md border border-atlantia-rose/30 px-4 py-3 text-sm focus:border-atlantia-wine focus:ring-atlantia-rose"
                    >
                </div>

                {{-- Ubicacion exacta --}}
                <div class="sm:col-span-2" data-checkout-location-root>
                    <label class="text-sm font-black text-atlantia-ink">
                        Ubicacion exacta
                    </label>
                    <p class="mt-1 text-xs text-atlantia-ink/55">
                        Indica el punto de entrega con tu GPS o eligiendo en el mapa.
                        @if ($formAlias === 'Regalo')
                            Para un regalo, elige la ubicacion del destinatario en el mapa.
                        @endif
                    </p>

                    {{-- Tab switcher --}}
                    <div class="mt-3 flex gap-2">
                        <button type="button" data-checkout-tab="gps"
                            class="rounded-md px-3 py-1.5 text-xs font-bold transition">
                            GPS automatico
                        </button>
                        <button type="button" data-checkout-tab="map"
                            class="rounded-md px-3 py-1.5 text-xs font-bold transition">
                            Elegir en el mapa
                        </button>
                    </div>

                    {{-- GPS panel --}}
                    <div data-checkout-panel="gps" class="mt-3">
                        <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
                            <button
                                type="button"
                                data-checkout-gps
                                @class([
                                    'rounded-md px-4 py-2.5 text-sm font-black transition',
                                    'bg-emerald-600 text-white hover:bg-emerald-700' => $formLatitude && $formLongitude,
                                    'border border-atlantia-rose/35 bg-white text-atlantia-wine hover:bg-atlantia-blush' => ! ($formLatitude && $formLongitude),
                                ])
                            >
                                @if ($formLatitude && $formLongitude)
                                    Ubicacion capturada
                                @else
                                    Usar mi ubicacion actual
                                @endif
                            </button>

                            <p
                                data-checkout-gps-status
                                class="rounded-md px-3 py-2 text-xs font-bold
                                    {{ $formLatitude && $formLongitude ? 'bg-emerald-50 text-emerald-800' : 'bg-atlantia-blush text-atlantia-ink/60' }}"
                            >
                                @if ($formLatitude && $formLongitude)
                                    GPS listo: {{ number_format($formLatitude, 6) }}, {{ number_format($formLongitude, 6) }}
                                @else
                                    Presiona el boton para capturar tu ubicacion exacta.
                                @endif
                            </p>
                        </div>
                    </div>

                    {{-- Map panel --}}
                    <div data-checkout-panel="map" class="mt-3 hidden">
                        <div
                            data-checkout-map-container
                            class="w-full overflow-hidden rounded-md border border-atlantia-rose/30"
                            style="height:260px"
                        ></div>
                        <p class="mt-1 text-xs text-atlantia-ink/60">
                            Haz clic en el mapa o arrastra el marcador para fijar el punto de entrega.
                        </p>
                        @if ($formLatitude && $formLongitude)
                            <p class="mt-1 rounded-md bg-emerald-50 px-3 py-2 text-xs font-bold text-emerald-800">
                                Ubicacion seleccionada: {{ number_format($formLatitude, 6) }}, {{ number_format($formLongitude, 6) }}
                            </p>
                        @endif
                    </div>

                    @error('formLatitude')
                        <p class="mt-2 rounded-md bg-red-50 px-3 py-2 text-xs font-bold text-red-700">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            {{-- Acciones --}}
            <div class="mt-6 flex flex-col gap-3 border-t border-atlantia-rose/15 pt-5 sm:flex-row sm:justify-end">
                <button
                    type="button"
                    wire:click="cerrarFormulario"
                    class="rounded-md border border-atlantia-rose/35 px-5 py-3 text-sm font-black text-atlantia-ink hover:bg-atlantia-blush"
                >
                    Cancelar
                </button>
                <button
                    type="button"
                    wire:click="guardarYSeleccionar"
                    wire:loading.attr="disabled"
                    wire:target="guardarYSeleccionar"
                    class="rounded-md bg-atlantia-wine px-5 py-3 text-sm font-black text-white hover:bg-atlantia-wine-700 disabled:opacity-60"
                >
                    <span wire:loading.remove wire:target="guardarYSeleccionar">Guardar y usar esta direccion</span>
                    <span wire:loading wire:target="guardarYSeleccionar">Guardando...</span>
                </button>
            </div>
        </div>

    @endif
</section>

@script
<script>
    /* ── state ────────────────────────────────────────────────────── */
    let _checkoutMap    = null;
    let _checkoutMarker = null;
    let _activeTab      = 'gps';

    /* ── helpers ─────────────────────────────────────────────────── */
    const applyCheckoutTab = (root, tab) => {
        root.querySelectorAll('[data-checkout-tab]').forEach(btn => {
            const active = btn.dataset.checkoutTab === tab;
            btn.classList.toggle('bg-atlantia-wine', active);
            btn.classList.toggle('text-white', active);
            btn.classList.toggle('bg-white', !active);
            btn.classList.toggle('text-atlantia-ink', !active);
        });
        root.querySelectorAll('[data-checkout-panel]').forEach(panel => {
            panel.classList.toggle('hidden', panel.dataset.checkoutPanel !== tab);
        });
    };

    const loadGoogleMapsOnce = (callback) => {
        if (window.google?.maps) { callback(); return; }
        const existing = document.querySelector('script[data-gmaps-checkout]');
        if (existing) { window._checkoutMapCbs = window._checkoutMapCbs || []; window._checkoutMapCbs.push(callback); return; }
        window._checkoutMapCbs = [callback];
        window._checkoutMapsReady = () => { (window._checkoutMapCbs || []).forEach(fn => fn()); window._checkoutMapCbs = []; };
        const s = document.createElement('script');
        s.dataset.gmapsCheckout = '1';
        s.async = true;
        s.defer = true;
        s.src = `https://maps.googleapis.com/maps/api/js?key={{ config('services.google_maps.api_key') }}&libraries=geometry&callback=_checkoutMapsReady&loading=async`;
        document.head.appendChild(s);
    };

    const initCheckoutMap = () => {
        const container = document.querySelector('[data-checkout-map-container]');
        if (! container) return;

        const wire = $wire;
        const existingLat = wire.formLatitude;
        const existingLng = wire.formLongitude;
        const defaultLat  = existingLat ?? 15.7261;
        const defaultLng  = existingLng ?? -88.5940;

        if (_checkoutMap && container.dataset.mapInit === '1') {
            google.maps.event.trigger(_checkoutMap, 'resize');
            return;
        }

        _checkoutMap = null;
        _checkoutMarker = null;
        container.dataset.mapInit = '1';

        _checkoutMap = new google.maps.Map(container, {
            center: { lat: defaultLat, lng: defaultLng },
            zoom: existingLat ? 15 : 13,
            mapTypeControl: false,
            streetViewControl: false,
            fullscreenControl: false,
        });

        _checkoutMarker = new google.maps.Marker({
            position: { lat: defaultLat, lng: defaultLng },
            map: _checkoutMap,
            draggable: true,
            title: 'Arrastra para mover',
        });

        const applyPos = (latLng) => {
            wire.setFormCoordinates(latLng.lat(), latLng.lng());
        };

        _checkoutMarker.addListener('dragend', e => applyPos(e.latLng));
        _checkoutMap.addListener('click', e => {
            _checkoutMarker.setPosition(e.latLng);
            applyPos(e.latLng);
        });
    };

    /* ── bind everything ─────────────────────────────────────────── */
    const bindCheckoutLocation = () => {
        const root = document.querySelector('[data-checkout-location-root]');
        if (! root || root._locationReady) return;
        root._locationReady = true;

        applyCheckoutTab(root, _activeTab);

        /* tab switching */
        root.querySelectorAll('[data-checkout-tab]').forEach(btn => {
            btn.addEventListener('click', () => {
                _activeTab = btn.dataset.checkoutTab;
                applyCheckoutTab(root, _activeTab);
                if (_activeTab === 'map') {
                    loadGoogleMapsOnce(initCheckoutMap);
                }
            });
        });

        /* GPS button */
        const gpsBtn = root.querySelector('[data-checkout-gps]');
        const gpsStatus = root.querySelector('[data-checkout-gps-status]');
        if (! gpsBtn) return;

        gpsBtn.addEventListener('click', () => {
            if (! navigator.geolocation) {
                if (gpsStatus) { gpsStatus.textContent = 'Tu navegador no permite obtener ubicacion GPS.'; gpsStatus.className = 'rounded-md px-3 py-2 text-xs font-bold bg-red-50 text-red-700'; }
                return;
            }

            gpsBtn.disabled = true;
            gpsBtn.textContent = 'Obteniendo ubicacion...';

            navigator.geolocation.getCurrentPosition(
                (position) => {
                    const lat = position.coords.latitude;
                    const lng = position.coords.longitude;
                    const accuracy = Math.round(position.coords.accuracy || 0);

                    $wire.setFormCoordinates(lat, lng);

                    if (_checkoutMarker) {
                        const pos = { lat, lng };
                        _checkoutMarker.setPosition(pos);
                        _checkoutMap?.panTo(pos);
                    }

                    if (gpsStatus) {
                        gpsStatus.textContent = `GPS listo: ${lat.toFixed(6)}, ${lng.toFixed(6)}.${accuracy ? ' Precision: ~' + accuracy + 'm.' : ''}`;
                        gpsStatus.className = 'rounded-md px-3 py-2 text-xs font-bold bg-emerald-50 text-emerald-800';
                    }

                    gpsBtn.textContent = 'Actualizar ubicacion';
                    gpsBtn.className = 'rounded-md px-4 py-2.5 text-sm font-black transition bg-emerald-600 text-white hover:bg-emerald-700';
                    gpsBtn.disabled = false;
                },
                () => {
                    if (gpsStatus) { gpsStatus.textContent = 'No pudimos obtener tu ubicacion. Revisa los permisos del navegador.'; gpsStatus.className = 'rounded-md px-3 py-2 text-xs font-bold bg-red-50 text-red-700'; }
                    gpsBtn.textContent = 'Usar mi ubicacion actual';
                    gpsBtn.disabled = false;
                },
                { enableHighAccuracy: true, timeout: 15000, maximumAge: 0 }
            );
        });
    };

    bindCheckoutLocation();
    $wire.hook('updated', () => {
        const root = document.querySelector('[data-checkout-location-root]');
        if (root) root._locationReady = false;
        _checkoutMap = null;
        _checkoutMarker = null;
        bindCheckoutLocation();
    });
</script>
@endscript
