@extends(auth()->user()?->isSuperAdmin() && request()->routeIs('admin.*') ? 'layouts.super-admin' : 'layouts.app')

@section('content')
    @php
        $estados = [
            'borrador' => ['Borrador', 'bg-amber-50 text-amber-800 ring-amber-200'],
            'pagada' => ['Pagada', 'bg-emerald-50 text-emerald-800 ring-emerald-200'],
            'anulada' => ['Anulada', 'bg-slate-100 text-slate-600 ring-slate-200'],
        ];
        $tipos = ['quincenal' => 'Quincenal', 'mensual' => 'Mensual', 'extraordinaria' => 'Extraordinaria'];
        $estadoActivo = request('estado');

        /* Sugiere la quincena en curso como periodo por defecto. */
        $hoy = now();
        $inicioSugerido = $hoy->day <= 15 ? $hoy->copy()->startOfMonth() : $hoy->copy()->startOfMonth()->addDays(15);
        $finSugerido = $hoy->day <= 15 ? $hoy->copy()->startOfMonth()->addDays(14) : $hoy->copy()->endOfMonth();

        $sinSalarios = $dashboard['salarios_configurados'] === 0;
        $tarjeta = 'rounded-2xl border border-atlantia-rose/15 bg-white shadow-[0_8px_20px_rgba(59,6,12,0.04)]';
        $campo = 'h-9 w-full rounded-lg border border-atlantia-rose/30 bg-white px-3 text-sm focus:border-atlantia-wine focus:outline-none focus:ring-2 focus:ring-atlantia-wine/15';
        $etiqueta = 'mb-1 block text-[11px] font-black uppercase tracking-wide text-atlantia-ink/50';
    @endphp

    <section class="mx-auto max-w-7xl space-y-4 pb-6 text-atlantia-ink" x-data="{ nueva: {{ $errors->any() ? 'true' : 'false' }} }">
        {{-- Encabezado --}}
        <header class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h1 class="text-3xl font-black tracking-tight">Nómina de empleados</h1>
                <p class="mt-0.5 text-sm text-atlantia-ink/60">Genera la planilla del periodo, revisa bonos y descuentos, y registra cuándo se pagó.</p>
            </div>
            <button type="button" x-on:click="nueva = !nueva" class="inline-flex items-center gap-2 rounded-xl bg-atlantia-wine px-4 py-2.5 text-sm font-black text-white transition hover:bg-atlantia-wine-700" :aria-expanded="nueva.toString()">
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M12 5v14M5 12h14" stroke="currentColor" stroke-width="2.4" stroke-linecap="round"/></svg>
                Generar nómina
            </button>
        </header>

        {{-- Formulario para generar la planilla --}}
        <form
            method="POST"
            action="{{ route('admin.nominas.store') }}"
            @class([$tarjeta, 'p-4', 'hidden' => ! $errors->any()])
            :class="{ 'hidden': !nueva }"
            data-protect-submit
            data-nueva-nomina
        >
            @csrf
            <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-[10rem_10rem_10rem_1fr_auto] lg:items-end">
                <label>
                    <span class="{{ $etiqueta }}">Tipo</span>
                    <select name="tipo_periodo" class="{{ $campo }}" required>
                        @foreach ($tipos as $valor => $texto)
                            <option value="{{ $valor }}" @selected(old('tipo_periodo', 'quincenal') === $valor)>{{ $texto }}</option>
                        @endforeach
                    </select>
                </label>
                <label>
                    <span class="{{ $etiqueta }}">Desde</span>
                    <input type="date" name="periodo_inicio" value="{{ old('periodo_inicio', $inicioSugerido->format('Y-m-d')) }}" class="{{ $campo }}" required>
                </label>
                <label>
                    <span class="{{ $etiqueta }}">Hasta</span>
                    <input type="date" name="periodo_fin" value="{{ old('periodo_fin', $finSugerido->format('Y-m-d')) }}" class="{{ $campo }}" required>
                </label>
                <label>
                    <span class="{{ $etiqueta }}">Nota interna (opcional)</span>
                    <input name="notas" maxlength="500" value="{{ old('notas') }}" placeholder="Ej. Incluye bono navideño" class="{{ $campo }}">
                </label>
                <div class="flex gap-2">
                    <button type="button" x-on:click="nueva = false" class="h-9 rounded-lg border border-atlantia-rose/30 px-3 text-sm font-black text-atlantia-wine hover:bg-atlantia-blush">Cancelar</button>
                    <button class="h-9 rounded-lg bg-atlantia-wine px-4 text-sm font-black text-white hover:bg-atlantia-wine-700">Generar</button>
                </div>
            </div>
            @if ($errors->any())
                <p class="mt-2 text-xs font-bold text-red-600">{{ $errors->first() }}</p>
            @endif
            @unless ($sinSalarios)
                <p class="mt-2 text-xs text-atlantia-ink/55">Incluye a {{ $dashboard['salarios_configurados'] === 1 ? 'el empleado activo' : 'los '.$dashboard['salarios_configurados'].' empleados activos' }} con salario configurado. Queda como borrador para que revises antes de pagar.</p>
            @endunless
        </form>

        @if ($sinSalarios)
            <p class="flex flex-wrap items-center gap-x-2 rounded-xl border border-amber-200 bg-amber-50 px-4 py-2.5 text-sm text-amber-900">
                <strong>{{ $dashboard['empleados_activos'] === 0 ? 'Aún no tienes empleados activos.' : 'Ningún empleado activo tiene salario configurado.' }}</strong>
                <span>Una nómina generada ahora saldría vacía.</span>
                <a href="{{ route('admin.empleados.index') }}" class="font-black text-atlantia-wine hover:underline">Ir a Empleados →</a>
            </p>
        @endif

        {{-- Resumen --}}
        <div class="grid grid-cols-2 gap-3 xl:grid-cols-[1.4fr_1fr_1fr_1fr]">
            <article class="col-span-2 rounded-2xl bg-[linear-gradient(135deg,#3a0004_0%,#5e0008_100%)] px-5 py-3.5 text-white shadow-lg xl:col-span-1">
                <p class="text-[11px] font-black uppercase tracking-wide text-white/70">Pendiente de pago</p>
                <p class="mt-0.5 text-3xl font-black">Q {{ number_format($dashboard['pendiente_pago'], 2) }}</p>
                <p class="text-xs text-white/70">neto de las nóminas en borrador</p>
            </article>
            <article class="{{ $tarjeta }} px-4 py-3.5">
                <p class="text-[11px] font-black uppercase tracking-wide text-atlantia-ink/50">Empleados activos</p>
                <p class="mt-0.5 text-3xl font-black">{{ $dashboard['empleados_activos'] }}</p>
                <p class="text-xs text-atlantia-ink/55">{{ $dashboard['salarios_configurados'] }} con salario</p>
            </article>
            @foreach (['borrador' => ['borradores', 'En borrador', 'text-amber-600'], 'pagada' => ['pagadas', 'Pagadas', 'text-emerald-600']] as $estado => [$clave, $texto, $color])
                <a href="{{ route('admin.nominas.index', $estadoActivo === $estado ? [] : ['estado' => $estado]) }}" @class([
                    $tarjeta,
                    'px-4 py-3.5 transition hover:border-atlantia-wine/40',
                    '!border-atlantia-wine ring-2 ring-atlantia-wine/15' => $estadoActivo === $estado,
                ])>
                    <p class="text-[11px] font-black uppercase tracking-wide text-atlantia-ink/50">{{ $texto }}</p>
                    <p class="mt-0.5 text-3xl font-black {{ $dashboard[$clave] > 0 ? $color : 'text-atlantia-ink/30' }}">{{ $dashboard[$clave] }}</p>
                    <p class="text-xs text-atlantia-ink/55">{{ $estado === 'borrador' ? 'por revisar o pagar' : 'periodos cerrados' }}</p>
                </a>
            @endforeach
        </div>

        {{-- Historial --}}
        <article class="{{ $tarjeta }} overflow-hidden">
            <div class="flex flex-wrap items-center gap-2 border-b border-atlantia-rose/10 px-4 py-3">
                <h2 class="mr-2 text-base font-black">Historial de planillas</h2>
                @foreach (['' => 'Todas', 'borrador' => 'Borradores', 'pagada' => 'Pagadas', 'anulada' => 'Anuladas'] as $valor => $texto)
                    <a href="{{ route('admin.nominas.index', $valor === '' ? [] : ['estado' => $valor]) }}" @class([
                        'rounded-full px-3 py-1 text-xs font-black transition',
                        'bg-atlantia-wine text-white' => (string) $estadoActivo === $valor,
                        'bg-atlantia-blush/50 text-atlantia-wine hover:bg-atlantia-blush' => (string) $estadoActivo !== $valor,
                    ])>{{ $texto }}</a>
                @endforeach
                <span class="ml-auto text-xs font-bold text-atlantia-ink/50">{{ $nominas->total() }} {{ $nominas->total() === 1 ? 'planilla' : 'planillas' }}</span>
            </div>

            @if ($nominas->isEmpty())
                <div class="px-6 py-10 text-center">
                    <p class="font-black">{{ $estadoActivo ? 'No hay planillas con ese estado.' : 'Aún no hay nóminas registradas.' }}</p>
                    <p class="mt-1 text-sm text-atlantia-ink/55">
                        {{ $estadoActivo ? 'Prueba con otro filtro.' : 'Cuando tengas empleados con salario, usa «Generar nómina» para crear la primera quincena.' }}
                    </p>
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-atlantia-blush/40 text-left text-[11px] font-black uppercase tracking-wide text-atlantia-ink/55">
                            <tr>
                                <th class="px-4 py-2.5">Periodo</th>
                                <th class="px-3 py-2.5">Tipo</th>
                                <th class="px-3 py-2.5 text-right">Empleados</th>
                                <th class="px-3 py-2.5 text-right">Salarios</th>
                                <th class="px-3 py-2.5 text-right">Neto a pagar</th>
                                <th class="px-3 py-2.5">Estado</th>
                                <th class="px-4 py-2.5"><span class="sr-only">Acciones</span></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-atlantia-rose/10">
                            @foreach ($nominas as $nomina)
                                @php
                                    [$estadoTexto, $estadoClase] = $estados[$nomina->estado] ?? [ucfirst($nomina->estado), 'bg-slate-100 text-slate-600 ring-slate-200'];
                                @endphp
                                <tr class="hover:bg-atlantia-blush/20">
                                    <td class="px-4 py-2.5 font-black">{{ optional($nomina->periodo_inicio)->format('d/m/Y') }} – {{ optional($nomina->periodo_fin)->format('d/m/Y') }}</td>
                                    <td class="px-3 py-2.5 text-atlantia-ink/70">{{ $tipos[$nomina->tipo_periodo] ?? ucfirst($nomina->tipo_periodo) }}</td>
                                    <td class="px-3 py-2.5 text-right text-atlantia-ink/70">{{ $nomina->detalles_count }}</td>
                                    <td class="px-3 py-2.5 text-right text-atlantia-ink/70">Q {{ number_format((float) $nomina->total_bruto, 2) }}</td>
                                    <td class="px-3 py-2.5 text-right font-black text-atlantia-wine">Q {{ number_format((float) $nomina->total_neto, 2) }}</td>
                                    <td class="px-3 py-2.5"><span class="rounded-full px-2.5 py-1 text-xs font-black ring-1 {{ $estadoClase }}">{{ $estadoTexto }}</span></td>
                                    <td class="px-4 py-2.5 text-right">
                                        <a href="{{ route('admin.nominas.show', $nomina->uuid) }}" class="text-sm font-black text-atlantia-wine hover:underline">{{ $nomina->estado === 'borrador' ? 'Revisar y pagar' : 'Ver detalle' }} →</a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @if ($nominas->hasPages())
                    <div class="border-t border-atlantia-rose/10 px-4 py-3">{{ $nominas->links() }}</div>
                @endif
            @endif
        </article>
    </section>
@endsection
