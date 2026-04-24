@extends(auth()->user()?->isSuperAdmin() && request()->routeIs('admin.*') ? 'layouts.super-admin' : 'layouts.app')

@section('content')
    @php
        $roles = $data['roles'];
        $permissions = $data['permissions'];
        $protectedRoles = ['super_admin', 'admin', 'cliente', 'vendedor', 'repartidor', 'empleado'];
        $protectedRolesCount = $roles->filter(fn ($role) => in_array($role->name, $protectedRoles, true))->count();
        $assignedUsersCount = (int) $roles->sum('users_count');
        $moduleCount = $permissions
            ->map(fn ($permission) => str((string) $permission->name)->before('.')->toString())
            ->filter()
            ->unique()
            ->count();
    @endphp

    <section class="mx-auto max-w-7xl px-4 py-8">
        <div class="max-w-3xl">
            <h1 class="text-4xl font-black tracking-tight text-atlantia-ink sm:text-5xl">Roles y permisos</h1>
            <p class="mt-4 max-w-2xl text-lg leading-8 text-atlantia-ink/70">
                Gestiona accesos del sistema y define permisos listos para modulos escalables.
                Controla con precision quien puede hacer que dentro de Atlantia.
            </p>
        </div>

        <div class="mt-8 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <article class="rounded-3xl border border-atlantia-rose/20 bg-white p-6 shadow-sm">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <p class="text-sm font-semibold uppercase tracking-[0.16em] text-atlantia-ink/65">Roles activos</p>
                        <p class="mt-6 text-5xl font-black text-atlantia-ink">{{ $roles->count() }}</p>
                        <p class="mt-2 text-sm font-semibold text-emerald-700">Base operativa actual</p>
                    </div>
                    <span class="grid h-12 w-12 place-items-center rounded-2xl bg-atlantia-blush text-atlantia-wine">
                        <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                            <path d="M12 3L18 5.5V11.3C18 15.2 15.5 18.8 12 20C8.5 18.8 6 15.2 6 11.3V5.5L12 3Z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/>
                        </svg>
                    </span>
                </div>
            </article>

            <article class="rounded-3xl border border-atlantia-rose/20 bg-white p-6 shadow-sm">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <p class="text-sm font-semibold uppercase tracking-[0.16em] text-atlantia-ink/65">Permisos totales</p>
                        <p class="mt-6 text-5xl font-black text-atlantia-ink">{{ $permissions->count() }}</p>
                        <p class="mt-2 text-sm font-semibold text-atlantia-ink/65">{{ $moduleCount }} modulos escalables</p>
                    </div>
                    <span class="grid h-12 w-12 place-items-center rounded-2xl bg-sky-50 text-sky-600">
                        <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                            <path d="M8 10V7.8C8 5.7 9.8 4 12 4C14.2 4 16 5.7 16 7.8V10" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                            <rect x="5" y="10" width="14" height="10" rx="2.2" stroke="currentColor" stroke-width="1.8"/>
                        </svg>
                    </span>
                </div>
            </article>

            <article class="rounded-3xl border border-atlantia-rose/20 bg-white p-6 shadow-sm">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <p class="text-sm font-semibold uppercase tracking-[0.16em] text-atlantia-ink/65">Usuarios asignados</p>
                        <p class="mt-6 text-5xl font-black text-atlantia-ink">{{ $assignedUsersCount }}</p>
                        <p class="mt-2 text-sm font-semibold text-emerald-700">Distribuidos entre roles</p>
                    </div>
                    <span class="grid h-12 w-12 place-items-center rounded-2xl bg-emerald-50 text-emerald-600">
                        <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                            <path d="M16 19V17.8C16 15.7 14.2 14 12 14H8C5.8 14 4 15.7 4 17.8V19" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                            <circle cx="10" cy="8" r="3" stroke="currentColor" stroke-width="1.8"/>
                            <path d="M20 19V17.9C20 16.2 18.8 14.8 17.2 14.4" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                            <path d="M15.2 5.4C16.8 5.8 18 7.2 18 8.9C18 10.6 16.8 12 15.2 12.4" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                        </svg>
                    </span>
                </div>
            </article>

            <article class="rounded-3xl border border-atlantia-rose/20 bg-white p-6 shadow-sm">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <p class="text-sm font-semibold uppercase tracking-[0.16em] text-atlantia-ink/65">Roles protegidos</p>
                        <p class="mt-6 text-5xl font-black text-atlantia-ink">{{ $protectedRolesCount }}</p>
                        <p class="mt-2 text-sm font-semibold text-atlantia-ink/65">No editables en estructura base</p>
                    </div>
                    <span class="grid h-12 w-12 place-items-center rounded-2xl bg-amber-50 text-amber-600">
                        <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                            <path d="M8 10V7.8C8 5.7 9.8 4 12 4C14.2 4 16 5.7 16 7.8V10" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                            <rect x="5" y="10" width="14" height="10" rx="2.2" stroke="currentColor" stroke-width="1.8"/>
                            <path d="M12 13V17" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                        </svg>
                    </span>
                </div>
            </article>
        </div>

        <div class="mt-8 grid gap-6 xl:grid-cols-[420px_1fr]">
            <div class="space-y-6">
                <section class="rounded-3xl border border-atlantia-rose/20 bg-white p-4 shadow-sm">
                    <div class="grid grid-cols-2 gap-2 rounded-2xl bg-atlantia-blush/50 p-1">
                        <button
                            type="button"
                            class="rounded-2xl bg-white px-4 py-3 text-sm font-bold text-atlantia-wine shadow-sm"
                            data-role-tab-trigger="role"
                        >
                            Crear rol
                        </button>
                        <button
                            type="button"
                            class="rounded-2xl px-4 py-3 text-sm font-bold text-atlantia-ink/70 transition hover:text-atlantia-wine"
                            data-role-tab-trigger="permission"
                        >
                            Crear permiso
                        </button>
                    </div>

                    <div class="mt-5" data-role-tab-panel="role">
                        <form
                            method="POST"
                            action="{{ route('admin.roles-permisos.store') }}"
                            class="rounded-3xl border border-atlantia-rose/20 bg-white p-6"
                        >
                            @csrf
                            <h2 class="text-3xl font-black text-atlantia-wine">Nuevo rol</h2>
                            <p class="mt-2 text-sm leading-6 text-atlantia-ink/65">
                                Usa nombres tecnicos como
                                <span class="rounded bg-atlantia-blush px-2 py-1 font-mono text-xs text-atlantia-wine">supervisor_bodega</span>
                                o
                                <span class="rounded bg-atlantia-blush px-2 py-1 font-mono text-xs text-atlantia-wine">soporte_cliente</span>.
                            </p>

                            <label for="role-name" class="mt-6 block text-sm font-bold text-atlantia-ink">
                                Nombre tecnico
                            </label>
                            <input
                                id="role-name"
                                name="name"
                                type="text"
                                value="{{ old('name') }}"
                                placeholder="supervisor_bodega"
                                class="mt-2 w-full rounded-2xl border border-atlantia-rose/30 px-4 py-3 text-sm focus:border-atlantia-wine focus:ring-atlantia-rose"
                                required
                            >
                            <p class="mt-2 text-xs font-medium text-atlantia-ink/55">Solo minusculas, numeros y guion bajo</p>

                            <div class="mt-6">
                                <p class="text-sm font-bold text-atlantia-ink">Permisos iniciales</p>
                                <div class="mt-3 rounded-2xl border border-atlantia-rose/20 bg-white px-4 py-3">
                                    <div class="flex items-center gap-3 text-atlantia-ink/50">
                                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                            <path d="M11 19C15.4 19 19 15.4 19 11C19 6.6 15.4 3 11 3C6.6 3 3 6.6 3 11C3 15.4 6.6 19 11 19Z" stroke="currentColor" stroke-width="2"/>
                                            <path d="M20.5 20.5L16.7 16.7" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                                        </svg>
                                        <input
                                            type="text"
                                            placeholder="Buscar permiso..."
                                            class="w-full border-0 bg-transparent p-0 text-sm placeholder:text-atlantia-ink/45 focus:outline-none focus:ring-0"
                                            data-permission-search="create-role"
                                        >
                                    </div>
                                </div>

                                <div class="mt-3 max-h-80 space-y-2 overflow-y-auto rounded-3xl border border-atlantia-rose/20 p-4" data-permission-list="create-role">
                                    @foreach ($permissions as $permission)
                                        <label class="flex items-center gap-3 rounded-xl px-2 py-2 text-sm text-atlantia-ink" data-permission-item="{{ strtolower($permission->name) }}">
                                            <input
                                                type="checkbox"
                                                name="permissions[]"
                                                value="{{ $permission->name }}"
                                                class="rounded border-atlantia-rose text-atlantia-wine focus:ring-atlantia-rose"
                                            >
                                            <span class="font-mono text-[15px]">{{ $permission->name }}</span>
                                        </label>
                                    @endforeach
                                </div>
                            </div>

                            <x-ui.button type="submit" class="mt-5 w-full">
                                <span class="inline-flex items-center gap-2">
                                    <span class="text-lg leading-none">+</span>
                                    Crear rol
                                </span>
                            </x-ui.button>
                        </form>
                    </div>

                    <div class="mt-5 hidden" data-role-tab-panel="permission">
                        <form
                            method="POST"
                            action="{{ route('admin.roles-permisos.permisos.store') }}"
                            class="rounded-3xl border border-atlantia-rose/20 bg-white p-6"
                        >
                            @csrf
                            <h2 class="text-3xl font-black text-atlantia-wine">Nuevo permiso</h2>
                            <p class="mt-2 text-sm leading-6 text-atlantia-ink/65">
                                Crea permisos tecnicos para modulos nuevos sin romper la estructura actual de roles.
                            </p>

                            <label for="permission-name" class="mt-6 block text-sm font-bold text-atlantia-ink">
                                Nombre tecnico
                            </label>
                            <input
                                id="permission-name"
                                name="name"
                                type="text"
                                value="{{ old('permission_name') }}"
                                placeholder="bodega.transferencias.validar"
                                class="mt-2 w-full rounded-2xl border border-atlantia-rose/30 px-4 py-3 text-sm focus:border-atlantia-wine focus:ring-atlantia-rose"
                                required
                            >
                            <p class="mt-2 text-xs font-medium text-atlantia-ink/55">
                                Usa segmentos con punto para mantener escalabilidad por modulo.
                            </p>

                            <x-ui.button type="submit" variant="secondary" class="mt-6 w-full">
                                Crear permiso
                            </x-ui.button>
                        </form>
                    </div>
                </section>
            </div>

            <div class="space-y-4">
                <section class="rounded-3xl border border-atlantia-rose/20 bg-white p-4 shadow-sm">
                    <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                        <div class="relative flex-1">
                            <span class="pointer-events-none absolute left-4 top-1/2 -translate-y-1/2 text-atlantia-ink/45">
                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                    <path d="M11 19C15.4 19 19 15.4 19 11C19 6.6 15.4 3 11 3C6.6 3 3 6.6 3 11C3 15.4 6.6 19 11 19Z" stroke="currentColor" stroke-width="2"/>
                                    <path d="M20.5 20.5L16.7 16.7" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                                </svg>
                            </span>
                            <input
                                type="text"
                                placeholder="Buscar rol o permiso"
                                class="w-full rounded-2xl border border-atlantia-rose/25 bg-white py-3 pl-11 pr-4 text-sm focus:border-atlantia-wine focus:ring-atlantia-rose"
                                data-role-filter-search
                            >
                        </div>

                        <div class="flex flex-wrap gap-2">
                            <button type="button" class="rounded-full bg-atlantia-wine px-4 py-2 text-sm font-bold text-white" data-role-filter="all">Todos</button>
                            <button type="button" class="rounded-full border border-atlantia-rose/25 px-4 py-2 text-sm font-semibold text-atlantia-ink/80" data-role-filter="protected">Protegidos</button>
                            <button type="button" class="rounded-full border border-atlantia-rose/25 px-4 py-2 text-sm font-semibold text-atlantia-ink/80" data-role-filter="custom">Personalizados</button>
                        </div>
                    </div>
                </section>

                @foreach ($roles as $role)
                    @php
                        $isProtected = in_array($role->name, $protectedRoles, true);
                        $initials = strtoupper(substr($role->name, 0, 2));
                        $badgeClasses = ['bg-rose-100 text-atlantia-wine', 'bg-blue-100 text-blue-700', 'bg-emerald-100 text-emerald-700', 'bg-amber-100 text-amber-700', 'bg-violet-100 text-violet-700'];
                        $badgeClass = $badgeClasses[$loop->index % count($badgeClasses)];
                    @endphp

                    <article
                        class="overflow-hidden rounded-3xl border border-atlantia-rose/20 bg-white shadow-sm"
                        data-role-card
                        data-role-name="{{ strtolower($role->name) }}"
                        data-role-type="{{ $isProtected ? 'protected' : 'custom' }}"
                        data-role-permissions="{{ strtolower($role->permissions->pluck('name')->implode(' ')) }}"
                    >
                        <div class="flex flex-col gap-4 border-b border-atlantia-rose/15 px-6 py-5 lg:flex-row lg:items-start lg:justify-between">
                            <div class="flex items-start gap-4">
                                <div class="{{ $badgeClass }} grid h-12 w-12 place-items-center rounded-2xl text-lg font-black">
                                    {{ $initials }}
                                </div>

                                <div class="grid gap-1 sm:grid-cols-[minmax(0,1fr)_auto_auto] sm:items-center sm:gap-x-8">
                                    <div>
                                        <h2 class="text-3xl font-black capitalize text-atlantia-ink">{{ str_replace('_', ' ', $role->name) }}</h2>
                                        <p class="text-sm leading-6 text-atlantia-ink/65">
                                            {{ $role->permissions->count() }} permisos asignados
                                        </p>
                                    </div>
                                    <p class="text-sm leading-6 text-atlantia-ink/65">
                                        <span class="block text-2xl font-black text-atlantia-ink">{{ $role->users_count }}</span>
                                        usuarios
                                    </p>
                                    <span class="inline-flex items-center rounded-full bg-atlantia-blush px-4 py-2 text-sm font-bold text-atlantia-wine">
                                        <span class="mr-2 h-2 w-2 rounded-full bg-atlantia-wine"></span>
                                        {{ $isProtected ? 'Rol base protegido' : 'Rol personalizado' }}
                                    </span>
                                </div>
                            </div>

                            @if (! $isProtected)
                                <form method="POST" action="{{ route('admin.roles-permisos.destroy', $role) }}">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="rounded-full px-3 py-2 text-sm font-bold text-red-700 transition hover:bg-red-50">
                                        Eliminar
                                    </button>
                                </form>
                            @else
                                <button type="button" class="rounded-full px-2 py-2 text-atlantia-ink/45" aria-label="Rol protegido">
                                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                                        <circle cx="12" cy="5" r="1.8"/>
                                        <circle cx="12" cy="12" r="1.8"/>
                                        <circle cx="12" cy="19" r="1.8"/>
                                    </svg>
                                </button>
                            @endif
                        </div>

                        <form method="POST" action="{{ route('admin.roles-permisos.sync', $role) }}">
                            @csrf
                            @method('PUT')

                            <div class="max-h-80 space-y-2 overflow-y-auto px-6 py-5">
                                @foreach ($permissions as $permission)
                                    <label class="flex items-center gap-3 rounded-xl px-2 py-2 text-sm text-atlantia-ink">
                                        <input
                                            type="checkbox"
                                            name="permissions[]"
                                            value="{{ $permission->name }}"
                                            @checked($role->permissions->contains('name', $permission->name))
                                            @disabled($role->name === 'super_admin')
                                            class="rounded border-atlantia-rose text-atlantia-wine focus:ring-atlantia-rose"
                                        >
                                        <span class="font-mono text-[15px]">{{ $permission->name }}</span>
                                    </label>
                                @endforeach
                            </div>

                            <div class="flex flex-col gap-4 border-t border-atlantia-rose/15 bg-[#fcfafb] px-6 py-5 sm:flex-row sm:items-center sm:justify-between">
                                <p class="text-sm font-medium text-atlantia-ink/60">
                                    @if ($role->name === 'super_admin')
                                        Rol protegido contra cambios directos.
                                    @else
                                        Todo guardado
                                    @endif
                                </p>

                                <div class="flex flex-wrap gap-3">
                                    <button
                                        type="reset"
                                        class="rounded-2xl border border-atlantia-rose/25 px-6 py-3 text-sm font-bold text-atlantia-ink/70 transition hover:bg-white"
                                    >
                                        Descartar
                                    </button>
                                    <x-ui.button type="submit" class="min-w-[170px]" :disabled="$role->name === 'super_admin'">
                                        Guardar permisos
                                    </x-ui.button>
                                </div>
                            </div>
                        </form>
                    </article>
                @endforeach
            </div>
        </div>
    </section>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const tabTriggers = document.querySelectorAll('[data-role-tab-trigger]');
            const tabPanels = document.querySelectorAll('[data-role-tab-panel]');

            const activateTab = (name) => {
                tabTriggers.forEach((trigger) => {
                    const active = trigger.dataset.roleTabTrigger === name;
                    trigger.classList.toggle('bg-white', active);
                    trigger.classList.toggle('text-atlantia-wine', active);
                    trigger.classList.toggle('shadow-sm', active);
                    trigger.classList.toggle('text-atlantia-ink/70', ! active);
                });

                tabPanels.forEach((panel) => {
                    panel.classList.toggle('hidden', panel.dataset.roleTabPanel !== name);
                });
            };

            tabTriggers.forEach((trigger) => {
                trigger.addEventListener('click', () => activateTab(trigger.dataset.roleTabTrigger));
            });

            activateTab('role');

            const search = document.querySelector('[data-role-filter-search]');
            const filterButtons = document.querySelectorAll('[data-role-filter]');
            const roleCards = document.querySelectorAll('[data-role-card]');
            let activeFilter = 'all';

            const renderCards = () => {
                const term = (search?.value ?? '').trim().toLowerCase();

                roleCards.forEach((card) => {
                    const byFilter = activeFilter === 'all' || card.dataset.roleType === activeFilter;
                    const haystack = `${card.dataset.roleName} ${card.dataset.rolePermissions}`;
                    const bySearch = term === '' || haystack.includes(term);
                    card.classList.toggle('hidden', ! (byFilter && bySearch));
                });
            };

            filterButtons.forEach((button) => {
                button.addEventListener('click', () => {
                    activeFilter = button.dataset.roleFilter;

                    filterButtons.forEach((item) => {
                        const selected = item === button;
                        item.classList.toggle('bg-atlantia-wine', selected);
                        item.classList.toggle('text-white', selected);
                        item.classList.toggle('border-atlantia-wine', selected);
                        item.classList.toggle('font-bold', selected);
                        item.classList.toggle('text-atlantia-ink/80', ! selected);
                    });

                    renderCards();
                });
            });

            search?.addEventListener('input', renderCards);
            renderCards();

            document.querySelectorAll('[data-permission-search]').forEach((input) => {
                input.addEventListener('input', () => {
                    const term = input.value.trim().toLowerCase();
                    const target = input.dataset.permissionSearch;
                    const list = document.querySelector(`[data-permission-list="${target}"]`);

                    list?.querySelectorAll('[data-permission-item]').forEach((item) => {
                        item.classList.toggle('hidden', term !== '' && ! item.dataset.permissionItem.includes(term));
                    });
                });
            });
        });
    </script>
@endpush
