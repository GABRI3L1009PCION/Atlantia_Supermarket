@extends(auth()->user()?->isSuperAdmin() && request()->routeIs('admin.*') ? 'layouts.super-admin' : 'layouts.app')

@section('content')
    <section class="mx-auto max-w-full py-2">
        <div class="rounded-2xl border border-atlantia-rose/20 bg-white p-6 shadow-sm">
            <x-page-header title="Repartidores" subtitle="Gestion del equipo de entregas y cuentas de reparto." />

            <div class="grid gap-6 xl:grid-cols-[420px_1fr]">
                <form method="POST" action="{{ route('admin.repartidores.store') }}" class="rounded-xl border border-atlantia-rose/20 bg-atlantia-cream p-5">
                    @csrf
                    <h2 class="text-lg font-bold text-atlantia-wine">Crear repartidor</h2>

                    <div class="mt-4 space-y-4">
                        <div>
                            <label class="text-sm font-semibold text-atlantia-ink">Nombre</label>
                            <input name="name" type="text" class="mt-1 w-full rounded-md border border-atlantia-rose/35 px-3 py-2" required>
                        </div>
                        <div>
                            <label class="text-sm font-semibold text-atlantia-ink">Correo</label>
                            <input name="email" type="email" class="mt-1 w-full rounded-md border border-atlantia-rose/35 px-3 py-2" required>
                        </div>
                        <div>
                            <label class="text-sm font-semibold text-atlantia-ink">Telefono</label>
                            <input name="phone" type="text" class="mt-1 w-full rounded-md border border-atlantia-rose/35 px-3 py-2">
                        </div>
                        <div>
                            <label class="text-sm font-semibold text-atlantia-ink">Estado</label>
                            <select name="status" class="mt-1 w-full rounded-md border border-atlantia-rose/35 px-3 py-2">
                                <option value="active">Activo</option>
                                <option value="inactive">Inactivo</option>
                                <option value="suspended">Suspendido</option>
                            </select>
                        </div>
                        <div>
                            <label class="text-sm font-semibold text-atlantia-ink">Contrasena</label>
                            <input name="password" type="password" class="mt-1 w-full rounded-md border border-atlantia-rose/35 px-3 py-2" required>
                        </div>
                        <div>
                            <label class="text-sm font-semibold text-atlantia-ink">Confirmar contrasena</label>
                            <input name="password_confirmation" type="password" class="mt-1 w-full rounded-md border border-atlantia-rose/35 px-3 py-2" required>
                        </div>
                    </div>

                    <x-ui.button type="submit" class="mt-5 w-full">Crear repartidor</x-ui.button>
                </form>

                <div class="rounded-xl border border-atlantia-rose/20 bg-white p-5">
                    <h2 class="text-lg font-bold text-atlantia-wine">Equipo de reparto</h2>

                    <div class="mt-5 overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <thead>
                                <tr class="border-b border-atlantia-rose/20 text-left text-atlantia-ink/55">
                                    <th class="pb-3">Nombre</th>
                                    <th class="pb-3">Correo</th>
                                    <th class="pb-3">Estado</th>
                                    <th class="pb-3 text-right">Acciones</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-atlantia-rose/15">
                                @forelse ($repartidores as $repartidor)
                                    <tr>
                                        <td class="py-3 font-semibold text-atlantia-ink">{{ $repartidor->name }}</td>
                                        <td class="py-3 text-atlantia-ink/70">{{ $repartidor->email }}</td>
                                        <td class="py-3">
                                            <span class="rounded-md bg-atlantia-blush px-3 py-1 text-xs font-bold text-atlantia-wine">
                                                {{ $repartidor->status }}
                                            </span>
                                        </td>
                                        <td class="py-3 text-right">
                                            <a href="{{ route('admin.repartidores.show', $repartidor) }}" class="font-semibold text-atlantia-wine hover:underline">Gestionar</a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="py-6 text-center text-atlantia-ink/60">No hay repartidores registrados.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-4">{{ $repartidores->links() }}</div>
                </div>
            </div>
        </div>
    </section>
@endsection
