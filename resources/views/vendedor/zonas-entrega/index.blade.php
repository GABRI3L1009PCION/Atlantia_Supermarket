@extends('layouts.app')

@section('content')
    @php
        $zonasListado = collect($zonas['zonas'] ?? []);
        $resumen = $zonas['resumen'] ?? ['disponibles' => 0, 'activas' => 0, 'cobertura' => 0];
        $selected = $zonasListado->firstWhere('activa', true) ?? $zonasListado->first();
        $metricCards = [
            ['label' => 'Zonas disponibles', 'value' => $resumen['disponibles'], 'hint' => 'Zonas configuradas por Atlantia', 'icon' => 'pin'],
            ['label' => 'Zonas activas', 'value' => $resumen['activas'], 'hint' => 'Visibles para tus productos', 'icon' => 'check'],
            ['label' => 'Cobertura estimada', 'value' => $resumen['cobertura'] . '%', 'hint' => 'De tu area operativa', 'icon' => 'users'],
        ];
        $icons = [
            'pin' => '<path d="M12 21s7-4.5 7-11a7 7 0 1 0-14 0c0 6.5 7 11 7 11Z"/><circle cx="12" cy="10" r="2.5"/>',
            'check' => '<path d="M9 12l2 2 4-5"/><path d="M12 3l7 4v5c0 5-3.5 8-7 9-3.5-1-7-4-7-9V7l7-4Z"/>',
            'users' => '<path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/>',
        ];
    @endphp

    <section class="mx-auto max-w-[1280px] space-y-5 pb-10">
        <form method="POST" action="{{ route('vendedor.zonas-entrega.sync') }}" id="vendor-delivery-zones-form">
            @csrf
            @method('PUT')

            <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                <div>
                    <h1 class="text-3xl font-black leading-tight text-atlantia-ink sm:text-4xl">Zonas de entrega</h1>
                    <p class="mt-2 text-sm leading-6 text-atlantia-ink/62">
                        Configura la cobertura operativa de tu tienda. El costo que definas aqui aplica solo a tus productos.
                    </p>
                </div>
                <div class="flex flex-col gap-2 sm:flex-row sm:items-center">
                    <p class="text-sm text-atlantia-ink/60">Solo las zonas activas se mostraran a tus clientes.</p>
                    <button type="submit" class="inline-flex items-center justify-center gap-2 rounded-lg bg-atlantia-wine px-6 py-3 text-sm font-black text-white shadow-lg shadow-atlantia-wine/15 transition hover:bg-atlantia-wine-700">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2Z"/><path d="M17 21v-8H7v8"/><path d="M7 3v5h8"/></svg>
                        Guardar cambios
                    </button>
                </div>
            </div>

            <div class="grid gap-4 md:grid-cols-3">
                @foreach ($metricCards as $card)
                    <article class="rounded-lg border border-atlantia-rose/15 bg-white p-5 shadow-[0_12px_32px_rgba(42,16,24,0.05)]">
                        <div class="flex items-center gap-5">
                            <span class="grid h-16 w-16 shrink-0 place-items-center rounded-full bg-atlantia-blush text-atlantia-wine">
                                <svg class="h-7 w-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">{!! $icons[$card['icon']] !!}</svg>
                            </span>
                            <div>
                                <p class="text-xs font-black uppercase tracking-wide text-atlantia-wine">{{ $card['label'] }}</p>
                                <p class="mt-1 text-3xl font-black leading-none text-atlantia-ink">{{ $card['value'] }}</p>
                                <p class="mt-2 text-sm text-atlantia-ink/60">{{ $card['hint'] }}</p>
                            </div>
                        </div>
                    </article>
                @endforeach
            </div>

            <div class="grid gap-5 xl:grid-cols-[1.25fr_0.9fr]">
                <article class="rounded-lg border border-atlantia-rose/15 bg-white p-5 shadow-[0_12px_32px_rgba(42,16,24,0.05)]">
                    <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                        <div>
                            <h2 class="text-xl font-black text-atlantia-ink">Listado de zonas</h2>
                            <p class="mt-1 text-sm text-atlantia-ink/60">Edita el precio y activa las zonas que atiende tu tienda.</p>
                        </div>
                        <div class="flex flex-col gap-3 sm:flex-row">
                            <div class="relative">
                                <input type="search" id="delivery-zone-search" placeholder="Buscar zona" class="w-full rounded-lg border border-atlantia-rose/25 bg-white px-4 py-3 pl-10 text-sm outline-none transition focus:border-atlantia-wine focus:ring-2 focus:ring-atlantia-rose/20 sm:w-72">
                                <svg class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-atlantia-ink/40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>
                            </div>
                            <div class="inline-flex rounded-full border border-atlantia-rose/20 bg-white p-1">
                                <button type="button" data-zone-filter="all" class="rounded-full bg-atlantia-wine px-4 py-2 text-xs font-black text-white">Todas</button>
                                <button type="button" data-zone-filter="active" class="rounded-full px-4 py-2 text-xs font-black text-atlantia-ink/60">Activas</button>
                                <button type="button" data-zone-filter="inactive" class="rounded-full px-4 py-2 text-xs font-black text-atlantia-ink/60">Inactivas</button>
                            </div>
                        </div>
                    </div>

                    <div class="mt-5 overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <thead>
                                <tr class="border-b border-atlantia-rose/15 text-left text-xs font-black uppercase tracking-wide text-atlantia-ink/55">
                                    <th class="px-3 py-3">Zona</th>
                                    <th class="px-3 py-3">Estado</th>
                                    <th class="px-3 py-3">Costo de envio</th>
                                    <th class="px-3 py-3">Tiempo</th>
                                    <th class="px-3 py-3 text-right">Activar</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-atlantia-rose/12">
                                @forelse ($zonasListado as $index => $zona)
                                    <tr
                                        class="delivery-zone-row cursor-pointer transition hover:bg-atlantia-cream/45"
                                        data-zone-row
                                        data-zone-name="{{ \Illuminate\Support\Str::lower($zona['nombre'] . ' ' . $zona['municipio']) }}"
                                        data-zone-active="{{ $zona['activa'] ? '1' : '0' }}"
                                        data-zone-title="{{ $zona['nombre'] }}"
                                        data-zone-cost="{{ number_format((float) $zona['costo_envio'], 2) }}"
                                        data-zone-base="{{ number_format((float) $zona['costo_base'], 2) }}"
                                        data-zone-time="{{ (int) $zona['tiempo_estimado_min'] }}"
                                        data-zone-radius="{{ number_format((float) $zona['radio_km'], 1) }}"
                                    >
                                        <td class="px-3 py-3">
                                            <input type="hidden" name="zonas[{{ $index }}][delivery_zone_id]" value="{{ $zona['id'] }}">
                                            <input type="hidden" name="zonas[{{ $index }}][activa]" value="0">
                                            <div class="flex items-center gap-3">
                                                <span class="grid h-10 w-10 shrink-0 place-items-center rounded-full bg-atlantia-blush text-atlantia-wine">
                                                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M12 21s7-4.5 7-11a7 7 0 1 0-14 0c0 6.5 7 11 7 11Z"/><circle cx="12" cy="10" r="2.5"/></svg>
                                                </span>
                                                <div>
                                                    <p class="font-black text-atlantia-ink">{{ $zona['nombre'] }}</p>
                                                    <p class="text-xs text-atlantia-ink/50">{{ $zona['municipio'] }}</p>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-3 py-3">
                                            <span class="{{ $zona['activa'] ? 'bg-emerald-50 text-emerald-700 ring-emerald-200' : 'bg-rose-50 text-rose-700 ring-rose-200' }} inline-flex rounded-full px-3 py-1 text-xs font-black ring-1">
                                                {{ $zona['activa'] ? 'Activa' : 'Inactiva' }}
                                            </span>
                                        </td>
                                        <td class="px-3 py-3">
                                            <div class="flex items-center gap-2">
                                                <span class="font-black text-atlantia-ink">Q</span>
                                                <input name="zonas[{{ $index }}][costo_override]" value="{{ number_format((float) $zona['costo_envio'], 2, '.', '') }}" type="number" step="0.01" min="0" class="w-24 rounded-lg border border-atlantia-rose/25 px-3 py-2 text-sm font-bold text-atlantia-ink outline-none focus:border-atlantia-wine focus:ring-2 focus:ring-atlantia-rose/15">
                                            </div>
                                            <p class="mt-1 text-[11px] text-atlantia-ink/45">Base super: Q {{ number_format((float) $zona['costo_base'], 2) }}</p>
                                        </td>
                                        <td class="px-3 py-3">
                                            <div class="flex items-center gap-2">
                                                <input name="zonas[{{ $index }}][tiempo_estimado_min]" value="{{ (int) $zona['tiempo_estimado_min'] }}" type="number" min="5" max="240" class="w-20 rounded-lg border border-atlantia-rose/25 px-3 py-2 text-sm font-bold text-atlantia-ink outline-none focus:border-atlantia-wine focus:ring-2 focus:ring-atlantia-rose/15">
                                                <span class="text-xs text-atlantia-ink/50">min</span>
                                            </div>
                                        </td>
                                        <td class="px-3 py-3 text-right">
                                            <label class="relative inline-flex cursor-pointer items-center">
                                                <input type="checkbox" name="zonas[{{ $index }}][activa]" value="1" class="peer sr-only" @checked($zona['activa'])>
                                                <span class="h-7 w-12 rounded-full bg-slate-200 transition peer-checked:bg-atlantia-wine"></span>
                                                <span class="absolute left-1 h-5 w-5 rounded-full bg-white shadow transition peer-checked:translate-x-5"></span>
                                            </label>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="px-3 py-10 text-center text-atlantia-ink/55">No hay zonas disponibles para configurar.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </article>

                <aside class="space-y-4">
                    <article class="rounded-lg border border-atlantia-rose/15 bg-white p-5 shadow-[0_12px_32px_rgba(42,16,24,0.05)]">
                        <div class="flex items-start justify-between gap-3">
                            <div class="flex items-start gap-3">
                                <span class="grid h-11 w-11 shrink-0 place-items-center rounded-full bg-atlantia-blush text-atlantia-wine">
                                    <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M12 21s7-4.5 7-11a7 7 0 1 0-14 0c0 6.5 7 11 7 11Z"/><circle cx="12" cy="10" r="2.5"/></svg>
                                </span>
                                <div>
                                    <h2 class="text-xl font-black text-atlantia-ink">Detalles de la zona</h2>
                                    <p id="zone-detail-name" class="mt-1 text-sm text-atlantia-ink/60">{{ $selected['nombre'] ?? 'Selecciona una zona' }}</p>
                                </div>
                            </div>
                            <span id="zone-detail-status" class="rounded-md bg-emerald-50 px-4 py-2 text-sm font-black text-emerald-700">{{ ($selected['activa'] ?? false) ? 'Activa' : 'Inactiva' }}</span>
                        </div>

                        <div class="mt-5 grid gap-3 sm:grid-cols-2">
                            <div class="rounded-lg border border-atlantia-rose/15 p-4">
                                <p class="text-xs text-atlantia-ink/55">Costo de envio</p>
                                <p class="mt-1 text-xl font-black text-atlantia-ink">Q <span id="zone-detail-cost">{{ isset($selected['costo_envio']) ? number_format((float) $selected['costo_envio'], 2) : '0.00' }}</span></p>
                                <p class="mt-1 text-xs text-atlantia-ink/50">Por pedido de tu tienda</p>
                            </div>
                            <div class="rounded-lg border border-atlantia-rose/15 p-4">
                                <p class="text-xs text-atlantia-ink/55">Tiempo estimado</p>
                                <p class="mt-1 text-xl font-black text-atlantia-ink"><span id="zone-detail-time">{{ (int) ($selected['tiempo_estimado_min'] ?? 30) }}</span> min</p>
                                <p class="mt-1 text-xs text-atlantia-ink/50">Entrega estimada</p>
                            </div>
                        </div>

                        <div class="mt-3 overflow-hidden rounded-lg border border-atlantia-rose/15">
                            <div class="grid min-h-[150px] grid-cols-[1fr_1.15fr] bg-atlantia-cream/45">
                                <div class="p-5">
                                    <p class="text-xs text-atlantia-ink/55">Cobertura</p>
                                    <p class="mt-1 text-sm text-atlantia-ink/65">Radio aproximado</p>
                                    <p class="mt-1 text-xl font-black text-atlantia-ink"><span id="zone-detail-radius">{{ number_format((float) ($selected['radio_km'] ?? 3.5), 1) }}</span> km</p>
                                </div>
                                <div class="relative bg-[linear-gradient(135deg,#fff_0%,#f8eef2_45%,#eef8f2_100%)]">
                                    <div class="absolute inset-5 rounded-full border border-atlantia-rose/25 bg-atlantia-blush/45"></div>
                                    <svg class="absolute inset-0 h-full w-full text-atlantia-rose/25" viewBox="0 0 220 150" fill="none" aria-hidden="true">
                                        <path d="M0 38h220M0 84h220M48 0v150M126 0v150M188 0v150" stroke="currentColor"/>
                                        <path d="M20 130 200 20M12 18l190 110" stroke="currentColor"/>
                                    </svg>
                                    <svg class="absolute left-1/2 top-1/2 h-10 w-10 -translate-x-1/2 -translate-y-1/2 text-atlantia-wine" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12 2a7 7 0 0 0-7 7c0 5.25 7 13 7 13s7-7.75 7-13a7 7 0 0 0-7-7Zm0 9.5A2.5 2.5 0 1 1 12 6a2.5 2.5 0 0 1 0 5.5Z"/></svg>
                                </div>
                            </div>
                        </div>

                        <div class="mt-3 rounded-lg border border-atlantia-rose/15 p-4">
                            <p class="text-sm font-black text-atlantia-ink">Tarifa independiente</p>
                            <p class="mt-1 text-sm leading-6 text-atlantia-ink/60">
                                Atlantia mantiene el costo base general. Tu precio de envio se guarda aparte y se usa para tus pedidos.
                            </p>
                        </div>
                    </article>
                </aside>
            </div>
        </form>
    </section>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const rows = Array.from(document.querySelectorAll('[data-zone-row]'));
            const search = document.getElementById('delivery-zone-search');
            const filterButtons = Array.from(document.querySelectorAll('[data-zone-filter]'));
            const details = {
                name: document.getElementById('zone-detail-name'),
                status: document.getElementById('zone-detail-status'),
                cost: document.getElementById('zone-detail-cost'),
                time: document.getElementById('zone-detail-time'),
                radius: document.getElementById('zone-detail-radius'),
            };
            let currentFilter = 'all';

            const setDetails = (row) => {
                if (! row) return;
                const active = row.dataset.zoneActive === '1';
                details.name.textContent = row.dataset.zoneTitle || 'Zona';
                details.status.textContent = active ? 'Activa' : 'Inactiva';
                details.status.className = active
                    ? 'rounded-md bg-emerald-50 px-4 py-2 text-sm font-black text-emerald-700'
                    : 'rounded-md bg-rose-50 px-4 py-2 text-sm font-black text-rose-700';
                details.cost.textContent = row.dataset.zoneCost || '0.00';
                details.time.textContent = row.dataset.zoneTime || '30';
                details.radius.textContent = row.dataset.zoneRadius || '3.5';
                rows.forEach((item) => item.classList.remove('bg-atlantia-blush/45'));
                row.classList.add('bg-atlantia-blush/45');
            };

            const applyFilters = () => {
                const term = (search?.value || '').trim().toLowerCase();
                rows.forEach((row) => {
                    const matchesText = ! term || (row.dataset.zoneName || '').includes(term);
                    const matchesFilter = currentFilter === 'all'
                        || (currentFilter === 'active' && row.dataset.zoneActive === '1')
                        || (currentFilter === 'inactive' && row.dataset.zoneActive === '0');
                    row.classList.toggle('hidden', ! matchesText || ! matchesFilter);
                });
            };

            rows.forEach((row) => {
                row.addEventListener('click', (event) => {
                    if (event.target.closest('input, label, button, select')) return;
                    setDetails(row);
                });
                const checkbox = row.querySelector('input[type="checkbox"]');
                checkbox?.addEventListener('change', () => {
                    row.dataset.zoneActive = checkbox.checked ? '1' : '0';
                    row.querySelector('td:nth-child(2) span').textContent = checkbox.checked ? 'Activa' : 'Inactiva';
                    row.querySelector('td:nth-child(2) span').className = checkbox.checked
                        ? 'bg-emerald-50 text-emerald-700 ring-emerald-200 inline-flex rounded-full px-3 py-1 text-xs font-black ring-1'
                        : 'bg-rose-50 text-rose-700 ring-rose-200 inline-flex rounded-full px-3 py-1 text-xs font-black ring-1';
                    setDetails(row);
                    applyFilters();
                });
            });

            search?.addEventListener('input', applyFilters);
            filterButtons.forEach((button) => {
                button.addEventListener('click', () => {
                    currentFilter = button.dataset.zoneFilter || 'all';
                    filterButtons.forEach((item) => {
                        item.className = 'rounded-full px-4 py-2 text-xs font-black text-atlantia-ink/60';
                    });
                    button.className = 'rounded-full bg-atlantia-wine px-4 py-2 text-xs font-black text-white';
                    applyFilters();
                });
            });
            setDetails(rows.find((row) => row.dataset.zoneActive === '1') || rows[0]);
        });
    </script>
@endsection
