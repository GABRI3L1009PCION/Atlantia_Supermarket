@extends('layouts.guest')

@section('content')
    @php
        $mailDriver = config('mail.default');
        $isLocalLogMailer = app()->environment('local') && in_array($mailDriver, ['log', 'array'], true);
    @endphp

    <x-page-header title="Verifica tu correo" subtitle="Antes de continuar, confirma tu direccion de correo electronico." />

    <div class="mb-5 rounded-lg border border-atlantia-rose/20 bg-atlantia-cream p-4 text-sm leading-6 text-atlantia-ink/75">
        <p>
            Enviamos un enlace de verificacion a
            <strong class="text-atlantia-ink">{{ auth()->user()?->email }}</strong>.
        </p>

        @if ($isLocalLogMailer)
            <div class="mt-3 rounded-md border border-amber-200 bg-amber-50 p-3 text-amber-900">
                <p class="font-bold">Modo desarrollo detectado</p>
                <p class="mt-1">
                    Tu aplicacion esta usando <strong>MAIL_MAILER={{ $mailDriver }}</strong>, por eso el correo no
                    llega a Gmail. Laravel guarda el enlace en <strong>storage/logs/laravel.log</strong>.
                </p>
            </div>
        @else
            <p class="mt-2">
                Si no aparece en tu bandeja principal, revisa spam, promociones o correo no deseado.
            </p>
        @endif
    </div>

    <form method="POST" action="{{ route('verification.send') }}" class="space-y-3">
        @csrf
        <x-ui.button type="submit" class="w-full">Reenviar verificacion</x-ui.button>
    </form>

    <form method="POST" action="{{ route('logout') }}" class="mt-4">
        @csrf
        <button type="submit" class="w-full rounded-md border border-atlantia-rose/30 px-4 py-2 text-sm font-bold text-atlantia-wine hover:bg-atlantia-blush">
            Usar otro correo
        </button>
    </form>
@endsection
