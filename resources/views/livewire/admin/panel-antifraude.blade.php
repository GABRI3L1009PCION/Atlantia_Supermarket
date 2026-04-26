<section wire:poll.60s="refreshPanel" class="rounded-lg border border-atlantia-rose/20 bg-white p-5 shadow-sm">
    <div class="flex flex-col gap-3 border-b border-atlantia-rose/15 pb-4 lg:flex-row lg:items-end lg:justify-between">
        <div>
            <p class="text-sm font-bold uppercase tracking-normal text-atlantia-wine">Antifraude</p>
            <h2 class="mt-1 text-2xl font-black text-atlantia-ink">Alertas operativas</h2>
            <p class="mt-1 text-sm text-atlantia-ink/65">Revision de riesgo, pedidos sospechosos y resolucion manual.</p>
        </div>
        <p class="text-sm font-semibold text-atlantia-ink/55">Actualizado {{ $lastRefreshed }}</p>
    </div>

    @if ($notice)
        <div class="mt-4 rounded-lg bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-700">
            {{ $notice }}
        </div>
    @endif

    <div class="mt-5 grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
        <article class="rounded-lg border border-amber-200 bg-amber-50 p-4">
            <p class="text-sm text-amber-800">Pendientes</p>
            <p class="mt-2 text-3xl font-black text-amber-700">{{ number_format((int) ($dashboard['pendientes'] ?? 0)) }}</p>
        </article>
        <article class="rounded-lg border border-emerald-200 bg-emerald-50 p-4">
            <p class="text-sm text-emerald-800">Resueltas</p>
            <p class="mt-2 text-3xl font-black text-emerald-700">{{ number_format((int) ($dashboard['resueltas'] ?? 0)) }}</p>
        </article>
        <article class="rounded-lg border border-rose-200 bg-rose-50 p-4">
            <p class="text-sm text-rose-800">Alto riesgo</p>
            <p class="mt-2 text-3xl font-black text-rose-700">{{ number_format((int) ($dashboard['alto_riesgo'] ?? 0)) }}</p>
        </article>
        <article class="rounded-lg border border-atlantia-rose/20 bg-atlantia-blush p-4">
            <p class="text-sm text-atlantia-wine">Tipos detectados</p>
            <p class="mt-2 text-3xl font-black text-atlantia-ink">{{ collect($dashboard['tipos'] ?? [])->count() }}</p>
        </article>
    </div>

    <div class="mt-5 grid gap-3 rounded-lg border border-atlantia-rose/15 bg-atlantia-blush/40 p-4 lg:grid-cols-[1fr_180px_180px]">
        <input
            type="search"
            wire:model.live.debounce.400ms="tipo"
            placeholder="Filtrar por tipo de alerta"
            class="rounded-md border border-atlantia-rose/25 bg-white px-4 py-3 text-sm focus:border-atlantia-wine focus:ring-atlantia-rose"
        >
        <select wire:model.live="estado" class="rounded-md border border-atlantia-rose/25 bg-white px-4 py-3 text-sm focus:border-atlantia-wine focus:ring-atlantia-rose">
            <option value="abiertas">Abiertas</option>
            <option value="pendientes">Pendientes</option>
            <option value="resueltas">Resueltas</option>
            <option value="todas">Todas</option>
        </select>
        <select wire:model.live="riesgo" class="rounded-md border border-atlantia-rose/25 bg-white px-4 py-3 text-sm focus:border-atlantia-wine focus:ring-atlantia-rose">
            <option value="todos">Todo riesgo</option>
            <option value="alto">Alto riesgo</option>
            <option value="medio">Riesgo medio</option>
        </select>
    </div>

    <div class="mt-5 overflow-hidden rounded-lg border border-atlantia-rose/15">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-atlantia-rose/15 text-sm">
                <thead class="bg-atlantia-blush/50 text-left text-xs uppercase tracking-normal text-atlantia-ink/60">
                    <tr>
                        <th class="px-4 py-3">Alerta</th>
                        <th class="px-4 py-3">Pedido</th>
                        <th class="px-4 py-3">Cliente</th>
                        <th class="px-4 py-3">Riesgo</th>
                        <th class="px-4 py-3">Estado</th>
                        <th class="px-4 py-3 text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-atlantia-rose/15">
                    @forelse ($alerts as $alert)
                        <tr class="bg-white">
                            <td class="px-4 py-3">
                                <p class="font-bold text-atlantia-ink">{{ str_replace('_', ' ', $alert->tipo) }}</p>
                                <p class="mt-1 text-xs text-atlantia-ink/55">{{ optional($alert->created_at)->format('d/m/Y H:i') }}</p>
                            </td>
                            <td class="px-4 py-3 text-atlantia-ink/75">
                                #{{ $alert->pedido?->numero_pedido ?? 'Sin pedido' }}
                            </td>
                            <td class="px-4 py-3 text-atlantia-ink/75">
                                {{ $alert->user?->name ?? $alert->pedido?->cliente?->name ?? 'Cliente no disponible' }}
                            </td>
                            <td class="px-4 py-3">
                                <span class="rounded-full px-3 py-1 text-xs font-black {{ (float) $alert->score_riesgo >= 0.8 ? 'bg-rose-50 text-rose-700' : 'bg-amber-50 text-amber-700' }}">
                                    {{ number_format((float) $alert->score_riesgo * 100, 0) }}%
                                </span>
                            </td>
                            <td class="px-4 py-3">
                                <span class="rounded-full bg-atlantia-blush px-3 py-1 text-xs font-bold text-atlantia-wine">
                                    {{ $alert->resuelta ? 'Resuelta' : ($alert->revisada ? 'Revisada' : 'Pendiente') }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-right">
                                @if (! $alert->resuelta)
                                    <button
                                        type="button"
                                        wire:click="resolver('{{ $alert->uuid }}')"
                                        wire:loading.attr="disabled"
                                        class="rounded-md bg-atlantia-wine px-3 py-2 text-xs font-bold text-white hover:bg-atlantia-wine-700"
                                    >
                                        Resolver
                                    </button>
                                @else
                                    <span class="text-xs font-semibold text-atlantia-ink/50">Cerrada</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-8 text-center text-sm text-atlantia-ink/65">
                                No hay alertas que coincidan con los filtros actuales.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</section>
