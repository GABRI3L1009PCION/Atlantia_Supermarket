@extends('layouts.guest')

@section('guestShellClass', 'w-full border-0 bg-transparent shadow-none backdrop-blur-0')
@section('guestMaxWidth', 'max-w-7xl')
@section('guestPadding', 'p-0')
@section('guestLogoClass', 'hidden')

@section('content')
    @php
        $logoPath = file_exists(public_path('images/logo.png')) ? 'images/logo.png' : 'images/atlantia-logo.svg';
        $fondoPath = file_exists(public_path('images/fondo.png')) ? asset('images/fondo.png') : null;
        $backHref = route('login');
        $prefilledCode = str_pad(substr(preg_replace('/\D/', '', old('code', '')), 0, 6), 6, ' ', STR_PAD_RIGHT);
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

        <div class="relative flex min-h-[520px] items-center justify-center px-3 py-3 sm:px-4 sm:py-4 lg:min-h-[560px] lg:px-6">
            <a
                href="{{ $backHref }}"
                class="absolute left-4 top-4 z-20 inline-flex h-9 items-center gap-2 rounded-xl border border-atlantia-rose/20 bg-white/96 px-3.5 text-sm font-bold text-atlantia-wine shadow-[0_10px_24px_rgba(135,22,61,0.12)] transition hover:bg-white sm:left-5 sm:top-5"
            >
                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                    <path d="M15 5L8 12L15 19" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
                Regresar
            </a>

            <section class="relative z-10 flex w-full items-center justify-center pt-12 lg:pt-0">
                <div class="relative grid min-h-[430px] w-full max-w-[690px] place-items-center rounded-[16px] border border-atlantia-rose/15 bg-white/96 px-4 py-8 shadow-[0_22px_58px_rgba(135,22,61,0.14)] backdrop-blur sm:px-5">
                    <img
                        src="{{ asset($logoPath) }}"
                        alt="Atlantia Supermarket"
                        class="absolute right-5 top-5 h-10 w-auto sm:h-11"
                    >

                    <div class="mx-auto w-full max-w-[640px] pt-10 sm:pt-8">
                    <form
                        method="POST"
                        action="{{ route('two-factor.verify') }}"
                        class="mx-auto grid w-full gap-4 md:grid-cols-[280px_1fr]"
                        data-protect-submit
                        x-data="{
                            code: @js(str_split($prefilledCode)),
                            sync() {
                                this.$refs.code.value = this.code.join('').replace(/\s/g, '');
                            },
                            setDigit(index, value) {
                                const clean = (value || '').replace(/\D/g, '').slice(-1);
                                this.code[index] = clean || ' ';
                                this.sync();
                                if (clean && index < 5) {
                                    this.$refs['digit' + (index + 1)].focus();
                                }
                            },
                            onBackspace(index, event) {
                                if ((this.code[index] === ' ' || this.code[index] === '') && index > 0) {
                                    this.$refs['digit' + (index - 1)].focus();
                                }
                                if (event.target.value === '' && index > 0) {
                                    this.code[index] = ' ';
                                    this.sync();
                                }
                            },
                            onPaste(event) {
                                const pasted = (event.clipboardData.getData('text') || '').replace(/\D/g, '').slice(0, 6).split('');
                                if (!pasted.length) return;
                                event.preventDefault();
                                for (let i = 0; i < 6; i++) {
                                    this.code[i] = pasted[i] ?? ' ';
                                }
                                this.sync();
                                const nextIndex = Math.min(pasted.length, 5);
                                this.$refs['digit' + nextIndex].focus();
                            },
                            init() {
                                this.code = this.code.slice(0, 6);
                                while (this.code.length < 6) this.code.push(' ');
                                this.sync();
                            }
                        }"
                    >
                        @csrf

                        <input type="hidden" name="code" x-ref="code" value="{{ old('code') }}">

                        <div class="space-y-3">
                            <div class="mx-auto flex max-w-[280px] flex-col items-center text-center">
                                <div class="flex items-center gap-3">
                                    <div class="flex h-11 w-11 items-center justify-center rounded-2xl border border-atlantia-rose/18 bg-atlantia-blush/45 text-atlantia-wine shadow-sm">
                                        <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                            <path d="M12 3L19 6V11.5C19 16.2 16 19.9 12 21C8 19.9 5 16.2 5 11.5V6L12 3Z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/>
                                            <path d="M10 11V9.8C10 8.8 10.8 8 11.8 8H12.2C13.2 8 14 8.8 14 9.8V11" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                                            <path d="M9.5 11H14.5V15.5H9.5V11Z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/>
                                        </svg>
                                    </div>
                                    <span class="rounded-lg bg-atlantia-wine px-3 py-1.5 text-base font-black text-white shadow-sm">
                                        2FA
                                    </span>
                                </div>

                                <p class="mt-3 text-xs font-black uppercase tracking-[0.08em] text-atlantia-rose">
                                    {{ ($challenge['setup_required'] ?? false) ? 'Configura tu autenticador' : 'Verificacion segura' }}
                                </p>
                                <h1 class="mt-2 text-[1.65rem] font-black leading-none text-atlantia-ink sm:text-[1.85rem]">
                                    {{ ($challenge['setup_required'] ?? false) ? 'Activa tu codigo seguro' : 'Confirma tu identidad' }}
                                </h1>
                                <p class="mt-2 max-w-[17rem] text-sm leading-6 text-atlantia-ink/68">
                                    @if ($challenge['setup_required'] ?? false)
                                        Escanea el QR y escribe el codigo generado.
                                    @else
                                        Ingresa el codigo de seis digitos para continuar.
                                    @endif
                                </p>
                            </div>

                            @if ($challenge['setup_required'] ?? false)
                                <div class="rounded-2xl border border-atlantia-rose/18 bg-atlantia-cream/45 p-3 text-center">
                                    <p class="text-xs font-black uppercase tracking-[0.08em] text-atlantia-rose">
                                        Escanea el codigo QR
                                    </p>
                                    <div class="mt-2 flex justify-center">
                                        <div
                                            class="flex h-40 w-40 items-center justify-center rounded-2xl border border-atlantia-rose/14 bg-white shadow-[0_10px_24px_rgba(135,22,61,0.08)]"
                                            data-otp-qr="{{ $challenge['otp_uri'] ?? '' }}"
                                        >
                                            <span class="text-xs font-semibold text-atlantia-ink/50">Generando QR...</span>
                                        </div>
                                    </div>
                                    <p class="mt-2 text-xs leading-5 text-atlantia-ink/65">
                                        Cuenta: {{ $challenge['account_label'] ?? '' }}
                                    </p>
                                </div>
                            @endif
                        </div>

                        <div class="space-y-3">
                            @if ($challenge['setup_required'] ?? false)
                                <div class="rounded-2xl border border-atlantia-rose/18 bg-atlantia-cream/45 p-3">
                                    <p class="text-xs font-black uppercase tracking-[0.08em] text-atlantia-rose">
                                        Clave manual
                                    </p>
                                    <p class="mt-2 break-words text-sm font-black tracking-[0.14em] text-atlantia-wine">
                                        {{ $challenge['manual_key'] ?? '' }}
                                    </p>
                                    <p class="mt-2 text-xs leading-5 text-atlantia-ink/60">
                                        Solo la necesitas si no puedes escanear el QR.
                                    </p>
                                </div>
                            @endif

                            <div>
                                <label class="block text-sm font-black text-atlantia-ink">
                                    Codigo de seguridad
                                </label>

                                <div class="mt-2.5 grid grid-cols-6 gap-2.5" @paste="onPaste($event)">
                                    @for ($i = 0; $i < 6; $i++)
                                        <input
                                            type="text"
                                            inputmode="numeric"
                                            maxlength="1"
                                            value="{{ trim($prefilledCode[$i]) }}"
                                            x-ref="digit{{ $i }}"
                                            @input="setDigit({{ $i }}, $event.target.value)"
                                            @keydown.backspace="onBackspace({{ $i }}, $event)"
                                            @keydown.arrow-left.prevent="{{ $i > 0 ? '$refs.digit' . ($i - 1) . '.focus()' : '$event.target.focus()' }}"
                                            @keydown.arrow-right.prevent="{{ $i < 5 ? '$refs.digit' . ($i + 1) . '.focus()' : '$event.target.focus()' }}"
                                            @focus="$event.target.select()"
                                            class="h-[52px] rounded-xl border border-atlantia-rose/18 bg-white text-center text-2xl font-black text-atlantia-wine shadow-[0_10px_24px_rgba(135,22,61,0.08)] outline-none transition focus:border-atlantia-wine focus:ring-4 focus:ring-atlantia-rose/18"
                                            aria-label="Digito {{ $i + 1 }} del codigo"
                                        >
                                    @endfor
                                </div>

                                <p class="mt-2 text-center text-xs text-atlantia-ink/60">
                                    El codigo debe tener exactamente seis numeros.
                                </p>

                                @error('code')
                                    <span class="mt-2 block text-center text-sm font-semibold text-red-700">{{ $message }}</span>
                                @enderror
                            </div>

                            <x-ui.button type="submit" class="flex w-full items-center justify-center gap-3 py-2.5 text-[0.95rem] shadow-[0_12px_22px_rgba(135,22,61,0.18)]">
                                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                    <path d="M7 10V8C7 5.2 9.2 3 12 3C14.8 3 17 5.2 17 8V10" stroke="currentColor" stroke-width="1.9" stroke-linecap="round"/>
                                    <path d="M6 10H18V20H6V10Z" stroke="currentColor" stroke-width="1.9" stroke-linejoin="round"/>
                                    <path d="M12 14V16.5" stroke="currentColor" stroke-width="1.9" stroke-linecap="round"/>
                                </svg>
                                Verificar y continuar
                            </x-ui.button>

                            <div class="grid gap-2.5 rounded-2xl border border-atlantia-rose/18 bg-atlantia-cream/50 p-2.5 text-atlantia-ink sm:grid-cols-[44px_1fr] sm:items-center">
                                <div class="mx-auto flex h-[42px] w-[42px] items-center justify-center rounded-full border border-atlantia-rose/18 bg-white text-atlantia-wine shadow-sm">
                                    <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                        <path d="M4 6H20V18H4V6Z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/>
                                        <path d="M8 21H16" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                                        <path d="M12 18V21" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                                    </svg>
                                </div>

                                <div class="text-center sm:text-left">
                                    @if ($challenge['setup_required'] ?? false)
                                        <p class="text-[0.98rem] font-black text-atlantia-wine">Configuralo una sola vez</p>
                                        <p class="mt-1 text-sm leading-5 text-atlantia-ink/72">
                                            Despues de esta activacion, tu app te dara el codigo cada 30 segundos.
                                        </p>
                                    @else
                                        <p class="text-[0.98rem] font-black text-atlantia-wine">No compartas este codigo</p>
                                        <p class="mt-1 text-sm leading-5 text-atlantia-ink/72">
                                            Si perdiste acceso a tu autenticador, solicita a un super administrador que reinicie tu segundo factor.
                                        </p>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </form>

                    <form method="POST" action="{{ route('logout') }}" class="mx-auto mt-4 max-w-[690px] text-center">
                        @csrf
                        <button type="submit" class="inline-flex items-center gap-2 border-t border-atlantia-rose/18 pt-4 text-sm font-bold text-atlantia-wine hover:text-atlantia-wine-700">
                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                <path d="M16 7C15 5.8 13.6 5 12 5C9 5 6.5 7.5 6.5 10.5C6.5 13.5 9 16 12 16C13.6 16 15 15.2 16 14" stroke="currentColor" stroke-width="1.9" stroke-linecap="round"/>
                                <path d="M14 12H21" stroke="currentColor" stroke-width="1.9" stroke-linecap="round"/>
                                <path d="M18 9L21 12L18 15" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                            Usar otra cuenta
                        </button>
                    </form>
                    </div>
                </div>
            </section>
        </div>
    </div>
@endsection
