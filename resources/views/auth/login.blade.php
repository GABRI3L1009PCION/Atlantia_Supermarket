@extends('layouts.guest')

@section('guestShellClass', 'w-full border-0 bg-transparent shadow-none backdrop-blur-0')
@section('guestMaxWidth', 'max-w-7xl')
@section('guestPadding', 'p-0')
@section('guestLogoClass', 'hidden')

@section('content')
    @php
        $logoPath = file_exists(public_path('images/logo.png')) ? 'images/logo.png' : 'images/atlantia-logo.svg';
        $fondoPath = file_exists(public_path('images/fondo.png')) ? asset('images/fondo.png') : null;
        $backHref = route('home');
    @endphp

    <div class="relative overflow-hidden rounded-[24px] shadow-[0_24px_70px_rgba(135,22,61,0.14)]">
        @if ($fondoPath)
            <div
                class="absolute inset-0"
                style="background: url('{{ $fondoPath }}') center center / cover no-repeat;"
                aria-hidden="true"
            ></div>
        @else
            <div
                class="absolute inset-0 bg-[linear-gradient(135deg,#fffafc_0%,#fff4f8_42%,#f7dce8_100%)]"
                aria-hidden="true"
            ></div>
        @endif

        <div class="relative grid min-h-[340px] lg:grid-cols-[0.9fr_0.78fr]">
            <aside class="relative flex min-h-[190px] flex-col p-3 sm:p-4 lg:p-4">

                <div class="relative z-10 flex items-start justify-between gap-4">
                    <a
                        href="{{ $backHref }}"
                        class="inline-flex h-9 items-center gap-2 rounded-xl border border-atlantia-rose/20 bg-white/96 px-3.5 text-sm font-bold text-atlantia-wine shadow-[0_10px_24px_rgba(135,22,61,0.12)] transition hover:bg-white"
                    >
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                            <path d="M15 5L8 12L15 19" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                        Regresar
                    </a>
                </div>

                <div class="relative z-10 flex flex-1 flex-col items-center justify-start pt-10 text-center sm:pt-12 lg:pt-14">
                    <img
                        src="{{ asset($logoPath) }}"
                        alt="Atlantia Supermarket"
                        class="h-12 w-auto sm:h-14 lg:h-[4rem]"
                    >

                    <div class="mt-3 max-w-[15rem] space-y-2 px-0 py-1">
                        <p class="text-[0.64rem] font-extrabold uppercase tracking-[0.22em] text-atlantia-wine sm:text-[0.72rem]">
                            Atlantia Supermarket
                        </p>

                        <div class="mx-auto flex w-full max-w-[140px] items-center justify-center gap-3 text-atlantia-wine/80">
                            <span class="h-px flex-1 bg-atlantia-rose/35"></span>
                            <svg class="h-5 w-5 shrink-0" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                <path d="M4 5H6L8.1 15.2C8.3 16.2 9.2 17 10.3 17H17.8C18.8 17 19.6 16.4 19.9 15.5L21 9H7" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"/>
                                <path d="M10 21C10.6 21 11 20.6 11 20C11 19.4 10.6 19 10 19C9.4 19 9 19.4 9 20C9 20.6 9.4 21 10 21Z" fill="currentColor"/>
                                <path d="M18 21C18.6 21 19 20.6 19 20C19 19.4 18.6 19 18 19C17.4 19 17 19.4 17 20C17 20.6 17.4 21 18 21Z" fill="currentColor"/>
                            </svg>
                            <span class="h-px flex-1 bg-atlantia-rose/35"></span>
                        </div>

                        <p class="mx-auto max-w-[11rem] text-sm leading-5 text-atlantia-ink/78 sm:text-[0.92rem]">
                            Tu supermercado de confianza,
                        </p>
                        <p class="mx-auto max-w-[9rem] text-sm leading-5 text-atlantia-ink/78 sm:text-[0.92rem]">
                            siempre cerca de ti.
                        </p>
                    </div>
                </div>
            </aside>

            <section class="flex items-center justify-center px-3 py-3 sm:px-4 lg:px-4">
                <div class="w-full max-w-[400px] rounded-[16px] border border-atlantia-rose/15 bg-white px-4 py-3.5 shadow-[0_18px_46px_rgba(135,22,61,0.12)] sm:px-4.5">
                    <div class="mx-auto flex max-w-[345px] flex-col items-center text-center">
                        <div class="flex h-11 w-11 items-center justify-center rounded-full bg-atlantia-blush/75 text-atlantia-wine">
                            <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                <path d="M7 10V8C7 5.2 9.2 3 12 3C14.8 3 17 5.2 17 8V10" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                                <path d="M6 10H18V20H6V10Z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/>
                                <path d="M12 14V16.5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                            </svg>
                        </div>

                        <h1 class="mt-2.5 text-[1.95rem] font-black leading-none text-atlantia-wine sm:text-[2.2rem]">
                            Ingresar
                        </h1>
                        <p class="mt-1 text-sm leading-5 text-atlantia-ink/65 sm:text-[0.92rem]">
                            Accede a tu cuenta de Atlantia Supermarket.
                        </p>
                    </div>

                    <form method="POST" action="{{ route('login.store') }}" class="mx-auto mt-3.5 max-w-[345px] space-y-2.5" data-protect-submit x-data="{ showPassword: false }">
                        @csrf

                        <label for="email" class="block">
                            <span class="mb-1 block text-sm font-black text-atlantia-ink">Correo electronico</span>
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
                            <span class="mb-1 block text-sm font-black text-atlantia-ink">Contrasena</span>
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
                                    placeholder="Ingresa tu contrasena"
                                    class="min-w-0 flex-1 border-0 bg-transparent px-3 py-2.5 text-[0.95rem] text-atlantia-ink placeholder:text-atlantia-ink/42 focus:outline-none focus:ring-0"
                                    aria-invalid="@error('password') true @else false @enderror"
                                >
                                <button
                                    type="button"
                                    class="shrink-0 rounded-md p-1 text-atlantia-ink/50 transition hover:bg-atlantia-blush hover:text-atlantia-wine"
                                    aria-label="Mostrar u ocultar contrasena"
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

                        <x-ui.button type="submit" class="w-full py-2.5 text-[0.95rem] shadow-[0_12px_22px_rgba(135,22,61,0.18)]">
                            Ingresar
                        </x-ui.button>
                    </form>

                    <a href="{{ route('password.request') }}" class="mx-auto mt-2.5 block w-fit border-b border-dashed border-atlantia-wine text-sm font-bold text-atlantia-wine hover:text-atlantia-wine-700">
                        Olvide mi contrasena
                    </a>

                    <div class="mx-auto mt-3 grid max-w-[345px] gap-2.5 rounded-2xl border border-atlantia-rose/18 bg-atlantia-cream/50 p-2.5 text-atlantia-ink sm:grid-cols-[44px_1fr] sm:items-center">
                        <div class="mx-auto flex h-[42px] w-[42px] items-center justify-center rounded-full border border-atlantia-rose/18 bg-white text-atlantia-wine shadow-sm">
                            <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                <path d="M4 5H6L8.1 15.2C8.3 16.2 9.2 17 10.3 17H17.8C18.8 17 19.6 16.4 19.9 15.5L21 9H7" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                <path d="M10 21C10.6 21 11 20.6 11 20C11 19.4 10.6 19 10 19C9.4 19 9 19.4 9 20C9 20.6 9.4 21 10 21Z" fill="currentColor"/>
                                <path d="M18 21C18.6 21 19 20.6 19 20C19 19.4 18.6 19 18 19C17.4 19 17 19.4 17 20C17 20.6 17.4 21 18 21Z" fill="currentColor"/>
                            </svg>
                        </div>

                        <div class="text-center sm:text-left">
                            <p class="text-[0.98rem] font-black text-atlantia-wine">Eres nuevo en Atlantia?</p>
                            <p class="mt-1 text-sm leading-5 text-atlantia-ink/72">
                                Crea tu cuenta gratis para guardar direcciones, realizar pedidos y seguir tus entregas.
                            </p>
                            <a href="{{ route('register') }}" class="mt-1 inline-flex items-center gap-2 text-sm font-black text-atlantia-wine hover:text-atlantia-wine-700">
                                Registrarme como cliente
                                <span aria-hidden="true">&rarr;</span>
                            </a>
                        </div>
                    </div>
                </div>
            </section>
        </div>
    </div>
@endsection
