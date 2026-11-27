@extends('layouts.app')

@section('content')
    <section class="mx-auto max-w-6xl space-y-5 pb-10">
        <div class="flex flex-wrap items-end justify-between gap-3">
            <div>
                <a href="{{ route('vendedor.pagos.index') }}" class="text-sm font-bold text-atlantia-wine">← Ventas y pagos</a>
                <h1 class="mt-1 text-3xl font-black text-atlantia-ink">Solicitud {{ $liquidacion->numero }}</h1>
                <p class="mt-1 text-sm text-atlantia-ink/60">
                    Solicitada el {{ $liquidacion->solicitada_at?->format('d/m/Y H:i') }}
                    @if ($liquidacion->banco_nombre) · a tu cuenta en {{ $liquidacion->banco_nombre }} @endif
                </p>
            </div>
            <span @class([
                'rounded-full px-3 py-1.5 text-sm font-black',
                'bg-amber-100 text-amber-800' => $liquidacion->estado === 'solicitada',
                'bg-emerald-100 text-emerald-800' => $liquidacion->estado === 'pagada',
                'bg-red-100 text-red-700' => $liquidacion->estado === 'rechazada',
            ])>{{ $liquidacion->etiquetaEstado() }}</span>
        </div>

        @if ($liquidacion->estado === 'pagada')
            <p class="rounded-xl bg-emerald-50 px-4 py-3 text-sm font-bold text-emerald-800">
                Pagado el {{ $liquidacion->pagada_at?->format('d/m/Y') }} · Referencia de transferencia: {{ $liquidacion->referencia_pago }}
            </p>
        @elseif ($liquidacion->estado === 'rechazada')
            <p class="rounded-xl bg-red-50 px-4 py-3 text-sm font-bold text-red-700">Motivo: {{ $liquidacion->notas }}. Estas ventas regresaron a tu saldo disponible.</p>
        @else
            <p class="rounded-xl bg-amber-50 px-4 py-3 text-sm font-bold text-amber-800">Estamos procesando tu pago. Te avisaremos cuando la transferencia esté hecha.</p>
        @endif

        @include('partials.liquidacion-detalle')
    </section>
@endsection
