@extends('layouts.marketplace')

@section('content')
@php
    $configurar = (bool) ($challenge['setup_required'] ?? false);
    $prefilledCode = str_pad(substr(preg_replace('/\D/', '', old('code', '')), 0, 6), 6, ' ', STR_PAD_RIGHT);
@endphp

<x-auth.panel
    :titulo="$configurar ? 'Activa tu código seguro' : 'Confirma tu identidad'"
    :subtitulo="$configurar ? 'Escanea el QR con tu app de autenticación (Google Authenticator, Authy o Microsoft Authenticator) y escribe el código que te muestre.' : 'Escribe el código de seis dígitos que muestra tu app de autenticación.'"
    :etiqueta="$configurar ? 'Configura tu autenticador' : 'Verificación en dos pasos'"
>
    <form
        method="POST"
        action="{{ route('two-factor.verify') }}"
        class="space-y-4"
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

        @if ($configurar)
            <div class="flex flex-col items-center gap-3 rounded-xl bg-atlantia-blush/40 p-3 text-center sm:flex-row sm:text-left">
                <div
                    class="flex h-36 w-36 shrink-0 items-center justify-center rounded-xl border border-atlantia-rose/15 bg-white"
                    data-otp-qr="{{ $challenge['otp_uri'] ?? '' }}"
                >
                    <span class="text-xs font-semibold text-atlantia-ink/50">Generando QR...</span>
                </div>
                <div class="min-w-0 text-xs leading-5 text-atlantia-ink/65">
                    <p class="font-black uppercase tracking-wide text-atlantia-wine">Cuenta</p>
                    <p class="break-words">{{ $challenge['account_label'] ?? '' }}</p>
                    <p class="mt-2 font-black uppercase tracking-wide text-atlantia-wine">Clave manual</p>
                    <p class="break-words font-black tracking-[0.12em] text-atlantia-ink">{{ $challenge['manual_key'] ?? '' }}</p>
                    <p class="mt-1">Úsala solo si no puedes escanear el QR.</p>
                </div>
            </div>
        @endif

        <div>
            <span class="mb-1.5 block text-sm font-black text-atlantia-ink">Código de seguridad</span>
            <div class="grid grid-cols-6 gap-2" @paste="onPaste($event)">
                @for ($i = 0; $i < 6; $i++)
                    <input
                        type="text"
                        inputmode="numeric"
                        maxlength="1"
                        @if ($i === 0) autofocus autocomplete="one-time-code" @endif
                        value="{{ trim($prefilledCode[$i]) }}"
                        x-ref="digit{{ $i }}"
                        @input="setDigit({{ $i }}, $event.target.value)"
                        @keydown.backspace="onBackspace({{ $i }}, $event)"
                        @keydown.arrow-left.prevent="{{ $i > 0 ? '$refs.digit' . ($i - 1) . '.focus()' : '$event.target.focus()' }}"
                        @keydown.arrow-right.prevent="{{ $i < 5 ? '$refs.digit' . ($i + 1) . '.focus()' : '$event.target.focus()' }}"
                        @focus="$event.target.select()"
                        class="h-12 w-full min-w-0 rounded-xl border border-atlantia-rose/35 bg-white text-center text-xl font-black text-atlantia-wine shadow-sm outline-none transition focus:border-atlantia-wine focus:ring-2 focus:ring-atlantia-rose/35 sm:h-14 sm:text-2xl"
                        aria-label="Dígito {{ $i + 1 }} del código"
                    >
                @endfor
            </div>
            @error('code')
                <span class="mt-2 block text-sm font-semibold text-red-700">{{ $message }}</span>
            @enderror
        </div>

        <x-ui.button type="submit" class="w-full py-2.5 text-[0.95rem] shadow-[0_12px_22px_rgba(127,18,32,0.18)]">
            Verificar y continuar
        </x-ui.button>

        <p class="flex gap-2 rounded-xl bg-atlantia-blush/40 px-3 py-2.5 text-xs leading-5 text-atlantia-ink/70">
            <svg class="mt-0.5 h-4 w-4 shrink-0 text-atlantia-wine" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M12 3 19 6v5c0 4.5-2.8 8.4-7 10-4.2-1.6-7-5.5-7-10V6l7-3Z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/></svg>
            @if ($configurar)
                <span>Solo lo configuras una vez. Después, tu app te dará un código nuevo cada 30 segundos.</span>
            @else
                <span><strong class="text-atlantia-wine">No compartas este código.</strong> Si perdiste tu teléfono, usa uno de tus códigos de respaldo o pide a Atlantia que reinicie tu segundo factor.</span>
            @endif
        </p>
    </form>

    @unless ($configurar)
        {{-- Entrada alternativa con un código de respaldo --}}
        <details class="mt-4 rounded-xl border border-atlantia-rose/20 px-4 py-3" @if (old('respaldo')) open @endif>
            <summary class="cursor-pointer text-sm font-black text-atlantia-wine">¿Perdiste tu teléfono? Usa un código de respaldo</summary>
            <form method="POST" action="{{ route('two-factor.verify') }}" class="mt-3 flex gap-2" data-protect-submit data-form-respaldo>
                @csrf
                <input type="hidden" name="respaldo" value="1">
                <input name="code" required maxlength="11" autocomplete="off" placeholder="XXXXX-XXXXX" aria-label="Código de respaldo"
                    class="min-w-0 flex-1 rounded-lg border border-atlantia-rose/35 px-3 py-2 font-mono text-sm uppercase tracking-wider focus:border-atlantia-wine focus:outline-none focus:ring-2 focus:ring-atlantia-rose/20">
                <button class="rounded-lg bg-atlantia-wine px-4 py-2 text-sm font-black text-white hover:bg-atlantia-wine-700">Entrar</button>
            </form>
            <p class="mt-2 text-xs text-atlantia-ink/55">Cada código sirve una sola vez. Al entrar, configura de nuevo tu autenticador.</p>
        </details>
    @endunless

    <x-slot:pie>
        <form method="POST" action="{{ route('logout') }}" class="mt-4 text-center">
            @csrf
            <button type="submit" class="text-sm font-bold text-atlantia-wine hover:text-atlantia-wine-700">Usar otra cuenta</button>
        </form>
    </x-slot:pie>
</x-auth.panel>
@endsection
