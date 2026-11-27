@extends('layouts.marketplace')

@section('content')
    @php
        $stepLabels = ['Datos', 'Entrega', 'Factura', 'Pago'];
    @endphp

    <section class="bg-[#fff8f8] px-3 py-2 sm:px-4 lg:px-6">
        <form
            method="POST"
            action="{{ route('cliente.checkout.store') }}"
            class="checkout-compact mx-auto grid w-full max-w-[1120px] gap-3 lg:grid-cols-[minmax(0,740px)_330px] lg:items-start lg:justify-center"
            data-disable-submit-guard
            data-checkout-wizard
            data-error-fields='@json($errors->keys())'
            data-has-old-input="{{ session()->hasOldInput() ? '1' : '0' }}"
            data-cobertura='@json($coberturaColonias ?? [])'
            data-aplica-cobertura="{{ ($aplicaCoberturaAtlantia ?? false) ? '1' : '0' }}"
        >
            @csrf

            <section
                class="mx-auto w-full overflow-hidden rounded-xl border border-atlantia-rose/20 bg-white shadow-xl shadow-atlantia-wine/10"
                role="dialog"
                aria-modal="true"
                aria-labelledby="checkout-modal-title"
            >
                <header class="border-b border-atlantia-rose/15 px-4 py-2.5">
                    <div class="flex items-start justify-between gap-3 lg:items-center">
                        <div class="shrink-0">
                            <p class="text-xs font-black uppercase tracking-normal text-atlantia-wine" data-step-counter>
                                Paso 1 de 4
                            </p>
                            <h1 id="checkout-modal-title" class="text-xl font-black leading-tight text-atlantia-ink">
                                Finalizar compra
                            </h1>
                        </div>
                        <ol class="hidden flex-1 grid-cols-4 gap-2 lg:grid lg:max-w-[400px]" aria-label="Progreso del checkout" data-steps-desktop></ol>
                        <a
                            href="{{ route('cliente.carrito.index') }}"
                            class="rounded-full px-3 py-1 text-xl font-black text-atlantia-ink/45 hover:bg-atlantia-blush hover:text-atlantia-wine"
                            aria-label="Cerrar checkout"
                        >
                            &times;
                        </a>
                    </div>

                    <ol class="mt-2 grid grid-cols-4 gap-2 lg:hidden" aria-label="Progreso del checkout" data-steps-list>
                        @foreach ($stepLabels as $label)
                            <li class="min-w-0">
                                <button
                                    type="button"
                                    class="group relative flex w-full flex-col items-center gap-1.5 text-center"
                                    data-step-jump="{{ $loop->index }}"
                                    aria-label="Ir a {{ $label }}"
                                >
                                    <span
                                        class="flex h-8 w-8 items-center justify-center rounded-full border-2 border-slate-200 bg-white text-xs font-black text-slate-400 transition"
                                        data-step-dot="{{ $loop->index }}"
                                    >
                                        {{ $loop->iteration }}
                                    </span>
                                    <span class="truncate text-xs font-bold text-atlantia-ink/55" data-step-label="{{ $loop->index }}">
                                        {{ $label }}
                                    </span>
                                </button>
                            </li>
                        @endforeach
                    </ol>
                </header>

                <div class="p-3 sm:p-4">
                    <section data-checkout-step="0" data-step-title="Datos de entrega" data-step-description="Direccion, telefono y correo para coordinar tu compra.">
                        <div class="flex items-center gap-3">
                            <span class="grid h-8 w-8 place-items-center rounded-full bg-atlantia-blush text-atlantia-wine">
                                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2" />
                                    <circle cx="12" cy="7" r="4" />
                                </svg>
                            </span>
                            <div>
                                <h2 class="text-base font-black leading-tight text-atlantia-ink">Datos de entrega</h2>
                                <p class="text-xs text-atlantia-ink/60">Completa la informacion para preparar tu pedido.</p>
                            </div>
                        </div>

                        <div class="mt-2 space-y-2">
                            @auth
                                <div class="rounded-lg border border-atlantia-rose/20">
                                    <livewire:checkout.selector-direccion />
                                </div>
                            @else
                                <div class="grid gap-x-3 gap-y-2 sm:grid-cols-2">
                                    <div>
                                        <label for="guest_nombre" class="text-xs font-black text-atlantia-ink">Nombre completo</label>
                                        <input id="guest_nombre" name="guest_nombre" type="text" value="{{ old('guest_nombre') }}" placeholder="Ej. Juan Perez" required data-step-required autocomplete="name" class="mt-0.5 w-full rounded-md border border-atlantia-rose/30 px-3 py-1.5 text-sm focus:border-atlantia-wine focus:ring-atlantia-rose">
                                        @error('guest_nombre') <p class="mt-1 text-xs font-bold text-red-600">{{ $message }}</p> @enderror
                                        <p class="mt-1 hidden text-xs font-bold text-red-600" data-field-error></p>
                                    </div>
                                    <div>
                                        <label for="guest_telefono" class="text-xs font-black text-atlantia-ink">Telefono</label>
                                        <input id="guest_telefono" name="guest_telefono" type="tel" value="{{ old('guest_telefono') }}" placeholder="Ej. 55551234" required pattern="^(\+502)?[2-7][0-9]{7}$" data-step-required autocomplete="tel" class="mt-0.5 w-full rounded-md border border-atlantia-rose/30 px-3 py-1.5 text-sm focus:border-atlantia-wine focus:ring-atlantia-rose">
                                        <p class="mt-0.5 text-[11px] font-semibold text-atlantia-ink/55">Te avisaremos por WhatsApp cuando tu pedido vaya en camino.</p>
                                        @error('guest_telefono') <p class="mt-1 text-xs font-bold text-red-600">{{ $message }}</p> @enderror
                                        <p class="mt-1 hidden text-xs font-bold text-red-600" data-field-error></p>
                                    </div>
                                    {{-- Solo operamos en estas dos zonas: se elige con un toque, sin lista desplegable. --}}
                                    <div>
                                        <span class="text-xs font-black text-atlantia-ink">Entregar en</span>
                                        <div class="mt-0.5 grid grid-cols-2 gap-1 rounded-md border border-atlantia-rose/30 bg-atlantia-cream/40 p-0.5" role="radiogroup" aria-label="Zona de entrega">
                                            @foreach (\App\Http\Requests\Cliente\CheckoutRequest::zonasEntrega() as $zona)
                                                <label class="cursor-pointer">
                                                    <input type="radio" name="guest_municipio" value="{{ $zona }}" @checked(old('guest_municipio', \App\Http\Requests\Cliente\CheckoutRequest::zonasEntrega()[0]) === $zona) class="peer sr-only" required>
                                                    <span class="block rounded px-2 py-1 text-center text-sm font-bold text-atlantia-ink/65 transition peer-checked:bg-atlantia-wine peer-checked:text-white peer-focus-visible:ring-2 peer-focus-visible:ring-atlantia-rose">{{ $zona === 'Santo Tomas' ? 'Santo Tomás' : $zona }}</span>
                                                </label>
                                            @endforeach
                                        </div>
                                        @error('guest_municipio') <p class="mt-1 text-xs font-bold text-red-600">{{ $message }}</p> @enderror
                                    </div>
                                    <div>
                                        <label for="guest_email" class="text-xs font-black text-atlantia-ink">Correo <span class="font-normal text-atlantia-ink/45" data-email-optional-label>{{ old('crear_cuenta') ? '(para tu cuenta)' : '(opcional)' }}</span></label>
                                        <input id="guest_email" name="guest_email" type="email" value="{{ old('guest_email') }}" placeholder="ejemplo@correo.com" autocomplete="email" @required(old('crear_cuenta')) data-guest-email class="mt-0.5 w-full rounded-md border border-atlantia-rose/30 px-3 py-1.5 text-sm focus:border-atlantia-wine focus:ring-atlantia-rose">
                                        @error('guest_email') <p class="mt-1 text-xs font-bold text-red-600">{{ $message }}</p> @enderror
                                        <p class="mt-1 hidden text-xs font-bold text-red-600" data-field-error></p>
                                    </div>
                                    <div class="sm:col-span-2">
                                        <label for="guest_direccion" class="text-xs font-black text-atlantia-ink">Direccion completa</label>
                                        <input id="guest_direccion" name="guest_direccion" type="text" value="{{ old('guest_direccion') }}" placeholder="Calle principal, casa #12, frente al parque..." required data-step-required autocomplete="street-address" class="mt-0.5 w-full rounded-md border border-atlantia-rose/30 px-3 py-1.5 text-sm focus:border-atlantia-wine focus:ring-atlantia-rose">
                                        @error('guest_direccion') <p class="mt-1 text-xs font-bold text-red-600">{{ $message }}</p> @enderror
                                        <p class="mt-1 hidden text-xs font-bold text-red-600" data-field-error></p>
                                    </div>
                                    <div>
                                        <label for="guest_zona_barrio" class="text-xs font-black text-atlantia-ink">Colonia / barrio <span class="font-normal text-atlantia-ink/45">(opcional)</span></label>
                                        <input id="guest_zona_barrio" name="guest_zona_barrio" type="text" value="{{ old('guest_zona_barrio') }}" placeholder="Ej. Barrio El Centro" list="colonias-sugeridas" autocomplete="off" class="mt-0.5 w-full rounded-md border border-atlantia-rose/30 px-3 py-1.5 text-sm focus:border-atlantia-wine focus:ring-atlantia-rose">
                                        <datalist id="colonias-sugeridas">
                                            @foreach ($coberturaColonias ?? [] as $municipioCatalogo => $listasCatalogo)
                                                @foreach ($listasCatalogo['cubiertas'] ?? [] as $coloniaSugerida)
                                                    <option value="{{ $coloniaSugerida }}">{{ $municipioCatalogo === 'Santo Tomas' ? 'Santo Tomás' : $municipioCatalogo }}</option>
                                                @endforeach
                                            @endforeach
                                        </datalist>
                                        <p class="mt-1 hidden text-[11px] font-bold text-emerald-700" data-colonia-municipio-note></p>
                                        <p class="mt-1 hidden text-[11px] font-bold text-red-600" data-colonia-excluida-note></p>
                                    </div>
                                    <div>
                                        <label for="guest_referencia" class="text-xs font-black text-atlantia-ink">Referencia <span class="font-normal text-atlantia-ink/45">(opcional)</span></label>
                                        <input id="guest_referencia" name="guest_referencia" type="text" value="{{ old('guest_referencia') }}" placeholder="Ej. Porton negro, a mano derecha" class="mt-0.5 w-full rounded-md border border-atlantia-rose/30 px-3 py-1.5 text-sm focus:border-atlantia-wine focus:ring-atlantia-rose">
                                    </div>
                                    <div class="sm:col-span-2">
                                        <label class="text-xs font-black text-atlantia-ink">Ubicacion exacta <span class="font-normal text-atlantia-ink/45">(opcional)</span></label>
                                        <input type="hidden" name="guest_latitude" value="{{ old('guest_latitude') }}" data-guest-latitude>
                                        <input type="hidden" name="guest_longitude" value="{{ old('guest_longitude') }}" data-guest-longitude>
                                        <div class="mt-0.5 grid gap-2 sm:grid-cols-[220px_minmax(0,1fr)] sm:items-center">
                                            <button type="button" data-guest-gps class="w-full rounded-md border border-atlantia-rose/35 px-3 py-1.5 text-xs font-black text-atlantia-wine hover:bg-atlantia-blush">
                                                Usar mi ubicacion actual
                                            </button>
                                            <p data-guest-gps-status class="rounded-md bg-atlantia-blush px-2 py-1 text-[11px] font-bold leading-4 text-atlantia-ink/60">
                                                Opcional: ayuda al repartidor a llegar mas rapido. Si no la compartes, te llamamos para confirmar.
                                            </p>
                                        </div>
                                        @error('guest_latitude') <p class="mt-1 text-xs font-bold text-red-600">{{ $message }}</p> @enderror
                                        <p class="mt-1 hidden text-xs font-bold text-red-600" data-field-error></p>
                                    </div>
                                </div>

                                <div class="rounded-lg border border-atlantia-rose/25 bg-atlantia-blush/35 px-3 py-2">
                                    <label class="flex items-start gap-3">
                                        <input type="checkbox" name="crear_cuenta" value="1" @checked(old('crear_cuenta')) class="mt-0.5 rounded border-atlantia-rose text-atlantia-wine focus:ring-atlantia-rose" data-create-account-toggle>
                                        <span>
                                            <span class="block text-xs font-black text-atlantia-ink">Crear una cuenta para futuras compras</span>
                                            <span class="block text-[11px] text-atlantia-ink/60">Necesitaras tu correo y una contrasena. Si prefieres comprar rapido, deja esto desmarcado.</span>
                                        </span>
                                    </label>
                                    <div class="mt-2 grid gap-3 sm:grid-cols-2 {{ old('crear_cuenta') ? '' : 'hidden' }}" data-account-passwords>
                                        <div>
                                            <label for="password" class="text-xs font-black text-atlantia-ink">Contrasena</label>
                                            <input id="password" name="password" type="password" autocomplete="new-password" class="mt-0.5 w-full rounded-md border border-atlantia-rose/30 px-3 py-1.5 text-sm focus:border-atlantia-wine focus:ring-atlantia-rose bg-white" data-account-password-field>
                                            @error('password') <p class="mt-1 text-xs font-bold text-red-600">{{ $message }}</p> @enderror
                                        </div>
                                        <div>
                                            <label for="password_confirmation" class="text-xs font-black text-atlantia-ink">Confirmar contrasena</label>
                                            <input id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" class="mt-0.5 w-full rounded-md border border-atlantia-rose/30 px-3 py-1.5 text-sm focus:border-atlantia-wine focus:ring-atlantia-rose bg-white" data-account-password-field>
                                        </div>
                                    </div>
                                </div>
                            @endauth
                        </div>
                    </section>

                    <section class="hidden" data-checkout-step="1" data-step-title="Tipo de entrega" data-step-description="Elige como prefieres recibir tu pedido.">
                        @php
                            $ventanasProgramadas = \App\Http\Requests\Cliente\CheckoutRequest::ventanasProgramadas();
                            $diasProgramados = collect(range(0, 6))->map(function (int $offset) {
                                $dia = now()->startOfDay()->addDays($offset);

                                return [
                                    'valor' => $dia->toDateString(),
                                    'titulo' => $offset === 0 ? 'Hoy' : ($offset === 1 ? 'Mañana' : ucfirst($dia->locale('es')->translatedFormat('D'))),
                                    'dia' => $dia->locale('es')->translatedFormat('j M'),
                                    'hoy' => $offset === 0,
                                ];
                            });
                        @endphp
                        <h2 class="text-xl font-black text-atlantia-ink">Tipo de entrega</h2>
                        <p class="mt-1 text-sm text-atlantia-ink/65">Elige como quieres recibir tu pedido. El envio se calcula segun tu direccion.</p>

                        @php
                            /* Atlantia compra al proveedor y entrega: sus productos no se recogen en tienda. */
                            $permiteRecoger = ! ($aplicaCoberturaAtlantia ?? false);
                            $tipoEntregaInicial = old('tipo_entrega', 'domicilio');
                            if (! $permiteRecoger && $tipoEntregaInicial === 'recoger') {
                                $tipoEntregaInicial = 'domicilio';
                            }
                            /* Fuera de horario solo se aceptan entregas programadas. */
                            $soloProgramado = (bool) ($horarioTiendas['cerrado'] ?? false);
                            if ($soloProgramado) {
                                $tipoEntregaInicial = 'programado';
                            }
                        @endphp
                        @if ($soloProgramado)
                            <div @class([
                                'mt-3 rounded-lg px-3 py-2 text-sm font-bold',
                                'bg-red-50 text-red-700' => $horarioTiendas['pausado'],
                                'bg-amber-50 text-amber-800' => ! $horarioTiendas['pausado'],
                            ]) data-aviso-horario>
                                @foreach ($horarioTiendas['mensajes'] as $mensajeHorario)
                                    <p>{{ $mensajeHorario }}</p>
                                @endforeach
                                @unless ($horarioTiendas['pausado'])
                                    <p class="mt-1 font-semibold">Puedes programar tu entrega para cuando abramos.</p>
                                @endunless
                            </div>
                        @endif
                        <div @class(['mt-4 grid gap-3', 'sm:grid-cols-3' => $permiteRecoger, 'sm:grid-cols-2' => ! $permiteRecoger])>
                            <label class="checkout-choice text-center has-[:disabled]:cursor-not-allowed has-[:disabled]:opacity-45">
                                <input type="radio" name="tipo_entrega" value="domicilio" @checked($tipoEntregaInicial === 'domicilio') @disabled($soloProgramado) class="sr-only" data-delivery-type>
                                <span class="block text-sm font-bold tracking-normal text-sky-700" aria-hidden="true">DOM</span>
                                <span class="mt-2 block font-bold text-atlantia-ink">Domicilio</span>
                                <span class="mt-1 block text-sm text-atlantia-ink/65">Lo antes posible, 45-60 min</span>
                                <span class="mt-2 block font-bold text-atlantia-wine">Segun zona</span>
                            </label>
                            @if ($permiteRecoger)
                            <label class="checkout-choice text-center has-[:disabled]:cursor-not-allowed has-[:disabled]:opacity-45">
                                <input type="radio" name="tipo_entrega" value="recoger" @checked($tipoEntregaInicial === 'recoger') @disabled($soloProgramado) class="sr-only" data-delivery-type>
                                <span class="block text-sm font-bold tracking-normal text-atlantia-wine" aria-hidden="true">TDA</span>
                                <span class="mt-2 block font-bold text-atlantia-ink">Recoger en tienda</span>
                                <span class="mt-1 block text-sm text-atlantia-ink/65">Te avisamos cuando este listo</span>
                                <span class="mt-2 block font-bold text-atlantia-wine">Gratis</span>
                            </label>
                            @endif
                            <label class="checkout-choice text-center has-[:disabled]:cursor-not-allowed has-[:disabled]:opacity-45">
                                <input type="radio" name="tipo_entrega" value="programado" @checked($tipoEntregaInicial === 'programado') class="sr-only" data-delivery-type>
                                <span class="block text-sm font-bold tracking-normal text-atlantia-wine" aria-hidden="true">PRO</span>
                                <span class="mt-2 block font-bold text-atlantia-ink">Programado</span>
                                <span class="mt-1 block text-sm text-atlantia-ink/65">Elige día y horario</span>
                                <span class="mt-2 block font-bold text-atlantia-wine">Segun zona</span>
                            </label>
                        </div>

                        @unless ($permiteRecoger)
                            <p class="mt-2 text-xs text-atlantia-ink/60">Los productos de Atlantia Store se entregan a domicilio.</p>
                        @endunless

                        {{-- Solo para "Programado": se despliega con animacion al elegirlo. --}}
                        <div class="grid grid-rows-[0fr] opacity-0 transition-all duration-300 ease-out data-[open]:grid-rows-[1fr] data-[open]:opacity-100" data-scheduled-delivery-panel>
                            <div class="min-h-0 overflow-hidden">
                                <div class="mt-4 rounded-xl border border-atlantia-rose/20 bg-atlantia-cream/40 p-3">
                                    <h3 class="text-sm font-black text-atlantia-ink">¿Qué día lo quieres recibir?</h3>
                                    <div class="mt-2 flex snap-x gap-2 overflow-x-auto pb-1 [scrollbar-width:none] [&::-webkit-scrollbar]:hidden">
                                        @foreach ($diasProgramados as $dia)
                                            <label class="checkout-window min-w-[76px] shrink-0 snap-start has-[:disabled]:cursor-not-allowed has-[:disabled]:opacity-40" @if ($dia['hoy']) data-today-label @endif>
                                                <input type="radio" name="programado_fecha" value="{{ $dia['valor'] }}" @checked(old('programado_fecha') === $dia['valor']) class="sr-only" data-scheduled-date data-today="{{ $dia['hoy'] ? '1' : '0' }}">
                                                <span class="block text-[11px] uppercase text-atlantia-ink/55">{{ $dia['titulo'] }}</span>
                                                {{ $dia['dia'] }}
                                                @if ($dia['hoy'])<span class="hidden text-[10px] font-bold normal-case text-atlantia-ink/55" data-today-full>Sin horarios</span>@endif
                                            </label>
                                        @endforeach
                                    </div>

                                    <h3 class="mt-3 text-sm font-black text-atlantia-ink">¿En qué horario?</h3>
                                    <div class="mt-2 grid grid-cols-3 gap-2 sm:grid-cols-6">
                                        @foreach ($ventanasProgramadas as $ventana => $inicio)
                                            <label class="checkout-window has-[:disabled]:cursor-not-allowed has-[:disabled]:opacity-40">
                                                <input type="radio" name="ventana_entrega" value="{{ $ventana }}" @checked(old('ventana_entrega') === $ventana) class="sr-only" data-delivery-window data-start="{{ $inicio }}">
                                                {{ $ventana }}
                                            </label>
                                        @endforeach
                                    </div>
                                    <p class="mt-2 text-[11px] text-atlantia-ink/55">Para hoy solo se pueden elegir horarios con al menos 1 hora de anticipación.</p>
                                    <p class="mt-1 hidden text-xs font-bold text-red-600" data-schedule-error></p>
                                    @error('programado_fecha') <p class="mt-1 text-xs font-bold text-red-600">{{ $message }}</p> @enderror
                                    @error('ventana_entrega') <p class="mt-1 text-xs font-bold text-red-600">{{ $message }}</p> @enderror
                                </div>
                            </div>
                        </div>

                        <div class="mt-4 hidden rounded-lg border border-slate-200 bg-slate-50 p-3" data-store-pickup-panel>
                            <p class="text-sm font-black text-atlantia-ink">Recoge en tienda</p>
                            <p class="mt-1 text-sm leading-6 text-atlantia-ink/65">Te avisaremos cuando tu pedido este listo para recoger en el comercio.</p>
                        </div>

                        <div class="mt-4">
                            <label for="notas" class="text-sm font-bold text-atlantia-ink">Instrucciones para el repartidor <span class="font-normal text-atlantia-ink/55">(opcional)</span></label>
                            <textarea id="notas" name="notas" rows="3" placeholder="Ej. Tocar timbre, dejar con portero, llamar al llegar..." class="mt-2 w-full rounded-md border border-atlantia-rose/30 px-4 py-3 text-sm focus:border-atlantia-wine focus:ring-atlantia-rose">{{ old('notas') }}</textarea>
                        </div>
                    </section>

                    <section class="hidden" data-checkout-step="2" data-step-title="Facturacion FEL" data-step-description="Elige como quieres recibir la factura.">
                        <h2 class="text-xl font-black text-atlantia-ink">Facturacion electronica FEL</h2>
                        <p class="mt-1 text-sm text-atlantia-ink/65">Cada vendedor emitira su propio DTE FEL al confirmar el pedido.</p>

                        <div class="mt-5 grid gap-3 sm:grid-cols-2">
                            <label class="checkout-choice">
                                <input type="radio" name="facturacion_tipo" value="datos" @checked(old('facturacion_tipo', auth()->check() ? 'datos' : 'cf') === 'datos') class="sr-only" data-billing-type>
                                <span class="font-bold text-atlantia-ink">Factura con mis datos</span>
                                <span class="mt-1 block text-sm text-atlantia-ink/65">Llega por correo al confirmar.</span>
                            </label>
                            <label class="checkout-choice">
                                <input type="radio" name="facturacion_tipo" value="cf" @checked(old('facturacion_tipo', auth()->check() ? 'datos' : 'cf') === 'cf') class="sr-only" data-billing-type>
                                <span class="font-bold text-atlantia-ink">Consumidor Final (CF)</span>
                                <span class="mt-1 block text-sm text-atlantia-ink/65">Impresa al entregar el pedido.</span>
                            </label>
                        </div>

                        <div class="mt-5 grid gap-4 sm:grid-cols-2" data-billing-fields>
                            <div>
                                <label for="nit_facturacion" class="text-sm font-bold text-atlantia-ink">NIT</label>
                                <input id="nit_facturacion" name="nit_facturacion" type="text" value="{{ old('nit_facturacion') }}" placeholder="Ej. 452198-6" class="mt-2 w-full rounded-md border border-atlantia-rose/30 px-4 py-3 text-sm focus:border-atlantia-wine focus:ring-atlantia-rose">
                            </div>
                            <div>
                                <label for="razon_social" class="text-sm font-bold text-atlantia-ink">Nombre / Razon social</label>
                                <input id="razon_social" name="razon_social" type="text" value="{{ old('razon_social', auth()->user()?->name) }}" class="mt-2 w-full rounded-md border border-atlantia-rose/30 px-4 py-3 text-sm focus:border-atlantia-wine focus:ring-atlantia-rose">
                            </div>
                            <div class="sm:col-span-2">
                                <label for="correo_facturacion" class="text-sm font-bold text-atlantia-ink">Correo electronico</label>
                                <input id="correo_facturacion" name="correo_facturacion" type="email" placeholder="Para enviarte la factura" data-billing-email value="{{ old('correo_facturacion', auth()->user()?->email) }}" class="mt-2 w-full rounded-md border border-atlantia-rose/30 px-4 py-3 text-sm focus:border-atlantia-wine focus:ring-atlantia-rose">
                            </div>
                        </div>

                        <div class="mt-5 hidden rounded-lg border border-slate-200 bg-slate-50 p-4" data-cf-message>
                            <p class="text-sm font-black text-atlantia-ink">Consumidor Final</p>
                            <p class="mt-1 text-sm leading-6 text-atlantia-ink/65">Emitiremos el documento como consumidor final. No necesitas completar datos fiscales.</p>
                        </div>
                    </section>

                    <section class="hidden" data-checkout-step="3" data-step-title="Metodo de pago" data-step-description="Selecciona como pagaras tu compra.">
                        <livewire:checkout.formulario-pago />

                        @php
                            $saldoDisponible = auth()->check() ? app(\App\Services\Clientes\SaldoService::class)->disponible(auth()->user()) : 0;
                            $propinaInicial = (string) old('propina', '0');
                        @endphp
                        <div class="mt-4 rounded-lg border border-atlantia-rose/20 bg-white p-4" data-propina-box>
                            <p class="text-sm font-black text-atlantia-ink">Propina para tu repartidor <span class="font-semibold text-atlantia-ink/50">(opcional)</span></p>
                            <p class="mt-0.5 text-xs text-atlantia-ink/55">El 100 % es para quien te lleva el pedido.</p>
                            <div class="mt-3 grid grid-cols-5 gap-2">
                                @foreach ([0, ...config('atlantia.propinas_sugeridas', [5, 10, 15])] as $monto)
                                    <label class="cursor-pointer rounded-lg border border-atlantia-rose/25 px-2 py-2 text-center text-sm font-black text-atlantia-ink has-[:checked]:border-atlantia-wine has-[:checked]:bg-atlantia-blush has-[:checked]:text-atlantia-wine">
                                        <input type="radio" name="propina_opcion" value="{{ $monto }}" class="sr-only" data-propina-opcion @checked((float) $propinaInicial === (float) $monto)>
                                        {{ $monto === 0 ? 'No' : 'Q '.$monto }}
                                    </label>
                                @endforeach
                                <label class="cursor-pointer rounded-lg border border-atlantia-rose/25 px-2 py-2 text-center text-sm font-black text-atlantia-ink has-[:checked]:border-atlantia-wine has-[:checked]:bg-atlantia-blush has-[:checked]:text-atlantia-wine">
                                    <input type="radio" name="propina_opcion" value="otro" class="sr-only" data-propina-opcion @checked(! in_array((float) $propinaInicial, [0, ...config('atlantia.propinas_sugeridas', [5, 10, 15])], false))>
                                    Otro
                                </label>
                            </div>
                            <div class="mt-2 hidden" data-propina-otro>
                                <label for="propina_otro" class="sr-only">Monto de propina</label>
                                <input id="propina_otro" type="number" min="0" max="500" step="1" inputmode="numeric" placeholder="Monto en quetzales" value="{{ $propinaInicial }}" class="w-full rounded-md border border-atlantia-rose/30 px-3 py-2 text-sm">
                            </div>
                            <input type="hidden" name="propina" value="{{ $propinaInicial }}" data-propina-valor>
                            @error('propina') <p class="mt-1 text-xs font-bold text-red-600">{{ $message }}</p> @enderror
                        </div>

                        @if ($saldoDisponible > 0)
                            <label class="mt-3 flex items-start gap-3 rounded-lg border border-emerald-200 bg-emerald-50 p-4" data-saldo-box>
                                <input type="checkbox" name="usar_saldo" value="1" @checked(old('usar_saldo', true)) class="mt-0.5 h-4 w-4 rounded border-emerald-300 text-emerald-700" data-usar-saldo>
                                <span>
                                    <span class="block text-sm font-black text-emerald-800">Usar mi saldo a favor: Q {{ number_format($saldoDisponible, 2) }}</span>
                                    <span class="block text-xs text-emerald-800/75">Se descuenta de lo que pagas hoy.</span>
                                </span>
                            </label>
                        @endif

                        @if (auth()->user()?->recurrente_metodo_pago_id && \App\Services\Pagos\Recurrente\RecurrenteClient::habilitado())
                            <label class="mt-3 flex items-start gap-3 rounded-lg border border-atlantia-rose/20 bg-white p-4" data-tarjeta-guardada-checkout>
                                <input type="checkbox" name="usar_tarjeta_guardada" value="1" @checked(old('usar_tarjeta_guardada', true)) class="mt-0.5 h-4 w-4 rounded border-atlantia-rose/40 text-atlantia-wine">
                                <span>
                                    <span class="block text-sm font-black text-atlantia-ink">Pagar con un toque: {{ auth()->user()->tarjeta_descripcion ?: 'mi tarjeta guardada' }}</span>
                                    <span class="block text-xs text-atlantia-ink/60">Aplica si eliges pago con tarjeta en línea.</span>
                                </span>
                            </label>
                        @endif
                    </section>
                </div>

                <footer class="flex items-center justify-between gap-3 border-t border-atlantia-rose/15 bg-atlantia-cream/50 px-3 py-2">
                    <button type="button" data-step-back class="rounded-md border border-atlantia-rose/35 px-4 py-2 text-sm font-black text-atlantia-ink hover:bg-atlantia-blush disabled:cursor-not-allowed disabled:opacity-45">
                        &larr; Atras
                    </button>
                    <button type="button" data-step-next class="rounded-md bg-atlantia-wine px-5 py-2 text-sm font-black text-white shadow-lg shadow-atlantia-wine/20 hover:bg-atlantia-wine-700 disabled:cursor-not-allowed disabled:opacity-60">
                        Siguiente &rarr;
                    </button>
                    <button type="submit" data-step-submit class="hidden rounded-md bg-atlantia-wine px-5 py-2 text-sm font-black text-white shadow-lg shadow-atlantia-wine/20 hover:bg-atlantia-wine-700 disabled:cursor-wait disabled:opacity-70">
                        <span data-submit-label>Confirmar pedido</span>
                    </button>
                </footer>
            </section>

            {{-- En celular el resumen va arriba como una barra plegable con el total (como las tiendas grandes);
                 en computadora queda fijo a la derecha. --}}
            <aside class="order-first mx-auto w-full max-w-[640px] lg:order-none lg:sticky lg:top-3 lg:h-fit lg:max-w-none" data-checkout-summary>
                <details class="group" open data-summary-details>
                    <summary class="flex cursor-pointer list-none items-center justify-between gap-3 rounded-xl border border-atlantia-rose/20 bg-white px-4 py-3 text-sm font-black text-atlantia-wine shadow-sm group-open:rounded-b-none group-open:border-b-transparent lg:hidden [&::-webkit-details-marker]:hidden">
                        <span class="flex items-center gap-2">
                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M4 5h2l2 10h9l2-7H7M10 19.5a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0ZM19 19.5a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0Z" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>
                            <span class="group-open:hidden">Ver resumen del pedido</span>
                            <span class="hidden group-open:inline">Ocultar resumen</span>
                            <svg class="h-4 w-4 transition group-open:rotate-180" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="m6 9 6 6 6-6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                        </span>
                        <span class="text-base text-atlantia-ink" data-summary-total-mirror></span>
                    </summary>
                    <div class="rounded-b-xl border-x border-b border-atlantia-rose/20 bg-white max-lg:[&>*]:!rounded-none max-lg:[&>*]:!border-0 max-lg:[&>*]:!shadow-none lg:rounded-none lg:border-0 lg:bg-transparent">
                        <livewire:checkout.resumen-multivendedor />
                    </div>
                </details>
            </aside>
        </form>

        {{-- Aviso amable cuando la colonia esta fuera de la cobertura propia de Atlantia. --}}
        <div class="fixed inset-0 z-[97] hidden items-center justify-center bg-slate-950/50 px-4 backdrop-blur-sm" data-cobertura-modal aria-hidden="true">
            <div class="w-full max-w-md rounded-3xl bg-white p-6 text-center shadow-2xl shadow-slate-950/25 sm:p-8" role="dialog" aria-modal="true" aria-labelledby="cobertura-modal-title">
                <div class="mx-auto grid h-24 w-24 place-items-center rounded-full bg-atlantia-blush/60">
                    <div class="grid h-16 w-16 place-items-center rounded-full bg-white text-atlantia-wine shadow-sm">
                        <svg class="h-8 w-8" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M12 21s7-6.1 7-11.5A7 7 0 0 0 5 9.5C5 14.9 12 21 12 21Z" />
                            <circle cx="12" cy="9.5" r="2.5" />
                        </svg>
                    </div>
                </div>
                <h2 id="cobertura-modal-title" class="mt-5 text-2xl font-black leading-tight text-atlantia-ink">¡Todavía no llegamos a <span data-cobertura-colonia>tu colonia</span>!</h2>
                <p class="mt-3 text-sm leading-6 text-atlantia-ink/70">
                    Por ahora Atlantia Store no hace entregas a domicilio en <strong class="text-atlantia-ink" data-cobertura-colonia>esta zona</strong>. Estamos trabajando para llegar pronto. Puedes usar otra dirección dentro de nuestra zona de entrega.
                </p>
                <div class="mt-6 grid gap-2">
                    <button type="button" class="inline-flex h-12 items-center justify-center rounded-xl bg-atlantia-wine px-5 text-sm font-black text-white shadow-lg shadow-atlantia-wine/20 transition hover:bg-atlantia-wine-700" data-cobertura-cambiar>
                        Usar otra dirección
                    </button>
                    <a href="{{ route('cliente.carrito.index') }}" class="inline-flex h-12 items-center justify-center rounded-xl border border-atlantia-rose/35 px-5 text-sm font-black text-atlantia-wine transition hover:bg-atlantia-blush">
                        Volver al carrito
                    </a>
                </div>
            </div>
        </div>
    </section>
@endsection

@push('scripts')
    <script @nonce>
        (() => {
            const form = document.querySelector('[data-checkout-wizard]');
            if (!form) return;

            const stepsList = form.querySelector('[data-steps-list]');
            const stepsDesktop = form.querySelector('[data-steps-desktop]');
            const placeSteps = () => {
                const target = window.matchMedia('(min-width: 1024px)').matches ? stepsDesktop : stepsList;
                if (target && stepsList && stepsDesktop && !target.children.length) {
                    const source = target === stepsDesktop ? stepsList : stepsDesktop;
                    while (source.firstChild) target.appendChild(source.firstChild);
                }
            };
            placeSteps();
            window.matchMedia('(min-width: 1024px)').addEventListener('change', placeSteps);

            const steps = [...form.querySelectorAll('[data-checkout-step]')];
            const dots = [...form.querySelectorAll('[data-step-dot]')];
            const labels = [...form.querySelectorAll('[data-step-label]')];
            const jumpers = [...form.querySelectorAll('[data-step-jump]')];
            const counter = form.querySelector('[data-step-counter]');
            const backButton = form.querySelector('[data-step-back]');
            const nextButton = form.querySelector('[data-step-next]');
            const submitButton = form.querySelector('[data-step-submit]');
            const submitLabel = form.querySelector('[data-submit-label]');
            const completed = new Set();
            let currentStep = 0;

            const formatMoney = (value) => `Q ${Number(value || 0).toFixed(2)}`;

            /* Resumen plegable en celular: cerrado al entrar y con el total visible en la barra. */
            const summaryDetails = document.querySelector('[data-summary-details]');
            if (summaryDetails && window.matchMedia('(max-width: 1023px)').matches) summaryDetails.open = false;
            const mirrorSummaryTotal = () => {
                const mirror = document.querySelector('[data-summary-total-mirror]');
                const total = document.querySelector('[data-checkout-total]');
                if (mirror && total) mirror.textContent = total.textContent.trim();
            };
            mirrorSummaryTotal();
            const summaryRoot = document.querySelector('[data-checkout-summary]');
            if (summaryRoot) new MutationObserver(mirrorSummaryTotal).observe(summaryRoot, { childList: true, subtree: true, characterData: true });

            const currentTotal = () => {
                const totalNode = document.querySelector('[data-checkout-total]');
                return totalNode?.dataset.checkoutTotal || totalNode?.textContent?.replace(/[^\d.]/g, '') || '';
            };

            /*
             * El resumen (Livewire) calcula el envio con el tipo de entrega y la zona elegida:
             * domicilio/programado segun Puerto Barrios o Santo Tomas (y el GPS si lo hay); recoger = gratis.
             */
            let deliveryTimer = null;
            let lastDeliveryPayload = '';
            const notifyDelivery = () => {
                window.clearTimeout(deliveryTimer);
                deliveryTimer = window.setTimeout(() => {
                    const payload = {
                        tipoEntrega: form.querySelector('[data-delivery-type]:checked')?.value ?? 'domicilio',
                        municipio: form.querySelector('input[name="guest_municipio"]:checked')?.value ?? null,
                        latitude: form.querySelector('[data-guest-latitude]')?.value || null,
                        longitude: form.querySelector('[data-guest-longitude]')?.value || null,
                        direccion: [form.querySelector('#guest_direccion')?.value, form.querySelector('#guest_zona_barrio')?.value].filter(Boolean).join(', ') || null,
                    };
                    const key = JSON.stringify(payload);
                    if (key === lastDeliveryPayload || !window.Livewire?.dispatch) return;
                    lastDeliveryPayload = key;
                    window.Livewire.dispatch('checkout.entrega-actualizada', payload);
                }, 250);
            };
            form.addEventListener('change', (event) => {
                if (event.target.matches('input[name="guest_municipio"], #guest_direccion, #guest_zona_barrio')) notifyDelivery();
            });
            form.querySelector('[data-guest-latitude]')?.addEventListener('input', notifyDelivery);
            if (window.Livewire?.dispatch) {
                notifyDelivery();
            } else {
                document.addEventListener('livewire:initialized', notifyDelivery, { once: true });
            }

            const syncSummaryTotals = (shippingAmount) => {
                const shippingNode = document.querySelector('[data-checkout-shipping]');
                const totalNode = document.querySelector('[data-checkout-total]');
                if (!shippingNode || !totalNode) return;

                if (!shippingNode.dataset.baseShipping) {
                    shippingNode.dataset.baseShipping = shippingNode.dataset.checkoutShipping || '0';
                }
                if (!totalNode.dataset.baseTotal) {
                    totalNode.dataset.baseTotal = totalNode.dataset.checkoutTotal || '0';
                }

                const baseShipping = Number(shippingNode.dataset.baseShipping || 0);
                const baseTotal = Number(totalNode.dataset.baseTotal || 0);
                const nextShipping = Number(shippingAmount ?? baseShipping);
                const nextTotal = Math.max(0, baseTotal - baseShipping + nextShipping);

                shippingNode.dataset.checkoutShipping = String(nextShipping.toFixed(2));
                shippingNode.textContent = formatMoney(nextShipping);
                totalNode.dataset.checkoutTotal = String(nextTotal.toFixed(2));
                totalNode.textContent = formatMoney(nextTotal);
            };

            const showFieldError = (field, message) => {
                field.classList.add('border-red-500', 'focus:border-red-500');
                const error = field.closest('div')?.querySelector('[data-field-error]');
                if (error) {
                    error.textContent = message;
                    error.classList.remove('hidden');
                }
            };

            const clearFieldError = (field) => {
                field.classList.remove('border-red-500', 'focus:border-red-500');
                const error = field.closest('div')?.querySelector('[data-field-error]');
                if (error) {
                    error.textContent = '';
                    error.classList.add('hidden');
                }
            };

            const isVisible = (element) => !element.closest('.hidden') && element.offsetParent !== null;

            const validateStep = (index, report = true) => {
                const step = steps[index];
                let valid = true;

                step.querySelectorAll('[data-step-required], input[required], select[required], textarea[required]').forEach((field) => {
                    const hiddenRequired = field.type === 'hidden' && field.hasAttribute('data-step-required');
                    if (field.disabled || (!hiddenRequired && !isVisible(field))) {
                        clearFieldError(field);
                        return;
                    }

                    const fieldValid = field.checkValidity();
                    if (!fieldValid) {
                        valid = false;
                        if (report) {
                            showFieldError(field, field.validity.valueMissing ? 'Este campo es obligatorio.' : 'Revisa el formato de este campo.');
                        }
                    } else {
                        clearFieldError(field);
                    }
                });

                /* Colonia fuera de la cobertura de Atlantia: solo se puede continuar recogiendo en tienda. */
                if ((index === 0 || index === 1) && window.atlantiaCobertura?.bloquea()) {
                    valid = false;
                }

                if (index === 1 && form.querySelector('[data-delivery-type]:checked')?.value === 'programado') {
                    const scheduleError = form.querySelector('[data-schedule-error]');
                    const missing = !form.querySelector('[data-scheduled-date]:checked')
                        ? 'Elige el dia de entrega.'
                        : (!form.querySelector('[data-delivery-window]:checked:not(:disabled)') ? 'Elige un horario de entrega.' : '');
                    if (missing) valid = false;
                    if (scheduleError) {
                        scheduleError.textContent = report ? missing : '';
                        scheduleError.classList.toggle('hidden', !(report && missing));
                    }
                }

                if (valid) {
                    completed.add(index);
                } else {
                    completed.delete(index);
                }

                return valid;
            };

            const syncProgress = () => {
                steps.forEach((step, index) => {
                    step.classList.toggle('hidden', index !== currentStep);
                });

                dots.forEach((dot, index) => {
                    dot.className = 'flex h-8 w-8 items-center justify-center rounded-full border-2 text-xs font-black transition';
                    if (completed.has(index) && index !== currentStep) {
                        dot.classList.add('border-emerald-600', 'bg-emerald-600', 'text-white');
                        dot.textContent = '✓';
                    } else if (index === currentStep) {
                        dot.classList.add('border-sky-600', 'bg-sky-600', 'text-white');
                        dot.textContent = String(index + 1);
                    } else {
                        dot.classList.add('border-slate-200', 'bg-white', 'text-slate-400');
                        dot.textContent = String(index + 1);
                    }
                });

                labels.forEach((label, index) => {
                    label.classList.toggle('text-atlantia-wine', index === currentStep);
                    label.classList.toggle('text-atlantia-ink/55', index !== currentStep);
                });

                counter.textContent = `Paso ${currentStep + 1} de ${steps.length}: ${steps[currentStep].dataset.stepTitle}`;
                backButton.disabled = currentStep === 0;
                nextButton.classList.toggle('hidden', currentStep === steps.length - 1);
                nextButton.disabled = currentStep !== steps.length - 1 && !validateStep(currentStep, false);
                submitButton.classList.toggle('hidden', currentStep !== steps.length - 1);
                submitLabel.textContent = currentTotal() ? `Confirmar y pagar ${formatMoney(currentTotal())}` : 'Confirmar y pagar';
            };

            const goToStep = (index) => {
                currentStep = Math.max(0, Math.min(index, steps.length - 1));
                /* En el ultimo paso se abre el resumen para revisar el total y aceptar las condiciones. */
                if (currentStep === steps.length - 1) {
                    const resumen = form.querySelector('[data-summary-details]');
                    if (resumen) resumen.open = true;
                }
                syncProgress();
            };

            /* Para hoy se bloquean los horarios que empiezan en menos de 1 hora. */
            const syncScheduleWindows = () => {
                const todaySelected = form.querySelector('[data-scheduled-date]:checked')?.dataset.today === '1';
                const minStart = new Date().getHours() + 1 + (new Date().getMinutes() > 0 ? 1 : 0);
                form.querySelectorAll('[data-delivery-window]').forEach((input) => {
                    input.disabled = todaySelected && Number(input.dataset.start) < minStart;
                    if (input.disabled) input.checked = false;
                });
            };

            const syncDeliverySchedule = () => {
                const selectedType = form.querySelector('[data-delivery-type]:checked')?.value ?? 'domicilio';
                const isScheduled = selectedType === 'programado';
                const isPickup = selectedType === 'recoger';
                const panel = form.querySelector('[data-scheduled-delivery-panel]');
                panel?.toggleAttribute('data-open', isScheduled);
                panel?.setAttribute('aria-hidden', isScheduled ? 'false' : 'true');
                form.querySelector('[data-store-pickup-panel]')?.classList.toggle('hidden', !isPickup);

                form.querySelectorAll('[data-scheduled-date]').forEach((input) => input.disabled = !isScheduled);
                if (isScheduled && !form.querySelector('[data-scheduled-date]:checked')) {
                    const first = form.querySelector('[data-scheduled-date]');
                    if (first) first.checked = true;
                }
                form.querySelectorAll('[data-delivery-window]').forEach((input) => input.disabled = !isScheduled);
                if (isScheduled) {
                    syncScheduleWindows();
                    /* Si hoy ya no quedan horarios, "Hoy" se bloquea y se pasa a manana. */
                    const todayInput = form.querySelector('[data-scheduled-date][data-today="1"]');
                    const minStart = new Date().getHours() + 1 + (new Date().getMinutes() > 0 ? 1 : 0);
                    const todayHasWindows = [...form.querySelectorAll('[data-delivery-window]')].some((input) => Number(input.dataset.start) >= minStart);
                    if (todayInput) {
                        todayInput.disabled = !todayHasWindows;
                        form.querySelector('[data-today-full]')?.classList.toggle('hidden', todayHasWindows);
                        form.querySelector('[data-today-full]')?.classList.toggle('block', !todayHasWindows);
                    }
                    if (todayInput && !todayHasWindows && (todayInput.checked || !form.querySelector('[data-scheduled-date]:checked'))) {
                        todayInput.checked = false;
                        const tomorrow = form.querySelectorAll('[data-scheduled-date]')[1];
                        if (tomorrow) { tomorrow.checked = true; syncScheduleWindows(); }
                    }
                }

                notifyDelivery();
            };

            const syncBilling = () => {
                const type = form.querySelector('[data-billing-type]:checked')?.value ?? 'datos';
                form.querySelector('[data-billing-fields]')?.classList.toggle('hidden', type !== 'datos');
                const billingEmail = form.querySelector('[data-billing-email]');
                const guestEmail = form.querySelector('[data-guest-email]');
                if (billingEmail) {
                    billingEmail.required = type === 'datos';
                    if (!billingEmail.value && guestEmail?.value) billingEmail.value = guestEmail.value;
                }
                form.querySelector('[data-cf-message]')?.classList.toggle('hidden', type !== 'cf');
            };

            const closeTermsModal = () => {
                const modal = document.querySelector('[data-checkout-terms-modal]');
                modal?.classList.add('hidden');
                modal?.classList.remove('flex');
                modal?.setAttribute('aria-hidden', 'true');
                document.body.classList.remove('overflow-hidden');
            };

            document.addEventListener('click', (event) => {
                const openButton = event.target.closest('[data-checkout-terms-open]');
                const closeButton = event.target.closest('[data-checkout-terms-close], [data-checkout-terms-accept]');
                const modalBackdrop = event.target.matches('[data-checkout-terms-modal]');

                if (openButton) {
                    event.preventDefault();
                    const modal = document.querySelector('[data-checkout-terms-modal]');
                    modal?.classList.remove('hidden');
                    modal?.classList.add('flex');
                    modal?.setAttribute('aria-hidden', 'false');
                    document.body.classList.add('overflow-hidden');
                }

                if (closeButton || modalBackdrop) {
                    event.preventDefault();
                    closeTermsModal();
                }
            });

            document.addEventListener('keydown', (event) => {
                if (event.key === 'Escape') {
                    closeTermsModal();
                }
            });

            nextButton.addEventListener('click', () => {
                if (validateStep(currentStep)) {
                    goToStep(currentStep + 1);
                }
            });

            backButton.addEventListener('click', () => goToStep(currentStep - 1));

            jumpers.forEach((button) => {
                button.addEventListener('click', () => {
                    const target = Number(button.dataset.stepJump);
                    const previousComplete = [...Array(target).keys()].every((index) => completed.has(index) || validateStep(index, false));
                    if (target <= currentStep || previousComplete) {
                        goToStep(target);
                    }
                });
            });

            form.addEventListener('input', (event) => {
                if (event.target.matches('input, select, textarea')) {
                    clearFieldError(event.target);
                    validateStep(currentStep, false);
                    syncProgress();
                }
            });

            form.addEventListener('change', (event) => {
                if (event.target.matches('[data-delivery-type]')) syncDeliverySchedule();
                if (event.target.matches('[data-scheduled-date]')) syncScheduleWindows();
                if (event.target.matches('[data-billing-type]')) syncBilling();
                validateStep(currentStep, false);
                syncProgress();
            });

            /* Antes de enviar: si falta aceptar las condiciones, se avisa ahi mismo sin recargar ni perder el paso. */
            form.addEventListener('submit', (event) => {
                const terms = form.querySelector('input[name="acepta_terminos_checkout"]');
                const termsError = form.querySelector('[data-terms-error]');
                if (terms && !terms.checked) {
                    /* En celular el resumen (donde estan los terminos) puede estar plegado. */
                    const resumen = form.querySelector('[data-summary-details]');
                    if (resumen) resumen.open = true;
                    event.preventDefault();
                    event.stopImmediatePropagation();
                    termsError?.classList.remove('hidden');
                    terms.closest('label')?.classList.add('rounded-lg', 'ring-2', 'ring-red-400', 'bg-red-50', 'p-2');
                    terms.closest('label')?.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    terms.focus({ preventScroll: true });
                    return;
                }
                submitButton.disabled = true;
                submitLabel.textContent = 'Procesando...';
            }, true);

            form.addEventListener('change', (event) => {
                if (event.target.matches('input[name="acepta_terminos_checkout"]') && event.target.checked) {
                    form.querySelector('[data-terms-error]')?.classList.add('hidden');
                    event.target.closest('label')?.classList.remove('ring-2', 'ring-red-400', 'bg-red-50', 'p-2');
                }
            });

            syncDeliverySchedule();
            syncBilling();

            /* Si el servidor devolvio errores, se abre el paso donde esta el primer error (no el inicio). */
            const stepForField = (field) => {
                if (/^(guest_|direccion_id|password|crear_cuenta)/.test(field)) return 0;
                if (/^(tipo_entrega|ventana_entrega|programado_|notas)/.test(field)) return 1;
                if (/^(facturacion_tipo|nit_facturacion|razon_social|correo_facturacion)/.test(field)) return 2;
                return 3;
            };
            const serverErrors = JSON.parse(form.dataset.errorFields || '[]');
            const resumeStep = serverErrors.length
                ? Math.min(...serverErrors.map(stepForField))
                : (form.dataset.hasOldInput === '1' ? steps.length - 1 : 0);

            for (let index = 0; index < resumeStep; index += 1) {
                validateStep(index, false);
            }
            validateStep(resumeStep, false);
            goToStep(resumeStep);
        })();
    </script>

    @guest
        <script @nonce>
            const accountToggle = document.querySelector('[data-create-account-toggle]');
            const passwordFields = document.querySelector('[data-account-passwords]');
            const passwordInputs = document.querySelectorAll('[data-account-password-field]');
            const guestEmailInput = document.querySelector('[data-guest-email]');
            const emailOptionalLabel = document.querySelector('[data-email-optional-label]');
            const syncAccountFields = () => {
                const creating = accountToggle?.checked ?? false;
                passwordFields?.classList.toggle('hidden', !creating);
                passwordInputs.forEach((input) => input.required = creating);
                if (guestEmailInput) {
                    guestEmailInput.required = creating;
                    guestEmailInput.toggleAttribute('data-step-required', creating);
                }
                if (emailOptionalLabel) emailOptionalLabel.textContent = creating ? '(para tu cuenta)' : '(opcional)';
                guestEmailInput?.dispatchEvent(new Event('input', { bubbles: true }));
            };
            accountToggle?.addEventListener('change', syncAccountFields);
            syncAccountFields();

            /* Propina y saldo: se envian con el formulario y se reflejan en el resumen. */
            (() => {
                const caja = document.querySelector('[data-propina-box]');
                if (!caja) return;
                const valor = caja.querySelector('[data-propina-valor]');
                const otro = caja.querySelector('[data-propina-otro]');
                const otroInput = otro?.querySelector('input');
                const saldo = document.querySelector('[data-usar-saldo]');
                let espera = null;
                const avisar = () => {
                    clearTimeout(espera);
                    espera = setTimeout(() => window.Livewire?.dispatch('checkout.extras-actualizados', {
                        propina: Number(valor.value || 0),
                        usarSaldo: Boolean(saldo?.checked),
                    }), 250);
                };
                const sincronizar = () => {
                    /* Recoger en tienda no lleva repartidor: no hay propina. */
                    const recoger = document.querySelector('[data-delivery-type]:checked')?.value === 'recoger';
                    caja.classList.toggle('hidden', recoger);
                    if (recoger) caja.querySelector('[data-propina-opcion][value="0"]').checked = true;
                    const elegido = caja.querySelector('[data-propina-opcion]:checked')?.value ?? '0';
                    otro.classList.toggle('hidden', elegido !== 'otro');
                    const monto = elegido === 'otro' ? Number(otroInput.value || 0) : Number(elegido);
                    valor.value = String(Math.max(0, Math.min(500, Math.round(monto * 100) / 100)));
                    avisar();
                };
                caja.addEventListener('change', sincronizar);
                otroInput?.addEventListener('input', sincronizar);
                document.querySelectorAll('[data-delivery-type]').forEach((radio) => radio.addEventListener('change', sincronizar));
                saldo?.addEventListener('change', avisar);
                document.addEventListener('livewire:initialized', sincronizar);
                sincronizar();
            })();

            /* Borrador del checkout: al volver de Terminos o Privacidad se recupera lo escrito (nunca contrasenas). */
            const draftKey = 'atlantia.checkout.borrador';
            const draftForm = document.querySelector('[data-checkout-wizard]');
            const draftFields = () => [...(draftForm?.querySelectorAll('input[name^="guest_"], input[name="crear_cuenta"]') ?? [])];
            try {
                const draft = JSON.parse(sessionStorage.getItem(draftKey) || '{}');
                draftFields().forEach((field) => {
                    if (!(field.name in draft)) return;
                    if (field.type === 'radio') field.checked = field.value === draft[field.name];
                    else if (field.type === 'checkbox') field.checked = Boolean(draft[field.name]);
                    else if (!field.value) field.value = draft[field.name];
                });
            } catch (error) { /* sin almacenamiento disponible */ }
            /* Refresca la vista de "crear cuenta" y la validacion del paso con los datos recuperados. */
            document.querySelector('[data-create-account-toggle]')?.dispatchEvent(new Event('change', { bubbles: true }));
            const saveDraft = () => {
                const data = {};
                draftFields().forEach((field) => {
                    if (field.type === 'radio') { if (field.checked) data[field.name] = field.value; }
                    else if (field.type === 'checkbox') data[field.name] = field.checked;
                    else data[field.name] = field.value;
                });
                try { sessionStorage.setItem(draftKey, JSON.stringify(data)); } catch (error) { /* ignorar */ }
            };
            draftForm?.addEventListener('input', saveDraft);
            draftForm?.addEventListener('change', saveDraft);
            draftForm?.addEventListener('submit', () => { try { sessionStorage.removeItem(draftKey); } catch (error) { /* ignorar */ } });

            /*
             * Cobertura por colonia (mismas reglas que App\Support\CoberturaColonias):
             * - Si la colonia es de otro municipio (ej. Quebrada Seca -> Santo Tomas), se cambia solo.
             * - Si Atlantia no entrega ahi, se muestra un aviso amable con opciones.
             */
            (() => {
                const wizard = document.querySelector('[data-checkout-wizard]');
                const catalogo = JSON.parse(wizard?.dataset.cobertura || '{}');
                const aplica = wizard?.dataset.aplicaCobertura === '1';
                const modal = document.querySelector('[data-cobertura-modal]');
                const colonia = document.querySelector('#guest_zona_barrio');
                const direccion = document.querySelector('#guest_direccion');
                const notaMunicipio = document.querySelector('[data-colonia-municipio-note]');
                const notaExcluida = document.querySelector('[data-colonia-excluida-note]');
                const normalizar = (valor) => String(valor || '').normalize('NFD').replace(/[̀-ͯ]/g, '').toLowerCase().replace(/[^a-z0-9]+/g, ' ').trim();
                const nucleo = (nombre) => normalizar(nombre).replace(/^(barrio|colonia)\s+/, '').replace(/\s+(i|ii|iii)(\s+(y|i|ii|iii))*$/, '').trim();
                const municipioActual = () => document.querySelector('input[name="guest_municipio"]:checked')?.value || '';
                const tipoEntrega = () => document.querySelector('[data-delivery-type]:checked')?.value || 'domicilio';
                const escapar = (texto) => texto.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');

                const excluida = () => {
                    const listas = catalogo[municipioActual()] || {};
                    const textoColonia = ` ${normalizar(colonia?.value)} `;
                    const textoDireccion = ` ${normalizar(direccion?.value)} `;
                    for (const nombre of listas.excluidas || []) {
                        const termino = normalizar(nombre);
                        if (!termino) continue;
                        if (textoColonia.trim() && textoColonia.includes(` ${termino} `)) return nombre;
                        if (new RegExp(`\\b(barrio|colonia|col|bo|bario)\\s+${escapar(termino)}\\b`).test(textoDireccion)) return nombre;
                    }
                    return null;
                };

                const municipioDe = () => {
                    const texto = ` ${normalizar(colonia?.value)} `;
                    if (!texto.trim()) return null;
                    for (const [municipio, listas] of Object.entries(catalogo)) {
                        for (const nombre of listas.cubiertas || []) {
                            const termino = nucleo(nombre);
                            if (termino && texto.includes(` ${termino} `)) return municipio;
                        }
                    }
                    return null;
                };

                const nombreMunicipio = (municipio) => municipio === 'Santo Tomas' ? 'Santo Tomás de Castilla' : municipio;

                const abrirModal = (nombre) => {
                    modal?.querySelectorAll('[data-cobertura-colonia]').forEach((nodo) => nodo.textContent = nombre);
                    modal?.classList.remove('hidden');
                    modal?.classList.add('flex');
                    modal?.setAttribute('aria-hidden', 'false');
                };
                const cerrarModal = () => {
                    modal?.classList.add('hidden');
                    modal?.classList.remove('flex');
                    modal?.setAttribute('aria-hidden', 'true');
                };

                const revisar = (mostrarAviso) => {
                    const destino = municipioDe();
                    if (destino && destino !== municipioActual()) {
                        const radio = document.querySelector(`input[name="guest_municipio"][value="${destino}"]`);
                        if (radio) {
                            radio.checked = true;
                            radio.dispatchEvent(new Event('change', { bubbles: true }));
                            if (notaMunicipio) {
                                notaMunicipio.textContent = `Cambiamos la zona a ${nombreMunicipio(destino)}, porque esa colonia pertenece ahi.`;
                                notaMunicipio.classList.remove('hidden');
                            }
                        }
                    }

                    const nombre = aplica ? excluida() : null;
                    const bloquea = Boolean(nombre) && tipoEntrega() !== 'recoger';
                    if (notaExcluida) {
                        notaExcluida.innerHTML = nombre
                            ? `Atlantia aún no entrega en ${nombre}. <button type="button" class="underline" data-cobertura-ver>Ver opciones</button>`
                            : '';
                        notaExcluida.classList.toggle('hidden', !nombre || !bloquea);
                    }
                    if (bloquea && mostrarAviso) abrirModal(nombre);
                    wizard?.dispatchEvent(new Event('input', { bubbles: true }));
                };

                window.atlantiaCobertura = {
                    bloquea: () => aplica && Boolean(excluida()) && tipoEntrega() !== 'recoger',
                };

                colonia?.addEventListener('change', () => revisar(true));
                direccion?.addEventListener('change', () => revisar(true));
                document.querySelectorAll('input[name="guest_municipio"]').forEach((radio) => radio.addEventListener('change', () => revisar(false)));
                document.querySelectorAll('[data-delivery-type]').forEach((radio) => radio.addEventListener('change', () => revisar(true)));

                document.addEventListener('click', (event) => {
                    if (event.target.closest('[data-cobertura-ver]')) abrirModal(excluida() || 'esta zona');
                    if (event.target.closest('[data-cobertura-cambiar]')) {
                        cerrarModal();
                        const tipo = document.querySelector('[data-delivery-type]:checked');
                        if (tipo && tipo.value !== 'recoger') {
                            document.querySelector('[data-step-jump="0"]')?.click();
                        }
                        (colonia?.value ? colonia : direccion)?.focus();
                        colonia?.select?.();
                    }
                    if (event.target === modal) cerrarModal();
                });
                document.addEventListener('keydown', (event) => {
                    if (event.key === 'Escape') cerrarModal();
                });

                revisar(false);
            })();

            const gpsButton = document.querySelector('[data-guest-gps]');
            const gpsStatus = document.querySelector('[data-guest-gps-status]');
            const latitudeInput = document.querySelector('[data-guest-latitude]');
            const longitudeInput = document.querySelector('[data-guest-longitude]');

            const markGpsReady = (lat, lng, accuracy = null) => {
                if (latitudeInput) latitudeInput.value = lat;
                if (longitudeInput) longitudeInput.value = lng;
                latitudeInput?.dispatchEvent(new Event('input', { bubbles: true }));
                if (gpsStatus) {
                    gpsStatus.textContent = `GPS listo: ${Number(lat).toFixed(6)}, ${Number(lng).toFixed(6)}${accuracy ? `. Precision: ~${accuracy}m.` : ''}`;
                    gpsStatus.className = 'rounded-md bg-emerald-50 px-2 py-1 text-[11px] font-bold leading-4 text-emerald-800';
                }
                if (gpsButton) {
                    gpsButton.textContent = 'Actualizar ubicacion';
                    gpsButton.className = 'w-full rounded-md bg-emerald-600 px-3 py-1.5 text-xs font-black text-white hover:bg-emerald-700';
                    gpsButton.disabled = false;
                }
            };

            if (latitudeInput?.value && longitudeInput?.value) {
                markGpsReady(latitudeInput.value, longitudeInput.value);
            }

            gpsButton?.addEventListener('click', () => {
                if (!navigator.geolocation) {
                    if (gpsStatus) {
                        gpsStatus.textContent = 'Tu navegador no permite obtener ubicacion GPS.';
                        gpsStatus.className = 'rounded-md bg-red-50 px-2 py-1 text-[11px] font-bold leading-4 text-red-700';
                    }
                    return;
                }

                gpsButton.disabled = true;
                gpsButton.textContent = 'Obteniendo ubicacion...';
                navigator.geolocation.getCurrentPosition(
                    (position) => markGpsReady(position.coords.latitude, position.coords.longitude, Math.round(position.coords.accuracy || 0)),
                    () => {
                        if (gpsStatus) {
                            gpsStatus.textContent = 'No pudimos obtener tu ubicacion. Revisa los permisos del navegador.';
                            gpsStatus.className = 'rounded-md bg-red-50 px-2 py-1 text-[11px] font-bold leading-4 text-red-700';
                        }
                        gpsButton.textContent = 'Usar mi ubicacion actual';
                        gpsButton.disabled = false;
                    },
                    { enableHighAccuracy: true, timeout: 15000, maximumAge: 0 }
                );
            });
        </script>
    @endguest
@endpush
