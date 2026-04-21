@extends('layouts.app')

@section('content')
    <section class="mx-auto max-w-full py-2">
        <div class="space-y-6 rounded-2xl border border-atlantia-rose/20 bg-white p-6 shadow-sm">
            <x-page-header title="Resenas" subtitle="Modera opiniones, revisa flags ML y protege la reputacion del marketplace." />

            <div class="grid gap-4 md:grid-cols-4">
                <div class="rounded-xl border border-atlantia-rose/20 bg-atlantia-cream p-4">
                    <p class="text-sm text-atlantia-ink/55">Total</p>
                    <p class="mt-2 text-2xl font-bold text-atlantia-wine">{{ $dashboard['total'] }}</p>
                </div>
                <div class="rounded-xl border border-amber-200 bg-white p-4">
                    <p class="text-sm text-atlantia-ink/55">Pendientes</p>
                    <p class="mt-2 text-2xl font-bold text-amber-600">{{ $dashboard['pendientes'] }}</p>
                </div>
                <div class="rounded-xl border border-emerald-200 bg-white p-4">
                    <p class="text-sm text-atlantia-ink/55">Aprobadas</p>
                    <p class="mt-2 text-2xl font-bold text-emerald-600">{{ $dashboard['aprobadas'] }}</p>
                </div>
                <div class="rounded-xl border border-rose-200 bg-white p-4">
                    <p class="text-sm text-atlantia-ink/55">Flagged ML</p>
                    <p class="mt-2 text-2xl font-bold text-rose-600">{{ $dashboard['flagged_ml'] }}</p>
                </div>
            </div>

            <div class="rounded-xl border border-atlantia-rose/20 bg-atlantia-cream p-4">
                <form method="GET" class="grid gap-3 xl:grid-cols-[1.2fr_0.7fr_0.7fr_auto]">
                    <input type="search" name="q" value="{{ request('q') }}" placeholder="Buscar por titulo, contenido, producto o cliente" class="rounded-md border border-atlantia-rose/35 px-3 py-2">
                    <select name="aprobada" class="rounded-md border border-atlantia-rose/35 px-3 py-2">
                        <option value="">Todas</option>
                        <option value="1" @selected(request('aprobada') === '1')>Aprobadas</option>
                        <option value="0" @selected(request('aprobada') === '0')>Pendientes</option>
                    </select>
                    <select name="flagged_ml" class="rounded-md border border-atlantia-rose/35 px-3 py-2">
                        <option value="">Con y sin flags</option>
                        <option value="1" @selected(request('flagged_ml') === '1')>Solo flagged ML</option>
                        <option value="0" @selected(request('flagged_ml') === '0')>Sin flags ML</option>
                    </select>
                    <x-ui.button type="submit" variant="secondary">Filtrar</x-ui.button>
                </form>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead>
                        <tr class="border-b border-atlantia-rose/20 text-left text-atlantia-ink/55">
                            <th class="pb-3">Resena</th>
                            <th class="pb-3">Producto</th>
                            <th class="pb-3">Cliente</th>
                            <th class="pb-3">Calificacion</th>
                            <th class="pb-3">Estado</th>
                            <th class="pb-3 text-right">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-atlantia-rose/15">
                        @forelse ($resenas as $resena)
                            <tr>
                                <td class="py-3">
                                    <p class="font-semibold text-atlantia-ink">{{ $resena->titulo }}</p>
                                    <p class="line-clamp-2 text-xs text-atlantia-ink/55">{{ $resena->contenido }}</p>
                                </td>
                                <td class="py-3 text-atlantia-ink/70">{{ $resena->producto?->nombre }}</td>
                                <td class="py-3 text-atlantia-ink/70">{{ $resena->cliente?->name }}</td>
                                <td class="py-3 font-semibold text-atlantia-wine">{{ $resena->calificacion }}/5</td>
                                <td class="py-3">
                                    <div class="flex flex-wrap gap-2">
                                        <span class="rounded-md bg-atlantia-blush px-3 py-1 text-xs font-bold text-atlantia-wine">
                                            {{ $resena->aprobada ? 'Aprobada' : 'Pendiente' }}
                                        </span>
                                        @if ($resena->flagged_ml)
                                            <span class="rounded-md bg-rose-100 px-3 py-1 text-xs font-bold text-rose-700">ML</span>
                                        @endif
                                    </div>
                                </td>
                                <td class="py-3 text-right">
                                    <a href="{{ route('admin.resenas.show', $resena->uuid) }}" class="font-semibold text-atlantia-wine hover:underline">
                                        Revisar
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="py-6 text-center text-atlantia-ink/60">No hay resenas para estos filtros.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div>{{ $resenas->links() }}</div>
        </div>
    </section>
@endsection
