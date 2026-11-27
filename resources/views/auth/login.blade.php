@extends('layouts.marketplace')

@section('content')
<x-auth.panel titulo="Ingresar" subtitulo="Accede a tu cuenta de Atlantia Store para comprar, guardar direcciones y seguir tus pedidos.">
                    <form method="POST" action="{{ route('login.store') }}" class="space-y-3" data-protect-submit x-data="{ showPassword: false }">
                        @csrf

                        <label for="email" class="block">
                            <span class="mb-1 block text-sm font-black text-atlantia-ink">Correo electrónico</span>
                            <span class="flex min-h-[44px] items-center rounded-xl border border-atlantia-rose/45 bg-white px-3.5 shadow-sm transition focus-within:border-atlantia-wine focus-within:ring-2 focus-within:ring-atlantia-rose/35">
                                <svg class="h-5 w-5 shrink-0 text-atlantia-ink/45" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                    <path d="M4 6.5H20V17.5H4V6.5Z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/>
                                    <path d="M5 7.5L12 13L19 7.5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                                <input
                                    id="email"
                                    name="email"
                                    type="email"
                                    value="{{ old('email') }}"
                                    required
                                    autofocus
                                    autocomplete="email"
                                    placeholder="ejemplo@correo.com"
                                    class="min-w-0 flex-1 border-0 bg-transparent px-3 py-2.5 text-[0.95rem] text-atlantia-ink placeholder:text-atlantia-ink/42 focus:outline-none focus:ring-0"
                                    aria-invalid="@error('email') true @else false @enderror"
                                >
                            </span>
                            @error('email')
                                <span class="mt-2 block text-sm font-semibold text-red-700">{{ $message }}</span>
                            @enderror
                        </label>

                        <label for="password" class="block">
                            <span class="mb-1 block text-sm font-black text-atlantia-ink">Contraseña</span>
                            <span class="flex min-h-[44px] items-center rounded-xl border border-atlantia-rose/35 bg-white px-3.5 shadow-sm transition focus-within:border-atlantia-wine focus-within:ring-2 focus-within:ring-atlantia-rose/35">
                                <svg class="h-5 w-5 shrink-0 text-atlantia-ink/45" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                    <path d="M7 10V8C7 5.2 9.2 3 12 3C14.8 3 17 5.2 17 8V10" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                                    <path d="M6 10H18V20H6V10Z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/>
                                    <path d="M12 14V16.5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                                </svg>
                                <input
                                    id="password"
                                    name="password"
                                    :type="showPassword ? 'text' : 'password'"
                                    required
                                    autocomplete="current-password"
                                    placeholder="Ingresa tu contraseña"
                                    class="min-w-0 flex-1 border-0 bg-transparent px-3 py-2.5 text-[0.95rem] text-atlantia-ink placeholder:text-atlantia-ink/42 focus:outline-none focus:ring-0"
                                    aria-invalid="@error('password') true @else false @enderror"
                                >
                                <button
                                    type="button"
                                    class="shrink-0 rounded-md p-1 text-atlantia-ink/50 transition hover:bg-atlantia-blush hover:text-atlantia-wine"
                                    aria-label="Mostrar u ocultar contraseña"
                                    @click="showPassword = !showPassword"
                                >
                                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                        <path d="M3.5 12C5.5 8.7 8.3 7 12 7C15.7 7 18.5 8.7 20.5 12C18.5 15.3 15.7 17 12 17C8.3 17 5.5 15.3 3.5 12Z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/>
                                        <path d="M12 14.2C13.2 14.2 14.2 13.2 14.2 12C14.2 10.8 13.2 9.8 12 9.8C10.8 9.8 9.8 10.8 9.8 12C9.8 13.2 10.8 14.2 12 14.2Z" stroke="currentColor" stroke-width="1.8"/>
                                    </svg>
                                </button>
                            </span>
                            @error('password')
                                <span class="mt-2 block text-sm font-semibold text-red-700">{{ $message }}</span>
                            @enderror
                        </label>

                        <div class="flex items-center justify-between gap-3 pt-0.5">
                            <label class="inline-flex items-center gap-2 text-sm font-semibold text-atlantia-ink/75">
                                <input type="checkbox" name="remember" value="1" class="h-4 w-4 rounded border-atlantia-rose/50 text-atlantia-wine focus:ring-atlantia-rose" @checked(old('remember'))>
                                Recordarme
                            </label>
                            <a href="{{ route('password.request') }}" class="text-sm font-bold text-atlantia-wine hover:text-atlantia-wine-700">Olvidé mi contraseña</a>
                        </div>

                        <x-ui.button type="submit" class="mt-1 w-full py-2.5 text-[0.95rem] shadow-[0_12px_22px_rgba(127,18,32,0.18)]">
                            Ingresar
                        </x-ui.button>
                    </form>

                    {{-- Acceso sin contrasena para clientes (el codigo por telefono solo si WhatsApp esta activo). --}}
                    @php
                        $accesoTelefono = app(\App\Services\Notificaciones\WhatsAppService::class)->habilitado();
                        $accesoGoogle = \App\Http\Controllers\Auth\AccesoRapidoController::googleConfigurado();
                    @endphp
                    @if ($accesoTelefono || $accesoGoogle)
                    <div class="mt-4">
                        <p class="flex items-center gap-3 text-xs font-bold text-atlantia-ink/45 before:h-px before:flex-1 before:bg-atlantia-rose/20 after:h-px after:flex-1 after:bg-atlantia-rose/20">o entra sin contraseña</p>
                        <div class="mt-2.5 grid gap-2">
                            @if ($accesoTelefono)
                            <a href="{{ route('acceso.telefono') }}" class="inline-flex items-center justify-center gap-2 rounded-xl bg-[#25D366] px-4 py-2.5 text-sm font-black text-white hover:brightness-95" data-acceso-telefono>
                                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12.04 2C6.58 2 2.13 6.45 2.13 11.91c0 1.75.46 3.45 1.32 4.95L2.05 22l5.25-1.38a9.87 9.87 0 0 0 4.74 1.21h.01c5.46 0 9.91-4.45 9.91-9.91A9.9 9.9 0 0 0 12.04 2Z"/></svg>
                                Con mi teléfono (código por WhatsApp)
                            </a>
                            @endif
                            @if ($accesoGoogle)
                                <a href="{{ route('acceso.google') }}" class="inline-flex items-center justify-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-black text-slate-700 hover:bg-slate-50" data-acceso-google>
                                    <svg class="h-5 w-5" viewBox="0 0 24 24" aria-hidden="true"><path fill="#EA4335" d="M12 10.2v3.9h5.5c-.2 1.3-1.6 3.8-5.5 3.8-3.3 0-6-2.7-6-6.1s2.7-6.1 6-6.1c1.9 0 3.1.8 3.8 1.5l2.6-2.5C16.8 3.2 14.6 2.2 12 2.2 6.6 2.2 2.2 6.6 2.2 12S6.6 21.8 12 21.8c5.7 0 9.4-4 9.4-9.6 0-.6-.1-1.1-.2-1.6H12Z"/></svg>
                                    Continuar con Google
                                </a>
                            @endif
                        </div>
                    </div>
                    @endif

    <x-slot:pie>
        <p class="mt-4 text-center text-sm text-atlantia-ink/65">
            ¿Eres nuevo en Atlantia?
            <a href="{{ route('register') }}" class="font-black text-atlantia-wine hover:text-atlantia-wine-700">Crear cuenta gratis</a>
        </p>
    </x-slot:pie>
</x-auth.panel>
@endsection
