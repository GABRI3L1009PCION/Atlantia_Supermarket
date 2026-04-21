@extends('layouts.app')

@section('content')
    <section class="mx-auto max-w-full py-2">
        <div class="space-y-6 rounded-2xl border border-atlantia-rose/20 bg-white p-6 shadow-sm">
            <x-page-header title="DTE FEL" subtitle="Supervisa certificacion, rechazo y anulacion fiscal de documentos emitidos." />

            <div class="grid gap-4 md:grid-cols-4">
                <div class="rounded-xl border border-emerald-200 bg-white p-4">
                    <p class="text-sm text-atlantia-ink/55">Certificados</p>
                    <p class="mt-2 text-2xl font-bold text-emerald-600">{{ $dashboard['certificados'] }}</p>
                </div>
                <div class="rounded-xl border border-rose-200 bg-white p-4">
                    <p class="text-sm text-atlantia-ink/55">Rechazados</p>
                    <p class="mt-2 text-2xl font-bold text-rose-600">{{ $dashboard['rechazados'] }}</p>
                </div>
                <div class="rounded-xl border border-slate-200 bg-white p-4">
                    <p class="text-sm text-atlantia-ink/55">Anulados</p>
                    <p class="mt-2 text-2xl font-bold text-slate-700">{{ $dashboard['anulados'] }}</p>
                </div>
                <div class="rounded-xl border border-atlantia-rose/20 bg-atlantia-cream p-4">
                    <p class="text-sm text-atlantia-ink/55">Monto total</p>
                    <p class="mt-2 text-2xl font-bold text-atlantia-wine">Q{{ number_format($dashboard['monto_total'], 2) }}</p>
                </div>
            </div>

            <div class="rounded-xl border border-atlantia-rose/20 bg-atlantia-cream p-4">
                <form method="GET" class="grid gap-3 xl:grid-cols-[1fr_0.8fr_0.8fr_0.8fr_0.8fr_auto]">
                    <select name="vendor_id" class="rounded-md border border-atlantia-rose/35 px-3 py-2">
                        <option value="">Todos los vendedores</option>
                        @foreach ($vendors as $vendor)
                            <option value="{{ $vendor->id }}" @selected((string) request('vendor_id') === (string) $vendor->id)>{{ $vendor->business_name }}</option>
                        @endforeach
                    </select>
                    <select name="estado" class="rounded-md border border-atlantia-rose/35 px-3 py-2">
                        <option value="">Todos los estados</option>
                        @foreach (['borrador', 'certificado', 'rechazado', 'anulado'] as $estado)
                            <option value="{{ $estado }}" @selected(request('estado') === $estado)>{{ ucfirst($estado) }}</option>
                        @endforeach
                    </select>
                    <select name="tipo_dte" class="rounded-md border border-atlantia-rose/35 px-3 py-2">
                        <option value="">Todos los tipos</option>
                        @foreach (['FACT', 'FCAM', 'FPEQ', 'NCRE', 'NDEB'] as $tipo)
                            <option value="{{ $tipo }}" @selected(request('tipo_dte') === $tipo)>{{ $tipo }}</option>
                        @endforeach
                    </select>
                    <input type="date" name="fecha_desde" value="{{ request('fecha_desde') }}" class="rounded-md border border-atlantia-rose/35 px-3 py-2">
                    <input type="date" name="fecha_hasta" value="{{ request('fecha_hasta') }}" class="rounded-md border border-atlantia-rose/35 px-3 py-2">
                    <x-ui.button type="submit" variant="secondary">Filtrar</x-ui.button>
                </form>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead>
                        <tr class="border-b border-atlantia-rose/20 text-left text-atlantia-ink/55">
                            <th class="pb-3">DTE</th>
                            <th class="pb-3">Vendedor</th>
                            <th class="pb-3">Tipo</th>
                            <th class="pb-3">Monto</th>
                            <th class="pb-3">Estado</th>
                            <th class="pb-3 text-right">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-atlantia-rose/15">
                        @forelse ($dtes as $dte)
                            <tr>
                                <td class="py-3">
                                    <p class="font-semibold text-atlantia-ink">{{ $dte->numero_dte }}</p>
                                    <p class="text-xs text-atlantia-ink/55">{{ $dte->uuid_sat ?? 'Sin UUID SAT' }}</p>
                                </td>
                                <td class="py-3 text-atlantia-ink/70">{{ $dte->vendor?->business_name }}</td>
                                <td class="py-3 text-atlantia-ink/70">{{ $dte->tipo_dte }}</td>
                                <td class="py-3 font-semibold text-atlantia-wine">Q{{ number_format((float) $dte->monto_total, 2) }}</td>
                                <td class="py-3">
                                    <span class="rounded-md bg-atlantia-blush px-3 py-1 text-xs font-bold text-atlantia-wine">{{ $dte->estado }}</span>
                                </td>
                                <td class="py-3 text-right">
                                    <a href="{{ route('admin.dte.show', $dte->uuid) }}" class="font-semibold text-atlantia-wine hover:underline">Gestionar</a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="py-6 text-center text-atlantia-ink/60">No hay DTE registrados para estos filtros.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div>{{ $dtes->links() }}</div>
        </div>
    </section>
@endsection
