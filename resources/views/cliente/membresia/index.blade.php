@extends('layouts.marketplace')

@section('content')
    <section class="mx-auto w-full max-w-5xl px-4 py-8 sm:px-6 lg:px-8">
        @php
            $mostrarMembresia = $membresiaHabilitada || $activa;
        @endphp
        @if ($mostrarMembresia)
            <x-page-header title="Membresía y pagos" subtitle="Envío gratis todo el mes y pago con un toque." />
        @else
            <x-page-header title="Tarjeta guardada" subtitle="Paga tus pedidos con un toque." />
        @endif

        <div class="mt-6 grid gap-5 {{ $mostrarMembresia ? 'lg:grid-cols-2' : 'max-w-xl' }}">
            @if ($mostrarMembresia)
            <article class="rounded-2xl border border-atlantia-rose/20 bg-white p-6 shadow-sm" data-membresia>
                <p class="text-xs font-black uppercase tracking-wide text-atlantia-wine">Membresía Envío Gratis</p>
                <h2 class="mt-1 text-2xl font-black text-atlantia-ink">Q {{ number_format($precio, 2) }} al mes</h2>
                <ul class="mt-3 space-y-1.5 text-sm text-atlantia-ink/75">
                    <li>✓ Envío gratis en compras desde Q {{ number_format($minimo, 2) }}</li>
                    <li>✓ Se renueva sola cada mes; cancelas cuando quieras</li>
                </ul>

                @if ($activa)
                    <p class="mt-4 rounded-lg bg-emerald-50 px-3 py-2 text-sm font-bold text-emerald-800">
                        Activa hasta el {{ $user->membresia_hasta->format('d/m/Y') }}
                    </p>
                    @if ($user->membresia_suscripcion_id)
                        <form method="POST" action="{{ route('cliente.membresia.cancelar') }}" class="mt-3">
                            @csrf
                            <button type="submit" class="text-sm font-bold text-atlantia-wine underline">Cancelar renovación</button>
                        </form>
                    @endif
                @elseif ($pagosEnLinea && $membresiaHabilitada)
                    <form method="POST" action="{{ route('cliente.membresia.suscribir') }}" class="mt-5">
                        @csrf
                        <button type="submit" class="w-full rounded-xl bg-atlantia-wine px-5 py-3 text-sm font-black text-white hover:bg-atlantia-wine-700">Unirme por Q {{ number_format($precio, 2) }}/mes</button>
                    </form>
                    <p class="mt-2 text-xs text-atlantia-ink/55">Pagas en la página segura de Recurrente.</p>
                @else
                    <p class="mt-4 text-sm text-atlantia-ink/60">Muy pronto podrás unirte desde aquí.</p>
                @endif
            </article>
            @endif

            <article class="rounded-2xl border border-atlantia-rose/20 bg-white p-6 shadow-sm" data-tarjeta-guardada>
                <p class="text-xs font-black uppercase tracking-wide text-atlantia-wine">Tarjeta guardada</p>
                @if ($user->recurrente_metodo_pago_id)
                    <h2 class="mt-1 text-xl font-black text-atlantia-ink">{{ $user->tarjeta_descripcion ?: 'Tarjeta guardada' }}</h2>
                    <p class="mt-1 text-sm text-atlantia-ink/65">Al pagar con tarjeta puedes usarla con un toque.</p>
                    <form method="POST" action="{{ route('cliente.tarjeta.eliminar') }}" class="mt-4">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="text-sm font-bold text-atlantia-wine underline">Quitar tarjeta</button>
                    </form>
                @elseif ($pagosEnLinea)
                    <h2 class="mt-1 text-xl font-black text-atlantia-ink">Paga con un toque</h2>
                    <p class="mt-1 text-sm text-atlantia-ink/65">Guarda tu tarjeta en Recurrente. Atlantia nunca ve ni guarda los números de tu tarjeta.</p>
                    <form method="POST" action="{{ route('cliente.tarjeta.guardar') }}" class="mt-4">
                        @csrf
                        <button type="submit" class="w-full rounded-xl border border-atlantia-wine px-5 py-3 text-sm font-black text-atlantia-wine hover:bg-atlantia-blush">Guardar mi tarjeta</button>
                    </form>
                @else
                    <p class="mt-2 text-sm text-atlantia-ink/60">El pago en línea con tarjeta todavía no está activo.</p>
                @endif
            </article>
        </div>
    </section>
@endsection
