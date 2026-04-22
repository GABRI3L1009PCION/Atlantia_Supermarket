@extends('layouts.guest')

@section('content')
    @php
        $mailDriver = config('mail.default');
        $isLocalLogMailer = app()->environment('local') && in_array($mailDriver, ['log', 'array'], true);
    @endphp

    <x-page-header title="Verifica tu correo" subtitle="Ingresa el codigo de 6 digitos que enviamos a tu correo." />

    <div class="mb-5 rounded-lg border border-atlantia-rose/20 bg-atlantia-cream p-4 text-sm leading-6 text-atlantia-ink/75">
        <p>
            Enviamos un codigo de verificacion a
            <strong class="text-atlantia-ink">{{ auth()->user()?->email }}</strong>.
        </p>

        @if ($isLocalLogMailer)
            <div class="mt-3 rounded-md border border-amber-200 bg-amber-50 p-3 text-amber-900">
                <p class="font-bold">Modo desarrollo detectado</p>
                <p class="mt-1">
                    Tu aplicacion esta usando <strong>MAIL_MAILER={{ $mailDriver }}</strong>, por eso el correo no
                    llega a Gmail. Laravel guarda el codigo en <strong>storage/logs/laravel.log</strong>.
                </p>
            </div>
        @else
            <p class="mt-2">
                Si no aparece en tu bandeja principal, revisa spam, promociones o correo no deseado.
            </p>
        @endif
    </div>

    <form method="POST" action="{{ route('verification.code') }}" class="space-y-4">
        @csrf
        <label class="block">
            <span class="mb-1 block text-sm font-bold text-atlantia-ink">Codigo de verificacion</span>
            <input
                name="code"
                type="text"
                inputmode="numeric"
                pattern="[0-9]{6}"
                maxlength="6"
                autocomplete="one-time-code"
                placeholder="123456"
                class="w-full rounded-md border border-atlantia-rose/40 bg-white px-4 py-3 text-center text-2xl font-black tracking-[0.35em] text-atlantia-ink shadow-sm focus:border-atlantia-wine focus:outline-none focus:ring-2 focus:ring-atlantia-rose"
                required
            >
            @error('code')
                <span class="mt-1 block text-sm font-semibold text-red-700">{{ $message }}</span>
            @enderror
        </label>

        <x-ui.button type="submit" class="w-full">Confirmar correo</x-ui.button>
    </form>

    <form method="POST" action="{{ route('verification.send') }}" class="mt-3">
        @csrf
        <button type="submit" class="w-full rounded-md border border-atlantia-rose/30 px-4 py-2 text-sm font-bold text-atlantia-wine hover:bg-atlantia-blush">
            Reenviar codigo
        </button>
    </form>

    <form method="POST" action="{{ route('logout') }}" class="mt-4">
        @csrf
        <button type="submit" class="w-full rounded-md border border-atlantia-rose/30 px-4 py-2 text-sm font-bold text-atlantia-wine hover:bg-atlantia-blush">
            Usar otro correo
        </button>
    </form>
@endsection
