@extends($user->hasRole('cliente') ? 'layouts.marketplace' : 'layouts.app')

@section('content')
    @php
        $campo = 'mt-1 h-10 w-full rounded-lg border border-atlantia-rose/30 bg-white px-3 text-sm text-atlantia-ink outline-none focus:border-atlantia-wine focus:ring-2 focus:ring-atlantia-blush';
        $etiqueta = 'text-xs font-bold text-atlantia-ink/70';
        $dispositivo = function (?string $agente): string {
            $agente = (string) $agente;
            $navegador = match (true) {
                str_contains($agente, 'Edg/') => 'Edge',
                str_contains($agente, 'OPR/') => 'Opera',
                str_contains($agente, 'Chrome/') => 'Chrome',
                str_contains($agente, 'Firefox/') => 'Firefox',
                str_contains($agente, 'Safari/') => 'Safari',
                default => 'Navegador',
            };
            $sistema = match (true) {
                str_contains($agente, 'Android') => 'Android',
                str_contains($agente, 'iPhone') || str_contains($agente, 'iPad') => 'iPhone / iPad',
                str_contains($agente, 'Windows') => 'Windows',
                str_contains($agente, 'Mac OS') => 'Mac',
                str_contains($agente, 'Linux') => 'Linux',
                default => 'dispositivo desconocido',
            };

            return $navegador.' en '.$sistema;
        };
    @endphp

    <section class="mx-auto max-w-[760px] space-y-4 pb-10 {{ $user->hasRole('cliente') ? 'px-4 pt-4' : '' }}">
        <div>
            <h1 class="text-2xl font-black text-atlantia-ink">Seguridad de la cuenta</h1>
            <p class="mt-0.5 text-sm text-atlantia-ink/60">Cambia tu contraseña, revisa la verificación en dos pasos y cierra sesiones que no reconozcas.</p>
        </div>

        {{-- Contraseña --}}
        <article class="rounded-xl border border-atlantia-rose/15 bg-white p-5" id="contrasena">
            <h2 class="text-base font-black text-atlantia-ink">Contraseña</h2>
            <p class="text-xs text-atlantia-ink/55">Al cambiarla, cerramos tu sesión en los demás dispositivos.</p>
            <form method="POST" action="{{ route('cuenta.seguridad.contrasena') }}" class="mt-3 grid gap-3 sm:grid-cols-3">
                @csrf
                @method('PUT')
                <label class="block">
                    <span class="{{ $etiqueta }}">Contraseña actual</span>
                    <input type="password" name="actual" required autocomplete="current-password" class="{{ $campo }}">
                </label>
                <label class="block">
                    <span class="{{ $etiqueta }}">Nueva contraseña</span>
                    <input type="password" name="nueva" required minlength="8" autocomplete="new-password" class="{{ $campo }}">
                </label>
                <label class="block">
                    <span class="{{ $etiqueta }}">Repite la nueva</span>
                    <input type="password" name="nueva_confirmation" required minlength="8" autocomplete="new-password" class="{{ $campo }}">
                </label>
                @if ($errors->contrasena->any())
                    <ul class="space-y-0.5 text-xs font-bold text-red-600 sm:col-span-3">
                        @foreach ($errors->contrasena->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                @endif
                <div class="flex items-center justify-between gap-3 sm:col-span-3">
                    <p class="text-[11px] text-atlantia-ink/50">Mínimo 8 caracteres, con mayúscula, minúscula, número y símbolo.</p>
                    <button type="submit" class="h-10 shrink-0 rounded-lg bg-atlantia-wine px-4 text-sm font-bold text-white hover:bg-atlantia-wine-700">Cambiar contraseña</button>
                </div>
            </form>
        </article>

        {{-- Verificación en dos pasos --}}
        <article class="rounded-xl border border-atlantia-rose/15 bg-white p-5" id="dos-pasos">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div>
                    <h2 class="text-base font-black text-atlantia-ink">Verificación en dos pasos</h2>
                    <p class="text-xs text-atlantia-ink/55">Con Google Authenticator, además de tu contraseña se pide un código de 6 dígitos.</p>
                </div>
                @if ($dosPasosActiva)
                    <span class="rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-bold text-emerald-700 ring-1 ring-emerald-200">Activa</span>
                @elseif ($user->two_factor_enabled)
                    <span class="rounded-full bg-amber-50 px-2.5 py-1 text-xs font-bold text-amber-800 ring-1 ring-amber-200">Pendiente de confirmar</span>
                @else
                    <span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-bold text-slate-600 ring-1 ring-slate-200">Inactiva</span>
                @endif
            </div>

            @if ($dosPasosActiva)
                <p class="mt-3 text-sm text-atlantia-ink/75">
                    Te quedan <strong class="{{ $codigosRestantes <= 2 ? 'text-rose-700' : 'text-atlantia-ink' }}">{{ $codigosRestantes }} {{ $codigosRestantes === 1 ? 'código' : 'códigos' }} de respaldo</strong>.
                    Sirven para entrar si pierdes el teléfono; cada uno se usa una sola vez.
                </p>

                @if (is_array($codigosNuevos) && $codigosNuevos !== [])
                    <div class="mt-3 rounded-lg border border-amber-200 bg-amber-50 p-3" data-codigos-nuevos>
                        <p class="text-xs font-black text-amber-900">Guarda estos códigos ahora. No los volveremos a mostrar.</p>
                        <ul class="mt-2 grid grid-cols-2 gap-1.5 font-mono text-sm text-atlantia-ink sm:grid-cols-4">
                            @foreach ($codigosNuevos as $codigo)
                                <li class="rounded bg-white px-2 py-1 text-center ring-1 ring-amber-200">{{ substr($codigo, 0, 5) }}-{{ substr($codigo, 5) }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form method="POST" action="{{ route('cuenta.seguridad.codigos') }}" class="mt-3 flex flex-wrap items-end gap-2" data-confirm="¿Generar códigos nuevos? Los que tienes ahora dejarán de funcionar.">
                    @csrf
                    <label class="block min-w-[200px] flex-1">
                        <span class="{{ $etiqueta }}">Confirma con tu contraseña</span>
                        <input type="password" name="contrasena" required autocomplete="current-password" class="{{ $campo }}">
                    </label>
                    <button type="submit" class="h-10 rounded-lg border border-atlantia-rose/30 px-4 text-sm font-bold text-atlantia-wine hover:bg-atlantia-blush">Generar códigos nuevos</button>
                </form>
                @error('contrasena', 'codigos') <p class="mt-1 text-xs font-bold text-red-600">{{ $message }}</p> @enderror
            @elseif ($user->two_factor_enabled)
                <p class="mt-3 text-sm text-atlantia-ink/75">La próxima vez que inicies sesión te mostraremos un código QR. Escanéalo con Google Authenticator y escribe el código de 6 dígitos para terminar.</p>
            @elseif ($user->requiresAdministrativeTwoFactor())
                <p class="mt-3 text-sm text-atlantia-ink/75">Tu cuenta la necesita: al iniciar sesión te pediremos configurarla con Google Authenticator.</p>
            @else
                <form method="POST" action="{{ route('two-factor.enable') }}" class="mt-3 flex flex-wrap items-center justify-between gap-3" data-confirm="¿Activar la verificación en dos pasos? La próxima vez que entres te mostraremos el código QR para Google Authenticator.">
                    @csrf
                    <p class="max-w-md text-sm text-atlantia-ink/75">Recomendado. Al activarla, la próxima vez que entres te mostraremos un código QR para escanear con Google Authenticator.</p>
                    <button type="submit" class="h-10 rounded-lg bg-atlantia-wine px-4 text-sm font-bold text-white hover:bg-atlantia-wine-700">Activar</button>
                </form>
            @endif
        </article>

        {{-- Sesiones --}}
        <article class="rounded-xl border border-atlantia-rose/15 bg-white p-5" id="sesiones">
            <h2 class="text-base font-black text-atlantia-ink">Dónde has entrado</h2>
            <p class="text-xs text-atlantia-ink/55">Tus últimos inicios de sesión. Si ves uno que no reconoces, cambia tu contraseña.</p>

            <ul class="mt-3 divide-y divide-atlantia-rose/10 rounded-lg border border-atlantia-rose/10">
                @forelse ($accesos as $acceso)
                    <li class="flex items-center justify-between gap-3 px-3 py-2 text-sm">
                        <span class="min-w-0 truncate text-atlantia-ink">{{ $dispositivo($acceso->user_agent) }} <span class="text-atlantia-ink/45">· {{ $acceso->ip_address }}</span></span>
                        <span class="shrink-0 text-xs text-atlantia-ink/55">{{ \Illuminate\Support\Carbon::parse($acceso->attempted_at)->format('d/m/Y h:i a') }}</span>
                    </li>
                @empty
                    <li class="px-3 py-2 text-sm text-atlantia-ink/55">Aún no hay registros.</li>
                @endforelse
            </ul>

            <form method="POST" action="{{ route('cuenta.seguridad.cerrar-otras') }}" class="mt-3 flex flex-wrap items-end gap-2" data-confirm="¿Cerrar tu sesión en todos los demás dispositivos? Este se queda abierto.">
                @csrf
                <label class="block min-w-[200px] flex-1">
                    <span class="{{ $etiqueta }}">Confirma con tu contraseña</span>
                    <input type="password" name="contrasena" required autocomplete="current-password" class="{{ $campo }}">
                </label>
                <button type="submit" class="h-10 rounded-lg bg-atlantia-ink px-4 text-sm font-bold text-white hover:bg-atlantia-ink/85">Cerrar sesión en los demás dispositivos</button>
            </form>
            @error('contrasena', 'sesiones') <p class="mt-1 text-xs font-bold text-red-600">{{ $message }}</p> @enderror
        </article>
    </section>
@endsection
