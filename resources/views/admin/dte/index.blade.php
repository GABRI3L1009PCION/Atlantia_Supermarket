@extends(auth()->user()?->isSuperAdmin() && request()->routeIs('admin.*') ? 'layouts.super-admin' : 'layouts.app')

@section('content')
    @php
        $estados = [
            'certificado' => ['Certificado', 'bg-emerald-50 text-emerald-800 ring-emerald-200'],
            'rechazado' => ['Rechazado', 'bg-red-50 text-red-700 ring-red-200'],
            'anulado' => ['Anulado', 'bg-slate-100 text-slate-600 ring-slate-200'],
            'borrador' => ['Sin certificar', 'bg-amber-50 text-amber-800 ring-amber-200'],
        ];
        $tipos = [
            'FACT' => 'Factura',
            'FCAM' => 'Factura cambiaria',
            'FPEQ' => 'Factura pequeño contribuyente',
            'NCRE' => 'Nota de crédito',
            'NDEB' => 'Nota de débito',
        ];

        /* Modo real de conexion con el certificador, en vez de un aviso fijo. */
        $simulado = (bool) config('services.infile.mock', app()->environment(['local', 'testing']));
        $sandbox = str_contains((string) config('services.infile.base_url', 'sandbox'), 'sandbox');
        [$modoTexto, $modoClase] = $simulado
            ? ['Modo simulado: las facturas no se envían a la SAT', 'border-amber-200 bg-amber-50 text-amber-800']
            : ($sandbox
                ? ['Conectado a Infile en ambiente de pruebas', 'border-sky-200 bg-sky-50 text-sky-800']
                : ['Conectado a Infile · SAT en producción', 'border-emerald-200 bg-emerald-50 text-emerald-800']);

        $filtros = request()->only(['vendor_id', 'estado', 'tipo_dte', 'fecha_desde', 'fecha_hasta']);
        $estadoActivo = $filtros['estado'] ?? null;
        $hayFiltros = collect($filtros)->filter(fn ($valor) => filled($valor))->isNotEmpty();
        $urlEstado = fn (?string $estado): string => route('admin.dte.index', array_filter([...$filtros, 'estado' => $estado], fn ($valor) => filled($valor)));
        $tarjeta = 'rounded-2xl border border-atlantia-rose/15 bg-white shadow-[0_8px_20px_rgba(59,6,12,0.04)]';
        $campo = 'h-9 rounded-lg border border-atlantia-rose/30 bg-white px-3 text-sm focus:border-atlantia-wine focus:outline-none focus:ring-2 focus:ring-atlantia-wine/15';
    @endphp

    <section class="mx-auto max-w-7xl space-y-4 pb-6 text-atlantia-ink">
        {{-- Encabezado --}}
        <header class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h1 class="text-3xl font-black tracking-tight">Facturas electrónicas (FEL)</h1>
                <p class="mt-0.5 text-sm text-atlantia-ink/60">Las facturas que se emiten a los clientes por cada pedido, certificadas ante la SAT.</p>
            </div>
            <span class="inline-flex items-center gap-2 rounded-full border px-3 py-1.5 text-xs font-black {{ $modoClase }}">
                <span class="h-1.5 w-1.5 rounded-full bg-current"></span>
                {{ $modoTexto }}
            </span>
        </header>

        {{-- Resumen: los estados filtran la lista --}}
        <div class="grid grid-cols-2 gap-3 xl:grid-cols-[1.4fr_1fr_1fr_1fr]">
            <article class="col-span-2 rounded-2xl bg-[linear-gradient(135deg,#3a0004_0%,#5e0008_100%)] px-5 py-3.5 text-white shadow-lg xl:col-span-1">
                <p class="text-[11px] font-black uppercase tracking-wide text-white/70">Monto facturado</p>
                <p class="mt-0.5 text-3xl font-black">Q {{ number_format((float) $dashboard['monto_total'], 2) }}</p>
                <p class="text-xs text-white/70">{{ $hayFiltros ? 'con los filtros aplicados' : 'en todas las facturas' }}</p>
            </article>
            @foreach ([
                'certificado' => ['certificados', 'Certificadas', 'text-emerald-600', 'válidas ante la SAT'],
                'rechazado' => ['rechazados', 'Rechazadas', 'text-red-600', 'ábrelas para reintentar'],
                'anulado' => ['anulados', 'Anuladas', 'text-slate-600', 'sin validez fiscal'],
            ] as $estado => [$clave, $texto, $color, $nota])
                @php
                    $cantidad = (int) $dashboard[$clave];
                @endphp
                <a href="{{ $urlEstado($estadoActivo === $estado ? null : $estado) }}" @class([
                    $tarjeta,
                    'px-4 py-3.5 transition hover:border-atlantia-wine/40',
                    '!border-atlantia-wine ring-2 ring-atlantia-wine/15' => $estadoActivo === $estado,
                    '!border-red-300 !bg-red-50/60' => $estado === 'rechazado' && $cantidad > 0 && $estadoActivo !== $estado,
                ])>
                    <p class="text-[11px] font-black uppercase tracking-wide text-atlantia-ink/50">{{ $texto }}</p>
                    <p class="mt-0.5 text-3xl font-black {{ $cantidad > 0 ? $color : 'text-atlantia-ink/30' }}">{{ number_format($cantidad) }}</p>
                    <p class="text-xs text-atlantia-ink/55">{{ $nota }}</p>
                </a>
            @endforeach
        </div>

        {{-- Lista de facturas --}}
        <article class="{{ $tarjeta }} overflow-hidden">
            <form method="GET" class="flex flex-wrap items-center gap-2 border-b border-atlantia-rose/10 p-3">
                <select name="vendor_id" data-autosubmit class="{{ $campo }} min-w-0 flex-1 sm:max-w-60" aria-label="Tienda">
                    <option value="">Todas las tiendas</option>
                    @foreach ($vendors as $vendor)
                        <option value="{{ $vendor->id }}" @selected((string) request('vendor_id') === (string) $vendor->id)>{{ $vendor->business_name }}</option>
                    @endforeach
                </select>
                <select name="estado" data-autosubmit class="{{ $campo }}" aria-label="Estado">
                    <option value="">Todos los estados</option>
                    @foreach ($estados as $clave => [$texto])
                        <option value="{{ $clave }}" @selected($estadoActivo === $clave)>{{ $texto }}</option>
                    @endforeach
                </select>
                <select name="tipo_dte" data-autosubmit class="{{ $campo }}" aria-label="Tipo de documento">
                    <option value="">Todos los tipos</option>
                    @foreach ($tipos as $clave => $texto)
                        <option value="{{ $clave }}" @selected(request('tipo_dte') === $clave)>{{ $texto }}</option>
                    @endforeach
                </select>
                <span class="flex items-center gap-1.5 text-xs font-bold text-atlantia-ink/50">
                    Del
                    <input type="date" name="fecha_desde" value="{{ request('fecha_desde') }}" data-autosubmit class="{{ $campo }}" aria-label="Fecha desde">
                    al
                    <input type="date" name="fecha_hasta" value="{{ request('fecha_hasta') }}" data-autosubmit class="{{ $campo }}" aria-label="Fecha hasta">
                </span>
                <button class="sr-only">Filtrar</button>
                @if ($hayFiltros)
                    <a href="{{ route('admin.dte.index') }}" class="text-sm font-bold text-atlantia-wine hover:underline">Quitar filtros</a>
                @endif
                <span class="ml-auto text-xs font-bold text-atlantia-ink/50">{{ $dtes->total() }} {{ $dtes->total() === 1 ? 'factura' : 'facturas' }}</span>
            </form>

            @if ($dtes->isEmpty())
                <div class="px-6 py-10 text-center">
                    <p class="font-black">{{ $hayFiltros ? 'Ninguna factura coincide con los filtros.' : 'Aún no hay facturas emitidas.' }}</p>
                    <p class="mt-1 text-sm text-atlantia-ink/55">{{ $hayFiltros ? 'Prueba con otra tienda, estado o fechas.' : 'Se emiten solas cuando un cliente hace un pedido.' }}</p>
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-atlantia-blush/40 text-left text-[11px] font-black uppercase tracking-wide text-atlantia-ink/55">
                            <tr>
                                <th class="px-4 py-2.5">Factura</th>
                                <th class="px-3 py-2.5">Fecha</th>
                                <th class="px-3 py-2.5">Tienda</th>
                                <th class="px-3 py-2.5">Pedido</th>
                                <th class="px-3 py-2.5">Tipo</th>
                                <th class="px-3 py-2.5 text-right">Monto</th>
                                <th class="px-3 py-2.5">Estado</th>
                                <th class="px-4 py-2.5"><span class="sr-only">Acciones</span></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-atlantia-rose/10">
                            @foreach ($dtes as $dte)
                                @php
                                    [$estadoTexto, $estadoClase] = $estados[$dte->estado] ?? [ucfirst($dte->estado), 'bg-atlantia-blush text-atlantia-wine ring-atlantia-rose/20'];
                                    $fecha = $dte->fecha_certificacion ?? $dte->created_at;
                                    $accion = in_array($dte->estado, ['rechazado', 'borrador'], true) ? 'Reintentar' : 'Ver';
                                @endphp
                                <tr class="hover:bg-atlantia-blush/20">
                                    <td class="px-4 py-2.5">
                                        <p class="font-black">{{ $dte->numero_dte }}</p>
                                        <p class="text-xs text-atlantia-ink/45" title="{{ $dte->uuid_sat }}">{{ $dte->uuid_sat ? 'SAT '.\Illuminate\Support\Str::upper(\Illuminate\Support\Str::before($dte->uuid_sat, '-')) : 'Sin autorización SAT' }}</p>
                                    </td>
                                    <td class="whitespace-nowrap px-3 py-2.5 text-atlantia-ink/70">{{ optional($fecha)->format('d/m/Y') }}</td>
                                    <td class="max-w-48 truncate px-3 py-2.5 text-atlantia-ink/75">{{ $dte->vendor?->business_name ?? 'Sin tienda' }}</td>
                                    <td class="whitespace-nowrap px-3 py-2.5 text-atlantia-ink/70">{{ $dte->pedido?->numero_pedido ?? '—' }}</td>
                                    <td class="px-3 py-2.5"><span class="rounded-md bg-atlantia-blush/50 px-2 py-0.5 text-xs font-black text-atlantia-wine" title="{{ $tipos[$dte->tipo_dte] ?? $dte->tipo_dte }}">{{ $dte->tipo_dte }}</span></td>
                                    <td class="whitespace-nowrap px-3 py-2.5 text-right font-black text-atlantia-wine">Q {{ number_format((float) $dte->monto_total, 2) }}</td>
                                    <td class="px-3 py-2.5"><span class="whitespace-nowrap rounded-full px-2.5 py-1 text-xs font-black ring-1 {{ $estadoClase }}">{{ $estadoTexto }}</span></td>
                                    <td class="whitespace-nowrap px-4 py-2.5 text-right">
                                        <a href="{{ route('admin.dte.show', $dte->uuid) }}" @class([
                                            'text-sm font-black hover:underline',
                                            'text-red-700' => $accion === 'Reintentar',
                                            'text-atlantia-wine' => $accion === 'Ver',
                                        ])>{{ $accion }} →</a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @if ($dtes->hasPages())
                    <div class="border-t border-atlantia-rose/10 px-4 py-3">{{ $dtes->withQueryString()->links() }}</div>
                @endif
            @endif
        </article>
    </section>
@endsection
