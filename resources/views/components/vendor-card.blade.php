@props([
    'vendor',
])

<article {{ $attributes->merge(['class' => 'rounded-lg border border-slate-200 bg-white p-5 shadow-sm']) }}>
    <div class="flex items-start gap-4">
        <div class="h-14 w-14 shrink-0 overflow-hidden rounded-md bg-slate-100">
            @if ($vendor->logo_path)
                <img
                    src="{{ asset('storage/' . $vendor->logo_path) }}"
                    alt="{{ $vendor->business_name }}"
                    class="h-full w-full object-cover"
                >
            @else
                <div class="flex h-full items-center justify-center text-sm font-bold text-emerald-800">
                    {{ mb_substr($vendor->business_name, 0, 1) }}
                </div>
            @endif
        </div>

        <div class="min-w-0">
            <h3 class="truncate text-base font-semibold text-slate-950">{{ $vendor->business_name }}</h3>
            <p class="mt-1 text-sm text-slate-600">{{ $vendor->municipio }}</p>
            <x-ui.badge class="mt-3" :variant="$vendor->is_approved ? 'success' : 'warning'">
                {{ $vendor->is_approved ? 'Aprobado' : 'Pendiente' }}
            </x-ui.badge>
        </div>
    </div>
</article>
