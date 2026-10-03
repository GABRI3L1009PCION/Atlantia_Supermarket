@extends(auth()->user()?->isSuperAdmin() && request()->routeIs('admin.*') ? 'layouts.super-admin' : 'layouts.app')

@section('content')
    @php
        $showCreateModal = $errors->any();
    @endphp

    <section class="mx-auto max-w-full py-2">
        <div class="rounded-2xl border border-atlantia-rose/20 bg-white p-6 shadow-sm">
            <div class="flex flex-col gap-5 lg:flex-row lg:items-start lg:justify-between">
                <x-page-header title="Repartidores" subtitle="Gestion del equipo de entregas y cuentas de reparto." />

                <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
                    <div class="rounded-xl border border-atlantia-rose/25 bg-atlantia-cream px-4 py-3">
                        <p class="text-sm font-black text-atlantia-wine">{{ $repartidores->total() }} repartidores registrados</p>
                        <p class="text-xs text-atlantia-ink/60">Cuentas activas y operativas de entrega.</p>
                    </div>
                    <button
                        type="button"
                        class="rounded-lg bg-atlantia-wine px-5 py-3 text-sm font-black text-white shadow-sm transition hover:bg-atlantia-wine-700"
                        data-open-create-modal
                    >
                        Crear nuevo repartidor
                    </button>
                </div>
            </div>

            <div class="mt-7 rounded-xl border border-atlantia-rose/20 bg-white p-5">
                <div class="flex flex-col gap-1">
                    <h2 class="text-xl font-black text-atlantia-wine">Equipo de reparto</h2>
                    <p class="text-sm text-atlantia-ink/65">Gestiona las cuentas de los repartidores sin cargar la vista principal.</p>
                </div>

                <div class="mt-5 overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead>
                            <tr class="border-b border-atlantia-rose/20 text-left text-xs uppercase tracking-wide text-atlantia-ink/55">
                                <th class="pb-3">Nombre</th>
                                <th class="pb-3">Correo</th>
                                <th class="pb-3">Estado</th>
                                <th class="pb-3 text-right">Acciones</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-atlantia-rose/15">
                            @forelse ($repartidores as $repartidor)
                                <tr class="transition hover:bg-atlantia-blush/40">
                                    <td class="py-4 pr-4">
                                        <div class="flex items-center gap-3">
                                            <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-atlantia-blush text-sm font-black text-atlantia-wine">
                                                {{ \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($repartidor->name ?? 'R', 0, 1)) }}
                                            </div>
                                            <div>
                                                <p class="font-black text-atlantia-ink">{{ $repartidor->name }}</p>
                                                <p class="text-xs text-atlantia-ink/55">Cuenta de reparto</p>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="py-4 pr-4 text-atlantia-ink/70">{{ $repartidor->email }}</td>
                                    <td class="py-4 pr-4">
                                        <span @class([
                                            'rounded-md px-3 py-1 text-xs font-black',
                                            'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-200' => $repartidor->status === 'active',
                                            'bg-slate-100 text-slate-600 ring-1 ring-slate-200' => $repartidor->status === 'inactive',
                                            'bg-amber-50 text-amber-700 ring-1 ring-amber-200' => $repartidor->status === 'suspended',
                                        ])>
                                            {{ $repartidor->status }}
                                        </span>
                                    </td>
                                    <td class="py-4 text-right">
                                        <a href="{{ route('admin.repartidores.show', $repartidor) }}" class="rounded-md border border-atlantia-rose/30 px-3 py-2 text-xs font-black text-atlantia-wine transition hover:bg-atlantia-blush">
                                            Gestionar
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="py-10 text-center">
                                        <p class="text-base font-black text-atlantia-ink">No hay repartidores registrados.</p>
                                        <p class="mt-1 text-sm text-atlantia-ink/60">Crea el primer repartidor desde el boton superior.</p>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="mt-4">{{ $repartidores->links() }}</div>
            </div>
        </div>
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
                    <p class="text-xs font-black uppercase tracking-[0.18em] text-atlantia-rose">Atlantia Supermarket</p>
                    <h2 id="delivery-create-title" class="mt-1 text-2xl font-black text-atlantia-ink">Crear nuevo repartidor</h2>
                    <p class="mt-1 text-sm text-atlantia-ink/60">Registra la cuenta que usara el colaborador de entregas.</p>
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
                        <label class="text-sm font-semibold text-atlantia-ink">Telefono</label>
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
                        <label class="text-sm font-semibold text-atlantia-ink">Contrasena</label>
                        <input name="password" type="password" class="mt-1 w-full rounded-md border border-atlantia-rose/35 px-3 py-2 outline-none transition focus:border-atlantia-wine focus:ring-2 focus:ring-atlantia-rose/20" required>
                        @error('password') <p class="mt-1 text-xs font-semibold text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="text-sm font-semibold text-atlantia-ink">Confirmar contrasena</label>
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
