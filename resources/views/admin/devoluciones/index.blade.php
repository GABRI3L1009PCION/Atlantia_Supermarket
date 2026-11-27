@extends(auth()->user()?->isSuperAdmin() && request()->routeIs('admin.*') ? 'layouts.super-admin' : 'layouts.app')

@section('content')
    @php
        $motivos = [
            'producto_defectuoso' => 'Producto defectuoso',
            'no_llego' => 'No llegó',
            'incorrecto' => 'Producto incorrecto',
            'otro' => 'Otro motivo',
        ];
        $tarjeta = 'rounded-2xl border border-atlantia-rose/15 bg-white shadow-[0_8px_20px_rgba(59,6,12,0.04)]';
        $campo = 'h-9 w-full rounded-lg border border-atlantia-rose/30 bg-white px-3 text-sm focus:border-atlantia-wine focus:outline-none focus:ring-2 focus:ring-atlantia-wine/15';
        $etiqueta = 'mb-1 block text-[11px] font-black uppercase tracking-wide text-atlantia-ink/50';
        $texto = fn (?string $valor): string => $valor ? ucfirst(str_replace('_', ' ', $valor)) : '—';
    @endphp

    <section class="mx-auto max-w-7xl space-y-4 pb-6 text-atlantia-ink">
        {{-- Encabezado --}}
        <header>
            <h1 class="text-3xl font-black tracking-tight">Devoluciones</h1>
            <p class="mt-0.5 text-sm text-atlantia-ink/60">Solicitudes de los clientes que tuvieron un problema con su pedido. Al aprobar una, se reembolsa el dinero, se cancela el pedido y el producto vuelve al inventario.</p>
        </header>

        {{-- Resumen --}}
        <div class="grid grid-cols-2 gap-3 xl:grid-cols-4">
            <a href="{{ route('admin.devoluciones.index') }}" @class([
                'rounded-2xl px-5 py-3.5 transition',
                'bg-[linear-gradient(135deg,#3a0004_0%,#5e0008_100%)] text-white shadow-lg' => $resumen['pendientes'] > 0,
                $tarjeta.' hover:border-atlantia-wine/40' => $resumen['pendientes'] === 0,
            ])>
                <p @class(['text-[11px] font-black uppercase tracking-wide', 'text-white/70' => $resumen['pendientes'] > 0, 'text-atlantia-ink/50' => $resumen['pendientes'] === 0])>Por resolver</p>
                <p @class(['mt-0.5 text-3xl font-black', 'text-atlantia-ink/30' => $resumen['pendientes'] === 0])>{{ $resumen['pendientes'] }}</p>
                <p @class(['text-xs', 'text-white/70' => $resumen['pendientes'] > 0, 'text-atlantia-ink/55' => $resumen['pendientes'] === 0])>{{ $resumen['pendientes'] > 0 ? 'clientes esperando respuesta' : 'todo al día' }}</p>
            </a>
            <article class="{{ $tarjeta }} px-5 py-3.5">
                <p class="text-[11px] font-black uppercase tracking-wide text-atlantia-ink/50">Reembolsado este mes</p>
                <p class="mt-0.5 text-3xl font-black {{ $resumen['reembolsado_mes'] > 0 ? 'text-red-700' : 'text-atlantia-ink/30' }}">Q {{ number_format($resumen['reembolsado_mes'], 2) }}</p>
                <p class="text-xs text-atlantia-ink/55">dinero devuelto a clientes</p>
            </article>
            <article class="{{ $tarjeta }} px-5 py-3.5">
                <p class="text-[11px] font-black uppercase tracking-wide text-atlantia-ink/50">Aprobadas este mes</p>
                <p class="mt-0.5 text-3xl font-black {{ $resumen['aprobadas_mes'] > 0 ? 'text-emerald-600' : 'text-atlantia-ink/30' }}">{{ $resumen['aprobadas_mes'] }}</p>
                <p class="text-xs text-atlantia-ink/55">con reembolso</p>
            </article>
            <article class="{{ $tarjeta }} px-5 py-3.5">
                <p class="text-[11px] font-black uppercase tracking-wide text-atlantia-ink/50">Rechazadas este mes</p>
                <p class="mt-0.5 text-3xl font-black {{ $resumen['rechazadas_mes'] > 0 ? 'text-atlantia-ink' : 'text-atlantia-ink/30' }}">{{ $resumen['rechazadas_mes'] }}</p>
                <p class="text-xs text-atlantia-ink/55">sin reembolso</p>
            </article>
        </div>

        <article class="{{ $tarjeta }} overflow-hidden">
            {{-- Pestañas --}}
            <div class="flex flex-wrap items-center gap-2 border-b border-atlantia-rose/10 px-4 py-3">
                @foreach (['pendientes' => ['Por resolver', $resumen['pendientes'], []], 'resueltas' => ['Historial', $resumen['resueltas'], ['vista' => 'resueltas']]] as $clave => [$nombre, $cantidad, $parametros])
                    <a href="{{ route('admin.devoluciones.index', $parametros) }}" @class([
                        'inline-flex items-center gap-1.5 rounded-full px-3 py-1 text-xs font-black transition',
                        'bg-atlantia-wine text-white' => $vista === $clave,
                        'bg-atlantia-blush/50 text-atlantia-wine hover:bg-atlantia-blush' => $vista !== $clave,
                    ])>
                        {{ $nombre }}
                        <span @class(['rounded-full px-1.5 text-[10px]', 'bg-white/20' => $vista === $clave, 'bg-white' => $vista !== $clave])>{{ $cantidad }}</span>
                    </a>
                @endforeach
            </div>

            @if ($vista === 'pendientes')
                @forelse ($devoluciones as $devolucion)
                    @php
                        $pedido = $devolucion->pedido;
                        $total = (float) ($pedido?->total ?? 0);
                    @endphp
                    <div class="grid gap-4 border-b border-atlantia-rose/10 p-4 last:border-b-0 lg:grid-cols-[1fr_22rem]">
                        {{-- Lo que pidió el cliente --}}
                        <div class="min-w-0">
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="rounded-full bg-amber-50 px-2.5 py-1 text-xs font-black text-amber-800 ring-1 ring-amber-200">{{ $motivos[$devolucion->motivo] ?? $texto($devolucion->motivo) }}</span>
                                <span class="text-xs text-atlantia-ink/50">solicitada {{ $devolucion->created_at?->diffForHumans() }}</span>
                            </div>
                            <h2 class="mt-1.5 text-lg font-black">
                                Pedido {{ $pedido?->numero_pedido ?? '—' }}
                                <span class="text-sm font-bold text-atlantia-ink/55">· {{ $devolucion->user?->name ?? 'Cliente' }}</span>
                            </h2>
                            <p class="mt-1.5 rounded-lg bg-atlantia-blush/30 px-3 py-2 text-sm leading-relaxed text-atlantia-ink/80">“{{ $devolucion->descripcion }}”</p>
                            @if ($devolucion->vendedor_respondio_at)
                                <p class="mt-1.5 rounded-lg border-l-4 px-3 py-2 text-sm {{ $devolucion->vendedor_de_acuerdo ? 'border-emerald-500 bg-emerald-50 text-emerald-900' : 'border-rose-500 bg-rose-50 text-rose-900' }}" data-respuesta-tienda>
                                    <strong>La tienda {{ $devolucion->vendedor_de_acuerdo ? 'está de acuerdo' : 'no está de acuerdo' }}.</strong>
                                    @if ($devolucion->respuesta_vendedor) {{ $devolucion->respuesta_vendedor }} @endif
                                </p>
                            @else
                                <p class="mt-1.5 text-xs text-atlantia-ink/45">La tienda aún no da su versión.</p>
                            @endif

                            <div class="mt-2 flex flex-wrap items-start gap-x-6 gap-y-2 text-xs">
                                <p><span class="text-atlantia-ink/50">Total del pedido</span><br><strong class="text-sm">Q {{ number_format($total, 2) }}</strong></p>
                                <p><span class="text-atlantia-ink/50">Pago</span><br><strong class="text-sm">{{ $texto($pedido?->estadoPagoValor()) }}</strong></p>
                                <p><span class="text-atlantia-ink/50">Pedido</span><br><strong class="text-sm">{{ $texto($pedido?->estadoValor()) }}</strong></p>
                                @if ($pedido?->items?->isNotEmpty())
                                    <p class="min-w-0 flex-1"><span class="text-atlantia-ink/50">Productos</span><br>
                                        <span class="text-sm text-atlantia-ink/75">{{ $pedido->items->take(3)->map(fn ($item) => $item->cantidad.' × '.($item->producto_nombre_snapshot ?? $item->producto?->nombre))->join(', ') }}{{ $pedido->items->count() > 3 ? ' y '.($pedido->items->count() - 3).' más' : '' }}</span>
                                    </p>
                                @endif
                                @if ($devolucion->foto_evidencia)
                                    <a href="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($devolucion->foto_evidencia) }}" target="_blank" rel="noopener" class="font-black text-atlantia-wine hover:underline">Ver foto del cliente ↗</a>
                                @endif
                            </div>
                        </div>

                        {{-- Decisión --}}
                        <form
                            method="POST"
                            action="{{ route('admin.devoluciones.update', $devolucion) }}"
                            class="rounded-xl border border-atlantia-rose/20 bg-atlantia-blush/20 p-3"
                            x-data="{ decision: 'aprobada' }"
                            data-protect-submit
                            data-confirm="¿Confirmas la decisión? Si apruebas, se reembolsa el dinero al cliente, el pedido se cancela y el producto vuelve al inventario. No se puede deshacer."
                        >
                            @csrf
                            @method('PATCH')
                            <div class="grid grid-cols-2 gap-1 rounded-lg bg-white p-1 ring-1 ring-atlantia-rose/20">
                                <label class="cursor-pointer rounded-md px-2 py-1.5 text-center text-sm font-black transition" :class="decision === 'aprobada' ? 'bg-emerald-600 text-white' : 'text-atlantia-ink/60 hover:bg-atlantia-blush/40'">
                                    <input type="radio" name="decision" value="aprobada" x-model="decision" class="sr-only" checked> Aprobar
                                </label>
                                <label class="cursor-pointer rounded-md px-2 py-1.5 text-center text-sm font-black transition" :class="decision === 'rechazada' ? 'bg-atlantia-wine text-white' : 'text-atlantia-ink/60 hover:bg-atlantia-blush/40'">
                                    <input type="radio" name="decision" value="rechazada" x-model="decision" class="sr-only"> Rechazar
                                </label>
                            </div>
                            <label class="mt-2 block" :class="{ 'hidden': decision !== 'aprobada' }">
                                <span class="{{ $etiqueta }}">Monto a reembolsar (máx. Q {{ number_format($total, 2) }})</span>
                                <input name="monto_reembolso" type="number" step="0.01" min="0.01" max="{{ $total }}" value="{{ number_format($total, 2, '.', '') }}" :disabled="decision !== 'aprobada'" class="{{ $campo }}">
                            </label>
                            <label class="mt-2 block">
                                <span class="{{ $etiqueta }}">Nota (queda en el historial)</span>
                                <input name="notas_admin" maxlength="1200" placeholder="Ej. Se verificó la foto, el producto llegó dañado" class="{{ $campo }}">
                            </label>
                            <button class="mt-2 h-9 w-full rounded-lg text-sm font-black text-white" :class="decision === 'aprobada' ? 'bg-emerald-600 hover:bg-emerald-700' : 'bg-atlantia-wine hover:bg-atlantia-wine-700'" x-text="decision === 'aprobada' ? 'Aprobar y reembolsar' : 'Rechazar solicitud'">Aprobar y reembolsar</button>
                        </form>
                    </div>
                @empty
                    <div class="px-6 py-10 text-center">
                        <span class="mx-auto grid h-10 w-10 place-items-center rounded-full bg-emerald-600 text-lg font-black text-white">✓</span>
                        <p class="mt-2 font-black">No hay devoluciones por resolver</p>
                        <p class="mt-1 text-sm text-atlantia-ink/55">Cuando un cliente reporte un problema con su pedido aparecerá aquí para que decidas.</p>
                    </div>
                @endforelse
            @else
                @if ($devoluciones->isEmpty())
                    <div class="px-6 py-10 text-center">
                        <p class="font-black">Aún no has resuelto devoluciones.</p>
                        <p class="mt-1 text-sm text-atlantia-ink/55">Aquí quedará el registro de cada decisión.</p>
                    </div>
                @else
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead class="bg-atlantia-blush/40 text-left text-[11px] font-black uppercase tracking-wide text-atlantia-ink/55">
                                <tr>
                                    <th class="px-4 py-2.5">Pedido</th>
                                    <th class="px-3 py-2.5">Motivo</th>
                                    <th class="px-3 py-2.5">Resuelta</th>
                                    <th class="px-3 py-2.5 text-right">Reembolso</th>
                                    <th class="px-3 py-2.5">Decisión</th>
                                    <th class="px-4 py-2.5">Nota</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-atlantia-rose/10">
                                @foreach ($devoluciones as $devolucion)
                                    <tr class="hover:bg-atlantia-blush/20">
                                        <td class="px-4 py-2.5">
                                            <p class="font-black">{{ $devolucion->pedido?->numero_pedido ?? '—' }}</p>
                                            <p class="text-xs text-atlantia-ink/50">{{ $devolucion->user?->name }}</p>
                                        </td>
                                        <td class="px-3 py-2.5 text-atlantia-ink/70">{{ $motivos[$devolucion->motivo] ?? $texto($devolucion->motivo) }}</td>
                                        <td class="whitespace-nowrap px-3 py-2.5 text-atlantia-ink/70">{{ $devolucion->resuelta_at?->format('d/m/Y') }}</td>
                                        <td class="whitespace-nowrap px-3 py-2.5 text-right font-black {{ $devolucion->estado === 'aprobada' ? 'text-red-700' : 'text-atlantia-ink/30' }}">
                                            {{ $devolucion->estado === 'aprobada' ? 'Q '.number_format((float) $devolucion->monto_reembolso, 2) : '—' }}
                                        </td>
                                        <td class="px-3 py-2.5">
                                            <span @class([
                                                'rounded-full px-2.5 py-1 text-xs font-black ring-1',
                                                'bg-emerald-50 text-emerald-800 ring-emerald-200' => $devolucion->estado === 'aprobada',
                                                'bg-slate-100 text-slate-600 ring-slate-200' => $devolucion->estado !== 'aprobada',
                                            ])>{{ $devolucion->estado === 'aprobada' ? 'Aprobada' : 'Rechazada' }}</span>
                                        </td>
                                        <td class="max-w-xs truncate px-4 py-2.5 text-xs text-atlantia-ink/60" title="{{ $devolucion->notas_admin }}">{{ $devolucion->notas_admin ?: '—' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            @endif

            @if ($devoluciones->hasPages())
                <div class="border-t border-atlantia-rose/10 px-4 py-3">{{ $devoluciones->appends(['vista' => $vista === 'resueltas' ? 'resueltas' : null])->links() }}</div>
            @endif
        </article>
    </section>
@endsection
