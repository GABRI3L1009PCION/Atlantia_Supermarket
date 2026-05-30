@extends(auth()->user()?->isSuperAdmin() && request()->routeIs('admin.*') ? 'layouts.super-admin' : 'layouts.app')

@section('content')
    @php
        $vendorsCollection = $vendors->getCollection();
        $statusLabels = [
            'pending' => 'Pendiente',
            'approved' => 'Aprobado',
            'rejected' => 'Rechazado',
            'suspended' => 'Suspendido',
        ];
        $statusTabs = [
            'pending' => 'Pendientes',
            'approved' => 'Aprobados',
            'rejected' => 'Rechazados',
            'suspended' => 'Suspendidos',
        ];
        $statusClasses = [
            'pending' => 'bg-amber-50 text-amber-700 ring-1 ring-amber-200',
            'approved' => 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-200',
            'rejected' => 'bg-rose-50 text-rose-700 ring-1 ring-rose-200',
            'suspended' => 'bg-slate-100 text-slate-700 ring-1 ring-slate-200',
        ];
        $countByStatus = collect($statusTabs)->mapWithKeys(fn ($label, $status) => [$status => $vendorsCollection->where('status', $status)->count()]);
        $approvedCount = $countByStatus['approved'] ?? 0;
        $pendingTotal = (float) $vendorsCollection->sum(fn ($vendor) => (float) ($vendor->pending_commission_total ?? 0));
        $salesTotal = (float) $vendorsCollection->sum(fn ($vendor) => (float) ($vendor->sales_30_total ?? 0));
        $ratings = $vendorsCollection->map(function ($vendor) {
            return $vendor->productos->flatMap->resenas->avg('calificacion');
        })->filter();
        $avgRating = $ratings->count() ? round((float) $ratings->avg(), 1) : 0;
        $vendorsPayload = $vendorsCollection->mapWithKeys(function ($vendor) use ($statusLabels) {
            $reviews = $vendor->productos->flatMap->resenas;
            $rating = $reviews->count() ? round((float) $reviews->avg('calificacion'), 1) : 0;
            $orders30 = (int) ($vendor->orders_30_count ?? 0);
            $delivered30 = (int) ($vendor->delivered_30_count ?? 0);
            $compliance = $orders30 > 0 ? round(($delivered30 / $orders30) * 100) : 100;
            $documents = collect($vendor->documents ?? [])->filter()->count();
            $personalAddress = $vendor->personal_address ?? [];
            $businessAddress = $vendor->business_address ?? [];
            $bankingInfo = $vendor->banking_info ?? [];
            $documentLabels = [
                'document_front' => 'Documento frente',
                'document_back' => 'Documento reverso',
                'business_logo' => 'Logo/foto del negocio',
                'bank_proof' => 'Comprobante bancario',
                'nit_file' => 'NIT/RIT',
            ];
            $documentLinks = collect($vendor->documents ?? [])->filter()->map(function ($path, $key) use ($documentLabels): array {
                return [
                    'label' => $documentLabels[$key] ?? str($key)->replace('_', ' ')->title()->toString(),
                    'url' => \Illuminate\Support\Facades\Storage::disk('public')->url((string) $path),
                    'path' => $path,
                ];
            })->values()->all();

            return [
                $vendor->id => [
                    'id' => $vendor->id,
                    'name' => $vendor->user?->name ?? $vendor->business_name,
                    'business' => $vendor->business_name,
                    'email' => $vendor->user?->email ?? $vendor->email_publico,
                    'phone' => $vendor->telefono_publico ?: $vendor->user?->phone,
                    'status' => $vendor->status,
                    'status_label' => $statusLabels[$vendor->status] ?? ucfirst($vendor->status),
                    'code' => $vendor->application_code ?: 'VND-' . str_pad((string) $vendor->id, 4, '0', STR_PAD_LEFT),
                    'municipio' => $vendor->municipio,
                    'address' => $vendor->direccion_comercial,
                    'birthdate' => optional($vendor->birthdate)->format('d/m/Y') ?: 'No registrada',
                    'gender' => $vendor->gender ?: 'No indicado',
                    'personal_address' => [
                        'calle' => $personalAddress['calle'] ?? null,
                        'numero' => $personalAddress['numero'] ?? null,
                        'apto' => $personalAddress['apto'] ?? null,
                        'municipio' => $personalAddress['municipio'] ?? $vendor->municipio,
                        'departamento' => $personalAddress['departamento'] ?? null,
                        'codigo_postal' => $personalAddress['codigo_postal'] ?? null,
                    ],
                    'document_type' => strtoupper((string) ($vendor->document_type ?? 'DPI')),
                    'document_number' => $vendor->document_number ?: 'Pendiente',
                    'category' => $vendor->business_category ?: 'Sin categoria',
                    'description' => str($vendor->descripcion ?: 'Sin descripcion registrada.')->limit(180)->toString(),
                    'description_full' => $vendor->descripcion ?: 'Sin descripcion registrada.',
                    'has_nit' => (bool) $vendor->has_nit,
                    'fiscal' => [
                        'nit' => $vendor->fiscalProfile?->nit ?: 'No aplica',
                        'razon_social' => $vendor->fiscalProfile?->razon_social ?: 'No aplica',
                        'regimen_sat' => $vendor->fiscalProfile?->regimen_sat ?: 'No aplica',
                        'direccion_fiscal' => $vendor->fiscalProfile?->direccion_fiscal ?: 'No aplica',
                    ],
                    'business_address' => [
                        'calle' => $businessAddress['calle'] ?? null,
                        'numero' => $businessAddress['numero'] ?? null,
                        'municipio' => $businessAddress['municipio'] ?? $vendor->municipio,
                    ],
                    'banking' => [
                        'banco' => $bankingInfo['banco'] ?? $vendor->fiscalProfile?->banco_nombre ?? 'No registrado',
                        'tipo_cuenta' => $bankingInfo['tipo_cuenta'] ?? $vendor->fiscalProfile?->cuenta_bancaria_tipo ?? 'No registrado',
                        'numero_cuenta' => $bankingInfo['numero_cuenta'] ?? $vendor->fiscalProfile?->cuenta_bancaria ?? 'No registrado',
                        'titular' => $bankingInfo['titular'] ?? $vendor->fiscalProfile?->cuenta_bancaria_titular ?? 'No registrado',
                    ],
                    'payment_frequency' => $vendor->payment_frequency ?: 'No registrada',
                    'preferred_payment_method' => $vendor->preferred_payment_method ?: 'No registrado',
                    'document_links' => $documentLinks,
                    'documents' => $documents,
                    'documents_total' => $vendor->has_nit ? 5 : 4,
                    'created_at' => optional($vendor->created_at)->format('d/m/Y H:i'),
                    'created_relative' => optional($vendor->created_at)->diffForHumans(),
                    'approved_at' => optional($vendor->approved_at)->format('d/m/Y H:i') ?: 'Pendiente',
                    'suspended_at' => optional($vendor->suspendido_at)->format('d/m/Y H:i') ?: 'No aplica',
                    'suspension_reason' => $vendor->motivo_suspension ?: 'Sin motivo registrado.',
                    'rejection_reason' => $vendor->rejection_reason ?: 'Sin motivo registrado.',
                    'active_products' => (int) ($vendor->active_products_count ?? 0),
                    'sales_30' => (float) ($vendor->sales_30_total ?? 0),
                    'commission_owed' => (float) ($vendor->pending_commission_total ?? 0),
                    'rating' => $rating,
                    'reviews_count' => $reviews->count(),
                    'orders_30' => $orders30,
                    'delivered_30' => $delivered30,
                    'cancelled_30' => (int) ($vendor->cancelled_30_count ?? 0),
                    'compliance' => $compliance,
                    'commission_percentage' => (float) $vendor->commission_percentage,
                    'monthly_rent' => (float) $vendor->monthly_rent,
                    'plan_label' => match (true) {
                        (int) $vendor->monthly_rent === 0 && (float) $vendor->commission_percentage === 18.0 => 'Emprendedor Starter',
                        (int) $vendor->monthly_rent === 149 => 'Emprendedor Plus',
                        (int) $vendor->monthly_rent === 349 => 'Crecimiento',
                        (int) $vendor->monthly_rent === 699 => 'Profesional',
                        default => 'Personalizado',
                    },
                    'show_url' => route('admin.vendedores.show', $vendor),
                    'approve_url' => route('admin.vendedores.approve', $vendor),
                    'suspend_url' => route('admin.vendedores.suspend', $vendor),
                    'reactivate_url' => route('admin.vendedores.reactivate', $vendor),
                ],
            ];
        });
    @endphp

    <section class="vendors-light-surface -mx-4 -my-6 bg-white px-4 py-5 text-atlantia-ink sm:-mx-6 sm:px-6 lg:-mx-8 lg:px-8">
        <div class="mx-auto max-w-7xl rounded-2xl border border-atlantia-rose/20 bg-white p-6 shadow-sm">
            <header class="flex flex-col gap-5 xl:flex-row xl:items-center xl:justify-between">
                <div>
                    <p class="text-xs font-black uppercase tracking-wide text-atlantia-rose">Atlantia Supermarket</p>
                    <h1 class="mt-2 text-4xl font-black leading-tight text-atlantia-ink">Vendedores</h1>
                    <p class="mt-3 max-w-3xl text-base leading-7 text-atlantia-ink/70">
                        Gestiona solicitudes, aprobaciones, comisiones y desempeño de vendedores
                    </p>
                </div>
                <div class="flex flex-wrap gap-3">
                    <a href="{{ route('admin.vendedores.report.pdf') }}" class="rounded-md border border-atlantia-rose/35 bg-white px-4 py-2.5 text-sm font-black text-atlantia-wine transition hover:bg-atlantia-blush">
                        Exportar reporte
                    </a>
                    <button type="button" class="rounded-md bg-atlantia-wine px-4 py-2.5 text-sm font-black text-white transition hover:bg-atlantia-wine-700" data-open-stats>
                        Ver estadisticas
                    </button>
                </div>
            </header>

            <div class="mt-6 grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                <article class="rounded-xl border border-atlantia-rose/20 bg-white p-5 shadow-sm">
                    <p class="text-sm font-bold text-atlantia-ink/60">Total vendedores</p>
                    <p class="mt-1 text-3xl font-black text-atlantia-wine">{{ $vendors->total() }}</p>
                    <p class="text-xs text-atlantia-ink/55">{{ $approvedCount }} activos</p>
                </article>
                <article class="rounded-xl border border-atlantia-rose/20 bg-white p-5 shadow-sm">
                    <p class="text-sm font-bold text-atlantia-ink/60">Ventas 30 dias</p>
                    <p class="mt-1 text-3xl font-black text-atlantia-wine">Q{{ number_format($salesTotal, 2) }}</p>
                    <p class="text-xs text-atlantia-ink/55">Suma de vendedores visibles</p>
                </article>
                <article class="rounded-xl border border-atlantia-rose/20 bg-white p-5 shadow-sm">
                    <p class="text-sm font-bold text-atlantia-ink/60">Comision pendiente</p>
                    <p class="mt-1 text-3xl font-black text-atlantia-wine">Q{{ number_format($pendingTotal, 2) }}</p>
                    <p class="text-xs text-atlantia-ink/55">Pendiente/facturada</p>
                </article>
                <article class="rounded-xl border border-atlantia-rose/20 bg-white p-5 shadow-sm">
                    <p class="text-sm font-bold text-atlantia-ink/60">Rating promedio</p>
                    <p class="mt-2 text-3xl font-black text-atlantia-wine">{{ number_format($avgRating, 1) }}★</p>
                    <p class="text-xs text-atlantia-ink/55">Reseñas aprobadas registradas</p>
                </article>
            </div>

            <div class="mt-6 rounded-xl border border-atlantia-rose/20 bg-white p-4">
                <div class="grid gap-3 xl:grid-cols-[1fr_auto] xl:items-center">
                    <div class="relative">
                        <span class="pointer-events-none absolute left-4 top-1/2 -translate-y-1/2 text-atlantia-ink/40">
                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M11 19C15.4 19 19 15.4 19 11C19 6.6 15.4 3 11 3C6.6 3 3 6.6 3 11C3 15.4 6.6 19 11 19Z" stroke="currentColor" stroke-width="2"/><path d="M20.5 20.5L16.7 16.7" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
                        </span>
                        <input type="search" class="w-full rounded-md border border-atlantia-rose/35 bg-white py-3 pl-12 pr-4 text-sm outline-none transition focus:border-atlantia-wine focus:ring-2 focus:ring-atlantia-rose/25" placeholder="Buscar por nombre, email, negocio, DPI..." data-vendor-search>
                    </div>
                    <button type="button" class="rounded-md border border-atlantia-rose/35 px-4 py-3 text-sm font-black text-atlantia-wine transition hover:bg-atlantia-blush" data-toggle-filters>
                        Filtros avanzados
                    </button>
                </div>
                <div class="mt-4 hidden grid gap-3 rounded-xl border border-atlantia-rose/15 bg-atlantia-cream p-4 md:grid-cols-4" data-advanced-filters>
                    <select class="rounded-md border border-atlantia-rose/35 px-3 py-2 text-sm" data-filter-rating>
                        <option value="">Rating</option>
                        <option value="4">≥ 4.0</option>
                        <option value="4.5">≥ 4.5</option>
                        <option value="3">≥ 3.0</option>
                        <option value="lt3">&lt; 3.0</option>
                    </select>
                    <select class="rounded-md border border-atlantia-rose/35 px-3 py-2 text-sm" data-filter-commission>
                        <option value="">Comision pendiente</option>
                        <option value="100">Mayor a Q100</option>
                        <option value="500">Mayor a Q500</option>
                        <option value="1000">Mayor a Q1,000</option>
                    </select>
                    <select class="rounded-md border border-atlantia-rose/35 px-3 py-2 text-sm" data-filter-compliance>
                        <option value="">Cumplimiento</option>
                        <option value="95">≥ 95%</option>
                        <option value="85">≥ 85%</option>
                        <option value="lt85">&lt; 85%</option>
                    </select>
                    <button type="button" class="rounded-md bg-atlantia-wine px-4 py-2 text-sm font-black text-white" data-clear-filters>Limpiar</button>
                </div>
            </div>

            <div class="mt-6 flex flex-wrap gap-2" role="tablist" aria-label="Estados de vendedores">
                @foreach ($statusTabs as $status => $label)
                    <button type="button" class="{{ $loop->first ? 'bg-atlantia-wine text-white' : 'bg-white text-atlantia-wine' }} rounded-md border border-atlantia-rose/30 px-4 py-2.5 text-sm font-black transition hover:bg-atlantia-blush hover:text-atlantia-wine" data-vendor-tab="{{ $status }}">
                        {{ $label }}
                        <span class="ml-2 rounded-full bg-white/20 px-2 py-0.5">{{ $countByStatus[$status] ?? 0 }}</span>
                    </button>
                @endforeach
                <button type="button" class="bg-white text-atlantia-wine rounded-md border border-atlantia-rose/30 px-4 py-2.5 text-sm font-black transition hover:bg-atlantia-blush" data-vendor-tab="dashboard">
                    Dashboard
                </button>
            </div>

            @foreach ($statusTabs as $status => $label)
                @php
                    $tabVendors = $vendorsCollection->where('status', $status);
                    $tabVendors = match ($status) {
                        'pending' => $tabVendors->sortBy('created_at'),
                        'approved' => $tabVendors->sortByDesc(fn ($vendor) => (float) ($vendor->sales_30_total ?? 0)),
                        default => $tabVendors->sortByDesc('updated_at'),
                    };
                @endphp
                <section class="{{ $loop->first ? '' : 'hidden' }} mt-5" data-vendor-panel="{{ $status }}">
                    @if (array_key_exists($status, $statusTabs))
                        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                            @forelse ($tabVendors as $vendor)
                                @php
                                    $payload = $vendorsPayload[$vendor->id];
                                    $initials = collect(explode(' ', $payload['name']))->filter()->take(2)->map(fn ($part) => strtoupper(mb_substr($part, 0, 1)))->join('') ?: 'VD';
                                    $progress = $payload['documents_total'] > 0 ? ($payload['documents'] / $payload['documents_total']) * 100 : 0;
                                @endphp

                                <article class="rounded-xl border border-atlantia-rose/20 bg-white p-4 shadow-sm transition hover:-translate-y-0.5 hover:border-atlantia-wine/40 hover:shadow-md" data-vendor-row data-status="{{ $status }}" data-search="{{ strtolower($payload['name'] . ' ' . $payload['email'] . ' ' . $payload['business'] . ' ' . $payload['code']) }}" data-rating="{{ $payload['rating'] }}" data-commission="{{ $payload['commission_owed'] }}" data-compliance="{{ $payload['compliance'] }}">
                                    <div class="flex items-start gap-4">
                                        <span class="grid h-12 w-12 shrink-0 place-items-center rounded-xl bg-atlantia-blush text-base font-black text-atlantia-wine ring-1 ring-atlantia-rose/20">{{ $initials }}</span>
                                        <div class="min-w-0 flex-1">
                                            <div class="flex items-start justify-between gap-3">
                                                <p class="truncate text-base font-black text-atlantia-ink">{{ $payload['business'] }}</p>
                                                <span class="{{ $statusClasses[$status] }} rounded-md px-3 py-1 text-xs font-black">{{ $payload['status_label'] }}</span>
                                            </div>
                                            <p class="mt-1 text-xs font-semibold text-atlantia-ink/50">{{ $payload['document_type'] }}: {{ $payload['document_number'] }}</p>
                                        </div>
                                    </div>

                                    <div class="mt-5 rounded-xl border border-atlantia-rose/15 bg-atlantia-cream/60 p-4">
                                        <p class="text-xs font-black uppercase tracking-wide text-atlantia-rose">Negocio</p>
                                        <p class="mt-1 font-black text-atlantia-ink">{{ $payload['name'] }}</p>
                                        <p class="mt-1 text-xs text-atlantia-ink/55">{{ $payload['category'] }}</p>
                                    </div>

                                    <div class="mt-4 grid gap-3 text-sm">
                                        <div>
                                            <p class="text-xs font-black uppercase tracking-wide text-atlantia-ink/45">Email</p>
                                            <p class="mt-1 truncate text-atlantia-ink/75">{{ $payload['email'] }}</p>
                                        </div>
                                        <div>
                                            <p class="text-xs font-black uppercase tracking-wide text-atlantia-ink/45">Telefono</p>
                                            <p class="mt-1 text-atlantia-ink/75">{{ $payload['phone'] ?: 'No registrado' }}</p>
                                        </div>
                                    </div>

                                    <div class="mt-4">
                                        <div class="flex items-center justify-between gap-3">
                                            <p class="text-xs font-black uppercase tracking-wide text-atlantia-ink/45">Documentos</p>
                                            <p class="text-xs font-black text-atlantia-wine">{{ $payload['documents'] }}/{{ $payload['documents_total'] }}</p>
                                        </div>
                                        <div class="mt-2 h-2 overflow-hidden rounded-full bg-atlantia-blush">
                                            <div class="h-full rounded-full bg-atlantia-wine" style="width: {{ $progress }}%"></div>
                                        </div>
                                    </div>

                                    <div class="mt-5 flex items-center justify-between gap-3 border-t border-atlantia-rose/15 pt-4">
                                        <p class="text-xs font-bold text-atlantia-ink/50">{{ $payload['created_relative'] }}</p>
                                        <div class="flex gap-2">
                                            <a href="{{ $payload['show_url'] }}" class="rounded-lg border border-atlantia-rose/30 px-4 py-2 text-xs font-black text-atlantia-wine transition hover:bg-atlantia-blush">Ver perfil</a>
                                            <button type="button" class="rounded-lg bg-atlantia-wine px-4 py-2 text-xs font-black text-white transition hover:bg-atlantia-wine-700" data-open-vendor="{{ $vendor->id }}">Revisar</button>
                                        </div>
                                    </div>
                                </article>
                            @empty
                                <div class="rounded-2xl border border-atlantia-rose/20 bg-white p-10 text-center text-atlantia-ink/60 md:col-span-2 xl:col-span-3">
                                    No hay vendedores en esta pestana.
                                </div>
                            @endforelse
                        </div>
                    @else
                    <div class="overflow-x-auto rounded-xl border border-atlantia-rose/20 bg-white">
                        <table class="min-w-[1100px] w-full text-sm">
                            <thead class="bg-atlantia-cream">
                                <tr class="border-b border-atlantia-rose/20 text-left text-xs font-black uppercase tracking-wide text-atlantia-ink/55">
                                    @if ($status === 'pending')
                                        <th class="px-4 py-3">Nombre vendedor</th>
                                        <th class="px-4 py-3">Negocio</th>
                                        <th class="px-4 py-3">Email</th>
                                        <th class="px-4 py-3">Telefono</th>
                                        <th class="px-4 py-3">Documentos</th>
                                        <th class="px-4 py-3">Fecha solicitud</th>
                                        <th class="px-4 py-3 text-right">Acciones</th>
                                    @elseif ($status === 'approved')
                                        <th class="px-4 py-3">Nombre vendedor</th>
                                        <th class="px-4 py-3">Negocio</th>
                                        <th class="px-4 py-3">Email</th>
                                        <th class="px-4 py-3">Productos activos</th>
                                        <th class="px-4 py-3">Ventas mes actual</th>
                                        <th class="px-4 py-3">Comision owed</th>
                                        <th class="px-4 py-3">Rating</th>
                                        <th class="px-4 py-3">Ultimos 30 dias</th>
                                        <th class="px-4 py-3 text-right">Acciones</th>
                                    @else
                                        <th class="px-4 py-3">Nombre</th>
                                        <th class="px-4 py-3">Negocio</th>
                                        <th class="px-4 py-3">Email</th>
                                        <th class="px-4 py-3">{{ $status === 'rejected' ? 'Motivo rechazo' : 'Motivo suspension' }}</th>
                                        <th class="px-4 py-3">{{ $status === 'rejected' ? 'Fecha rechazo' : 'Fecha suspension' }}</th>
                                        <th class="px-4 py-3 text-right">Acciones</th>
                                    @endif
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-atlantia-rose/15">
                                @forelse ($tabVendors as $vendor)
                                    @php
                                        $payload = $vendorsPayload[$vendor->id];
                                        $initials = collect(explode(' ', $payload['name']))->filter()->take(2)->map(fn ($part) => strtoupper(mb_substr($part, 0, 1)))->join('') ?: 'VD';
                                        $complianceTone = $payload['compliance'] >= 95 ? 'bg-emerald-500' : ($payload['compliance'] >= 85 ? 'bg-amber-500' : 'bg-rose-600');
                                        $commissionTone = $payload['commission_owed'] > 1000 ? 'text-rose-700' : ($payload['commission_owed'] > 500 ? 'text-amber-700' : 'text-atlantia-ink');
                                    @endphp
                                    <tr class="transition hover:bg-atlantia-cream" data-vendor-row data-status="{{ $status }}" data-search="{{ strtolower($payload['name'] . ' ' . $payload['email'] . ' ' . $payload['business'] . ' ' . $payload['code']) }}" data-rating="{{ $payload['rating'] }}" data-commission="{{ $payload['commission_owed'] }}" data-compliance="{{ $payload['compliance'] }}">
                                        @if ($status === 'pending')
                                            <td class="px-4 py-4">
                                                <div class="flex items-center gap-3">
                                                    <span class="grid h-10 w-10 place-items-center rounded-full bg-atlantia-blush font-black text-atlantia-wine">{{ $initials }}</span>
                                                    <div><p class="font-black text-atlantia-ink">{{ $payload['name'] }}</p><p class="text-xs text-atlantia-ink/50">DPI: Pendiente de verificar</p></div>
                                                </div>
                                            </td>
                                            <td class="px-4 py-4"><p class="font-bold text-atlantia-ink">{{ $payload['business'] }}</p><p class="text-xs text-atlantia-ink/50">{{ $payload['category'] }}</p></td>
                                            <td class="px-4 py-4 text-atlantia-ink/70">{{ $payload['email'] }}</td>
                                            <td class="px-4 py-4 text-atlantia-ink/70">{{ $payload['phone'] ?: 'No registrado' }}</td>
                                            <td class="px-4 py-4">
                                                <div class="w-32">
                                                    <p class="text-xs font-black text-atlantia-wine">{{ $payload['documents'] }}/{{ $payload['documents_total'] }} documentos</p>
                                                    <div class="mt-1 h-2 overflow-hidden rounded-full bg-atlantia-blush"><div class="h-full bg-atlantia-wine" style="width: {{ $payload['documents_total'] > 0 ? min(100, ($payload['documents'] / $payload['documents_total']) * 100) : 0 }}%"></div></div>
                                                </div>
                                            </td>
                                            <td class="px-4 py-4 text-atlantia-ink/65">{{ $payload['created_relative'] }}</td>
                                            <td class="px-4 py-4 text-right">
                                                <button type="button" class="rounded-md border border-atlantia-rose/30 px-3 py-2 text-xs font-black text-atlantia-wine hover:bg-atlantia-blush" data-open-vendor="{{ $vendor->id }}">Revisar</button>
                                            </td>
                                        @elseif ($status === 'approved')
                                            <td class="px-4 py-4">
                                                <div class="flex items-center gap-3">
                                                    <span class="grid h-10 w-10 place-items-center rounded-full bg-atlantia-blush font-black text-atlantia-wine">{{ $initials }}</span>
                                                    <div><p class="font-black text-atlantia-ink">{{ $payload['name'] }}</p><span class="{{ $statusClasses[$status] }} rounded-md px-2 py-0.5 text-xs font-black">Activo</span></div>
                                                </div>
                                            </td>
                                            <td class="px-4 py-4"><p class="font-bold text-atlantia-ink">{{ $payload['business'] }}</p><p class="text-xs text-atlantia-ink/50">{{ $payload['category'] }}</p></td>
                                            <td class="px-4 py-4"><button type="button" class="text-atlantia-wine hover:underline" data-copy="{{ $payload['email'] }}">{{ $payload['email'] }}</button></td>
                                            <td class="px-4 py-4"><a href="{{ route('admin.productos.index', ['vendor' => $vendor->id]) }}" class="font-black text-atlantia-wine">{{ $payload['active_products'] }}</a></td>
                                            <td class="px-4 py-4"><p class="font-black text-atlantia-ink">Q{{ number_format($payload['sales_30'], 2) }}</p><p class="text-xs text-emerald-700">+{{ min(18, $payload['orders_30'] * 2) }}%</p></td>
                                            <td class="px-4 py-4"><p class="font-black {{ $commissionTone }}">Q{{ number_format($payload['commission_owed'], 2) }}</p><p class="text-xs text-atlantia-ink/50">{{ number_format($payload['commission_percentage'], 1) }}% de ventas</p></td>
                                            <td class="px-4 py-4"><p class="font-black text-atlantia-ink">{{ str_repeat('★', (int) round($payload['rating'])) }}<span class="text-atlantia-ink/35">{{ str_repeat('☆', 5 - (int) round($payload['rating'])) }}</span> {{ number_format($payload['rating'], 1) }}</p><p class="text-xs text-atlantia-ink/50">({{ $payload['reviews_count'] }} reviews)</p></td>
                                            <td class="px-4 py-4"><p class="text-xs font-black text-atlantia-ink">{{ $payload['compliance'] }}% cumplimiento</p><div class="mt-1 h-2 w-28 overflow-hidden rounded-full bg-atlantia-blush"><div class="h-full {{ $complianceTone }}" style="width: {{ $payload['compliance'] }}%"></div></div></td>
                                            <td class="px-4 py-4 text-right"><button type="button" class="rounded-md border border-atlantia-rose/30 px-3 py-2 text-xs font-black text-atlantia-wine hover:bg-atlantia-blush" data-open-vendor="{{ $vendor->id }}">Ver detalle</button></td>
                                        @else
                                            <td class="px-4 py-4 font-black text-atlantia-ink">{{ $payload['name'] }}</td>
                                            <td class="px-4 py-4 text-atlantia-ink/70">{{ $payload['business'] }}</td>
                                            <td class="px-4 py-4 text-atlantia-ink/70">{{ $payload['email'] }}</td>
                                            <td class="px-4 py-4 text-atlantia-ink/70">{{ $status === 'suspended' ? $payload['suspension_reason'] : $payload['rejection_reason'] }}</td>
                                            <td class="px-4 py-4 text-atlantia-ink/65">{{ $status === 'suspended' ? $payload['suspended_at'] : $payload['created_at'] }}</td>
                                            <td class="px-4 py-4 text-right">
                                                @if ($status === 'suspended')
                                                    <form method="POST" action="{{ route('admin.vendedores.reactivate', $vendor) }}" onsubmit="return confirm('¿Reactivar a {{ $payload['name'] }}?')">
                                                        @csrf
                                                        @method('PATCH')
                                                        <button class="rounded-md bg-emerald-600 px-3 py-2 text-xs font-black text-white hover:bg-emerald-700">Reactivar</button>
                                                    </form>
                                                @else
                                                    <button type="button" class="rounded-md border border-atlantia-rose/30 px-3 py-2 text-xs font-black text-atlantia-wine hover:bg-atlantia-blush">Permitir reintentar</button>
                                                @endif
                                            </td>
                                        @endif
                                    </tr>
                                @empty
                                    <tr><td colspan="9" class="px-4 py-10 text-center text-atlantia-ink/60">No hay vendedores en esta pestaña.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    @endif
                </section>
            @endforeach

            <section class="mt-5 hidden" data-vendor-panel="dashboard">
                <div class="grid gap-4 lg:grid-cols-3">
                    <article class="rounded-xl border border-atlantia-rose/20 bg-atlantia-cream p-5"><p class="font-black text-atlantia-wine">Vendedores por estado</p><p class="mt-3 text-sm text-atlantia-ink/65">Activos {{ $countByStatus['approved'] ?? 0 }}, pendientes {{ $countByStatus['pending'] ?? 0 }}, rechazados {{ $countByStatus['rejected'] ?? 0 }}, suspendidos {{ $countByStatus['suspended'] ?? 0 }}.</p></article>
                    <article class="rounded-xl border border-atlantia-rose/20 bg-atlantia-cream p-5"><p class="font-black text-atlantia-wine">Top vendedores</p><div class="mt-3 space-y-2 text-sm">@foreach ($vendorsCollection->sortByDesc(fn ($vendor) => (float) ($vendor->sales_30_total ?? 0))->take(5) as $vendor)<p>{{ $loop->iteration }}. {{ $vendor->business_name }} - Q{{ number_format((float) ($vendor->sales_30_total ?? 0), 2) }}</p>@endforeach</div></article>
                    <article class="rounded-xl border border-atlantia-rose/20 bg-atlantia-cream p-5"><p class="font-black text-atlantia-wine">Alertas</p><p class="mt-3 text-sm text-atlantia-ink/65">Comision &gt; Q500: {{ $vendorsCollection->filter(fn ($vendor) => (float) ($vendor->pending_commission_total ?? 0) > 500)->count() }} vendedores.</p><p class="mt-1 text-sm text-atlantia-ink/65">Rating bajo: {{ $vendorsPayload->filter(fn ($vendor) => $vendor['rating'] > 0 && $vendor['rating'] < 2.5)->count() }} vendedores.</p></article>
                </div>
            </section>

            <div class="mt-4">{{ $vendors->links() }}</div>
        </div>

        <div class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-950/55 px-4 py-6 backdrop-blur-sm" data-vendor-drawer>
            <section class="max-h-[92vh] w-full max-w-5xl overflow-y-auto rounded-2xl border border-atlantia-rose/25 bg-white p-6 shadow-2xl">
                <div class="flex items-start justify-between gap-4 border-b border-atlantia-rose/15 pb-4">
                    <div>
                        <p class="text-xs font-black uppercase tracking-wide text-atlantia-rose" data-drawer-code></p>
                        <h2 class="mt-1 text-2xl font-black text-atlantia-ink" data-drawer-title></h2>
                        <p class="mt-1 text-sm text-atlantia-ink/60" data-drawer-subtitle></p>
                    </div>
                    <button type="button" class="rounded-md border border-atlantia-rose/30 px-3 py-2 text-sm font-black text-atlantia-wine hover:bg-atlantia-blush" data-close-drawer>Cerrar</button>
                </div>

                <div class="mt-5 grid gap-3 sm:grid-cols-4">
                    <div class="rounded-xl border border-atlantia-rose/20 bg-atlantia-cream p-4"><p class="text-xs font-bold text-atlantia-ink/55">Ventas</p><p class="mt-1 text-xl font-black text-atlantia-wine" data-drawer-sales></p></div>
                    <div class="rounded-xl border border-atlantia-rose/20 bg-atlantia-cream p-4"><p class="text-xs font-bold text-atlantia-ink/55">Productos</p><p class="mt-1 text-xl font-black text-atlantia-wine" data-drawer-products></p></div>
                    <div class="rounded-xl border border-atlantia-rose/20 bg-atlantia-cream p-4"><p class="text-xs font-bold text-atlantia-ink/55">Rating</p><p class="mt-1 text-xl font-black text-atlantia-wine" data-drawer-rating></p></div>
                    <div class="rounded-xl border border-atlantia-rose/20 bg-atlantia-cream p-4"><p class="text-xs font-bold text-atlantia-ink/55">Cumplimiento</p><p class="mt-1 text-xl font-black text-atlantia-wine" data-drawer-compliance></p></div>
                </div>

                <div class="mt-5 grid gap-4 lg:grid-cols-2">
                    <section class="rounded-xl border border-atlantia-rose/20 p-4"><h3 class="font-black text-atlantia-wine">Informacion personal</h3><div class="mt-3 space-y-2 text-sm text-atlantia-ink/70" data-drawer-personal></div></section>
                    <section class="rounded-xl border border-atlantia-rose/20 p-4"><h3 class="font-black text-atlantia-wine">Identidad y documentos</h3><div class="mt-3 space-y-2 text-sm text-atlantia-ink/70" data-drawer-identity></div></section>
                    <section class="rounded-xl border border-atlantia-rose/20 p-4"><h3 class="font-black text-atlantia-wine">Informacion del negocio</h3><div class="mt-3 space-y-2 text-sm text-atlantia-ink/70" data-drawer-business></div></section>
                    <section class="rounded-xl border border-atlantia-rose/20 p-4"><h3 class="font-black text-atlantia-wine">Informacion fiscal</h3><div class="mt-3 space-y-2 text-sm text-atlantia-ink/70" data-drawer-fiscal></div></section>
                    <section class="rounded-xl border border-atlantia-rose/20 p-4"><h3 class="font-black text-atlantia-wine">Informacion bancaria y pagos</h3><div class="mt-3 space-y-2 text-sm text-atlantia-ink/70" data-drawer-banking></div></section>
                    <section class="rounded-xl border border-atlantia-rose/20 p-4"><h3 class="font-black text-atlantia-wine">Archivos para verificar</h3><div class="mt-3 grid gap-2 text-sm text-atlantia-ink/70" data-drawer-documents></div></section>
                </div>

                <div class="mt-5 rounded-xl border border-atlantia-rose/20 p-4">
                    <h3 class="font-black text-atlantia-wine">Notas internas</h3>
                    <textarea class="mt-3 w-full rounded-md border border-atlantia-rose/35 px-3 py-2 text-sm" rows="3" placeholder="Ej: Documento valido, aprobado"></textarea>
                </div>

                <div class="mt-5 flex flex-wrap justify-end gap-3 border-t border-atlantia-rose/15 pt-4" data-drawer-actions></div>
                <div class="hidden" data-drawer-commissions></div>
            </section>
        </div>

        <div class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-950/55 px-4" data-stats-modal>
            <section class="w-full max-w-3xl rounded-2xl border border-atlantia-rose/20 bg-white p-6 shadow-2xl">
                <div class="flex items-start justify-between gap-4"><div><h2 class="text-2xl font-black text-atlantia-ink">Dashboard de vendedores</h2><p class="mt-1 text-sm text-atlantia-ink/60">Resumen ejecutivo de vendedores visibles.</p></div><button type="button" class="rounded-md border border-atlantia-rose/30 px-3 py-2 text-sm font-black text-atlantia-wine hover:bg-atlantia-blush" data-close-stats>Cerrar</button></div>
                <div class="mt-5 grid gap-4 md:grid-cols-2"><div class="rounded-xl bg-atlantia-cream p-4"><p class="font-black text-atlantia-wine">Comision acumulada</p><p class="mt-2 text-3xl font-black">Q{{ number_format($pendingTotal, 2) }}</p></div><div class="rounded-xl bg-atlantia-cream p-4"><p class="font-black text-atlantia-wine">Promedio rating</p><p class="mt-2 text-3xl font-black">{{ number_format($avgRating, 1) }}★</p></div></div>
            </section>
        </div>
    </section>

    <script nonce="{{ request()->attributes->get('csp_nonce') }}">
        (() => {
            const vendors = @json($vendorsPayload);
            const tabs = [...document.querySelectorAll('[data-vendor-tab]')];
            const panels = [...document.querySelectorAll('[data-vendor-panel]')];
            const search = document.querySelector('[data-vendor-search]');
            const rating = document.querySelector('[data-filter-rating]');
            const commission = document.querySelector('[data-filter-commission]');
            const compliance = document.querySelector('[data-filter-compliance]');
            let active = 'pending';
            const money = (value) => `Q${Number(value || 0).toLocaleString('es-GT', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
            const escapeHtml = (value) => String(value ?? '')
                .replaceAll('&', '&amp;')
                .replaceAll('<', '&lt;')
                .replaceAll('>', '&gt;')
                .replaceAll('"', '&quot;')
                .replaceAll("'", '&#039;');
            const field = (label, value) => `<p><span class="font-black text-atlantia-ink">${escapeHtml(label)}:</span> ${escapeHtml(value || 'No registrado')}</p>`;
            const formatAddress = (address = {}) => [address.calle, address.numero, address.apto, address.municipio, address.departamento, address.codigo_postal].filter(Boolean).join(', ') || 'No registrada';

            const applyFilters = () => {
                const term = (search?.value || '').toLowerCase().trim();
                document.querySelectorAll('[data-vendor-row]').forEach((row) => {
                    const rowRating = Number(row.dataset.rating || 0);
                    const rowCommission = Number(row.dataset.commission || 0);
                    const rowCompliance = Number(row.dataset.compliance || 0);
                    let show = row.dataset.status === active && (!term || row.dataset.search.includes(term));
                    if (rating?.value === 'lt3') show = show && rowRating > 0 && rowRating < 3;
                    else if (rating?.value) show = show && rowRating >= Number(rating.value);
                    if (commission?.value) show = show && rowCommission > Number(commission.value);
                    if (compliance?.value === 'lt85') show = show && rowCompliance < 85;
                    else if (compliance?.value) show = show && rowCompliance >= Number(compliance.value);
                    row.classList.toggle('hidden', !show);
                });
            };

            const activate = (status) => {
                active = status;
                tabs.forEach((tab) => {
                    const selected = tab.dataset.vendorTab === status;
                    tab.classList.toggle('bg-atlantia-wine', selected);
                    tab.classList.toggle('text-white', selected);
                    tab.classList.toggle('bg-white', !selected);
                    tab.classList.toggle('text-atlantia-wine', !selected);
                });
                panels.forEach((panel) => panel.classList.toggle('hidden', panel.dataset.vendorPanel !== status));
                applyFilters();
            };

            tabs.forEach((tab) => tab.addEventListener('click', () => activate(tab.dataset.vendorTab)));
            [search, rating, commission, compliance].forEach((control) => control?.addEventListener('input', applyFilters));
            [rating, commission, compliance].forEach((control) => control?.addEventListener('change', applyFilters));
            document.querySelector('[data-toggle-filters]')?.addEventListener('click', () => document.querySelector('[data-advanced-filters]')?.classList.toggle('hidden'));
            document.querySelector('[data-clear-filters]')?.addEventListener('click', () => { search.value = ''; rating.value = ''; commission.value = ''; compliance.value = ''; applyFilters(); });
            document.querySelectorAll('[data-copy]').forEach((button) => button.addEventListener('click', async () => { await navigator.clipboard?.writeText(button.dataset.copy); window.dispatchEvent(new CustomEvent('toast', { detail: { type: 'success', message: 'Email copiado.' } })); }));

            const drawer = document.querySelector('[data-vendor-drawer]');
            document.querySelectorAll('[data-open-vendor]').forEach((button) => {
                button.addEventListener('click', () => {
                    const vendor = vendors[button.dataset.openVendor];
                    drawer.classList.remove('hidden');
                    drawer.classList.add('flex');
                    drawer.querySelector('[data-drawer-code]').textContent = vendor.code;
                    drawer.querySelector('[data-drawer-title]').textContent = `${vendor.name} - ${vendor.business}`;
                    drawer.querySelector('[data-drawer-subtitle]').textContent = `${vendor.status_label} | ${vendor.municipio}`;
                    drawer.querySelector('[data-drawer-sales]').textContent = money(vendor.sales_30);
                    drawer.querySelector('[data-drawer-products]').textContent = vendor.active_products;
                    drawer.querySelector('[data-drawer-rating]').textContent = `${Number(vendor.rating).toFixed(1)}★`;
                    drawer.querySelector('[data-drawer-compliance]').textContent = `${vendor.compliance}%`;
                    drawer.querySelector('[data-drawer-personal]').innerHTML = `<p>Nombre: ${vendor.name}</p><p>Email: ${vendor.email || 'No registrado'}</p><p>Telefono: ${vendor.phone || 'No registrado'}</p><p>Direccion: ${vendor.address || 'Pendiente'}</p>`;
                    drawer.querySelector('[data-drawer-business]').innerHTML = `<p>Nombre: ${vendor.business}</p><p>Descripcion: ${vendor.description}</p><p>Categoria: Alimentos</p><p>Municipio: ${vendor.municipio}</p>`;
                    drawer.querySelector('[data-drawer-documents]').innerHTML = `<p>✓ DPI frente</p><p>✓ DPI reverso</p><p>${vendor.documents >= 3 ? '✓' : '✗'} Comprobante banco</p><p>${vendor.documents >= 4 ? '✓' : '✗'} Perfil fiscal/NIT</p>`;
                    drawer.querySelector('[data-drawer-commissions]').innerHTML = `<p>Total owed: ${money(vendor.commission_owed)}</p><p>Comision vigente: ${vendor.commission_percentage}%</p><p>Renta mensual: ${money(vendor.monthly_rent)}</p>`;
                    drawer.querySelector('[data-drawer-personal]').innerHTML = [
                        field('Nombre', vendor.name),
                        field('Email', vendor.email),
                        field('Telefono', vendor.phone),
                        field('Fecha de nacimiento', vendor.birthdate),
                        field('Genero', vendor.gender),
                        field('Direccion personal', formatAddress(vendor.personal_address)),
                    ].join('');
                    drawer.querySelector('[data-drawer-identity]').innerHTML = [
                        field('Tipo de documento', vendor.document_type),
                        field('Numero de documento', vendor.document_number),
                        field('Documentos recibidos', `${vendor.documents}/${vendor.documents_total}`),
                    ].join('');
                    drawer.querySelector('[data-drawer-business]').innerHTML = [
                        field('Nombre', vendor.business),
                        field('Descripcion', vendor.description_full),
                        field('Categoria', vendor.category),
                        field('Municipio', vendor.municipio),
                        field('Direccion comercial', formatAddress(vendor.business_address) || vendor.address),
                    ].join('');
                    drawer.querySelector('[data-drawer-fiscal]').innerHTML = [
                        field('Tiene NIT', vendor.has_nit ? 'Si' : 'No'),
                        field('NIT', vendor.fiscal?.nit),
                        field('Razon social', vendor.fiscal?.razon_social),
                        field('Regimen SAT', vendor.fiscal?.regimen_sat),
                        field('Direccion fiscal', vendor.fiscal?.direccion_fiscal),
                    ].join('');
                    drawer.querySelector('[data-drawer-banking]').innerHTML = [
                        field('Banco', vendor.banking?.banco),
                        field('Tipo de cuenta', vendor.banking?.tipo_cuenta),
                        field('Numero de cuenta', vendor.banking?.numero_cuenta),
                        field('Titular', vendor.banking?.titular),
                        field('Frecuencia de pago', vendor.payment_frequency),
                        field('Metodo de pago', vendor.preferred_payment_method),
                        field('Plan sugerido', vendor.plan_label),
                        field('Comision vigente', `${vendor.commission_percentage}%`),
                        field('Renta mensual', money(vendor.monthly_rent)),
                    ].join('');
                    drawer.querySelector('[data-drawer-documents]').innerHTML = (vendor.document_links || []).length
                        ? vendor.document_links.map((document) => `<a href="${escapeHtml(document.url)}" target="_blank" rel="noopener" class="flex items-center justify-between rounded-lg border border-atlantia-rose/20 px-3 py-2 font-bold text-atlantia-wine transition hover:bg-atlantia-blush"><span>${escapeHtml(document.label)}</span><span class="text-xs">Ver archivo</span></a>`).join('')
                        : '<p>No hay archivos cargados.</p>';
                    drawer.querySelector('[data-drawer-actions]').innerHTML = vendor.status === 'pending'
                        ? `<form method="POST" action="${vendor.approve_url}" onsubmit="return confirm('¿Aprobar a ${vendor.name} con ${money(vendor.monthly_rent)} mensual y ${vendor.commission_percentage}% de comision?')"><input type="hidden" name="_token" value="{{ csrf_token() }}"><input type="hidden" name="_method" value="PATCH"><input type="hidden" name="commission_percentage" value="${vendor.commission_percentage}"><input type="hidden" name="monthly_rent" value="${vendor.monthly_rent}"><button class="rounded-md bg-atlantia-wine px-4 py-2 text-sm font-black text-white">Aprobar plan</button></form><button class="rounded-md border border-atlantia-rose/30 px-4 py-2 text-sm font-black text-atlantia-wine">Rechazar</button>`
                        : vendor.status === 'suspended'
                            ? `<form method="POST" action="${vendor.reactivate_url}" onsubmit="return confirm('¿Reactivar a ${vendor.name}?')"><input type="hidden" name="_token" value="{{ csrf_token() }}"><input type="hidden" name="_method" value="PATCH"><button class="rounded-md bg-emerald-600 px-4 py-2 text-sm font-black text-white">Reactivar</button></form>`
                            : `<form method="POST" action="${vendor.suspend_url}" onsubmit="return confirm('¿Suspender a ${vendor.name}?')"><input type="hidden" name="_token" value="{{ csrf_token() }}"><input type="hidden" name="_method" value="PATCH"><input type="hidden" name="motivo_suspension" value="Suspension administrativa desde panel."><input type="hidden" name="tipo_suspension" value="operativa"><button class="rounded-md bg-amber-600 px-4 py-2 text-sm font-black text-white">Suspender</button></form><a href="${vendor.show_url}" class="rounded-md border border-atlantia-rose/30 px-4 py-2 text-sm font-black text-atlantia-wine">Abrir ficha</a>`;
                });
            });
            document.querySelector('[data-close-drawer]')?.addEventListener('click', () => { drawer.classList.add('hidden'); drawer.classList.remove('flex'); });
            drawer?.addEventListener('click', (event) => { if (event.target === drawer) { drawer.classList.add('hidden'); drawer.classList.remove('flex'); } });

            document.querySelector('[data-open-stats]')?.addEventListener('click', () => { document.querySelector('[data-stats-modal]')?.classList.remove('hidden'); document.querySelector('[data-stats-modal]')?.classList.add('flex'); });
            document.querySelector('[data-close-stats]')?.addEventListener('click', () => { document.querySelector('[data-stats-modal]')?.classList.add('hidden'); document.querySelector('[data-stats-modal]')?.classList.remove('flex'); });
            activate(active);
        })();
    </script>
@endsection
