@extends('layouts.marketplace', ['title' => 'Solicitud de vendedor | Atlantia Supermarket'])

@section('content')
@php
    $steps = [
        1 => ['title' => 'Informacion personal', 'subtitle' => 'Datos para validar tu identidad.'],
        2 => ['title' => 'Documentos', 'subtitle' => 'Documento de identidad claro y legible.'],
        3 => ['title' => 'Negocio', 'subtitle' => 'Como se vera tu tienda en Atlantia.'],
        4 => ['title' => 'Fiscal', 'subtitle' => 'Solo aplica si tienes NIT registrado.'],
        5 => ['title' => 'Banco', 'subtitle' => 'Cuenta donde depositaremos tus ventas.'],
        6 => ['title' => 'Pagos', 'subtitle' => 'Preferencias de pago y liquidacion.'],
        7 => ['title' => 'Terminos', 'subtitle' => 'Confirmaciones finales.'],
    ];
@endphp

<section class="bg-atlantia-cream px-4 py-6 sm:px-6 lg:px-8">
    <div class="mx-auto max-w-4xl">
        <div class="overflow-hidden rounded-2xl border border-atlantia-rose/25 bg-white shadow-sm">
            <div class="border-b border-atlantia-rose/15 bg-white px-5 py-5 sm:px-7">
                <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                    <div>
                        <p class="text-[11px] font-black uppercase tracking-[0.18em] text-atlantia-rose">Atlantia Supermarket</p>
                        <h1 class="mt-2 text-2xl font-black leading-tight text-atlantia-ink sm:text-3xl">Solicitud para vender</h1>
                        <p class="mt-2 max-w-2xl text-sm leading-6 text-atlantia-ink/65">
                        Completa la informacion necesaria para validar tu identidad, documentos, negocio y cuenta bancaria.
                        Revisaremos tu solicitud en 3 a 5 dias.
                        </p>
                    </div>
                    <a href="{{ route('home') }}" class="inline-flex items-center justify-center rounded-lg border border-atlantia-rose/30 bg-white px-4 py-2 text-sm font-black text-atlantia-wine transition hover:bg-atlantia-blush" data-cancel-application>
                        Cancelar
                    </a>
                </div>
            </div>

            @if ($errors->any())
                <div class="mx-5 mt-5 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800 sm:mx-7">
                    Hay campos que necesitan revision. Corrigelos y vuelve a intentar.
                </div>
            @endif

            <div class="px-5 pt-5 sm:px-7">
                <div class="flex items-center justify-between text-xs font-black text-atlantia-wine">
                    <span data-progress-label>Paso 1 de 7</span>
                    <span data-progress-percent>14%</span>
                </div>
                <div class="mt-2 h-1.5 rounded-full bg-atlantia-blush">
                    <div class="h-1.5 rounded-full bg-atlantia-wine transition-all duration-300" style="width: 14%" data-progress-bar></div>
                </div>
                <div class="mt-4 flex gap-2 overflow-x-auto pb-2 [scrollbar-width:none] [&::-webkit-scrollbar]:hidden">
                    @foreach ($steps as $number => $step)
                        <button type="button" class="flex min-w-[132px] items-center gap-2 rounded-full border border-atlantia-rose/25 bg-white px-3 py-2 text-left transition hover:border-atlantia-wine/50" data-step-jump="{{ $number }}">
                            <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-atlantia-blush text-xs font-black text-atlantia-wine">{{ $number }}</span>
                            <span class="min-w-0">
                                <span class="block truncate text-xs font-black text-atlantia-ink">{{ $step['title'] }}</span>
                            </span>
                        </button>
                    @endforeach
                </div>
            </div>

            <form class="px-5 py-5 sm:px-7 sm:py-7" method="POST" action="{{ route('vendedor.solicitar.store') }}" enctype="multipart/form-data" data-vendor-application-form novalidate>
                @csrf

                <div class="space-y-6">
                    <section class="rounded-2xl border border-atlantia-rose/20 bg-white p-5 shadow-[0_18px_45px_rgba(122,31,61,0.06)] sm:p-6" data-step-panel="1">
                        <x-step-heading number="1" title="Informacion personal" subtitle="Como aparece en tu documento de identidad." />
                        <div class="mt-6 grid gap-5 lg:grid-cols-2">
                            <x-form-field name="name" label="Nombre completo" help="Como aparece en tu documento de identidad">
                                <input required minlength="5" maxlength="100" name="name" value="{{ old('name') }}" placeholder="Ej: Daniela Maria Escalante Moreno" class="w-full rounded-md border border-atlantia-rose/35 bg-white px-4 py-3 text-sm text-atlantia-ink outline-none transition focus:border-atlantia-wine focus:ring-2 focus:ring-atlantia-rose/20">
                            </x-form-field>
                            <x-form-field name="email" label="Correo electronico" help="Usaremos este correo para tu cuenta y comunicaciones">
                                <input required type="email" name="email" value="{{ old('email') }}" placeholder="tu@email.com" data-check-email-url="{{ route('vendedor.solicitar.check-email') }}" class="w-full rounded-md border border-atlantia-rose/35 bg-white px-4 py-3 text-sm text-atlantia-ink outline-none transition focus:border-atlantia-wine focus:ring-2 focus:ring-atlantia-rose/20">
                            </x-form-field>
                            <x-form-field name="phone" label="Telefono" help="Formato Guatemala: +502 XXXX XXXX">
                                <input required name="phone" value="{{ old('phone', '+502 ') }}" placeholder="+502 XXXX XXXX" data-phone-mask class="w-full rounded-md border border-atlantia-rose/35 bg-white px-4 py-3 text-sm text-atlantia-ink outline-none transition focus:border-atlantia-wine focus:ring-2 focus:ring-atlantia-rose/20">
                            </x-form-field>
                            <x-form-field name="birthdate" label="Fecha de nacimiento" help="Debes ser mayor de 18 anos para vender">
                                <input required type="date" name="birthdate" value="{{ old('birthdate') }}" class="w-full rounded-md border border-atlantia-rose/35 bg-white px-4 py-3 text-sm text-atlantia-ink outline-none transition focus:border-atlantia-wine focus:ring-2 focus:ring-atlantia-rose/20">
                            </x-form-field>
                            <x-form-field name="gender" label="Genero" help="Opcional">
                                <select name="gender" class="w-full rounded-md border border-atlantia-rose/35 bg-white px-4 py-3 text-sm text-atlantia-ink outline-none transition focus:border-atlantia-wine focus:ring-2 focus:ring-atlantia-rose/20">
                                    <option value="">Seleccionar</option>
                                    <option value="masculino" @selected(old('gender') === 'masculino')>Masculino</option>
                                    <option value="femenino" @selected(old('gender') === 'femenino')>Femenino</option>
                                    <option value="prefiero_no_decir" @selected(old('gender') === 'prefiero_no_decir')>Prefiero no decir</option>
                                </select>
                            </x-form-field>
                        </div>
                        <div class="mt-6 rounded-xl border border-atlantia-rose/15 bg-atlantia-cream/45 p-4">
                            <h3 class="text-base font-black text-atlantia-wine">Direccion completa</h3>
                            <p class="mt-1 text-sm text-atlantia-ink/65">Direccion donde podemos verificar tu identidad si es necesario.</p>
                            <div class="mt-5 grid gap-5 md:grid-cols-2 lg:grid-cols-3">
                                <x-form-field name="address_street" label="Calle"><input required name="address_street" value="{{ old('address_street') }}" placeholder="Calle Principal" class="field-input"></x-form-field>
                                <x-form-field name="address_number" label="Numero"><input required name="address_number" value="{{ old('address_number') }}" placeholder="123" class="field-input"></x-form-field>
                                <x-form-field name="address_suite" label="Apto/Suite"><input name="address_suite" value="{{ old('address_suite') }}" placeholder="Apto 4B" class="field-input"></x-form-field>
                                <x-form-field name="address_municipio" label="Municipio">
                                    <select required name="address_municipio" class="field-input">
                                        <option value="">Seleccionar</option>
                                        @foreach ($municipios as $municipio)
                                            <option value="{{ $municipio }}" @selected(old('address_municipio') === $municipio)>{{ $municipio }}</option>
                                        @endforeach
                                    </select>
                                </x-form-field>
                                <x-form-field name="address_departamento" label="Departamento">
                                    <select required name="address_departamento" class="field-input">
                                        <option value="">Seleccionar</option>
                                        @foreach ($departamentos as $departamento)
                                            <option value="{{ $departamento }}" @selected(old('address_departamento', 'Izabal') === $departamento)>{{ $departamento }}</option>
                                        @endforeach
                                    </select>
                                </x-form-field>
                                <x-form-field name="address_zip" label="Codigo postal"><input name="address_zip" value="{{ old('address_zip') }}" placeholder="19001" class="field-input"></x-form-field>
                            </div>
                        </div>
                    </section>

                    <section class="rounded-2xl border border-atlantia-rose/20 bg-white p-5 shadow-[0_18px_45px_rgba(122,31,61,0.06)] sm:p-6" data-step-panel="2" hidden>
                        <x-step-heading number="2" title="Documentos de identidad" subtitle="Sube fotos claras. Deben ser legibles." />
                        <div class="mt-6 grid gap-5 lg:grid-cols-2">
                            <x-form-field name="document_type" label="Tipo de documento" help="Selecciona el documento que vas a usar para tu cuenta">
                                <div class="grid gap-3 sm:grid-cols-3">
                                    @foreach (['dpi' => 'DPI', 'nit' => 'NIT', 'pasaporte' => 'Pasaporte'] as $value => $label)
                                        <label class="rounded-xl border border-atlantia-rose/25 bg-white p-4 text-sm font-black text-atlantia-ink transition has-[:checked]:border-atlantia-wine has-[:checked]:bg-atlantia-blush">
                                            <input required type="radio" name="document_type" value="{{ $value }}" class="mr-2 accent-atlantia-wine" @checked(old('document_type', 'dpi') === $value)> {{ $label }}
                                        </label>
                                    @endforeach
                                </div>
                            </x-form-field>
                            <x-form-field name="document_number" label="Numero de documento" help="Tal como aparece en tu documento">
                                <input required name="document_number" value="{{ old('document_number') }}" placeholder="XXXXXXXX-XXXX" data-check-document-url="{{ route('vendedor.solicitar.check-document') }}" class="field-input">
                            </x-form-field>
                            <x-form-field name="document_front" label="Documento frente" help="JPG, PNG o PDF. Maximo 5MB.">
                                <input required type="file" name="document_front" accept=".jpg,.jpeg,.png,.pdf" class="file-input">
                            </x-form-field>
                            <x-form-field name="document_back" label="Documento reverso" help="JPG, PNG o PDF. Maximo 5MB.">
                                <input required type="file" name="document_back" accept=".jpg,.jpeg,.png,.pdf" class="file-input">
                            </x-form-field>
                        </div>
                    </section>

                    <section class="rounded-2xl border border-atlantia-rose/20 bg-white p-5 shadow-[0_18px_45px_rgba(122,31,61,0.06)] sm:p-6" data-step-panel="3" hidden>
                        <x-step-heading number="3" title="Informacion del negocio" subtitle="Esta informacion sera visible para tus clientes." />
                        <div class="mt-6 grid gap-5 lg:grid-cols-2">
                            <x-form-field name="business_name" label="Nombre del negocio" help="Como aparecera en tu tienda dentro de Atlantia">
                                <input required minlength="3" maxlength="100" name="business_name" value="{{ old('business_name') }}" placeholder="Ej: La Pasteleria Daniela" class="field-input">
                            </x-form-field>
                            <x-form-field name="business_category" label="Categoria de productos" help="En que categoria entra principalmente tu negocio">
                                <select required name="business_category" data-business-category class="field-input">
                                    <option value="">Seleccionar</option>
                                    @foreach ($categories as $value => $label)
                                        <option value="{{ $value }}" @selected(old('business_category') === $value)>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </x-form-field>
                            <x-form-field name="business_category_other" label="Especificar categoria" help="Solo si seleccionaste Otro">
                                <input name="business_category_other" value="{{ old('business_category_other') }}" placeholder="Describe tu categoria" data-other-category class="field-input" disabled>
                            </x-form-field>
                            <x-form-field name="business_logo" label="Logo o foto del negocio" help="JPG o PNG. Recomendado 500x500px. Maximo 5MB.">
                                <input required type="file" name="business_logo" accept=".jpg,.jpeg,.png" class="file-input">
                            </x-form-field>
                            <x-form-field name="business_description" label="Descripcion del negocio" help="Describe que vendes, tu especialidad y que te diferencia">
                                <textarea required maxlength="500" name="business_description" rows="5" placeholder="Describe brevemente que vendes..." data-counter-target="business-description-counter" class="field-input">{{ old('business_description') }}</textarea>
                                <p class="mt-1 text-xs font-bold text-atlantia-ink/55"><span id="business-description-counter">0</span>/500 caracteres</p>
                            </x-form-field>
                            <x-form-field name="has_nit" label="Tienes NIT registrado" help="Si no tienes NIT puedes registrarte como Persona Natural">
                                <div class="grid gap-3 sm:grid-cols-2">
                                    <label class="rounded-xl border border-atlantia-rose/25 bg-white p-4 text-sm font-black text-atlantia-ink transition has-[:checked]:border-atlantia-wine has-[:checked]:bg-atlantia-blush">
                                        <input required type="radio" name="has_nit" value="1" class="mr-2 accent-atlantia-wine" @checked(old('has_nit') === '1') data-has-nit> Si tengo NIT
                                    </label>
                                    <label class="rounded-xl border border-atlantia-rose/25 bg-white p-4 text-sm font-black text-atlantia-ink transition has-[:checked]:border-atlantia-wine has-[:checked]:bg-atlantia-blush">
                                        <input required type="radio" name="has_nit" value="0" class="mr-2 accent-atlantia-wine" @checked(old('has_nit', '0') === '0') data-has-nit> Persona natural
                                    </label>
                                </div>
                            </x-form-field>
                        </div>
                        <div class="mt-6 rounded-xl border border-atlantia-rose/20 bg-atlantia-cream/45 p-4">
                            <h3 class="text-base font-black text-atlantia-wine">Elige tu plan mensual</h3>
                            <p class="mt-1 text-sm text-atlantia-ink/65">Elige el plan que mejor se ajusta a tu negocio. El admin confirma estas condiciones al aprobar.</p>
                            <div class="mt-4 grid gap-3 lg:grid-cols-2">
                                @foreach ($plans as $value => $plan)
                                    <label class="rounded-xl border border-atlantia-rose/25 bg-white p-4 text-sm text-atlantia-ink transition has-[:checked]:border-atlantia-wine has-[:checked]:bg-atlantia-blush">
                                        <input required type="radio" name="seller_plan" value="{{ $value }}" class="sr-only" @checked(old('seller_plan', 'starter') === $value)>
                                        <span class="block text-base font-black text-atlantia-ink">{{ $plan['name'] }}</span>
                                        <span class="mt-2 block text-2xl font-black text-atlantia-wine">{{ $plan['price'] > 0 ? 'Q' . number_format($plan['price'], 0) : 'Gratis' }}<span class="text-xs text-atlantia-ink/50">{{ $plan['price'] > 0 ? '/mes' : '' }}</span></span>
                                        <span class="mt-1 block text-xs font-black text-atlantia-wine">{{ $plan['commission'] }}% comision por venta</span>
                                        <span class="mt-2 block text-xs font-bold text-atlantia-ink/60">{{ $plan['products'] }} · {{ $plan['users'] }}</span>
                                        <span class="mt-1 block text-xs font-bold text-atlantia-ink/60">{{ $plan['payout'] }} · {{ $plan['support'] }}</span>
                                        <span class="mt-1 block text-xs font-bold text-atlantia-ink/60">{{ $plan['analytics'] }}</span>
                                        <span class="mt-2 block text-xs leading-5 text-atlantia-ink/55">{{ $plan['description'] }}</span>
                                    </label>
                                @endforeach
                            </div>
                        </div>
                    </section>

                    <section class="rounded-2xl border border-atlantia-rose/20 bg-white p-5 shadow-[0_18px_45px_rgba(122,31,61,0.06)] sm:p-6" data-step-panel="4" hidden>
                        <x-step-heading number="4" title="Informacion fiscal" subtitle="Completa esta parte si cuentas con NIT registrado." />
                        <div class="mt-4 rounded-xl border border-atlantia-rose/20 bg-white px-4 py-3 text-sm text-atlantia-ink/70" data-natural-message>
                            Si te registras como Persona Natural, puedes continuar sin NIT. Atlantia revisara tu solicitud con tu documento principal.
                        </div>
                        <div class="mt-6 grid gap-5 lg:grid-cols-2" data-nit-fields>
                            <x-form-field name="nit_number" label="NIT" help="Tu numero NIT registrado con SAT">
                                <input name="nit_number" value="{{ old('nit_number') }}" placeholder="XXXXXXX-X" class="field-input">
                            </x-form-field>
                            <x-form-field name="razon_social" label="Razon social" help="Nombre de la empresa como aparece en SAT">
                                <input name="razon_social" value="{{ old('razon_social') }}" placeholder="Nombre registrado en SAT" class="field-input">
                            </x-form-field>
                            <x-form-field name="regimen_sat" label="Regimen tributario" help="Bajo que regimen estas registrado con SAT">
                                <select name="regimen_sat" class="field-input">
                                    <option value="">Seleccionar</option>
                                    <option value="ordinario" @selected(old('regimen_sat') === 'ordinario')>Regimen Ordinario</option>
                                    <option value="simplificado" @selected(old('regimen_sat') === 'simplificado')>Regimen Simplificado</option>
                                    <option value="otro" @selected(old('regimen_sat') === 'otro')>Otro</option>
                                </select>
                            </x-form-field>
                            <x-form-field name="nit_file" label="Fotocopia de NIT/RIT" help="JPG, PNG o PDF. Maximo 5MB.">
                                <input type="file" name="nit_file" accept=".jpg,.jpeg,.png,.pdf" class="file-input">
                            </x-form-field>
                            <x-form-field name="business_street" label="Direccion comercial - calle"><input name="business_street" value="{{ old('business_street') }}" placeholder="Calle Principal" class="field-input"></x-form-field>
                            <x-form-field name="business_number" label="Direccion comercial - numero"><input name="business_number" value="{{ old('business_number') }}" placeholder="123" class="field-input"></x-form-field>
                            <x-form-field name="business_municipio" label="Municipio comercial">
                                <select name="business_municipio" class="field-input">
                                    <option value="">Usar municipio personal</option>
                                    @foreach ($municipios as $municipio)
                                        <option value="{{ $municipio }}" @selected(old('business_municipio') === $municipio)>{{ $municipio }}</option>
                                    @endforeach
                                </select>
                            </x-form-field>
                        </div>
                    </section>

                    <section class="rounded-2xl border border-atlantia-rose/20 bg-white p-5 shadow-[0_18px_45px_rgba(122,31,61,0.06)] sm:p-6" data-step-panel="5" hidden>
                        <x-step-heading number="5" title="Informacion bancaria" subtitle="Verificaremos esta cuenta antes de liquidar pagos." />
                        <div class="mt-6 grid gap-5 lg:grid-cols-2">
                            <x-form-field name="bank" label="Banco" help="Banco donde te depositaremos tus ventas">
                                <select required name="bank" class="field-input">
                                    <option value="">Seleccionar</option>
                                    @foreach ($banks as $bank)
                                        <option value="{{ $bank }}" @selected(old('bank') === $bank)>{{ $bank }}</option>
                                    @endforeach
                                </select>
                            </x-form-field>
                            <x-form-field name="account_type" label="Tipo de cuenta" help="Tipo de cuenta bancaria">
                                <div class="grid gap-3 sm:grid-cols-2">
                                    <label class="rounded-xl border border-atlantia-rose/25 bg-white p-4 text-sm font-black text-atlantia-ink transition has-[:checked]:border-atlantia-wine has-[:checked]:bg-atlantia-blush">
                                        <input required type="radio" name="account_type" value="ahorros" class="mr-2 accent-atlantia-wine" @checked(old('account_type', 'ahorros') === 'ahorros')> Cuenta de ahorros
                                    </label>
                                    <label class="rounded-xl border border-atlantia-rose/25 bg-white p-4 text-sm font-black text-atlantia-ink transition has-[:checked]:border-atlantia-wine has-[:checked]:bg-atlantia-blush">
                                        <input required type="radio" name="account_type" value="corriente" class="mr-2 accent-atlantia-wine" @checked(old('account_type') === 'corriente')> Cuenta corriente
                                    </label>
                                </div>
                            </x-form-field>
                            <x-form-field name="account_number" label="Numero de cuenta" help="Numero completo de tu cuenta">
                                <input required name="account_number" value="{{ old('account_number') }}" placeholder="XXXXXXXXXXXXXXXX" class="field-input">
                            </x-form-field>
                            <x-form-field name="account_holder" label="Nombre del titular" help="Debe coincidir exactamente con la cuenta">
                                <input required minlength="5" name="account_holder" value="{{ old('account_holder') }}" placeholder="Nombre exacto del titular" class="field-input">
                            </x-form-field>
                            <x-form-field name="bank_proof" label="Comprobante bancario" help="Estado de cuenta reciente o libreta. JPG, PNG o PDF. Maximo 5MB.">
                                <input required type="file" name="bank_proof" accept=".jpg,.jpeg,.png,.pdf" class="file-input">
                            </x-form-field>
                        </div>
                    </section>

                    <section class="rounded-2xl border border-atlantia-rose/20 bg-white p-5 shadow-[0_18px_45px_rgba(122,31,61,0.06)] sm:p-6" data-step-panel="6" hidden>
                        <x-step-heading number="6" title="Informacion de pago" subtitle="Indica como prefieres recibir tus pagos." />
                        <div class="mt-6 grid gap-5 lg:grid-cols-2">
                            <x-form-field name="payment_frequency" label="Frecuencia de pago preferida" help="Con que frecuencia quieres recibir tus pagos">
                                <div class="grid gap-3">
                                    @foreach (['semanal' => 'Semanal (cada lunes)', 'quincenal' => 'Quincenal (cada 15 dias)', 'mensual' => 'Mensual (fin de mes)'] as $value => $label)
                                        <label class="rounded-xl border border-atlantia-rose/25 bg-white p-4 text-sm font-black text-atlantia-ink transition has-[:checked]:border-atlantia-wine has-[:checked]:bg-atlantia-blush">
                                            <input required type="radio" name="payment_frequency" value="{{ $value }}" class="mr-2 accent-atlantia-wine" @checked(old('payment_frequency', 'quincenal') === $value)> {{ $label }}
                                        </label>
                                    @endforeach
                                </div>
                            </x-form-field>
                            <x-form-field name="preferred_payment_method" label="Metodo de pago preferido" help="Como prefieres recibir tus pagos">
                                <div class="grid gap-3">
                                    @foreach (['transferencia' => 'Transferencia bancaria (recomendado)', 'deposito' => 'Deposito en efectivo (previa validacion)'] as $value => $label)
                                        <label class="rounded-xl border border-atlantia-rose/25 bg-white p-4 text-sm font-black text-atlantia-ink transition has-[:checked]:border-atlantia-wine has-[:checked]:bg-atlantia-blush">
                                            <input required type="radio" name="preferred_payment_method" value="{{ $value }}" class="mr-2 accent-atlantia-wine" @checked(old('preferred_payment_method', 'transferencia') === $value)> {{ $label }}
                                        </label>
                                    @endforeach
                                </div>
                            </x-form-field>
                        </div>
                    </section>

                    <section class="rounded-2xl border border-atlantia-rose/20 bg-white p-5 shadow-[0_18px_45px_rgba(122,31,61,0.06)] sm:p-6" data-step-panel="7" hidden>
                        <x-step-heading number="7" title="Terminos y condiciones" subtitle="Ultimas confirmaciones antes de enviar." />
                        <div class="mt-6 space-y-4">
                            <label class="flex gap-3 rounded-xl border border-atlantia-rose/25 bg-white p-4 text-sm text-atlantia-ink transition hover:border-atlantia-wine/45">
                                <input required type="checkbox" name="terms" value="1" class="mt-1 accent-atlantia-wine" @checked(old('terms'))>
                                <span><strong>Acepto los terminos y condiciones de Atlantia Supermarket.</strong><br><span class="text-atlantia-ink/65">Confirmas que venderas productos de calidad y respetaras las politicas de privacidad y marketplace.</span></span>
                            </label>
                            <label class="flex gap-3 rounded-xl border border-atlantia-rose/25 bg-white p-4 text-sm text-atlantia-ink transition hover:border-atlantia-wine/45">
                                <input required type="checkbox" name="truth" value="1" class="mt-1 accent-atlantia-wine" @checked(old('truth'))>
                                <span><strong>Certifico que todos los datos proporcionados son veridicos y completos.</strong><br><span class="text-atlantia-ink/65">Proporcionar informacion falsa puede resultar en rechazo o sancion.</span></span>
                            </label>
                            <label class="flex gap-3 rounded-xl border border-atlantia-rose/25 bg-white p-4 text-sm text-atlantia-ink transition hover:border-atlantia-wine/45">
                                <input required type="checkbox" name="data_consent" value="1" class="mt-1 accent-atlantia-wine" @checked(old('data_consent'))>
                                <span><strong>Autorizo a Atlantia a procesar mis datos personales segun su politica de privacidad.</strong><br><span class="text-atlantia-ink/65">Tus documentos se usan solo para validar identidad, fiscalidad y pagos.</span></span>
                            </label>
                        </div>
                    </section>
                </div>

                <div class="mt-5 rounded-xl border border-atlantia-rose/20 bg-atlantia-cream/55 p-3 sm:p-4">
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                        <p class="text-xs font-bold text-atlantia-ink/55" data-step-helper>Progreso guardado automaticamente.</p>
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

        const checkAvailability = async (field, param, unavailableMessage) => {
            const url = field.dataset.checkEmailUrl || field.dataset.checkDocumentUrl;
            if (!url || !field.value) return;
            setError(field, '');
            try {
                const response = await fetch(`${url}?${param}=${encodeURIComponent(field.value)}`, { headers: { Accept: 'application/json' } });
                if (!response.ok) return;
                const payload = await response.json();
                if (typeof payload.available !== 'boolean') return;
                setError(field, payload.available ? '' : unavailableMessage);
            } catch {
                // La validacion del servidor se mantiene como respaldo.
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

        form.querySelector('[name="email"]')?.addEventListener('blur', (event) => checkAvailability(event.target, 'email', 'Este correo ya esta registrado.'));
        form.querySelector('[name="document_number"]')?.addEventListener('blur', (event) => checkAvailability(event.target, 'document', 'Este documento ya tiene una cuenta o solicitud.'));
        document.querySelector('[data-cancel-application]')?.addEventListener('click', (event) => {
            if (localStorage.getItem(storageKey) && !confirm('Si sales ahora perderas el progreso guardado en este navegador. ¿Deseas cancelar?')) {
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
