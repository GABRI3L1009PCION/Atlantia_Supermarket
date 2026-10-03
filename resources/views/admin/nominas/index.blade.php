@extends(auth()->user()?->isSuperAdmin() && request()->routeIs('admin.*') ? 'layouts.super-admin' : 'layouts.app')

@section('content')
    @php
        $showCreateModal = $errors->any();
        $stateClasses = [
            'borrador' => 'bg-amber-50 text-amber-700 ring-amber-200',
            'pagada' => 'bg-emerald-50 text-emerald-700 ring-emerald-200',
            'anulada' => 'bg-slate-100 text-slate-600 ring-slate-200',
        ];
    @endphp

    <section class="-mx-4 -my-6 bg-atlantia-cream/35 px-4 py-6 text-atlantia-ink sm:-mx-6 sm:px-6 lg:-mx-8 lg:px-8">
        <div class="mx-auto max-w-7xl rounded-2xl border border-atlantia-rose/20 bg-white p-6 shadow-sm lg:p-8">
            <header class="flex flex-col gap-5 lg:flex-row lg:items-start lg:justify-between">
                <div>
                    <p class="text-xs font-black uppercase tracking-[0.18em] text-atlantia-rose">Atlantia Supermarket</p>
                    <h1 class="mt-2 text-4xl font-black leading-tight text-atlantia-ink">Nomina de empleados</h1>
                    <p class="mt-2 text-sm text-atlantia-ink/65">Genera planillas internas, revisa ajustes y registra pagos reales del equipo.</p>
                </div>
                <button type="button" class="inline-flex items-center justify-center gap-2 rounded-md bg-atlantia-wine px-5 py-3 text-sm font-black text-white shadow-sm transition hover:bg-atlantia-wine-700" data-open-create-modal>
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M12 5V19M5 12H19" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
                    Generar nomina
                </button>
            </header>

            <div class="mt-7 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                <article class="rounded-xl border border-atlantia-rose/20 bg-atlantia-blush/25 p-4 shadow-sm">
                    <p class="text-sm text-atlantia-ink/60">Empleados activos</p>
                    <p class="mt-1 text-3xl font-black text-atlantia-wine">{{ $dashboard['empleados_activos'] }}</p>
                    <p class="mt-1 text-xs text-atlantia-ink/55">{{ $dashboard['salarios_configurados'] }} con salario configurado</p>
                </article>
                <article class="rounded-xl border border-amber-200 bg-white p-4 shadow-sm">
                    <p class="text-sm text-atlantia-ink/60">Nominas en borrador</p>
                    <p class="mt-1 text-3xl font-black text-amber-600">{{ $dashboard['borradores'] }}</p>
                    <p class="mt-1 text-xs text-atlantia-ink/55">Pendientes de revision o pago</p>
                </article>
                <article class="rounded-xl border border-emerald-200 bg-white p-4 shadow-sm">
                    <p class="text-sm text-atlantia-ink/60">Nominas pagadas</p>
                    <p class="mt-1 text-3xl font-black text-emerald-600">{{ $dashboard['pagadas'] }}</p>
                    <p class="mt-1 text-xs text-atlantia-ink/55">Periodos cerrados</p>
                </article>
                <article class="rounded-xl border border-atlantia-rose/20 bg-white p-4 shadow-sm">
                    <p class="text-sm text-atlantia-ink/60">Pendiente de pago</p>
                    <p class="mt-1 text-3xl font-black text-atlantia-wine">Q{{ number_format($dashboard['pendiente_pago'], 2) }}</p>
                    <p class="mt-1 text-xs text-atlantia-ink/55">Total neto de borradores</p>
                </article>
            </div>

            <div class="mt-6 rounded-xl border border-atlantia-rose/20 bg-white p-5 shadow-sm">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h2 class="text-xl font-black text-atlantia-wine">Historial de planillas</h2>
                        <p class="mt-1 text-sm text-atlantia-ink/60">Los montos se generan solo con empleados activos que tengan salario base configurado.</p>
                    </div>
                    <form method="GET">
                        <select name="estado" onchange="this.form.submit()" class="rounded-md border border-atlantia-rose/30 bg-white px-4 py-2 text-sm">
                            <option value="">Todos los estados</option>
                            <option value="borrador" @selected(request('estado') === 'borrador')>Borradores</option>
                            <option value="pagada" @selected(request('estado') === 'pagada')>Pagadas</option>
                            <option value="anulada" @selected(request('estado') === 'anulada')>Anuladas</option>
                        </select>
                    </form>
                </div>

                <div class="mt-5 grid gap-4 lg:grid-cols-2">
                    @forelse ($nominas as $nomina)
                        <article class="rounded-xl border border-atlantia-rose/20 bg-atlantia-cream/25 p-4 transition hover:border-atlantia-wine/35">
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <p class="text-lg font-black text-atlantia-ink">
                                        {{ optional($nomina->periodo_inicio)->format('d/m/Y') }} - {{ optional($nomina->periodo_fin)->format('d/m/Y') }}
                                    </p>
                                    <p class="mt-1 text-xs font-semibold uppercase tracking-wide text-atlantia-ink/50">{{ $nomina->tipo_periodo }} · {{ $nomina->detalles_count }} empleados</p>
                                </div>
                                <span class="{{ $stateClasses[$nomina->estado] ?? 'bg-slate-100 text-slate-600 ring-slate-200' }} rounded-md px-3 py-1 text-xs font-black ring-1">
                                    {{ ucfirst($nomina->estado) }}
                                </span>
                            </div>
                            <div class="mt-4 grid grid-cols-2 gap-3">
                                <div class="rounded-lg border border-atlantia-rose/15 bg-white px-3 py-2">
                                    <p class="text-xs font-black uppercase tracking-wide text-atlantia-ink/45">Salarios</p>
                                    <p class="mt-1 font-black text-atlantia-ink">Q{{ number_format((float) $nomina->total_bruto, 2) }}</p>
                                </div>
                                <div class="rounded-lg border border-atlantia-rose/15 bg-white px-3 py-2">
                                    <p class="text-xs font-black uppercase tracking-wide text-atlantia-ink/45">Total neto</p>
                                    <p class="mt-1 font-black text-atlantia-wine">Q{{ number_format((float) $nomina->total_neto, 2) }}</p>
                                </div>
                            </div>
                            <a href="{{ route('admin.nominas.show', $nomina->uuid) }}" class="mt-4 inline-flex rounded-md border border-atlantia-rose/35 bg-white px-4 py-2 text-sm font-black text-atlantia-wine transition hover:bg-atlantia-blush">
                                Revisar detalle
                            </a>
                        </article>
                    @empty
                        <div class="grid min-h-64 place-items-center rounded-xl border border-dashed border-atlantia-rose/25 bg-atlantia-cream/20 px-5 text-center lg:col-span-2">
                            <div>
                                <span class="mx-auto grid h-16 w-16 place-items-center rounded-full bg-atlantia-blush text-atlantia-wine">
                                    <svg class="h-8 w-8" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M6 3H15L19 7V21H6V3Z" stroke="currentColor" stroke-width="2"/><path d="M14 3V8H19M9 13H16M9 17H14" stroke="currentColor" stroke-width="2"/></svg>
                                </span>
                                <p class="mt-4 text-lg font-black text-atlantia-ink">Aun no hay nominas registradas.</p>
                                <p class="mt-1 text-sm text-atlantia-ink/60">Configura salarios y genera el primer periodo cuando estes listo.</p>
                            </div>
                        </div>
                    @endforelse
                </div>

                <div class="mt-4">{{ $nominas->links() }}</div>
            </div>
        </div>
    </section>

    <div class="{{ $showCreateModal ? 'flex' : 'hidden' }} fixed inset-0 z-[110] items-center justify-center bg-slate-950/55 px-4 py-6 backdrop-blur-sm" data-create-modal role="dialog" aria-modal="true">
        <div class="w-full max-w-xl overflow-hidden rounded-2xl border border-atlantia-rose/25 bg-white shadow-2xl">
            <div class="flex items-start justify-between gap-3 border-b border-atlantia-rose/15 px-6 py-5">
                <div>
                    <p class="text-xs font-black uppercase tracking-[0.18em] text-atlantia-rose">Nueva planilla</p>
                    <h2 class="mt-1 text-2xl font-black text-atlantia-ink">Generar nomina</h2>
                    <p class="mt-1 text-sm text-atlantia-ink/60">Selecciona el rango que deseas liquidar.</p>
                </div>
                <button type="button" class="rounded-md border border-atlantia-rose/30 px-3 py-2 text-sm font-black text-atlantia-wine hover:bg-atlantia-blush" data-close-create-modal>Cerrar</button>
            </div>
            <form method="POST" action="{{ route('admin.nominas.store') }}" class="px-6 py-5">
                @csrf
                @if ($errors->any())
                    <div class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm font-semibold text-red-700">{{ $errors->first() }}</div>
                @endif
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label class="text-sm font-semibold text-atlantia-ink">Fecha inicial</label>
                        <input type="date" name="periodo_inicio" value="{{ old('periodo_inicio') }}" class="mt-1 w-full rounded-md border border-atlantia-rose/35 px-3 py-2" required>
                    </div>
                    <div>
                        <label class="text-sm font-semibold text-atlantia-ink">Fecha final</label>
                        <input type="date" name="periodo_fin" value="{{ old('periodo_fin') }}" class="mt-1 w-full rounded-md border border-atlantia-rose/35 px-3 py-2" required>
                    </div>
                    <div class="sm:col-span-2">
                        <label class="text-sm font-semibold text-atlantia-ink">Tipo de periodo</label>
                        <select name="tipo_periodo" class="mt-1 w-full rounded-md border border-atlantia-rose/35 px-3 py-2" required>
                            <option value="mensual" @selected(old('tipo_periodo') === 'mensual')>Mensual</option>
                            <option value="quincenal" @selected(old('tipo_periodo', 'quincenal') === 'quincenal')>Quincenal</option>
                            <option value="extraordinaria" @selected(old('tipo_periodo') === 'extraordinaria')>Extraordinaria</option>
                        </select>
                    </div>
                    <div class="sm:col-span-2">
                        <label class="text-sm font-semibold text-atlantia-ink">Notas internas</label>
                        <textarea name="notas" rows="3" class="mt-1 w-full rounded-md border border-atlantia-rose/35 px-3 py-2" placeholder="Opcional">{{ old('notas') }}</textarea>
                    </div>
                </div>
                <div class="mt-5 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
                    Solo se incluiran empleados activos con salario base mayor que Q0.00.
                </div>
                <div class="mt-5 flex justify-end gap-3 border-t border-atlantia-rose/15 pt-4">
                    <button type="button" class="rounded-md border border-atlantia-rose/30 px-4 py-2 text-sm font-black text-atlantia-wine" data-close-create-modal>Cancelar</button>
                    <button type="submit" class="rounded-md bg-atlantia-wine px-4 py-2 text-sm font-black text-white">Generar nomina</button>
                </div>
            </form>
        </div>
    </div>
@endsection
