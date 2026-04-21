@extends(auth()->user()?->isSuperAdmin() && request()->routeIs('admin.*') ? 'layouts.super-admin' : 'layouts.app')

@section('content')
    @php
        $permisosDisponibles = ['contacto', 'transferencias', 'moderacion', 'reportes', 'soporte'];
    @endphp

    <section class="mx-auto max-w-full py-2">
        <div class="grid gap-6 xl:grid-cols-[1.35fr_0.65fr]">
            <div class="rounded-2xl border border-atlantia-rose/20 bg-white p-6 shadow-sm">
                <x-page-header
                    title="Gestionar empleado"
                    :subtitle="'Ajusta datos operativos de ' . ($empleado->user?->name ?? 'empleado interno') . '.'"
                />

                <form method="POST" action="{{ route('admin.empleados.update', $empleado->uuid) }}" class="mt-6 grid gap-4">
                    @csrf
                    @method('PUT')

                    <div class="grid gap-4 md:grid-cols-2">
                        <div>
                            <label class="text-sm font-semibold text-atlantia-ink">Nombre completo</label>
                            <input name="name" type="text" value="{{ old('name', $empleado->user?->name) }}" class="mt-1 w-full rounded-md border border-atlantia-rose/35 px-3 py-2" required>
                        </div>
                        <div>
                            <label class="text-sm font-semibold text-atlantia-ink">Correo electronico</label>
                            <input name="email" type="email" value="{{ old('email', $empleado->user?->email) }}" class="mt-1 w-full rounded-md border border-atlantia-rose/35 px-3 py-2" required>
                        </div>
                    </div>

                    <div class="grid gap-4 md:grid-cols-2">
                        <div>
                            <label class="text-sm font-semibold text-atlantia-ink">Telefono</label>
                            <input name="phone" type="text" value="{{ old('phone', $empleado->user?->phone) }}" class="mt-1 w-full rounded-md border border-atlantia-rose/35 px-3 py-2">
                        </div>
                        <div>
                            <label class="text-sm font-semibold text-atlantia-ink">Telefono interno</label>
                            <input name="telefono_interno" type="text" value="{{ old('telefono_interno', $empleado->telefono_interno) }}" class="mt-1 w-full rounded-md border border-atlantia-rose/35 px-3 py-2">
                        </div>
                    </div>

                    <div class="grid gap-4 md:grid-cols-2">
                        <div>
                            <label class="text-sm font-semibold text-atlantia-ink">Codigo</label>
                            <input name="codigo_empleado" type="text" value="{{ old('codigo_empleado', $empleado->codigo_empleado) }}" class="mt-1 w-full rounded-md border border-atlantia-rose/35 px-3 py-2" required>
                        </div>
                        <div>
                            <label class="text-sm font-semibold text-atlantia-ink">Fecha de contratacion</label>
                            <input name="fecha_contratacion" type="date" value="{{ old('fecha_contratacion', optional($empleado->fecha_contratacion)->format('Y-m-d')) }}" class="mt-1 w-full rounded-md border border-atlantia-rose/35 px-3 py-2" required>
                        </div>
                    </div>

                    <div class="grid gap-4 md:grid-cols-3">
                        <div>
                            <label class="text-sm font-semibold text-atlantia-ink">Departamento</label>
                            <select name="departamento" class="mt-1 w-full rounded-md border border-atlantia-rose/35 px-3 py-2" required>
                                @foreach (['Operaciones', 'Atencion al cliente', 'Finanzas', 'Logistica', 'Calidad', 'Tecnologia'] as $departamento)
                                    <option value="{{ $departamento }}" @selected(old('departamento', $empleado->departamento) === $departamento)>{{ $departamento }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="text-sm font-semibold text-atlantia-ink">Puesto</label>
                            <input name="puesto" type="text" value="{{ old('puesto', $empleado->puesto) }}" class="mt-1 w-full rounded-md border border-atlantia-rose/35 px-3 py-2" required>
                        </div>
                        <div>
                            <label class="text-sm font-semibold text-atlantia-ink">Estado</label>
                            <select name="status" class="mt-1 w-full rounded-md border border-atlantia-rose/35 px-3 py-2" required>
                                @foreach (['active' => 'Activo', 'inactive' => 'Inactivo', 'suspended' => 'Suspendido'] as $valor => $label)
                                    <option value="{{ $valor }}" @selected(old('status', $empleado->status) === $valor)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div>
                        <label class="text-sm font-semibold text-atlantia-ink">Supervisor</label>
                        <select name="supervisor_id" class="mt-1 w-full rounded-md border border-atlantia-rose/35 px-3 py-2">
                            <option value="">Sin supervisor asignado</option>
                            @foreach ($supervisores as $supervisor)
                                <option value="{{ $supervisor->id }}" @selected((int) old('supervisor_id', $empleado->supervisor_id) === (int) $supervisor->id)>
                                    {{ $supervisor->codigo_empleado }} - {{ $supervisor->user?->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="grid gap-4 md:grid-cols-2">
                        <div>
                            <label class="text-sm font-semibold text-atlantia-ink">Nueva contrasena</label>
                            <input name="password" type="password" class="mt-1 w-full rounded-md border border-atlantia-rose/35 px-3 py-2">
                        </div>
                        <div>
                            <label class="text-sm font-semibold text-atlantia-ink">Confirmar contrasena</label>
                            <input name="password_confirmation" type="password" class="mt-1 w-full rounded-md border border-atlantia-rose/35 px-3 py-2">
                        </div>
                    </div>

                    <div>
                        <p class="text-sm font-semibold text-atlantia-ink">Permisos operativos</p>
                        <div class="mt-2 grid gap-2 md:grid-cols-3">
                            @foreach ($permisosDisponibles as $permiso)
                                <label class="inline-flex items-center gap-2 rounded-lg border border-atlantia-rose/20 bg-atlantia-cream px-3 py-2 text-sm">
                                    <input
                                        type="checkbox"
                                        name="permisos_operativos[]"
                                        value="{{ $permiso }}"
                                        @checked(in_array($permiso, old('permisos_operativos', $empleado->permisos_operativos ?? []), true))
                                        class="rounded border-atlantia-rose text-atlantia-wine"
                                    >
                                    <span>{{ ucfirst($permiso) }}</span>
                                </label>
                            @endforeach
                        </div>
                    </div>

                    <div class="flex flex-wrap gap-3">
                        <x-ui.button type="submit">Guardar cambios</x-ui.button>
                        <a href="{{ route('admin.empleados.index') }}" class="inline-flex items-center rounded-md border border-atlantia-rose/30 px-4 py-2 text-sm font-semibold text-atlantia-wine">
                            Volver al listado
                        </a>
                    </div>
                </form>
            </div>

            <aside class="space-y-6">
                <div class="rounded-2xl border border-atlantia-rose/20 bg-white p-5 shadow-sm">
                    <h2 class="text-lg font-bold text-atlantia-wine">Resumen</h2>
                    <dl class="mt-4 space-y-3 text-sm">
                        <div>
                            <dt class="text-atlantia-ink/55">Codigo</dt>
                            <dd class="font-semibold text-atlantia-ink">{{ $empleado->codigo_empleado }}</dd>
                        </div>
                        <div>
                            <dt class="text-atlantia-ink/55">Supervisor</dt>
                            <dd class="font-semibold text-atlantia-ink">{{ $empleado->supervisor?->user?->name ?? 'Sin asignar' }}</dd>
                        </div>
                        <div>
                            <dt class="text-atlantia-ink/55">Acceso</dt>
                            <dd class="font-semibold text-atlantia-ink">{{ $empleado->user?->email }}</dd>
                        </div>
                    </dl>
                </div>

                <div class="rounded-2xl border border-red-200 bg-white p-5 shadow-sm">
                    <h2 class="text-lg font-bold text-red-700">Zona sensible</h2>
                    <p class="mt-2 text-sm text-atlantia-ink/70">
                        Esta accion desactiva el perfil interno y conserva el historial operativo.
                    </p>

                    <form method="POST" action="{{ route('admin.empleados.destroy', $empleado->uuid) }}" class="mt-4">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="w-full rounded-md bg-red-600 px-4 py-2 text-sm font-semibold text-white">
                            Eliminar empleado
                        </button>
                    </form>
                </div>
            </aside>
        </div>
    </section>
@endsection
