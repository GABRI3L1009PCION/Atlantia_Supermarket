@extends(auth()->user()?->isSuperAdmin() && request()->routeIs('admin.*') ? 'layouts.super-admin' : 'layouts.app')

@section('content')
    @php
        $availableRoles = auth()->user()?->isSuperAdmin()
            ? $roles
            : $roles->reject(fn ($role) => in_array($role->name, ['admin', 'super_admin'], true));
        $departments = [
            'operaciones' => 'Operaciones',
            'ventas' => 'Ventas',
            'logistica' => 'Logistica',
            'bodega' => 'Bodega',
            'compras' => 'Compras y proveedores',
            'soporte' => 'Soporte',
            'contabilidad' => 'Contabilidad',
            'finanzas' => 'Finanzas',
            'tecnologia' => 'Tecnologia',
        ];
        $roleLabels = [
            'cliente' => 'Cliente',
            'vendedor' => 'Emprendedor',
            'bodeguero' => 'Bodeguero',
            'proveedor' => 'Proveedor',
            'soporte' => 'Soporte',
            'contabilidad_finanzas' => 'Contabilidad y finanzas',
            'supervisor_logistica' => 'Supervisor de logistica',
            'empleado' => 'Empleado',
            'repartidor' => 'Repartidor',
            'admin' => 'Admin',
            'super_admin' => 'Super admin',
        ];
        $roleIcons = [
            'cliente' => 'CL',
            'vendedor' => 'EP',
            'bodeguero' => 'BO',
            'proveedor' => 'PR',
            'soporte' => 'SO',
            'contabilidad_finanzas' => 'CF',
            'supervisor_logistica' => 'SL',
            'empleado' => 'EM',
            'repartidor' => 'RP',
            'admin' => 'AD',
            'super_admin' => 'SA',
        ];
        $statusClasses = [
            'active' => 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-200',
            'inactive' => 'bg-slate-100 text-slate-600 ring-1 ring-slate-200',
            'suspended' => 'bg-amber-50 text-amber-700 ring-1 ring-amber-200',
        ];
        $statusLabels = [
            'active' => 'Activo',
            'inactive' => 'Inactivo',
            'suspended' => 'Suspendido',
        ];
        $usersPayload = $usuarios->getCollection()->mapWithKeys(function ($usuario) use ($roleLabels, $statusLabels) {
            return [
                $usuario->id => [
                    'id' => $usuario->id,
                    'name' => $usuario->name,
                    'email' => $usuario->email,
                    'phone' => $usuario->phone,
                    'status' => $usuario->status,
                    'status_label' => $statusLabels[$usuario->status] ?? ucfirst($usuario->status),
                    'roles' => $usuario->roles->pluck('name')->values(),
                    'roles_label' => $usuario->roles->pluck('name')->map(fn ($role) => $roleLabels[$role] ?? ucfirst($role))->join(', ') ?: 'Sin rol',
                    'created_at' => optional($usuario->created_at)->format('d/m/Y H:i'),
                    'created_relative' => optional($usuario->created_at)->diffForHumans(),
                    'updated_at' => optional($usuario->updated_at)->format('d/m/Y H:i'),
                    'updated_relative' => optional($usuario->updated_at)->diffForHumans(),
                    'last_activity' => optional($usuario->last_login_at ?? $usuario->updated_at)->diffForHumans() ?: 'Sin actividad',
                    'created_by' => 'Administracion Atlantia',
                    'updated_by' => 'Administracion Atlantia',
                    'inactive_reason' => $usuario->status === 'inactive' ? 'Cuenta desactivada administrativamente.' : null,
                    'show_url' => route('admin.usuarios.show', $usuario),
                    'update_url' => route('admin.usuarios.update', $usuario),
                    'delete_url' => route('admin.usuarios.destroy', $usuario),
                ],
            ];
        });
        $pageUsers = $usuarios->getCollection();
        $totalUsers = max(1, $usuarios->total());
        $activeUsers = $pageUsers->where('status', 'active')->count();
        $pendingUsers = $pageUsers->filter(fn ($usuario) => $usuario->email_verified_at === null || $usuario->status === 'pending')->count();
        $adminUsers = $pageUsers->filter(fn ($usuario) => $usuario->hasAnyRole(['admin', 'super_admin']))->count();
        $avatarColors = ['bg-rose-100 text-rose-700', 'bg-orange-100 text-orange-700', 'bg-violet-100 text-violet-700', 'bg-sky-100 text-sky-700', 'bg-emerald-100 text-emerald-700', 'bg-amber-100 text-amber-700'];
        $roleBadgeClasses = [
            'admin' => 'bg-rose-50 text-rose-700 ring-rose-200',
            'super_admin' => 'bg-rose-50 text-rose-700 ring-rose-200',
            'repartidor' => 'bg-sky-50 text-sky-700 ring-sky-200',
            'vendedor' => 'bg-violet-50 text-violet-700 ring-violet-200',
            'empleado' => 'bg-amber-50 text-amber-700 ring-amber-200',
            'cliente' => 'bg-emerald-50 text-emerald-700 ring-emerald-200',
        ];
    @endphp

    <section class="users-light-surface -mx-4 -my-6 min-h-[calc(100vh-4rem)] bg-white px-4 py-5 text-atlantia-ink sm:-mx-6 sm:px-6 lg:-mx-8 lg:px-8">
        <div class="mx-auto max-w-7xl">
            <div class="grid gap-4 lg:grid-cols-[1fr_0.82fr_auto] lg:items-center">
                <div class="flex items-center gap-4">
                    <span class="grid h-14 w-14 shrink-0 place-items-center rounded-md bg-atlantia-blush text-atlantia-wine shadow-sm">
                        <svg class="h-7 w-7" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                            <path d="M16 21V19C16 17.3 14.7 16 13 16H7C5.3 16 4 17.3 4 19V21M13 8C13 10.2 11.2 12 9 12C6.8 12 5 10.2 5 8C5 5.8 6.8 4 9 4C11.2 4 13 5.8 13 8ZM20 21V19.5C20 18.1 19.1 16.9 17.8 16.4M16 4.2C17.7 4.7 19 6.2 19 8C19 9.8 17.7 11.3 16 11.8" stroke="currentColor" stroke-width="1.9" stroke-linecap="round"/>
                        </svg>
                    </span>
                    <div>
                        <h1 class="text-3xl font-black leading-tight text-atlantia-ink">Usuarios</h1>
                        <p class="mt-1 max-w-2xl text-sm leading-6 text-atlantia-ink/60">
                            Gestiona cuentas, roles, estados y actividad operativa del marketplace.
                        </p>
                        <span id="breadcrumb-user" class="sr-only">Listado</span>
                    </div>
                </div>

                <div class="rounded-md border border-atlantia-rose/20 bg-white px-4 py-3 text-sm shadow-sm">
                    <p class="font-black text-atlantia-ink">{{ $usuarios->total() }} resultados encontrados</p>
                    <p class="mt-1 text-xs text-atlantia-ink/58">Mostrando maximo 10 usuarios por pagina.</p>
                </div>

                <button
                    type="button"
                    class="inline-flex min-h-12 items-center justify-center gap-3 rounded-md bg-atlantia-wine px-6 text-sm font-black text-white shadow-[0_14px_28px_rgba(122,31,61,0.18)] transition hover:bg-atlantia-wine-700 focus:outline-none focus:ring-2 focus:ring-atlantia-rose focus:ring-offset-2"
                    data-open-create-modal
                >
                    <span class="text-xl leading-none">+</span>
                    Crear nuevo usuario
                </button>
            </div>

            <div class="mt-6 grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                <article class="rounded-md border border-atlantia-rose/20 bg-white p-5 shadow-sm">
                    <div class="flex items-center gap-4">
                        <span class="grid h-12 w-12 place-items-center rounded-md bg-rose-50 text-atlantia-wine">
                            <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                <path d="M16 21V19C16 17.3 14.7 16 13 16H7C5.3 16 4 17.3 4 19V21M13 8C13 10.2 11.2 12 9 12C6.8 12 5 10.2 5 8C5 5.8 6.8 4 9 4C11.2 4 13 5.8 13 8ZM20 21V19.5C20 18.1 19.1 16.9 17.8 16.4" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                            </svg>
                        </span>
                        <div>
                            <p class="text-2xl font-black text-atlantia-ink">{{ number_format($usuarios->total()) }}</p>
                            <p class="text-sm font-black text-atlantia-ink">Total usuarios</p>
                            <p class="text-xs text-atlantia-ink/55">Todos los registros</p>
                        </div>
                    </div>
                </article>

                <article class="rounded-md border border-atlantia-rose/20 bg-white p-5 shadow-sm">
                    <div class="flex items-center gap-4">
                        <span class="grid h-12 w-12 place-items-center rounded-md bg-emerald-50 text-emerald-700">
                            <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                <path d="M5 12L10 17L20 7" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </span>
                        <div>
                            <p class="text-2xl font-black text-atlantia-ink">{{ number_format($activeUsers) }}</p>
                            <p class="text-sm font-black text-atlantia-ink">Usuarios activos</p>
                            <p class="text-xs text-atlantia-ink/55">{{ number_format(($activeUsers / max(1, $pageUsers->count())) * 100, 0) }}% del listado</p>
                        </div>
                    </div>
                </article>

                <article class="rounded-md border border-atlantia-rose/20 bg-white p-5 shadow-sm">
                    <div class="flex items-center gap-4">
                        <span class="grid h-12 w-12 place-items-center rounded-md bg-orange-50 text-orange-600">
                            <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                <path d="M12 7V12L15 15M21 12C21 17 17 21 12 21C7 21 3 17 3 12C3 7 7 3 12 3C17 3 21 7 21 12Z" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                            </svg>
                        </span>
                        <div>
                            <p class="text-2xl font-black text-atlantia-ink">{{ number_format($pendingUsers) }}</p>
                            <p class="text-sm font-black text-atlantia-ink">Usuarios pendientes</p>
                            <p class="text-xs text-atlantia-ink/55">{{ number_format(($pendingUsers / $totalUsers) * 100, 0) }}% del total</p>
                        </div>
                    </div>
                </article>

                <article class="rounded-md border border-atlantia-rose/20 bg-white p-5 shadow-sm">
                    <div class="flex items-center gap-4">
                        <span class="grid h-12 w-12 place-items-center rounded-md bg-violet-50 text-violet-700">
                            <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                <path d="M12 3L19 6V11.5C19 16.2 16 19.8 12 21C8 19.8 5 16.2 5 11.5V6L12 3Z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/>
                                <path d="M9 12L11 14L15 10" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </span>
                        <div>
                            <p class="text-2xl font-black text-atlantia-ink">{{ number_format($adminUsers) }}</p>
                            <p class="text-sm font-black text-atlantia-ink">Administradores</p>
                            <p class="text-xs text-atlantia-ink/55">{{ number_format(($adminUsers / $totalUsers) * 100, 0) }}% del total</p>
                        </div>
                    </div>
                </article>
            </div>

            <form method="GET" class="relative mt-5 rounded-md border border-atlantia-rose/20 bg-white p-4 shadow-sm" data-filter-form>
                <div class="absolute inset-0 z-10 hidden rounded-md bg-white/85 p-5 backdrop-blur" data-table-loading>
                    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                        @for ($i = 0; $i < 4; $i++)
                            <div class="h-36 animate-pulse rounded-md bg-atlantia-blush/60"></div>
                        @endfor
                    </div>
                </div>

                <div class="grid gap-3 lg:grid-cols-[1.25fr_0.8fr_0.8fr_0.85fr_auto_auto]">
                    <label class="flex min-h-11 items-center gap-3 rounded-md border border-atlantia-rose/25 bg-white px-3 text-sm text-atlantia-ink/60 focus-within:border-atlantia-wine focus-within:ring-2 focus-within:ring-atlantia-rose/15">
                        <svg class="h-5 w-5 shrink-0" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                            <path d="M10.5 18C14.6 18 18 14.6 18 10.5C18 6.4 14.6 3 10.5 3C6.4 3 3 6.4 3 10.5C3 14.6 6.4 18 10.5 18ZM16 16L21 21" stroke="currentColor" stroke-width="1.9" stroke-linecap="round"/>
                        </svg>
                        <input type="search" name="q" value="{{ request('q') }}" placeholder="Buscar por nombre o correo" class="min-w-0 flex-1 border-0 bg-transparent py-2 text-sm outline-none focus:ring-0">
                    </label>

                    <select name="role" class="min-h-11 rounded-md border border-atlantia-rose/25 bg-white px-3 text-sm text-atlantia-ink">
                        <option value="">Todos los roles</option>
                        @foreach ($availableRoles as $role)
                            <option value="{{ $role->name }}" @selected(request('role') === $role->name)>{{ $roleLabels[$role->name] ?? $role->name }}</option>
                        @endforeach
                    </select>

                    <select name="status" class="min-h-11 rounded-md border border-atlantia-rose/25 bg-white px-3 text-sm text-atlantia-ink">
                        <option value="">Todos los estados</option>
                        <option value="active" @selected(request('status') === 'active')>Activo</option>
                        <option value="inactive" @selected(request('status') === 'inactive')>Inactivo</option>
                        <option value="pending" @selected(request('status') === 'pending')>Pendiente</option>
                    </select>

                    <select name="created_range" class="min-h-11 rounded-md border border-atlantia-rose/25 bg-white px-3 text-sm text-atlantia-ink" data-created-range>
                        <option value="">Cualquier fecha</option>
                        <option value="7_days" @selected(request('created_range') === '7_days')>Ultimos 7 dias</option>
                        <option value="month" @selected(request('created_range') === 'month')>Ultimo mes</option>
                        <option value="custom" @selected(request('created_range') === 'custom')>Personalizado</option>
                    </select>

                    <button type="submit" class="inline-flex min-h-11 items-center justify-center gap-2 rounded-md bg-atlantia-wine px-5 text-sm font-black text-white shadow-sm transition hover:bg-atlantia-wine-700">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                            <path d="M4 6H20M7 12H17M10 18H14" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                        </svg>
                        Filtrar
                    </button>

                    <a href="{{ route('admin.usuarios.index') }}" class="inline-flex min-h-11 items-center justify-center gap-2 rounded-md border border-atlantia-rose/25 bg-white px-5 text-sm font-black text-atlantia-ink/65 transition hover:bg-atlantia-blush">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                            <path d="M20 6V11H15M4 18V13H9M5.7 9C6.9 6.6 9.3 5 12 5C15.1 5 17.8 7 18.7 9.8M18.3 15C17.1 17.4 14.7 19 12 19C8.9 19 6.2 17 5.3 14.2" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                        Limpiar
                    </a>
                </div>

                <div class="{{ request('created_range') === 'custom' ? 'grid' : 'hidden' }} mt-3 gap-3 sm:grid-cols-[1fr_1fr]" data-custom-dates>
                    <input type="date" name="created_from" value="{{ request('created_from') }}" class="rounded-md border border-atlantia-rose/25 bg-white px-3 py-2 text-sm">
                    <input type="date" name="created_to" value="{{ request('created_to') }}" class="rounded-md border border-atlantia-rose/25 bg-white px-3 py-2 text-sm">
                </div>
            </form>

            <div class="mt-5 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                @forelse ($usuarios as $usuario)
                    @php
                        $primaryRole = $usuario->roles->first()?->name;
                        $roleText = $usuario->roles->pluck('name')->map(fn ($role) => $roleLabels[$role] ?? ucfirst($role))->join(', ') ?: 'Sin rol';
                        $initials = collect(preg_split('/\s+/', trim($usuario->name)) ?: [])
                            ->filter()
                            ->take(2)
                            ->map(fn ($part) => mb_strtoupper(mb_substr($part, 0, 1)))
                            ->join('') ?: 'U';
                        $avatarClass = $avatarColors[$loop->index % count($avatarColors)];
                        $roleBadgeClass = $roleBadgeClasses[$primaryRole] ?? 'bg-slate-50 text-slate-700 ring-slate-200';
                        $statusClass = $statusClasses[$usuario->status] ?? $statusClasses['inactive'];
                        $statusText = $statusLabels[$usuario->status] ?? ucfirst($usuario->status);
                        if ($usuario->email_verified_at === null) {
                            $statusClass = 'bg-orange-50 text-orange-700 ring-1 ring-orange-200';
                            $statusText = 'Pendiente';
                        }
                    @endphp

                    <article class="group flex min-h-[258px] flex-col rounded-md border border-atlantia-rose/15 bg-white p-4 shadow-sm transition hover:-translate-y-0.5 hover:shadow-[0_18px_40px_rgba(42,16,24,0.10)]" data-user-row data-user-id="{{ $usuario->id }}">
                        <div class="flex items-start justify-between gap-3">
                            <div class="flex min-w-0 items-center gap-3">
                                <span class="{{ $avatarClass }} grid h-12 w-12 shrink-0 place-items-center rounded-full text-sm font-black">
                                    {{ $initials }}
                                </span>
                                <div class="min-w-0">
                                    <h2 class="truncate text-sm font-black text-atlantia-ink" data-row-name>{{ $usuario->name }}</h2>
                                    <p class="truncate text-xs text-atlantia-ink/58" data-row-email>{{ $usuario->email }}</p>
                                </div>
                            </div>
                            <div class="relative" data-no-row-open>
                                <input type="checkbox" value="{{ $usuario->id }}" class="sr-only" data-row-check>
                                <button type="button" class="rounded-md p-1 text-atlantia-ink/45 transition hover:bg-atlantia-blush hover:text-atlantia-wine" data-view-user="{{ $usuario->id }}" aria-label="Abrir acciones de {{ $usuario->name }}">
                                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                        <path d="M12 5.5H12.01M12 12H12.01M12 18.5H12.01" stroke="currentColor" stroke-width="2.4" stroke-linecap="round"/>
                                    </svg>
                                </button>
                            </div>
                        </div>

                        <div class="mt-4 flex min-h-[30px] flex-wrap gap-2 text-xs">
                            <span class="{{ $roleBadgeClass }} inline-flex items-center gap-1 rounded-md px-2.5 py-1 font-black ring-1" data-row-roles>
                                <span>{{ $roleIcons[$primaryRole] ?? 'SR' }}</span>
                                {{ $roleText }}
                            </span>
                            <span class="{{ $statusClass }} inline-flex items-center gap-1 rounded-md px-2.5 py-1 font-black" data-row-status>
                                <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                    <path d="M5 12L10 17L20 7" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                                {{ $statusText }}
                            </span>
                        </div>

                        <div class="mt-auto grid grid-cols-2 gap-3 border-t border-atlantia-rose/15 pt-3 text-xs">
                            <div>
                                <p class="text-atlantia-ink/45">Ultima actividad</p>
                                <p class="mt-1 font-semibold text-atlantia-ink/70">{{ optional($usuario->last_login_at ?? $usuario->updated_at)->diffForHumans() ?: 'Sin actividad' }}</p>
                            </div>
                            <div class="border-l border-atlantia-rose/15 pl-3">
                                <p class="text-atlantia-ink/45">Creado</p>
                                <p class="mt-1 font-semibold text-atlantia-ink/70">{{ optional($usuario->created_at)->diffForHumans() }}</p>
                            </div>
                        </div>

                        <div class="mt-4 grid grid-cols-2 gap-3" data-no-row-open>
                            <button type="button" class="inline-flex min-h-10 items-center justify-center gap-2 rounded-md border border-atlantia-rose/25 bg-white px-3 text-xs font-black text-atlantia-wine transition hover:bg-atlantia-blush" data-edit-user="{{ $usuario->id }}" data-fallback-url="{{ route('admin.usuarios.show', $usuario) }}">
                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                    <path d="M4 20H8L18.5 9.5C19.6 8.4 19.6 6.6 18.5 5.5C17.4 4.4 15.6 4.4 14.5 5.5L4 16V20Z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/>
                                </svg>
                                Editar
                            </button>
                            <a href="{{ route('admin.usuarios.show', $usuario) }}" class="inline-flex min-h-10 items-center justify-center gap-2 rounded-md bg-atlantia-wine px-3 text-xs font-black text-white transition hover:bg-atlantia-wine-700" data-view-user="{{ $usuario->id }}">
                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                    <path d="M3.5 12C5.5 8.7 8.3 7 12 7C15.7 7 18.5 8.7 20.5 12C18.5 15.3 15.7 17 12 17C8.3 17 5.5 15.3 3.5 12Z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/>
                                    <path d="M12 14.2C13.2 14.2 14.2 13.2 14.2 12C14.2 10.8 13.2 9.8 12 9.8C10.8 9.8 9.8 10.8 9.8 12C9.8 13.2 10.8 14.2 12 14.2Z" stroke="currentColor" stroke-width="1.8"/>
                                </svg>
                                Ver detalles
                            </a>
                        </div>
                    </article>
                @empty
                    <div class="col-span-full rounded-md border border-atlantia-rose/20 bg-white px-6 py-14 text-center shadow-sm">
                        <div class="mx-auto grid h-14 w-14 place-items-center rounded-xl bg-atlantia-blush text-lg font-black text-atlantia-wine">0</div>
                        <p class="mt-4 text-lg font-black text-atlantia-ink">No hay usuarios para mostrar</p>
                        <p class="mt-2 text-sm leading-6 text-atlantia-ink/60">Prueba limpiar filtros o crea una nueva cuenta desde el formulario.</p>
                    </div>
                @endforelse
            </div>

            <div class="mt-5 flex flex-col justify-between gap-3 border-t border-atlantia-rose/15 pt-4 text-sm text-atlantia-ink/60 sm:flex-row sm:items-center">
                <span>Pagina {{ $usuarios->currentPage() }} de {{ $usuarios->lastPage() }}</span>
                <div class="flex gap-2">
                    <a href="{{ $usuarios->previousPageUrl() ?: '#' }}" class="{{ $usuarios->onFirstPage() ? 'pointer-events-none opacity-40' : '' }} rounded-md border border-atlantia-rose/30 px-3 py-2 font-bold text-atlantia-wine transition hover:bg-atlantia-blush">Anterior</a>
                    <a href="{{ $usuarios->nextPageUrl() ?: '#' }}" class="{{ $usuarios->hasMorePages() ? '' : 'pointer-events-none opacity-40' }} rounded-md border border-atlantia-rose/30 px-3 py-2 font-bold text-atlantia-wine transition hover:bg-atlantia-blush">Siguiente</a>
                </div>
            </div>
        </div>
    </section>

    <div class="users-light-surface fixed inset-0 z-50 hidden items-center justify-center bg-slate-950/58 px-4 py-4 backdrop-blur-sm" data-create-modal>
        <section class="w-full max-w-4xl overflow-hidden rounded-xl border border-atlantia-rose/25 bg-white px-5 py-4 shadow-2xl sm:px-6">
            <div class="flex items-start justify-between gap-4">
                <div class="flex min-w-0 items-start gap-4">
                    <div data-name-avatar class="grid h-11 w-11 shrink-0 place-items-center rounded-lg bg-atlantia-wine text-xl font-black text-white shadow-lg shadow-atlantia-wine/20">
                        A
                    </div>
                    <div>
                        <p class="text-[11px] font-black uppercase tracking-[0.18em] text-atlantia-rose">Nuevo usuario</p>
                        <h2 class="text-2xl font-black leading-tight text-atlantia-ink">Crear usuario</h2>
                        <p class="mt-0.5 text-sm leading-5 text-atlantia-ink/60">
                            Completa los datos basicos y asigna rol inicial.
                        </p>
                    </div>
                </div>
                <button type="button" class="inline-flex min-h-9 items-center justify-center gap-2 rounded-md border border-atlantia-rose/35 bg-white px-3.5 text-sm font-black text-atlantia-wine transition hover:bg-atlantia-blush" data-close-create-modal>
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                        <path d="M6 6L18 18M18 6L6 18" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                    </svg>
                    Cerrar
                </button>
            </div>

            <form
                method="POST"
                action="{{ route('admin.usuarios.store') }}"
                class="mt-4 space-y-2.5"
                data-user-create-form
                data-disable-submit-guard
                novalidate
            >
                @csrf

                <div class="grid gap-x-5 gap-y-2.5 sm:grid-cols-2">
                    <div>
                        <label class="text-xs font-black text-atlantia-ink">Nombre completo</label>
                        <input name="name" type="text" value="{{ old('name') }}" class="mt-1 h-9 w-full rounded-md border border-atlantia-rose/35 bg-white px-3 text-sm outline-none transition focus:border-atlantia-wine focus:ring-2 focus:ring-atlantia-rose/25" required data-validate-name>
                        <p class="mt-0.5 text-[11px] text-atlantia-ink/55">Minimo 3 caracteres.</p>
                    </div>
                    <div>
                        <label class="text-xs font-black text-atlantia-ink">Correo electronico</label>
                        <input name="email" type="email" value="{{ old('email') }}" class="mt-1 h-9 w-full rounded-md border border-atlantia-rose/35 bg-white px-3 text-sm outline-none transition focus:border-atlantia-wine focus:ring-2 focus:ring-atlantia-rose/25" required data-validate-email>
                        <p class="mt-0.5 text-[11px] text-atlantia-ink/55">Formato usuario@dominio.com.</p>
                    </div>
                    <div>
                        <label class="text-xs font-black text-atlantia-ink">Telefono <span class="font-medium text-atlantia-ink/50">(opcional)</span></label>
                        <input name="phone" type="text" value="{{ old('phone') }}" placeholder="0000-0000" maxlength="9" class="mt-1 h-9 w-full rounded-md border border-atlantia-rose/35 bg-white px-3 text-sm outline-none transition focus:border-atlantia-wine focus:ring-2 focus:ring-atlantia-rose/25" data-phone-mask>
                    </div>
                    <div>
                        <label class="text-xs font-black text-atlantia-ink">Departamento/Equipo</label>
                        <select name="department" class="mt-1 h-9 w-full rounded-md border border-atlantia-rose/35 bg-white px-3 text-sm outline-none transition focus:border-atlantia-wine focus:ring-2 focus:ring-atlantia-rose/25">
                            @foreach ($departments as $value => $label)
                                <option value="{{ $value }}" @selected(old('department') === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="text-xs font-black text-atlantia-ink">Rol</label>
                        <select name="role" class="mt-1 h-9 w-full rounded-md border border-atlantia-rose/35 bg-white px-3 text-sm outline-none transition focus:border-atlantia-wine focus:ring-2 focus:ring-atlantia-rose/25" required>
                            @foreach ($availableRoles as $role)
                                <option value="{{ $role->name }}" @selected(old('role') === $role->name)>{{ $roleLabels[$role->name] ?? $role->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="text-xs font-black text-atlantia-ink">Estado</label>
                        <select name="status" class="mt-1 h-9 w-full rounded-md border border-atlantia-rose/35 bg-white px-3 text-sm outline-none transition focus:border-atlantia-wine focus:ring-2 focus:ring-atlantia-rose/25" required>
                            <option value="active" @selected(old('status', 'active') === 'active')>Activo</option>
                            <option value="inactive" @selected(old('status') === 'inactive')>Inactivo</option>
                            <option value="suspended" @selected(old('status') === 'suspended')>Suspendido</option>
                        </select>
                    </div>
                </div>

                <div>
                    <label class="text-xs font-black text-atlantia-ink">Contrasena</label>
                    <div class="relative mt-1">
                        <input name="password" type="password" class="h-9 w-full rounded-md border border-atlantia-rose/35 bg-white px-3 pr-10 text-sm outline-none transition focus:border-atlantia-wine focus:ring-2 focus:ring-atlantia-rose/25" required data-validate-password>
                        <button type="button" class="absolute right-2 top-1/2 grid h-7 w-7 -translate-y-1/2 place-items-center rounded-md text-atlantia-ink/55 hover:bg-atlantia-blush hover:text-atlantia-wine" data-toggle-password="password" aria-label="Mostrar contrasena">
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                <path d="M2.5 12S6 5 12 5S21.5 12 21.5 12S18 19 12 19S2.5 12 2.5 12Z" stroke="currentColor" stroke-width="1.7"/>
                                <path d="M12 15.2A3.2 3.2 0 1 0 12 8.8A3.2 3.2 0 0 0 12 15.2Z" stroke="currentColor" stroke-width="1.7"/>
                            </svg>
                        </button>
                    </div>
                    <div class="mt-2 h-1.5 overflow-hidden rounded-full bg-atlantia-rose/15">
                        <div class="h-full w-0 rounded-full bg-rose-500 transition-all" data-password-meter></div>
                    </div>
                    <div class="mt-2 grid gap-2 text-xs sm:grid-cols-4">
                        <span data-password-rule="length" class="inline-flex min-h-7 items-center justify-center gap-2 rounded-md bg-atlantia-cream px-2 font-semibold text-atlantia-wine/70">
                            <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M7 11V8A5 5 0 0 1 17 8V11M6 11H18V21H6V11Z" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round"/></svg>
                            8 caracteres
                        </span>
                        <span data-password-rule="upper" class="inline-flex min-h-7 items-center justify-center gap-2 rounded-md bg-atlantia-cream px-2 font-semibold text-atlantia-wine/70">Aa Mayuscula</span>
                        <span data-password-rule="lower" class="inline-flex min-h-7 items-center justify-center gap-2 rounded-md bg-atlantia-cream px-2 font-semibold text-atlantia-wine/70">aa Minuscula</span>
                        <span data-password-rule="symbol" class="inline-flex min-h-7 items-center justify-center gap-2 rounded-md bg-atlantia-cream px-2 font-semibold text-atlantia-wine/70"># Simbolo</span>
                    </div>
                </div>

                <div>
                    <label class="text-xs font-black text-atlantia-ink">Confirmar contrasena</label>
                    <div class="relative mt-1">
                        <input name="password_confirmation" type="password" class="h-9 w-full rounded-md border border-atlantia-rose/35 bg-white px-3 pr-10 text-sm outline-none transition focus:border-atlantia-wine focus:ring-2 focus:ring-atlantia-rose/25" required>
                        <button type="button" class="absolute right-2 top-1/2 grid h-7 w-7 -translate-y-1/2 place-items-center rounded-md text-atlantia-ink/55 hover:bg-atlantia-blush hover:text-atlantia-wine" data-toggle-password="password_confirmation" aria-label="Mostrar confirmacion de contrasena">
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                <path d="M2.5 12S6 5 12 5S21.5 12 21.5 12S18 19 12 19S2.5 12 2.5 12Z" stroke="currentColor" stroke-width="1.7"/>
                                <path d="M12 15.2A3.2 3.2 0 1 0 12 8.8A3.2 3.2 0 0 0 12 15.2Z" stroke="currentColor" stroke-width="1.7"/>
                            </svg>
                        </button>
                    </div>
                </div>

                <div class="flex justify-end gap-3 border-t border-atlantia-rose/20 pt-3">
                    <button type="button" class="inline-flex min-h-9 min-w-28 items-center justify-center rounded-md border border-atlantia-rose/35 bg-white px-4 text-sm font-black text-atlantia-wine transition hover:bg-atlantia-blush" data-close-create-modal>
                        Cancelar
                    </button>
                    <x-ui.button type="submit" class="min-h-9 min-w-40" data-create-submit>Crear usuario</x-ui.button>
                </div>
            </form>
        </section>
    </div>

    <div class="users-light-surface fixed inset-x-4 bottom-4 z-40 hidden rounded-xl border border-atlantia-rose/20 bg-white p-4 shadow-2xl sm:left-1/2 sm:right-auto sm:w-[min(720px,calc(100%-2rem))] sm:-translate-x-1/2" data-bulk-bar>
        <form method="POST" action="{{ route('admin.usuarios.batch') }}" class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between" data-bulk-form data-disable-submit-guard>
            @csrf
            <div>
                <p class="text-sm font-black text-atlantia-ink"><span data-selected-count>0</span> seleccionados</p>
                <p class="text-xs text-atlantia-ink/55">Aplica una accion masiva con confirmacion para eliminar.</p>
            </div>
            <div class="flex flex-wrap gap-2">
                <select name="role" class="rounded-md border border-atlantia-rose/35 px-3 py-2 text-sm">
                    @foreach ($availableRoles as $role)
                        <option value="{{ $role->name }}">{{ $roleLabels[$role->name] ?? $role->name }}</option>
                    @endforeach
                </select>
                <button type="submit" name="action" value="role" class="rounded-md border border-atlantia-rose/35 px-3 py-2 text-sm font-black text-atlantia-wine hover:bg-atlantia-blush">Cambiar rol</button>
                <button type="submit" name="action" value="activate" class="rounded-md bg-emerald-600 px-3 py-2 text-sm font-black text-white hover:bg-emerald-700">Activar</button>
                <button type="submit" name="action" value="deactivate" class="rounded-md bg-slate-600 px-3 py-2 text-sm font-black text-white hover:bg-slate-700">Desactivar</button>
                <button type="submit" name="action" value="delete" class="rounded-md bg-rose-700 px-3 py-2 text-sm font-black text-white hover:bg-rose-800" data-confirm-delete>Eliminar</button>
            </div>
        </form>
    </div>

    <div class="users-light-surface fixed inset-0 z-50 hidden items-center justify-center overflow-hidden bg-slate-950/55 px-3 py-2 backdrop-blur-sm" data-user-drawer>
        <aside class="max-h-[calc(100vh-0.75rem)] w-full max-w-4xl overflow-y-auto rounded-xl border border-atlantia-rose/15 bg-white p-3 shadow-[0_30px_90px_rgba(49,18,32,0.22)] sm:p-4">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <p class="text-xs font-black uppercase tracking-wide text-atlantia-rose">Detalle de usuario</p>
                    <h2 class="text-2xl font-black leading-tight text-atlantia-ink" data-drawer-name>Usuario</h2>
                    <p class="text-sm text-atlantia-ink/55" data-drawer-email></p>
                </div>
                <button type="button" class="inline-flex min-h-9 items-center justify-center gap-2 rounded-md border border-atlantia-rose/35 px-4 text-sm font-black text-atlantia-wine transition hover:bg-atlantia-blush" data-close-drawer>
                    Cerrar
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                        <path d="M6 6L18 18M18 6L6 18" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                    </svg>
                </button>
            </div>

            <div class="mt-2 grid gap-2 sm:grid-cols-2 lg:grid-cols-4">
                <div class="flex items-center gap-2 rounded-lg border border-atlantia-rose/20 bg-atlantia-cream/55 p-2">
                    <span class="grid h-8 w-8 shrink-0 place-items-center rounded-full border border-atlantia-rose/20 bg-white text-atlantia-wine">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                            <path d="M12 12C14.2 12 16 10.2 16 8C16 5.8 14.2 4 12 4C9.8 4 8 5.8 8 8C8 10.2 9.8 12 12 12ZM5 20C5.8 16.9 8.4 15 12 15C15.6 15 18.2 16.9 19 20" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                        </svg>
                    </span>
                    <div>
                        <p class="text-[11px] text-atlantia-ink/65">Estado</p>
                        <p class="text-base font-black text-atlantia-wine" data-drawer-status></p>
                    </div>
                    <p class="mt-1 text-xs text-atlantia-ink/55" data-drawer-inactive></p>
                </div>
                <div class="flex items-center gap-2 rounded-lg border border-atlantia-rose/20 bg-atlantia-cream/55 p-2">
                    <span class="grid h-8 w-8 shrink-0 place-items-center rounded-full border border-atlantia-rose/20 bg-white text-atlantia-wine">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                            <path d="M12 7V12L15 15M21 12C21 17 17 21 12 21C7 21 3 17 3 12C3 7 7 3 12 3C17 3 21 7 21 12Z" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                        </svg>
                    </span>
                    <div>
                        <p class="text-[11px] text-atlantia-ink/65">Ultima actividad</p>
                        <p class="text-base font-black text-atlantia-wine" data-drawer-activity></p>
                    </div>
                </div>
                <div class="flex items-center gap-2 rounded-lg border border-atlantia-rose/20 bg-atlantia-cream/55 p-2">
                    <span class="grid h-8 w-8 shrink-0 place-items-center rounded-full border border-atlantia-rose/20 bg-white text-atlantia-wine">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                            <path d="M7 4V7M17 4V7M4.5 10H19.5M6.5 6H17.5C18.6 6 19.5 6.9 19.5 8V18C19.5 19.1 18.6 20 17.5 20H6.5C5.4 20 4.5 19.1 4.5 18V8C4.5 6.9 5.4 6 6.5 6Z" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                        </svg>
                    </span>
                    <div>
                        <p class="text-[11px] text-atlantia-ink/65">Creado por</p>
                        <p class="text-sm font-black leading-tight text-atlantia-ink" data-drawer-created></p>
                        <p class="text-[11px] text-atlantia-ink/60" data-drawer-created-at></p>
                    </div>
                </div>
                <div class="flex items-center gap-2 rounded-lg border border-atlantia-rose/20 bg-atlantia-cream/55 p-2">
                    <span class="grid h-8 w-8 shrink-0 place-items-center rounded-full border border-atlantia-rose/20 bg-white text-atlantia-wine">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                            <path d="M4 20H8L18.5 9.5C19.6 8.4 19.6 6.6 18.5 5.5C17.4 4.4 15.6 4.4 14.5 5.5L4 16V20Z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/>
                        </svg>
                    </span>
                    <div>
                        <p class="text-[11px] text-atlantia-ink/65">Modificado por</p>
                        <p class="text-sm font-black leading-tight text-atlantia-ink" data-drawer-updated></p>
                        <p class="text-[11px] text-atlantia-ink/60" data-drawer-updated-at></p>
                    </div>
                </div>
            </div>

            <form method="POST" class="mt-2 space-y-2 rounded-lg border border-atlantia-rose/20 bg-white p-3 shadow-sm" data-edit-form data-disable-submit-guard>
                @csrf
                @method('PUT')
                <div class="flex items-center gap-3">
                    <span class="grid h-8 w-8 place-items-center rounded-full bg-atlantia-wine text-white">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                            <path d="M12 12C14.2 12 16 10.2 16 8C16 5.8 14.2 4 12 4C9.8 4 8 5.8 8 8C8 10.2 9.8 12 12 12ZM5 20C5.8 16.9 8.4 15 12 15C15.6 15 18.2 16.9 19 20" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                        </svg>
                    </span>
                    <h3 class="text-base font-black text-atlantia-ink">Editar usuario</h3>
                </div>
                <div class="grid gap-2 sm:grid-cols-2">
                    <div>
                        <label class="text-xs font-bold text-atlantia-ink">Nombre</label>
                        <input name="name" class="mt-1 min-h-9 w-full rounded-md border border-atlantia-rose/35 px-3 text-sm" required>
                    </div>
                    <div>
                        <label class="text-xs font-bold text-atlantia-ink">Correo</label>
                        <input name="email" type="email" class="mt-1 min-h-9 w-full rounded-md border border-atlantia-rose/35 px-3 text-sm" required data-edit-email>
                    </div>
                    <div>
                        <label class="text-xs font-bold text-atlantia-ink">Telefono</label>
                        <input name="phone" class="mt-1 min-h-9 w-full rounded-md border border-atlantia-rose/35 px-3 text-sm" data-phone-mask>
                    </div>
                    <div>
                        <label class="text-xs font-bold text-atlantia-ink">Estado</label>
                        <select name="status" class="mt-1 min-h-9 w-full rounded-md border border-atlantia-rose/35 px-3 text-sm">
                            <option value="active">Activo</option>
                            <option value="inactive">Inactivo</option>
                            <option value="suspended">Suspendido</option>
                        </select>
                    </div>
                </div>
                <div data-edit-roles-section>
                    <label class="text-xs font-black text-atlantia-ink">Roles</label>
                    <div class="mt-1 grid gap-1.5 sm:grid-cols-3 lg:grid-cols-4" data-edit-role-fields>
                        @foreach ($availableRoles as $role)
                            <label class="flex min-h-8 items-center gap-2 rounded-md border border-atlantia-rose/20 px-2 py-1 text-xs font-semibold">
                                <input type="checkbox" name="roles[]" value="{{ $role->name }}" class="rounded border-atlantia-rose text-atlantia-wine focus:ring-atlantia-rose">
                                <span>{{ $roleLabels[$role->name] ?? $role->name }}</span>
                            </label>
                        @endforeach
                    </div>
                </div>
                <div data-edit-hidden-roles></div>
                <div class="grid gap-2 sm:grid-cols-2" data-edit-password-fields>
                    <div>
                        <label class="text-xs font-bold text-atlantia-ink">Nueva contrasena</label>
                        <input name="password" type="password" class="mt-1 min-h-9 w-full rounded-md border border-atlantia-rose/35 px-3 text-sm">
                    </div>
                    <div>
                        <label class="text-xs font-bold text-atlantia-ink">Confirmar contrasena</label>
                        <input name="password_confirmation" type="password" class="mt-1 min-h-9 w-full rounded-md border border-atlantia-rose/35 px-3 text-sm">
                    </div>
                </div>
                <div class="hidden items-center gap-2 rounded-md border border-amber-300 bg-amber-50 px-3 py-2 text-sm font-semibold text-amber-900" data-edit-password-self-note>
                    <span class="grid h-6 w-6 shrink-0 place-items-center rounded-full text-amber-700">
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                            <path d="M12 3L19 6V11.5C19 16 16.2 19.5 12 21C7.8 19.5 5 16 5 11.5V6L12 3Z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/>
                            <path d="M9 12L11 14L15 10" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </span>
                    <span>Por seguridad, no puedes cambiar tu correo, roles ni contrasena desde tu propia cuenta administrativa.</span>
                </div>
                <div class="grid gap-3 border-t border-atlantia-rose/15 pt-2 sm:grid-cols-[1fr_1fr_1.2fr]">
                    <button type="button" class="inline-flex min-h-9 items-center justify-center rounded-md border border-atlantia-rose/30 px-4 text-sm font-black text-atlantia-wine hover:bg-atlantia-blush" data-close-drawer>
                        Cancelar
                    </button>
                    <x-ui.button type="submit" class="min-h-9 py-1.5">Guardar sin salir</x-ui.button>
                    <x-ui.button type="submit" class="min-h-9 py-1.5">Guardar cambios</x-ui.button>
                </div>
            </form>

            <div class="mt-2 rounded-lg border border-atlantia-rose/20 bg-white p-2 shadow-sm">
                <div class="flex items-center gap-3">
                    <span class="grid h-7 w-7 place-items-center rounded-full bg-atlantia-wine text-white">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                            <path d="M8 7H16M8 12H16M8 17H13M6 3H18C19.1 3 20 3.9 20 5V19C20 20.1 19.1 21 18 21H6C4.9 21 4 20.1 4 19V5C4 3.9 4.9 3 6 3Z" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                        </svg>
                    </span>
                    <h3 class="text-base font-black text-atlantia-ink">Historial de cambios</h3>
                </div>
                <div class="mt-1.5 space-y-1 text-xs text-atlantia-ink/65" data-drawer-history></div>
            </div>
        </aside>
    </div>

    <div class="users-light-surface fixed inset-0 z-50 hidden items-center justify-center overflow-hidden bg-slate-950/55 px-3 py-3 backdrop-blur-sm" data-user-detail-modal>
        <section class="w-full max-w-3xl rounded-xl border border-atlantia-rose/15 bg-white p-3 shadow-[0_26px_80px_rgba(49,18,32,0.22)]">
            <div class="flex items-center justify-between gap-4">
                <h2 class="text-sm font-black text-atlantia-ink">Detalles del usuario</h2>
                <button type="button" class="grid h-8 w-8 place-items-center rounded-md border border-atlantia-rose/25 text-atlantia-ink/60 transition hover:bg-atlantia-blush hover:text-atlantia-wine" data-close-detail-modal aria-label="Cerrar detalles">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                        <path d="M6 6L18 18M18 6L6 18" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                    </svg>
                </button>
            </div>

            <div class="mt-3 flex items-center gap-3">
                <div class="grid h-12 w-12 shrink-0 place-items-center rounded-full bg-rose-100 text-base font-black text-atlantia-wine" data-detail-avatar>GP</div>
                <div class="min-w-0">
                    <h3 class="truncate text-lg font-black text-atlantia-ink" data-detail-name>Usuario</h3>
                    <p class="truncate text-xs text-atlantia-ink/55" data-detail-email></p>
                    <div class="mt-1 flex flex-wrap gap-2 text-xs font-black">
                        <span class="rounded-md bg-rose-50 px-2 py-1 text-atlantia-wine ring-1 ring-rose-200" data-detail-primary-role></span>
                        <span class="rounded-md bg-emerald-50 px-2 py-1 text-emerald-700 ring-1 ring-emerald-200" data-detail-status></span>
                    </div>
                </div>
            </div>

            <div class="mt-3 grid gap-3 lg:grid-cols-2">
                <article class="rounded-lg border border-atlantia-rose/15 p-3">
                    <div class="mb-2 flex items-center gap-2">
                        <span class="grid h-7 w-7 place-items-center rounded-full bg-rose-50 text-atlantia-wine">
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                <path d="M12 12C14.2 12 16 10.2 16 8C16 5.8 14.2 4 12 4C9.8 4 8 5.8 8 8C8 10.2 9.8 12 12 12ZM5 20C5.8 16.9 8.4 15 12 15C15.6 15 18.2 16.9 19 20" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                            </svg>
                        </span>
                        <h4 class="text-sm font-black text-atlantia-ink">Informacion general</h4>
                    </div>
                    <dl class="divide-y divide-atlantia-rose/12 text-xs">
                        <div class="grid grid-cols-[0.9fr_1.15fr] gap-3 py-1.5">
                            <dt class="text-atlantia-ink/55">Nombre completo</dt>
                            <dd class="font-semibold text-atlantia-ink" data-detail-info-name></dd>
                        </div>
                        <div class="grid grid-cols-[0.9fr_1.15fr] gap-3 py-1.5">
                            <dt class="text-atlantia-ink/55">Correo electronico</dt>
                            <dd class="break-all font-semibold text-atlantia-ink" data-detail-info-email></dd>
                        </div>
                        <div class="grid grid-cols-[0.9fr_1.15fr] gap-3 py-1.5">
                            <dt class="text-atlantia-ink/55">Telefono</dt>
                            <dd class="font-semibold text-atlantia-ink" data-detail-phone></dd>
                        </div>
                        <div class="grid grid-cols-[0.9fr_1.15fr] gap-3 py-1.5">
                            <dt class="text-atlantia-ink/55">Estado</dt>
                            <dd><span class="rounded bg-emerald-50 px-2 py-1 text-xs font-black text-emerald-700" data-detail-info-status></span></dd>
                        </div>
                        <div class="grid grid-cols-[0.9fr_1.15fr] gap-3 py-1.5">
                            <dt class="text-atlantia-ink/55">Rol principal</dt>
                            <dd><span class="rounded bg-rose-50 px-2 py-1 text-xs font-black text-atlantia-wine" data-detail-info-role></span></dd>
                        </div>
                    </dl>
                </article>

                <article class="rounded-lg border border-atlantia-rose/15 p-3">
                    <div class="mb-2 flex items-center gap-2">
                        <span class="grid h-7 w-7 place-items-center rounded-full bg-rose-50 text-atlantia-wine">
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                <path d="M12 3L19 6V11.5C19 16.2 16 19.8 12 21C8 19.8 5 16.2 5 11.5V6L12 3Z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/>
                            </svg>
                        </span>
                        <h4 class="text-sm font-black text-atlantia-ink">Roles y permisos</h4>
                    </div>
                    <p class="text-xs font-black text-atlantia-ink/60">Roles asignados (<span data-detail-role-count>0</span>)</p>
                    <div class="mt-1.5 space-y-1.5" data-detail-roles></div>
                    <p class="mt-2 text-xs font-black text-atlantia-ink/60">Permisos principales</p>
                    <div class="mt-1.5 flex flex-wrap gap-1.5 text-xs">
                        <span class="rounded-md border border-atlantia-rose/15 bg-atlantia-cream/60 px-2 py-1 font-bold text-atlantia-wine">Usuarios</span>
                        <span class="rounded-md border border-atlantia-rose/15 bg-atlantia-cream/60 px-2 py-1 font-bold text-atlantia-wine">Roles</span>
                        <span class="rounded-md border border-atlantia-rose/15 bg-atlantia-cream/60 px-2 py-1 font-bold text-atlantia-wine">Configuracion</span>
                        <span class="rounded-md border border-atlantia-rose/15 bg-atlantia-cream/60 px-2 py-1 font-bold text-atlantia-wine">Reportes</span>
                    </div>
                </article>

                <article class="rounded-lg border border-atlantia-rose/15 p-3">
                    <div class="mb-2 flex items-center gap-2">
                        <span class="grid h-7 w-7 place-items-center rounded-full bg-rose-50 text-atlantia-wine">
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                <path d="M12 7V12L15 15M21 12C21 17 17 21 12 21C7 21 3 17 3 12C3 7 7 3 12 3C17 3 21 7 21 12Z" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                            </svg>
                        </span>
                        <h4 class="text-sm font-black text-atlantia-ink">Actividad</h4>
                    </div>
                    <dl class="divide-y divide-atlantia-rose/12 text-xs">
                        <div class="grid grid-cols-[0.95fr_1.2fr] gap-3 py-1.5">
                            <dt class="text-atlantia-ink/55">Ultima actividad</dt>
                            <dd class="font-black text-atlantia-wine" data-detail-activity></dd>
                        </div>
                        <div class="grid grid-cols-[0.95fr_1.2fr] gap-3 py-1.5">
                            <dt class="text-atlantia-ink/55">Creado</dt>
                            <dd class="font-semibold text-atlantia-ink" data-detail-created></dd>
                        </div>
                        <div class="grid grid-cols-[0.95fr_1.2fr] gap-3 py-1.5">
                            <dt class="text-atlantia-ink/55">Ultima modificacion</dt>
                            <dd class="font-semibold text-atlantia-ink" data-detail-updated></dd>
                        </div>
                    </dl>
                </article>

                <article class="rounded-lg border border-atlantia-rose/15 p-3">
                    <div class="mb-2 flex items-center gap-2">
                        <span class="grid h-7 w-7 place-items-center rounded-full bg-rose-50 text-atlantia-wine">
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                <path d="M8 7H16M8 12H16M8 17H13M6 3H18C19.1 3 20 3.9 20 5V19C20 20.1 19.1 21 18 21H6C4.9 21 4 20.1 4 19V5C4 3.9 4.9 3 6 3Z" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                            </svg>
                        </span>
                        <h4 class="text-sm font-black text-atlantia-ink">Historial de cambios</h4>
                    </div>
                    <div class="space-y-1.5 text-xs" data-detail-history></div>
                </article>
            </div>

            <div class="mt-3 flex items-center justify-between gap-3">
                <button type="button" class="inline-flex min-h-9 items-center justify-center rounded-md border border-atlantia-rose/30 px-5 text-sm font-black text-atlantia-wine hover:bg-atlantia-blush" data-close-detail-modal>Cerrar</button>
                <button type="button" class="inline-flex min-h-9 items-center justify-center gap-2 rounded-md bg-atlantia-wine px-5 text-sm font-black text-white hover:bg-atlantia-wine-700" data-detail-edit-user>
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                        <path d="M4 20H8L18.5 9.5C19.6 8.4 19.6 6.6 18.5 5.5C17.4 4.4 15.6 4.4 14.5 5.5L4 16V20Z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/>
                    </svg>
                    Editar usuario
                </button>
            </div>
        </section>
    </div>

    <script nonce="{{ request()->attributes->get('csp_nonce') }}">
        window.atlantiaUsers = @json($usersPayload);
        window.atlantiaRoleLabels = @json($roleLabels);
        window.atlantiaStatusLabels = @json($statusLabels);
        window.atlantiaStatusClasses = @json($statusClasses);
        window.atlantiaCurrentUserId = @json(auth()->id());
    </script>
@endsection

@push('scripts')
    <script nonce="{{ request()->attributes->get('csp_nonce') }}">
        (() => {
            const csrf = document.querySelector('meta[name="csrf-token"]')?.content;
            const users = window.atlantiaUsers || {};
            const roleLabels = window.atlantiaRoleLabels || {};
            const statusLabels = window.atlantiaStatusLabels || {};
            const statusClasses = window.atlantiaStatusClasses || {};
            const currentUserId = Number(window.atlantiaCurrentUserId || 0);
            const toast = (type, message) => window.dispatchEvent(new CustomEvent('toast', { detail: { type, message } }));

            const formatPhone = (value) => value.replace(/\D/g, '').slice(0, 8).replace(/(\d{4})(\d+)/, '$1-$2');
            document.querySelectorAll('[data-phone-mask]').forEach((input) => {
                input.addEventListener('input', () => {
                    input.value = formatPhone(input.value);
                });
            });

            document.querySelectorAll('[data-toggle-password]').forEach((button) => {
                button.addEventListener('click', () => {
                    const input = button.closest('div')?.querySelector(`[name="${button.dataset.togglePassword}"]`);
                    if (!input) return;

                    input.type = input.type === 'password' ? 'text' : 'password';
                    button.setAttribute('aria-label', input.type === 'password' ? 'Mostrar contrasena' : 'Ocultar contrasena');
                });
            });

            const createModal = document.querySelector('[data-create-modal]');
            const openCreateModal = () => {
                createModal?.classList.remove('hidden');
                createModal?.classList.add('flex');
                createModal?.querySelector('[name="name"]')?.focus();
            };
            const closeCreateModal = () => {
                createModal?.classList.add('hidden');
                createModal?.classList.remove('flex');
            };
            document.querySelector('[data-open-create-modal]')?.addEventListener('click', openCreateModal);
            document.querySelectorAll('[data-close-create-modal]').forEach((button) => {
                button.addEventListener('click', closeCreateModal);
            });
            createModal?.addEventListener('click', (event) => {
                if (event.target === createModal) closeCreateModal();
            });

            const form = document.querySelector('[data-user-create-form]');
            if (form) {
                const nameInput = form.querySelector('[data-validate-name]');
                const emailInput = form.querySelector('[data-validate-email]');
                const passwordInput = form.querySelector('[data-validate-password]');
                const avatar = document.querySelector('[data-create-modal] [data-name-avatar]');
                const meter = form.querySelector('[data-password-meter]');
                const submit = form.querySelector('[data-create-submit]');
                const submitIdleHtml = submit.innerHTML;
                let creating = false;
                const rules = {
                    length: (value) => value.length >= 8,
                    upper: (value) => /[A-Z]/.test(value),
                    lower: (value) => /[a-z]/.test(value),
                    symbol: (value) => /[^A-Za-z0-9]/.test(value),
                };

                const mark = (element, valid) => {
                    element.classList.toggle('border-emerald-400', valid);
                    element.classList.toggle('border-rose-400', !valid && element.value.length > 0);
                };

                const validate = () => {
                    const nameOk = nameInput.value.trim().length >= 3;
                    const emailOk = /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(emailInput.value.trim());
                    const passwordScore = Object.entries(rules).filter(([, test]) => test(passwordInput.value)).length;

                    mark(nameInput, nameOk);
                    mark(emailInput, emailOk);
                    mark(passwordInput, passwordScore === 4);

                    if (avatar) {
                        avatar.textContent = (nameInput.value.trim()[0] || 'A').toUpperCase();
                    }
                    meter.style.width = `${passwordScore * 25}%`;
                    meter.className = `h-full rounded-full transition-all ${passwordScore < 2 ? 'bg-rose-500' : passwordScore < 4 ? 'bg-amber-500' : 'bg-emerald-500'}`;

                    Object.entries(rules).forEach(([key, test]) => {
                        const rule = form.querySelector(`[data-password-rule="${key}"]`);
                        const valid = test(passwordInput.value);
                        rule.classList.toggle('text-emerald-700', valid);
                        rule.classList.toggle('bg-emerald-50', valid);
                        rule.classList.toggle('ring-1', valid);
                        rule.classList.toggle('ring-emerald-200', valid);
                    });

                    submit.disabled = !(nameOk && emailOk && passwordScore === 4);
                    submit.classList.toggle('opacity-60', submit.disabled);
                };

                ['input', 'change'].forEach((eventName) => form.addEventListener(eventName, validate));
                validate();

                form.addEventListener('submit', async (event) => {
                    event.preventDefault();
                    if (creating) return;

                    validate();
                    if (submit.disabled) {
                        toast('warning', 'Completa los requisitos antes de crear el usuario.');
                        return;
                    }

                    creating = true;
                    submit.disabled = true;
                    submit.setAttribute('aria-busy', 'true');
                    submit.innerHTML = `
                        <span class="mr-2 inline-block h-4 w-4 animate-spin rounded-full border-2 border-current border-r-transparent align-[-0.125em]"></span>
                        Creando...
                    `;

                    try {
                        const response = await fetch(form.action, {
                            method: 'POST',
                            credentials: 'same-origin',
                            headers: {
                                'Accept': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest',
                                'X-CSRF-TOKEN': csrf,
                            },
                            body: new FormData(form),
                        });
                        const contentType = response.headers.get('content-type') || '';
                        const isJson = contentType.includes('application/json');
                        const payload = isJson ? await response.json() : {};

                        if (!isJson) {
                            throw new Error('El servidor no devolvio una respuesta valida. Recarga la pagina e intenta otra vez.');
                        }

                        if (!response.ok) {
                            throw new Error(Object.values(payload.errors || {}).flat()[0] || payload.message || 'No fue posible crear el usuario.');
                        }

                        form.reset();
                        validate();
                        toast('success', payload.message || 'Usuario creado correctamente.');
                        closeCreateModal();
                        setTimeout(() => window.location.reload(), 350);
                    } catch (error) {
                        toast('error', error.message);
                    } finally {
                        creating = false;
                        submit.removeAttribute('aria-busy');
                        submit.innerHTML = submitIdleHtml;
                        validate();
                    }
                });
            }

            const drawer = document.querySelector('[data-user-drawer]');
            const editForm = document.querySelector('[data-edit-form]');
            const detailModal = document.querySelector('[data-user-detail-modal]');
            let activeDetailUserId = null;

            const closeDrawer = () => {
                drawer?.classList.add('hidden');
                drawer?.classList.remove('flex');
                document.getElementById('breadcrumb-user').textContent = 'Listado';
            };

            const closeDetailModal = () => {
                detailModal?.classList.add('hidden');
                detailModal?.classList.remove('flex');
            };

            const userInitials = (name) => (name || 'U')
                .trim()
                .split(/\s+/)
                .slice(0, 2)
                .map((part) => part[0] || '')
                .join('')
                .toUpperCase();

            const openDetail = (id) => {
                const user = users[id];
                if (!user || !detailModal) return;

                activeDetailUserId = id;
                detailModal.classList.remove('hidden');
                detailModal.classList.add('flex');
                detailModal.querySelector('[data-detail-avatar]').textContent = userInitials(user.name);
                detailModal.querySelector('[data-detail-name]').textContent = user.name;
                detailModal.querySelector('[data-detail-email]').textContent = user.email;
                detailModal.querySelector('[data-detail-primary-role]').textContent = user.roles_label || 'Sin rol';
                detailModal.querySelector('[data-detail-status]').textContent = user.status_label;
                detailModal.querySelector('[data-detail-info-name]').textContent = user.name;
                detailModal.querySelector('[data-detail-info-email]').textContent = user.email;
                detailModal.querySelector('[data-detail-phone]').textContent = user.phone || 'Sin telefono';
                detailModal.querySelector('[data-detail-info-status]').textContent = user.status_label;
                detailModal.querySelector('[data-detail-info-role]').textContent = user.roles_label || 'Sin rol';
                detailModal.querySelector('[data-detail-activity]').textContent = user.last_activity;
                detailModal.querySelector('[data-detail-created]').textContent = `${user.created_relative || ''}\n${user.created_at || ''}`.trim();
                detailModal.querySelector('[data-detail-updated]').textContent = `${user.updated_relative || ''}\n${user.updated_at || ''}`.trim();
                detailModal.querySelector('[data-detail-role-count]').textContent = user.roles.length;
                detailModal.querySelector('[data-detail-roles]').innerHTML = user.roles.map((role) => `
                    <div class="flex items-center justify-between rounded-md border border-atlantia-rose/15 bg-atlantia-cream/55 px-2 py-1.5">
                        <div class="flex items-center gap-2">
                            <span class="grid h-7 w-7 place-items-center rounded-md bg-rose-50 text-xs font-black text-atlantia-wine">${(roleLabels[role] || role).slice(0, 2).toUpperCase()}</span>
                            <div>
                                <p class="text-xs font-black text-atlantia-ink">${roleLabels[role] || role}</p>
                                <p class="text-[11px] text-atlantia-ink/55">Acceso operativo</p>
                            </div>
                        </div>
                        <span class="text-atlantia-ink/35">›</span>
                    </div>
                `).join('') || '<p class="rounded-md bg-atlantia-cream/60 px-2 py-1.5 text-xs text-atlantia-ink/60">Sin roles asignados</p>';
                detailModal.querySelector('[data-detail-history]').innerHTML = `
                    <div class="grid grid-cols-[78px_1fr] gap-2 rounded-md bg-atlantia-cream/55 px-2 py-1.5"><span class="text-atlantia-ink/45">${user.created_at || '--'}</span><span><strong>Usuario creado</strong><br><span class="text-atlantia-ink/55">${user.created_by}</span></span></div>
                    <div class="grid grid-cols-[78px_1fr] gap-2 rounded-md bg-atlantia-cream/55 px-2 py-1.5"><span class="text-atlantia-ink/45">${user.updated_at || '--'}</span><span><strong>Usuario actualizado</strong><br><span class="text-atlantia-ink/55">${user.updated_by}</span></span></div>
                    <div class="grid grid-cols-[78px_1fr] gap-2 rounded-md bg-atlantia-cream/55 px-2 py-1.5"><span class="text-atlantia-ink/45">--</span><span><strong>Cuenta activa</strong><br><span class="text-atlantia-ink/55">Administracion Atlantia</span></span></div>
                `;
            };

            const openDrawer = (id) => {
                const user = users[id];
                if (!user || !drawer || !editForm) return;

                document.getElementById('breadcrumb-user').textContent = user.name;
                drawer.classList.remove('hidden');
                drawer.classList.add('flex');
                drawer.querySelector('[data-drawer-name]').textContent = user.name;
                drawer.querySelector('[data-drawer-email]').textContent = user.email;
                drawer.querySelector('[data-drawer-status]').textContent = user.status_label;
                drawer.querySelector('[data-drawer-activity]').textContent = user.last_activity;
                drawer.querySelector('[data-drawer-created]').textContent = user.created_by;
                drawer.querySelector('[data-drawer-created-at]').textContent = `${user.created_at} (${user.created_relative})`;
                drawer.querySelector('[data-drawer-updated]').textContent = user.updated_by;
                drawer.querySelector('[data-drawer-updated-at]').textContent = `${user.updated_at} (${user.updated_relative})`;
                drawer.querySelector('[data-drawer-inactive]').textContent = user.inactive_reason || '';
                drawer.querySelector('[data-drawer-history]').innerHTML = `
                    <div class="flex items-center gap-4 rounded-md bg-atlantia-cream/60 px-4 py-2"><span class="h-2 w-2 shrink-0 rounded-full bg-atlantia-wine"></span><span>Cuenta creada por ${user.created_by} el ${user.created_at}.</span></div>
                    <div class="flex items-center gap-4 rounded-md bg-atlantia-cream/60 px-4 py-2"><span class="h-2 w-2 shrink-0 rounded-full bg-atlantia-wine"></span><span>Ultima modificacion por ${user.updated_by} el ${user.updated_at}.</span></div>
                    <div class="flex items-center gap-4 rounded-md bg-atlantia-cream/60 px-4 py-2"><span class="h-2 w-2 shrink-0 rounded-full bg-atlantia-wine"></span><span>Roles actuales: ${user.roles_label}.</span></div>
                `;

                editForm.action = user.update_url;
                editForm.querySelector('[name="name"]').value = user.name;
                const emailInput = editForm.querySelector('[name="email"]');
                emailInput.value = user.email;
                editForm.querySelector('[name="phone"]').value = user.phone || '';
                editForm.querySelector('[name="status"]').value = user.status;
                editForm.querySelector('[name="password"]').value = '';
                editForm.querySelector('[name="password_confirmation"]').value = '';

                const editingSelf = Number(user.id) === currentUserId;
                const passwordFields = editForm.querySelector('[data-edit-password-fields]');
                const passwordSelfNote = editForm.querySelector('[data-edit-password-self-note]');
                const passwordInput = editForm.querySelector('[name="password"]');
                const passwordConfirmationInput = editForm.querySelector('[name="password_confirmation"]');
                const rolesSection = editForm.querySelector('[data-edit-roles-section]');
                const hiddenRoles = editForm.querySelector('[data-edit-hidden-roles]');

                emailInput.readOnly = editingSelf;
                emailInput.classList.toggle('bg-atlantia-cream/70', editingSelf);
                emailInput.classList.toggle('text-atlantia-ink/65', editingSelf);

                hiddenRoles.innerHTML = '';
                editForm.querySelectorAll('[name="roles[]"]').forEach((checkbox) => {
                    checkbox.checked = user.roles.includes(checkbox.value);
                    checkbox.disabled = editingSelf;
                    checkbox.closest('label')?.classList.toggle('bg-atlantia-cream/70', editingSelf);
                    checkbox.closest('label')?.classList.toggle('text-atlantia-ink/60', editingSelf);
                });

                if (editingSelf) {
                    user.roles.forEach((role) => {
                        const input = document.createElement('input');
                        input.type = 'hidden';
                        input.name = 'roles[]';
                        input.value = role;
                        hiddenRoles.appendChild(input);
                    });
                }

                rolesSection?.classList.toggle('hidden', editingSelf);
                passwordFields?.classList.toggle('hidden', editingSelf);
                passwordSelfNote?.classList.toggle('hidden', !editingSelf);
                passwordSelfNote?.classList.toggle('flex', editingSelf);
                if (passwordInput) passwordInput.disabled = editingSelf;
                if (passwordConfirmationInput) passwordConfirmationInput.disabled = editingSelf;
            };

            document.addEventListener('click', (event) => {
                const actionButton = event.target.closest('[data-view-user], [data-edit-user]');

                if (actionButton) {
                    const id = actionButton.dataset.viewUser || actionButton.dataset.editUser;

                    if (users[id]) {
                        event.preventDefault();
                        event.stopPropagation();
                        if (actionButton.dataset.editUser) {
                            openDrawer(id);
                        } else {
                            openDetail(id);
                        }
                        return;
                    }

                    if (actionButton.dataset.fallbackUrl) {
                        window.location.href = actionButton.dataset.fallbackUrl;
                    }
                    return;
                }

                const row = event.target.closest('[data-user-row]');

                if (!row || event.target.closest('[data-no-row-open], button, a, input, form')) {
                    return;
                }

                openDrawer(row.dataset.userId);
            });
            document.querySelectorAll('[data-close-drawer]').forEach((button) => {
                button.addEventListener('click', closeDrawer);
            });
            drawer?.addEventListener('click', (event) => {
                if (event.target === drawer) closeDrawer();
            });
            document.querySelectorAll('[data-close-detail-modal]').forEach((button) => {
                button.addEventListener('click', closeDetailModal);
            });
            detailModal?.addEventListener('click', (event) => {
                if (event.target === detailModal) closeDetailModal();
            });
            document.querySelector('[data-detail-edit-user]')?.addEventListener('click', () => {
                if (!activeDetailUserId) return;
                closeDetailModal();
                openDrawer(activeDetailUserId);
            });

            editForm?.addEventListener('submit', async (event) => {
                event.preventDefault();
                try {
                    const response = await fetch(editForm.action, {
                        method: 'POST',
                        credentials: 'same-origin',
                        headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': csrf },
                        body: new FormData(editForm),
                    });
                    const editContentType = response.headers.get('content-type') || '';
                    const editIsJson = editContentType.includes('application/json');
                    const payload = editIsJson ? await response.json() : {};
                    if (!editIsJson) {
                        throw new Error('El servidor no devolvio una respuesta valida. Recarga la pagina e intenta otra vez.');
                    }
                    if (!response.ok) {
                        throw new Error(Object.values(payload.errors || {}).flat()[0] || payload.message || 'No fue posible actualizar el usuario.');
                    }
                    if (payload.user) {
                        const user = payload.user;
                        users[user.id] = { ...users[user.id], ...user, roles_label: (user.roles || []).map((role) => roleLabels[role] || role).join(', '), status_label: statusLabels[user.status] || user.status };
                        const row = document.querySelector(`[data-user-row][data-user-id="${user.id}"]`);
                        row?.querySelector('[data-row-name]') && (row.querySelector('[data-row-name]').textContent = user.name);
                        row?.querySelector('[data-row-email]') && (row.querySelector('[data-row-email]').textContent = user.email);
                        row?.querySelector('[data-row-roles]') && (row.querySelector('[data-row-roles]').textContent = users[user.id].roles_label || 'Sin rol');
                        const status = row?.querySelector('[data-row-status]');
                        if (status) {
                            status.textContent = users[user.id].status_label;
                            status.className = `${statusClasses[user.status] || statusClasses.inactive} rounded-md px-3 py-1 text-xs font-black`;
                        }
                        openDrawer(user.id);
                    }
                    toast('success', payload.message || 'Usuario actualizado correctamente.');
                } catch (error) {
                    toast('error', error.message);
                }
            });

            const checks = [...document.querySelectorAll('[data-row-check]')];
            const bulkBar = document.querySelector('[data-bulk-bar]');
            const selectedCount = document.querySelector('[data-selected-count]');
            const selectAll = document.querySelector('[data-select-all]');
            const syncBulk = () => {
                const selected = checks.filter((check) => check.checked);
                bulkBar.classList.toggle('hidden', selected.length === 0);
                selectedCount.textContent = selected.length;
                if (selectAll) {
                    selectAll.checked = selected.length > 0 && selected.length === checks.length;
                }
            };
            checks.forEach((check) => check.addEventListener('change', syncBulk));
            selectAll?.addEventListener('change', () => {
                checks.forEach((check) => check.checked = selectAll.checked);
                syncBulk();
            });

            document.querySelector('[data-bulk-form]')?.addEventListener('submit', async (event) => {
                event.preventDefault();
                const submitter = event.submitter;
                const action = submitter?.value;
                const selected = checks.filter((check) => check.checked).map((check) => check.value);
                if (action === 'delete' && !confirm('Esta accion eliminara los usuarios seleccionados. Deseas continuar?')) {
                    return;
                }

                const data = new FormData(event.currentTarget);
                data.set('action', action);
                selected.forEach((id) => data.append('ids[]', id));

                try {
                    const response = await fetch(event.currentTarget.action, {
                        method: 'POST',
                        credentials: 'same-origin',
                        headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': csrf },
                        body: data,
                    });
                    const bulkContentType = response.headers.get('content-type') || '';
                    const bulkIsJson = bulkContentType.includes('application/json');
                    const payload = bulkIsJson ? await response.json() : {};
                    if (!bulkIsJson) {
                        throw new Error('El servidor no devolvio una respuesta valida. Recarga la pagina e intenta otra vez.');
                    }
                    if (!response.ok) throw new Error(payload.message || 'No fue posible aplicar la accion masiva.');
                    toast('success', payload.message);
                    setTimeout(() => window.location.reload(), 700);
                } catch (error) {
                    toast('error', error.message);
                }
            });

            const range = document.querySelector('[data-created-range]');
            const customDates = document.querySelector('[data-custom-dates]');
            range?.addEventListener('change', () => {
                customDates.classList.toggle('hidden', range.value !== 'custom');
                customDates.classList.toggle('grid', range.value === 'custom');
            });
            document.querySelector('[data-filter-form]')?.addEventListener('submit', () => {
                document.querySelector('[data-table-loading]')?.classList.remove('hidden');
            });
        })();
    </script>
@endpush
