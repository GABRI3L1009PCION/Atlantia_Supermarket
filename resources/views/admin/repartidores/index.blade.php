@extends(auth()->user()?->isSuperAdmin() && request()->routeIs('admin.*') ? 'layouts.super-admin' : 'layouts.app')

@section('content')
    @php
        $showCreateModal = $errors->any();
        $estadoActual = request('estado');
        $cuentas = [
            'active' => ['Activa', 'bg-emerald-50 text-emerald-700'],
            'inactive' => ['Inactiva', 'bg-slate-100 text-slate-600'],
            'suspended' => ['Suspendida', 'bg-amber-50 text-amber-800'],
        ];
        $disponibilidad = [
            'available' => ['Disponible', 'bg-emerald-500'],
            'busy' => ['En entrega', 'bg-atlantia-wine'],
            'paused' => ['En pausa', 'bg-amber-500'],
            'emergency' => ['Emergencia', 'bg-red-600'],
            'offline' => ['Desconectado', 'bg-slate-300'],
        ];
        $vehiculos = ['moto' => 'Moto', 'motocicleta' => 'Moto', 'bicicleta' => 'Bicicleta', 'carro' => 'Carro', 'auto' => 'Carro', 'pie' => 'A pie'];
        $filtros = ['' => 'Todos', 'active' => 'Activos', 'inactive' => 'Inactivos', 'suspended' => 'Suspendidos'];
    @endphp

    <section class="mx-auto max-w-7xl space-y-4 pb-10">
        {{-- Encabezado --}}
        <header class="flex flex-wrap items-end justify-between gap-3">
            <div>
                <h1 class="text-3xl font-black tracking-tight text-atlantia-ink">Repartidores</h1>
                <p class="mt-1 text-sm text-atlantia-ink/60">Quién está disponible, cuánto ha entregado y cuánto ha ganado este mes.</p>
            </div>
            <button type="button" class="inline-flex items-center gap-2 rounded-xl bg-atlantia-wine px-4 py-2.5 text-sm font-black text-white transition hover:bg-atlantia-wine-700" data-open-create-modal>
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M12 5v14M5 12h14" stroke="currentColor" stroke-width="2.4" stroke-linecap="round"/></svg>
                Nuevo repartidor
            </button>
        </header>

        {{-- Resumen --}}
        <div class="grid grid-cols-2 gap-3 lg:grid-cols-4">
            @foreach ([
                ['Repartidores', number_format($resumen['total']), 'registrados'],
                ['Disponibles ahora', number_format($resumen['disponibles']), 'conectados y libres'],
                ['En camino', number_format($resumen['en_ruta']), 'entregas en curso'],
                ['Entregas de hoy', number_format($resumen['entregas_hoy']), 'completadas'],
            ] as [$etiqueta, $valor, $nota])
                <article class="rounded-2xl border border-atlantia-rose/15 bg-white px-4 py-3 shadow-[0_8px_20px_rgba(59,6,12,0.04)]">
                    <p class="text-[11px] font-black uppercase tracking-wide text-atlantia-ink/50">{{ $etiqueta }}</p>
                    <p class="mt-0.5 text-2xl font-black text-atlantia-ink">{{ $valor }}</p>
                    <p class="text-xs text-atlantia-ink/50">{{ $nota }}</p>
                </article>
            @endforeach
        </div>

        <div class="overflow-hidden rounded-2xl border border-atlantia-rose/15 bg-white">
            {{-- Filtros y búsqueda --}}
            <form method="GET" class="flex flex-col gap-3 border-b border-atlantia-rose/10 p-3 lg:flex-row lg:items-center">
                <div class="flex gap-1.5 overflow-x-auto">
                    @foreach ($filtros as $valor => $texto)
                        <a href="{{ route('admin.repartidores.index', array_filter(['estado' => $valor, 'q' => request('q')])) }}"
                           @class(['shrink-0 rounded-lg border px-3 py-2 text-sm font-black transition', 'border-atlantia-wine bg-atlantia-wine text-white' => (string) $estadoActual === (string) $valor, 'border-atlantia-rose/25 bg-white text-atlantia-wine hover:bg-atlantia-blush' => (string) $estadoActual !== (string) $valor])>{{ $texto }}</a>
                    @endforeach
                </div>
                @if ($estadoActual)
                    <input type="hidden" name="estado" value="{{ $estadoActual }}">
                @endif
                <div class="relative flex-1">
                    <span class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-atlantia-ink/40">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M11 19a8 8 0 1 0 0-16 8 8 0 0 0 0 16ZM20.5 20.5l-3.8-3.8" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
                    </span>
                    <input type="search" name="q" value="{{ request('q') }}" placeholder="Buscar por nombre, correo o teléfono" class="h-10 w-full rounded-lg border border-atlantia-rose/30 pl-9 pr-3 text-sm outline-none transition focus:border-atlantia-wine focus:ring-2 focus:ring-atlantia-rose/20">
                </div>
                <button type="submit" class="h-10 shrink-0 rounded-lg border border-atlantia-rose/30 px-4 text-sm font-black text-atlantia-wine hover:bg-atlantia-blush">Buscar</button>
            </form>

            {{-- Lista --}}
            <div class="overflow-x-auto">
                <table class="w-full min-w-[820px] text-sm">
                    <thead>
                        <tr class="border-b border-atlantia-rose/10 bg-atlantia-cream/50 text-left text-[11px] font-black uppercase tracking-wide text-atlantia-ink/50">
                            <th class="px-4 py-2.5">Repartidor</th>
                            <th class="px-4 py-2.5">Ahora</th>
                            <th class="px-4 py-2.5 text-right">Entregas del mes</th>
                            <th class="px-4 py-2.5 text-right">Ganado este mes</th>
                            <th class="px-4 py-2.5 text-right">Efectivo por liquidar</th>
                            <th class="px-4 py-2.5">Cuenta</th>
                            <th class="px-4 py-2.5"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-atlantia-rose/10">
                        @forelse ($repartidores as $repartidor)
                            @php
                                $perfil = $repartidor->courierProfile;
                                $iniciales = collect(explode(' ', (string) $repartidor->name))->filter()->take(2)->map(fn ($p) => mb_strtoupper(mb_substr($p, 0, 1)))->join('') ?: 'R';
                                [$dispTexto, $dispColor] = $perfil ? ($disponibilidad[$perfil->availability_status] ?? $disponibilidad['offline']) : ['Sin perfil de reparto', 'bg-slate-300'];
                                [$cuentaTexto, $cuentaClase] = $cuentas[$repartidor->status] ?? [ucfirst((string) $repartidor->status), 'bg-slate-100 text-slate-600'];
                                $mes = $delMes->get($repartidor->id);
                                $rutas = (int) ($enRuta[$repartidor->id] ?? 0);
                                $efectivo = (float) ($repartidor->courierWallet?->cash_balance ?? 0);
                                $vehiculo = $perfil?->vehicle_type ? ($vehiculos[strtolower($perfil->vehicle_type)] ?? ucfirst($perfil->vehicle_type)) : null;
                            @endphp
                            <tr class="transition hover:bg-atlantia-cream/60">
                                <td class="px-4 py-3">
                                    <div class="flex items-center gap-3">
                                        <span class="grid h-10 w-10 shrink-0 place-items-center rounded-xl bg-atlantia-blush text-sm font-black text-atlantia-wine">{{ $iniciales }}</span>
                                        <div class="min-w-0">
                                            <p class="truncate font-black text-atlantia-ink">{{ $repartidor->name }}</p>
                                            <p class="truncate text-xs text-atlantia-ink/55">{{ $repartidor->phone ?: $repartidor->email }}{{ $vehiculo ? ' · '.$vehiculo : '' }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-4 py-3">
                                    <p class="flex items-center gap-1.5 text-xs font-bold text-atlantia-ink/75"><span class="h-2 w-2 rounded-full {{ $dispColor }}"></span>{{ $dispTexto }}</p>
                                    <p class="text-[11px] text-atlantia-ink/50">
                                        @if ($rutas > 0)
                                            {{ $rutas }} {{ $rutas === 1 ? 'entrega en curso' : 'entregas en curso' }}
                                        @elseif ($perfil?->last_online_at)
                                            Conectado {{ $perfil->last_online_at->diffForHumans() }}
                                        @else
                                            Nunca se ha conectado
                                        @endif
                                    </p>
                                </td>
                                <td class="px-4 py-3 text-right font-black text-atlantia-ink">{{ number_format((int) ($mes->entregas ?? 0)) }}</td>
                                <td class="px-4 py-3 text-right font-black text-atlantia-ink">Q {{ number_format((float) ($mes->ganancia ?? 0), 2) }}</td>
                                <td @class(['px-4 py-3 text-right font-black', 'text-amber-700' => $efectivo > 0, 'text-atlantia-ink/40' => $efectivo <= 0])>Q {{ number_format($efectivo, 2) }}</td>
                                <td class="px-4 py-3"><span class="rounded-full px-2.5 py-1 text-xs font-black {{ $cuentaClase }}">{{ $cuentaTexto }}</span></td>
                                <td class="px-4 py-3 text-right">
                                    <a href="{{ route('admin.repartidores.show', $repartidor) }}" class="rounded-lg border border-atlantia-rose/30 px-3 py-1.5 text-xs font-black text-atlantia-wine transition hover:bg-atlantia-blush">Gestionar</a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-4 py-12 text-center">
                                    <p class="font-black text-atlantia-ink">{{ request('q') || $estadoActual ? 'No hay repartidores con ese filtro.' : 'Todavía no tienes repartidores.' }}</p>
                                    <p class="mt-1 text-sm text-atlantia-ink/55">Registra a tus repartidores con "Nuevo repartidor".</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div>{{ $repartidores->links() }}</div>
    </section>

    <div
        class="{{ $showCreateModal ? 'flex' : 'hidden' }} fixed inset-0 z-50 items-center justify-center bg-slate-950/55 px-4 py-6 backdrop-blur-sm"
        data-create-modal
        role="dialog"
        aria-modal="true"
        aria-labelledby="delivery-create-title"
    >
        <div class="max-h-[92vh] w-full max-w-2xl overflow-hidden rounded-2xl border border-atlantia-rose/25 bg-white shadow-2xl">
            <div class="flex items-start justify-between gap-4 border-b border-atlantia-rose/15 px-6 py-5">
                <div>
                    <h2 id="delivery-create-title" class="mt-1 text-2xl font-black text-atlantia-ink">Crear nuevo repartidor</h2>
                    <p class="mt-1 text-sm text-atlantia-ink/60">Crea la cuenta con la que el repartidor entra a la app de entregas.</p>
                </div>
                <button type="button" class="rounded-md border border-atlantia-rose/30 px-3 py-2 text-sm font-black text-atlantia-wine hover:bg-atlantia-blush" data-close-create-modal>
                    Cerrar
                </button>
            </div>

            <form method="POST" action="{{ route('admin.repartidores.store') }}" class="max-h-[calc(92vh-92px)] overflow-y-auto px-6 py-5">
                @csrf

                @if ($errors->any())
                    <div class="mb-5 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-semibold text-red-800">
                        Revisa los campos marcados antes de crear el repartidor.
                    </div>
                @endif

                <div class="grid gap-4 md:grid-cols-2">
                    <div class="md:col-span-2">
                        <label class="text-sm font-semibold text-atlantia-ink">Nombre</label>
                        <input name="name" type="text" value="{{ old('name') }}" class="mt-1 w-full rounded-md border border-atlantia-rose/35 px-3 py-2 outline-none transition focus:border-atlantia-wine focus:ring-2 focus:ring-atlantia-rose/20" required>
                        @error('name') <p class="mt-1 text-xs font-semibold text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div class="md:col-span-2">
                        <label class="text-sm font-semibold text-atlantia-ink">Correo</label>
                        <input name="email" type="email" value="{{ old('email') }}" class="mt-1 w-full rounded-md border border-atlantia-rose/35 px-3 py-2 outline-none transition focus:border-atlantia-wine focus:ring-2 focus:ring-atlantia-rose/20" required>
                        @error('email') <p class="mt-1 text-xs font-semibold text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="text-sm font-semibold text-atlantia-ink">Teléfono</label>
                        <input name="phone" type="text" value="{{ old('phone') }}" class="mt-1 w-full rounded-md border border-atlantia-rose/35 px-3 py-2 outline-none transition focus:border-atlantia-wine focus:ring-2 focus:ring-atlantia-rose/20">
                        @error('phone') <p class="mt-1 text-xs font-semibold text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="text-sm font-semibold text-atlantia-ink">Estado</label>
                        <select name="status" class="mt-1 w-full rounded-md border border-atlantia-rose/35 px-3 py-2 outline-none transition focus:border-atlantia-wine focus:ring-2 focus:ring-atlantia-rose/20">
                            <option value="active" @selected(old('status', 'active') === 'active')>Activo</option>
                            <option value="inactive" @selected(old('status') === 'inactive')>Inactivo</option>
                            <option value="suspended" @selected(old('status') === 'suspended')>Suspendido</option>
                        </select>
                        @error('status') <p class="mt-1 text-xs font-semibold text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="text-sm font-semibold text-atlantia-ink">Contraseña</label>
                        <input name="password" type="password" class="mt-1 w-full rounded-md border border-atlantia-rose/35 px-3 py-2 outline-none transition focus:border-atlantia-wine focus:ring-2 focus:ring-atlantia-rose/20" required>
                        @error('password') <p class="mt-1 text-xs font-semibold text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="text-sm font-semibold text-atlantia-ink">Confirmar contraseña</label>
                        <input name="password_confirmation" type="password" class="mt-1 w-full rounded-md border border-atlantia-rose/35 px-3 py-2 outline-none transition focus:border-atlantia-wine focus:ring-2 focus:ring-atlantia-rose/20" required>
                    </div>
                </div>

                <div class="mt-6 flex flex-col-reverse gap-3 border-t border-atlantia-rose/15 pt-5 sm:flex-row sm:justify-end">
                    <button type="button" class="rounded-md border border-atlantia-rose/30 px-4 py-2 text-sm font-black text-atlantia-wine hover:bg-atlantia-blush" data-close-create-modal>
                        Cancelar
                    </button>
                    <x-ui.button type="submit">Crear repartidor</x-ui.button>
                </div>
            </form>
        </div>
    </div>
@endsection
