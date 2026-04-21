@extends(auth()->user()?->isSuperAdmin() && request()->routeIs('admin.*') ? 'layouts.super-admin' : 'layouts.app')

@section('content')
    <section class="mx-auto max-w-5xl py-2">
        <div class="rounded-2xl border border-atlantia-rose/20 bg-white p-6 shadow-sm">
            <x-page-header title="Gestionar repartidor" subtitle="Actualiza datos de acceso y disponibilidad del repartidor." />

            <div class="grid gap-6 xl:grid-cols-[1fr_280px]">
                <form method="POST" action="{{ route('admin.repartidores.update', $repartidor) }}" class="space-y-5">
                    @csrf
                    @method('PUT')

                    <div class="grid gap-4 md:grid-cols-2">
                        <div>
                            <label class="text-sm font-semibold text-atlantia-ink">Nombre</label>
                            <input name="name" type="text" value="{{ $repartidor->name }}" class="mt-1 w-full rounded-md border border-atlantia-rose/35 px-3 py-2" required>
                        </div>
                        <div>
                            <label class="text-sm font-semibold text-atlantia-ink">Correo</label>
                            <input name="email" type="email" value="{{ $repartidor->email }}" class="mt-1 w-full rounded-md border border-atlantia-rose/35 px-3 py-2" required>
                        </div>
                        <div>
                            <label class="text-sm font-semibold text-atlantia-ink">Telefono</label>
                            <input name="phone" type="text" value="{{ $repartidor->phone }}" class="mt-1 w-full rounded-md border border-atlantia-rose/35 px-3 py-2">
                        </div>
                        <div>
                            <label class="text-sm font-semibold text-atlantia-ink">Estado</label>
                            <select name="status" class="mt-1 w-full rounded-md border border-atlantia-rose/35 px-3 py-2">
                                @foreach (['active' => 'Activo', 'inactive' => 'Inactivo', 'suspended' => 'Suspendido'] as $value => $label)
                                    <option value="{{ $value }}" @selected($repartidor->status === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
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

                    <div class="flex gap-3">
                        <x-ui.button type="submit">Guardar cambios</x-ui.button>
                        <a href="{{ route('admin.repartidores.index') }}" class="inline-flex items-center rounded-md border border-atlantia-rose/35 px-4 py-2 text-sm font-semibold text-atlantia-wine hover:bg-atlantia-blush">
                            Volver
                        </a>
                    </div>
                </form>

                <aside class="space-y-4">
                    <div class="rounded-xl border border-atlantia-rose/20 bg-atlantia-cream p-4">
                        <p class="text-xs font-semibold uppercase text-atlantia-rose">Cuenta</p>
                        <p class="mt-2 text-sm text-atlantia-ink/65">UUID</p>
                        <p class="font-semibold text-atlantia-ink">{{ $repartidor->uuid }}</p>
                        <p class="mt-3 text-sm text-atlantia-ink/65">Rol</p>
                        <p class="font-semibold text-atlantia-ink">{{ $repartidor->roles->pluck('name')->join(', ') }}</p>
                    </div>

                    <form method="POST" action="{{ route('admin.repartidores.destroy', $repartidor) }}" class="rounded-xl border border-red-200 bg-red-50 p-4">
                        @csrf
                        @method('DELETE')
                        <p class="text-sm font-bold text-red-800">Eliminar repartidor</p>
                        <p class="mt-2 text-sm text-red-700/80">La cuenta quedara inactiva con eliminacion logica.</p>
                        <button type="submit" class="mt-4 rounded-md bg-red-700 px-4 py-2 text-sm font-semibold text-white hover:bg-red-800">
                            Eliminar repartidor
                        </button>
                    </form>
                </aside>
            </div>
        </div>
    </section>
@endsection
