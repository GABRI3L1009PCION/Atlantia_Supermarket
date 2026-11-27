{{-- Servicios, proceso de actualización y modelos ML (antes en la vista general del super admin). --}}
@php
    $services = $plataforma['services'];
    $release = $plataforma['release'];
    $models = $plataforma['models'];
    $healthyServices = $services->where('status', 'operativo')->count();
    $tarjetaClase = 'rounded-2xl border border-atlantia-rose/15 bg-white p-5 shadow-[0_10px_26px_rgba(59,6,12,0.05)]';
@endphp

<div class="grid gap-4 xl:grid-cols-2" data-plataforma>
    <article class="{{ $tarjetaClase }}">
        <div class="flex items-center justify-between gap-3">
            <h2 class="text-xl font-black text-atlantia-ink">Servicios</h2>
            <span class="rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-black text-emerald-700">{{ $healthyServices }} de {{ max(1, $services->count()) }} operativos</span>
        </div>
        <div class="mt-3 divide-y divide-atlantia-rose/10">
            @foreach ($services as $service)
                @php
                    $statusClass = match ($service['status']) {
                        'operativo' => 'bg-emerald-50 text-emerald-700',
                        'configurar' => 'bg-amber-50 text-amber-800',
                        default => 'bg-red-50 text-red-700',
                    };
                @endphp
                <div class="flex items-center justify-between gap-4 py-2.5">
                    <div class="min-w-0">
                        <p class="text-sm font-black text-atlantia-ink">{{ $service['name'] }}</p>
                        <p class="text-xs text-atlantia-ink/50">{{ $service['detail'] }}</p>
                    </div>
                    <span class="{{ $statusClass }} shrink-0 rounded-full px-2.5 py-1 text-xs font-black">{{ $service['status'] }}</span>
                </div>
            @endforeach
        </div>
    </article>

    <article class="{{ $tarjetaClase }}">
        <div class="flex items-center justify-between gap-3">
            <h2 class="text-xl font-black text-atlantia-ink">Modelos inteligentes</h2>
            <a href="{{ route('admin.ml.monitor') }}" class="text-sm font-black text-atlantia-wine hover:underline">Monitor ML →</a>
        </div>
        <div class="mt-3 divide-y divide-atlantia-rose/10">
            @forelse ($models as $model)
                <div class="flex items-center justify-between gap-4 py-2.5">
                    <div class="min-w-0">
                        <p class="truncate text-sm font-black text-atlantia-ink">{{ $model->nombre_modelo }}</p>
                        <p class="text-xs text-atlantia-ink/50">Versión {{ $model->version }} · MAPE {{ ($model->metricas ?? [])['mape'] ?? 'sin métrica' }}</p>
                    </div>
                    <span class="shrink-0 rounded-full bg-atlantia-blush px-2.5 py-1 text-xs font-black text-atlantia-wine">{{ $model->estado }}</span>
                </div>
            @empty
                <p class="py-6 text-sm text-atlantia-ink/50">Sin modelos desplegados.</p>
            @endforelse
        </div>
    </article>

    <article class="{{ $tarjetaClase }} xl:col-span-2">
        <div class="flex items-center justify-between gap-3">
            <h2 class="text-xl font-black text-atlantia-ink">Proceso de actualización</h2>
            <p class="text-xs font-semibold text-atlantia-ink/50">Versión {{ $release['current'] }} · rama {{ $release['branch'] }}</p>
        </div>
        <div class="mt-4 grid gap-2 md:grid-cols-5">
            @foreach ($release['pipeline'] as $step)
                @php
                    $stepClass = match ($step['status']) {
                        'lista' => 'border-emerald-200 bg-emerald-50 text-emerald-800',
                        'espera' => 'border-amber-200 bg-amber-50 text-amber-900',
                        default => 'border-atlantia-rose/15 bg-atlantia-cream/50 text-atlantia-ink/55',
                    };
                @endphp
                <div class="{{ $stepClass }} rounded-xl border px-3 py-3.5 text-center">
                    <p class="text-sm font-black">{{ $step['label'] }}</p>
                    <p class="mt-1 text-xs font-semibold opacity-80">{{ $step['detail'] }}</p>
                </div>
            @endforeach
        </div>
    </article>
</div>
