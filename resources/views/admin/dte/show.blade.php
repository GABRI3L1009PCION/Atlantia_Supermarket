@extends('layouts.app')

@section('content')
    <section class="mx-auto max-w-full py-2">
        <div class="grid gap-6 xl:grid-cols-[1.05fr_0.95fr]">
            <div class="space-y-6">
                <div class="rounded-2xl border border-atlantia-rose/20 bg-white p-6 shadow-sm">
                    <x-page-header :title="'DTE ' . $dte->numero_dte" subtitle="Detalle fiscal, payload del certificador y soporte operativo." />

                    <div class="mt-6 grid gap-4 md:grid-cols-4">
                        <div class="rounded-xl border border-atlantia-rose/20 bg-atlantia-cream p-4">
                            <p class="text-sm text-atlantia-ink/55">Vendedor</p>
                            <p class="mt-2 font-semibold text-atlantia-ink">{{ $dte->vendor?->business_name }}</p>
                        </div>
                        <div class="rounded-xl border border-atlantia-rose/20 bg-atlantia-cream p-4">
                            <p class="text-sm text-atlantia-ink/55">Tipo</p>
                            <p class="mt-2 font-semibold text-atlantia-ink">{{ $dte->tipo_dte }}</p>
                        </div>
                        <div class="rounded-xl border border-atlantia-rose/20 bg-atlantia-cream p-4">
                            <p class="text-sm text-atlantia-ink/55">Estado</p>
                            <p class="mt-2 font-semibold text-atlantia-ink">{{ $dte->estado }}</p>
                        </div>
                        <div class="rounded-xl border border-atlantia-rose/20 bg-atlantia-cream p-4">
                            <p class="text-sm text-atlantia-ink/55">Total</p>
                            <p class="mt-2 text-2xl font-bold text-atlantia-wine">Q{{ number_format((float) $dte->monto_total, 2) }}</p>
                        </div>
                    </div>
                </div>

                <div class="rounded-2xl border border-atlantia-rose/20 bg-white p-6 shadow-sm">
                    <h2 class="text-lg font-bold text-atlantia-wine">Items facturados</h2>
                    <div class="mt-4 overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <thead>
                                <tr class="border-b border-atlantia-rose/20 text-left text-atlantia-ink/55">
                                    <th class="pb-3">Descripcion</th>
                                    <th class="pb-3">Cantidad</th>
                                    <th class="pb-3">Precio</th>
                                    <th class="pb-3">IVA</th>
                                    <th class="pb-3">Total</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-atlantia-rose/15">
                                @foreach ($dte->items as $item)
                                    <tr>
                                        <td class="py-3 font-semibold text-atlantia-ink">{{ $item->descripcion }}</td>
                                        <td class="py-3 text-atlantia-ink/70">{{ $item->cantidad }}</td>
                                        <td class="py-3 text-atlantia-ink/70">Q{{ number_format((float) $item->precio_unitario, 2) }}</td>
                                        <td class="py-3 text-atlantia-ink/70">Q{{ number_format((float) $item->monto_iva, 2) }}</td>
                                        <td class="py-3 font-semibold text-atlantia-wine">Q{{ number_format((float) $item->monto_total, 2) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="rounded-2xl border border-atlantia-rose/20 bg-white p-6 shadow-sm">
                    <h2 class="text-lg font-bold text-atlantia-wine">Respuesta del certificador</h2>
                    <pre class="mt-4 overflow-x-auto rounded-xl bg-slate-950 p-4 text-xs text-slate-100">{{ json_encode($dte->certificador_respuesta, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}</pre>
                </div>
            </div>

            <aside class="space-y-6">
                <div class="rounded-2xl border border-atlantia-rose/20 bg-white p-6 shadow-sm">
                    <h2 class="text-lg font-bold text-atlantia-wine">Acciones</h2>

                    @if (in_array($dte->estado, ['borrador', 'rechazado'], true))
                        <form method="POST" action="{{ route('admin.dte.retry', $dte->uuid) }}" class="mt-4">
                            @csrf
                            <x-ui.button type="submit" class="w-full">Reintentar certificacion</x-ui.button>
                        </form>
                    @endif

                    @if ($dte->estado === 'certificado' && $dte->anulacion === null)
                        <form method="POST" action="{{ route('admin.dte.anular', $dte->uuid) }}" class="mt-4 space-y-3">
                            @csrf
                            <label class="text-sm font-semibold text-atlantia-ink">Motivo de anulacion</label>
                            <textarea name="motivo" rows="3" class="w-full rounded-md border border-atlantia-rose/35 px-3 py-2" required></textarea>
                            <button type="submit" class="w-full rounded-md bg-red-600 px-4 py-2 text-sm font-semibold text-white">Anular DTE</button>
                        </form>
                    @endif
                </div>

                <div class="rounded-2xl border border-atlantia-rose/20 bg-white p-6 shadow-sm">
                    <h2 class="text-lg font-bold text-atlantia-wine">Meta fiscal</h2>
                    <dl class="mt-4 space-y-3 text-sm">
                        <div>
                            <dt class="text-atlantia-ink/55">UUID SAT</dt>
                            <dd class="font-semibold text-atlantia-ink">{{ $dte->uuid_sat ?? 'Pendiente' }}</dd>
                        </div>
                        <div>
                            <dt class="text-atlantia-ink/55">Serie / Numero</dt>
                            <dd class="font-semibold text-atlantia-ink">{{ ($dte->serie ?? '-') . ' / ' . ($dte->numero ?? '-') }}</dd>
                        </div>
                        <div>
                            <dt class="text-atlantia-ink/55">Fecha certificacion</dt>
                            <dd class="font-semibold text-atlantia-ink">{{ $dte->fecha_certificacion?->format('d/m/Y H:i') ?? 'Pendiente' }}</dd>
                        </div>
                        <div>
                            <dt class="text-atlantia-ink/55">PDF</dt>
                            <dd class="font-semibold text-atlantia-ink">{{ $dte->pdf_path ?? 'No disponible' }}</dd>
                        </div>
                    </dl>
                </div>

                @if ($dte->anulacion !== null)
                    <div class="rounded-2xl border border-rose-200 bg-white p-6 shadow-sm">
                        <h2 class="text-lg font-bold text-rose-700">Anulacion registrada</h2>
                        <p class="mt-3 text-sm text-atlantia-ink/70">{{ $dte->anulacion->motivo }}</p>
                        <p class="mt-2 text-sm font-semibold text-atlantia-ink">{{ $dte->anulacion->estado }}</p>
                        <p class="mt-1 text-xs text-atlantia-ink/55">{{ $dte->anulacion->uuid_anulacion_sat ?? 'Sin UUID SAT de anulacion' }}</p>
                    </div>
                @endif
            </aside>
        </div>
    </section>
@endsection
