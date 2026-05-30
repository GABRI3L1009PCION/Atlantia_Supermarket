@extends('layouts.marketplace')

@section('content')
    @php
        $stepLabels = ['Datos', 'Entrega', 'Factura', 'Pago'];
    @endphp

    <section class="bg-[#fff8fb] px-3 py-2 sm:px-4 lg:px-6">
        <form
            method="POST"
            action="{{ route('cliente.checkout.store') }}"
            class="checkout-compact mx-auto grid w-full max-w-[1120px] gap-3 lg:grid-cols-[minmax(0,740px)_330px] lg:items-start lg:justify-center"
            data-disable-submit-guard
            data-stripe-checkout
            data-checkout-wizard
            data-stripe-publishable-key="{{ config('services.stripe.publishable_key') }}"
            data-stripe-currency="{{ strtolower(config('services.stripe.currency', 'gtq')) }}"
        >
            @csrf

            <section
                class="mx-auto w-full overflow-hidden rounded-xl border border-atlantia-rose/20 bg-white shadow-xl shadow-atlantia-wine/10"
                role="dialog"
                aria-modal="true"
                aria-labelledby="checkout-modal-title"
            >
                <header class="border-b border-atlantia-rose/15 px-4 py-3">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <p class="text-xs font-black uppercase tracking-normal text-atlantia-wine" data-step-counter>
                                Paso 1 de 4
                            </p>
                            <h1 id="checkout-modal-title" class="text-xl font-black leading-tight text-atlantia-ink">
                                Finalizar compra
                            </h1>
                        </div>
                        <a
                            href="{{ route('cliente.carrito.index') }}"
                            class="rounded-full px-3 py-1 text-xl font-black text-atlantia-ink/45 hover:bg-atlantia-blush hover:text-atlantia-wine"
                            aria-label="Cerrar checkout"
                        >
                            &times;
                        </a>
                    </div>

                    <ol class="mt-3 grid grid-cols-4 gap-2" aria-label="Progreso del checkout">
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
                                <div class="grid gap-x-2 gap-y-1.5 sm:grid-cols-2">
                                    <div>
                                        <label for="guest_nombre" class="text-xs font-black text-atlantia-ink">Nombre completo</label>
                                        <input id="guest_nombre" name="guest_nombre" type="text" value="{{ old('guest_nombre') }}" placeholder="Ej. Juan Perez" required data-step-required class="mt-0.5 w-full rounded-md border border-atlantia-rose/30 px-3 py-1.5 text-sm focus:border-atlantia-wine focus:ring-atlantia-rose">
                                        @error('guest_nombre') <p class="mt-1 text-xs font-bold text-red-600">{{ $message }}</p> @enderror
                                        <p class="mt-1 hidden text-xs font-bold text-red-600" data-field-error></p>
                                    </div>
                                    <div>
                                        <label for="guest_telefono" class="text-xs font-black text-atlantia-ink">Telefono</label>
                                        <input id="guest_telefono" name="guest_telefono" type="tel" value="{{ old('guest_telefono') }}" placeholder="Ej. 55551234" required pattern="^(\+502)?[2-7][0-9]{7}$" data-step-required class="mt-0.5 w-full rounded-md border border-atlantia-rose/30 px-3 py-1.5 text-sm focus:border-atlantia-wine focus:ring-atlantia-rose">
                                        @error('guest_telefono') <p class="mt-1 text-xs font-bold text-red-600">{{ $message }}</p> @enderror
                                        <p class="mt-1 hidden text-xs font-bold text-red-600" data-field-error></p>
                                    </div>
                                    <div class="sm:col-span-2">
                                        <label for="guest_email" class="text-xs font-black text-atlantia-ink">Correo para confirmacion</label>
                                        <input id="guest_email" name="guest_email" type="email" value="{{ old('guest_email') }}" placeholder="ejemplo@correo.com" required data-step-required class="mt-0.5 w-full rounded-md border border-atlantia-rose/30 px-3 py-1.5 text-sm focus:border-atlantia-wine focus:ring-atlantia-rose">
                                        @error('guest_email') <p class="mt-1 text-xs font-bold text-red-600">{{ $message }}</p> @enderror
                                        <p class="mt-1 hidden text-xs font-bold text-red-600" data-field-error></p>
                                    </div>
                                    <div>
                                        <label for="guest_alias" class="text-xs font-black text-atlantia-ink">Tipo de direccion</label>
                                        <select id="guest_alias" name="guest_alias" class="mt-0.5 w-full rounded-md border border-atlantia-rose/30 px-3 py-1.5 text-sm focus:border-atlantia-wine focus:ring-atlantia-rose">
                                            @foreach (['Casa', 'Trabajo', 'Regalo', 'Otro'] as $alias)
                                                <option value="{{ $alias }}" @selected(old('guest_alias', 'Casa') === $alias)>{{ $alias }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div>
                                        <label for="guest_municipio" class="text-xs font-black text-atlantia-ink">Municipio</label>
                                        <select id="guest_municipio" name="guest_municipio" required data-step-required class="mt-0.5 w-full rounded-md border border-atlantia-rose/30 px-3 py-1.5 text-sm focus:border-atlantia-wine focus:ring-atlantia-rose">
                                            @foreach (['Puerto Barrios', 'Santo Tomas', 'Morales', 'Los Amates', 'Livingston', 'El Estor'] as $municipio)
                                                <option value="{{ $municipio }}" @selected(old('guest_municipio', 'Puerto Barrios') === $municipio)>{{ $municipio }}</option>
                                            @endforeach
                                        </select>
                                        @error('guest_municipio') <p class="mt-1 text-xs font-bold text-red-600">{{ $message }}</p> @enderror
                                        <p class="mt-1 hidden text-xs font-bold text-red-600" data-field-error></p>
                                    </div>
                                    <div class="sm:col-span-2">
                                        <label for="guest_direccion" class="text-xs font-black text-atlantia-ink">Direccion completa</label>
                                        <input id="guest_direccion" name="guest_direccion" type="text" value="{{ old('guest_direccion') }}" placeholder="Calle principal, casa #12, frente al parque..." required data-step-required class="mt-0.5 w-full rounded-md border border-atlantia-rose/30 px-3 py-1.5 text-sm focus:border-atlantia-wine focus:ring-atlantia-rose">
                                        @error('guest_direccion') <p class="mt-1 text-xs font-bold text-red-600">{{ $message }}</p> @enderror
                                        <p class="mt-1 hidden text-xs font-bold text-red-600" data-field-error></p>
                                    </div>
                                    <div>
                                        <label for="guest_zona_barrio" class="text-xs font-black text-atlantia-ink">Colonia / barrio <span class="font-normal text-atlantia-ink/45">(opcional)</span></label>
                                        <input id="guest_zona_barrio" name="guest_zona_barrio" type="text" value="{{ old('guest_zona_barrio') }}" placeholder="Ej. Barrio El Centro" class="mt-0.5 w-full rounded-md border border-atlantia-rose/30 px-3 py-1.5 text-sm focus:border-atlantia-wine focus:ring-atlantia-rose">
                                    </div>
                                    <div>
                                        <label class="text-xs font-black text-atlantia-ink">Ubicacion exacta</label>
                                        <input type="hidden" name="guest_latitude" value="{{ old('guest_latitude') }}" data-guest-latitude required data-step-required>
                                        <input type="hidden" name="guest_longitude" value="{{ old('guest_longitude') }}" data-guest-longitude required>
                                        <button type="button" data-guest-gps class="mt-0.5 w-full rounded-md border border-atlantia-rose/35 px-3 py-1.5 text-xs font-black text-atlantia-wine hover:bg-atlantia-blush">
                                            Usar mi ubicacion actual
                                        </button>
                                        <p data-guest-gps-status class="mt-0.5 rounded-md bg-atlantia-blush px-2 py-0.5 text-[10px] font-bold leading-4 text-atlantia-ink/60">
                                            Necesitamos el punto GPS para validar cobertura.
                                        </p>
                                        @error('guest_latitude') <p class="mt-1 text-xs font-bold text-red-600">{{ $message }}</p> @enderror
                                        <p class="mt-1 hidden text-xs font-bold text-red-600" data-field-error></p>
                                    </div>
                                    <div class="sm:col-span-2">
                                        <label for="guest_referencia" class="text-xs font-black text-atlantia-ink">Referencia <span class="font-normal text-atlantia-ink/45">(opcional)</span></label>
                                        <input id="guest_referencia" name="guest_referencia" type="text" value="{{ old('guest_referencia') }}" placeholder="Ej. Porton negro, casa a mano derecha" class="mt-0.5 w-full rounded-md border border-atlantia-rose/30 px-3 py-1.5 text-sm focus:border-atlantia-wine focus:ring-atlantia-rose">
                                    </div>
                                </div>

                                <div class="rounded-lg border border-atlantia-rose/25 bg-atlantia-blush/35 px-3 py-2">
                                    <label class="flex items-start gap-3">
                                        <input type="checkbox" name="crear_cuenta" value="1" @checked(old('crear_cuenta')) class="mt-1 rounded border-atlantia-rose text-atlantia-wine focus:ring-atlantia-rose" data-create-account-toggle>
                                        <span>
                                            <span class="block text-xs font-black text-atlantia-ink">Crear una cuenta para futuras compras</span>
                                            <span class="block text-[11px] text-atlantia-ink/60">Si prefieres comprar rapido, deja esto desmarcado.</span>
                                        </span>
                                    </label>
                                    <div class="mt-4 grid gap-4 sm:grid-cols-2 {{ old('crear_cuenta') ? '' : 'hidden' }}" data-account-passwords>
                                        <div>
                                            <label for="password" class="text-sm font-black text-atlantia-ink">Contrasena</label>
                                            <input id="password" name="password" type="password" autocomplete="new-password" class="mt-2 w-full rounded-md border border-atlantia-rose/30 px-4 py-3 text-sm focus:border-atlantia-wine focus:ring-atlantia-rose" data-account-password-field>
                                            @error('password') <p class="mt-1 text-xs font-bold text-red-600">{{ $message }}</p> @enderror
                                        </div>
                                        <div>
                                            <label for="password_confirmation" class="text-sm font-black text-atlantia-ink">Confirmar contrasena</label>
                                            <input id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" class="mt-2 w-full rounded-md border border-atlantia-rose/30 px-4 py-3 text-sm focus:border-atlantia-wine focus:ring-atlantia-rose" data-account-password-field>
                                        </div>
                                    </div>
                                </div>
                            @endauth
                        </div>
                    </section>

                    <section class="hidden" data-checkout-step="1" data-step-title="Tipo de entrega" data-step-description="Elige como prefieres recibir tu pedido.">
                        <h2 class="text-xl font-black text-atlantia-ink">Tipo de entrega</h2>
                        <p class="mt-1 text-sm text-atlantia-ink/65">Selecciona la opcion que mejor se ajusta al cliente.</p>

                        <div class="mt-5 grid gap-3 sm:grid-cols-3">
                            <label class="checkout-choice text-center">
                                <input type="radio" name="tipo_entrega" value="domicilio" @checked(old('tipo_entrega', 'domicilio') === 'domicilio') class="sr-only" data-delivery-type data-shipping-amount="0">
                                <span class="block text-sm font-bold tracking-normal text-sky-700" aria-hidden="true">DOM</span>
                                <span class="mt-2 block font-bold text-atlantia-ink">Domicilio</span>
                                <span class="mt-1 block text-sm text-atlantia-ink/65">45-60 min</span>
                                <span class="mt-2 block font-bold text-atlantia-wine">Segun zona</span>
                            </label>
                            <label class="checkout-choice text-center">
                                <input type="radio" name="tipo_entrega" value="recoger" @checked(old('tipo_entrega') === 'recoger') class="sr-only" data-delivery-type data-shipping-amount="0">
                                <span class="block text-sm font-bold tracking-normal text-atlantia-wine" aria-hidden="true">TDA</span>
                                <span class="mt-2 block font-bold text-atlantia-ink">Recoger en tienda</span>
                                <span class="mt-1 block text-sm text-atlantia-ink/65">Hoy, desde 3:00 pm</span>
                                <span class="mt-2 block font-bold text-atlantia-wine">Gratis</span>
                            </label>
                            <label class="checkout-choice text-center">
                                <input type="radio" name="tipo_entrega" value="programado" @checked(old('tipo_entrega') === 'programado') class="sr-only" data-delivery-type data-shipping-amount="15">
                                <span class="block text-sm font-bold tracking-normal text-atlantia-wine" aria-hidden="true">PRO</span>
                                <span class="mt-2 block font-bold text-atlantia-ink">Programado</span>
                                <span class="mt-1 block text-sm text-atlantia-ink/65">Elige horario</span>
                                <span class="mt-2 block font-bold text-atlantia-wine">Q 15.00</span>
                            </label>
                        </div>

                        <div class="mt-6" data-delivery-window-panel>
                            <h3 class="text-sm font-bold text-atlantia-ink">Ventana de entrega</h3>
                            <div class="mt-3 grid gap-3 sm:grid-cols-4">
                                @foreach (['2-4 pm', '4-6 pm', '6-8 pm', '8-10 am'] as $ventana)
                                    <label class="checkout-window">
                                        <input type="radio" name="ventana_entrega" value="{{ $ventana }}" @checked(old('ventana_entrega', '4-6 pm') === $ventana) class="sr-only" data-delivery-window>
                                        <span class="block text-xs uppercase text-atlantia-ink/50">{{ $loop->last ? 'Manana' : 'Hoy' }}</span>
                                        {{ $ventana }}
                                    </label>
                                @endforeach
                            </div>
                            @error('ventana_entrega') <p class="mt-2 text-sm font-semibold text-red-700">{{ $message }}</p> @enderror
                        </div>

                        <div class="mt-6 hidden rounded-lg border border-slate-200 bg-slate-50 p-4" data-store-pickup-panel>
                            <p class="text-sm font-black text-atlantia-ink">Recoge en tienda</p>
                            <p class="mt-1 text-sm leading-6 text-atlantia-ink/65">Tu pedido quedara listo para recoger en Atlantia Supermarket. Te avisaremos cuando este preparado.</p>
                        </div>

                        <div class="mt-6 hidden" data-scheduled-delivery-panel>
                            <h3 class="text-sm font-bold text-atlantia-ink">Horario programado</h3>
                            <div class="mt-3 grid gap-4 sm:grid-cols-2">
                                <div>
                                    <label for="programado_fecha" class="text-sm font-bold text-atlantia-ink">Fecha de entrega</label>
                                    <input id="programado_fecha" name="programado_fecha" type="date" value="{{ old('programado_fecha') }}" min="{{ now()->toDateString() }}" max="{{ now()->addDays(14)->toDateString() }}" data-scheduled-date class="mt-2 w-full rounded-md border border-atlantia-rose/30 px-4 py-3 text-sm focus:border-atlantia-wine focus:ring-atlantia-rose">
                                    @error('programado_fecha') <p class="mt-1 text-xs font-bold text-red-600">{{ $message }}</p> @enderror
                                    <p class="mt-1 hidden text-xs font-bold text-red-600" data-field-error></p>
                                </div>
                                <div>
                                    <label for="programado_hora" class="text-sm font-bold text-atlantia-ink">Hora de entrega</label>
                                    <input id="programado_hora" name="programado_hora" type="time" value="{{ old('programado_hora') }}" min="08:00" max="20:00" step="900" data-scheduled-time class="mt-2 w-full rounded-md border border-atlantia-rose/30 px-4 py-3 text-sm focus:border-atlantia-wine focus:ring-atlantia-rose">
                                    @error('programado_hora') <p class="mt-1 text-xs font-bold text-red-600">{{ $message }}</p> @enderror
                                    <p class="mt-1 hidden text-xs font-bold text-red-600" data-field-error></p>
                                </div>
                            </div>
                        </div>

                        <div class="mt-5">
                            <label for="notas" class="text-sm font-bold text-atlantia-ink">Instrucciones para el repartidor <span class="font-normal text-atlantia-ink/55">(opcional)</span></label>
                            <textarea id="notas" name="notas" rows="3" placeholder="Ej. Tocar timbre, dejar con portero, llamar al llegar..." class="mt-2 w-full rounded-md border border-atlantia-rose/30 px-4 py-3 text-sm focus:border-atlantia-wine focus:ring-atlantia-rose">{{ old('notas') }}</textarea>
                        </div>
                    </section>

                    <section class="hidden" data-checkout-step="2" data-step-title="Facturacion FEL" data-step-description="Elige como quieres recibir la factura.">
                        <h2 class="text-xl font-black text-atlantia-ink">Facturacion electronica FEL</h2>
                        <p class="mt-1 text-sm text-atlantia-ink/65">Cada vendedor emitira su propio DTE FEL al confirmar el pedido.</p>

                        <div class="mt-5 grid gap-3 sm:grid-cols-2">
                            <label class="checkout-choice">
                                <input type="radio" name="facturacion_tipo" value="datos" @checked(old('facturacion_tipo', 'datos') === 'datos') class="sr-only" data-billing-type>
                                <span class="font-bold text-atlantia-ink">Factura con mis datos</span>
                                <span class="mt-1 block text-sm text-atlantia-ink/65">Llega por correo al confirmar.</span>
                            </label>
                            <label class="checkout-choice">
                                <input type="radio" name="facturacion_tipo" value="cf" @checked(old('facturacion_tipo') === 'cf') class="sr-only" data-billing-type>
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
                                <input id="correo_facturacion" name="correo_facturacion" type="email" value="{{ old('correo_facturacion', auth()->user()?->email) }}" class="mt-2 w-full rounded-md border border-atlantia-rose/30 px-4 py-3 text-sm focus:border-atlantia-wine focus:ring-atlantia-rose">
                            </div>
                        </div>

                        <div class="mt-5 hidden rounded-lg border border-slate-200 bg-slate-50 p-4" data-cf-message>
                            <p class="text-sm font-black text-atlantia-ink">Consumidor Final</p>
                            <p class="mt-1 text-sm leading-6 text-atlantia-ink/65">Emitiremos el documento como consumidor final. No necesitas completar datos fiscales.</p>
                        </div>
                    </section>

                    <section class="hidden" data-checkout-step="3" data-step-title="Metodo de pago" data-step-description="Selecciona como pagaras tu compra.">
                        <livewire:checkout.formulario-pago />
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
                        <span data-submit-label>Confirmar y pagar</span>
                    </button>
                </footer>
            </section>

            <aside class="mx-auto w-full max-w-[640px] lg:sticky lg:top-3 lg:h-fit lg:max-w-none" data-checkout-summary>
                <details class="lg:block" open>
                    <summary class="cursor-pointer rounded-t-lg border border-atlantia-rose/20 bg-white px-5 py-4 text-sm font-black text-atlantia-ink lg:hidden">
                        Resumen del pedido
                    </summary>
                    <div class="border-x border-b border-atlantia-rose/20 bg-white lg:border-0 lg:bg-transparent">
                        <livewire:checkout.resumen-multivendedor />
                    </div>
                </details>
            </aside>
        </form>
    </section>
@endsection

@push('scripts')
    <script src="https://js.stripe.com/v3/" @nonce></script>
    <script @nonce>
        (() => {
            const form = document.querySelector('[data-checkout-wizard]');
            if (!form) return;

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

            const currentTotal = () => {
                const totalNode = document.querySelector('[data-checkout-total]');
                return totalNode?.dataset.checkoutTotal || totalNode?.textContent?.replace(/[^\d.]/g, '') || '';
            };

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

                if (index === 1 && form.querySelector('[data-delivery-type]:checked')?.value === 'programado') {
                    [form.querySelector('[data-scheduled-date]'), form.querySelector('[data-scheduled-time]')].forEach((field) => {
                        if (field && !field.value) {
                            valid = false;
                            if (report) showFieldError(field, 'Este campo es obligatorio.');
                        }
                    });
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
                syncProgress();
            };

            const syncDeliverySchedule = () => {
                const selectedType = form.querySelector('[data-delivery-type]:checked')?.value ?? 'domicilio';
                const isScheduled = selectedType === 'programado';
                const isPickup = selectedType === 'recoger';
                const shippingInput = form.querySelector('input[name="envio"]');
                const checkedType = form.querySelector('[data-delivery-type]:checked');
                const amount = selectedType === 'domicilio'
                    ? (document.querySelector('[data-checkout-shipping]')?.dataset.baseShipping ?? document.querySelector('[data-checkout-shipping]')?.dataset.checkoutShipping ?? 0)
                    : checkedType?.dataset.shippingAmount;

                form.querySelector('[data-delivery-window-panel]')?.classList.toggle('hidden', isScheduled || isPickup);
                form.querySelector('[data-scheduled-delivery-panel]')?.classList.toggle('hidden', !isScheduled);
                form.querySelector('[data-store-pickup-panel]')?.classList.toggle('hidden', !isPickup);
                form.querySelectorAll('[data-delivery-window]').forEach((input) => input.disabled = isScheduled || isPickup);

                const date = form.querySelector('[data-scheduled-date]');
                const time = form.querySelector('[data-scheduled-time]');
                if (date) {
                    date.disabled = !isScheduled;
                    date.required = isScheduled;
                }
                if (time) {
                    time.disabled = !isScheduled;
                    time.required = isScheduled;
                }
                if (shippingInput && amount !== undefined) {
                    shippingInput.value = amount;
                }
                syncSummaryTotals(amount);
            };

            const syncBilling = () => {
                const type = form.querySelector('[data-billing-type]:checked')?.value ?? 'datos';
                form.querySelector('[data-billing-fields]')?.classList.toggle('hidden', type !== 'datos');
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
                if (event.target.matches('[data-billing-type]')) syncBilling();
                validateStep(currentStep, false);
                syncProgress();
            });

            form.addEventListener('submit', () => {
                submitButton.disabled = true;
                submitLabel.textContent = 'Procesando...';
            });

            syncDeliverySchedule();
            syncBilling();
            validateStep(0, false);
            syncProgress();
        })();
    </script>

    @guest
        <script @nonce>
            const accountToggle = document.querySelector('[data-create-account-toggle]');
            const passwordFields = document.querySelector('[data-account-passwords]');
            const passwordInputs = document.querySelectorAll('[data-account-password-field]');
            accountToggle?.addEventListener('change', () => {
                passwordFields?.classList.toggle('hidden', !accountToggle.checked);
                passwordInputs.forEach((input) => input.required = accountToggle.checked);
            });
            passwordInputs.forEach((input) => input.required = accountToggle?.checked ?? false);

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
                    gpsStatus.className = 'mt-2 rounded-md bg-emerald-50 px-3 py-2 text-xs font-bold text-emerald-800';
                }
                if (gpsButton) {
                    gpsButton.textContent = 'Actualizar ubicacion';
                    gpsButton.className = 'mt-2 w-full rounded-md bg-emerald-600 px-4 py-3 text-sm font-black text-white hover:bg-emerald-700';
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
                        gpsStatus.className = 'mt-2 rounded-md bg-red-50 px-3 py-2 text-xs font-bold text-red-700';
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
                            gpsStatus.className = 'mt-2 rounded-md bg-red-50 px-3 py-2 text-xs font-bold text-red-700';
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
