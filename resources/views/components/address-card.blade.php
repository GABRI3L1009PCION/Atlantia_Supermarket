@props([
    'direccion',
])

<article {{ $attributes->merge(['class' => 'rounded-lg border border-slate-200 bg-white p-4 shadow-sm']) }}>
    <div class="flex items-start justify-between gap-4">
        <div>
            <h3 class="font-semibold text-slate-950">{{ $direccion->alias }}</h3>
            <p class="mt-1 text-sm text-slate-600">{{ $direccion->direccion_linea_1 }}</p>
            <p class="mt-1 text-sm text-slate-600">{{ $direccion->municipio }}, Izabal</p>
        </div>

        @if ($direccion->es_principal)
            <x-ui.badge variant="success">Principal</x-ui.badge>
        @endif
    </div>
</article>
