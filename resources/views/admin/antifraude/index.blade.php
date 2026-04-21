@extends('layouts.app')

@section('content')
    <section class="mx-auto max-w-full py-2">
        <div class="space-y-6 rounded-2xl border border-atlantia-rose/20 bg-white p-6 shadow-sm">
            <x-page-header title="Antifraude" subtitle="Prioriza alertas de riesgo, revisa casos y registra resoluciones operativas." />

            <div class="grid gap-4 md:grid-cols-4">
                <div class="rounded-xl border border-amber-200 bg-white p-4">
                    <p class="text-sm text-atlantia-ink/55">Pendientes</p>
                    <p class="mt-2 text-2xl font-bold text-amber-600">{{ $dashboard['pendientes'] }}</p>
                </div>
                <div class="rounded-xl border border-emerald-200 bg-white p-4">
                    <p class="text-sm text-atlantia-ink/55">Resueltas</p>
                    <p class="mt-2 text-2xl font-bold text-emerald-600">{{ $dashboard['resueltas'] }}</p>
                </div>
                <div class="rounded-xl border border-rose-200 bg-white p-4">
                    <p class="text-sm text-atlantia-ink/55">Alto riesgo</p>
                    <p class="mt-2 text-2xl font-bold text-rose-600">{{ $dashboard['alto_riesgo'] }}</p>
                </div>
                <div class="rounded-xl border border-atlantia-rose/20 bg-atlantia-cream p-4">
                    <p class="text-sm text-atlantia-ink/55">Tipos detectados</p>
                    <p class="mt-2 text-xl font-bold text-atlantia-wine">{{ $dashboard['tipos']->count() }}</p>
                </div>
            </div>

            <div class="rounded-xl border border-atlantia-rose/20 bg-atlantia-cream p-4">
                <form method="GET" class="grid gap-3 xl:grid-cols-[1fr_0.8fr_0.7fr_0.7fr_auto]">
                    <input type="text" name="tipo" value="{{ request('tipo') }}" placeholder="Tipo de alerta" class="rounded-md border border-atlantia-rose/35 px-3 py-2">
                    <select name="revisada" class="rounded-md border border-atlantia-rose/35 px-3 py-2">
                        <option value="">Revisadas y pendientes</option>
                        <option value="1" @selected(request('revisada') === '1')>Revisadas</option>
                        <option value="0" @selected(request('revisada') === '0')>Pendientes</option>
                    </select>
                    <select name="resuelta" class="rounded-md border border-atlantia-rose/35 px-3 py-2">
                        <option value="">Resueltas y abiertas</option>
                        <option value="1" @selected(request('resuelta') === '1')>Resueltas</option>
                        <option value="0" @selected(request('resuelta') === '0')>Abiertas</option>
                    </select>
                    <input type="number" name="riesgo_min" step="0.01" min="0" max="1" value="{{ request('riesgo_min') }}" placeholder="Riesgo minimo" class="rounded-md border border-atlantia-rose/35 px-3 py-2">
                    <x-ui.button type="submit" variant="secondary">Filtrar</x-ui.button>
                </form>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead>
                        <tr class="border-b border-atlantia-rose/20 text-left text-atlantia-ink/55">
                            <th class="pb-3">Alerta</th>
                            <th class="pb-3">Pedido</th>
                            <th class="pb-3">Cliente</th>
                            <th class="pb-3">Riesgo</th>
                            <th class="pb-3">Estado</th>
                            <th class="pb-3 text-right">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-atlantia-rose/15">
                        @forelse ($alerts as $alert)
                            <tr>
                                <td class="py-3">
                                    <p class="font-semibold text-atlantia-ink">{{ $alert->tipo }}</p>
                                    <p class="text-xs text-atlantia-ink/55">{{ $alert->created_at?->format('d/m/Y H:i') }}</p>
                                </td>
                                <td class="py-3 text-atlantia-ink/70">{{ $alert->pedido?->numero_pedido ?? 'Sin pedido' }}</td>
                                <td class="py-3 text-atlantia-ink/70">{{ $alert->user?->name ?? 'Sin usuario' }}</td>
                                <td class="py-3">
                                    <span class="rounded-md bg-rose-100 px-3 py-1 text-xs font-bold text-rose-700">{{ number_format((float) $alert->score_riesgo, 2) }}</span>
                                </td>
                                <td class="py-3">
                                    <span class="rounded-md bg-atlantia-blush px-3 py-1 text-xs font-bold text-atlantia-wine">
                                        {{ $alert->resuelta ? 'Resuelta' : ($alert->revisada ? 'Revisada' : 'Pendiente') }}
                                    </span>
                                </td>
                                <td class="py-3 text-right">
                                    <a href="{{ route('admin.antifraude.show', $alert->uuid) }}" class="font-semibold text-atlantia-wine hover:underline">Revisar</a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="py-6 text-center text-atlantia-ink/60">No hay alertas que coincidan con los filtros.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div>{{ $alerts->links() }}</div>
        </div>
    </section>
@endsection
