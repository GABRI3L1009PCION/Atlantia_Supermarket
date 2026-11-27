@extends(auth()->user()?->isSuperAdmin() && request()->routeIs('admin.*') ? 'layouts.super-admin' : 'layouts.app')

@section('content')
    <section class="mx-auto max-w-6xl space-y-5 pb-10">
        <div class="flex flex-wrap items-end justify-between gap-3">
            <div>
                <h1 class="text-3xl font-black text-atlantia-ink">Pagos a vendedores</h1>
                <p class="mt-1 text-sm text-atlantia-ink/60">Solicitudes de pago de las tiendas. Transfiere y registra la referencia.</p>
            </div>
            <p class="rounded-xl bg-atlantia-blush px-4 py-2 text-sm font-black text-atlantia-wine">Por pagar: Q {{ number_format($porPagar, 2) }}</p>
        </div>

        <nav class="flex gap-2">
            @foreach (['solicitada' => 'Por pagar', 'pagada' => 'Pagadas', 'rechazada' => 'Rechazadas'] as $clave => $texto)
                <a href="{{ route('admin.liquidaciones.index', ['estado' => $clave]) }}" @class([
                    'rounded-lg px-4 py-2 text-sm font-black',
                    'bg-atlantia-wine text-white' => $estado === $clave,
                    'bg-white text-atlantia-wine ring-1 ring-atlantia-rose/20' => $estado !== $clave,
                ])>{{ $texto }}</a>
            @endforeach
        </nav>

        <div class="overflow-x-auto rounded-2xl border border-atlantia-rose/15 bg-white shadow-sm">
            <table class="w-full min-w-[720px] text-left text-sm">
                <thead class="bg-atlantia-blush/40 text-xs uppercase text-atlantia-ink/60">
                    <tr>
                        <th class="px-4 py-2.5">Solicitud</th>
                        <th class="px-4 py-2.5">Tienda</th>
                        <th class="px-4 py-2.5">Banco</th>
                        <th class="px-4 py-2.5 text-right">Ventas</th>
                        <th class="px-4 py-2.5 text-right">A transferir</th>
                        <th class="px-4 py-2.5"></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($solicitudes as $solicitud)
                        <tr class="border-t border-atlantia-rose/10">
                            <td class="px-4 py-3">
                                <p class="font-bold text-atlantia-ink">{{ $solicitud->numero }}</p>
                                <p class="text-xs text-atlantia-ink/50">{{ $solicitud->solicitada_at?->format('d/m/Y H:i') }}{{ $solicitud->automatica ? ' · automática' : ' · la pidió la tienda' }}</p>
                                @if ($solicitud->estado === 'solicitada' && $solicitud->pagar_antes_de)
                                    <p @class(['mt-0.5 inline-flex rounded-full px-2 py-0.5 text-[11px] font-bold', 'bg-rose-50 text-rose-700' => $solicitud->pagar_antes_de->isPast(), 'bg-amber-50 text-amber-800' => ! $solicitud->pagar_antes_de->isPast()]) data-plazo-pago>
                                        {{ $solicitud->pagar_antes_de->isPast() ? 'Atrasado · ' : 'Transferir antes de ' }}{{ $solicitud->pagar_antes_de->format('d/m h:i a') }}
                                    </p>
                                @endif
                            </td>
                            <td class="px-4 py-3 font-semibold">{{ $solicitud->vendor?->business_name }}</td>
                            <td class="px-4 py-3 text-xs">{{ $solicitud->banco_nombre ?? '—' }}<br><span class="text-atlantia-ink/50">{{ $solicitud->cuenta_titular }}</span></td>
                            <td class="px-4 py-3 text-right">{{ $solicitud->ventas }}</td>
                            <td class="px-4 py-3 text-right font-black text-atlantia-wine">Q {{ number_format((float) $solicitud->monto_neto, 2) }}</td>
                            <td class="px-4 py-3 text-right"><a href="{{ route('admin.liquidaciones.show', $solicitud) }}" class="font-bold text-atlantia-wine">Ver detalle</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-4 py-8 text-center text-atlantia-ink/55">No hay solicitudes en este estado.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        {{ $solicitudes->links() }}
    </section>
@endsection
