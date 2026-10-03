@extends('layouts.app')

@section('content')
    @php
        $field = fn (string $name, mixed $fallback = '') => old($name, $perfil?->{$name} ?? $fallback);
        $inputBase = 'mt-1 w-full rounded-md border bg-white px-3 py-2 text-sm text-atlantia-ink outline-none transition placeholder:text-atlantia-ink/35 focus:border-atlantia-wine focus:ring-2 focus:ring-atlantia-rose/20';
        $inputNormal = 'border-atlantia-rose/25';
        $inputError = 'border-rose-500 bg-rose-50';
        $errorText = 'mt-1 text-xs font-semibold text-rose-700';

        $sections = [
            'Datos fiscales' => filled($field('nit')) && filled($field('razon_social')) && filled($field('direccion_fiscal')),
            'Configuracion SAT' => filled($field('regimen_sat')) && filled($field('codigo_establecimiento')) && filled($field('afiliacion_iva')),
            'Configuracion FEL' => filled($field('certificador_fel')) && filled($field('fel_usuario')) && filled($field('fel_llave_firma')) && filled($field('fel_llave_certificador')),
            'Datos bancarios' => filled($field('banco_nombre')) && filled($field('cuenta_bancaria')) && filled($field('cuenta_bancaria_tipo')) && filled($field('cuenta_bancaria_titular')),
            'Validacion' => (bool) ($perfil?->fel_activo ?? false),
        ];
        $completed = collect($sections)->filter()->count();
        $totalSections = count($sections);
        $progress = (int) round(($completed / max($totalSections, 1)) * 100);
        $regimenLabels = [
            'pequeno_contribuyente' => 'Pequeno contribuyente',
            'general' => 'General',
            'exento' => 'Exento',
        ];
        $cuentaLabels = [
            'monetaria' => 'Cuenta Monetaria',
            'ahorro' => 'Cuenta de Ahorro',
        ];
    @endphp

    <section class="mx-auto max-w-[1280px] space-y-5 pb-10">
        <div>
            <p class="text-xs font-black uppercase tracking-[0.22em] text-atlantia-rose">Atlantia Supermarket</p>
            <h1 class="mt-2 text-3xl font-black leading-tight text-atlantia-ink sm:text-4xl">Perfil fiscal</h1>
            <p class="mt-2 max-w-2xl text-sm leading-6 text-atlantia-ink/62">
                Configura y mantiene tu informacion fiscal para la emision de DTE ante SAT.
            </p>
        </div>

        <div class="grid gap-5 xl:grid-cols-[1fr_300px]">
            <form
                method="POST"
                action="{{ route('vendedor.perfil-fiscal.update') }}"
                class="rounded-lg border border-atlantia-rose/15 bg-white shadow-[0_12px_32px_rgba(42,16,24,0.05)]"
            >
                @csrf
                @method('PUT')

                <div class="space-y-6 p-5 sm:p-6">
                    <section>
                        <div class="flex items-center gap-2">
                            <span class="grid h-8 w-8 place-items-center rounded-lg bg-atlantia-blush text-atlantia-wine">
                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8Z"/><path d="M14 2v6h6"/><path d="M8 13h8"/><path d="M8 17h5"/>
                                </svg>
                            </span>
                            <h2 class="text-sm font-black text-atlantia-wine">1. Datos fiscales</h2>
                        </div>

                        <div class="mt-4 grid gap-4 lg:grid-cols-3">
                            <label class="block">
                                <span class="text-xs font-bold text-atlantia-ink">NIT <span class="text-atlantia-wine">*</span></span>
                                <input name="nit" value="{{ $field('nit') }}" placeholder="CF-10" class="{{ $inputBase }} {{ $errors->has('nit') ? $inputError : $inputNormal }}" required>
                                <span class="mt-1 block text-[11px] text-atlantia-ink/48">Escribe tu NIT tal como aparece en SAT.</span>
                                @error('nit') <p class="{{ $errorText }}">{{ $message }}</p> @enderror
                            </label>

                            <label class="block">
                                <span class="text-xs font-bold text-atlantia-ink">Razon social <span class="text-atlantia-wine">*</span></span>
                                <input name="razon_social" value="{{ $field('razon_social', auth()->user()?->name) }}" class="{{ $inputBase }} {{ $errors->has('razon_social') ? $inputError : $inputNormal }}" required>
                                <span class="mt-1 block text-[11px] text-atlantia-ink/48">Nombre o razon social registrada.</span>
                                @error('razon_social') <p class="{{ $errorText }}">{{ $message }}</p> @enderror
                            </label>

                            <label class="block">
                                <span class="text-xs font-bold text-atlantia-ink">Nombre comercial SAT</span>
                                <input name="nombre_comercial_sat" value="{{ $field('nombre_comercial_sat', auth()->user()?->vendor?->business_name) }}" placeholder="Atlantia Supermarket" class="{{ $inputBase }} {{ $errors->has('nombre_comercial_sat') ? $inputError : $inputNormal }}">
                                <span class="mt-1 block text-[11px] text-atlantia-ink/48">Tal como esta registrado en SAT.</span>
                                @error('nombre_comercial_sat') <p class="{{ $errorText }}">{{ $message }}</p> @enderror
                            </label>
                        </div>

                        <label class="mt-4 block">
                            <span class="text-xs font-bold text-atlantia-ink">Direccion fiscal <span class="text-atlantia-wine">*</span></span>
                            <textarea name="direccion_fiscal" rows="3" class="{{ $inputBase }} {{ $errors->has('direccion_fiscal') ? $inputError : $inputNormal }}" required>{{ $field('direccion_fiscal') }}</textarea>
                            <span class="mt-1 block text-[11px] text-atlantia-ink/48">Direccion fiscal completa registrada ante SAT.</span>
                            @error('direccion_fiscal') <p class="{{ $errorText }}">{{ $message }}</p> @enderror
                        </label>
                    </section>

                    <section class="border-t border-atlantia-rose/15 pt-5">
                        <div class="flex items-center gap-2">
                            <span class="grid h-8 w-8 place-items-center rounded-lg bg-atlantia-blush text-atlantia-wine">
                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <path d="M5 3v18l2-1 2 1 2-1 2 1 2-1 2 1 2-1V3Z"/><path d="M8 7h8"/><path d="M8 11h8"/><path d="M8 15h5"/>
                                </svg>
                            </span>
                            <h2 class="text-sm font-black text-atlantia-wine">2. Configuracion SAT</h2>
                        </div>

                        <div class="mt-4 grid gap-4 lg:grid-cols-3">
                            <label class="block">
                                <span class="text-xs font-bold text-atlantia-ink">Regimen SAT <span class="text-atlantia-wine">*</span></span>
                                <select name="regimen_sat" class="{{ $inputBase }} {{ $errors->has('regimen_sat') ? $inputError : $inputNormal }}" required>
                                    @foreach ($regimenLabels as $value => $label)
                                        <option value="{{ $value }}" @selected($field('regimen_sat', 'general') === $value)>{{ $label }}</option>
                                    @endforeach
                                </select>
                                @error('regimen_sat') <p class="{{ $errorText }}">{{ $message }}</p> @enderror
                            </label>

                            <label class="block">
                                <span class="text-xs font-bold text-atlantia-ink">Codigo de establecimiento <span class="text-atlantia-wine">*</span></span>
                                <input name="codigo_establecimiento" value="{{ $field('codigo_establecimiento') }}" placeholder="ADM-10" class="{{ $inputBase }} {{ $errors->has('codigo_establecimiento') ? $inputError : $inputNormal }}" required>
                                <span class="mt-1 block text-[11px] text-atlantia-ink/48">Codigo asignado por SAT.</span>
                                @error('codigo_establecimiento') <p class="{{ $errorText }}">{{ $message }}</p> @enderror
                            </label>

                            <label class="block">
                                <span class="text-xs font-bold text-atlantia-ink">Afiliacion IVA <span class="text-atlantia-wine">*</span></span>
                                <input name="afiliacion_iva" value="{{ $field('afiliacion_iva', 'GEN') }}" placeholder="GEN" class="{{ $inputBase }} {{ $errors->has('afiliacion_iva') ? $inputError : $inputNormal }}" required>
                                @error('afiliacion_iva') <p class="{{ $errorText }}">{{ $message }}</p> @enderror
                            </label>
                        </div>
                    </section>

                    <section class="border-t border-atlantia-rose/15 pt-5">
                        <div class="flex items-center gap-2">
                            <span class="grid h-8 w-8 place-items-center rounded-lg bg-atlantia-blush text-atlantia-wine">
                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10Z"/><path d="m9 12 2 2 4-5"/>
                                </svg>
                            </span>
                            <h2 class="text-sm font-black text-atlantia-wine">3. Configuracion FEL</h2>
                        </div>

                        <div class="mt-4 grid gap-4 xl:grid-cols-[1fr_250px]">
                            <div class="grid gap-4 lg:grid-cols-2">
                                <label class="block">
                                    <span class="text-xs font-bold text-atlantia-ink">Certificador FEL <span class="text-atlantia-wine">*</span></span>
                                    <select name="certificador_fel" class="{{ $inputBase }} {{ $errors->has('certificador_fel') ? $inputError : $inputNormal }}" required>
                                        <option value="infile" @selected($field('certificador_fel', 'infile') === 'infile')>INFILE</option>
                                    </select>
                                    @error('certificador_fel') <p class="{{ $errorText }}">{{ $message }}</p> @enderror
                                </label>

                                <label class="block">
                                    <span class="text-xs font-bold text-atlantia-ink">Usuario FEL</span>
                                    <input name="fel_usuario" value="{{ $field('fel_usuario') }}" placeholder="miusuario.fel" class="{{ $inputBase }} {{ $errors->has('fel_usuario') ? $inputError : $inputNormal }}">
                                    <span class="mt-1 block text-[11px] text-atlantia-ink/48">Usuario proporcionado por tu certificador.</span>
                                    @error('fel_usuario') <p class="{{ $errorText }}">{{ $message }}</p> @enderror
                                </label>

                                <label class="block">
                                    <span class="text-xs font-bold text-atlantia-ink">Llave de firma</span>
                                    <input name="fel_llave_firma" type="password" value="{{ old('fel_llave_firma') }}" placeholder="{{ filled($perfil?->fel_llave_firma) ? 'Llave configurada; escribe una nueva para cambiarla' : 'Pega la llave de firma' }}" autocomplete="new-password" class="{{ $inputBase }} {{ $errors->has('fel_llave_firma') ? $inputError : $inputNormal }}">
                                    <span class="mt-1 block text-[11px] text-atlantia-ink/48">Clave privada o llave en formato PEM.</span>
                                    @error('fel_llave_firma') <p class="{{ $errorText }}">{{ $message }}</p> @enderror
                                </label>

                                <label class="block">
                                    <span class="text-xs font-bold text-atlantia-ink">Llave certificador</span>
                                    <input name="fel_llave_certificador" type="password" value="{{ old('fel_llave_certificador') }}" placeholder="{{ filled($perfil?->fel_llave_certificador) ? 'Llave configurada; escribe una nueva para cambiarla' : 'Pega la llave del certificador' }}" autocomplete="new-password" class="{{ $inputBase }} {{ $errors->has('fel_llave_certificador') ? $inputError : $inputNormal }}">
                                    <span class="mt-1 block text-[11px] text-atlantia-ink/48">Llave publica del certificador.</span>
                                    @error('fel_llave_certificador') <p class="{{ $errorText }}">{{ $message }}</p> @enderror
                                </label>

                                <div class="lg:col-span-2">
                                    <span class="text-xs font-bold text-atlantia-ink">FEL activo</span>
                                    <div class="mt-2 flex items-center gap-3">
                                        <span @class([
                                            'relative inline-flex h-7 w-12 items-center rounded-full transition',
                                            'bg-atlantia-wine' => (bool) ($perfil?->fel_activo ?? false),
                                            'bg-atlantia-rose/25' => ! (bool) ($perfil?->fel_activo ?? false),
                                        ])>
                                            <span @class([
                                                'inline-block h-5 w-5 rounded-full bg-white shadow transition',
                                                'translate-x-6' => (bool) ($perfil?->fel_activo ?? false),
                                                'translate-x-1' => ! (bool) ($perfil?->fel_activo ?? false),
                                            ])></span>
                                        </span>
                                        <span class="text-xs text-atlantia-ink/55">Activa para emitir DTE despues de la validacion.</span>
                                    </div>
                                </div>
                            </div>

                            <aside class="rounded-lg border border-atlantia-rose/15 bg-atlantia-cream/45 p-4">
                                <p class="text-sm font-black text-atlantia-wine">Estado de FEL</p>
                                <span class="mt-3 inline-flex rounded-md bg-orange-50 px-3 py-1 text-xs font-black text-orange-700 ring-1 ring-orange-200">
                                    {{ $perfil?->fel_activo ? 'Validado' : 'Pendiente de validar' }}
                                </span>
                                <p class="mt-3 text-xs leading-5 text-atlantia-ink/58">
                                    Completa la configuracion y valida para confirmar la conexion con SAT.
                                </p>
                                <button type="button" class="mt-4 w-full rounded-lg border border-atlantia-wine/35 bg-white px-4 py-2 text-xs font-black text-atlantia-wine transition hover:bg-atlantia-blush">
                                    Validar configuracion
                                </button>
                            </aside>
                        </div>
                    </section>

                    <section class="border-t border-atlantia-rose/15 pt-5">
                        <div class="flex items-center gap-2">
                            <span class="grid h-8 w-8 place-items-center rounded-lg bg-atlantia-blush text-atlantia-wine">
                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <path d="M3 9h18"/><path d="M5 9V6l7-3 7 3v3"/><path d="M6 12v6"/><path d="M10 12v6"/><path d="M14 12v6"/><path d="M18 12v6"/><path d="M4 21h16"/>
                                </svg>
                            </span>
                            <h2 class="text-sm font-black text-atlantia-wine">4. Datos bancarios</h2>
                        </div>

                        <div class="mt-4 grid gap-4 lg:grid-cols-4">
                            <label class="block">
                                <span class="text-xs font-bold text-atlantia-ink">Banco</span>
                                <input name="banco_nombre" value="{{ $field('banco_nombre') }}" placeholder="Banco Industrial" class="{{ $inputBase }} {{ $errors->has('banco_nombre') ? $inputError : $inputNormal }}">
                                @error('banco_nombre') <p class="{{ $errorText }}">{{ $message }}</p> @enderror
                            </label>

                            <label class="block">
                                <span class="text-xs font-bold text-atlantia-ink">Tipo de cuenta</span>
                                <select name="cuenta_bancaria_tipo" class="{{ $inputBase }} {{ $errors->has('cuenta_bancaria_tipo') ? $inputError : $inputNormal }}">
                                    <option value="">Selecciona</option>
                                    @foreach ($cuentaLabels as $value => $label)
                                        <option value="{{ $value }}" @selected($field('cuenta_bancaria_tipo') === $value)>{{ $label }}</option>
                                    @endforeach
                                </select>
                                @error('cuenta_bancaria_tipo') <p class="{{ $errorText }}">{{ $message }}</p> @enderror
                            </label>

                            <label class="block">
                                <span class="text-xs font-bold text-atlantia-ink">Numero de cuenta</span>
                                <input name="cuenta_bancaria" value="{{ $field('cuenta_bancaria') }}" placeholder="123-456789-0" class="{{ $inputBase }} {{ $errors->has('cuenta_bancaria') ? $inputError : $inputNormal }}">
                                @error('cuenta_bancaria') <p class="{{ $errorText }}">{{ $message }}</p> @enderror
                            </label>

                            <label class="block">
                                <span class="text-xs font-bold text-atlantia-ink">Titular de la cuenta</span>
                                <input name="cuenta_bancaria_titular" value="{{ $field('cuenta_bancaria_titular', $field('razon_social', auth()->user()?->name)) }}" class="{{ $inputBase }} {{ $errors->has('cuenta_bancaria_titular') ? $inputError : $inputNormal }}">
                                @error('cuenta_bancaria_titular') <p class="{{ $errorText }}">{{ $message }}</p> @enderror
                            </label>
                        </div>
                    </section>
                </div>

                <div class="flex flex-col gap-3 border-t border-atlantia-rose/15 bg-white px-5 py-4 sm:flex-row sm:items-center sm:justify-between sm:px-6">
                    <a href="{{ route('vendedor.dashboard') }}" class="inline-flex items-center justify-center rounded-lg border border-atlantia-rose/35 bg-white px-5 py-2.5 text-sm font-black text-atlantia-wine transition hover:bg-atlantia-blush">
                        Cancelar
                    </a>
                    <div class="flex flex-col-reverse gap-3 sm:flex-row sm:items-center">
                        @if (session('success'))
                            <span class="text-xs font-bold text-emerald-700">Cambios guardados {{ now()->format('d/m/Y H:i') }}</span>
                        @endif
                        <button type="submit" class="inline-flex min-w-52 items-center justify-center gap-2 rounded-lg bg-atlantia-wine px-6 py-2.5 text-sm font-black text-white shadow-sm transition hover:bg-atlantia-wine-700">
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2Z"/><path d="M17 21v-8H7v8"/><path d="M7 3v5h8"/>
                            </svg>
                            Guardar cambios
                        </button>
                    </div>
                </div>
            </form>

            <aside class="space-y-4">
                <section class="rounded-lg border border-atlantia-rose/15 bg-white p-5 shadow-[0_12px_32px_rgba(42,16,24,0.05)]">
                    <h2 class="text-sm font-black text-atlantia-wine">Estado de configuracion</h2>
                    <div class="mt-5 flex items-center gap-4">
                        <div class="grid h-20 w-20 place-items-center rounded-full bg-atlantia-blush/70">
                            <div class="grid h-14 w-14 place-items-center rounded-full bg-white text-sm font-black text-atlantia-wine shadow-sm">
                                {{ $progress }}%
                            </div>
                        </div>
                        <div>
                            <p class="text-xl font-black text-atlantia-ink">{{ $completed }} de {{ $totalSections }}</p>
                            <p class="text-xs text-atlantia-ink/55">Secciones completas</p>
                        </div>
                    </div>

                    <div class="mt-5 space-y-2">
                        @foreach ($sections as $label => $done)
                            <div @class([
                                'flex items-center gap-3 rounded-lg px-3 py-2 text-xs font-bold',
                                'bg-atlantia-blush/55 text-atlantia-wine' => ! $done && $label === 'Configuracion FEL',
                                'text-atlantia-ink/65' => $done || $label !== 'Configuracion FEL',
                            ])>
                                <span @class([
                                    'grid h-5 w-5 place-items-center rounded-full border text-[10px]',
                                    'border-emerald-300 bg-emerald-50 text-emerald-700' => $done,
                                    'border-orange-300 bg-orange-50 text-orange-700' => ! $done && $label === 'Configuracion FEL',
                                    'border-slate-300 bg-white text-slate-400' => ! $done && $label !== 'Configuracion FEL',
                                ])>
                                    @if ($done)
                                        <svg class="h-3 w-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                            <path d="m5 12 4 4L19 6"/>
                                        </svg>
                                    @elseif ($label === 'Configuracion FEL')
                                        !
                                    @endif
                                </span>
                                {{ $label }}
                            </div>
                        @endforeach
                    </div>
                </section>

                <section class="rounded-lg border border-atlantia-rose/15 bg-white p-5 shadow-[0_12px_32px_rgba(42,16,24,0.05)]">
                    <h2 class="text-sm font-black text-atlantia-wine">Necesitas ayuda?</h2>
                    <div class="mt-4 space-y-3">
                        <a href="#" class="flex gap-3 rounded-lg p-2 transition hover:bg-atlantia-blush/45">
                            <span class="grid h-8 w-8 shrink-0 place-items-center rounded-full bg-atlantia-blush text-atlantia-wine">
                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M4 4.5A2.5 2.5 0 0 1 6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5z"/>
                                </svg>
                            </span>
                            <span>
                                <span class="block text-xs font-black text-atlantia-wine">Guia de configuracion FEL</span>
                                <span class="mt-0.5 block text-[11px] text-atlantia-ink/55">Paso a paso para configurar tu FEL.</span>
                            </span>
                        </a>
                        <a href="#" class="flex gap-3 rounded-lg p-2 transition hover:bg-atlantia-blush/45">
                            <span class="grid h-8 w-8 shrink-0 place-items-center rounded-full bg-atlantia-blush text-atlantia-wine">
                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <path d="M9 11h6"/><path d="M9 15h6"/><path d="M17 21H7a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h7l5 5v11a2 2 0 0 1-2 2Z"/><path d="M14 3v5h5"/>
                                </svg>
                            </span>
                            <span>
                                <span class="block text-xs font-black text-atlantia-wine">Requisitos SAT</span>
                                <span class="mt-0.5 block text-[11px] text-atlantia-ink/55">Conoce los requisitos fiscales.</span>
                            </span>
                        </a>
                        <a href="#" class="flex gap-3 rounded-lg p-2 transition hover:bg-atlantia-blush/45">
                            <span class="grid h-8 w-8 shrink-0 place-items-center rounded-full bg-atlantia-blush text-atlantia-wine">
                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <circle cx="12" cy="12" r="9"/><path d="M12 17h.01"/><path d="M9.5 9a2.5 2.5 0 1 1 3.9 2.1c-.9.6-1.4 1.2-1.4 2.4"/>
                                </svg>
                            </span>
                            <span>
                                <span class="block text-xs font-black text-atlantia-wine">Centro de ayuda</span>
                                <span class="mt-0.5 block text-[11px] text-atlantia-ink/55">Resuelve dudas frecuentes.</span>
                            </span>
                        </a>
                    </div>

                    <a href="{{ route('vendedor.dashboard') }}" class="mt-4 inline-flex w-full items-center justify-center gap-2 rounded-lg border border-atlantia-wine/35 bg-white px-4 py-2.5 text-xs font-black text-atlantia-wine transition hover:bg-atlantia-blush">
                        Contactar soporte
                    </a>
                </section>
            </aside>
        </div>
    </section>
@endsection
