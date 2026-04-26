<section wire:poll.60s="refreshPanel" class="rounded-lg border border-atlantia-rose/20 bg-white p-5 shadow-sm">
    <div class="flex flex-col gap-3 border-b border-atlantia-rose/15 pb-4 lg:flex-row lg:items-end lg:justify-between">
        <div>
            <p class="text-sm font-bold uppercase tracking-normal text-atlantia-wine">Reabasto inteligente</p>
            <h2 class="mt-1 text-2xl font-black text-atlantia-ink">Sugerencias de inventario</h2>
            <p class="mt-1 text-sm text-atlantia-ink/65">Prioriza productos con riesgo de quiebre de stock.</p>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            <select wire:model.live="urgencia" class="rounded-md border border-atlantia-rose/25 px-3 py-2 text-sm focus:border-atlantia-wine focus:ring-atlantia-rose">
                <option value="todas">Todas</option>
                <option value="critica">Critica</option>
                <option value="alta">Alta</option>
                <option value="media">Media</option>
                <option value="baja">Baja</option>
            </select>
            <button
                type="button"
                wire:click="generarSugerencias"
                wire:loading.attr="disabled"
                class="rounded-md bg-atlantia-wine px-4 py-2 text-sm font-bold text-white hover:bg-atlantia-wine-700"
            >
                <span wire:loading.remove wire:target="generarSugerencias">Actualizar</span>
                <span wire:loading wire:target="generarSugerencias">Analizando...</span>
            </button>
        </div>
    </div>

    <p class="mt-3 text-xs font-semibold text-atlantia-ink/55">Actualizado {{ $lastRefreshed }}</p>

    @if ($notice)
        <div class="mt-4 rounded-lg bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-700">
            {{ $notice }}
        </div>
    @endif

    @if ($error)
        <div class="mt-4 rounded-lg bg-rose-50 px-4 py-3 text-sm font-semibold text-rose-700">
            {{ $error }}
        </div>
    @endif

    <div class="mt-5 grid gap-3 sm:grid-cols-3">
        <article class="rounded-lg bg-atlantia-blush p-4">
            <p class="text-sm text-atlantia-wine">Pendientes</p>
            <p class="mt-2 text-3xl font-black text-atlantia-ink">{{ number_format($sugerencias->count()) }}</p>
        </article>
        <article class="rounded-lg bg-amber-50 p-4">
            <p class="text-sm text-amber-800">Urgentes</p>
            <p class="mt-2 text-3xl font-black text-amber-700">{{ number_format($urgentes) }}</p>
        </article>
        <article class="rounded-lg bg-emerald-50 p-4">
            <p class="text-sm text-emerald-800">Unidades sugeridas</p>
            <p class="mt-2 text-3xl font-black text-emerald-700">{{ number_format($stockSugeridoTotal) }}</p>
        </article>
    </div>

    @if ($sugerencias->isEmpty())
        <div class="mt-5 rounded-lg border border-dashed border-atlantia-rose/30 bg-atlantia-blush/40 px-6 py-10 text-center">
            <h3 class="font-black text-atlantia-ink">No hay sugerencias pendientes</h3>
            <p class="mx-auto mt-2 max-w-lg text-sm leading-6 text-atlantia-ink/65">
                Presiona Actualizar para analizar tus productos activos. Si ML no responde, se mantiene el historial guardado.
            </p>
        </div>
    @else
        <div class="mt-5 grid gap-3">
            @foreach ($sugerencias as $suggestion)
                <article class="rounded-lg border border-atlantia-rose/15 p-4">
                    <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                        <div class="min-w-0">
                            <div class="flex flex-wrap items-center gap-2">
                                <h3 class="font-black text-atlantia-ink">{{ $suggestion->producto?->nombre ?? 'Producto no disponible' }}</h3>
                                <span class="rounded-full px-3 py-1 text-xs font-black
                                    {{ in_array($suggestion->urgencia, ['critica', 'alta'], true) ? 'bg-rose-50 text-rose-700' : 'bg-amber-50 text-amber-700' }}">
                                    {{ ucfirst($suggestion->urgencia) }}
                                </span>
                            </div>
                            <p class="mt-2 text-sm text-atlantia-ink/65">
                                Stock actual {{ number_format($suggestion->stock_actual) }} ·
                                sugerido {{ number_format($suggestion->stock_sugerido) }} ·
                                quiebre en {{ $suggestion->dias_hasta_quiebre ?? 'n/a' }} dias
                            </p>
                        </div>

                        <button
                            type="button"
                            wire:click="marcarAtendida({{ $suggestion->id }})"
                            wire:loading.attr="disabled"
                            class="rounded-md border border-atlantia-rose/25 px-4 py-2 text-sm font-bold text-atlantia-wine hover:bg-atlantia-blush"
                        >
                            Marcar atendida
                        </button>
                    </div>
                </article>
            @endforeach
        </div>
    @endif
</section>
