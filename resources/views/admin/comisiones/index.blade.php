@extends(auth()->user()?->isSuperAdmin() && request()->routeIs('admin.*') ? 'layouts.super-admin' : 'layouts.app')

@section('content')
    @php
        $showRecalculateModal = $errors->any();
        $commissionStates = ['pendiente', 'facturada', 'pagada', 'vencida', 'anulada'];
        $stateClasses = [
            'pendiente' => 'bg-amber-50 text-amber-700 ring-1 ring-amber-200',
            'facturada' => 'bg-sky-50 text-sky-700 ring-1 ring-sky-200',
            'pagada' => 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-200',
            'vencida' => 'bg-rose-50 text-rose-700 ring-1 ring-rose-200',
            'anulada' => 'bg-slate-100 text-slate-700 ring-1 ring-slate-200',
        ];
    @endphp

    <section class="-mx-4 -my-6 bg-atlantia-cream/35 px-4 py-6 text-atlantia-ink sm:-mx-6 sm:px-6 lg:-mx-8 lg:px-8">
        <div class="mx-auto max-w-7xl rounded-2xl border border-atlantia-rose/20 bg-white p-6 shadow-sm lg:p-8">
            <header class="flex flex-col gap-6 lg:flex-row lg:items-start lg:justify-between">
                <div>
                    <p class="text-xs font-black uppercase tracking-[0.18em] text-atlantia-rose">Atlantia Supermarket</p>
                    <h1 class="mt-2 text-5xl font-black leading-tight text-atlantia-ink">Comisiones</h1>
                    <p class="mt-3 text-base text-atlantia-ink/65">Controla el cobro mensual y la conciliacion operativa por vendedor.</p>
                </div>

                <button
                    type="button"
                    class="inline-flex items-center justify-center gap-3 rounded-md bg-atlantia-wine px-6 py-4 text-base font-black text-white shadow-sm transition hover:bg-atlantia-wine-700"
                    data-open-create-modal
                >
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M20 12A8 8 0 0 1 6.3 17.7M4 12A8 8 0 0 1 17.7 6.3" stroke="currentColor" stroke-width="2" stroke-linecap="round"/><path d="M6 21V18H3" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><path d="M18 3V6H21" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    Recalcular periodo
                </button>
            </header>

            <div class="mt-8 grid gap-4 md:grid-cols-2 xl:grid-cols-5">
                <article class="rounded-xl border border-atlantia-rose/20 bg-atlantia-blush/25 p-5 shadow-sm">
                    <div class="flex items-center gap-4">
                        <span class="grid h-14 w-14 place-items-center rounded-xl border border-atlantia-rose/20 bg-atlantia-blush text-atlantia-wine">
                            <svg class="h-7 w-7" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M4 7H20V18H4V7Z" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/><path d="M17 12H20" stroke="currentColor" stroke-width="2" stroke-linecap="round"/><path d="M7 7V5H17V7" stroke="currentColor" stroke-width="2"/></svg>
                        </span>
                        <div>
                            <p class="text-sm text-atlantia-ink/60">Monto del periodo</p>
                            <p class="mt-1 text-3xl font-black text-atlantia-wine">Q{{ number_format($dashboard['total_periodo'], 2) }}</p>
                        </div>
                    </div>
                </article>
                <article class="rounded-xl border border-amber-200 bg-white p-5 shadow-sm">
                    <div class="flex items-center gap-4">
                        <span class="grid h-14 w-14 place-items-center rounded-xl bg-amber-50 text-amber-600">
                            <svg class="h-7 w-7" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M12 7V12L15 15" stroke="currentColor" stroke-width="2" stroke-linecap="round"/><path d="M21 12C21 17 17 21 12 21C7 21 3 17 3 12C3 7 7 3 12 3C17 3 21 7 21 12Z" stroke="currentColor" stroke-width="2"/></svg>
                        </span>
                        <div>
                            <p class="text-sm text-atlantia-ink/60">Pendientes</p>
                            <p class="mt-1 text-3xl font-black text-amber-600">{{ $dashboard['pendientes'] }}</p>
                        </div>
                    </div>
                </article>
                <article class="rounded-xl border border-sky-200 bg-white p-5 shadow-sm">
                    <div class="flex items-center gap-4">
                        <span class="grid h-14 w-14 place-items-center rounded-xl bg-sky-50 text-sky-600">
                            <svg class="h-7 w-7" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M6 3H15L19 7V21H6V3Z" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/><path d="M14 3V8H19" stroke="currentColor" stroke-width="2"/><path d="M9 13H15M9 17H15" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
                        </span>
                        <div>
                            <p class="text-sm text-atlantia-ink/60">Facturadas</p>
                            <p class="mt-1 text-3xl font-black text-sky-600">{{ $dashboard['facturadas'] }}</p>
                        </div>
                    </div>
                </article>
                <article class="rounded-xl border border-emerald-200 bg-white p-5 shadow-sm">
                    <div class="flex items-center gap-4">
                        <span class="grid h-14 w-14 place-items-center rounded-xl bg-emerald-50 text-emerald-600">
                            <svg class="h-7 w-7" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M20 6L9 17L4 12" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                        </span>
                        <div>
                            <p class="text-sm text-atlantia-ink/60">Pagadas</p>
                            <p class="mt-1 text-3xl font-black text-emerald-600">{{ $dashboard['pagadas'] }}</p>
                        </div>
                    </div>
                </article>
                <article class="rounded-xl border border-rose-200 bg-white p-5 shadow-sm">
                    <div class="flex items-center gap-4">
                        <span class="grid h-14 w-14 place-items-center rounded-xl bg-rose-50 text-rose-600">
                            <svg class="h-7 w-7" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M12 4L21 20H3L12 4Z" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/><path d="M12 9V14" stroke="currentColor" stroke-width="2" stroke-linecap="round"/><path d="M12 17H12.01" stroke="currentColor" stroke-width="3" stroke-linecap="round"/></svg>
                        </span>
                        <div>
                            <p class="text-sm text-atlantia-ink/60">Vencidas</p>
                            <p class="mt-1 text-3xl font-black text-rose-600">{{ $dashboard['vencidas'] }}</p>
                        </div>
                    </div>
                </article>
            </div>

            <div class="mt-6 grid gap-6 xl:grid-cols-[380px_1fr]">
                <aside class="rounded-xl border border-atlantia-rose/20 bg-white p-5 shadow-sm">
                    <div class="flex items-start gap-4">
                        <span class="grid h-12 w-12 shrink-0 place-items-center rounded-full bg-atlantia-blush text-atlantia-wine">
                            <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M12 12C14.2 12 16 10.2 16 8C16 5.8 14.2 4 12 4C9.8 4 8 5.8 8 8C8 10.2 9.8 12 12 12Z" stroke="currentColor" stroke-width="2"/><path d="M5 21C5.8 17.6 8.5 15.5 12 15.5C15.5 15.5 18.2 17.6 19 21" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
                        </span>
                        <div>
                            <h2 class="text-xl font-black text-atlantia-wine">Mayor saldo</h2>
                            <p class="mt-1 text-sm text-atlantia-ink/65">Vendedores con comisiones mas altas del periodo.</p>
                        </div>
                    </div>

                    <div class="mt-5 space-y-3">
                        @forelse ($dashboard['top_vendedores'] as $top)
                            <div class="rounded-xl border border-atlantia-rose/15 bg-atlantia-cream/45 px-4 py-3 text-sm">
                                <div class="flex items-center justify-between gap-3">
                                    <span class="font-black text-atlantia-ink">{{ $top['vendor'] }}</span>
                                    <span class="font-black text-atlantia-wine">Q{{ number_format($top['monto_total'], 2) }}</span>
                                </div>
                                <p class="mt-1 text-xs text-atlantia-ink/55">{{ $top['periodo'] }} - {{ $top['estado'] }}</p>
                            </div>
                        @empty
                            <div class="grid min-h-64 place-items-center rounded-xl border border-dashed border-atlantia-rose/25 bg-white text-center">
                                <div>
                                    <span class="mx-auto grid h-20 w-20 place-items-center rounded-full bg-atlantia-blush text-atlantia-wine">
                                        <svg class="h-10 w-10" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M6 3H15L19 7V21H6V3Z" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/><path d="M14 3V8H19" stroke="currentColor" stroke-width="2"/><path d="M10 14L15 19M15 14L10 19" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
                                    </span>
                                    <p class="mt-5 font-black text-atlantia-ink/70">Aun no hay comisiones registradas.</p>
                                    <p class="mt-2 text-sm text-atlantia-ink/60">Recalcula un periodo para ver resultados.</p>
                                </div>
                            </div>
                        @endforelse
                    </div>
                </aside>

                <div class="rounded-xl border border-atlantia-rose/20 bg-white p-5 shadow-sm">
                    <form method="GET" class="grid gap-3 xl:grid-cols-[1fr_0.9fr_0.65fr_0.65fr_auto]">
                        <select name="vendor_id" class="rounded-md border border-atlantia-rose/30 bg-white px-4 py-3 text-sm outline-none focus:border-atlantia-wine focus:ring-2 focus:ring-atlantia-rose/20">
                            <option value="">Todos los vendedores</option>
                            @foreach ($vendors as $vendor)
                                <option value="{{ $vendor->id }}" @selected((string) request('vendor_id') === (string) $vendor->id)>{{ $vendor->business_name }}</option>
                            @endforeach
                        </select>
                        <select name="estado" class="rounded-md border border-atlantia-rose/30 bg-white px-4 py-3 text-sm outline-none focus:border-atlantia-wine focus:ring-2 focus:ring-atlantia-rose/20">
                            <option value="">Todos los estados</option>
                            @foreach ($commissionStates as $estado)
                                <option value="{{ $estado }}" @selected(request('estado') === $estado)>{{ ucfirst($estado) }}</option>
                            @endforeach
                        </select>
                        <input type="number" name="anio" min="2024" max="2100" value="{{ request('anio') }}" placeholder="Anio" class="rounded-md border border-atlantia-rose/30 bg-white px-4 py-3 text-sm outline-none focus:border-atlantia-wine focus:ring-2 focus:ring-atlantia-rose/20">
                        <input type="number" name="mes" min="1" max="12" value="{{ request('mes') }}" placeholder="Mes" class="rounded-md border border-atlantia-rose/30 bg-white px-4 py-3 text-sm outline-none focus:border-atlantia-wine focus:ring-2 focus:ring-atlantia-rose/20">
                        <button type="submit" class="inline-flex items-center justify-center gap-2 rounded-md border border-atlantia-rose/35 bg-white px-5 py-3 text-sm font-black text-atlantia-wine transition hover:bg-atlantia-blush">
                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M11 19C15.4 19 19 15.4 19 11C19 6.6 15.4 3 11 3C6.6 3 3 6.6 3 11C3 15.4 6.6 19 11 19Z" stroke="currentColor" stroke-width="2"/><path d="M20.5 20.5L16.7 16.7" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
                            Filtrar
                        </button>
                    </form>

                    <div class="mt-5 grid gap-4 lg:grid-cols-2">
                        @forelse ($comisiones as $comision)
                            <article class="rounded-xl border border-atlantia-rose/20 bg-atlantia-cream/35 p-4 shadow-sm transition hover:border-atlantia-wine/35 hover:bg-atlantia-cream/60">
                                <div class="flex items-start justify-between gap-3">
                                    <div>
                                        <p class="text-lg font-black text-atlantia-ink">{{ $comision->vendor?->business_name ?? 'Vendedor no disponible' }}</p>
                                        <p class="mt-1 text-xs font-semibold text-atlantia-ink/55">Periodo {{ sprintf('%02d/%d', $comision->mes, $comision->anio) }}</p>
                                    </div>
                                    <span class="{{ $stateClasses[$comision->estado] ?? 'bg-atlantia-blush text-atlantia-wine ring-1 ring-atlantia-rose/20' }} rounded-md px-3 py-1 text-xs font-black">
                                        {{ ucfirst($comision->estado) }}
                                    </span>
                                </div>

                                <div class="mt-4 grid grid-cols-2 gap-3">
                                    <div class="rounded-lg border border-atlantia-rose/15 bg-white px-3 py-2">
                                        <p class="text-xs font-black uppercase tracking-wide text-atlantia-ink/45">Ventas</p>
                                        <p class="mt-1 font-black text-atlantia-ink">Q{{ number_format((float) $comision->total_ventas, 2) }}</p>
                                    </div>
                                    <div class="rounded-lg border border-atlantia-rose/15 bg-white px-3 py-2">
                                        <p class="text-xs font-black uppercase tracking-wide text-atlantia-ink/45">Comision ventas</p>
                                        <p class="mt-1 font-black text-atlantia-wine">Q{{ number_format((float) $comision->monto_comision, 2) }}</p>
                                    </div>
                                    <div class="rounded-lg border border-atlantia-rose/15 bg-white px-3 py-2">
                                        <p class="text-xs font-black uppercase tracking-wide text-atlantia-ink/45">Renta fija</p>
                                        <p class="mt-1 font-black text-atlantia-wine">Q{{ number_format((float) $comision->renta_fija, 2) }}</p>
                                    </div>
                                    <div class="rounded-lg border border-atlantia-rose/15 bg-white px-3 py-2">
                                        <p class="text-xs font-black uppercase tracking-wide text-atlantia-ink/45">Total a cobrar</p>
                                        <p class="mt-1 font-black text-atlantia-wine">Q{{ number_format((float) $comision->monto_total, 2) }}</p>
                                    </div>
                                </div>

                                <form method="POST" action="{{ route('admin.comisiones.update', $comision) }}" class="mt-4 grid gap-2 sm:grid-cols-[1fr_1fr_auto]">
                                    @csrf
                                    @method('PUT')
                                    <select name="estado" class="rounded-md border border-atlantia-rose/35 px-3 py-2 text-sm">
                                        @foreach ($commissionStates as $estado)
                                            <option value="{{ $estado }}" @selected($comision->estado === $estado)>{{ ucfirst($estado) }}</option>
                                        @endforeach
                                    </select>
                                    <input type="date" name="fecha_vencimiento" value="{{ optional($comision->fecha_vencimiento)->format('Y-m-d') }}" class="rounded-md border border-atlantia-rose/35 px-3 py-2 text-sm">
                                    <button type="submit" class="rounded-md bg-atlantia-wine px-4 py-2 text-sm font-black text-white transition hover:bg-atlantia-wine-700">Guardar</button>
                                </form>
                            </article>
                        @empty
                            <div class="grid min-h-64 place-items-center rounded-xl border border-dashed border-atlantia-rose/25 bg-white px-4 py-10 text-center lg:col-span-2">
                                <div>
                                    <span class="mx-auto grid h-20 w-20 place-items-center rounded-full bg-atlantia-cream text-atlantia-ink/55">
                                        <svg class="h-10 w-10" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M4 7H20V18H4V7Z" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/><path d="M8 7V5H16V7" stroke="currentColor" stroke-width="2"/></svg>
                                    </span>
                                    <p class="mt-5 text-xl font-black text-atlantia-ink">No hay comisiones registradas.</p>
                                    <p class="mt-2 text-sm text-atlantia-ink/60">Prueba recalcular un periodo o ajustar los filtros.</p>
                                </div>
                            </div>
                        @endforelse
                    </div>

                    <div class="mt-4">{{ $comisiones->links() }}</div>
                </div>
            </div>
        </div>
    </section>

    <div
        class="{{ $showRecalculateModal ? 'flex' : 'hidden' }} fixed inset-0 z-50 items-center justify-center bg-slate-950/55 px-4 py-6 backdrop-blur-sm"
        data-create-modal
        role="dialog"
        aria-modal="true"
        aria-labelledby="commission-recalculate-title"
    >
        <div class="max-h-[92vh] w-full max-w-xl overflow-hidden rounded-2xl border border-atlantia-rose/25 bg-white shadow-2xl">
            <div class="flex items-start justify-between gap-4 border-b border-atlantia-rose/15 px-6 py-5">
                <div>
                    <p class="text-xs font-black uppercase tracking-[0.18em] text-atlantia-rose">Atlantia Supermarket</p>
                    <h2 id="commission-recalculate-title" class="mt-1 text-2xl font-black text-atlantia-ink">Recalcular periodo</h2>
                    <p class="mt-1 text-sm text-atlantia-ink/60">Genera o actualiza las comisiones mensuales de vendedores aprobados.</p>
                </div>
                <button type="button" class="rounded-md border border-atlantia-rose/30 px-3 py-2 text-sm font-black text-atlantia-wine hover:bg-atlantia-blush" data-close-create-modal>
                    Cerrar
                </button>
            </div>

            <form method="POST" action="{{ route('admin.comisiones.recalcular') }}" class="px-6 py-5">
                @csrf

                @if ($errors->any())
                    <div class="mb-5 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-semibold text-red-800">
                        Revisa anio y mes antes de recalcular.
                    </div>
                @endif

                <div class="grid gap-4 md:grid-cols-2">
                    <div>
                        <label class="text-sm font-semibold text-atlantia-ink">Anio</label>
                        <input name="anio" type="number" min="2024" max="2100" value="{{ old('anio', request('anio', now()->year)) }}" class="mt-1 w-full rounded-md border border-atlantia-rose/35 px-3 py-2" required>
                        @error('anio') <p class="mt-1 text-xs font-semibold text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="text-sm font-semibold text-atlantia-ink">Mes</label>
                        <input name="mes" type="number" min="1" max="12" value="{{ old('mes', request('mes', now()->month)) }}" class="mt-1 w-full rounded-md border border-atlantia-rose/35 px-3 py-2" required>
                        @error('mes') <p class="mt-1 text-xs font-semibold text-red-600">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div class="mt-5 rounded-xl border border-atlantia-rose/20 bg-atlantia-cream px-4 py-3 text-sm text-atlantia-ink/70">
                    El recalculo toma ventas cobrables del periodo y suma la renta fija mensual configurada en cada vendedor.
                </div>

                <div class="mt-6 flex flex-col-reverse gap-3 border-t border-atlantia-rose/15 pt-5 sm:flex-row sm:justify-end">
                    <button type="button" class="rounded-md border border-atlantia-rose/30 px-4 py-2 text-sm font-black text-atlantia-wine hover:bg-atlantia-blush" data-close-create-modal>
                        Cancelar
                    </button>
                    <button type="submit" class="rounded-md bg-atlantia-wine px-4 py-2 text-sm font-black text-white transition hover:bg-atlantia-wine-700">Recalcular comisiones</button>
                </div>
            </form>
        </div>
    </div>
@endsection
