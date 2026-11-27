@extends('layouts.marketplace')

@section('content')
    <section class="mx-auto max-w-5xl px-4 py-8">
        <x-page-header title="Mi perfil" subtitle="Actualiza tus datos personales y de contacto." />
        <div class="mb-6 rounded-2xl border border-sky-200 bg-sky-50 p-5">
            <p class="text-sm font-black uppercase tracking-normal text-sky-700">Programa de puntos Atlantia</p>
            <div class="mt-3 grid gap-4 sm:grid-cols-3">
                <div>
                    <p class="text-sm text-atlantia-ink/60">Saldo actual</p>
                    <p class="mt-1 text-3xl font-black text-atlantia-wine">
                        {{ number_format((int) (auth()->user()?->puntosCliente?->puntos_actuales ?? 0)) }}
                    </p>
                </div>
                <div>
                    <p class="text-sm text-atlantia-ink/60">Total ganado</p>
                    <p class="mt-1 text-3xl font-black text-atlantia-wine">
                        {{ number_format((int) (auth()->user()?->puntosCliente?->puntos_totales_ganados ?? 0)) }}
                    </p>
                </div>
                <div>
                    <p class="text-sm text-atlantia-ink/60">Canje disponible</p>
                    <p class="mt-1 text-lg font-black text-atlantia-ink">
                        {{ floor((int) (auth()->user()?->puntosCliente?->puntos_actuales ?? 0) / 100) }} bloques de Q5
                    </p>
                </div>
            </div>
        </div>
        <livewire:cliente.profile-form />

        @php
            $campoPerfil = 'mt-1 h-10 w-full rounded-lg border border-atlantia-rose/30 bg-white px-3 text-sm text-atlantia-ink outline-none focus:border-atlantia-wine focus:ring-2 focus:ring-atlantia-blush';
            $bolsaEliminar = $errors->getBag('eliminar');
        @endphp

        <article id="avisos" class="mt-6 rounded-xl border border-atlantia-rose/15 bg-white p-5" data-avisos-ofertas>
            <h2 class="text-base font-black text-atlantia-ink">Avisos de ofertas</h2>
            <p class="text-xs text-atlantia-ink/55">Dos veces por semana te avisamos de ofertas y productos nuevos. Los avisos de tus pedidos llegan siempre.</p>
            <form method="POST" action="{{ route('cliente.perfil.avisos') }}" class="mt-3 flex flex-wrap items-center justify-between gap-3">
                @csrf
                @method('PUT')
                <label class="inline-flex items-center gap-2 text-sm font-bold text-atlantia-ink">
                    <input type="hidden" name="ofertas" value="0">
                    <input type="checkbox" name="ofertas" value="1" @checked($aceptaOfertas) class="h-4 w-4 rounded border-atlantia-rose/40 text-atlantia-wine focus:ring-atlantia-blush">
                    Quiero recibir avisos de ofertas y novedades
                </label>
                <button type="submit" class="h-9 rounded-lg border border-atlantia-rose/30 px-4 text-xs font-bold text-atlantia-wine hover:bg-atlantia-blush">Guardar</button>
            </form>
        </article>

        <details id="eliminar-cuenta" class="mt-6 rounded-xl border border-rose-200 bg-white" @if ($bolsaEliminar->any()) open @endif data-eliminar-cuenta>
            <summary class="cursor-pointer list-none px-5 py-4 text-sm font-black text-rose-700 [&::-webkit-details-marker]:hidden">Eliminar mi cuenta</summary>
            <div class="border-t border-rose-100 px-5 py-4">
                <p class="text-sm text-atlantia-ink/75">Se borran tu nombre, correo, teléfono, direcciones, favoritos, tarjeta guardada y avisos, y ya no podrás entrar con esta cuenta. Tu saldo a favor y tus puntos se pierden.</p>
                <p class="mt-1 text-xs text-atlantia-ink/55">Por ley guardamos tus pedidos y facturas, pero sin tus datos personales.{{ auth()->user()?->membresia_suscripcion_id ? ' Se cancelan los cobros de tu membresía.' : '' }}</p>
                @if ($impedimentoEliminar)
                    <p class="mt-3 rounded-lg bg-amber-50 px-3 py-2 text-sm font-semibold text-amber-900">{{ $impedimentoEliminar }}</p>
                @else
                    <form method="POST" action="{{ route('cliente.perfil.destroy') }}" class="mt-3 space-y-3" data-confirm="¿Eliminar tu cuenta para siempre? Esta acción no se puede deshacer.">
                        @csrf
                        @method('DELETE')
                        <label class="block max-w-sm">
                            <span class="text-xs font-bold text-atlantia-ink/70">Tu contraseña</span>
                            <input type="password" name="contrasena" required autocomplete="current-password" class="{{ $campoPerfil }}">
                        </label>
                        <label class="flex items-start gap-2 text-sm text-atlantia-ink/80">
                            <input type="checkbox" name="confirmo" value="1" required class="mt-0.5 h-4 w-4 rounded border-atlantia-rose/40 text-rose-700">
                            Entiendo que mi cuenta se elimina para siempre y no se puede recuperar.
                        </label>
                        @if ($bolsaEliminar->any())
                            <p class="text-xs font-bold text-red-600">{{ $bolsaEliminar->first() }}</p>
                        @endif
                        <button type="submit" class="h-10 rounded-lg bg-rose-700 px-5 text-sm font-bold text-white hover:bg-rose-800">Eliminar mi cuenta</button>
                    </form>
                @endif
            </div>
        </details>
    </section>
@endsection
