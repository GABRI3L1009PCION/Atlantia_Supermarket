@extends(auth()->user()?->isSuperAdmin() && request()->routeIs('admin.*') ? 'layouts.super-admin' : 'layouts.app')

@section('content')
    @php
        $tipos = [
            'monto_alto' => 'Monto muy alto',
            'pedido_monto_inusual' => 'Monto inusual',
            'tarjeta_no_confirmada' => 'Tarjeta sin confirmar',
            'frecuencia_alta' => 'Muchos pedidos seguidos',
            'zona_remota_monto_alto' => 'Zona remota con monto alto',
            'ml_order_fraud' => 'Detectado por la IA',
            'riesgo_operativo' => 'Riesgo operativo',
        ];
        $acciones = [
            'pedido_aprobado' => 'Todo bien: pedido aprobado',
            'cliente_contactado' => 'Se contactó al cliente',
            'revision_manual' => 'Revisado manualmente',
            'pedido_bloqueado' => 'Pedido bloqueado',
            'escalado' => 'Escalado',
        ];
        $nombreTipo = fn (?string $tipo): string => $tipos[$tipo] ?? ucfirst(str_replace('_', ' ', (string) $tipo));
        $nivel = function (float $score): array {
            return match (true) {
                $score >= 0.8 => ['Alto', 'bg-red-50 text-red-700 ring-red-200', 'bg-red-600'],
                $score >= 0.5 => ['Medio', 'bg-amber-50 text-amber-800 ring-amber-200', 'bg-amber-500'],
                default => ['Bajo', 'bg-slate-100 text-slate-600 ring-slate-200', 'bg-slate-400'],
            };
        };

        $resuelta = request('resuelta');
        $riesgoMin = request('riesgo_min');
        $tipoActivo = request('tipo');
        $vistas = [
            ['Todas', [], $resuelta === null && blank($riesgoMin)],
            ['Abiertas', ['resuelta' => '0'], $resuelta === '0' && blank($riesgoMin)],
            ['Alto riesgo', ['riesgo_min' => '0.8'], $riesgoMin === '0.8' && $resuelta === null],
            ['Resueltas', ['resuelta' => '1'], $resuelta === '1' && blank($riesgoMin)],
        ];
        $hayFiltros = $resuelta !== null || filled($riesgoMin) || filled($tipoActivo) || request('revisada') !== null;
        $tipoFrecuente = $dashboard['tipos']->sortDesc()->keys()->first();
        $tarjeta = 'rounded-2xl border border-atlantia-rose/15 bg-white shadow-[0_8px_20px_rgba(59,6,12,0.04)]';
        $campo = 'h-9 rounded-lg border border-atlantia-rose/30 bg-white px-3 text-sm focus:border-atlantia-wine focus:outline-none focus:ring-2 focus:ring-atlantia-wine/15';
    @endphp

    <section class="mx-auto max-w-7xl space-y-4 pb-6 text-atlantia-ink">
        {{-- Encabezado --}}
        <header>
            <h1 class="text-3xl font-black tracking-tight">Antifraude</h1>
            <p class="mt-0.5 text-sm text-atlantia-ink/60">El sistema revisa cada pedido y te avisa cuando algo se ve raro: montos muy altos, tarjetas sin confirmar o muchos pedidos seguidos del mismo cliente.</p>
        </header>

        {{-- Resumen --}}
        <div class="grid grid-cols-2 gap-3 xl:grid-cols-4">
            <a href="{{ route('admin.antifraude.index', ['revisada' => '0']) }}" @class([
                'rounded-2xl px-5 py-3.5 transition',
                'bg-[linear-gradient(135deg,#3a0004_0%,#5e0008_100%)] text-white shadow-lg' => $dashboard['pendientes'] > 0,
                $tarjeta.' hover:border-atlantia-wine/40' => $dashboard['pendientes'] === 0,
            ])>
                <p @class(['text-[11px] font-black uppercase tracking-wide', 'text-white/70' => $dashboard['pendientes'] > 0, 'text-atlantia-ink/50' => $dashboard['pendientes'] === 0])>Por revisar</p>
                <p @class(['mt-0.5 text-3xl font-black', 'text-atlantia-ink/30' => $dashboard['pendientes'] === 0])>{{ $dashboard['pendientes'] }}</p>
                <p @class(['text-xs', 'text-white/70' => $dashboard['pendientes'] > 0, 'text-atlantia-ink/55' => $dashboard['pendientes'] === 0])>{{ $dashboard['pendientes'] > 0 ? 'alertas sin revisar' : 'todo al día' }}</p>
            </a>
            <a href="{{ route('admin.antifraude.index', ['riesgo_min' => '0.8']) }}" @class([
                $tarjeta,
                'px-5 py-3.5 transition hover:border-atlantia-wine/40',
                '!border-red-300 !bg-red-50/60' => $dashboard['alto_riesgo'] > 0,
            ])>
                <p class="text-[11px] font-black uppercase tracking-wide text-atlantia-ink/50">Alto riesgo</p>
                <p class="mt-0.5 text-3xl font-black {{ $dashboard['alto_riesgo'] > 0 ? 'text-red-600' : 'text-atlantia-ink/30' }}">{{ $dashboard['alto_riesgo'] }}</p>
                <p class="text-xs text-atlantia-ink/55">riesgo de 80 % o más</p>
            </a>
            <a href="{{ route('admin.antifraude.index', ['resuelta' => '1']) }}" class="{{ $tarjeta }} px-5 py-3.5 transition hover:border-atlantia-wine/40">
                <p class="text-[11px] font-black uppercase tracking-wide text-atlantia-ink/50">Resueltas</p>
                <p class="mt-0.5 text-3xl font-black {{ $dashboard['resueltas'] > 0 ? 'text-emerald-600' : 'text-atlantia-ink/30' }}">{{ $dashboard['resueltas'] }}</p>
                <p class="text-xs text-atlantia-ink/55">casos cerrados</p>
            </a>
            <article class="{{ $tarjeta }} px-5 py-3.5">
                <p class="text-[11px] font-black uppercase tracking-wide text-atlantia-ink/50">Motivo más frecuente</p>
                <p class="mt-1 truncate text-lg font-black {{ $tipoFrecuente ? '' : 'text-atlantia-ink/30' }}">{{ $tipoFrecuente ? $nombreTipo($tipoFrecuente) : '—' }}</p>
                <p class="text-xs text-atlantia-ink/55">{{ $tipoFrecuente ? $dashboard['tipos'][$tipoFrecuente].' '.($dashboard['tipos'][$tipoFrecuente] === 1 ? 'alerta' : 'alertas') : 'sin alertas todavía' }}</p>
            </article>
        </div>

        {{-- Bandeja de alertas --}}
        <article class="{{ $tarjeta }} overflow-hidden" x-data="{ seleccion: [], todos(e) { this.seleccion = e.target.checked ? [...document.querySelectorAll('[data-alerta-check]')].map(c => c.value) : []; } }">
            <div class="flex flex-wrap items-center gap-2 border-b border-atlantia-rose/10 p-3">
                @foreach ($vistas as [$texto, $parametros, $activa])
                    <a href="{{ route('admin.antifraude.index', array_filter([...$parametros, 'tipo' => $tipoActivo], fn ($valor) => filled($valor))) }}" @class([
                        'rounded-full px-3 py-1 text-xs font-black transition',
                        'bg-atlantia-wine text-white' => $activa,
                        'bg-atlantia-blush/50 text-atlantia-wine hover:bg-atlantia-blush' => ! $activa,
                    ])>{{ $texto }}</a>
                @endforeach
                <form method="GET" class="ml-auto flex items-center gap-2">
                    @if ($resuelta !== null)<input type="hidden" name="resuelta" value="{{ $resuelta }}">@endif
                    @if (filled($riesgoMin))<input type="hidden" name="riesgo_min" value="{{ $riesgoMin }}">@endif
                    <select name="tipo" data-autosubmit class="{{ $campo }}" aria-label="Motivo de la alerta">
                        <option value="">Todos los motivos</option>
                        @foreach ($dashboard['tipos'] as $tipo => $cantidad)
                            <option value="{{ $tipo }}" @selected($tipoActivo === $tipo)>{{ $nombreTipo($tipo) }} ({{ $cantidad }})</option>
                        @endforeach
                    </select>
                    <button class="sr-only">Filtrar</button>
                </form>
            </div>

            {{-- Barra de acciones: aparece al seleccionar alertas --}}
            <form
                id="alertas-lote"
                method="POST"
                action="{{ route('admin.antifraude.batch-resolve') }}"
                class="hidden flex-wrap items-center gap-2 border-b border-atlantia-rose/10 bg-atlantia-blush/40 px-3 py-2"
                :class="{ 'hidden': seleccion.length === 0, 'flex': seleccion.length > 0 }"
                data-protect-submit
            >
                @csrf
                <span class="text-sm font-black text-atlantia-wine" x-text="seleccion.length + (seleccion.length === 1 ? ' seleccionada' : ' seleccionadas')"></span>
                <select name="accion" class="{{ $campo }}" aria-label="Cómo se resolvió" required>
                    @foreach ($acciones as $valor => $texto)
                        <option value="{{ $valor }}">{{ $texto }}</option>
                    @endforeach
                </select>
                <input name="notas" maxlength="1000" placeholder="Nota (opcional)" class="{{ $campo }} min-w-0 flex-1 sm:max-w-xs">
                <button class="h-9 rounded-lg bg-atlantia-wine px-4 text-sm font-black text-white hover:bg-atlantia-wine-700">Marcar como resueltas</button>
            </form>

            @if ($alerts->isEmpty())
                <div class="px-6 py-10 text-center">
                    <p class="font-black">{{ $hayFiltros ? 'Ninguna alerta coincide.' : 'No hay alertas de fraude.' }}</p>
                    <p class="mt-1 text-sm text-atlantia-ink/55">{{ $hayFiltros ? 'Prueba con otra pestaña o motivo.' : 'Cuando un pedido parezca riesgoso aparecerá aquí.' }}</p>
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-atlantia-blush/40 text-left text-[11px] font-black uppercase tracking-wide text-atlantia-ink/55">
                            <tr>
                                <th class="w-10 px-4 py-2.5"><input type="checkbox" x-on:change="todos($event)" class="h-4 w-4 rounded border-atlantia-rose/40 text-atlantia-wine" aria-label="Seleccionar todas las abiertas"></th>
                                <th class="px-3 py-2.5">Motivo</th>
                                <th class="px-3 py-2.5">Pedido</th>
                                <th class="px-3 py-2.5">Cliente</th>
                                <th class="px-3 py-2.5">Riesgo</th>
                                <th class="px-3 py-2.5">Estado</th>
                                <th class="px-4 py-2.5"><span class="sr-only">Acciones</span></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-atlantia-rose/10">
                            @foreach ($alerts as $alert)
                                @php
                                    $score = (float) $alert->score_riesgo;
                                    [$nivelTexto, $nivelClase, $nivelBarra] = $nivel($score);
                                @endphp
                                <tr class="hover:bg-atlantia-blush/20">
                                    <td class="px-4 py-2.5">
                                        @if (! $alert->resuelta)
                                            <input type="checkbox" form="alertas-lote" name="alertas[]" value="{{ $alert->uuid }}" x-model="seleccion" data-alerta-check class="h-4 w-4 rounded border-atlantia-rose/40 text-atlantia-wine" aria-label="Seleccionar alerta">
                                        @endif
                                    </td>
                                    <td class="px-3 py-2.5">
                                        <p class="font-black">{{ $nombreTipo($alert->tipo) }}</p>
                                        <p class="text-xs text-atlantia-ink/50">{{ $alert->created_at?->format('d/m/Y H:i') }}</p>
                                    </td>
                                    <td class="px-3 py-2.5 text-atlantia-ink/75">{{ $alert->pedido?->numero_pedido ?? 'Sin pedido' }}</td>
                                    <td class="px-3 py-2.5 text-atlantia-ink/75">{{ $alert->user?->name ?? 'Sin cliente' }}</td>
                                    <td class="px-3 py-2.5">
                                        <div class="flex items-center gap-2">
                                            <div class="h-1.5 w-16 overflow-hidden rounded-full bg-atlantia-rose/15"><div class="h-full rounded-full {{ $nivelBarra }}" style="width: {{ round($score * 100) }}%"></div></div>
                                            <span class="text-xs font-black">{{ round($score * 100) }} %</span>
                                            <span class="rounded-full px-2 py-0.5 text-[11px] font-black ring-1 {{ $nivelClase }}">{{ $nivelTexto }}</span>
                                        </div>
                                    </td>
                                    <td class="px-3 py-2.5">
                                        <span @class([
                                            'whitespace-nowrap rounded-full px-2.5 py-1 text-xs font-black ring-1',
                                            'bg-emerald-50 text-emerald-800 ring-emerald-200' => $alert->resuelta,
                                            'bg-sky-50 text-sky-800 ring-sky-200' => ! $alert->resuelta && $alert->revisada,
                                            'bg-amber-50 text-amber-800 ring-amber-200' => ! $alert->resuelta && ! $alert->revisada,
                                        ])>{{ $alert->resuelta ? 'Resuelta' : ($alert->revisada ? 'En revisión' : 'Por revisar') }}</span>
                                    </td>
                                    <td class="whitespace-nowrap px-4 py-2.5 text-right">
                                        <a href="{{ route('admin.antifraude.show', $alert->uuid) }}" class="text-sm font-black text-atlantia-wine hover:underline">{{ $alert->resuelta ? 'Ver' : 'Revisar' }} →</a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @if ($alerts->hasPages())
                    <div class="border-t border-atlantia-rose/10 px-4 py-3">{{ $alerts->withQueryString()->links() }}</div>
                @endif
            @endif
        </article>
    </section>
@endsection
