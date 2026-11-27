@extends('layouts.app')

@section('content')
    <section class="mx-auto max-w-7xl space-y-5 pb-10">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <h1 class="text-3xl font-black text-atlantia-ink">Planes y publicidad</h1>
                <p class="mt-1 text-sm text-atlantia-ink/60">Lo que pagan los comercios y anunciantes. Los banners externos se cargan en “Banners” con el nombre del anunciante.</p>
            </div>
            <div class="flex gap-3">
                <p class="rounded-xl bg-emerald-50 px-4 py-2 text-sm font-bold text-emerald-800">Cobrado este mes: Q {{ number_format($ingresosMes, 2) }}</p>
                <p class="rounded-xl bg-amber-50 px-4 py-2 text-sm font-bold text-amber-800">Por revisar: {{ $porRevisar }}</p>
            </div>
        </div>

        <form method="GET" class="flex gap-2">
            <select name="estado" class="rounded-lg border border-atlantia-rose/30 px-3 py-2 text-sm">
                <option value="">Todos los estados</option>
                @foreach (['pendiente_pago' => 'Pendiente de pago', 'en_revision' => 'En revisión', 'activo' => 'Activo', 'vencido' => 'Terminado', 'rechazado' => 'Rechazado'] as $valor => $etiqueta)
                    <option value="{{ $valor }}" @selected($estado === $valor)>{{ $etiqueta }}</option>
                @endforeach
            </select>
            <button type="submit" class="rounded-lg border border-atlantia-rose/30 px-4 py-2 text-sm font-black text-atlantia-wine">Filtrar</button>
        </form>

        <div class="overflow-x-auto rounded-2xl border border-atlantia-rose/20 bg-white shadow-sm">
            <table class="w-full min-w-[900px] text-left text-sm">
                <thead class="bg-atlantia-blush/50 text-xs uppercase text-atlantia-ink/60">
                    <tr><th class="px-4 py-3">Servicio</th><th class="px-4 py-3">Cliente</th><th class="px-4 py-3">Fechas</th><th class="px-4 py-3">Monto</th><th class="px-4 py-3">Estado</th><th class="px-4 py-3 text-right">Acciones</th></tr>
                </thead>
                <tbody>
                    @forelse ($servicios as $servicio)
                        <tr class="border-t border-atlantia-rose/10" data-servicio>
                            <td class="px-4 py-3">
                                <p class="font-bold text-atlantia-ink">{{ $servicio->etiquetaTipo() }}</p>
                                <p class="text-xs text-atlantia-ink/55">
                                    @if ($servicio->tipo === 'plan') Plan {{ data_get($servicio->detalle, 'plan') }}
                                    @elseif ($servicio->tipo === 'banner') Posición {{ data_get($servicio->detalle, 'posicion') }} · {{ data_get($servicio->detalle, 'periodo') }}
                                    @else {{ $servicio->referencia?->nombre }} @endif
                                </p>
                                @if ($servicio->tipo === 'banner' && $servicio->referencia?->getFirstMediaUrl('hero_desktop'))
                                    <a href="{{ $servicio->referencia->getFirstMediaUrl('hero_desktop') }}" target="_blank" rel="noopener" class="text-xs font-bold text-atlantia-wine underline">Ver arte</a>
                                @endif
                            </td>
                            <td class="px-4 py-3">{{ $servicio->vendor?->business_name ?? $servicio->anunciante }}</td>
                            <td class="px-4 py-3 text-xs">{{ $servicio->inicia_at?->format('d/m/Y') }} – {{ $servicio->termina_at?->format('d/m/Y') }}</td>
                            <td class="px-4 py-3">Q {{ number_format((float) $servicio->monto, 2) }}<span class="block text-xs text-atlantia-ink/50">{{ $servicio->metodo_pago }}</span></td>
                            <td class="px-4 py-3 text-xs font-bold">{{ $servicio->etiquetaEstado() }}</td>
                            <td class="px-4 py-3">
                                <div class="flex justify-end gap-2">
                                    @if ($servicio->estado === 'pendiente_pago')
                                        <form method="POST" action="{{ route('admin.servicios.pagado', $servicio) }}">@csrf<button class="rounded-md bg-emerald-700 px-3 py-1.5 text-xs font-black text-white">Registrar pago</button></form>
                                    @endif
                                    @if ($servicio->estado === 'en_revision')
                                        <form method="POST" action="{{ route('admin.servicios.aprobar', $servicio) }}">@csrf<button class="rounded-md bg-atlantia-wine px-3 py-1.5 text-xs font-black text-white">Aprobar banner</button></form>
                                    @endif
                                    @if (in_array($servicio->estado, ['pendiente_pago', 'en_revision', 'activo'], true))
                                        <form method="POST" action="{{ route('admin.servicios.rechazar', $servicio) }}">@csrf<button class="rounded-md border border-red-200 px-3 py-1.5 text-xs font-black text-red-700">Rechazar</button></form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-4 py-6 text-center text-atlantia-ink/60">Sin servicios.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        {{ $servicios->links() }}
    </section>
@endsection
