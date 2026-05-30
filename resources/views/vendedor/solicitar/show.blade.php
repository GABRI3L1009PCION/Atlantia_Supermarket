@extends('layouts.marketplace', ['title' => 'Estado de solicitud | Atlantia Supermarket'])

@section('content')
@php
    $statusLabels = [
        'pending' => 'En revision',
        'approved' => 'Aprobada',
        'rejected' => 'Rechazada',
        'suspended' => 'Suspendida',
    ];
    $currentStatus = $statusLabels[$vendor->status] ?? 'En revision';
@endphp

<section class="bg-atlantia-cream px-4 py-10 sm:px-6 lg:px-8">
    <div class="mx-auto max-w-4xl">
        <div class="rounded-2xl border border-atlantia-rose/25 bg-white p-6 shadow-sm sm:p-8">
            @if (session('status'))
                <div class="mb-6 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-bold text-emerald-800">
                    {{ session('status') }}
                </div>
            @endif

            <p class="text-xs font-black uppercase tracking-[0.18em] text-atlantia-rose">Atlantia Supermarket</p>
            <h1 class="mt-2 text-3xl font-black text-atlantia-ink">Tu solicitud ha sido recibida</h1>
            <p class="mt-3 text-sm leading-6 text-atlantia-ink/70">
                Revisaremos tu documentacion en 3 a 5 dias. Te notificaremos al correo
                <strong>{{ $vendor->user?->email }}</strong> cuando cambie el estado.
            </p>

            <div class="mt-6 grid gap-4 sm:grid-cols-2">
                <div class="rounded-xl border border-atlantia-rose/25 bg-atlantia-cream p-4">
                    <p class="text-xs font-black uppercase tracking-[0.16em] text-atlantia-rose">Numero de seguimiento</p>
                    <p class="mt-2 text-2xl font-black text-atlantia-wine">{{ $vendor->application_code }}</p>
                </div>
                <div class="rounded-xl border border-atlantia-rose/25 bg-atlantia-cream p-4">
                    <p class="text-xs font-black uppercase tracking-[0.16em] text-atlantia-rose">Estado actual</p>
                    <p class="mt-2 text-2xl font-black text-atlantia-wine">{{ $currentStatus }}</p>
                </div>
            </div>

            <div class="mt-8 rounded-2xl border border-atlantia-rose/25 bg-white p-5">
                <h2 class="text-xl font-black text-atlantia-ink">{{ $vendor->business_name }}</h2>
                <p class="mt-1 text-sm text-atlantia-ink/65">{{ $vendor->descripcion }}</p>
                <div class="mt-5 space-y-4">
                    <div class="flex gap-3">
                        <span class="flex h-8 w-8 items-center justify-center rounded-full bg-atlantia-wine text-sm font-black text-white">1</span>
                        <div>
                            <p class="font-black text-atlantia-ink">Solicitud enviada</p>
                            <p class="text-sm text-atlantia-ink/60">Recibimos tu informacion y documentos.</p>
                        </div>
                    </div>
                    <div class="flex gap-3">
                        <span class="flex h-8 w-8 items-center justify-center rounded-full bg-atlantia-blush text-sm font-black text-atlantia-wine">2</span>
                        <div>
                            <p class="font-black text-atlantia-ink">Verificacion de documentos</p>
                            <p class="text-sm text-atlantia-ink/60">El equipo revisa identidad, negocio y cuenta bancaria.</p>
                        </div>
                    </div>
                    <div class="flex gap-3">
                        <span class="flex h-8 w-8 items-center justify-center rounded-full bg-atlantia-blush text-sm font-black text-atlantia-wine">3</span>
                        <div>
                            <p class="font-black text-atlantia-ink">Aprobacion de administrador</p>
                            <p class="text-sm text-atlantia-ink/60">Un administrador confirma si puedes vender en el marketplace.</p>
                        </div>
                    </div>
                    <div class="flex gap-3">
                        <span class="flex h-8 w-8 items-center justify-center rounded-full bg-atlantia-blush text-sm font-black text-atlantia-wine">4</span>
                        <div>
                            <p class="font-black text-atlantia-ink">Envio de credenciales</p>
                            <p class="text-sm text-atlantia-ink/60">Si se aprueba, recibiras acceso al panel de vendedor.</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="mt-6 flex flex-wrap gap-3">
                <a href="{{ route('home') }}" class="rounded-md border border-atlantia-rose/35 bg-white px-5 py-2.5 text-sm font-black text-atlantia-wine transition hover:bg-atlantia-blush">Volver al inicio</a>
                <a href="{{ route('vendedor.solicitar.create') }}" class="rounded-md bg-atlantia-wine px-5 py-2.5 text-sm font-black text-white transition hover:bg-atlantia-wine-700">Enviar otra solicitud</a>
            </div>
        </div>
    </div>
</section>
@endsection
