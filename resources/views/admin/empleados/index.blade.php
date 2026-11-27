@extends(auth()->user()?->isSuperAdmin() && request()->routeIs('admin.*') ? 'layouts.super-admin' : 'layouts.app')

@section('content')
    @php
        $permisosDisponibles = ['contacto', 'transferencias', 'moderacion', 'reportes', 'soporte'];
        $departamentos = \App\Models\Empleado::DEPARTAMENTOS;
        $roles = \App\Models\Empleado::ROLES;
        $selectedPermisos = collect(old('permisos_operativos', []));
        $showCreateModal = $errors->any();
        $estadoActual = request('estado');
        $estados = [
            'active' => ['Activo', 'bg-emerald-50 text-emerald-700'],
            'inactive' => ['Inactivo', 'bg-slate-100 text-slate-600'],
            'suspended' => ['Suspendido', 'bg-amber-50 text-amber-800'],
        ];
        $filtros = ['' => 'Todos', 'active' => 'Activos', 'inactive' => 'Inactivos', 'suspended' => 'Suspendidos'];
    @endphp

    <section class="mx-auto max-w-7xl space-y-4 pb-10">
        {{-- Encabezado --}}
        <header class="flex flex-wrap items-end justify-between gap-3">
            <div>
                <h1 class="text-3xl font-black tracking-tight text-atlantia-ink">Empleados</h1>
                <p class="mt-1 text-sm text-atlantia-ink/60">El equipo interno que opera Atlantia Store: bodega, atención, finanzas y logística.</p>
            </div>
            <button type="button" class="inline-flex items-center gap-2 rounded-xl bg-atlantia-wine px-4 py-2.5 text-sm font-black text-white transition hover:bg-atlantia-wine-700" data-open-create-modal>
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M12 5v14M5 12h14" stroke="currentColor" stroke-width="2.4" stroke-linecap="round"/></svg>
                Nuevo empleado
            </button>
        </header>

        {{-- Resumen --}}
        <div class="grid grid-cols-2 gap-3 lg:grid-cols-4">
            @foreach ([
                ['Empleados', number_format($resumen['total']), 'registrados'],
                ['Activos', number_format($resumen['activos']), 'trabajando ahora'],
                ['Inactivos o suspendidos', number_format($resumen['inactivos']), 'sin acceso al panel'],
                ['Nómina mensual', 'Q '.number_format($resumen['nomina'], 2), 'salario base de activos'],
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
                        <a href="{{ route('admin.empleados.index', array_filter(['estado' => $valor, 'q' => request('q')])) }}"
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
                    <input type="search" name="q" value="{{ request('q') }}" placeholder="Buscar por nombre, correo, código o puesto" class="h-10 w-full rounded-lg border border-atlantia-rose/30 pl-9 pr-3 text-sm outline-none transition focus:border-atlantia-wine focus:ring-2 focus:ring-atlantia-rose/20">
                </div>
                <button type="submit" class="h-10 shrink-0 rounded-lg border border-atlantia-rose/30 px-4 text-sm font-black text-atlantia-wine hover:bg-atlantia-blush">Buscar</button>
            </form>

            {{-- Lista --}}
            <div class="overflow-x-auto">
                <table class="w-full min-w-[760px] text-sm">
                    <thead>
                        <tr class="border-b border-atlantia-rose/10 bg-atlantia-cream/50 text-left text-[11px] font-black uppercase tracking-wide text-atlantia-ink/50">
                            <th class="px-4 py-2.5">Empleado</th>
                            <th class="px-4 py-2.5">Rol</th>
                            <th class="px-4 py-2.5">Departamento y puesto</th>
                            <th class="px-4 py-2.5">Desde</th>
                            <th class="px-4 py-2.5">Estado</th>
                            <th class="px-4 py-2.5"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-atlantia-rose/10">
                        @forelse ($empleados as $empleado)
                            @php
                                $nombre = $empleado->user?->name ?? 'Sin usuario';
                                $iniciales = collect(explode(' ', $nombre))->filter()->take(2)->map(fn ($p) => mb_strtoupper(mb_substr($p, 0, 1)))->join('') ?: 'E';
                                $rolClave = $empleado->user?->roles->pluck('name')->first(fn ($r) => array_key_exists($r, $roles));
                                $otrosRoles = $empleado->user?->roles->pluck('name')->reject(fn ($r) => array_key_exists($r, $roles))->values() ?? collect();
                                [$estadoTexto, $estadoClase] = $estados[$empleado->status] ?? [ucfirst((string) $empleado->status), 'bg-slate-100 text-slate-600'];
                            @endphp
                            <tr class="transition hover:bg-atlantia-cream/60">
                                <td class="px-4 py-3">
                                    <div class="flex items-center gap-3">
                                        <span class="grid h-10 w-10 shrink-0 place-items-center rounded-xl bg-atlantia-blush text-sm font-black text-atlantia-wine">{{ $iniciales }}</span>
                                        <div class="min-w-0">
                                            <p class="truncate font-black text-atlantia-ink">{{ $nombre }}</p>
                                            <p class="truncate text-xs text-atlantia-ink/55">{{ $empleado->codigo_empleado }} · {{ $empleado->user?->email }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-4 py-3">
                                    @if ($rolClave)
                                        <span class="rounded-full bg-atlantia-blush px-2.5 py-1 text-xs font-black text-atlantia-wine">{{ $roles[$rolClave] }}</span>
                                    @else
                                        <span class="rounded-full bg-amber-50 px-2.5 py-1 text-xs font-black text-amber-800" title="Esta cuenta no tiene un rol de empleado, así que no puede entrar al panel de empleados.">Sin rol de empleado</span>
                                    @endif
                                    @if ($otrosRoles->isNotEmpty())
                                        <p class="mt-1 text-[11px] text-atlantia-ink/50">También: {{ $otrosRoles->join(', ') }}</p>
                                    @endif
                                </td>
                                <td class="px-4 py-3">
                                    <p class="font-semibold text-atlantia-ink">{{ $departamentos[$empleado->departamento] ?? ucfirst(str_replace('_', ' ', (string) $empleado->departamento)) }}</p>
                                    <p class="text-xs text-atlantia-ink/55">{{ $empleado->puesto }}</p>
                                </td>
                                <td class="px-4 py-3 text-xs text-atlantia-ink/65">{{ optional($empleado->fecha_contratacion)->translatedFormat('j M Y') ?? '—' }}</td>
                                <td class="px-4 py-3"><span class="rounded-full px-2.5 py-1 text-xs font-black {{ $estadoClase }}">{{ $estadoTexto }}</span></td>
                                <td class="px-4 py-3 text-right">
                                    <a href="{{ route('admin.empleados.show', $empleado->uuid) }}" class="rounded-lg border border-atlantia-rose/30 px-3 py-1.5 text-xs font-black text-atlantia-wine transition hover:bg-atlantia-blush">Gestionar</a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-4 py-12 text-center">
                                    <p class="font-black text-atlantia-ink">{{ request('q') || $estadoActual ? 'No hay empleados con ese filtro.' : 'Todavía no tienes empleados.' }}</p>
                                    <p class="mt-1 text-sm text-atlantia-ink/55">Cuando contrates a alguien, regístralo con "Nuevo empleado" y elige su rol.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div>{{ $empleados->withQueryString()->links() }}</div>
    </section>

    <div
        class="{{ $showCreateModal ? 'flex' : 'hidden' }} fixed inset-0 z-50 items-center justify-center bg-slate-950/55 px-4 py-6 backdrop-blur-sm"
        data-create-modal
        role="dialog"
        aria-modal="true"
        aria-labelledby="employee-create-title"
    >
        <div class="max-h-[92vh] w-full max-w-4xl overflow-hidden rounded-2xl border border-atlantia-rose/25 bg-white shadow-2xl">
            <div class="flex items-start justify-between gap-4 border-b border-atlantia-rose/15 px-6 py-5">
                <div>
                    <h2 id="employee-create-title" class="mt-1 text-2xl font-black text-atlantia-ink">Crear nuevo empleado</h2>
                    <p class="mt-1 text-sm text-atlantia-ink/60">Registra sus datos, su rol y lo que puede hacer en el sistema.</p>
                </div>
                <button type="button" class="rounded-md border border-atlantia-rose/30 px-3 py-2 text-sm font-black text-atlantia-wine hover:bg-atlantia-blush" data-close-create-modal>
                    Cerrar
                </button>
            </div>

            <form method="POST" action="{{ route('admin.empleados.store') }}" class="max-h-[calc(92vh-92px)] overflow-y-auto px-6 py-5">
                @csrf

                @if ($errors->any())
                    <div class="mb-5 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-semibold text-red-800">
                        Revisa los campos marcados antes de crear el empleado.
                    </div>
                @endif

                <div class="grid gap-4 md:grid-cols-2">
                    <div class="md:col-span-2">
                        <label class="text-sm font-semibold text-atlantia-ink">Nombre completo</label>
                        <input name="name" type="text" value="{{ old('name') }}" class="mt-1 w-full rounded-md border border-atlantia-rose/35 px-3 py-2 outline-none transition focus:border-atlantia-wine focus:ring-2 focus:ring-atlantia-rose/20" required>
                        @error('name') <p class="mt-1 text-xs font-semibold text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div class="md:col-span-2">
                        <label class="text-sm font-semibold text-atlantia-ink">Correo electrónico</label>
                        <input name="email" type="email" value="{{ old('email') }}" class="mt-1 w-full rounded-md border border-atlantia-rose/35 px-3 py-2 outline-none transition focus:border-atlantia-wine focus:ring-2 focus:ring-atlantia-rose/20" required>
                        @error('email') <p class="mt-1 text-xs font-semibold text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="text-sm font-semibold text-atlantia-ink">Teléfono</label>
                        <input name="phone" type="text" value="{{ old('phone') }}" class="mt-1 w-full rounded-md border border-atlantia-rose/35 px-3 py-2 outline-none transition focus:border-atlantia-wine focus:ring-2 focus:ring-atlantia-rose/20">
                        @error('phone') <p class="mt-1 text-xs font-semibold text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="text-sm font-semibold text-atlantia-ink">Teléfono interno</label>
                        <input name="telefono_interno" type="text" value="{{ old('telefono_interno') }}" class="mt-1 w-full rounded-md border border-atlantia-rose/35 px-3 py-2 outline-none transition focus:border-atlantia-wine focus:ring-2 focus:ring-atlantia-rose/20">
                        @error('telefono_interno') <p class="mt-1 text-xs font-semibold text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="text-sm font-semibold text-atlantia-ink">Código</label>
                        <input name="codigo_empleado" type="text" value="{{ old('codigo_empleado') }}" class="mt-1 w-full rounded-md border border-atlantia-rose/35 px-3 py-2 outline-none transition focus:border-atlantia-wine focus:ring-2 focus:ring-atlantia-rose/20" required>
                        @error('codigo_empleado') <p class="mt-1 text-xs font-semibold text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="text-sm font-semibold text-atlantia-ink">Fecha de contratación</label>
                        <input name="fecha_contratacion" type="date" value="{{ old('fecha_contratacion') }}" class="mt-1 w-full rounded-md border border-atlantia-rose/35 px-3 py-2 outline-none transition focus:border-atlantia-wine focus:ring-2 focus:ring-atlantia-rose/20" required>
                        @error('fecha_contratacion') <p class="mt-1 text-xs font-semibold text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="text-sm font-semibold text-atlantia-ink">Departamento</label>
                        <select name="departamento" class="mt-1 w-full rounded-md border border-atlantia-rose/35 px-3 py-2 outline-none transition focus:border-atlantia-wine focus:ring-2 focus:ring-atlantia-rose/20" required>
                            @foreach ($departamentos as $value => $label)
                                <option value="{{ $value }}" @selected(old('departamento') === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('departamento') <p class="mt-1 text-xs font-semibold text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="text-sm font-semibold text-atlantia-ink">Rol en el sistema</label>
                        <select name="rol" class="mt-1 w-full rounded-md border border-atlantia-rose/35 px-3 py-2 outline-none transition focus:border-atlantia-wine focus:ring-2 focus:ring-atlantia-rose/20" required>
                            @foreach ($roles as $value => $label)
                                <option value="{{ $value }}" @selected(old('rol', 'empleado') === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                        <p class="mt-1 text-xs text-atlantia-ink/55">Define qué ve al entrar al panel de empleados.</p>
                        @error('rol') <p class="mt-1 text-xs font-semibold text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="text-sm font-semibold text-atlantia-ink">Estado</label>
                        <select name="status" class="mt-1 w-full rounded-md border border-atlantia-rose/35 px-3 py-2 outline-none transition focus:border-atlantia-wine focus:ring-2 focus:ring-atlantia-rose/20" required>
                            <option value="active" @selected(old('status', 'active') === 'active')>Activo</option>
                            <option value="inactive" @selected(old('status') === 'inactive')>Inactivo</option>
                            <option value="suspended" @selected(old('status') === 'suspended')>Suspendido</option>
                        </select>
                        @error('status') <p class="mt-1 text-xs font-semibold text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div class="md:col-span-2">
                        <label class="text-sm font-semibold text-atlantia-ink">Puesto</label>
                        <input name="puesto" type="text" value="{{ old('puesto') }}" class="mt-1 w-full rounded-md border border-atlantia-rose/35 px-3 py-2 outline-none transition focus:border-atlantia-wine focus:ring-2 focus:ring-atlantia-rose/20" required>
                        @error('puesto') <p class="mt-1 text-xs font-semibold text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div class="md:col-span-2">
                        <label class="text-sm font-semibold text-atlantia-ink">Salario base mensual</label>
                        <div class="mt-1 flex overflow-hidden rounded-md border border-atlantia-rose/35 bg-white focus-within:border-atlantia-wine focus-within:ring-2 focus-within:ring-atlantia-rose/20">
                            <span class="grid w-12 place-items-center bg-atlantia-blush text-sm font-black text-atlantia-wine">Q</span>
                            <input name="salario_base" type="number" step="0.01" min="0" value="{{ old('salario_base', 0) }}" class="w-full border-0 px-3 py-2 outline-none" required>
                        </div>
                        <p class="mt-1 text-xs text-atlantia-ink/55">La nómina solo incluye empleados activos con salario mayor que cero.</p>
                        @error('salario_base') <p class="mt-1 text-xs font-semibold text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div class="md:col-span-2">
                        <label class="text-sm font-semibold text-atlantia-ink">Supervisor</label>
                        <select name="supervisor_id" class="mt-1 w-full rounded-md border border-atlantia-rose/35 px-3 py-2 outline-none transition focus:border-atlantia-wine focus:ring-2 focus:ring-atlantia-rose/20">
                            <option value="">Sin supervisor asignado</option>
                            @foreach ($supervisores as $supervisor)
                                <option value="{{ $supervisor->id }}" @selected((string) old('supervisor_id') === (string) $supervisor->id)>
                                    {{ $supervisor->codigo_empleado }} - {{ $supervisor->user?->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('supervisor_id') <p class="mt-1 text-xs font-semibold text-red-600">{{ $message }}</p> @enderror
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

                <div class="mt-5 rounded-xl border border-atlantia-rose/20 bg-atlantia-cream p-4">
                    <p class="text-sm font-black text-atlantia-ink">Permisos operativos</p>
                    <div class="mt-3 grid gap-2 sm:grid-cols-2 lg:grid-cols-3">
                        @foreach ($permisosDisponibles as $permiso)
                            <label class="inline-flex items-center gap-2 rounded-lg border border-atlantia-rose/20 bg-white px-3 py-2 text-sm transition hover:border-atlantia-wine/50">
                                <input type="checkbox" name="permisos_operativos[]" value="{{ $permiso }}" class="rounded border-atlantia-rose text-atlantia-wine" @checked($selectedPermisos->contains($permiso))>
                                <span>{{ ucfirst($permiso) }}</span>
                            </label>
                        @endforeach
                    </div>
                    @error('permisos_operativos') <p class="mt-2 text-xs font-semibold text-red-600">{{ $message }}</p> @enderror
                </div>

                <div class="mt-6 flex flex-col-reverse gap-3 border-t border-atlantia-rose/15 pt-5 sm:flex-row sm:justify-end">
                    <button type="button" class="rounded-md border border-atlantia-rose/30 px-4 py-2 text-sm font-black text-atlantia-wine hover:bg-atlantia-blush" data-close-create-modal>
                        Cancelar
                    </button>
                    <x-ui.button type="submit">Crear empleado</x-ui.button>
                </div>
            </form>
        </div>
    </div>
@endsection
