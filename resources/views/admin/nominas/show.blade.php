@extends(auth()->user()?->isSuperAdmin() && request()->routeIs('admin.*') ? 'layouts.super-admin' : 'layouts.app')

@section('content')
    @php($editable = $nomina->estado === 'borrador')

    <section class="-mx-4 -my-6 bg-atlantia-cream/35 px-4 py-6 text-atlantia-ink sm:-mx-6 sm:px-6 lg:-mx-8 lg:px-8">
        <div class="mx-auto max-w-7xl rounded-2xl border border-atlantia-rose/20 bg-white p-6 shadow-sm lg:p-8">
            <header class="flex flex-col gap-5 lg:flex-row lg:items-start lg:justify-between">
                <div>
                    <p class="text-xs font-black uppercase tracking-[0.18em] text-atlantia-rose">Detalle de nomina</p>
                    <h1 class="mt-2 text-3xl font-black text-atlantia-ink">{{ optional($nomina->periodo_inicio)->format('d/m/Y') }} - {{ optional($nomina->periodo_fin)->format('d/m/Y') }}</h1>
                    <p class="mt-2 text-sm text-atlantia-ink/60">{{ ucfirst($nomina->tipo_periodo) }} · generada por {{ $nomina->generadaPor?->name ?? 'usuario no disponible' }}</p>
                </div>
                <div class="flex flex-wrap gap-3">
                    <a href="{{ route('admin.nominas.index') }}" class="rounded-md border border-atlantia-rose/35 bg-white px-4 py-2.5 text-sm font-black text-atlantia-wine">Volver</a>
                    @if ($editable)
                        <form method="POST" action="{{ route('admin.nominas.pay', $nomina->uuid) }}">
                            @csrf
                            @method('PATCH')
                            <button type="submit" class="rounded-md bg-atlantia-wine px-4 py-2.5 text-sm font-black text-white" onclick="return confirm('Confirma que la nomina ya fue pagada. Despues no podras modificarla.')">
                                Marcar como pagada
                            </button>
                        </form>
                    @endif
                </div>
            </header>

            <div class="mt-6 grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                <article class="rounded-xl border border-atlantia-rose/20 bg-white p-4 shadow-sm">
                    <p class="text-xs font-black uppercase tracking-wide text-atlantia-ink/45">Salarios base</p>
                    <p class="mt-2 text-2xl font-black text-atlantia-ink">Q{{ number_format((float) $nomina->total_bruto, 2) }}</p>
                </article>
                <article class="rounded-xl border border-emerald-200 bg-white p-4 shadow-sm">
                    <p class="text-xs font-black uppercase tracking-wide text-atlantia-ink/45">Bonificaciones</p>
                    <p class="mt-2 text-2xl font-black text-emerald-600">Q{{ number_format((float) $nomina->total_bonificaciones, 2) }}</p>
                </article>
                <article class="rounded-xl border border-rose-200 bg-white p-4 shadow-sm">
                    <p class="text-xs font-black uppercase tracking-wide text-atlantia-ink/45">Descuentos</p>
                    <p class="mt-2 text-2xl font-black text-rose-600">Q{{ number_format((float) $nomina->total_descuentos, 2) }}</p>
                </article>
                <article class="rounded-xl border border-atlantia-rose/20 bg-atlantia-blush/25 p-4 shadow-sm">
                    <p class="text-xs font-black uppercase tracking-wide text-atlantia-ink/45">Total neto</p>
                    <p class="mt-2 text-2xl font-black text-atlantia-wine">Q{{ number_format((float) $nomina->total_neto, 2) }}</p>
                </article>
            </div>

            @if (! $editable)
                <div class="mt-5 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-800">
                    Nomina pagada {{ optional($nomina->pagada_at)->format('d/m/Y H:i') }} por {{ $nomina->pagadaPor?->name ?? 'usuario no disponible' }}. Los montos quedaron bloqueados.
                </div>
            @endif

            <div class="mt-6 overflow-x-auto rounded-xl border border-atlantia-rose/20">
                <table class="min-w-[960px] w-full text-sm">
                    <thead class="bg-atlantia-cream/70 text-left text-xs uppercase tracking-wide text-atlantia-ink/55">
                        <tr>
                            <th class="px-4 py-3">Empleado</th>
                            <th class="px-4 py-3">Salario base</th>
                            <th class="px-4 py-3">Bonificacion</th>
                            <th class="px-4 py-3">Descuento</th>
                            <th class="px-4 py-3">Total neto</th>
                            <th class="px-4 py-3">Observaciones</th>
                            @if ($editable)<th class="px-4 py-3 text-right">Accion</th>@endif
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-atlantia-rose/15 bg-white">
                        @foreach ($nomina->detalles as $detalle)
                            <tr>
                                <td class="px-4 py-3">
                                    <p class="font-black text-atlantia-ink">{{ $detalle->empleado?->user?->name ?? 'Empleado no disponible' }}</p>
                                    <p class="mt-0.5 text-xs text-atlantia-ink/55">{{ $detalle->empleado?->codigo_empleado }}</p>
                                </td>
                                @if ($editable)
                                    <form method="POST" action="{{ route('admin.nominas.detalles.update', [$nomina->uuid, $detalle]) }}">
                                        @csrf
                                        @method('PUT')
                                        <td class="px-4 py-3 font-black text-atlantia-ink">Q{{ number_format((float) $detalle->salario_base, 2) }}</td>
                                        <td class="px-4 py-3"><input name="bonificaciones" type="number" step="0.01" min="0" value="{{ $detalle->bonificaciones }}" class="w-28 rounded-md border border-atlantia-rose/30 px-3 py-2"></td>
                                        <td class="px-4 py-3"><input name="descuentos" type="number" step="0.01" min="0" value="{{ $detalle->descuentos }}" class="w-28 rounded-md border border-atlantia-rose/30 px-3 py-2"></td>
                                        <td class="px-4 py-3 font-black text-atlantia-wine">Q{{ number_format((float) $detalle->total_neto, 2) }}</td>
                                        <td class="px-4 py-3"><input name="observaciones" type="text" value="{{ $detalle->observaciones }}" class="w-56 rounded-md border border-atlantia-rose/30 px-3 py-2" placeholder="Opcional"></td>
                                        <td class="px-4 py-3 text-right"><button type="submit" class="rounded-md bg-atlantia-wine px-4 py-2 text-xs font-black text-white">Guardar</button></td>
                                    </form>
                                @else
                                    <td class="px-4 py-3 font-black">Q{{ number_format((float) $detalle->salario_base, 2) }}</td>
                                    <td class="px-4 py-3 text-emerald-700">Q{{ number_format((float) $detalle->bonificaciones, 2) }}</td>
                                    <td class="px-4 py-3 text-rose-700">Q{{ number_format((float) $detalle->descuentos, 2) }}</td>
                                    <td class="px-4 py-3 font-black text-atlantia-wine">Q{{ number_format((float) $detalle->total_neto, 2) }}</td>
                                    <td class="px-4 py-3 text-atlantia-ink/65">{{ $detalle->observaciones ?: 'Sin observaciones' }}</td>
                                @endif
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </section>
@endsection
