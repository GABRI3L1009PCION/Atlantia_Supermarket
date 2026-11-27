@extends('layouts.marketplace', ['title' => 'Estado de solicitud | Atlantia Store'])

@section('content')
@php
    $estado = $vendor->status;
    $estados = [
        'pending' => ['En revisión', 'bg-amber-50 text-amber-800 ring-amber-200'],
        'approved' => ['Aprobada', 'bg-emerald-50 text-emerald-800 ring-emerald-200'],
        'rejected' => ['No aprobada', 'bg-red-50 text-red-700 ring-red-200'],
        'suspended' => ['Suspendida', 'bg-slate-100 text-slate-700 ring-slate-200'],
    ];
    [$estadoTexto, $estadoClase] = $estados[$estado] ?? $estados['pending'];
    $aprobada = $estado === 'approved';

    /* Paso en curso: 2 en revisión, 4 crear acceso, 5 todo completado. */
    $alcanzado = match (true) {
        $aprobada && $yaTieneContrasena => 5,
        $aprobada => 4,
        default => 2,
    };
    $pasos = [
        1 => ['Solicitud enviada', 'Recibimos tu información y documentos.'],
        2 => ['Revisión de documentos', 'El equipo de Atlantia revisa tu identidad, tu negocio y tu cuenta bancaria (3 a 5 días).'],
        3 => ['Aprobación', $aprobada ? 'Tu tienda fue aprobada. Te enviamos un correo para crear tu contraseña.' : 'Te avisamos por correo en cuanto se tome una decisión.'],
        4 => ['Acceso a tu panel', $yaTieneContrasena ? 'Ya creaste tu contraseña. Entra con tu correo, tu contraseña y Google Authenticator.' : 'Creas tu contraseña, activas Google Authenticator y empiezas a subir productos.'],
    ];
@endphp

<section class="bg-atlantia-cream px-4 py-6 sm:px-6 lg:px-8">
    <div class="mx-auto max-w-3xl">
        <div class="rounded-2xl border border-atlantia-rose/25 bg-white p-5 shadow-sm sm:p-7">
            @if (session('status'))
                <div class="mb-4 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-bold text-emerald-800">{{ session('status') }}</div>
            @endif
            @if (session('error'))
                <div class="mb-4 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-bold text-red-700">{{ session('error') }}</div>
            @endif

            <div class="flex flex-wrap items-start justify-between gap-3">
                <div>
                    <h1 class="text-2xl font-black text-atlantia-ink">{{ $vendor->business_name }}</h1>
                    <p class="mt-0.5 text-sm text-atlantia-ink/60">Solicitud <strong class="text-atlantia-wine">{{ $vendor->application_code }}</strong> · avisos a <strong>{{ $correoVisible }}</strong></p>
                </div>
                <span class="rounded-full px-3 py-1.5 text-sm font-black ring-1 {{ $estadoClase }}">{{ $estadoTexto }}</span>
            </div>

            {{-- Mensaje principal según el estado --}}
            @if ($aprobada && ! $yaTieneContrasena)
                <div class="mt-5 rounded-2xl border border-emerald-200 bg-emerald-50 p-4">
                    <p class="font-black text-emerald-900">¡Tu tienda fue aprobada! 🎉</p>
                    <p class="mt-1 text-sm text-emerald-900/80">Te enviamos un correo a <strong>{{ $correoVisible }}</strong> con el botón <strong>«Crear mi contraseña»</strong>. Revisa también la carpeta de spam o promociones.</p>
                    <form method="POST" action="{{ route('vendedor.solicitud.reenviar', $vendor->application_code) }}" class="mt-3" data-protect-submit>
                        @csrf
                        <button class="rounded-lg border border-emerald-300 bg-white px-4 py-2 text-sm font-black text-emerald-800 hover:bg-emerald-100">¿No te llegó o venció? Enviarme otro enlace</button>
                    </form>
                </div>
            @elseif ($aprobada)
                <div class="mt-5 flex flex-wrap items-center justify-between gap-3 rounded-2xl border border-emerald-200 bg-emerald-50 p-4">
                    <p class="text-sm text-emerald-900"><strong>Tu tienda está activa.</strong> Entra con tu correo, tu contraseña y el código de Google Authenticator.</p>
                    <a href="{{ route('login') }}" class="rounded-lg bg-atlantia-wine px-4 py-2 text-sm font-black text-white hover:bg-atlantia-wine-700">Iniciar sesión</a>
                </div>
            @elseif ($estado === 'rejected')
                <div class="mt-5 rounded-2xl border border-red-200 bg-red-50 p-4">
                    <p class="font-black text-red-800">Por ahora no pudimos aprobar tu solicitud</p>
                    <p class="mt-1 text-sm text-red-900/80"><strong>Motivo:</strong> {{ $vendor->rejection_reason ?: 'No se especificó un motivo.' }}</p>
                    <a href="{{ route('vendedor.solicitar.create') }}" class="mt-3 inline-flex rounded-lg bg-atlantia-wine px-4 py-2 text-sm font-black text-white hover:bg-atlantia-wine-700">Corregir y enviar una nueva solicitud</a>
                </div>
            @else
                <p class="mt-5 rounded-2xl border border-atlantia-rose/20 bg-atlantia-blush/25 p-4 text-sm text-atlantia-ink/75">
                    Estamos revisando tu documentación. Te avisaremos por correo a <strong>{{ $correoVisible }}</strong> en 3 a 5 días. Guarda tu código <strong>{{ $vendor->application_code }}</strong> para consultar el avance.
                </p>
            @endif

            {{-- Línea de tiempo --}}
            @if ($estado !== 'rejected')
                <ol class="mt-6 space-y-4">
                    @foreach ($pasos as $numero => [$titulo, $detalle])
                        @php
                            $hecho = $numero < $alcanzado;
                            $actual = $numero === $alcanzado;
                        @endphp
                        <li class="flex gap-3">
                            <span @class([
                                'flex h-8 w-8 shrink-0 items-center justify-center rounded-full text-sm font-black',
                                'bg-emerald-600 text-white' => $hecho,
                                'bg-atlantia-wine text-white ring-4 ring-atlantia-wine/15' => $actual,
                                'bg-atlantia-blush text-atlantia-wine' => ! $hecho && ! $actual,
                            ])>{{ $hecho ? '✓' : $numero }}</span>
                            <div>
                                <p @class(['font-black', 'text-atlantia-ink' => $hecho || $actual, 'text-atlantia-ink/50' => ! $hecho && ! $actual])>{{ $titulo }}</p>
                                <p class="text-sm text-atlantia-ink/60">{{ $detalle }}</p>
                            </div>
                        </li>
                    @endforeach
                </ol>
            @endif

            <div class="mt-6 flex flex-wrap gap-3 border-t border-atlantia-rose/15 pt-5">
                <a href="{{ route('home') }}" class="rounded-lg border border-atlantia-rose/35 bg-white px-5 py-2.5 text-sm font-black text-atlantia-wine transition hover:bg-atlantia-blush">Volver al inicio</a>
            </div>
        </div>
    </div>
</section>
@endsection
