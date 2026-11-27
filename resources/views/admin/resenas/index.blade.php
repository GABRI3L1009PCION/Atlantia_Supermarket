@extends(auth()->user()?->isSuperAdmin() && request()->routeIs('admin.*') ? 'layouts.super-admin' : 'layouts.app')

@section('content')
    @php
        $aprobada = request('aprobada');
        $sospechosa = request('flagged_ml');
        $vistas = [
            'todas' => ['Todas', [], $aprobada === null && $sospechosa === null],
            'pendientes' => ['Por revisar', ['aprobada' => '0'], $aprobada === '0' && $sospechosa === null],
            'aprobadas' => ['Aprobadas', ['aprobada' => '1'], $aprobada === '1' && $sospechosa === null],
            'sospechosas' => ['Sospechosas', ['flagged_ml' => '1'], $sospechosa === '1' && $aprobada === null],
        ];
        $busqueda = request('q');
        $hayFiltros = filled($busqueda) || $aprobada !== null || $sospechosa !== null;
        $tarjeta = 'rounded-2xl border border-atlantia-rose/15 bg-white shadow-[0_8px_20px_rgba(59,6,12,0.04)]';
        $campo = 'h-9 rounded-lg border border-atlantia-rose/30 bg-white px-3 text-sm focus:border-atlantia-wine focus:outline-none focus:ring-2 focus:ring-atlantia-wine/15';
    @endphp

    <section class="mx-auto max-w-7xl space-y-4 pb-6 text-atlantia-ink">
        {{-- Encabezado --}}
        <header>
            <h1 class="text-3xl font-black tracking-tight">Reseñas</h1>
            <p class="mt-0.5 text-sm text-atlantia-ink/60">Lo que opinan los clientes de los productos. Solo las aprobadas se muestran en la tienda.</p>
        </header>

        {{-- Resumen --}}
        <div class="grid grid-cols-2 gap-3 xl:grid-cols-4">
            <a href="{{ route('admin.resenas.index', ['aprobada' => '0']) }}" @class([
                'rounded-2xl px-5 py-3.5 transition',
                'bg-[linear-gradient(135deg,#3a0004_0%,#5e0008_100%)] text-white shadow-lg' => $dashboard['pendientes'] > 0,
                $tarjeta.' hover:border-atlantia-wine/40' => $dashboard['pendientes'] === 0,
            ])>
                <p @class(['text-[11px] font-black uppercase tracking-wide', 'text-white/70' => $dashboard['pendientes'] > 0, 'text-atlantia-ink/50' => $dashboard['pendientes'] === 0])>Por revisar</p>
                <p @class(['mt-0.5 text-3xl font-black', 'text-atlantia-ink/30' => $dashboard['pendientes'] === 0])>{{ $dashboard['pendientes'] }}</p>
                <p @class(['text-xs', 'text-white/70' => $dashboard['pendientes'] > 0, 'text-atlantia-ink/55' => $dashboard['pendientes'] === 0])>{{ $dashboard['pendientes'] > 0 ? 'esperan tu aprobación' : 'todo al día' }}</p>
            </a>
            <a href="{{ route('admin.resenas.index', ['aprobada' => '1']) }}" class="{{ $tarjeta }} px-5 py-3.5 transition hover:border-atlantia-wine/40">
                <p class="text-[11px] font-black uppercase tracking-wide text-atlantia-ink/50">Publicadas</p>
                <p class="mt-0.5 text-3xl font-black {{ $dashboard['aprobadas'] > 0 ? 'text-emerald-600' : 'text-atlantia-ink/30' }}">{{ $dashboard['aprobadas'] }}</p>
                <p class="text-xs text-atlantia-ink/55">visibles en la tienda</p>
            </a>
            <article class="{{ $tarjeta }} px-5 py-3.5">
                <p class="text-[11px] font-black uppercase tracking-wide text-atlantia-ink/50">Calificación promedio</p>
                @if ($dashboard['promedio'] !== null)
                    <p class="mt-0.5 text-3xl font-black">{{ number_format($dashboard['promedio'], 1) }} <span class="text-xl text-amber-500">★</span></p>
                    <p class="text-xs text-atlantia-ink/55">de las publicadas, sobre 5</p>
                @else
                    <p class="mt-0.5 text-3xl font-black text-atlantia-ink/30">—</p>
                    <p class="text-xs text-atlantia-ink/55">aún no hay publicadas</p>
                @endif
            </article>
            <a href="{{ route('admin.resenas.index', ['flagged_ml' => '1']) }}" @class([
                $tarjeta,
                'px-5 py-3.5 transition hover:border-atlantia-wine/40',
                '!border-red-300 !bg-red-50/60' => $dashboard['flagged_ml'] > 0,
            ])>
                <p class="text-[11px] font-black uppercase tracking-wide text-atlantia-ink/50">Sospechosas</p>
                <p class="mt-0.5 text-3xl font-black {{ $dashboard['flagged_ml'] > 0 ? 'text-red-600' : 'text-atlantia-ink/30' }}">{{ $dashboard['flagged_ml'] }}</p>
                <p class="text-xs text-atlantia-ink/55">marcadas por la IA como posible spam o falsas</p>
            </a>
        </div>

        {{-- Bandeja de moderacion --}}
        <article class="{{ $tarjeta }} overflow-hidden" x-data="{ seleccion: [], todos(e) { this.seleccion = e.target.checked ? [...document.querySelectorAll('[data-resena-check]')].map(c => c.value) : []; } }">
            <div class="flex flex-wrap items-center gap-2 border-b border-atlantia-rose/10 p-3">
                @foreach ($vistas as [$texto, $parametros, $activa])
                    <a href="{{ route('admin.resenas.index', array_filter([...$parametros, 'q' => $busqueda], fn ($valor) => filled($valor))) }}" @class([
                        'rounded-full px-3 py-1 text-xs font-black transition',
                        'bg-atlantia-wine text-white' => $activa,
                        'bg-atlantia-blush/50 text-atlantia-wine hover:bg-atlantia-blush' => ! $activa,
                    ])>{{ $texto }}</a>
                @endforeach
                <form method="GET" class="ml-auto flex min-w-0 flex-1 items-center gap-2 sm:max-w-sm">
                    @if ($aprobada !== null)<input type="hidden" name="aprobada" value="{{ $aprobada }}">@endif
                    @if ($sospechosa !== null)<input type="hidden" name="flagged_ml" value="{{ $sospechosa }}">@endif
                    <input type="search" name="q" value="{{ $busqueda }}" placeholder="Buscar por texto, producto o cliente" class="{{ $campo }} w-full min-w-0">
                    <button class="sr-only">Buscar</button>
                </form>
            </div>

            {{-- Barra de acciones: aparece al seleccionar reseñas --}}
            <form
                id="resenas-lote"
                method="POST"
                action="{{ route('admin.resenas.batch-moderate') }}"
                class="hidden flex-wrap items-center gap-2 border-b border-atlantia-rose/10 bg-atlantia-blush/40 px-3 py-2"
                :class="{ 'hidden': seleccion.length === 0, 'flex': seleccion.length > 0 }"
                data-protect-submit
            >
                @csrf
                <span class="text-sm font-black text-atlantia-wine" x-text="seleccion.length + (seleccion.length === 1 ? ' seleccionada' : ' seleccionadas')"></span>
                <input name="notas" maxlength="1000" placeholder="Nota interna (opcional)" class="{{ $campo }} min-w-0 flex-1 sm:max-w-xs">
                <button name="accion" value="aprobar" class="h-9 rounded-lg bg-emerald-600 px-3 text-sm font-black text-white hover:bg-emerald-700">Aprobar</button>
                <button name="accion" value="rechazar" class="h-9 rounded-lg border border-atlantia-rose/30 bg-white px-3 text-sm font-black text-atlantia-wine hover:bg-atlantia-blush">Rechazar</button>
                <button name="accion" value="marcar_ml" class="h-9 rounded-lg border border-red-200 bg-white px-3 text-sm font-black text-red-700 hover:bg-red-50">Marcar sospechosas</button>
            </form>

            @if ($resenas->isEmpty())
                <div class="px-6 py-10 text-center">
                    <p class="font-black">{{ $hayFiltros ? 'Ninguna reseña coincide.' : 'Aún no hay reseñas.' }}</p>
                    <p class="mt-1 text-sm text-atlantia-ink/55">{{ $hayFiltros ? 'Prueba con otra pestaña o búsqueda.' : 'Aparecerán aquí cuando los clientes califiquen un producto.' }}</p>
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-atlantia-blush/40 text-left text-[11px] font-black uppercase tracking-wide text-atlantia-ink/55">
                            <tr>
                                <th class="w-10 px-4 py-2.5"><input type="checkbox" x-on:change="todos($event)" class="h-4 w-4 rounded border-atlantia-rose/40 text-atlantia-wine" aria-label="Seleccionar todas"></th>
                                <th class="px-3 py-2.5">Reseña</th>
                                <th class="px-3 py-2.5">Producto</th>
                                <th class="px-3 py-2.5">Cliente</th>
                                <th class="px-3 py-2.5">Estado</th>
                                <th class="px-4 py-2.5"><span class="sr-only">Acciones</span></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-atlantia-rose/10">
                            @foreach ($resenas as $resena)
                                <tr class="align-top hover:bg-atlantia-blush/20">
                                    <td class="px-4 py-3">
                                        <input type="checkbox" form="resenas-lote" name="resenas[]" value="{{ $resena->uuid }}" x-model="seleccion" data-resena-check class="h-4 w-4 rounded border-atlantia-rose/40 text-atlantia-wine" aria-label="Seleccionar reseña">
                                    </td>
                                    <td class="max-w-md px-3 py-2.5">
                                        <p class="text-sm tracking-tight text-amber-500" aria-label="{{ $resena->calificacion }} de 5 estrellas">{{ str_repeat('★', (int) $resena->calificacion) }}<span class="text-atlantia-ink/15">{{ str_repeat('★', 5 - (int) $resena->calificacion) }}</span></p>
                                        @if ($resena->titulo)
                                            <p class="font-black">{{ $resena->titulo }}</p>
                                        @endif
                                        <p class="line-clamp-2 text-xs text-atlantia-ink/60">{{ $resena->contenido }}</p>
                                    </td>
                                    <td class="px-3 py-2.5">
                                        <p class="font-bold text-atlantia-ink/80">{{ $resena->producto?->nombre ?? 'Producto eliminado' }}</p>
                                        <p class="text-xs text-atlantia-ink/50">{{ $resena->producto?->vendor?->business_name }}</p>
                                    </td>
                                    <td class="px-3 py-2.5">
                                        <p class="text-atlantia-ink/75">{{ $resena->cliente?->name ?? 'Cliente' }}</p>
                                        <p class="text-xs text-atlantia-ink/45">{{ $resena->created_at?->format('d/m/Y') }}</p>
                                    </td>
                                    <td class="px-3 py-2.5">
                                        <div class="flex flex-wrap gap-1.5">
                                            <span @class([
                                                'whitespace-nowrap rounded-full px-2.5 py-1 text-xs font-black ring-1',
                                                'bg-emerald-50 text-emerald-800 ring-emerald-200' => $resena->aprobada,
                                                'bg-amber-50 text-amber-800 ring-amber-200' => ! $resena->aprobada,
                                            ])>{{ $resena->aprobada ? 'Publicada' : 'Por revisar' }}</span>
                                            @if ($resena->flagged_ml)
                                                <span class="whitespace-nowrap rounded-full bg-red-50 px-2.5 py-1 text-xs font-black text-red-700 ring-1 ring-red-200">Sospechosa</span>
                                            @endif
                                        </div>
                                    </td>
                                    <td class="whitespace-nowrap px-4 py-2.5 text-right">
                                        <a href="{{ route('admin.resenas.show', $resena->uuid) }}" class="text-sm font-black text-atlantia-wine hover:underline">Revisar →</a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @if ($resenas->hasPages())
                    <div class="border-t border-atlantia-rose/10 px-4 py-3">{{ $resenas->links() }}</div>
                @endif
            @endif
        </article>
    </section>
@endsection
