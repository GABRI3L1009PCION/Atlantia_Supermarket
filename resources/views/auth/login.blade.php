@extends('layouts.guest')

@section('content')
    <x-page-header title="Ingresar" subtitle="Accede a tu cuenta de Atlantia Supermarket." />

    <form method="POST" action="{{ route('login.store') }}" class="space-y-4" data-protect-submit>
        @csrf
        <x-ui.input name="email" type="email" label="Correo electronico" required autofocus />
        <x-ui.input name="password" type="password" label="Contrasena" required />
        <x-ui.button type="submit" class="w-full">Ingresar</x-ui.button>
    </form>

    <a href="{{ route('password.request') }}" class="mt-4 block text-sm font-medium text-atlantia-wine">
        Olvide mi contrasena
    </a>

    <div class="mt-6 rounded-md bg-atlantia-blush p-4 text-center text-sm text-atlantia-ink">
        <p class="font-semibold">Quieres comprar en Atlantia?</p>
        <p class="mt-1 text-atlantia-ink/75">
            Crea tu cuenta gratis para guardar direcciones, finalizar pedidos y seguir tus entregas.
        </p>
        <a href="{{ route('register') }}" class="mt-3 inline-flex font-bold text-atlantia-wine hover:underline">
            Registrarme como cliente
        </a>
    </div>
@endsection
