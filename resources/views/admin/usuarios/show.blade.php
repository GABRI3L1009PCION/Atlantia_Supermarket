@extends('layouts.app')

@section('content')
    @php
        $availableRoles = auth()->user()?->isSuperAdmin()
            ? $roles
            : $roles->reject(fn ($role) => in_array($role->name, ['admin', 'super_admin'], true));
    @endphp

    <section class="mx-auto max-w-5xl py-2">
        <div class="rounded-2xl border border-atlantia-rose/20 bg-white p-6 shadow-sm">
            <x-page-header title="Gestionar usuario" subtitle="Edita datos, roles y estado de la cuenta seleccionada." />

            <div class="grid gap-6 xl:grid-cols-[1fr_280px]">
                <form method="POST" action="{{ route('admin.usuarios.update', $usuario) }}" class="space-y-5">
                    @csrf
                    @method('PUT')

                    <div class="grid gap-4 md:grid-cols-2">
                        <div>
                            <label class="text-sm font-semibold text-atlantia-ink">Nombre completo</label>
                            <input name="name" type="text" value="{{ old('name', $usuario->name) }}" class="mt-1 w-full rounded-md border border-atlantia-rose/35 px-3 py-2" required>
                        </div>
                        <div>
                            <label class="text-sm font-semibold text-atlantia-ink">Correo electronico</label>
                            <input name="email" type="email" value="{{ old('email', $usuario->email) }}" class="mt-1 w-full rounded-md border border-atlantia-rose/35 px-3 py-2" required>
                        </div>
                        <div>
                            <label class="text-sm font-semibold text-atlantia-ink">Telefono</label>
                            <input name="phone" type="text" value="{{ old('phone', $usuario->phone) }}" class="mt-1 w-full rounded-md border border-atlantia-rose/35 px-3 py-2">
                        </div>
                        <div>
                            <label class="text-sm font-semibold text-atlantia-ink">Estado</label>
                            <select name="status" class="mt-1 w-full rounded-md border border-atlantia-rose/35 px-3 py-2">
                                @foreach (['active' => 'Activo', 'inactive' => 'Inactivo', 'suspended' => 'Suspendido'] as $value => $label)
                                    <option value="{{ $value }}" @selected(old('status', $usuario->status) === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div>
                        <label class="text-sm font-semibold text-atlantia-ink">Roles</label>
                        <div class="mt-2 grid gap-2 rounded-lg border border-atlantia-rose/20 p-4 md:grid-cols-2">
                            @foreach ($availableRoles as $role)
                                <label class="flex items-center gap-2 text-sm text-atlantia-ink">
                                    <input
                                        type="checkbox"
                                        name="roles[]"
                                        value="{{ $role->name }}"
                                        @checked(collect(old('roles', $usuario->roles->pluck('name')->all()))->contains($role->name))
                                        class="rounded border-atlantia-rose text-atlantia-wine focus:ring-atlantia-rose"
                                    >
                                    <span>{{ $role->name }}</span>
                                </label>
                            @endforeach
                        </div>
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

                    <div class="flex flex-wrap gap-3">
                        <x-ui.button type="submit">Guardar cambios</x-ui.button>
                        <a href="{{ route('admin.usuarios.index') }}" class="inline-flex items-center rounded-md border border-atlantia-rose/35 px-4 py-2 text-sm font-semibold text-atlantia-wine hover:bg-atlantia-blush">
                            Volver al listado
                        </a>
                    </div>
                </form>

                <aside class="space-y-4">
                    <div class="rounded-xl border border-atlantia-rose/20 bg-atlantia-cream p-4">
                        <p class="text-xs font-semibold uppercase text-atlantia-rose">Cuenta</p>
                        <p class="mt-2 text-sm text-atlantia-ink/70">UUID publico</p>
                        <p class="text-sm font-semibold text-atlantia-ink">{{ $usuario->uuid }}</p>
                        <p class="mt-3 text-sm text-atlantia-ink/70">Ultimo acceso</p>
                        <p class="text-sm font-semibold text-atlantia-ink">{{ optional($usuario->last_login_at)->format('d/m/Y H:i') ?: 'Sin registro' }}</p>
                    </div>

                    @can('delete', $usuario)
                        <form method="POST" action="{{ route('admin.usuarios.destroy', $usuario) }}" class="rounded-xl border border-red-200 bg-red-50 p-4">
                            @csrf
                            @method('DELETE')
                            <p class="text-sm font-bold text-red-800">Eliminar cuenta</p>
                            <p class="mt-2 text-sm text-red-700/80">
                                Esta accion desactiva la cuenta y la retira del panel operativo.
                            </p>
                            <button type="submit" class="mt-4 rounded-md bg-red-700 px-4 py-2 text-sm font-semibold text-white hover:bg-red-800">
                                Eliminar usuario
                            </button>
                        </form>
                    @endcan
                </aside>
            </div>
        </div>
    </section>
@endsection
