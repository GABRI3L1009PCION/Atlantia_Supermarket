@extends(auth()->user()?->isSuperAdmin() && request()->routeIs('admin.*') ? 'layouts.super-admin' : 'layouts.app')

@section('content')
    <section class="mx-auto max-w-full py-2">
        <div class="space-y-6 rounded-2xl border border-atlantia-rose/20 bg-white p-6 shadow-sm">
            <x-page-header title="Comisiones" subtitle="Controla el cobro mensual y la conciliacion operativa por vendedor." />

            <div class="grid gap-4 md:grid-cols-5">
                <div class="rounded-xl border border-atlantia-rose/20 bg-atlantia-cream p-4">
                    <p class="text-sm text-atlantia-ink/55">Monto del periodo</p>
                    <p class="mt-2 text-2xl font-bold text-atlantia-wine">Q{{ number_format($dashboard['total_periodo'], 2) }}</p>
                </div>
                <div class="rounded-xl border border-amber-200 bg-white p-4">
                    <p class="text-sm text-atlantia-ink/55">Pendientes</p>
                    <p class="mt-2 text-2xl font-bold text-amber-600">{{ $dashboard['pendientes'] }}</p>
                </div>
                <div class="rounded-xl border border-sky-200 bg-white p-4">
                    <p class="text-sm text-atlantia-ink/55">Facturadas</p>
                    <p class="mt-2 text-2xl font-bold text-sky-600">{{ $dashboard['facturadas'] }}</p>
                </div>
                <div class="rounded-xl border border-emerald-200 bg-white p-4">
                    <p class="text-sm text-atlantia-ink/55">Pagadas</p>
                    <p class="mt-2 text-2xl font-bold text-emerald-600">{{ $dashboard['pagadas'] }}</p>
                </div>
                <div class="rounded-xl border border-rose-200 bg-white p-4">
                    <p class="text-sm text-atlantia-ink/55">Vencidas</p>
                    <p class="mt-2 text-2xl font-bold text-rose-600">{{ $dashboard['vencidas'] }}</p>
                </div>
            </div>

            <div class="grid gap-6 xl:grid-cols-[410px_1fr]">
                <form method="POST" action="{{ route('admin.comisiones.recalcular') }}" class="rounded-xl border border-atlantia-rose/20 bg-atlantia-cream p-5">
                    @csrf
                    <h2 class="text-lg font-bold text-atlantia-wine">Recalcular periodo</h2>
                    <p class="mt-2 text-sm text-atlantia-ink/65">Genera o actualiza las comisiones mensuales de todos los vendedores aprobados.</p>

                    <div class="mt-4 grid gap-4 md:grid-cols-2">
                        <div>
                            <label class="text-sm font-semibold text-atlantia-ink">Anio</label>
                            <input name="anio" type="number" min="2024" max="2100" value="{{ request('anio', now()->year) }}" class="mt-1 w-full rounded-md border border-atlantia-rose/35 px-3 py-2" required>
                        </div>
                        <div>
                            <label class="text-sm font-semibold text-atlantia-ink">Mes</label>
                            <input name="mes" type="number" min="1" max="12" value="{{ request('mes', now()->month) }}" class="mt-1 w-full rounded-md border border-atlantia-rose/35 px-3 py-2" required>
                        </div>
                    </div>

                    <x-ui.button type="submit" class="mt-5 w-full">Recalcular comisiones</x-ui.button>

                    <div class="mt-6">
                        <h3 class="text-sm font-semibold text-atlantia-ink">Vendedores con mayor saldo</h3>
                        <div class="mt-3 space-y-2">
                            @forelse ($dashboard['top_vendedores'] as $top)
                                <div class="rounded-lg border border-atlantia-rose/15 bg-white px-3 py-2 text-sm">
                                    <div class="flex items-center justify-between gap-3">
                                        <span class="font-semibold text-atlantia-ink">{{ $top['vendor'] }}</span>
                                        <span class="font-bold text-atlantia-wine">Q{{ number_format($top['monto_total'], 2) }}</span>
                                    </div>
                                    <p class="mt-1 text-xs text-atlantia-ink/55">{{ $top['periodo'] }} · {{ $top['estado'] }}</p>
                                </div>
                            @empty
                                <p class="text-sm text-atlantia-ink/60">Aun no hay comisiones registradas.</p>
                            @endforelse
                        </div>
                    </div>
                </form>

                <div class="rounded-xl border border-atlantia-rose/20 bg-white p-5">
                    <form method="GET" class="grid gap-3 xl:grid-cols-[1fr_0.8fr_0.55fr_0.55fr_auto]">
                        <select name="vendor_id" class="rounded-md border border-atlantia-rose/35 px-3 py-2">
                            <option value="">Todos los vendedores</option>
                            @foreach ($vendors as $vendor)
                                <option value="{{ $vendor->id }}" @selected((string) request('vendor_id') === (string) $vendor->id)>{{ $vendor->business_name }}</option>
                            @endforeach
                        </select>
                        <select name="estado" class="rounded-md border border-atlantia-rose/35 px-3 py-2">
                            <option value="">Todos los estados</option>
                            @foreach (['pendiente', 'facturada', 'pagada', 'vencida', 'anulada'] as $estado)
                                <option value="{{ $estado }}" @selected(request('estado') === $estado)>{{ ucfirst($estado) }}</option>
                            @endforeach
                        </select>
                        <input type="number" name="anio" min="2024" max="2100" value="{{ request('anio') }}" placeholder="Anio" class="rounded-md border border-atlantia-rose/35 px-3 py-2">
                        <input type="number" name="mes" min="1" max="12" value="{{ request('mes') }}" placeholder="Mes" class="rounded-md border border-atlantia-rose/35 px-3 py-2">
                        <x-ui.button type="submit" variant="secondary">Filtrar</x-ui.button>
                    </form>

                    <div class="mt-5 overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <thead>
                                <tr class="border-b border-atlantia-rose/20 text-left text-atlantia-ink/55">
                                    <th class="pb-3">Vendedor</th>
                                    <th class="pb-3">Periodo</th>
                                    <th class="pb-3">Ventas</th>
                                    <th class="pb-3">Comision</th>
                                    <th class="pb-3">Estado</th>
                                    <th class="pb-3">Gestion</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-atlantia-rose/15">
                                @forelse ($comisiones as $comision)
                                    <tr>
                                        <td class="py-3 font-semibold text-atlantia-ink">{{ $comision->vendor?->business_name }}</td>
                                        <td class="py-3 text-atlantia-ink/70">{{ sprintf('%02d/%d', $comision->mes, $comision->anio) }}</td>
                                        <td class="py-3 text-atlantia-ink/70">Q{{ number_format((float) $comision->total_ventas, 2) }}</td>
                                        <td class="py-3 font-semibold text-atlantia-wine">Q{{ number_format((float) $comision->monto_total, 2) }}</td>
                                        <td class="py-3">
                                            <span class="rounded-md bg-atlantia-blush px-3 py-1 text-xs font-bold text-atlantia-wine">{{ $comision->estado }}</span>
                                        </td>
                                        <td class="py-3">
                                            <form method="POST" action="{{ route('admin.comisiones.update', $comision) }}" class="grid gap-2 md:grid-cols-[1fr_1fr_auto]">
                                                @csrf
                                                @method('PUT')
                                                <select name="estado" class="rounded-md border border-atlantia-rose/35 px-3 py-2">
                                                    @foreach (['pendiente', 'facturada', 'pagada', 'vencida', 'anulada'] as $estado)
                                                        <option value="{{ $estado }}" @selected($comision->estado === $estado)>{{ ucfirst($estado) }}</option>
                                                    @endforeach
                                                </select>
                                                <input type="date" name="fecha_vencimiento" value="{{ optional($comision->fecha_vencimiento)->format('Y-m-d') }}" class="rounded-md border border-atlantia-rose/35 px-3 py-2">
                                                <x-ui.button type="submit">Guardar</x-ui.button>
                                            </form>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="py-6 text-center text-atlantia-ink/60">No hay comisiones registradas para estos filtros.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-4">{{ $comisiones->links() }}</div>
                </div>
            </div>
        </div>
    </section>
@endsection
