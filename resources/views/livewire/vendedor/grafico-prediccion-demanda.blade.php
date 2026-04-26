@php
    $max = max(1, (float) $predicciones->max('valor_predicho'));
@endphp

<section wire:poll.60s="refreshChart" class="rounded-lg border border-atlantia-rose/20 bg-white p-5 shadow-sm">
    <div class="flex flex-col gap-3 border-b border-atlantia-rose/15 pb-4 lg:flex-row lg:items-end lg:justify-between">
        <div>
            <p class="text-sm font-bold uppercase tracking-normal text-atlantia-wine">Prediccion ML</p>
            <h2 class="mt-1 text-2xl font-black text-atlantia-ink">Demanda esperada</h2>
            <p class="mt-1 text-sm text-atlantia-ink/65">Estimacion por producto para anticipar inventario.</p>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            <select wire:model.live="horizonteDias" class="rounded-md border border-atlantia-rose/25 px-3 py-2 text-sm focus:border-atlantia-wine focus:ring-atlantia-rose">
                <option value="7">7 dias</option>
                <option value="14">14 dias</option>
                <option value="30">30 dias</option>
            </select>
            <button
                type="button"
                wire:click="generarPredicciones"
                wire:loading.attr="disabled"
                class="rounded-md bg-atlantia-wine px-4 py-2 text-sm font-bold text-white hover:bg-atlantia-wine-700"
            >
                <span wire:loading.remove wire:target="generarPredicciones">Actualizar</span>
                <span wire:loading wire:target="generarPredicciones">Consultando...</span>
            </button>
        </div>
    </div>

    <p class="mt-3 text-xs font-semibold text-atlantia-ink/55">Actualizado {{ $lastRefreshed }}</p>

    @if ($mlDegradado)
        <div class="mt-4 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm font-semibold text-amber-800">
            ML no respondio recientemente. Se muestran datos guardados o fallback local para que puedas continuar.
        </div>
    @endif

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

    @if ($predicciones->isEmpty())
        <div class="mt-5 rounded-lg border border-dashed border-atlantia-rose/30 bg-atlantia-blush/40 px-6 py-10 text-center">
            <h3 class="font-black text-atlantia-ink">Aun no hay predicciones de demanda</h3>
            <p class="mx-auto mt-2 max-w-lg text-sm leading-6 text-atlantia-ink/65">
                Presiona Actualizar para generar estimaciones. Si ML no responde, Atlantia usara una regla local conservadora.
            </p>
        </div>
    @else
        <div class="mt-5 grid gap-3 sm:grid-cols-3">
            <article class="rounded-lg bg-atlantia-blush p-4">
                <p class="text-sm text-atlantia-wine">Total esperado</p>
                <p class="mt-2 text-3xl font-black text-atlantia-ink">{{ number_format($totalPredicho, 0) }}</p>
            </article>
            <article class="rounded-lg bg-emerald-50 p-4">
                <p class="text-sm text-emerald-800">Productos evaluados</p>
                <p class="mt-2 text-3xl font-black text-emerald-700">{{ number_format($predicciones->count()) }}</p>
            </article>
            <article class="rounded-lg bg-sky-50 p-4">
                <p class="text-sm text-sky-800">Horizonte activo</p>
                <p class="mt-2 text-3xl font-black text-sky-700">{{ $horizonteDias }}d</p>
            </article>
        </div>

        <div class="mt-6 space-y-3">
            @foreach ($predicciones as $prediction)
                <div class="rounded-lg border border-atlantia-rose/15 p-4">
                    <div class="flex items-center justify-between gap-4">
                        <div class="min-w-0">
                            <p class="truncate font-bold text-atlantia-ink">{{ $prediction->producto?->nombre ?? 'Producto no disponible' }}</p>
                            <p class="mt-1 text-xs text-atlantia-ink/55">
                                {{ optional($prediction->fecha_prediccion)->format('d/m/Y') }} · {{ $prediction->horizonte_dias }} dias
                            </p>
                        </div>
                        <p class="text-lg font-black text-atlantia-wine">{{ number_format((float) $prediction->valor_predicho, 0) }}</p>
                    </div>
                    <div class="mt-3 h-3 overflow-hidden rounded-full bg-atlantia-blush">
                        <div
                            class="h-full rounded-full bg-atlantia-wine"
                            style="width: {{ max(6, round(((float) $prediction->valor_predicho / $max) * 100)) }}%;"
                        ></div>
                    </div>
                    <p class="mt-2 text-xs text-atlantia-ink/55">
                        Rango estimado:
                        {{ number_format((float) ($prediction->intervalo_inferior ?? 0), 0) }}
                        -
                        {{ number_format((float) ($prediction->intervalo_superior ?? 0), 0) }}
                    </p>
                </div>
            @endforeach
        </div>
    @endif
</section>
