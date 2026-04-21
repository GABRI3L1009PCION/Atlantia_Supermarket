@extends('layouts.app')

@section('content')
    @php
        $permisosDisponibles = ['contacto', 'transferencias', 'moderacion', 'reportes', 'soporte'];
    @endphp

    <section class="mx-auto max-w-full py-2">
        <div class="rounded-2xl border border-atlantia-rose/20 bg-white p-6 shadow-sm">
            <x-page-header title="Empleados" subtitle="Administra al equipo interno que opera Atlantia." />

            <div class="grid gap-6 xl:grid-cols-[430px_1fr]">
                <form method="POST" action="{{ route('admin.empleados.store') }}" class="rounded-xl border border-atlantia-rose/20 bg-atlantia-cream p-5">
                    @csrf
                    <h2 class="text-lg font-bold text-atlantia-wine">Crear empleado</h2>

                    <div class="mt-4 grid gap-4">
                        <div>
                            <label class="text-sm font-semibold text-atlantia-ink">Nombre completo</label>
                            <input name="name" type="text" class="mt-1 w-full rounded-md border border-atlantia-rose/35 px-3 py-2" required>
                        </div>
                        <div>
                            <label class="text-sm font-semibold text-atlantia-ink">Correo electronico</label>
                            <input name="email" type="email" class="mt-1 w-full rounded-md border border-atlantia-rose/35 px-3 py-2" required>
                        </div>
                        <div class="grid gap-4 md:grid-cols-2">
                            <div>
                                <label class="text-sm font-semibold text-atlantia-ink">Telefono</label>
                                <input name="phone" type="text" class="mt-1 w-full rounded-md border border-atlantia-rose/35 px-3 py-2">
                            </div>
                            <div>
                                <label class="text-sm font-semibold text-atlantia-ink">Telefono interno</label>
                                <input name="telefono_interno" type="text" class="mt-1 w-full rounded-md border border-atlantia-rose/35 px-3 py-2">
                            </div>
                        </div>
                        <div class="grid gap-4 md:grid-cols-2">
                            <div>
                                <label class="text-sm font-semibold text-atlantia-ink">Codigo</label>
                                <input name="codigo_empleado" type="text" class="mt-1 w-full rounded-md border border-atlantia-rose/35 px-3 py-2" required>
                            </div>
                            <div>
                                <label class="text-sm font-semibold text-atlantia-ink">Fecha de contratacion</label>
                                <input name="fecha_contratacion" type="date" class="mt-1 w-full rounded-md border border-atlantia-rose/35 px-3 py-2" required>
                            </div>
                        </div>
                        <div class="grid gap-4 md:grid-cols-2">
                            <div>
                                <label class="text-sm font-semibold text-atlantia-ink">Departamento</label>
                                <select name="departamento" class="mt-1 w-full rounded-md border border-atlantia-rose/35 px-3 py-2" required>
                                    @foreach (['Operaciones', 'Atencion al cliente', 'Finanzas', 'Logistica', 'Calidad', 'Tecnologia'] as $departamento)
                                        <option value="{{ $departamento }}">{{ $departamento }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="text-sm font-semibold text-atlantia-ink">Estado</label>
                                <select name="status" class="mt-1 w-full rounded-md border border-atlantia-rose/35 px-3 py-2" required>
                                    <option value="active">Activo</option>
                                    <option value="inactive">Inactivo</option>
                                    <option value="suspended">Suspendido</option>
                                </select>
                            </div>
                        </div>
                        <div>
                            <label class="text-sm font-semibold text-atlantia-ink">Puesto</label>
                            <input name="puesto" type="text" class="mt-1 w-full rounded-md border border-atlantia-rose/35 px-3 py-2" required>
                        </div>
                        <div>
                            <label class="text-sm font-semibold text-atlantia-ink">Supervisor</label>
                            <select name="supervisor_id" class="mt-1 w-full rounded-md border border-atlantia-rose/35 px-3 py-2">
                                <option value="">Sin supervisor asignado</option>
                                @foreach ($supervisores as $supervisor)
                                    <option value="{{ $supervisor->id }}">{{ $supervisor->codigo_empleado }} - {{ $supervisor->user?->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="grid gap-3 md:grid-cols-2">
                            <div>
                                <label class="text-sm font-semibold text-atlantia-ink">Contrasena</label>
                                <input name="password" type="password" class="mt-1 w-full rounded-md border border-atlantia-rose/35 px-3 py-2" required>
                            </div>
                            <div>
                                <label class="text-sm font-semibold text-atlantia-ink">Confirmar contrasena</label>
                                <input name="password_confirmation" type="password" class="mt-1 w-full rounded-md border border-atlantia-rose/35 px-3 py-2" required>
                            </div>
                        </div>
                    </div>

                    <div class="mt-4">
                        <p class="text-sm font-semibold text-atlantia-ink">Permisos operativos</p>
                        <div class="mt-2 grid gap-2 md:grid-cols-2">
                            @foreach ($permisosDisponibles as $permiso)
                                <label class="inline-flex items-center gap-2 rounded-lg border border-atlantia-rose/20 bg-white px-3 py-2 text-sm">
                                    <input type="checkbox" name="permisos_operativos[]" value="{{ $permiso }}" class="rounded border-atlantia-rose text-atlantia-wine">
                                    <span>{{ ucfirst($permiso) }}</span>
                                </label>
                            @endforeach
                        </div>
                    </div>

                    <x-ui.button type="submit" class="mt-5 w-full">Crear empleado</x-ui.button>
                </form>

                <div class="rounded-xl border border-atlantia-rose/20 bg-white p-5">
                    <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                        <h2 class="text-lg font-bold text-atlantia-wine">Equipo interno</h2>
                        <form method="GET" class="flex gap-2">
                            <input type="search" name="q" value="{{ request('q') }}" placeholder="Buscar por nombre, correo o codigo" class="w-72 rounded-md border border-atlantia-rose/35 px-3 py-2">
                            <x-ui.button type="submit" variant="secondary">Buscar</x-ui.button>
                        </form>
                    </div>

                    <div class="mt-5 overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <thead>
                                <tr class="border-b border-atlantia-rose/20 text-left text-atlantia-ink/55">
                                    <th class="pb-3">Empleado</th>
                                    <th class="pb-3">Departamento</th>
                                    <th class="pb-3">Puesto</th>
                                    <th class="pb-3">Estado</th>
                                    <th class="pb-3 text-right">Acciones</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-atlantia-rose/15">
                                @forelse ($empleados as $empleado)
                                    <tr>
                                        <td class="py-3">
                                            <p class="font-semibold text-atlantia-ink">{{ $empleado->user?->name }}</p>
                                            <p class="text-xs text-atlantia-ink/55">{{ $empleado->codigo_empleado }} · {{ $empleado->user?->email }}</p>
                                        </td>
                                        <td class="py-3 text-atlantia-ink/70">{{ $empleado->departamento }}</td>
                                        <td class="py-3 text-atlantia-ink/70">{{ $empleado->puesto }}</td>
                                        <td class="py-3">
                                            <span class="rounded-md bg-atlantia-blush px-3 py-1 text-xs font-bold text-atlantia-wine">
                                                {{ $empleado->status }}
                                            </span>
                                        </td>
                                        <td class="py-3 text-right">
                                            <a href="{{ route('admin.empleados.show', $empleado->uuid) }}" class="font-semibold text-atlantia-wine hover:underline">
                                                Gestionar
                                            </a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="py-6 text-center text-atlantia-ink/60">No hay empleados registrados.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-4">{{ $empleados->links() }}</div>
                </div>
            </div>
        </div>
    </section>
@endsection
