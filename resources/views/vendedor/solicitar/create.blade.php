@extends('layouts.marketplace', ['title' => 'Solicitud de vendedor | Atlantia Store'])

@section('content')
@php
    $steps = [
        1 => ['title' => 'Personal', 'subtitle' => 'Datos para validar tu identidad.'],
        2 => ['title' => 'Documentos', 'subtitle' => 'Documento de identidad claro y legible.'],
        3 => ['title' => 'Negocio', 'subtitle' => 'Cómo se verá tu tienda en Atlantia.'],
        4 => ['title' => 'Fiscal', 'subtitle' => 'Solo aplica si tienes NIT registrado.'],
        5 => ['title' => 'Banco', 'subtitle' => 'Cuenta donde depositaremos tus ventas.'],
        6 => ['title' => 'Pagos', 'subtitle' => 'Preferencias de pago y liquidación.'],
        7 => ['title' => 'Términos', 'subtitle' => 'Confirmaciones finales.'],
    ];
@endphp

<section class="bg-atlantia-cream px-4 py-4 sm:px-6 lg:px-8">
    <div class="mx-auto max-w-4xl">
        <div class="rounded-2xl border border-atlantia-rose/25 bg-white shadow-sm">
            {{-- Encabezado compacto --}}
            <div class="flex flex-wrap items-center justify-between gap-3 border-b border-atlantia-rose/15 px-5 py-4 sm:px-7">
                <div class="min-w-0">
                    <h1 class="text-2xl font-black leading-tight text-atlantia-ink">Solicitud para vender</h1>
                    <p class="mt-0.5 text-sm text-atlantia-ink/60">Toma unos 10 minutos. Si cumples los requisitos, tu tienda se aprueba al terminar.</p>
                    @php
                        $planSolicitud = old('seller_plan', request('plan'));
                        $datosPlanSolicitud = array_key_exists((string) $planSolicitud, \App\Services\Monetizacion\PlanesVendedorService::contratables()) ? \App\Services\Monetizacion\PlanesVendedorService::datos($planSolicitud) : null;
                    @endphp
                    @if ($datosPlanSolicitud)
                        <p class="mt-2 inline-flex flex-wrap items-center gap-x-2 rounded-lg bg-atlantia-blush px-3 py-1.5 text-xs text-atlantia-ink" data-plan-solicitud>
                            <strong class="text-atlantia-wine">Plan {{ $datosPlanSolicitud['nombre'] }}{{ (float) ($datosPlanSolicitud['precio'] ?? 0) > 0 ? ' · Q'.number_format((float) $datosPlanSolicitud['precio'], 0).'/mes' : ' · Q0/mes + '.rtrim(rtrim(number_format((float) $datosPlanSolicitud['comision'], 2), '0'), '.').' % por venta' }}</strong>
                            <span>{{ (float) ($datosPlanSolicitud['precio'] ?? 0) > 0 ? 'Al terminar te llega un correo para entrar a tu panel, y ahí lo activas con tarjeta.' : 'Sin mensualidad: solo pagas cuando vendes.' }}</span>
                            <a href="{{ route('vende') }}#planes" class="font-bold text-atlantia-wine underline">Cambiar</a>
                        </p>
                    @endif
                </div>
                <a href="{{ route('home') }}" class="inline-flex items-center justify-center rounded-lg border border-atlantia-rose/30 bg-white px-4 py-2 text-sm font-black text-atlantia-wine transition hover:bg-atlantia-blush" data-cancel-application>
                    Cancelar
                </a>
            </div>

            @if ($errors->any())
                <div class="mx-5 mt-4 rounded-xl border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-800 sm:mx-7">
                    Hay campos que necesitan revisión. Corrígelos y vuelve a intentar.
                </div>
            @endif

            {{-- Pasos y avance en una sola franja --}}
            <div class="px-5 pt-4 sm:px-7">
                <div class="flex gap-1.5 overflow-x-auto [scrollbar-width:none] [&::-webkit-scrollbar]:hidden">
                    @foreach ($steps as $number => $step)
                        <button type="button" class="flex min-w-[7.25rem] flex-1 items-center gap-1.5 rounded-full border border-atlantia-rose/25 bg-white px-2 py-1.5 text-left transition hover:border-atlantia-wine/50" data-step-jump="{{ $number }}" title="{{ $step['subtitle'] }}">
                            <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-atlantia-blush text-[11px] font-black text-atlantia-wine">{{ $number }}</span>
                            <span class="min-w-0 truncate text-xs font-black text-atlantia-ink">{{ $step['title'] }}</span>
                        </button>
                    @endforeach
                </div>
                <div class="mt-2.5 flex items-center gap-3">
                    <div class="h-1.5 flex-1 rounded-full bg-atlantia-blush">
                        <div class="h-1.5 rounded-full bg-atlantia-wine transition-all duration-300" style="width: 14%" data-progress-bar></div>
                    </div>
                    <span class="shrink-0 text-[11px] font-black text-atlantia-wine"><span data-progress-label>Paso 1 de 7</span> · <span data-progress-percent>14%</span></span>
                </div>
            </div>

            <form class="px-5 pb-5 pt-4 sm:px-7" method="POST" action="{{ route('vendedor.solicitar.store') }}" enctype="multipart/form-data" data-vendor-application-form novalidate>
                @csrf

                <div class="space-y-6">
                    <section class="pt-1" data-step-panel="1">
                        <div class="flex flex-wrap items-baseline gap-x-3 gap-y-0.5"><h2 class="text-lg font-black text-atlantia-ink">Información personal</h2><p class="text-sm text-atlantia-ink/55">Cómo aparece en tu documento de identidad.</p></div>
                        <div class="mt-3 grid gap-x-5 gap-y-3 md:grid-cols-2">
                            <x-form-field name="name" label="Nombre completo" help="Cómo aparece en tu documento de identidad">
                                <input required minlength="5" maxlength="100" name="name" value="{{ old('name') }}" placeholder="Ej: Daniela Maria Escalante Moreno" class="w-full rounded-md border border-atlantia-rose/35 bg-white px-3 py-2 text-sm text-atlantia-ink outline-none transition focus:border-atlantia-wine focus:ring-2 focus:ring-atlantia-rose/20">
                            </x-form-field>
                            <x-form-field name="email" label="Correo electrónico" help="Usaremos este correo para tu cuenta y comunicaciones">
                                <input required type="email" name="email" value="{{ old('email') }}" placeholder="tu@email.com" class="w-full rounded-md border border-atlantia-rose/35 bg-white px-3 py-2 text-sm text-atlantia-ink outline-none transition focus:border-atlantia-wine focus:ring-2 focus:ring-atlantia-rose/20">
                            </x-form-field>
                            <x-form-field name="phone" label="Teléfono" help="Formato Guatemala: +502 XXXX XXXX">
                                <input required name="phone" value="{{ old('phone', '+502 ') }}" placeholder="+502 XXXX XXXX" data-phone-mask class="w-full rounded-md border border-atlantia-rose/35 bg-white px-3 py-2 text-sm text-atlantia-ink outline-none transition focus:border-atlantia-wine focus:ring-2 focus:ring-atlantia-rose/20">
                            </x-form-field>
                            <x-form-field name="birthdate" label="Fecha de nacimiento" help="Debes ser mayor de 18 años para vender">
                                <input required type="date" name="birthdate" value="{{ old('birthdate') }}" class="w-full rounded-md border border-atlantia-rose/35 bg-white px-3 py-2 text-sm text-atlantia-ink outline-none transition focus:border-atlantia-wine focus:ring-2 focus:ring-atlantia-rose/20">
                            </x-form-field>
                            <x-form-field name="gender" label="Género" help="Opcional">
                                <select name="gender" class="w-full rounded-md border border-atlantia-rose/35 bg-white px-3 py-2 text-sm text-atlantia-ink outline-none transition focus:border-atlantia-wine focus:ring-2 focus:ring-atlantia-rose/20">
                                    <option value="">Seleccionar</option>
                                    <option value="masculino" @selected(old('gender') === 'masculino')>Masculino</option>
                                    <option value="femenino" @selected(old('gender') === 'femenino')>Femenino</option>
                                    <option value="prefiero_no_decir" @selected(old('gender') === 'prefiero_no_decir')>Prefiero no decir</option>
                                </select>
                            </x-form-field>
                        </div>
                        <div class="mt-4 rounded-xl border border-atlantia-rose/15 bg-atlantia-cream/45 p-4">
                            <h3 class="text-base font-black text-atlantia-wine">¿Dónde vives?</h3>
                            <p class="mt-1 text-sm text-atlantia-ink/65">Por si necesitamos verificar tu identidad. Izabal, Guatemala.</p>
                            <input type="hidden" name="address_departamento" value="Izabal">
                            <div class="mt-3 grid gap-x-4 gap-y-3 md:grid-cols-[auto_1fr]">
                                <x-form-field name="address_municipio" label="Municipio">
                                    <div class="flex flex-wrap gap-2">
                                        @foreach ($municipios as $municipio)
                                            <label class="flex cursor-pointer items-center rounded-lg border border-atlantia-rose/25 bg-white px-3 py-2 text-sm font-bold text-atlantia-ink has-[:checked]:border-atlantia-wine has-[:checked]:bg-atlantia-blush">
                                                <input required type="radio" name="address_municipio" value="{{ $municipio }}" class="mr-2 accent-atlantia-wine" @checked(old('address_municipio', $municipios[0] ?? null) === $municipio)> {{ $nombresMunicipio[$municipio] ?? $municipio }}
                                            </label>
                                        @endforeach
                                    </div>
                                </x-form-field>
                                <x-form-field name="address_colonia" label="Colonia o barrio"><input required name="address_colonia" value="{{ old('address_colonia') }}" placeholder="Ej. Barrio El Centro" list="colonias-solicitud" autocomplete="off" class="field-input !px-3 !py-2"></x-form-field>
                                <div class="md:col-span-2">
                                    <x-form-field name="address_street" label="Dirección y referencias" help="Calle, avenida o callejón, y algo que ayude a encontrar tu casa.">
                                        <input required name="address_street" value="{{ old('address_street') }}" placeholder="Ej. 8a calle entre 5a y 6a avenida, casa celeste frente a la tienda" class="field-input !px-3 !py-2">
                                    </x-form-field>
                                </div>
                            </div>
                            <datalist id="colonias-solicitud">
                                @foreach ($coloniasSugeridas as $colonia)
                                    <option value="{{ $colonia }}"></option>
                                @endforeach
                            </datalist>
                        </div>
                    </section>

                    <section class="pt-1" data-step-panel="2" hidden>
                        <div class="flex flex-wrap items-baseline gap-x-3 gap-y-0.5"><h2 class="text-lg font-black text-atlantia-ink">Documentos de identidad</h2><p class="text-sm text-atlantia-ink/55">Sube fotos claras. Deben ser legibles.</p></div>
                        <div class="mt-3 grid gap-x-5 gap-y-3 md:grid-cols-2">
                            <x-form-field name="document_type" label="Tipo de documento" help="Selecciona el documento que vas a usar para tu cuenta">
                                <div class="grid gap-2 sm:grid-cols-3">
                                    @foreach (['dpi' => 'DPI', 'nit' => 'NIT', 'pasaporte' => 'Pasaporte'] as $value => $label)
                                        <label class="rounded-xl border border-atlantia-rose/25 bg-white px-3 py-2.5 text-sm font-black text-atlantia-ink transition has-[:checked]:border-atlantia-wine has-[:checked]:bg-atlantia-blush">
                                            <input required type="radio" name="document_type" value="{{ $value }}" class="mr-2 accent-atlantia-wine" @checked(old('document_type', 'dpi') === $value)> {{ $label }}
                                        </label>
                                    @endforeach
                                </div>
                            </x-form-field>
                            <x-form-field name="document_number" label="Número de documento" help="Tal como aparece en tu documento">
                                <input required name="document_number" value="{{ old('document_number') }}" placeholder="XXXXXXXX-XXXX" class="field-input !px-3 !py-2">
                            </x-form-field>
                            <x-form-field name="document_front" label="Documento frente" help="JPG, PNG o PDF. Máximo 5MB.">
                                <input required type="file" name="document_front" accept=".jpg,.jpeg,.png,.pdf" class="file-input">
                            </x-form-field>
                            <x-form-field name="document_back" label="Documento reverso" help="JPG, PNG o PDF. Máximo 5MB.">
                                <input required type="file" name="document_back" accept=".jpg,.jpeg,.png,.pdf" class="file-input">
                            </x-form-field>
                        </div>
                    </section>

                    <section class="pt-1" data-step-panel="3" hidden>
                        <div class="flex flex-wrap items-baseline gap-x-3 gap-y-0.5"><h2 class="text-lg font-black text-atlantia-ink">Información del negocio</h2><p class="text-sm text-atlantia-ink/55">Esta información será visible para tus clientes.</p></div>
                        <div class="mt-3 grid gap-x-5 gap-y-3 md:grid-cols-2">
                            <x-form-field name="business_name" label="Nombre del negocio" help="Cómo aparecera en tu tienda dentro de Atlantia">
                                <input required minlength="3" maxlength="100" name="business_name" value="{{ old('business_name') }}" placeholder="Ej: La Pastelería Daniela" class="field-input !px-3 !py-2">
                            </x-form-field>
                            <x-form-field name="business_category" label="Categoría de productos" help="En qué categoría entra principalmente tu negocio">
                                <select required name="business_category" data-business-category class="field-input !px-3 !py-2">
                                    <option value="">Seleccionar</option>
                                    @foreach ($categories as $value => $label)
                                        <option value="{{ $value }}" @selected(old('business_category') === $value)>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </x-form-field>
                            <x-form-field name="business_category_other" label="Especificar categoría" help="Solo si seleccionaste Otro">
                                <input name="business_category_other" value="{{ old('business_category_other') }}" placeholder="Describe tu categoría" data-other-category class="field-input !px-3 !py-2" disabled>
                            </x-form-field>
                            <x-form-field name="business_logo" label="Logo o foto del negocio" help="JPG o PNG. Recomendado 500x500px. Máximo 5MB.">
                                <input required type="file" name="business_logo" accept=".jpg,.jpeg,.png" class="file-input">
                            </x-form-field>
                            <x-form-field name="business_description" label="Descripción del negocio" help="Describe qué vendes, tu especialidad y qué te diferencia">
                                <textarea required maxlength="500" name="business_description" rows="3" placeholder="Describe brevemente qué vendes..." data-counter-target="business-description-counter" class="field-input !px-3 !py-2">{{ old('business_description') }}</textarea>
                                <p class="mt-1 text-xs font-bold text-atlantia-ink/55"><span id="business-description-counter">0</span>/500 caracteres</p>
                            </x-form-field>
                            <x-form-field name="has_nit" label="¿Tienes NIT registrado?" help="Si no tienes NIT puedes registrarte como Persona Natural">
                                <div class="grid gap-2 sm:grid-cols-2">
                                    <label class="rounded-xl border border-atlantia-rose/25 bg-white px-3 py-2.5 text-sm font-black text-atlantia-ink transition has-[:checked]:border-atlantia-wine has-[:checked]:bg-atlantia-blush">
                                        <input required type="radio" name="has_nit" value="1" class="mr-2 accent-atlantia-wine" @checked(old('has_nit') === '1') data-has-nit> Sí tengo NIT
                                    </label>
                                    <label class="rounded-xl border border-atlantia-rose/25 bg-white px-3 py-2.5 text-sm font-black text-atlantia-ink transition has-[:checked]:border-atlantia-wine has-[:checked]:bg-atlantia-blush">
                                        <input required type="radio" name="has_nit" value="0" class="mr-2 accent-atlantia-wine" @checked(old('has_nit', '0') === '0') data-has-nit> Persona natural
                                    </label>
                                </div>
                            </x-form-field>
                        </div>
                        <div class="mt-4 rounded-xl border border-atlantia-rose/20 bg-atlantia-cream/45 p-3">
                            <div class="flex flex-wrap items-baseline justify-between gap-x-3">
                                <h3 class="text-base font-black text-atlantia-wine">Elige tu plan mensual</h3>
                                <p class="text-xs text-atlantia-ink/55">Atlantia confirma estas condiciones al aprobar.</p>
                                @if (\App\Services\Monetizacion\PruebaAperturaService::vigente())
                                    <p class="mt-2 rounded-lg bg-emerald-50 px-3 py-2 text-xs font-bold text-emerald-800 ring-1 ring-emerald-200" data-aviso-apertura>
                                        Promoción de apertura: al aprobar tu tienda usas el plan que elijas {{ \App\Services\Monetizacion\PruebaAperturaService::dias() }} días gratis, sin tarjeta. En el plan Inicial son {{ \App\Services\Monetizacion\PruebaAperturaService::dias() }} días sin la comisión de Atlantia. La tarifa de la pasarela por pagos con tarjeta sí se cobra.
                                    </p>
                                @endif
                            </div>
                            <div class="mt-2 grid gap-2 sm:grid-cols-2 lg:grid-cols-3">
                                @foreach ($plans as $value => $plan)
                                    {{-- Tarjeta compacta: el detalle completo aparece al pasar el cursor --}}
                                    <label class="cursor-pointer rounded-xl border border-atlantia-rose/25 bg-white px-3 py-2 text-sm text-atlantia-ink transition hover:border-atlantia-wine/45 has-[:checked]:border-atlantia-wine has-[:checked]:bg-atlantia-blush" title="{{ $plan['description'] }} · {{ $plan['payout'] }} · {{ $plan['support'] }} · {{ $plan['analytics'] }}">
                                        <input required type="radio" name="seller_plan" value="{{ $value }}" class="sr-only" @checked(old('seller_plan', request('plan', config('monetizacion.plan_base', 'inicial'))) === $value)>
                                        <span class="flex items-baseline justify-between gap-2">
                                            <span class="font-black text-atlantia-ink">{{ $plan['name'] }}</span>
                                            <span class="text-lg font-black text-atlantia-wine">{{ 'Q' . number_format($plan['price'], 0) }}<span class="text-[11px] text-atlantia-ink/50">/mes</span></span>
                                        </span>
                                        <span class="block text-xs font-black text-atlantia-wine">{{ $plan['commission'] > 0 ? '+ '.$plan['commission'].'% por venta' : 'Sin comisión por venta' }}</span>
                                        <span class="mt-0.5 block text-[11px] font-bold leading-snug text-atlantia-ink/55">{{ $plan['products'] }} · {{ $plan['users'] }}</span>
                                    </label>
                                @endforeach
                            </div>
                        </div>
                    </section>

                    <section class="pt-1" data-step-panel="4" hidden>
                        <div class="flex flex-wrap items-baseline gap-x-3 gap-y-0.5"><h2 class="text-lg font-black text-atlantia-ink">Información fiscal</h2><p class="text-sm text-atlantia-ink/55">Completa esta parte si cuentas con NIT registrado.</p></div>
                        <div class="mt-4 rounded-xl border border-atlantia-rose/20 bg-white px-3 py-2 text-sm text-atlantia-ink/70" data-natural-message>
                            Si te registras como Persona Natural, puedes continuar sin NIT. Atlantia revisara tu solicitud con tu documento principal.
                        </div>
                        <div class="mt-3 grid gap-x-5 gap-y-3 md:grid-cols-2" data-nit-fields>
                            <x-form-field name="nit_number" label="NIT" help="Tu numero NIT registrado con SAT">
                                <input name="nit_number" value="{{ old('nit_number') }}" placeholder="XXXXXXX-X" class="field-input !px-3 !py-2">
                            </x-form-field>
                            <x-form-field name="razon_social" label="Razón social" help="Nombre de la empresa como aparece en SAT">
                                <input name="razon_social" value="{{ old('razon_social') }}" placeholder="Nombre registrado en SAT" class="field-input !px-3 !py-2">
                            </x-form-field>
                            <x-form-field name="regimen_sat" label="Régimen tributario" help="Bajo qué regimen estas registrado con SAT">
                                <select name="regimen_sat" class="field-input !px-3 !py-2">
                                    <option value="">Seleccionar</option>
                                    <option value="ordinario" @selected(old('regimen_sat') === 'ordinario')>Regimen Ordinario</option>
                                    <option value="simplificado" @selected(old('regimen_sat') === 'simplificado')>Regimen Simplificado</option>
                                    <option value="otro" @selected(old('regimen_sat') === 'otro')>Otro</option>
                                </select>
                            </x-form-field>
                            <x-form-field name="nit_file" label="Fotocopia de NIT/RIT" help="JPG, PNG o PDF. Máximo 5MB.">
                                <input type="file" name="nit_file" accept=".jpg,.jpeg,.png,.pdf" class="file-input">
                            </x-form-field>
                            <x-form-field name="business_street" label="Dirección del negocio (si no es tu casa)"><input name="business_street" value="{{ old('business_street') }}" placeholder="Ej. Local 4, Mercado Municipal" class="field-input !px-3 !py-2"></x-form-field>
                            <x-form-field name="business_municipio" label="Municipio del negocio">
                                <select name="business_municipio" class="field-input !px-3 !py-2">
                                    <option value="">El mismo donde vivo</option>
                                    @foreach ($municipios as $municipio)
                                        <option value="{{ $municipio }}" @selected(old('business_municipio') === $municipio)>{{ $nombresMunicipio[$municipio] ?? $municipio }}</option>
                                    @endforeach
                                </select>
                            </x-form-field>
                        </div>
                    </section>

                    <section class="pt-1" data-step-panel="5" hidden>
                        <div class="flex flex-wrap items-baseline gap-x-3 gap-y-0.5"><h2 class="text-lg font-black text-atlantia-ink">Información bancaria</h2><p class="text-sm text-atlantia-ink/55">Verificaremos esta cuenta antes de liquidar pagos.</p></div>
                        <div class="mt-3 grid gap-x-5 gap-y-3 md:grid-cols-2">
                            <x-form-field name="bank" label="Banco" help="Banco donde te depositaremos tus ventas">
                                <select required name="bank" class="field-input !px-3 !py-2">
                                    <option value="">Seleccionar</option>
                                    @foreach ($banks as $bank)
                                        <option value="{{ $bank }}" @selected(old('bank') === $bank)>{{ $bank }}</option>
                                    @endforeach
                                </select>
                            </x-form-field>
                            <x-form-field name="account_type" label="Tipo de cuenta" help="Tipo de cuenta bancaria">
                                <div class="grid gap-2 sm:grid-cols-2">
                                    <label class="rounded-xl border border-atlantia-rose/25 bg-white px-3 py-2.5 text-sm font-black text-atlantia-ink transition has-[:checked]:border-atlantia-wine has-[:checked]:bg-atlantia-blush">
                                        <input required type="radio" name="account_type" value="ahorros" class="mr-2 accent-atlantia-wine" @checked(old('account_type', 'ahorros') === 'ahorros')> Cuenta de ahorros
                                    </label>
                                    <label class="rounded-xl border border-atlantia-rose/25 bg-white px-3 py-2.5 text-sm font-black text-atlantia-ink transition has-[:checked]:border-atlantia-wine has-[:checked]:bg-atlantia-blush">
                                        <input required type="radio" name="account_type" value="corriente" class="mr-2 accent-atlantia-wine" @checked(old('account_type') === 'corriente')> Cuenta corriente
                                    </label>
                                </div>
                            </x-form-field>
                            <x-form-field name="account_number" label="Número de cuenta" help="Número completo de tu cuenta">
                                <input required name="account_number" value="{{ old('account_number') }}" placeholder="XXXXXXXXXXXXXXXX" class="field-input !px-3 !py-2">
                            </x-form-field>
                            <x-form-field name="account_holder" label="Nombre del titular" help="Debe coincidir exactamente con la cuenta">
                                <input required minlength="5" name="account_holder" value="{{ old('account_holder') }}" placeholder="Nombre exacto del titular" class="field-input !px-3 !py-2">
                            </x-form-field>
                            <x-form-field name="bank_proof" label="Comprobante bancario" help="Estado de cuenta reciente o libreta. JPG, PNG o PDF. Máximo 5MB.">
                                <input required type="file" name="bank_proof" accept=".jpg,.jpeg,.png,.pdf" class="file-input">
                            </x-form-field>
                        </div>
                    </section>

                    <section class="pt-1" data-step-panel="6" hidden>
                        <div class="flex flex-wrap items-baseline gap-x-3 gap-y-0.5"><h2 class="text-lg font-black text-atlantia-ink">Información de pago</h2><p class="text-sm text-atlantia-ink/55">Indica cómo prefieres recibir tus pagos.</p></div>
                        <div class="mt-3 grid gap-x-5 gap-y-3 md:grid-cols-2">
                            <x-form-field name="payment_frequency" label="¿Cada cuánto quieres recibir tu dinero?" help="Sin costo. Puedes cambiarlo cuando quieras desde tu panel.">
                                <div class="grid gap-2">
                                    @foreach (\App\Services\Vendedores\LiquidacionesVendedorService::FRECUENCIAS as $value => $label)
                                        <label class="rounded-xl border border-atlantia-rose/25 bg-white px-3 py-2.5 text-sm font-black text-atlantia-ink transition has-[:checked]:border-atlantia-wine has-[:checked]:bg-atlantia-blush">
                                            <input required type="radio" name="payment_frequency" value="{{ $value }}" class="mr-2 accent-atlantia-wine" @checked(old('payment_frequency', 'semanal') === $value)> {{ $label }}
                                        </label>
                                    @endforeach
                                </div>
                            </x-form-field>
                            <x-form-field name="preferred_payment_method" label="Método de pago preferido" help="Cómo prefieres recibir tus pagos">
                                <div class="grid gap-2">
                                    @foreach (['transferencia' => 'Transferencia bancaria (recomendado)', 'deposito' => 'Depósito en efectivo (previa validación)'] as $value => $label)
                                        <label class="rounded-xl border border-atlantia-rose/25 bg-white px-3 py-2.5 text-sm font-black text-atlantia-ink transition has-[:checked]:border-atlantia-wine has-[:checked]:bg-atlantia-blush">
                                            <input required type="radio" name="preferred_payment_method" value="{{ $value }}" class="mr-2 accent-atlantia-wine" @checked(old('preferred_payment_method', 'transferencia') === $value)> {{ $label }}
                                        </label>
                                    @endforeach
                                </div>
                            </x-form-field>
                        </div>
                    </section>

                    <section class="pt-1" data-step-panel="7" hidden>
                        <div class="flex flex-wrap items-baseline gap-x-3 gap-y-0.5"><h2 class="text-lg font-black text-atlantia-ink">Términos y condiciones</h2><p class="text-sm text-atlantia-ink/55">Últimas confirmaciones antes de enviar.</p></div>
                        <div class="mt-3 space-y-2">
                            <label class="flex gap-3 rounded-xl border border-atlantia-rose/25 bg-white px-3 py-2.5 text-sm text-atlantia-ink transition hover:border-atlantia-wine/45">
                                <input required type="checkbox" name="terms" value="1" class="mt-1 accent-atlantia-wine" @checked(old('terms'))>
                                <span><strong>Acepto los <a href="{{ route('legal.terminos') }}#comercios" class="font-bold text-atlantia-wine underline decoration-atlantia-wine/40 underline-offset-2 hover:text-atlantia-wine-700" target="_blank" rel="noopener">Términos y Condiciones</a> de Atlantia Store, incluidas las condiciones para comercios afiliados.</strong><br><span class="text-atlantia-ink/65">Respondes por la calidad, el vencimiento y la factura FEL de tus productos, y por cumplir los pedidos que aceptes.</span></span>
                            </label>
                            <label class="flex gap-3 rounded-xl border border-atlantia-rose/25 bg-white px-3 py-2.5 text-sm text-atlantia-ink transition hover:border-atlantia-wine/45">
                                <input required type="checkbox" name="truth" value="1" class="mt-1 accent-atlantia-wine" @checked(old('truth'))>
                                <span><strong>Certifico que todos los datos proporcionados son verídicos y completos.</strong><br><span class="text-atlantia-ink/65">Proporcionar información falsa puede resultar en rechazo o sancion.</span></span>
                            </label>
                            <label class="flex gap-3 rounded-xl border border-atlantia-rose/25 bg-white px-3 py-2.5 text-sm text-atlantia-ink transition hover:border-atlantia-wine/45">
                                <input required type="checkbox" name="data_consent" value="1" class="mt-1 accent-atlantia-wine" @checked(old('data_consent'))>
                                <span><strong>Autorizo a Atlantia a procesar mis datos personales según su <a href="{{ route('legal.privacidad') }}" class="font-bold text-atlantia-wine underline decoration-atlantia-wine/40 underline-offset-2 hover:text-atlantia-wine-700" target="_blank" rel="noopener">Política de Privacidad</a>.</strong><br><span class="text-atlantia-ink/65">Tus documentos se usan solo para validar identidad, fiscalidad y pagos.</span></span>
                            </label>
                        </div>
                    </section>
                </div>

                <div class="sticky bottom-0 z-10 -mx-5 mt-4 border-t border-atlantia-rose/15 bg-white/95 px-5 py-3 backdrop-blur sm:-mx-7 sm:px-7 rounded-b-2xl">
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                        <p class="text-xs font-bold text-atlantia-ink/55" data-step-helper>Tu avance se guarda automáticamente en este dispositivo.</p>
                        <div class="flex flex-wrap gap-3">
                            <button type="button" class="rounded-lg border border-atlantia-rose/35 bg-white px-5 py-2.5 text-sm font-black text-atlantia-wine transition hover:bg-atlantia-blush disabled:cursor-not-allowed" data-prev-step>Anterior</button>
                            <button type="button" class="rounded-lg bg-atlantia-wine px-5 py-2.5 text-sm font-black text-white shadow-sm transition hover:bg-atlantia-wine-700" data-next-step>Siguiente</button>
                            <button type="submit" class="hidden rounded-lg bg-atlantia-wine px-5 py-2.5 text-sm font-black text-white shadow-sm transition hover:bg-atlantia-wine-700" data-submit-step>Enviar solicitud</button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
</section>
@endsection

@push('scripts')
<script nonce="{{ request()->attributes->get('csp_nonce') }}">
    document.addEventListener('DOMContentLoaded', () => {
        const form = document.querySelector('[data-vendor-application-form]');
        if (!form) return;

        const panels = [...form.querySelectorAll('[data-step-panel]')];
        const progressBar = document.querySelector('[data-progress-bar]');
        const progressLabel = document.querySelector('[data-progress-label]');
        const progressPercent = document.querySelector('[data-progress-percent]');
        const nextButton = form.querySelector('[data-next-step]');
        const prevButton = form.querySelector('[data-prev-step]');
        const submitButton = form.querySelector('[data-submit-step]');
        const jumps = [...document.querySelectorAll('[data-step-jump]')];
        const storageKey = 'atlantia_vendor_application';
        let currentStep = 1;

        const setError = (field, message) => {
            const wrapper = field.closest('[data-field]');
            if (!wrapper) return;
            const error = wrapper.querySelector('[data-field-error]');
            field.classList.toggle('border-red-400', Boolean(message));
            if (error) {
                error.textContent = message || '';
                error.hidden = !message;
            }
        };

        const validateField = (field) => {
            if (field.disabled || field.type === 'hidden') return true;
            let message = '';
            if (field.required && !field.value && field.type !== 'radio' && field.type !== 'checkbox' && field.type !== 'file') {
                message = 'Este campo es requerido.';
            }
            if (field.required && field.type === 'file' && field.files.length === 0) {
                message = 'Debes subir este archivo.';
            }
            if (field.type === 'email' && field.value && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(field.value)) {
                message = 'Ingresa un correo valido.';
            }
            if (field.name === 'phone' && field.value && !/^\+502\s?\d{4}\s?\d{4}$/.test(field.value)) {
                message = 'Usa el formato +502 XXXX XXXX.';
            }
            if (field.name === 'birthdate' && field.value) {
                const birth = new Date(field.value);
                const today = new Date();
                let age = today.getFullYear() - birth.getFullYear();
                const month = today.getMonth() - birth.getMonth();
                if (month < 0 || (month === 0 && today.getDate() < birth.getDate())) age -= 1;
                if (age < 18) message = 'Debes tener al menos 18 anos.';
                if (age > 120) message = 'Revisa la fecha de nacimiento.';
            }
            if (field.files?.length) {
                const file = field.files[0];
                const allowed = ['image/jpeg', 'image/png', 'application/pdf'];
                if (!allowed.includes(file.type)) message = 'Solo aceptamos JPG, PNG o PDF.';
                if (file.size > 5 * 1024 * 1024) message = 'Maximo 5MB por archivo.';
            }
            if (field.required && (field.type === 'radio' || field.type === 'checkbox')) {
                const checked = form.querySelector(`[name="${field.name}"]:checked`);
                if (!checked) message = 'Selecciona una opcion.';
            }
            setError(field, message);
            return !message;
        };

        const validateStep = () => {
            const panel = form.querySelector(`[data-step-panel="${currentStep}"]`);
            const fields = [...panel.querySelectorAll('input, select, textarea')];
            const valid = fields.map(validateField).every(Boolean);
            if (!valid) panel.querySelector('.border-red-400, [data-field-error]:not([hidden])')?.scrollIntoView({ behavior: 'smooth', block: 'center' });
            return valid;
        };

        const showStep = (step) => {
            currentStep = Math.max(1, Math.min(7, step));
            panels.forEach((panel) => {
                panel.hidden = Number(panel.dataset.stepPanel) !== currentStep;
            });
            const percent = Math.round((currentStep / 7) * 100);
            progressBar.style.width = `${percent}%`;
            progressLabel.textContent = `Paso ${currentStep} de 7`;
            progressPercent.textContent = `${percent}%`;
            prevButton.disabled = currentStep === 1;
            prevButton.classList.toggle('opacity-50', currentStep === 1);
            nextButton.classList.toggle('hidden', currentStep === 7);
            submitButton.classList.toggle('hidden', currentStep !== 7);
            jumps.forEach((jump) => {
                const active = Number(jump.dataset.stepJump) === currentStep;
                jump.classList.toggle('border-atlantia-wine', active);
                jump.classList.toggle('bg-atlantia-blush', active);
            });
        };

        const saveDraft = () => {
            const data = {};
            new FormData(form).forEach((value, key) => {
                if (!(value instanceof File)) data[key] = value;
            });
            localStorage.setItem(storageKey, JSON.stringify(data));
        };

        const restoreDraft = () => {
            try {
                const draft = JSON.parse(localStorage.getItem(storageKey) || '{}');
                Object.entries(draft).forEach(([key, value]) => {
                    const fields = [...form.querySelectorAll(`[name="${key}"]`)];
                    fields.forEach((field) => {
                        if (field.type === 'file') return;
                        if (field.type === 'radio' || field.type === 'checkbox') {
                            field.checked = field.value === value;
                        } else if (!field.value) {
                            field.value = value;
                        }
                    });
                });
            } catch {
                localStorage.removeItem(storageKey);
            }
        };

        const toggleNit = () => {
            const hasNit = form.querySelector('[name="has_nit"]:checked')?.value === '1';
            const nitFields = form.querySelector('[data-nit-fields]');
            const naturalMessage = form.querySelector('[data-natural-message]');
            nitFields.querySelectorAll('input, select').forEach((field) => {
                const requiredNames = ['nit_number', 'razon_social', 'regimen_sat', 'nit_file'];
                field.required = hasNit && requiredNames.includes(field.name);
                field.disabled = !hasNit && requiredNames.includes(field.name);
            });
            nitFields.classList.toggle('opacity-50', !hasNit);
            naturalMessage.hidden = hasNit;
        };

        const toggleOtherCategory = () => {
            const select = form.querySelector('[data-business-category]');
            const other = form.querySelector('[data-other-category]');
            const enabled = select?.value === 'otro';
            if (other) {
                other.disabled = !enabled;
                other.required = enabled;
            }
        };

        restoreDraft();
        toggleNit();
        toggleOtherCategory();
        showStep(1);

        form.addEventListener('input', (event) => {
            const field = event.target;
            if (field.matches('[data-phone-mask]')) {
                const digits = field.value.replace(/\D/g, '').replace(/^502/, '').slice(0, 8);
                field.value = `+502 ${digits.slice(0, 4)}${digits.length > 4 ? ' ' + digits.slice(4) : ''}`;
            }
            if (field.matches('[data-counter-target]')) {
                const target = document.getElementById(field.dataset.counterTarget);
                if (target) target.textContent = field.value.length;
            }
            validateField(field);
            saveDraft();
        });

        form.addEventListener('change', (event) => {
            const field = event.target;
            if (field.name === 'has_nit') toggleNit();
            if (field.name === 'business_category') toggleOtherCategory();
            validateField(field);
            saveDraft();
        });

        document.querySelector('[data-cancel-application]')?.addEventListener('click', (event) => {
            if (localStorage.getItem(storageKey) && !confirm('Si sales ahora perderas el progreso guardado en este navegador. Deseas cancelar?')) {
                event.preventDefault();
            }
        });

        nextButton.addEventListener('click', () => {
            if (validateStep()) showStep(currentStep + 1);
        });
        prevButton.addEventListener('click', () => showStep(currentStep - 1));
        jumps.forEach((jump) => jump.addEventListener('click', () => {
            const targetStep = Number(jump.dataset.stepJump);
            if (targetStep <= currentStep || validateStep()) showStep(targetStep);
        }));
        form.addEventListener('submit', (event) => {
            let firstInvalidStep = null;
            panels.forEach((panel) => {
                const step = Number(panel.dataset.stepPanel);
                const valid = [...panel.querySelectorAll('input, select, textarea')].map(validateField).every(Boolean);
                if (!valid && firstInvalidStep === null) firstInvalidStep = step;
            });
            if (firstInvalidStep !== null) {
                event.preventDefault();
                showStep(firstInvalidStep);
                return;
            }
            localStorage.removeItem(storageKey);
            submitButton.disabled = true;
            submitButton.textContent = 'Enviando...';
        });
    });
</script>
@endpush
