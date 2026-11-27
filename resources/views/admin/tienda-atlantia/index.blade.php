@extends('layouts.app')

@section('content')
    @php
        $campo = 'mt-1 h-10 w-full rounded-lg border border-atlantia-rose/30 bg-white px-3 text-sm text-atlantia-ink outline-none focus:border-atlantia-wine focus:ring-2 focus:ring-atlantia-blush';
        $estadoTexto = match (true) {
            $horario['pausado'] => ['En pausa', 'bg-amber-50 text-amber-800 ring-amber-200'],
            $horario['abierto'] => ['Abierta ahora', 'bg-emerald-50 text-emerald-700 ring-emerald-200'],
            default => ['Cerrada ahora', 'bg-slate-100 text-slate-600 ring-slate-200'],
        };
    @endphp

    <section class="mx-auto max-w-[900px] space-y-4 pb-10">
        <div class="flex flex-wrap items-end justify-between gap-3">
            <div class="max-w-xl">
                <h1 class="text-2xl font-black text-atlantia-ink">Atlantia Store</h1>
                <p class="mt-0.5 text-sm text-atlantia-ink/60">La tienda propia de Atlantia se maneja con el mismo panel que usan las demás tiendas: pedidos, inventario, horario y pausa, reseñas, cupones, reportes y reabasto.</p>
            </div>
            <form method="POST" action="{{ route('admin.tienda-atlantia.entrar') }}">
                @csrf
                <button type="submit" class="inline-flex h-11 items-center gap-2 rounded-lg bg-atlantia-wine px-5 text-sm font-bold text-white hover:bg-atlantia-wine-700" data-entrar-tienda>
                    Entrar al panel de la tienda
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
                </button>
            </form>
        </div>

        <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
            <div class="rounded-xl border border-atlantia-rose/15 bg-white p-4">
                <p class="text-xs font-bold text-atlantia-ink/55">Pedidos por atender</p>
                <p class="mt-1 text-2xl font-black {{ $resumen['pedidos_pendientes'] > 0 ? 'text-atlantia-wine' : 'text-atlantia-ink' }}">{{ $resumen['pedidos_pendientes'] }}</p>
            </div>
            <div class="rounded-xl border border-atlantia-rose/15 bg-white p-4">
                <p class="text-xs font-bold text-atlantia-ink/55">Productos</p>
                <p class="mt-1 text-2xl font-black text-atlantia-ink">{{ $resumen['productos'] }}</p>
            </div>
            <div class="rounded-xl border border-atlantia-rose/15 bg-white p-4">
                <p class="text-xs font-bold text-atlantia-ink/55">Agotados</p>
                <p class="mt-1 text-2xl font-black {{ $resumen['agotados'] > 0 ? 'text-rose-700' : 'text-atlantia-ink' }}">{{ $resumen['agotados'] }}</p>
            </div>
            <div class="rounded-xl border border-atlantia-rose/15 bg-white p-4">
                <p class="text-xs font-bold text-atlantia-ink/55">Reseñas sin responder</p>
                <p class="mt-1 text-2xl font-black text-atlantia-ink">{{ $resumen['resenas_sin_responder'] }}</p>
            </div>
        </div>

        <article class="flex flex-wrap items-center justify-between gap-3 rounded-xl border border-atlantia-rose/15 bg-white p-4">
            <div>
                <p class="text-sm font-black text-atlantia-ink">Horario: {{ \App\Support\HorarioTienda::legible($horario['apertura']) }} a {{ \App\Support\HorarioTienda::legible($horario['cierre']) }}</p>
                <p class="text-xs text-atlantia-ink/55">Se cambia dentro del panel de la tienda, en “Horario y pausa”.</p>
            </div>
            <span class="rounded-full px-2.5 py-1 text-xs font-bold ring-1 {{ $estadoTexto[1] }}">{{ $estadoTexto[0] }}</span>
        </article>

        <article class="rounded-xl border border-atlantia-rose/15 bg-white p-5">
            <h2 class="text-base font-black text-atlantia-ink">Acceso de un empleado</h2>
            @if ($accesoDirecto)
                <p class="mt-1 text-sm text-atlantia-ink/75">
                    Hoy entra directamente con <strong>{{ \App\Services\Vendedores\AccesoVendedorService::correoEnmascarado($operador->email) }}</strong>.
                    Los administradores también pueden entrar con el botón de arriba.
                </p>
            @else
                <p class="mt-1 text-sm text-atlantia-ink/75">Solo los administradores entran, con el botón de arriba. Si un empleado va a manejar la tienda todos los días, dale su propio acceso: le enviamos un enlace para crear su contraseña.</p>
            @endif

            <form method="POST" action="{{ route('admin.tienda-atlantia.acceso') }}" class="mt-3 flex flex-wrap items-end gap-2"
                  data-confirm="{{ $accesoDirecto ? '¿Cambiar el acceso a este correo? El correo anterior ya no podrá entrar.' : '¿Dar acceso al panel de Atlantia Store a este correo?' }}">
                @csrf
                <label class="block min-w-[240px] flex-1">
                    <span class="text-xs font-bold text-atlantia-ink/70">{{ $accesoDirecto ? 'Cambiar a otro correo' : 'Correo del empleado' }}</span>
                    <input type="email" name="email" value="{{ old('email') }}" required maxlength="190" placeholder="empleado@correo.com" class="{{ $campo }}">
                </label>
                <button type="submit" class="h-10 rounded-lg border border-atlantia-rose/30 px-4 text-sm font-bold text-atlantia-wine hover:bg-atlantia-blush">Enviar acceso</button>
            </form>
            @error('email') <p class="mt-1 text-xs font-bold text-red-600">{{ $message }}</p> @enderror
            <p class="mt-2 text-[11px] text-atlantia-ink/50">Usa un correo que no tenga otra cuenta en Atlantia. Al entrar por primera vez configurará Google Authenticator si tu política lo pide.</p>
        </article>

        <article class="rounded-xl border border-atlantia-rose/15 bg-white">
            <h2 class="border-b border-atlantia-rose/10 px-5 py-3 text-base font-black text-atlantia-ink">Quién entró al panel de la tienda</h2>
            <ul class="divide-y divide-atlantia-rose/10">
                @forelse ($entradas as $entrada)
                    <li class="flex items-center justify-between gap-3 px-5 py-2 text-sm">
                        <span class="text-atlantia-ink">{{ $entrada->user?->name ?? 'Administrador' }}</span>
                        <span class="text-xs text-atlantia-ink/55">{{ $entrada->created_at?->format('d/m/Y h:i a') }}</span>
                    </li>
                @empty
                    <li class="px-5 py-4 text-sm text-atlantia-ink/55">Nadie ha entrado todavía.</li>
                @endforelse
            </ul>
        </article>
    </section>
@endsection
