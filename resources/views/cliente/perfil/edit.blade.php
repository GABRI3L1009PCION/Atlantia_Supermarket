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
    </section>
@endsection
