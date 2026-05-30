@extends(auth()->user()?->isSuperAdmin() && request()->routeIs('admin.*') ? 'layouts.super-admin' : 'layouts.app')

@section('content')
    @php
        $statusStyles = [
            'borrador' => 'bg-slate-100 text-slate-700',
            'certificado' => 'bg-emerald-100 text-emerald-700',
            'rechazado' => 'bg-rose-100 text-rose-700',
            'anulado' => 'bg-amber-100 text-amber-700',
        ];
    @endphp

    <section class="-mx-4 -my-6 overflow-x-hidden bg-atlantia-cream/35 px-4 py-6 sm:-mx-6 sm:px-6 lg:-mx-8 lg:px-8">
        <div class="mx-auto max-w-7xl rounded-2xl border border-atlantia-rose/20 bg-white p-5 shadow-sm sm:p-7">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                <div class="min-w-0">
                    <p class="text-sm font-black uppercase tracking-[0.18em] text-atlantia-rose">Atlantia Supermarket</p>
                    <h1 class="mt-2 text-4xl font-black leading-tight text-atlantia-ink">DTE FEL</h1>
                    <p class="mt-2 max-w-3xl text-sm text-atlantia-ink/65">
                        Supervisa certificacion, rechazo y anulacion fiscal de documentos emitidos.
                    </p>
                </div>

                <div class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-xs font-semibold text-amber-800">
                    Modo emulado hasta conectar certificador FEL real.
                </div>
            </div>

            <div class="mt-7 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                <article class="rounded-xl border border-emerald-200 bg-white p-4 shadow-sm">
                    <div class="flex items-center gap-4">
                        <span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-full bg-emerald-50 text-emerald-600">
                            <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                <path d="m6 12 4 4 8-8" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </span>
                        <div>
                            <p class="text-sm text-atlantia-ink/55">Certificados</p>
                            <p class="mt-1 text-2xl font-black text-emerald-600">{{ number_format((int) $dashboard['certificados']) }}</p>
                        </div>
                    </div>
                </article>

                <article class="rounded-xl border border-rose-200 bg-white p-4 shadow-sm">
                    <div class="flex items-center gap-4">
                        <span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-full bg-rose-50 text-rose-600">
                            <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                <path d="M12 8v5M12 16h.01" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"/>
                                <path d="M10.3 4.7a2 2 0 0 1 3.4 0l7 12.1A2 2 0 0 1 19 19.8H5a2 2 0 0 1-1.7-3l7-12.1Z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/>
                            </svg>
                        </span>
                        <div>
                            <p class="text-sm text-atlantia-ink/55">Rechazados</p>
                            <p class="mt-1 text-2xl font-black text-rose-600">{{ number_format((int) $dashboard['rechazados']) }}</p>
                        </div>
                    </div>
                </article>

                <article class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                    <div class="flex items-center gap-4">
                        <span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-full bg-slate-50 text-slate-700">
                            <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                <path d="m7 7 10 10M17 7 7 17" stroke="currentColor" stroke-width="2.1" stroke-linecap="round"/>
                                <circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="1.8"/>
                            </svg>
                        </span>
                        <div>
                            <p class="text-sm text-atlantia-ink/55">Anulados</p>
                            <p class="mt-1 text-2xl font-black text-slate-700">{{ number_format((int) $dashboard['anulados']) }}</p>
                        </div>
                    </div>
                </article>

                <article class="rounded-xl border border-atlantia-rose/20 bg-atlantia-blush/35 p-4 shadow-sm">
                    <div class="flex items-center gap-4">
                        <span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-full bg-white text-atlantia-wine">
                            <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                <path d="M5 4h14v16H5z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/>
                                <path d="M8 8h8M8 12h8M8 16h4" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                            </svg>
                        </span>
                        <div>
                            <p class="text-sm text-atlantia-ink/55">Monto total</p>
                            <p class="mt-1 text-2xl font-black text-atlantia-wine">Q{{ number_format((float) $dashboard['monto_total'], 2) }}</p>
                        </div>
                    </div>
                </article>
            </div>

            <div class="mt-6 rounded-2xl border border-atlantia-rose/20 bg-atlantia-blush/25 p-4">
                <form method="GET" class="grid min-w-0 gap-3 sm:grid-cols-2 lg:grid-cols-6">
                    <label class="min-w-0 lg:col-span-2">
                        <span class="sr-only">Vendedor</span>
                        <select name="vendor_id" class="h-11 w-full min-w-0 rounded-lg border border-atlantia-rose/35 bg-white px-3 text-sm font-semibold text-atlantia-ink outline-none transition focus:border-atlantia-wine focus:ring-2 focus:ring-atlantia-rose/20">
                            <option value="">Todos los vendedores</option>
                            @foreach ($vendors as $vendor)
                                <option value="{{ $vendor->id }}" @selected((string) request('vendor_id') === (string) $vendor->id)>{{ $vendor->business_name }}</option>
                            @endforeach
                        </select>
                    </label>

                    <label class="min-w-0">
                        <span class="sr-only">Estado</span>
                        <select name="estado" class="h-11 w-full min-w-0 rounded-lg border border-atlantia-rose/35 bg-white px-3 text-sm font-semibold text-atlantia-ink outline-none transition focus:border-atlantia-wine focus:ring-2 focus:ring-atlantia-rose/20">
                            <option value="">Todos los estados</option>
                            @foreach (['borrador', 'certificado', 'rechazado', 'anulado'] as $estado)
                                <option value="{{ $estado }}" @selected(request('estado') === $estado)>{{ ucfirst($estado) }}</option>
                            @endforeach
                        </select>
                    </label>

                    <label class="min-w-0">
                        <span class="sr-only">Tipo</span>
                        <select name="tipo_dte" class="h-11 w-full min-w-0 rounded-lg border border-atlantia-rose/35 bg-white px-3 text-sm font-semibold text-atlantia-ink outline-none transition focus:border-atlantia-wine focus:ring-2 focus:ring-atlantia-rose/20">
                            <option value="">Todos los tipos</option>
                            @foreach (['FACT', 'FCAM', 'FPEQ', 'NCRE', 'NDEB'] as $tipo)
                                <option value="{{ $tipo }}" @selected(request('tipo_dte') === $tipo)>{{ $tipo }}</option>
                            @endforeach
                        </select>
                    </label>

                    <label class="min-w-0">
                        <span class="sr-only">Fecha desde</span>
                        <input type="date" name="fecha_desde" value="{{ request('fecha_desde') }}" class="h-11 w-full min-w-0 rounded-lg border border-atlantia-rose/35 bg-white px-3 text-sm font-semibold text-atlantia-ink outline-none transition focus:border-atlantia-wine focus:ring-2 focus:ring-atlantia-rose/20">
                    </label>

                    <label class="min-w-0">
                        <span class="sr-only">Fecha hasta</span>
                        <input type="date" name="fecha_hasta" value="{{ request('fecha_hasta') }}" class="h-11 w-full min-w-0 rounded-lg border border-atlantia-rose/35 bg-white px-3 text-sm font-semibold text-atlantia-ink outline-none transition focus:border-atlantia-wine focus:ring-2 focus:ring-atlantia-rose/20">
                    </label>

                    <div class="sm:col-span-2 lg:col-span-6 flex flex-col gap-2 sm:flex-row sm:justify-end">
                        <a href="{{ route('admin.dte.index') }}" class="inline-flex h-11 items-center justify-center rounded-lg border border-atlantia-rose/35 bg-white px-5 text-sm font-black text-atlantia-wine transition hover:bg-atlantia-blush">
                            Limpiar
                        </a>
                        <button type="submit" class="inline-flex h-11 items-center justify-center gap-2 rounded-lg bg-atlantia-wine px-6 text-sm font-black text-white shadow-sm transition hover:bg-atlantia-wine-700">
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                <path d="M4 6h16M7 12h10M10 18h4" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                            </svg>
                            Filtrar
                        </button>
                    </div>
                </form>
            </div>

            <div class="mt-6 overflow-hidden rounded-2xl border border-atlantia-rose/20 bg-white">
                <div class="grid grid-cols-12 border-b border-atlantia-rose/15 bg-atlantia-cream/65 px-4 py-3 text-xs font-black uppercase tracking-wide text-atlantia-ink/55">
                    <div class="col-span-4">DTE</div>
                    <div class="col-span-3 hidden md:block">Vendedor</div>
                    <div class="col-span-2 hidden lg:block">Tipo</div>
                    <div class="col-span-2 hidden sm:block">Monto</div>
                    <div class="col-span-4 sm:col-span-2 md:col-span-2 lg:col-span-1">Estado</div>
                    <div class="col-span-4 sm:col-span-2 md:col-span-1 text-right">Acciones</div>
                </div>

                @forelse ($dtes as $dte)
                    <article class="grid grid-cols-12 items-center gap-2 border-b border-atlantia-rose/10 px-4 py-4 last:border-b-0">
                        <div class="col-span-4 min-w-0">
                            <p class="truncate font-black text-atlantia-ink">{{ $dte->numero_dte }}</p>
                            <p class="truncate text-xs text-atlantia-ink/50">{{ $dte->uuid_sat ?? 'Sin UUID SAT' }}</p>
                            <p class="mt-1 text-xs text-atlantia-ink/55 md:hidden">{{ $dte->vendor?->business_name ?? 'Sin vendedor' }}</p>
                        </div>
                        <div class="col-span-3 hidden min-w-0 md:block">
                            <p class="truncate text-sm font-semibold text-atlantia-ink/70">{{ $dte->vendor?->business_name ?? 'Sin vendedor' }}</p>
                        </div>
                        <div class="col-span-2 hidden lg:block">
                            <span class="rounded-md border border-atlantia-rose/20 bg-white px-2.5 py-1 text-xs font-black text-atlantia-wine">{{ $dte->tipo_dte }}</span>
                        </div>
                        <div class="col-span-2 hidden sm:block">
                            <p class="font-black text-atlantia-wine">Q{{ number_format((float) $dte->monto_total, 2) }}</p>
                        </div>
                        <div class="col-span-4 sm:col-span-2 md:col-span-2 lg:col-span-1">
                            <span class="inline-flex rounded-md px-2.5 py-1 text-xs font-black {{ $statusStyles[$dte->estado] ?? 'bg-atlantia-blush text-atlantia-wine' }}">
                                {{ ucfirst($dte->estado) }}
                            </span>
                        </div>
                        <div class="col-span-4 sm:col-span-2 md:col-span-1 text-right">
                            <a href="{{ route('admin.dte.show', $dte->uuid) }}" class="inline-flex rounded-lg border border-atlantia-rose/30 px-3 py-2 text-xs font-black text-atlantia-wine transition hover:bg-atlantia-blush">
                                Gestionar
                            </a>
                        </div>
                    </article>
                @empty
                    <div class="px-4 py-12 text-center">
                        <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-atlantia-blush text-atlantia-wine">
                            <svg class="h-8 w-8" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                <path d="M5 4h14v16H5z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/>
                                <path d="M8 9h8M8 13h8M8 17h4" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                            </svg>
                        </div>
                        <p class="mt-4 text-lg font-black text-atlantia-ink">No hay DTE registrados.</p>
                        <p class="mt-1 text-sm text-atlantia-ink/60">Cuando se generen comprobantes de compra apareceran aqui.</p>
                    </div>
                @endforelse
            </div>

            <div class="mt-5">
                {{ $dtes->links() }}
            </div>
        </div>
    </section>
@endsection
