@props([
    'title',
    'items' => null,
    'empty' => 'No hay registros disponibles.',
])

<section {{ $attributes->merge(['class' => 'rounded-lg border border-atlantia-rose/30 bg-white p-5 shadow-sm']) }}>
    <div class="flex flex-col gap-2 border-b border-atlantia-rose/15 pb-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h2 class="text-xl font-bold text-atlantia-ink">{{ $title }}</h2>
            <p class="mt-1 text-sm text-atlantia-ink/60">Informacion operativa actualizada del modulo.</p>
        </div>
    </div>

    @if (is_array($items))
        <dl class="mt-4 grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
            @foreach ($items as $label => $value)
                <div class="rounded-lg border border-atlantia-rose/15 bg-atlantia-blush p-4">
                    <dt class="text-xs font-semibold uppercase tracking-normal text-atlantia-wine">{{ str_replace('_', ' ', $label) }}</dt>
                    <dd class="mt-1 text-xl font-bold text-atlantia-ink">
                        @if (is_scalar($value) || $value === null)
                            {{ is_numeric($value) ? number_format((float) $value, 2) : ($value ?? 'Sin dato') }}
                        @else
                            {{ collect($value)->count() }} registros
                        @endif
                    </dd>
                </div>
            @endforeach
        </dl>
    @elseif ($items instanceof \Illuminate\Contracts\Pagination\Paginator || $items instanceof \Illuminate\Support\Collection || $items instanceof \Illuminate\Database\Eloquent\Collection)
        @if ($items->count() > 0)
            <div class="mt-4 overflow-hidden rounded-lg border border-atlantia-rose/15">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-atlantia-rose/20 text-sm">
                        <tbody class="divide-y divide-atlantia-rose/20">
                        @foreach ($items as $item)
                            <tr class="bg-white transition hover:bg-atlantia-cream">
                                <td class="px-4 py-3 text-atlantia-ink">
                                    <p class="font-semibold">
                                        {{ $item->nombre ?? $item->name ?? $item->business_name ?? $item->numero_pedido ?? $item->numero_dte ?? $item->titulo ?? $item->uuid ?? 'Registro' }}
                                    </p>
                                    <p class="mt-1 text-xs text-atlantia-ink/50">
                                        {{ $item->email ?? $item->sku ?? $item->metodo ?? $item->tipo ?? optional($item->created_at)->format('d/m/Y H:i') }}
                                    </p>
                                </td>
                                <td class="px-4 py-3 text-right text-atlantia-ink/65">
                                    <span class="inline-flex rounded-md bg-atlantia-blush px-3 py-1 text-xs font-bold text-atlantia-wine">
                                        {{ $item->estado ?? $item->status ?? optional($item->created_at)->format('d/m/Y H:i') }}
                                    </span>
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            @if (method_exists($items, 'links'))
                <div class="mt-4">{{ $items->links() }}</div>
            @endif
        @else
            <p class="mt-4 text-sm text-atlantia-ink/70">{{ $empty }}</p>
        @endif
    @elseif ($items instanceof \Illuminate\Database\Eloquent\Model)
        <dl class="mt-4 grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
            @foreach ($items->getAttributes() as $label => $value)
                @if (! str_contains($label, 'password') && ! str_contains($label, 'token'))
                    <div class="rounded-lg border border-atlantia-rose/15 bg-atlantia-cream p-4">
                        <dt class="text-xs font-semibold uppercase text-atlantia-rose">{{ str_replace('_', ' ', $label) }}</dt>
                        <dd class="mt-2 break-words text-sm font-semibold text-atlantia-ink">
                            @if ($value instanceof \Carbon\CarbonInterface)
                                {{ $value->format('d/m/Y H:i') }}
                            @elseif (is_bool($value))
                                {{ $value ? 'Si' : 'No' }}
                            @elseif (is_array($value))
                                {{ json_encode($value, JSON_UNESCAPED_UNICODE) }}
                            @else
                                {{ $value ?? 'Sin dato' }}
                            @endif
                        </dd>
                    </div>
                @endif
            @endforeach
        </dl>
    @elseif ($items)
        <pre class="mt-4 overflow-x-auto rounded-md bg-atlantia-blush p-4 text-xs text-atlantia-ink">{{ json_encode($items, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
    @else
        <p class="mt-4 text-sm text-atlantia-ink/70">{{ $empty }}</p>
    @endif
</section>
