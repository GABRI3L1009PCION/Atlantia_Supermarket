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
            $documentLinks = collect($vendor->documents ?? [])->filter()->map(function ($path, $key) use ($documentLabels, $vendor): array {
                $extension = strtolower(pathinfo((string) $path, PATHINFO_EXTENSION));

                return [
                    'label' => $documentLabels[$key] ?? str($key)->replace('_', ' ')->title()->toString(),
                    'url' => route('admin.vendedores.documents.show', [$vendor, $key]),
                    'ver_url' => route('admin.vendedores.documents.show', [$vendor, $key, 'ver' => 1]),
                    'tipo' => $extension === 'pdf' ? 'pdf' : 'imagen',
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
                        'colonia' => $personalAddress['colonia'] ?? null,
                        'municipio' => $personalAddress['municipio'] ?? $vendor->municipio,
                        'departamento' => $personalAddress['departamento'] ?? null,
                        'codigo_postal' => $personalAddress['codigo_postal'] ?? null,
                    ],
                    'document_type' => strtoupper((string) ($vendor->document_type ?? 'DPI')),
                    'document_number' => $vendor->document_number ?: 'Pendiente',
                    'category' => $vendor->categoriaTexto() ?: 'Sin categoría',
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
                    'plan_label' => (function () use ($vendor): string {
                        $planesServicio = app(\App\Services\Monetizacion\PlanesVendedorService::class);
                        $vigente = $planesServicio->planActual($vendor);
                        $elegido = config('monetizacion.planes.'.$vendor->plan.'.nombre', 'Personalizado');

                        /* Si eligio un plan con mensualidad pero no lo ha pagado, opera con el plan Inicial. */
                        return $vigente === $vendor->plan
                            ? $elegido
                            : config('monetizacion.planes.'.$vigente.'.nombre').' (eligió '.$elegido.', mensualidad sin pagar)';
                    })(),
                    'show_url' => route('admin.vendedores.show', $vendor),
                    'approve_url' => route('admin.vendedores.approve', $vendor),
                    'suspend_url' => route('admin.vendedores.suspend', $vendor),
                    'reactivate_url' => route('admin.vendedores.reactivate', $vendor),
                    'reject_url' => route('admin.vendedores.reject', $vendor),
                    'resend_url' => route('admin.vendedores.reenviar-acceso', $vendor),
                    'reset_2fa_url' => route('admin.vendedores.reiniciar-2fa', $vendor),
                    'tiene_contrasena' => $vendor->user?->email_verified_at !== null,
                    'tiene_2fa' => $vendor->user?->two_factor_confirmed_at !== null,
                ],
            ];
        });
    @endphp

    <section class="vendors-light-surface mx-auto max-w-7xl space-y-4 pb-10 text-atlantia-ink">
            {{-- Encabezado compacto --}}
            <header class="flex flex-wrap items-end justify-between gap-3">
                <div>
                    <h1 class="text-3xl font-black tracking-tight text-atlantia-ink">Vendedores</h1>
                    <p class="mt-1 text-sm text-atlantia-ink/60">Solicitudes, aprobaciones, comisiones y desempeño de las tiendas.</p>
                </div>
                <div class="flex gap-2">
                    <a href="{{ route('admin.vendedores.report.pdf') }}" class="rounded-xl border border-atlantia-rose/25 bg-white px-4 py-2.5 text-sm font-black text-atlantia-wine transition hover:bg-atlantia-blush">Exportar reporte</a>
                    <button type="button" class="rounded-xl bg-atlantia-wine px-4 py-2.5 text-sm font-black text-white transition hover:bg-atlantia-wine-700" data-open-stats>Estadísticas</button>
                </div>
            </header>

            {{-- Métricas en una franja --}}
            <div class="grid grid-cols-2 gap-3 lg:grid-cols-4">
                @foreach ([
                    ['Vendedores', number_format($vendors->total()), $approvedCount.' activos'],
                    ['Ventas 30 días', 'Q '.number_format($salesTotal, 2), 'de las tiendas visibles'],
                    ['Comisión pendiente', 'Q '.number_format($pendingTotal, 2), 'por cobrar o facturada'],
                    ['Calificación', number_format($avgRating, 1).' ★', 'promedio de reseñas'],
                ] as [$etiqueta, $valor, $nota])
                    <article class="rounded-2xl border border-atlantia-rose/15 bg-white px-4 py-3 shadow-[0_8px_20px_rgba(59,6,12,0.04)]">
                        <p class="text-[11px] font-black uppercase tracking-wide text-atlantia-ink/50">{{ $etiqueta }}</p>
                        <p class="mt-0.5 text-2xl font-black text-atlantia-ink">{{ $valor }}</p>
                        <p class="text-xs text-atlantia-ink/50">{{ $nota }}</p>
                    </article>
                @endforeach
            </div>

            {{-- Pestañas + búsqueda en una sola barra --}}
            <div class="rounded-2xl border border-atlantia-rose/15 bg-white p-3">
                <div class="flex flex-col gap-3 lg:flex-row lg:items-center">
                    <div class="flex gap-1.5 overflow-x-auto" role="tablist" aria-label="Estados de vendedores">
                        @foreach ($statusTabs as $status => $label)
                            <button type="button" class="{{ $loop->first ? 'bg-atlantia-wine text-white' : 'bg-white text-atlantia-wine' }} shrink-0 rounded-lg border border-atlantia-rose/25 px-3 py-2 text-sm font-black transition hover:bg-atlantia-blush hover:text-atlantia-wine" data-vendor-tab="{{ $status }}">
                                {{ $label }}
                                <span class="ml-1 rounded-full bg-black/5 px-1.5 py-0.5 text-xs">{{ $countByStatus[$status] ?? 0 }}</span>
                            </button>
                        @endforeach
                        <button type="button" class="bg-white text-atlantia-wine shrink-0 rounded-lg border border-atlantia-rose/25 px-3 py-2 text-sm font-black transition hover:bg-atlantia-blush" data-vendor-tab="dashboard">Resumen</button>
                    </div>
                    <div class="flex flex-1 gap-2">
                        <div class="relative flex-1">
                            <span class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-atlantia-ink/40">
                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M11 19a8 8 0 1 0 0-16 8 8 0 0 0 0 16ZM20.5 20.5l-3.8-3.8" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
                            </span>
                            <input type="search" class="h-10 w-full rounded-lg border border-atlantia-rose/30 bg-white pl-9 pr-3 text-sm outline-none transition focus:border-atlantia-wine focus:ring-2 focus:ring-atlantia-rose/25" placeholder="Buscar por nombre, correo, negocio o código" data-vendor-search>
                        </div>
                        <button type="button" class="h-10 shrink-0 rounded-lg border border-atlantia-rose/30 px-3 text-sm font-black text-atlantia-wine transition hover:bg-atlantia-blush" data-toggle-filters>Filtros</button>
                    </div>
                </div>
                <div class="mt-3 hidden grid gap-2 rounded-xl bg-atlantia-cream p-3 md:grid-cols-4" data-advanced-filters>
                    <select class="rounded-lg border border-atlantia-rose/30 px-3 py-2 text-sm" data-filter-rating>
                        <option value="">Calificación</option>
                        <option value="4">4.0 o más</option>
                        <option value="4.5">4.5 o más</option>
                        <option value="3">3.0 o más</option>
                        <option value="lt3">Menos de 3.0</option>
                    </select>
                    <select class="rounded-lg border border-atlantia-rose/30 px-3 py-2 text-sm" data-filter-commission>
                        <option value="">Comisión pendiente</option>
                        <option value="100">Más de Q100</option>
                        <option value="500">Más de Q500</option>
                        <option value="1000">Más de Q1,000</option>
                    </select>
                    <select class="rounded-lg border border-atlantia-rose/30 px-3 py-2 text-sm" data-filter-compliance>
                        <option value="">Cumplimiento</option>
                        <option value="95">95 % o más</option>
                        <option value="85">85 % o más</option>
                        <option value="lt85">Menos de 85 %</option>
                    </select>
                    <button type="button" class="rounded-lg bg-atlantia-wine px-4 py-2 text-sm font-black text-white" data-clear-filters>Limpiar</button>
                </div>
            </div>

            {{-- Lista compacta: una fila por tienda --}}
            @foreach ($statusTabs as $status => $label)
                @php
                    $tabVendors = $vendorsCollection->where('status', $status);
                    $tabVendors = match ($status) {
                        'pending' => $tabVendors->sortBy('created_at'),
                        'approved' => $tabVendors->sortByDesc(fn ($vendor) => (float) ($vendor->sales_30_total ?? 0)),
                        default => $tabVendors->sortByDesc('updated_at'),
                    };
                @endphp
                <section class="{{ $loop->first ? '' : 'hidden' }} overflow-hidden rounded-2xl border border-atlantia-rose/15 bg-white" data-vendor-panel="{{ $status }}">
                    <div class="divide-y divide-atlantia-rose/10">
                        @forelse ($tabVendors as $vendor)
                            @php
                                $payload = $vendorsPayload[$vendor->id];
                                $initials = collect(explode(' ', $payload['business']))->filter()->take(2)->map(fn ($part) => strtoupper(mb_substr($part, 0, 1)))->join('') ?: 'VD';
                                $progress = $payload['documents_total'] > 0 ? min(100, ($payload['documents'] / $payload['documents_total']) * 100) : 0;
                            @endphp
                            <div class="grid items-center gap-x-4 gap-y-2 px-4 py-3 transition hover:bg-atlantia-cream/60 md:grid-cols-[minmax(0,1.4fr)_minmax(0,1.1fr)_minmax(0,0.9fr)_auto]" data-vendor-row data-status="{{ $status }}" data-search="{{ strtolower($payload['name'].' '.$payload['email'].' '.$payload['business'].' '.$payload['code']) }}" data-rating="{{ $payload['rating'] }}" data-commission="{{ $payload['commission_owed'] }}" data-compliance="{{ $payload['compliance'] }}">
                                {{-- Tienda --}}
                                <div class="flex min-w-0 items-center gap-3">
                                    <span class="grid h-10 w-10 shrink-0 place-items-center rounded-xl bg-atlantia-blush text-sm font-black text-atlantia-wine">{{ $initials }}</span>
                                    <div class="min-w-0">
                                        <p class="flex items-center gap-2 truncate text-sm font-black text-atlantia-ink">
                                            {{ $payload['business'] }}
                                            <span class="{{ $statusClasses[$status] }} shrink-0 rounded-md px-1.5 py-0.5 text-[10px] font-black">{{ $payload['status_label'] }}</span>
                                        </p>
                                        <p class="truncate text-xs text-atlantia-ink/55">{{ $payload['name'] }} · {{ str_replace('_', ' ', $payload['category']) }}</p>
                                    </div>
                                </div>

                                {{-- Contacto --}}
                                <div class="min-w-0 text-xs text-atlantia-ink/65">
                                    <p class="truncate">{{ $payload['email'] }}</p>
                                    <p class="truncate">{{ $payload['phone'] ?: 'Sin teléfono' }} · {{ $payload['document_type'] }} {{ $payload['document_number'] }}</p>
                                </div>

                                {{-- Dato clave según la pestaña --}}
                                <div class="min-w-0 text-xs">
                                    @if ($status === 'pending')
                                        <p class="font-bold text-atlantia-ink/70">Documentos <span class="font-black text-atlantia-wine">{{ $payload['documents'] }}/{{ $payload['documents_total'] }}</span> · {{ $payload['created_relative'] }}</p>
                                        <div class="mt-1 h-1.5 w-full max-w-[10rem] overflow-hidden rounded-full bg-atlantia-blush"><div class="h-full rounded-full bg-atlantia-wine" style="width: {{ $progress }}%"></div></div>
                                    @elseif ($status === 'approved')
                                        <p class="text-atlantia-ink/60"><strong class="text-sm text-atlantia-ink">Q {{ number_format($payload['sales_30'], 2) }}</strong> en 30 días</p>
                                        <p class="text-atlantia-ink/55">{{ $payload['active_products'] }} productos · {{ number_format($payload['rating'], 1) }} ★ · {{ $payload['compliance'] }} % cumplimiento</p>
                                    @else
                                        <p class="line-clamp-2 text-atlantia-ink/65">{{ $status === 'suspended' ? $payload['suspension_reason'] : $payload['rejection_reason'] }}</p>
                                    @endif
                                </div>

                                {{-- Acciones --}}
                                <div class="flex shrink-0 gap-2 md:justify-end">
                                    <a href="{{ $payload['show_url'] }}" class="rounded-lg border border-atlantia-rose/30 px-3 py-1.5 text-xs font-black text-atlantia-wine transition hover:bg-atlantia-blush">Perfil</a>
                                    @if ($status === 'suspended')
                                        <form method="POST" action="{{ route('admin.vendedores.reactivate', $vendor) }}" data-confirm="¿Reactivar a {{ $payload['business'] }}?">
                                            @csrf
                                            @method('PATCH')
                                            <button class="rounded-lg bg-emerald-600 px-3 py-1.5 text-xs font-black text-white hover:bg-emerald-700">Reactivar</button>
                                        </form>
                                    @else
                                        <button type="button" class="rounded-lg bg-atlantia-wine px-3 py-1.5 text-xs font-black text-white transition hover:bg-atlantia-wine-700" data-open-vendor="{{ $vendor->id }}">{{ $status === 'pending' ? 'Revisar' : 'Detalle' }}</button>
                                    @endif
                                </div>
                            </div>
                        @empty
                            <p class="px-4 py-10 text-center text-sm text-atlantia-ink/55">No hay vendedores en esta pestaña.</p>
                        @endforelse
                    </div>
                </section>
            @endforeach

            <section class="hidden" data-vendor-panel="dashboard">
                <div class="grid gap-4 lg:grid-cols-3">
                    <article class="rounded-xl border border-atlantia-rose/20 bg-atlantia-cream p-5"><p class="font-black text-atlantia-wine">Vendedores por estado</p><p class="mt-3 text-sm text-atlantia-ink/65">Activos {{ $countByStatus['approved'] ?? 0 }}, pendientes {{ $countByStatus['pending'] ?? 0 }}, rechazados {{ $countByStatus['rejected'] ?? 0 }}, suspendidos {{ $countByStatus['suspended'] ?? 0 }}.</p></article>
                    <article class="rounded-xl border border-atlantia-rose/20 bg-atlantia-cream p-5"><p class="font-black text-atlantia-wine">Top vendedores</p><div class="mt-3 space-y-2 text-sm">@foreach ($vendorsCollection->sortByDesc(fn ($vendor) => (float) ($vendor->sales_30_total ?? 0))->take(5) as $vendor)<p>{{ $loop->iteration }}. {{ $vendor->business_name }} - Q{{ number_format((float) ($vendor->sales_30_total ?? 0), 2) }}</p>@endforeach</div></article>
                    <article class="rounded-xl border border-atlantia-rose/20 bg-atlantia-cream p-5"><p class="font-black text-atlantia-wine">Alertas</p><p class="mt-3 text-sm text-atlantia-ink/65">Comision &gt; Q500: {{ $vendorsCollection->filter(fn ($vendor) => (float) ($vendor->pending_commission_total ?? 0) > 500)->count() }} vendedores.</p><p class="mt-1 text-sm text-atlantia-ink/65">Rating bajo: {{ $vendorsPayload->filter(fn ($vendor) => $vendor['rating'] > 0 && $vendor['rating'] < 2.5)->count() }} vendedores.</p></article>
                </div>
            </section>

            <div>{{ $vendors->links() }}</div>

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
                    <section class="rounded-xl border border-atlantia-rose/20 p-4"><h3 class="font-black text-atlantia-wine">Información personal</h3><div class="mt-3 space-y-2 text-sm text-atlantia-ink/70" data-drawer-personal></div></section>
                    <section class="rounded-xl border border-atlantia-rose/20 p-4"><h3 class="font-black text-atlantia-wine">Identidad y documentos</h3><div class="mt-3 space-y-2 text-sm text-atlantia-ink/70" data-drawer-identity></div></section>
                    <section class="rounded-xl border border-atlantia-rose/20 p-4"><h3 class="font-black text-atlantia-wine">Información del negocio</h3><div class="mt-3 space-y-2 text-sm text-atlantia-ink/70" data-drawer-business></div></section>
                    <section class="rounded-xl border border-atlantia-rose/20 p-4"><h3 class="font-black text-atlantia-wine">Información fiscal</h3><div class="mt-3 space-y-2 text-sm text-atlantia-ink/70" data-drawer-fiscal></div></section>
                    <section class="rounded-xl border border-atlantia-rose/20 p-4"><h3 class="font-black text-atlantia-wine">Información bancaria y pagos</h3><div class="mt-3 space-y-2 text-sm text-atlantia-ink/70" data-drawer-banking></div></section>
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
                <div class="flex items-start justify-between gap-4"><div><h2 class="text-2xl font-black text-atlantia-ink">Resumen de vendedores</h2><p class="mt-1 text-sm text-atlantia-ink/60">Resumen ejecutivo de vendedores visibles.</p></div><button type="button" class="rounded-md border border-atlantia-rose/30 px-3 py-2 text-sm font-black text-atlantia-wine hover:bg-atlantia-blush" data-close-stats>Cerrar</button></div>
                <div class="mt-5 grid gap-4 md:grid-cols-2"><div class="rounded-xl bg-atlantia-cream p-4"><p class="font-black text-atlantia-wine">Comisión acumulada</p><p class="mt-2 text-3xl font-black">Q{{ number_format($pendingTotal, 2) }}</p></div><div class="rounded-xl bg-atlantia-cream p-4"><p class="font-black text-atlantia-wine">Calificación promedio</p><p class="mt-2 text-3xl font-black">{{ number_format($avgRating, 1) }}★</p></div></div>
            </section>
        </div>

        {{-- Visor de documentos del vendedor --}}
        <div class="fixed inset-0 z-[90] hidden flex-col bg-black/85 backdrop-blur-sm" data-visor role="dialog" aria-modal="true" aria-label="Visor de documentos">
            <div class="flex flex-wrap items-center justify-between gap-3 border-b border-white/10 px-4 py-3 text-white">
                <div class="min-w-0">
                    <p class="truncate text-base font-black" data-visor-titulo></p>
                    <p class="text-xs text-white/55"><span data-visor-posicion></span> · Usa ← → para cambiar de documento y Esc para cerrar</p>
                </div>
                <div class="flex flex-wrap items-center gap-1.5">
                    <button type="button" data-visor-girar data-visor-solo-imagen class="rounded-lg bg-white/10 px-3 py-2 text-sm font-bold hover:bg-white/20" title="Girar 90°">↻ Girar</button>
                    <button type="button" data-visor-zoom data-visor-solo-imagen class="rounded-lg bg-white/10 px-3 py-2 text-sm font-bold hover:bg-white/20" title="Acercar o alejar">🔍 Zoom</button>
                    <a href="#" target="_blank" rel="noopener" data-visor-nueva class="rounded-lg bg-white/10 px-3 py-2 text-sm font-bold hover:bg-white/20" title="Abrir en otra pestaña">↗</a>
                    <a href="#" data-visor-descargar class="rounded-lg bg-white/10 px-3 py-2 text-sm font-bold hover:bg-white/20">Descargar</a>
                    <button type="button" data-visor-cerrar class="rounded-lg bg-white px-3 py-2 text-sm font-black text-atlantia-wine hover:bg-atlantia-blush">Cerrar ✕</button>
                </div>
            </div>
            <div class="relative flex min-h-0 flex-1 items-center gap-3 p-4">
                <button type="button" data-visor-anterior class="grid h-11 w-11 shrink-0 place-items-center rounded-full bg-white/10 text-2xl font-black text-white hover:bg-white/20 disabled:opacity-30" aria-label="Documento anterior">‹</button>
                <div class="flex h-full min-w-0 flex-1 items-center justify-center overflow-auto" data-visor-cuerpo></div>
                <button type="button" data-visor-siguiente class="grid h-11 w-11 shrink-0 place-items-center rounded-full bg-white/10 text-2xl font-black text-white hover:bg-white/20 disabled:opacity-30" aria-label="Documento siguiente">›</button>
            </div>
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
            let documentosActuales = [];
            const money = (value) => `Q${Number(value || 0).toLocaleString('es-GT', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
            const numeric = (value) => Number.isFinite(Number(value)) ? Number(value) : 0;
            const escapeHtml = (value) => String(value ?? '')
                .replaceAll('&', '&amp;')
                .replaceAll('<', '&lt;')
                .replaceAll('>', '&gt;')
                .replaceAll('"', '&quot;')
                .replaceAll("'", '&#039;');
            const field = (label, value) => `<p><span class="font-black text-atlantia-ink">${escapeHtml(label)}:</span> ${escapeHtml(value || 'No registrado')}</p>`;
            const formatAddress = (address = {}) => [address.calle, address.numero, address.apto, address.colonia, address.municipio, address.departamento, address.codigo_postal].filter(Boolean).join(', ') || 'No registrada';

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
                    documentosActuales = vendor.document_links || [];
                    drawer.querySelector('[data-drawer-documents]').innerHTML = documentosActuales.length
                        ? documentosActuales.map((document, indice) => `<button type="button" data-ver-documento="${indice}" class="flex w-full items-center justify-between gap-3 rounded-lg border border-atlantia-rose/20 px-3 py-2 text-left font-bold text-atlantia-wine transition hover:bg-atlantia-blush"><span class="flex items-center gap-2"><span class="rounded bg-atlantia-blush px-1.5 py-0.5 text-[10px] font-black uppercase">${document.tipo === 'pdf' ? 'PDF' : 'IMG'}</span>${escapeHtml(document.label)}</span><span class="text-xs">Ver aquí →</span></button>`).join('')
                        : '<p>No hay archivos cargados.</p>';
                    const vendorName = String(vendor.name ?? 'este vendedor');
                    const approveUrl = escapeHtml(vendor.approve_url);
                    const reactivateUrl = escapeHtml(vendor.reactivate_url);
                    const suspendUrl = escapeHtml(vendor.suspend_url);
                    const showUrl = escapeHtml(vendor.show_url);
                    const commissionPercentage = numeric(vendor.commission_percentage);
                    const monthlyRent = numeric(vendor.monthly_rent);
                    const token = '<input type="hidden" name="_token" value="{{ csrf_token() }}">';
                    const rejectUrl = escapeHtml(vendor.reject_url);
                    const resendUrl = escapeHtml(vendor.resend_url);
                    const reset2faUrl = escapeHtml(vendor.reset_2fa_url);
                    /* Estado del acceso del vendedor aprobado y acciones de soporte. */
                    const accesoAprobado = `<div class="mt-3 w-full rounded-lg border border-atlantia-rose/20 bg-atlantia-cream/50 p-3 text-xs">
                        <p class="font-black text-atlantia-ink">Acceso del vendedor</p>
                        <p class="mt-1 text-atlantia-ink/70">${vendor.tiene_contrasena ? '✓ Ya creó su contraseña' : '○ Aún no crea su contraseña'} · ${vendor.tiene_2fa ? '✓ Doble factor activo' : '○ Doble factor pendiente'}</p>
                        <div class="mt-2 flex flex-wrap gap-2">
                            <form method="POST" action="${resendUrl}" data-confirm="${escapeHtml(`Enviar a ${vendorName} un enlace nuevo para crear su contraseña?`)}">${token}<button class="rounded-md border border-atlantia-rose/30 bg-white px-3 py-1.5 font-black text-atlantia-wine">Reenviar acceso</button></form>
                            ${vendor.tiene_2fa ? `<form method="POST" action="${reset2faUrl}" data-confirm="${escapeHtml(`Reiniciar el doble factor de ${vendorName}? Deberá escanear un QR nuevo al entrar. Hazlo solo si confirmaste su identidad.`)}">${token}<input type="hidden" name="_method" value="DELETE"><button class="rounded-md border border-amber-300 bg-white px-3 py-1.5 font-black text-amber-800">Reiniciar doble factor</button></form>` : ''}
                        </div>
                    </div>`;
                    drawer.querySelector('[data-drawer-actions]').innerHTML = vendor.status === 'pending'
                        ? `<form method="POST" action="${approveUrl}" data-confirm="${escapeHtml(`Aprobar a ${vendorName} con ${money(monthlyRent)} mensual y ${commissionPercentage}% de comision? Se le enviará un correo para crear su contraseña.`)}">${token}<input type="hidden" name="_method" value="PATCH"><input type="hidden" name="commission_percentage" value="${commissionPercentage}"><input type="hidden" name="monthly_rent" value="${monthlyRent}"><button class="rounded-md bg-atlantia-wine px-4 py-2 text-sm font-black text-white">Aprobar plan</button></form>
                           <details class="w-full" data-rechazo>
                               <summary class="inline-flex cursor-pointer list-none rounded-md border border-atlantia-rose/30 px-4 py-2 text-sm font-black text-atlantia-wine hover:bg-atlantia-blush">Rechazar…</summary>
                               <form method="POST" action="${rejectUrl}" class="mt-2 space-y-2" data-confirm="${escapeHtml(`Rechazar la solicitud de ${vendorName}? Se le enviará el motivo por correo.`)}">${token}<input type="hidden" name="_method" value="PATCH">
                                   <textarea name="motivo" required minlength="10" maxlength="500" rows="3" placeholder="Motivo que verá el solicitante. Ej.: La foto del DPI no se lee; envía una más clara." class="w-full rounded-lg border border-atlantia-rose/30 px-3 py-2 text-sm"></textarea>
                                   <button class="rounded-md bg-red-700 px-4 py-2 text-sm font-black text-white">Rechazar y avisar</button>
                               </form>
                           </details>`
                        : vendor.status === 'suspended'
                            ? `<form method="POST" action="${reactivateUrl}" data-confirm="${escapeHtml(`Reactivar a ${vendorName}?`)}"><input type="hidden" name="_token" value="{{ csrf_token() }}"><input type="hidden" name="_method" value="PATCH"><button class="rounded-md bg-emerald-600 px-4 py-2 text-sm font-black text-white">Reactivar</button></form>`
                            : `<form method="POST" action="${suspendUrl}" data-confirm="${escapeHtml(`Suspender a ${vendorName}?`)}"><input type="hidden" name="_token" value="{{ csrf_token() }}"><input type="hidden" name="_method" value="PATCH"><input type="hidden" name="motivo_suspension" value="Suspension administrativa desde panel."><input type="hidden" name="tipo_suspension" value="operativa"><button class="rounded-md bg-amber-600 px-4 py-2 text-sm font-black text-white">Suspender</button></form><a href="${showUrl}" class="rounded-md border border-atlantia-rose/30 px-4 py-2 text-sm font-black text-atlantia-wine">Abrir ficha</a>${vendor.status === 'approved' ? accesoAprobado : ''}`;
                });
            });
            /* La confirmacion de data-confirm la maneja resources/js/acciones-seguras.js. */
            document.querySelector('[data-close-drawer]')?.addEventListener('click', () => { drawer.classList.add('hidden'); drawer.classList.remove('flex'); });
            drawer?.addEventListener('click', (event) => { if (event.target === drawer) { drawer.classList.add('hidden'); drawer.classList.remove('flex'); } });

            /* Visor de documentos: imágenes y PDF dentro del panel, sin descargar ni salir. */
            const visor = document.querySelector('[data-visor]');
            const visorCuerpo = visor?.querySelector('[data-visor-cuerpo]');
            let indiceVisor = 0;
            let giro = 0;
            let ampliado = false;

            const pintarVisor = () => {
                const doc = documentosActuales[indiceVisor];
                if (!visor || !doc) return;
                giro = 0;
                ampliado = false;
                visor.querySelector('[data-visor-titulo]').textContent = doc.label;
                visor.querySelector('[data-visor-posicion]').textContent = `${indiceVisor + 1} de ${documentosActuales.length}`;
                visor.querySelector('[data-visor-descargar]').href = doc.url;
                visor.querySelector('[data-visor-nueva]').href = doc.ver_url;
                visor.querySelectorAll('[data-visor-solo-imagen]').forEach((b) => b.classList.toggle('hidden', doc.tipo === 'pdf'));
                visor.querySelector('[data-visor-anterior]').disabled = documentosActuales.length < 2;
                visor.querySelector('[data-visor-siguiente]').disabled = documentosActuales.length < 2;
                visorCuerpo.replaceChildren();

                if (doc.tipo === 'pdf') {
                    const marco = document.createElement('iframe');
                    marco.src = doc.ver_url;
                    marco.title = doc.label;
                    marco.className = 'h-full w-full rounded-lg bg-white';
                    visorCuerpo.append(marco);
                    return;
                }

                const imagen = document.createElement('img');
                imagen.src = doc.ver_url;
                imagen.alt = doc.label;
                imagen.dataset.visorImagen = '';
                imagen.className = 'max-h-full max-w-full cursor-zoom-in select-none rounded-lg object-contain shadow-2xl transition-transform duration-200';
                imagen.addEventListener('click', () => { ampliado = !ampliado; aplicarTransformacion(); });
                imagen.addEventListener('error', () => {
                    visorCuerpo.replaceChildren(Object.assign(document.createElement('p'), { className: 'text-sm font-bold text-white/80', textContent: 'No se pudo cargar el archivo. Usa «Descargar» para revisarlo.' }));
                });
                visorCuerpo.append(imagen);
            };

            const aplicarTransformacion = () => {
                const imagen = visorCuerpo?.querySelector('[data-visor-imagen]');
                if (!imagen) return;
                imagen.style.transform = `rotate(${giro}deg) scale(${ampliado ? 1.8 : 1})`;
                imagen.classList.toggle('cursor-zoom-in', !ampliado);
                imagen.classList.toggle('cursor-zoom-out', ampliado);
            };

            const abrirVisor = (indice) => {
                indiceVisor = indice;
                pintarVisor();
                visor.classList.remove('hidden');
                visor.classList.add('flex');
            };
            const cerrarVisor = () => {
                visor?.classList.add('hidden');
                visor?.classList.remove('flex');
                visorCuerpo?.replaceChildren();
            };
            const mover = (paso) => {
                if (documentosActuales.length < 2) return;
                indiceVisor = (indiceVisor + paso + documentosActuales.length) % documentosActuales.length;
                pintarVisor();
            };

            drawer?.addEventListener('click', (event) => {
                const boton = event.target.closest('[data-ver-documento]');
                if (boton) abrirVisor(Number(boton.dataset.verDocumento));
            });
            visor?.querySelector('[data-visor-cerrar]')?.addEventListener('click', cerrarVisor);
            visor?.addEventListener('click', (event) => { if (event.target === visor || event.target === visorCuerpo) cerrarVisor(); });
            visor?.querySelector('[data-visor-anterior]')?.addEventListener('click', () => mover(-1));
            visor?.querySelector('[data-visor-siguiente]')?.addEventListener('click', () => mover(1));
            visor?.querySelector('[data-visor-girar]')?.addEventListener('click', () => { giro = (giro + 90) % 360; aplicarTransformacion(); });
            visor?.querySelector('[data-visor-zoom]')?.addEventListener('click', () => { ampliado = !ampliado; aplicarTransformacion(); });
            document.addEventListener('keydown', (event) => {
                if (!visor || visor.classList.contains('hidden')) return;
                if (event.key === 'Escape') cerrarVisor();
                if (event.key === 'ArrowLeft') mover(-1);
                if (event.key === 'ArrowRight') mover(1);
            });

            document.querySelector('[data-open-stats]')?.addEventListener('click', () => { document.querySelector('[data-stats-modal]')?.classList.remove('hidden'); document.querySelector('[data-stats-modal]')?.classList.add('flex'); });
            document.querySelector('[data-close-stats]')?.addEventListener('click', () => { document.querySelector('[data-stats-modal]')?.classList.add('hidden'); document.querySelector('[data-stats-modal]')?.classList.remove('flex'); });
            activate(active);
        })();
    </script>
@endsection
