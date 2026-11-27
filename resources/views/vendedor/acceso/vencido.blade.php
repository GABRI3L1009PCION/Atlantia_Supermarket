@extends('layouts.marketplace', ['title' => 'Enlace vencido | Atlantia Store'])

@section('content')
<x-auth.panel titulo="Este enlace ya no sirve" subtitulo="Venció o ya se usó para crear la contraseña." etiqueta="Enlace no válido">
    <div class="space-y-4 text-sm text-atlantia-ink/70">
        <p>Por seguridad, el enlace para crear tu contraseña funciona una sola vez y durante {{ \App\Services\Vendedores\AccesoVendedorService::HORAS_VIGENCIA }} horas.</p>
        <ul class="list-disc space-y-1 pl-5">
            <li>Si <strong>ya creaste tu contraseña</strong>, simplemente inicia sesión.</li>
            <li>Si <strong>no alcanzaste a crearla</strong>, pide un enlace nuevo desde la página de seguimiento de tu solicitud (con tu código <strong>VND-…</strong>).</li>
        </ul>
        <a href="{{ route('login') }}" class="block w-full rounded-xl bg-atlantia-wine px-4 py-3 text-center text-sm font-black text-white transition hover:bg-atlantia-wine-700">Iniciar sesión</a>
    </div>
</x-auth.panel>
@endsection
