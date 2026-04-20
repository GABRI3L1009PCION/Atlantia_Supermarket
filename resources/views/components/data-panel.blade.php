@props([
    'title',
    'items' => null,
    'empty' => 'No hay registros disponibles.',
])

<section {{ $attributes->merge(['class' => 'rounded-lg border border-atlantia-rose/30 bg-white p-5 shadow-sm']) }}>
    <h2 class="text-lg font-semibold text-atlantia-ink">{{ $title }}</h2>

    @if (is_array($items))
        <dl class="mt-4 grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
            @foreach ($items as $label => $value)
                <div class="rounded-md bg-atlantia-blush p-3">
                    <dt class="text-xs font-semibold uppercase tracking-normal text-atlantia-wine">{{ str_replace('_', ' ', $label) }}</dt>
                    <dd class="mt-1 text-xl font-bold text-atlantia-ink">
                        {{ is_numeric($value) ? number_format((float) $value, 2) : $value }}
                    </dd>
                </div>
            @endforeach
        </dl>
    @elseif ($items instanceof \Illuminate\Contracts\Pagination\Paginator || $items instanceof \Illuminate\Support\Collection || $items instanceof \Illuminate\Database\Eloquent\Collection)
        @if ($items->count() > 0)
            <div class="mt-4 overflow-x-auto">
                <table class="min-w-full divide-y divide-atlantia-rose/20 text-sm">
                    <tbody class="divide-y divide-atlantia-rose/20">
                        @foreach ($items as $item)
                            <tr>
                                <td class="py-3 text-atlantia-ink">
                                    {{ $item->nombre ?? $item->name ?? $item->business_name ?? $item->numero_pedido ?? $item->uuid ?? 'Registro' }}
                                </td>
                                <td class="py-3 text-right text-atlantia-ink/65">
                                    {{ $item->estado ?? $item->status ?? optional($item->created_at)->format('d/m/Y H:i') }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if (method_exists($items, 'links'))
                <div class="mt-4">{{ $items->links() }}</div>
            @endif
        @else
            <p class="mt-4 text-sm text-atlantia-ink/70">{{ $empty }}</p>
        @endif
    @elseif ($items)
        <pre class="mt-4 overflow-x-auto rounded-md bg-atlantia-blush p-4 text-xs text-atlantia-ink">{{ json_encode($items, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
    @else
        <p class="mt-4 text-sm text-atlantia-ink/70">{{ $empty }}</p>
    @endif
</section>

