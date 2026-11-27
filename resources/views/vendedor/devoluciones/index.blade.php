@extends('layouts.app')

@section('content')
    @php
        $motivos = ['producto_defectuoso' => 'Producto dañado o defectuoso', 'no_llego' => 'No le llegó', 'incorrecto' => 'Recibió otro producto', 'otro' => 'Otro motivo'];
        $estados = [
            'solicitada' => ['Atlantia está revisando', 'bg-amber-50 text-amber-800 ring-amber-200'],
            'aprobada' => ['Reembolso aprobado', 'bg-rose-50 text-rose-700 ring-rose-200'],
            'rechazada' => ['Devolución rechazada', 'bg-emerald-50 text-emerald-700 ring-emerald-200'],
            'completada' => ['Completada', 'bg-slate-100 text-slate-600 ring-slate-200'],
        ];
        $pestanas = ['' => 'Todas', 'solicitada' => 'En revisión', 'aprobada' => 'Aprobadas', 'rechazada' => 'Rechazadas'];
        $nombreCorto = function (?string $nombre): string {
            $partes = preg_split('/\s+/', trim((string) $nombre)) ?: [];

            return ($partes[0] ?? 'Cliente').(isset($partes[1]) ? ' '.mb_substr($partes[1], 0, 1).'.' : '');
        };
    @endphp

    <section class="mx-auto max-w-[1000px] space-y-4 pb-10">
        <div>
            <h1 class="text-2xl font-black text-atlantia-ink">Devoluciones</h1>
            <p class="mt-0.5 text-sm text-atlantia-ink/60">Cuando un cliente pide devolver un pedido tuyo, aparece aquí. Cuéntanos tu versión: Atlantia la toma en cuenta antes de decidir el reembolso.</p>
        </div>

        <nav class="flex gap-1.5 overflow-x-auto" aria-label="Filtrar">
            @foreach ($pestanas as $clave => $texto)
                <a href="{{ route('vendedor.devoluciones.index', array_filter(['estado' => $clave])) }}" @class([
                    'inline-flex h-9 shrink-0 items-center gap-1.5 rounded-full px-4 text-sm font-bold',
                    'bg-atlantia-wine text-white' => ($filtro ?? '') === $clave,
                    'border border-atlantia-rose/20 bg-white text-atlantia-ink/70 hover:text-atlantia-wine' => ($filtro ?? '') !== $clave,
                ])>
                    {{ $texto }}
                    <span class="text-xs opacity-70">{{ $clave === '' ? $conteos->sum() : ($conteos[$clave] ?? 0) }}</span>
                </a>
            @endforeach
        </nav>

        @if ($devoluciones->isEmpty())
            <div class="rounded-xl border border-atlantia-rose/15 bg-white px-6 py-14 text-center">
                <p class="text-base font-black text-atlantia-ink">{{ $filtro ? 'No hay devoluciones aquí' : 'No tienes devoluciones' }}</p>
                <p class="mt-1 text-sm text-atlantia-ink/60">Si un cliente pide devolver un pedido tuyo, te avisaremos en la campana.</p>
            </div>
        @else
            <ul class="space-y-3">
                @foreach ($devoluciones as $devolucion)
                    @php
                        $pedidoTienda = $devolucion->pedido_tienda;
                        $suVenta = $pedidoTienda ? (float) $pedidoTienda->total - (float) $pedidoTienda->envio - (float) $pedidoTienda->propina : 0;
                        $foto = $devolucion->foto_evidencia ? \Illuminate\Support\Facades\Storage::url($devolucion->foto_evidencia) : null;
                    @endphp
                    <li class="overflow-hidden rounded-xl border border-atlantia-rose/15 bg-white" data-devolucion="{{ $devolucion->uuid }}">
                        <div class="flex flex-wrap items-start justify-between gap-3 border-b border-atlantia-rose/10 px-4 py-3">
                            <div>
                                <p class="font-black text-atlantia-ink">
                                    @if ($pedidoTienda)
                                        <a href="{{ route('vendedor.pedidos.show', $pedidoTienda) }}" class="hover:text-atlantia-wine">Pedido #{{ $pedidoTienda->numero_pedido }}</a>
                                    @else
                                        Pedido #{{ $devolucion->pedido?->numero_pedido }}
                                    @endif
                                </p>
                                <p class="text-xs text-atlantia-ink/55">{{ $nombreCorto($devolucion->user?->name) }} · pedida el {{ $devolucion->created_at?->format('d/m/Y h:i a') }}</p>
                            </div>
                            <span class="rounded-full px-2.5 py-1 text-xs font-bold ring-1 {{ $estados[$devolucion->estado][1] ?? '' }}">{{ $estados[$devolucion->estado][0] ?? $devolucion->estado }}</span>
                        </div>

                        <div class="grid gap-4 px-4 py-3 sm:grid-cols-[minmax(0,1fr)_14rem]">
                            <div class="min-w-0">
                                <p class="text-sm font-bold text-atlantia-ink">{{ $motivos[$devolucion->motivo] ?? $devolucion->motivo }}</p>
                                <p class="mt-1 whitespace-pre-line text-sm text-atlantia-ink/70">{{ $devolucion->descripcion }}</p>
                                @if ($pedidoTienda && $pedidoTienda->items->isNotEmpty())
                                    <p class="mt-2 text-xs text-atlantia-ink/55">Tus productos en este pedido: {{ $pedidoTienda->items->map(fn ($i) => $i->producto_nombre_snapshot.' ×'.$i->cantidad)->join(', ') }} · tu venta Q{{ number_format($suVenta, 2) }}</p>
                                @endif
                            </div>
                            @if ($foto)
                                <a href="{{ $foto }}" target="_blank" rel="noopener" class="block overflow-hidden rounded-lg border border-atlantia-rose/15">
                                    <img src="{{ $foto }}" alt="Foto que envió el cliente" class="h-32 w-full object-cover" loading="lazy">
                                    <span class="block bg-[#fbf8f7] px-2 py-1 text-center text-[11px] font-bold text-atlantia-wine">Ver foto del cliente</span>
                                </a>
                            @endif
                        </div>

                        <div class="border-t border-atlantia-rose/10 bg-[#fbf8f7] px-4 py-3">
                            @if ($devolucion->vendedor_respondio_at)
                                <p class="text-sm"><span class="font-black text-atlantia-ink">Tu respuesta:</span>
                                    <span @class(['font-bold', 'text-emerald-700' => $devolucion->vendedor_de_acuerdo, 'text-rose-700' => ! $devolucion->vendedor_de_acuerdo])>{{ $devolucion->vendedor_de_acuerdo ? 'Estás de acuerdo' : 'No estás de acuerdo' }}</span>
                                    <span class="text-xs text-atlantia-ink/45">· {{ $devolucion->vendedor_respondio_at->format('d/m/Y h:i a') }}</span>
                                </p>
                                @if ($devolucion->respuesta_vendedor)
                                    <p class="mt-0.5 text-sm text-atlantia-ink/70">{{ $devolucion->respuesta_vendedor }}</p>
                                @endif
                            @endif

                            @if ($devolucion->estado === 'solicitada')
                                <details class="mt-1" @if (! $devolucion->vendedor_respondio_at || ($errors->any() && old('devolucion') === $devolucion->uuid)) open @endif>
                                    <summary class="cursor-pointer list-none text-sm font-bold text-atlantia-wine [&::-webkit-details-marker]:hidden">{{ $devolucion->vendedor_respondio_at ? 'Cambiar mi respuesta' : '¿Qué opinas de esta devolución?' }}</summary>
                                    <form method="POST" action="{{ route('vendedor.devoluciones.responder', $devolucion) }}" class="mt-2 space-y-2">
                                        @csrf
                                        <input type="hidden" name="devolucion" value="{{ $devolucion->uuid }}">
                                        <div class="flex flex-wrap gap-2">
                                            <label class="cursor-pointer rounded-lg border border-atlantia-rose/30 bg-white px-3 py-2 text-sm font-bold text-atlantia-ink has-[:checked]:border-emerald-600 has-[:checked]:bg-emerald-600 has-[:checked]:text-white">
                                                <input type="radio" name="de_acuerdo" value="1" class="sr-only" @checked(old('devolucion') === $devolucion->uuid ? old('de_acuerdo') === '1' : $devolucion->vendedor_de_acuerdo === true) required>
                                                Estoy de acuerdo
                                            </label>
                                            <label class="cursor-pointer rounded-lg border border-atlantia-rose/30 bg-white px-3 py-2 text-sm font-bold text-atlantia-ink has-[:checked]:border-rose-700 has-[:checked]:bg-rose-700 has-[:checked]:text-white">
                                                <input type="radio" name="de_acuerdo" value="0" class="sr-only" @checked(old('devolucion') === $devolucion->uuid ? old('de_acuerdo') === '0' : $devolucion->vendedor_de_acuerdo === false)>
                                                No estoy de acuerdo
                                            </label>
                                        </div>
                                        <textarea name="comentario" rows="2" maxlength="800" placeholder="Explica lo que pasó (obligatorio si no estás de acuerdo). Ej. El producto salió sellado y en buen estado." class="w-full resize-none rounded-lg border border-atlantia-rose/30 bg-white px-3 py-2 text-sm outline-none focus:border-atlantia-wine focus:ring-2 focus:ring-atlantia-blush">{{ old('devolucion') === $devolucion->uuid ? old('comentario') : $devolucion->respuesta_vendedor }}</textarea>
                                        @if ($errors->any() && old('devolucion') === $devolucion->uuid)
                                            <p class="text-xs font-bold text-red-600">{{ $errors->first() }}</p>
                                        @endif
                                        <div class="flex justify-end"><button type="submit" class="h-9 rounded-lg bg-atlantia-wine px-4 text-xs font-bold text-white hover:bg-atlantia-wine-700">Enviar a Atlantia</button></div>
                                    </form>
                                </details>
                            @else
                                <p class="mt-1 text-sm text-atlantia-ink/70">
                                    @if ($devolucion->estado === 'aprobada')
                                        Atlantia devolvió Q{{ number_format((float) $devolucion->monto_reembolso, 2) }} al cliente el {{ $devolucion->resuelta_at?->format('d/m/Y') }}. Esta venta ya no se te paga.
                                    @else
                                        Atlantia rechazó la devolución el {{ $devolucion->resuelta_at?->format('d/m/Y') }}. Tu venta sigue igual.
                                    @endif
                                </p>
                                @if ($devolucion->notas_admin)
                                    <p class="mt-1 text-xs text-atlantia-ink/55">Nota de Atlantia: {{ $devolucion->notas_admin }}</p>
                                @endif
                            @endif
                        </div>
                    </li>
                @endforeach
            </ul>
            @if ($devoluciones->hasPages())
                <div>{{ $devoluciones->links() }}</div>
            @endif
        @endif
    </section>
@endsection
