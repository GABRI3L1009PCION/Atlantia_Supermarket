@extends(auth()->user()?->isSuperAdmin() && request()->routeIs('admin.*') ? 'layouts.super-admin' : 'layouts.app')

@section('content')
    @php
        $permisosDisponibles = ['contacto', 'transferencias', 'moderacion', 'reportes', 'soporte'];
        $departamentos = [
            'administracion' => 'Administracion',
            'operaciones' => 'Operaciones',
            'soporte_cliente' => 'Atencion al cliente',
            'finanzas' => 'Finanzas',
            'logistica' => 'Logistica',
            'moderacion' => 'Moderacion',
        ];
        $selectedPermisos = collect(old('permisos_operativos', []));
        $showCreateModal = $errors->any();
    @endphp

    <section class="mx-auto max-w-full py-2">
        <div class="rounded-2xl border border-atlantia-rose/20 bg-white p-6 shadow-sm">
            <div class="flex flex-col gap-5 lg:flex-row lg:items-start lg:justify-between">
                <x-page-header title="Empleados" subtitle="Administra al equipo interno que opera Atlantia." />

                <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
                    <div class="rounded-xl border border-atlantia-rose/25 bg-atlantia-cream px-4 py-3">
                        <p class="text-sm font-black text-atlantia-wine">{{ $empleados->total() }} empleados registrados</p>
                        <p class="text-xs text-atlantia-ink/60">Equipo operativo y administrativo.</p>
                    </div>
                    <button
                        type="button"
                        class="rounded-lg bg-atlantia-wine px-5 py-3 text-sm font-black text-white shadow-sm transition hover:bg-atlantia-wine-700"
                        data-open-create-modal
                    >
                        Crear nuevo empleado
                    </button>
                </div>
            </div>

            <div class="mt-7 rounded-xl border border-atlantia-rose/20 bg-white p-5">
                <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                    <div>
                        <h2 class="text-xl font-black text-atlantia-wine">Equipo interno</h2>
                        <p class="mt-1 text-sm text-atlantia-ink/65">Busca, revisa y gestiona colaboradores desde un listado mas limpio.</p>
                    </div>
                    <form method="GET" class="flex flex-col gap-2 sm:flex-row">
                        <input
                            type="search"
                            name="q"
                            value="{{ request('q') }}"
                            placeholder="Buscar por nombre, correo o codigo"
                            class="w-full rounded-md border border-atlantia-rose/35 px-3 py-2 text-sm outline-none transition focus:border-atlantia-wine focus:ring-2 focus:ring-atlantia-rose/20 sm:w-80"
                        >
                        <x-ui.button type="submit" variant="secondary">Buscar</x-ui.button>
                    </form>
                </div>

                <div class="mt-5 overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead>
                            <tr class="border-b border-atlantia-rose/20 text-left text-xs uppercase tracking-wide text-atlantia-ink/55">
                                <th class="pb-3">Empleado</th>
                                <th class="pb-3">Departamento</th>
                                <th class="pb-3">Puesto</th>
                                <th class="pb-3">Estado</th>
                                <th class="pb-3 text-right">Acciones</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-atlantia-rose/15">
                            @forelse ($empleados as $empleado)
                                <tr class="transition hover:bg-atlantia-blush/40">
                                    <td class="py-4 pr-4">
                                        <div class="flex items-center gap-3">
                                            <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-atlantia-blush text-sm font-black text-atlantia-wine">
                                                {{ \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($empleado->user?->name ?? 'E', 0, 1)) }}
                                            </div>
                                            <div>
                                                <p class="font-black text-atlantia-ink">{{ $empleado->user?->name }}</p>
                                                <p class="text-xs text-atlantia-ink/55">{{ $empleado->codigo_empleado }} - {{ $empleado->user?->email }}</p>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="py-4 pr-4 text-atlantia-ink/70">{{ $empleado->departamento }}</td>
                                    <td class="py-4 pr-4 text-atlantia-ink/70">{{ $empleado->puesto }}</td>
                                    <td class="py-4 pr-4">
                                        <span @class([
                                            'rounded-md px-3 py-1 text-xs font-black',
                                            'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-200' => $empleado->status === 'active',
                                            'bg-slate-100 text-slate-600 ring-1 ring-slate-200' => $empleado->status === 'inactive',
                                            'bg-amber-50 text-amber-700 ring-1 ring-amber-200' => $empleado->status === 'suspended',
                                        ])>
                                            {{ $empleado->status }}
                                        </span>
                                    </td>
                                    <td class="py-4 text-right">
                                        <a href="{{ route('admin.empleados.show', $empleado->uuid) }}" class="rounded-md border border-atlantia-rose/30 px-3 py-2 text-xs font-black text-atlantia-wine transition hover:bg-atlantia-blush">
                                            Gestionar
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="py-10 text-center">
                                        <p class="text-base font-black text-atlantia-ink">No hay empleados registrados.</p>
                                        <p class="mt-1 text-sm text-atlantia-ink/60">Crea el primer empleado desde el boton superior.</p>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="mt-4">{{ $empleados->links() }}</div>
            </div>
        </div>
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
                    <p class="text-xs font-black uppercase tracking-[0.18em] text-atlantia-rose">Atlantia Supermarket</p>
                    <h2 id="employee-create-title" class="mt-1 text-2xl font-black text-atlantia-ink">Crear nuevo empleado</h2>
                    <p class="mt-1 text-sm text-atlantia-ink/60">Registra datos, puesto y permisos operativos del colaborador.</p>
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
                        <label class="text-sm font-semibold text-atlantia-ink">Correo electronico</label>
                        <input name="email" type="email" value="{{ old('email') }}" class="mt-1 w-full rounded-md border border-atlantia-rose/35 px-3 py-2 outline-none transition focus:border-atlantia-wine focus:ring-2 focus:ring-atlantia-rose/20" required>
                        @error('email') <p class="mt-1 text-xs font-semibold text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="text-sm font-semibold text-atlantia-ink">Telefono</label>
                        <input name="phone" type="text" value="{{ old('phone') }}" class="mt-1 w-full rounded-md border border-atlantia-rose/35 px-3 py-2 outline-none transition focus:border-atlantia-wine focus:ring-2 focus:ring-atlantia-rose/20">
                        @error('phone') <p class="mt-1 text-xs font-semibold text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="text-sm font-semibold text-atlantia-ink">Telefono interno</label>
                        <input name="telefono_interno" type="text" value="{{ old('telefono_interno') }}" class="mt-1 w-full rounded-md border border-atlantia-rose/35 px-3 py-2 outline-none transition focus:border-atlantia-wine focus:ring-2 focus:ring-atlantia-rose/20">
                        @error('telefono_interno') <p class="mt-1 text-xs font-semibold text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="text-sm font-semibold text-atlantia-ink">Codigo</label>
                        <input name="codigo_empleado" type="text" value="{{ old('codigo_empleado') }}" class="mt-1 w-full rounded-md border border-atlantia-rose/35 px-3 py-2 outline-none transition focus:border-atlantia-wine focus:ring-2 focus:ring-atlantia-rose/20" required>
                        @error('codigo_empleado') <p class="mt-1 text-xs font-semibold text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="text-sm font-semibold text-atlantia-ink">Fecha de contratacion</label>
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
                        <p class="mt-1 text-xs text-atlantia-ink/55">La nomina solo incluye empleados activos con salario mayor que cero.</p>
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
                        <label class="text-sm font-semibold text-atlantia-ink">Contrasena</label>
                        <input name="password" type="password" class="mt-1 w-full rounded-md border border-atlantia-rose/35 px-3 py-2 outline-none transition focus:border-atlantia-wine focus:ring-2 focus:ring-atlantia-rose/20" required>
                        @error('password') <p class="mt-1 text-xs font-semibold text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="text-sm font-semibold text-atlantia-ink">Confirmar contrasena</label>
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
