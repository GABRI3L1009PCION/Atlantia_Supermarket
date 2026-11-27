@extends(auth()->user()?->isSuperAdmin() ? 'layouts.super-admin' : 'layouts.app')

@section('content')
    @php
        $metrics = $cuponMetrics;
        $storeUrl = route('admin.cupones.store');
        $editarUrl = old('_editar_url');
        $formulario = [
            'codigo' => old('codigo', ''),
            'tipo' => old('tipo', 'porcentaje'),
            'valor' => old('valor', ''),
            'minimo_compra' => old('minimo_compra', ''),
            'maximo_descuento' => old('maximo_descuento', ''),
            'usos_maximos' => old('usos_maximos', ''),
            'fecha_inicio' => old('fecha_inicio', ''),
            'fecha_fin' => old('fecha_fin', ''),
            'descripcion' => old('descripcion', ''),
            'activo' => (bool) old('activo', true),
            'solo_primera_compra' => (bool) old('solo_primera_compra', false),
        ];
        $estadoCupon = function ($cupon): array {
            if (! $cupon->activo) {
                return ['Inactivo', 'bg-slate-100 text-slate-600 ring-slate-200'];
            }
            if ($cupon->fecha_fin && $cupon->fecha_fin->isPast()) {
                return ['Vencido', 'bg-red-50 text-red-700 ring-red-200'];
            }
            if ($cupon->usos_maximos && $cupon->usos_actuales >= $cupon->usos_maximos) {
                return ['Agotado', 'bg-red-50 text-red-700 ring-red-200'];
            }
            if ($cupon->fecha_inicio && $cupon->fecha_inicio->isFuture()) {
                return ['Programado', 'bg-sky-50 text-sky-800 ring-sky-200'];
            }

            return ['Vigente', 'bg-emerald-50 text-emerald-800 ring-emerald-200'];
        };
        $tarjeta = 'rounded-2xl border border-atlantia-rose/15 bg-white shadow-[0_8px_20px_rgba(59,6,12,0.04)]';
        $campo = 'h-9 w-full rounded-lg border border-atlantia-rose/30 bg-white px-3 text-sm focus:border-atlantia-wine focus:outline-none focus:ring-2 focus:ring-atlantia-wine/15';
        $etiqueta = 'mb-1 block text-[11px] font-black uppercase tracking-wide text-atlantia-ink/50';
    @endphp

    <section
        class="mx-auto max-w-7xl space-y-4 pb-6 text-atlantia-ink"
        x-data="{
            abierto: {{ $errors->any() ? 'true' : 'false' }},
            accion: @js($editarUrl ?: $storeUrl),
            editando: @js($editarUrl ? old('codigo') : null),
            f: @js($formulario),
            vacio: @js(array_merge($formulario, ['codigo' => '', 'tipo' => 'porcentaje', 'valor' => '', 'minimo_compra' => '', 'maximo_descuento' => '', 'usos_maximos' => '', 'fecha_inicio' => '', 'fecha_fin' => '', 'descripcion' => '', 'activo' => true, 'solo_primera_compra' => false])),
            nuevo() { this.f = { ...this.vacio }; this.accion = @js($storeUrl); this.editando = null; this.abierto = true; this.$nextTick(() => this.$refs.codigo.focus()); },
            editar(c) { this.f = { ...c }; this.accion = c.url; this.editando = c.codigo; this.abierto = true; window.scrollTo({ top: 0, behavior: 'smooth' }); this.$nextTick(() => this.$refs.codigo.focus()); },
            resumen() {
                if (!this.f.valor) return 'Escribe el descuento para ver cómo lo verá el cliente.';
                let texto = this.f.tipo === 'porcentaje' ? Number(this.f.valor) + ' % de descuento' : 'Q ' + Number(this.f.valor).toFixed(2) + ' de descuento';
                if (this.f.tipo === 'porcentaje' && this.f.maximo_descuento) texto += ' (hasta Q ' + Number(this.f.maximo_descuento).toFixed(2) + ')';
                texto += this.f.minimo_compra ? ' en compras desde Q ' + Number(this.f.minimo_compra).toFixed(2) : ' en cualquier compra';
                if (this.f.solo_primera_compra) texto += ', solo en la primera compra';
                if (this.f.usos_maximos) texto += '. Se puede usar ' + this.f.usos_maximos + ' veces en total';
                return texto + '.';
            },
        }"
    >
        {{-- Encabezado --}}
        <header class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h1 class="text-3xl font-black tracking-tight">Cupones y descuentos</h1>
                <p class="mt-0.5 text-sm text-atlantia-ink/60">Los cupones que creas aquí son de Atlantia Store: solo descuentan sus productos y los paga Atlantia. Cada vendedor crea y paga los suyos.</p>
                <div class="mt-2 flex gap-1.5 text-xs font-bold">
                    @foreach (['' => 'Todos', 'atlantia' => 'De Atlantia Store', 'vendedores' => 'De vendedores'] as $valor => $texto)
                        <a href="{{ route('admin.cupones.index', array_filter(['tienda' => $valor])) }}" @class(['rounded-full px-3 py-1', 'bg-atlantia-wine text-white' => (string) request('tienda', '') === $valor, 'bg-white text-atlantia-ink/65 ring-1 ring-atlantia-rose/20 hover:text-atlantia-wine' => (string) request('tienda', '') !== $valor])>{{ $texto }}</a>
                    @endforeach
                </div>
            </div>
            <button type="button" x-on:click="nuevo()" class="inline-flex items-center gap-2 rounded-xl bg-atlantia-wine px-4 py-2.5 text-sm font-black text-white transition hover:bg-atlantia-wine-700">
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M12 5v14M5 12h14" stroke="currentColor" stroke-width="2.4" stroke-linecap="round"/></svg>
                Nuevo cupón
            </button>
        </header>

        {{-- Formulario para crear o editar --}}
        <form
            method="POST"
            :action="accion"
            @class([$tarjeta, 'p-4', 'hidden' => ! $errors->any()])
            :class="{ 'hidden': !abierto }"
            data-protect-submit
            data-cupon-form
        >
            @csrf
            <template x-if="editando"><input type="hidden" name="_method" value="PUT"></template>
            <input type="hidden" name="_editar_url" :value="editando ? accion : ''">

            <div class="mb-3 flex items-center justify-between gap-3">
                <h2 class="text-base font-black" x-text="editando ? 'Editar cupón ' + editando : 'Nuevo cupón'">Nuevo cupón</h2>
                <button type="button" x-on:click="abierto = false" class="text-sm font-bold text-atlantia-ink/50 hover:text-atlantia-wine">Cerrar ✕</button>
            </div>

            <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-6">
                <label class="lg:col-span-2">
                    <span class="{{ $etiqueta }}">Código</span>
                    <input name="codigo" x-ref="codigo" x-model="f.codigo" required maxlength="60" placeholder="Ej. BIENVENIDO10" class="{{ $campo }} font-black uppercase tracking-wide">
                </label>
                <label>
                    <span class="{{ $etiqueta }}">Tipo</span>
                    <select name="tipo" x-model="f.tipo" class="{{ $campo }}">
                        <option value="porcentaje">Porcentaje (%)</option>
                        <option value="monto_fijo">Monto fijo (Q)</option>
                    </select>
                </label>
                <label>
                    <span class="{{ $etiqueta }}" x-text="f.tipo === 'porcentaje' ? 'Descuento (%)' : 'Descuento (Q)'">Descuento</span>
                    <input type="number" name="valor" x-model="f.valor" step="0.01" min="0.01" required placeholder="10" class="{{ $campo }}">
                </label>
                <label>
                    <span class="{{ $etiqueta }}">Compra mínima (Q)</span>
                    <input type="number" name="minimo_compra" x-model="f.minimo_compra" step="0.01" min="0" placeholder="Sin mínimo" class="{{ $campo }}">
                </label>
                <label>
                    <span class="{{ $etiqueta }}">Descuento máximo (Q)</span>
                    <input type="number" name="maximo_descuento" x-model="f.maximo_descuento" step="0.01" min="0.01" placeholder="Sin tope" class="{{ $campo }}">
                </label>

                <label>
                    <span class="{{ $etiqueta }}">Usos totales</span>
                    <input type="number" name="usos_maximos" x-model="f.usos_maximos" min="1" placeholder="Ilimitado" class="{{ $campo }}">
                </label>
                <label>
                    <span class="{{ $etiqueta }}">Válido desde</span>
                    <input type="date" name="fecha_inicio" x-model="f.fecha_inicio" class="{{ $campo }}">
                </label>
                <label>
                    <span class="{{ $etiqueta }}">Válido hasta</span>
                    <input type="date" name="fecha_fin" x-model="f.fecha_fin" class="{{ $campo }}">
                </label>
                <label class="sm:col-span-2 lg:col-span-3">
                    <span class="{{ $etiqueta }}">Descripción interna (opcional)</span>
                    <input name="descripcion" x-model="f.descripcion" maxlength="500" placeholder="Ej. Campaña de apertura en Facebook" class="{{ $campo }}">
                </label>
            </div>

            <div class="mt-3 flex flex-wrap items-center gap-x-5 gap-y-2">
                <input type="hidden" name="activo" value="0">
                <label class="inline-flex items-center gap-2 text-sm font-bold">
                    <input type="checkbox" name="activo" value="1" x-model="f.activo" class="h-4 w-4 rounded border-atlantia-rose text-atlantia-wine focus:ring-atlantia-wine">
                    Activo
                </label>
                <input type="hidden" name="solo_primera_compra" value="0">
                <label class="inline-flex items-center gap-2 text-sm font-bold">
                    <input type="checkbox" name="solo_primera_compra" value="1" x-model="f.solo_primera_compra" class="h-4 w-4 rounded border-atlantia-rose text-atlantia-wine focus:ring-atlantia-wine">
                    Solo primera compra
                </label>
                <p class="min-w-0 flex-1 rounded-lg bg-atlantia-blush/40 px-3 py-1.5 text-xs font-bold text-atlantia-wine" x-text="resumen()"></p>
                <button class="h-9 rounded-lg bg-atlantia-wine px-5 text-sm font-black text-white hover:bg-atlantia-wine-700" x-text="editando ? 'Guardar cambios' : 'Crear cupón'">Crear cupón</button>
            </div>

            @if ($errors->any())
                <p class="mt-2 text-xs font-bold text-red-600">{{ $errors->first() }}</p>
            @endif
        </form>

        {{-- Resumen --}}
        <div class="grid grid-cols-2 gap-3 xl:grid-cols-4">
            @foreach ([
                ['Vigentes hoy', $metrics['vigentes'], 'el cliente los puede usar ya'],
                ['Activos', $metrics['activos'], 'encendidos, con o sin fecha'],
                ['Usos acumulados', number_format($metrics['usos']), 'veces que se aplicaron'],
                ['Primera compra', $metrics['primera_compra'], 'para clientes nuevos'],
            ] as [$texto, $valor, $nota])
                <article @class([
                    'px-5 py-3.5',
                    'rounded-2xl bg-[linear-gradient(135deg,#3a0004_0%,#5e0008_100%)] text-white shadow-lg' => $loop->first,
                    $tarjeta => ! $loop->first,
                ])>
                    <p @class(['text-[11px] font-black uppercase tracking-wide', 'text-white/70' => $loop->first, 'text-atlantia-ink/50' => ! $loop->first])>{{ $texto }}</p>
                    <p class="mt-0.5 text-3xl font-black">{{ $valor }}</p>
                    <p @class(['text-xs', 'text-white/70' => $loop->first, 'text-atlantia-ink/55' => ! $loop->first])>{{ $nota }}</p>
                </article>
            @endforeach
        </div>

        {{-- Lista de cupones --}}
        <article class="{{ $tarjeta }} overflow-hidden">
            <div class="flex items-center justify-between gap-3 border-b border-atlantia-rose/10 px-4 py-3">
                <h2 class="text-base font-black">Cupones registrados</h2>
                <span class="text-xs font-bold text-atlantia-ink/50">{{ $cupones->total() }} {{ $cupones->total() === 1 ? 'cupón' : 'cupones' }}</span>
            </div>

            @if ($cupones->isEmpty())
                <div class="px-6 py-10 text-center">
                    <p class="font-black">Aún no hay cupones.</p>
                    <p class="mt-1 text-sm text-atlantia-ink/55">Una buena idea para empezar: un cupón de bienvenida solo para la primera compra.</p>
                    <button type="button" x-on:click="nuevo(); f.codigo = 'BIENVENIDO10'; f.valor = '10'; f.maximo_descuento = '25'; f.solo_primera_compra = true" class="mt-3 rounded-lg border border-atlantia-rose/30 px-4 py-2 text-sm font-black text-atlantia-wine hover:bg-atlantia-blush">
                        Crear cupón de bienvenida
                    </button>
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-atlantia-blush/40 text-left text-[11px] font-black uppercase tracking-wide text-atlantia-ink/55">
                            <tr>
                                <th class="px-4 py-2.5">Código</th>
                                <th class="px-3 py-2.5">Descuento</th>
                                <th class="px-3 py-2.5">Condiciones</th>
                                <th class="px-3 py-2.5">Usos</th>
                                <th class="px-3 py-2.5">Vigencia</th>
                                <th class="px-3 py-2.5">Estado</th>
                                <th class="px-4 py-2.5"><span class="sr-only">Acciones</span></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-atlantia-rose/10">
                            @foreach ($cupones as $cupon)
                                @php
                                    [$estadoTexto, $estadoClase] = $estadoCupon($cupon);
                                    $porcentajeUso = $cupon->usos_maximos ? min(100, round($cupon->usos_actuales / $cupon->usos_maximos * 100)) : null;
                                    $datos = [
                                        'url' => route('admin.cupones.update', $cupon),
                                        'codigo' => $cupon->codigo,
                                        'tipo' => $cupon->tipo,
                                        'valor' => (string) $cupon->valor,
                                        'minimo_compra' => (string) ($cupon->minimo_compra ?? ''),
                                        'maximo_descuento' => (string) ($cupon->maximo_descuento ?? ''),
                                        'usos_maximos' => (string) ($cupon->usos_maximos ?? ''),
                                        'fecha_inicio' => optional($cupon->fecha_inicio)->format('Y-m-d') ?? '',
                                        'fecha_fin' => optional($cupon->fecha_fin)->format('Y-m-d') ?? '',
                                        'descripcion' => (string) ($cupon->descripcion ?? ''),
                                        'activo' => (bool) $cupon->activo,
                                        'solo_primera_compra' => (bool) $cupon->solo_primera_compra,
                                    ];
                                @endphp
                                <tr class="hover:bg-atlantia-blush/20">
                                    <td class="px-4 py-2.5">
                                        <p class="font-black tracking-wide">{{ $cupon->codigo }}</p>
                                        <p class="text-[11px] font-bold text-atlantia-rose" data-tienda-cupon>{{ $cupon->vendor?->business_name ?? 'Atlantia Store' }}</p>
                                        @if ($cupon->descripcion)
                                            <p class="max-w-56 truncate text-xs text-atlantia-ink/50">{{ $cupon->descripcion }}</p>
                                        @endif
                                    </td>
                                    <td class="px-3 py-2.5 font-black text-atlantia-wine">
                                        {{ $cupon->tipo === 'porcentaje' ? rtrim(rtrim(number_format((float) $cupon->valor, 2), '0'), '.').' %' : 'Q '.number_format((float) $cupon->valor, 2) }}
                                        @if ($cupon->tipo === 'porcentaje' && $cupon->maximo_descuento)
                                            <span class="block text-xs font-bold text-atlantia-ink/50">hasta Q {{ number_format((float) $cupon->maximo_descuento, 2) }}</span>
                                        @endif
                                    </td>
                                    <td class="px-3 py-2.5 text-xs text-atlantia-ink/65">
                                        {{ $cupon->minimo_compra > 0 ? 'Desde Q '.number_format((float) $cupon->minimo_compra, 2) : 'Sin mínimo' }}
                                        @if ($cupon->solo_primera_compra)
                                            <span class="mt-0.5 block font-bold text-atlantia-wine">Solo primera compra</span>
                                        @endif
                                    </td>
                                    <td class="px-3 py-2.5">
                                        <p class="text-xs font-bold">{{ number_format($cupon->usos_actuales) }}{{ $cupon->usos_maximos ? ' de '.number_format($cupon->usos_maximos) : ' · sin límite' }}</p>
                                        @if ($porcentajeUso !== null)
                                            <div class="mt-1 h-1.5 w-24 overflow-hidden rounded-full bg-atlantia-rose/15"><div class="h-full rounded-full bg-atlantia-wine" style="width: {{ $porcentajeUso }}%"></div></div>
                                        @endif
                                    </td>
                                    <td class="px-3 py-2.5 text-xs text-atlantia-ink/65">
                                        @if ($cupon->fecha_inicio || $cupon->fecha_fin)
                                            {{ $cupon->fecha_inicio ? $cupon->fecha_inicio->format('d/m/Y') : 'Ya' }} – {{ $cupon->fecha_fin ? $cupon->fecha_fin->format('d/m/Y') : 'sin fin' }}
                                        @else
                                            Sin fecha límite
                                        @endif
                                    </td>
                                    <td class="px-3 py-2.5"><span class="rounded-full px-2.5 py-1 text-xs font-black ring-1 {{ $estadoClase }}">{{ $estadoTexto }}</span></td>
                                    <td class="px-4 py-2.5">
                                        <div class="flex items-center justify-end gap-3">
                                            <button type="button" x-on:click="editar(@js($datos))" class="text-sm font-black text-atlantia-wine hover:underline">Editar</button>
                                            <form method="POST" action="{{ route('admin.cupones.destroy', $cupon) }}" data-confirm="¿Eliminar el cupón {{ $cupon->codigo }}? Los clientes ya no podrán usarlo.">
                                                @csrf
                                                @method('DELETE')
                                                <button class="text-sm font-bold text-atlantia-ink/45 hover:text-red-600">Eliminar</button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @if ($cupones->hasPages())
                    <div class="border-t border-atlantia-rose/10 px-4 py-3">{{ $cupones->links() }}</div>
                @endif
            @endif
        </article>
    </section>
@endsection
