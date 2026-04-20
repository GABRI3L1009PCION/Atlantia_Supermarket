@extends('layouts.app')

@section('content')
    @php
        $roles = $data['roles'];
        $permissions = $data['permissions'];
        $protectedRoles = ['super_admin', 'admin', 'cliente', 'vendedor', 'repartidor', 'empleado'];
    @endphp

    <section class="mx-auto max-w-7xl px-4 py-8">
        <x-page-header
            title="Roles y permisos"
            subtitle="Gestiona accesos del sistema y deja permisos listos para modulos escalables."
        />

        <div class="mt-6 grid gap-6 lg:grid-cols-[420px_1fr]">
            <div class="space-y-6">
                <form
                    method="POST"
                    action="{{ route('admin.roles-permisos.store') }}"
                    class="rounded-lg border border-atlantia-rose/30 bg-white p-5 shadow-sm"
                >
                    @csrf
                    <h2 class="text-lg font-bold text-atlantia-wine">Crear rol</h2>
                    <p class="mt-1 text-sm text-atlantia-ink/70">
                        Usa nombres tecnicos como supervisor_bodega o soporte_cliente.
                    </p>

                    <label for="role-name" class="mt-4 block text-sm font-semibold text-atlantia-ink">
                        Nombre tecnico del rol
                    </label>
                    <input
                        id="role-name"
                        name="name"
                        type="text"
                        value="{{ old('name') }}"
                        placeholder="supervisor_bodega"
                        class="mt-1 w-full rounded-md border border-atlantia-rose/40 px-3 py-2 text-sm focus:border-atlantia-wine focus:ring-atlantia-rose"
                        required
                    >

                    <fieldset class="mt-4">
                        <legend class="text-sm font-semibold text-atlantia-ink">Permisos iniciales</legend>
                        <div class="mt-2 max-h-56 space-y-2 overflow-y-auto rounded-md border border-atlantia-rose/20 p-3">
                            @foreach ($permissions as $permission)
                                <label class="flex items-center gap-2 text-sm text-atlantia-ink">
                                    <input
                                        type="checkbox"
                                        name="permissions[]"
                                        value="{{ $permission->name }}"
                                        class="rounded border-atlantia-rose text-atlantia-wine focus:ring-atlantia-rose"
                                    >
                                    <span>{{ $permission->name }}</span>
                                </label>
                            @endforeach
                        </div>
                    </fieldset>

                    <x-ui.button type="submit" class="mt-5 w-full">Crear rol</x-ui.button>
                </form>

                <form
                    method="POST"
                    action="{{ route('admin.roles-permisos.permisos.store') }}"
                    class="rounded-lg border border-atlantia-rose/30 bg-white p-5 shadow-sm"
                >
                    @csrf
                    <h2 class="text-lg font-bold text-atlantia-wine">Crear permiso escalable</h2>
                    <p class="mt-1 text-sm text-atlantia-ink/70">
                        Sirve para activar funciones nuevas sin cambiar la estructura de roles.
                    </p>

                    <label for="permission-name" class="mt-4 block text-sm font-semibold text-atlantia-ink">
                        Nombre tecnico del permiso
                    </label>
                    <input
                        id="permission-name"
                        name="name"
                        type="text"
                        value="{{ old('permission_name') }}"
                        placeholder="bodega.transferencias.validar"
                        class="mt-1 w-full rounded-md border border-atlantia-rose/40 px-3 py-2 text-sm focus:border-atlantia-wine focus:ring-atlantia-rose"
                        required
                    >

                    <x-ui.button type="submit" variant="secondary" class="mt-5 w-full">Crear permiso</x-ui.button>
                </form>
            </div>

            <div class="space-y-4">
                @foreach ($roles as $role)
                    <article class="rounded-lg border border-atlantia-rose/30 bg-white p-5 shadow-sm">
                        <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                            <div>
                                <h2 class="text-lg font-bold text-atlantia-ink">{{ $role->name }}</h2>
                                <p class="mt-1 text-sm text-atlantia-ink/65">
                                    {{ $role->permissions->count() }} permisos asignados
                                </p>
                            </div>

                            @if (! in_array($role->name, $protectedRoles, true))
                                <form method="POST" action="{{ route('admin.roles-permisos.destroy', $role) }}">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-sm font-bold text-red-700 hover:underline">
                                        Eliminar rol
                                    </button>
                                </form>
                            @else
                                <span class="rounded-md bg-atlantia-blush px-3 py-1 text-xs font-bold text-atlantia-wine">
                                    Rol base protegido
                                </span>
                            @endif
                        </div>

                        <form method="POST" action="{{ route('admin.roles-permisos.sync', $role) }}" class="mt-4">
                            @csrf
                            @method('PUT')

                            <div class="grid max-h-72 gap-2 overflow-y-auto rounded-md border border-atlantia-rose/20 p-3 md:grid-cols-2">
                                @foreach ($permissions as $permission)
                                    <label class="flex items-start gap-2 text-sm text-atlantia-ink">
                                        <input
                                            type="checkbox"
                                            name="permissions[]"
                                            value="{{ $permission->name }}"
                                            @checked($role->permissions->contains('name', $permission->name))
                                            @disabled($role->name === 'super_admin')
                                            class="mt-1 rounded border-atlantia-rose text-atlantia-wine focus:ring-atlantia-rose"
                                        >
                                        <span>{{ $permission->name }}</span>
                                    </label>
                                @endforeach
                            </div>

                            <x-ui.button
                                type="submit"
                                class="mt-4"
                                :disabled="$role->name === 'super_admin'"
                            >
                                Guardar permisos
                            </x-ui.button>
                        </form>
                    </article>
                @endforeach
            </div>
        </div>
    </section>
@endsection
