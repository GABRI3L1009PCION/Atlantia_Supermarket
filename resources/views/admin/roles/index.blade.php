@extends(auth()->user()?->isSuperAdmin() && request()->routeIs('admin.*') ? 'layouts.super-admin' : 'layouts.app')

@section('content')
    @php
        $roles = $data['roles'];
        $roleOrder = [
            'admin',
            'cliente',
            'vendedor',
            'proveedor',
            'bodeguero',
            'supervisor_logistica',
            'repartidor',
            'soporte',
            'contabilidad_finanzas',
            'empleado',
            'super_admin',
        ];
        $orderedRoles = collect($roleOrder)
            ->map(fn ($name) => $roles->firstWhere('name', $name))
            ->filter()
            ->merge($roles->reject(fn ($role) => in_array($role->name, $roleOrder, true)))
            ->values();
        $selectedRoleName = old('selected_role', request('role', $orderedRoles->first()?->name));
        $selectedRole = $orderedRoles->firstWhere('name', $selectedRoleName) ?? $orderedRoles->first();
        $roleLabels = [
            'admin' => 'Admin',
            'cliente' => 'Cliente',
            'bodeguero' => 'Bodeguero',
            'contabilidad_finanzas' => 'Contabilidad y finanzas',
            'empleado' => 'Empleado',
            'proveedor' => 'Proveedor',
            'repartidor' => 'Repartidor',
            'soporte' => 'Soporte',
            'super_admin' => 'Super Admin',
            'supervisor_logistica' => 'Supervisor de logistica',
            'vendedor' => 'Emprendedor',
        ];
        $roleInitials = [
            'admin' => 'AD',
            'cliente' => 'CL',
            'bodeguero' => 'BO',
            'contabilidad_finanzas' => 'CF',
            'empleado' => 'EM',
            'proveedor' => 'PR',
            'repartidor' => 'RE',
            'soporte' => 'SO',
            'super_admin' => 'SU',
            'supervisor_logistica' => 'SL',
            'vendedor' => 'EP',
        ];
        $permissionSections = [
            [
                'icon' => 'Lock',
                'name' => 'Panel administrativo',
                'permissions' => [
                    ['name' => 'admin.panel', 'label' => 'Panel del administrador', 'description' => 'Permite entrar al area administrativa y consultar el dashboard principal con metricas del negocio.', 'badges' => ['Esencial']],
                    ['name' => 'sistema.configurar', 'label' => 'Configuracion del sistema', 'description' => 'Permite modificar parametros globales, integraciones y ajustes delicados de la plataforma.', 'badges' => ['Avanzado', 'Riesgo alto']],
                    ['name' => 'auditoria.ver', 'label' => 'Auditoria del sistema', 'description' => 'Permite revisar el historial de acciones realizadas por usuarios dentro del panel administrativo.', 'badges' => ['Compliance', 'Lectura']],
                ],
            ],
            [
                'icon' => 'Box',
                'name' => 'Catalogo y productos',
                'permissions' => [
                    ['name' => 'catalogo.ver', 'label' => 'Ver catalogo', 'description' => 'Permite consultar productos, precios, imagenes, descripciones e inventario disponible.', 'badges' => ['Lectura']],
                    ['name' => 'categorias.gestionar', 'label' => 'Gestionar categorias', 'description' => 'Permite crear, editar, ordenar y eliminar categorias donde se organizan los productos.', 'badges' => ['Escritura', 'Admin']],
                    ['name' => 'inventario.gestionar', 'label' => 'Gestionar inventario', 'description' => 'Permite ajustar existencias, registrar entradas y salidas, y atender alertas de bajo stock.', 'badges' => ['Escritura', 'Operacional']],
                ],
            ],
            [
                'icon' => 'Cart',
                'name' => 'Ordenes y ventas',
                'permissions' => [
                    ['name' => 'checkout.crear', 'label' => 'Crear ventas manuales', 'description' => 'Permite crear pedidos desde el panel para ventas telefonicas, mostrador o punto de venta.', 'badges' => ['Ventas', 'POS']],
                    ['name' => 'ordenes.ver', 'label' => 'Ver ordenes', 'description' => 'Permite consultar pedidos con cliente, productos, estado, fechas y datos de entrega.', 'badges' => ['Lectura']],
                    ['name' => 'ordenes.procesar', 'label' => 'Procesar ordenes', 'description' => 'Permite cambiar estados de pedidos, preparar envios y generar documentos operativos.', 'badges' => ['Escritura', 'Operacional']],
                    ['name' => 'comisiones.ver_propias', 'label' => 'Ver mis comisiones', 'description' => 'Permite consultar unicamente el historial de comisiones propias del usuario.', 'badges' => ['Lectura personal']],
                ],
            ],
            [
                'icon' => 'Basket',
                'name' => 'Carrito de compras',
                'permissions' => [
                    ['name' => 'carrito.crear', 'label' => 'Crear carrito', 'description' => 'Permite iniciar carritos para clientes y agregar, editar o quitar productos.', 'badges' => ['Escritura']],
                    ['name' => 'carrito.gestionar', 'label' => 'Gestionar carrito', 'description' => 'Permite aplicar cupones, cambiar metodo de pago y realizar ajustes avanzados en carritos activos.', 'badges' => ['Escritura', 'Admin']],
                ],
            ],
            [
                'icon' => 'Box',
                'name' => 'Bodega e inventario operativo',
                'permissions' => [
                    ['name' => 'bodeguero.panel', 'label' => 'Panel de bodega', 'description' => 'Permite entrar al panel operativo de bodega y consultar tareas de preparacion, recepcion e inventario fisico.', 'badges' => ['Operacional']],
                    ['name' => 'inventario.ver_operativo', 'label' => 'Ver inventario operativo', 'description' => 'Permite consultar existencias, ubicaciones internas, productos pendientes de preparacion y disponibilidad fisica.', 'badges' => ['Lectura']],
                    ['name' => 'inventario.ajustar_stock', 'label' => 'Ajustar stock', 'description' => 'Permite registrar ajustes controlados por merma, conteo fisico, recepcion o correccion autorizada.', 'badges' => ['Escritura', 'Control']],
                    ['name' => 'pedidos.preparar', 'label' => 'Preparar pedidos', 'description' => 'Permite marcar productos como preparados, empacados y listos para despacho.', 'badges' => ['Bodega']],
                    ['name' => 'recepciones.registrar', 'label' => 'Registrar recepciones', 'description' => 'Permite registrar entrada de mercaderia recibida desde proveedores o compras internas.', 'badges' => ['Recepcion']],
                    ['name' => 'stock.reportar_incidencias', 'label' => 'Reportar incidencias de stock', 'description' => 'Permite informar faltantes, sobrantes, productos danados o diferencias contra inventario.', 'badges' => ['Incidencias']],
                ],
            ],
            [
                'icon' => 'Cart',
                'name' => 'Abastecimiento y proveedores',
                'permissions' => [
                    ['name' => 'proveedor.panel', 'label' => 'Panel de proveedor', 'description' => 'Permite entrar al panel del proveedor y consultar relacion comercial, solicitudes y documentos.', 'badges' => ['Externo']],
                    ['name' => 'catalogo_mayorista.gestionar', 'label' => 'Gestionar catalogo mayorista', 'description' => 'Permite mantener productos, precios, unidades de compra y disponibilidad para abastecimiento.', 'badges' => ['Catalogo']],
                    ['name' => 'cotizaciones.gestionar', 'label' => 'Gestionar cotizaciones', 'description' => 'Permite responder solicitudes de precio, condiciones comerciales y tiempos estimados de entrega.', 'badges' => ['Compras']],
                    ['name' => 'abastecimiento.ver_solicitudes', 'label' => 'Ver solicitudes de abastecimiento', 'description' => 'Permite revisar requerimientos de compra generados por Atlantia o por alertas de reabasto.', 'badges' => ['Lectura']],
                    ['name' => 'ordenes_compra.ver', 'label' => 'Ver ordenes de compra', 'description' => 'Permite consultar ordenes de compra, estados de entrega y condiciones acordadas.', 'badges' => ['Lectura']],
                    ['name' => 'facturas_proveedor.gestionar', 'label' => 'Gestionar facturas de proveedor', 'description' => 'Permite cargar o consultar documentos fiscales y comprobantes relacionados con abastecimiento.', 'badges' => ['Finanzas']],
                ],
            ],
            [
                'icon' => 'Lock',
                'name' => 'Soporte y atencion al cliente',
                'permissions' => [
                    ['name' => 'soporte.panel', 'label' => 'Panel de soporte', 'description' => 'Permite entrar al panel de soporte para atender casos de clientes, emprendedores y repartidores.', 'badges' => ['Atencion']],
                    ['name' => 'tickets.atender', 'label' => 'Atender tickets', 'description' => 'Permite tomar, responder, escalar y cerrar solicitudes de soporte registradas en la plataforma.', 'badges' => ['Soporte']],
                    ['name' => 'clientes.asistir', 'label' => 'Asistir clientes', 'description' => 'Permite consultar datos necesarios para orientar al cliente sin modificar informacion sensible.', 'badges' => ['Cliente']],
                    ['name' => 'pedidos.seguimiento_soporte', 'label' => 'Dar seguimiento a pedidos', 'description' => 'Permite revisar estados de pedido para informar avances, atrasos o incidencias al cliente.', 'badges' => ['Seguimiento']],
                    ['name' => 'devoluciones.gestionar_soporte', 'label' => 'Gestionar devoluciones desde soporte', 'description' => 'Permite documentar reclamos, solicitar evidencia y derivar devoluciones a revision operativa.', 'badges' => ['Postventa']],
                    ['name' => 'chatbot.supervisar', 'label' => 'Supervisar chatbot', 'description' => 'Permite revisar conversaciones del asistente virtual y escalar casos que requieren atencion humana.', 'badges' => ['Chatbot']],
                ],
            ],
            [
                'icon' => 'Cart',
                'name' => 'Contabilidad y finanzas',
                'permissions' => [
                    ['name' => 'contabilidad.panel', 'label' => 'Panel contable', 'description' => 'Permite entrar al panel financiero y revisar indicadores de pagos, comisiones y facturacion.', 'badges' => ['Finanzas']],
                    ['name' => 'pagos.conciliar', 'label' => 'Conciliar pagos', 'description' => 'Permite contrastar pagos, transferencias, cobros de tarjeta y registros internos de pedidos.', 'badges' => ['Conciliacion']],
                    ['name' => 'transferencias.validar', 'label' => 'Validar transferencias', 'description' => 'Permite confirmar transferencias bancarias reportadas por clientes o areas operativas.', 'badges' => ['Bancos']],
                    ['name' => 'comisiones.liquidar', 'label' => 'Liquidar comisiones', 'description' => 'Permite revisar, aprobar y registrar liquidaciones de comisiones de emprendedores.', 'badges' => ['Comisiones']],
                    ['name' => 'reportes_financieros.ver', 'label' => 'Ver reportes financieros', 'description' => 'Permite consultar reportes de ventas, pagos, cobros, comisiones y movimientos por periodo.', 'badges' => ['Reportes']],
                    ['name' => 'facturacion.ver', 'label' => 'Ver facturacion', 'description' => 'Permite consultar documentos fiscales, facturas emitidas, anulaciones y soportes contables.', 'badges' => ['Fiscal']],
                ],
            ],
            [
                'icon' => 'Box',
                'name' => 'Supervision logistica',
                'permissions' => [
                    ['name' => 'logistica.panel', 'label' => 'Panel logistico', 'description' => 'Permite entrar al panel de supervision logistica para revisar rutas, entregas y desempeno operativo.', 'badges' => ['Logistica']],
                    ['name' => 'repartidores.supervisar', 'label' => 'Supervisar repartidores', 'description' => 'Permite consultar disponibilidad, asignaciones y desempeno del equipo de reparto.', 'badges' => ['Reparto']],
                    ['name' => 'rutas.planificar', 'label' => 'Planificar rutas', 'description' => 'Permite organizar rutas, priorizar entregas y preparar cargas de trabajo por zona.', 'badges' => ['Rutas']],
                    ['name' => 'entregas.reasignar', 'label' => 'Reasignar entregas', 'description' => 'Permite cambiar repartidor o ruta cuando hay atrasos, ausencias o incidencias operativas.', 'badges' => ['Coordinacion']],
                    ['name' => 'incidencias_logistica.gestionar', 'label' => 'Gestionar incidencias logisticas', 'description' => 'Permite registrar, dar seguimiento y cerrar problemas de entrega, ruta o despacho.', 'badges' => ['Incidencias']],
                    ['name' => 'zonas_entrega.supervisar', 'label' => 'Supervisar zonas de entrega', 'description' => 'Permite revisar cobertura, capacidad operativa y restricciones de entrega por municipio o zona.', 'badges' => ['Cobertura']],
                ],
            ],
        ];
    @endphp

    <section class="rbac-light-surface -mx-4 -my-6 bg-white px-4 py-4 text-atlantia-ink sm:-mx-6 sm:px-6 lg:-mx-8 lg:px-8">
        <div class="mx-auto max-w-7xl rounded-xl border border-atlantia-rose/20 bg-white p-5 shadow-sm">
            <header class="grid gap-5 xl:grid-cols-[1fr_0.95fr] xl:items-center">
                <div class="flex items-center gap-4">
                    <span class="grid h-14 w-14 shrink-0 place-items-center rounded-md bg-atlantia-blush text-atlantia-wine">
                        <svg class="h-8 w-8" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                            <path d="M12 3L19 6V11.5C19 16.2 16 19.8 12 21C8 19.8 5 16.2 5 11.5V6L12 3Z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/>
                            <path d="M9 12L11 14L15 10" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </span>
                    <div>
                    <h1 class="text-3xl font-black leading-tight text-atlantia-ink">Gestion de permisos</h1>
                    <p class="mt-1 max-w-3xl text-sm leading-6 text-atlantia-ink/70">
                        Asigna permisos a roles y controla exactamente que puede hacer cada usuario en el sistema
                    </p>
                    </div>
                </div>

                <div class="grid w-full gap-3 lg:grid-cols-[minmax(260px,1fr)_auto_auto] lg:items-center">
                    <div class="relative min-w-0">
                        <span class="pointer-events-none absolute left-4 top-1/2 -translate-y-1/2 text-atlantia-ink/40">
                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                <path d="M11 19C15.4 19 19 15.4 19 11C19 6.6 15.4 3 11 3C6.6 3 3 6.6 3 11C3 15.4 6.6 19 11 19Z" stroke="currentColor" stroke-width="2"/>
                                <path d="M20.5 20.5L16.7 16.7" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                            </svg>
                        </span>
                        <input
                            type="search"
                            placeholder="Buscar permiso por nombre o descripcion..."
                            class="min-h-11 w-full rounded-md border border-atlantia-rose/35 bg-white pl-12 pr-4 text-sm outline-none transition focus:border-atlantia-wine focus:ring-2 focus:ring-atlantia-rose/25"
                            data-permission-search
                        >
                    </div>
                    <button type="button" class="min-h-11 rounded-md border border-atlantia-rose/35 bg-white px-5 text-sm font-black text-atlantia-wine transition hover:bg-atlantia-blush" data-open-role-modal>
                            Crear rol
                    </button>
                    <button type="button" class="min-h-11 rounded-md bg-atlantia-wine px-5 text-sm font-black text-white transition hover:bg-atlantia-wine-700" data-open-permission-modal>
                            Crear permiso
                    </button>
                </div>
            </header>

            <div class="relative mt-7">
                <button type="button" class="absolute -left-3 top-1/2 z-10 hidden h-10 w-10 -translate-y-1/2 place-items-center rounded-md border border-atlantia-rose/25 bg-white text-atlantia-wine shadow-sm transition hover:bg-atlantia-blush lg:grid" data-role-slider-prev aria-label="Ver roles anteriores">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                        <path d="M15 5L8 12L15 19" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </button>
                <div class="flex gap-3 overflow-x-auto scroll-smooth pb-1 [scrollbar-width:none] [&::-webkit-scrollbar]:hidden" role="tablist" aria-label="Seleccion de rol" data-role-slider>
                    @foreach ($orderedRoles as $role)
                        @php
                            $isActive = $selectedRole?->name === $role->name;
                        @endphp
                        <button
                            type="button"
                            class="{{ $isActive ? 'border-atlantia-wine bg-atlantia-blush/55 shadow-sm' : 'border-atlantia-rose/20 bg-white hover:border-atlantia-wine/45 hover:bg-atlantia-cream' }} relative grid min-h-24 w-24 shrink-0 place-items-center rounded-md border p-3 text-center transition"
                            data-role-tab="{{ $role->name }}"
                            role="tab"
                            aria-selected="{{ $isActive ? 'true' : 'false' }}"
                        >
                            @if ($isActive)
                                <span class="absolute right-2 top-2 grid h-5 w-5 place-items-center rounded-full bg-atlantia-wine text-white">
                                    <svg class="h-3 w-3" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                        <path d="M5 12L10 17L20 7" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"/>
                                    </svg>
                                </span>
                            @endif
                            <span class="mx-auto grid h-10 w-10 place-items-center rounded-full bg-atlantia-wine text-sm font-black text-white">
                                {{ $roleInitials[$role->name] ?? strtoupper(substr($role->name, 0, 2)) }}
                            </span>
                            <span class="mt-2 line-clamp-2 block text-xs font-black text-atlantia-ink">{{ $roleLabels[$role->name] ?? ucfirst($role->name) }}</span>
                        </button>
                    @endforeach
                </div>
                <button type="button" class="absolute -right-3 top-1/2 z-10 hidden h-10 w-10 -translate-y-1/2 place-items-center rounded-md border border-atlantia-rose/25 bg-white text-atlantia-wine shadow-sm transition hover:bg-atlantia-blush lg:grid" data-role-slider-next aria-label="Ver mas roles">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                        <path d="M9 5L16 12L9 19" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </button>
            </div>

            @foreach ($orderedRoles as $role)
                @php
                    $isActive = $selectedRole?->name === $role->name;
                    $isSuperAdmin = $role->name === 'super_admin';
                    $assignedPermissions = $role->permissions->pluck('name');
                @endphp

                <form
                    method="POST"
                    action="{{ route('admin.roles-permisos.sync', $role) }}"
                    class="{{ $isActive ? '' : 'hidden' }} mt-8 transition"
                    data-role-panel="{{ $role->name }}"
                >
                    @csrf
                    @method('PUT')
                    <input type="hidden" name="selected_role" value="{{ $role->name }}">

                    <div class="mb-4 flex flex-col gap-3 rounded-md border border-atlantia-rose/20 bg-atlantia-cream px-4 py-3 sm:flex-row sm:items-center sm:justify-between" data-role-summary>
                        <div class="flex items-center gap-3">
                            <span class="grid h-10 w-10 shrink-0 place-items-center rounded-full border border-atlantia-rose/20 bg-white text-atlantia-wine">
                                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                    <path d="M12 12C14.2 12 16 10.2 16 8C16 5.8 14.2 4 12 4C9.8 4 8 5.8 8 8C8 10.2 9.8 12 12 12ZM5 20C5.8 16.9 8.4 15 12 15C15.6 15 18.2 16.9 19 20" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                                </svg>
                            </span>
                            <div>
                            <p class="text-lg font-black text-atlantia-wine">Permisos del rol: {{ $roleLabels[$role->name] ?? ucfirst($role->name) }}</p>
                            <p class="text-sm text-atlantia-ink/65">
                                {{ $isSuperAdmin ? 'Rol protegido: conserva todos los permisos core.' : 'Aqui ves todos los permisos disponibles para este rol. Marca o desmarca y guarda al final.' }}
                            </p>
                            </div>
                        </div>
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="rounded-full bg-white px-4 py-1.5 text-xs font-black text-atlantia-wine ring-1 ring-atlantia-rose/20">
                                {{ collect($permissionSections)->sum(fn ($section) => count($section['permissions'])) }} permisos disponibles
                            </span>
                            <span class="rounded-full bg-emerald-50 px-4 py-1.5 text-xs font-black text-emerald-700 ring-1 ring-emerald-200" data-change-status>
                                Sin cambios pendientes
                            </span>
                        </div>
                    </div>

                    <div class="grid gap-4 xl:grid-cols-3" data-permission-sections>
                        @foreach ($permissionSections as $section)
                            <section class="overflow-hidden rounded-md border border-atlantia-rose/20 bg-white" data-permission-section>
                                <header class="border-b border-atlantia-rose/15 px-4 py-3">
                                    <div class="flex flex-wrap items-center justify-between gap-3">
                                        <div class="flex items-center gap-3">
                                            <span class="grid h-9 w-9 place-items-center rounded-md bg-atlantia-blush text-atlantia-wine">
                                                @if ($section['icon'] === 'Lock')
                                                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M8 10V8C8 5.8 9.8 4 12 4C14.2 4 16 5.8 16 8V10" stroke="currentColor" stroke-width="2" stroke-linecap="round"/><rect x="5" y="10" width="14" height="10" rx="2" stroke="currentColor" stroke-width="2"/></svg>
                                                @elseif ($section['icon'] === 'Box')
                                                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M4 8L12 4L20 8V16L12 20L4 16V8Z" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/><path d="M4 8L12 12L20 8" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/></svg>
                                                @elseif ($section['icon'] === 'Cart')
                                                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M4 5H6L8.2 15H17.5L20 8H7" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><circle cx="10" cy="19" r="1" fill="currentColor"/><circle cx="17" cy="19" r="1" fill="currentColor"/></svg>
                                                @else
                                                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M5 8H19L18 20H6L5 8Z" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/><path d="M9 8C9 5.8 10.3 4 12 4C13.7 4 15 5.8 15 8" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
                                                @endif
                                            </span>
                                            <div>
                                                <h2 class="text-base font-black text-atlantia-ink">{{ $section['name'] }}</h2>
                                                <p class="text-sm text-atlantia-ink/55">{{ count($section['permissions']) }} permisos</p>
                                            </div>
                                        </div>
                                    </div>
                                </header>

                                <div class="divide-y divide-atlantia-rose/12">
                                    @foreach ($section['permissions'] as $permission)
                                        @php
                                            $isChecked = $isSuperAdmin || $assignedPermissions->contains($permission['name']);
                                            $isLocked = $isSuperAdmin || ($role->name === 'admin' && $permission['name'] === 'admin.panel');
                                            $haystack = strtolower($permission['label'] . ' ' . $permission['name'] . ' ' . $permission['description'] . ' ' . implode(' ', $permission['badges']));
                                        @endphp
                                        <article
                                            class="bg-white px-4 py-3 transition hover:bg-atlantia-cream/50"
                                            data-permission-card
                                            data-permission-search-text="{{ $haystack }}"
                                            data-permission-name="{{ $permission['name'] }}"
                                        >
                                            <div class="flex gap-3">
                                                <div class="pt-1">
                                                    <input
                                                        id="{{ $role->name }}-{{ str_replace('.', '-', $permission['name']) }}"
                                                        type="checkbox"
                                                        name="permissions[]"
                                                        value="{{ $permission['name'] }}"
                                                        @checked($isChecked)
                                                        @disabled($isLocked)
                                                        class="h-4 w-4 rounded border-atlantia-rose text-atlantia-wine focus:ring-atlantia-rose"
                                                        data-permission-checkbox
                                                    >
                                                    @if ($isLocked)
                                                        <input type="hidden" name="permissions[]" value="{{ $permission['name'] }}">
                                                    @endif
                                                </div>

                                                <div class="min-w-0 flex-1">
                                                    <label for="{{ $role->name }}-{{ str_replace('.', '-', $permission['name']) }}" class="text-sm font-black text-atlantia-ink">
                                                        {{ $permission['label'] }}
                                                    </label>
                                                    <p class="mt-0.5 font-mono text-[11px] font-bold text-atlantia-wine/65">{{ $permission['name'] }}</p>
                                                    <p class="mt-1 line-clamp-2 text-xs leading-5 text-atlantia-ink/65">{{ $permission['description'] }}</p>
                                                    <div class="mt-2 flex flex-wrap gap-1.5">
                                                        @foreach ($permission['badges'] as $badge)
                                                            <span class="rounded-full bg-white px-2 py-0.5 text-[11px] font-bold text-atlantia-wine ring-1 ring-atlantia-rose/20">{{ $badge }}</span>
                                                        @endforeach
                                                        @if ($isLocked)
                                                            <span class="rounded-full bg-atlantia-blush px-2 py-0.5 text-[11px] font-bold text-atlantia-wine ring-1 ring-atlantia-rose/20">Protegido</span>
                                                        @endif
                                                    </div>
                                                </div>

                                                <div class="flex shrink-0 items-start gap-2">
                                                    <button type="button" class="rounded-md border border-atlantia-rose/25 px-2 py-1 text-[11px] font-black text-atlantia-wine transition hover:bg-atlantia-blush" aria-label="Ver detalle de {{ $permission['label'] }}" title="{{ $permission['description'] }}">
                                                        Detalle
                                                    </button>
                                                    <button type="button" class="rounded-md px-2 py-1.5 text-atlantia-ink/45 transition hover:bg-atlantia-blush hover:text-atlantia-wine" aria-label="Mas opciones para {{ $permission['name'] }}">
                                                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><circle cx="12" cy="5" r="1.7"/><circle cx="12" cy="12" r="1.7"/><circle cx="12" cy="19" r="1.7"/></svg>
                                                    </button>
                                                </div>
                                            </div>
                                        </article>
                                    @endforeach
                                </div>
                            </section>
                        @endforeach
                    </div>

                    <div class="mt-6 hidden rounded-xl border border-atlantia-rose/20 bg-atlantia-cream p-8 text-center" data-empty-state>
                        <p class="text-lg font-black text-atlantia-wine">No encontramos permisos con esa busqueda</p>
                        <p class="mt-2 text-sm text-atlantia-ink/60">Prueba con otro nombre tecnico, descripcion o badge.</p>
                    </div>

                    <footer class="mt-5 flex flex-col gap-3 rounded-md border border-atlantia-rose/20 bg-atlantia-cream/55 px-4 py-3 sm:flex-row sm:items-center sm:justify-between">
                        <p class="flex items-center gap-3 text-sm font-bold text-atlantia-ink/60" data-rule-hint>
                            <span class="grid h-7 w-7 place-items-center rounded-full border border-atlantia-rose/25 text-atlantia-wine">
                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                    <path d="M12 8V12M12 16H12.01M21 12C21 17 17 21 12 21C7 21 3 17 3 12C3 7 7 3 12 3C17 3 21 7 21 12Z" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                                </svg>
                            </span>
                            Los cambios se guardan solo al presionar el boton principal.
                        </p>
                        <div class="flex justify-end gap-3">
                            <button type="reset" class="min-h-11 rounded-md border border-atlantia-rose/30 px-7 text-sm font-black text-atlantia-ink/70 transition hover:bg-atlantia-blush">
                                Descartar
                            </button>
                            <x-ui.button type="submit" :disabled="$isSuperAdmin" class="min-h-11 px-7">
                                Guardar cambios
                            </x-ui.button>
                        </div>
                    </footer>
                </form>
            @endforeach
        </div>

        <div class="fixed inset-0 z-[110] hidden items-center justify-center bg-slate-950/60 px-4 py-6" data-role-create-modal>
            <section class="w-full max-w-2xl rounded-2xl border border-atlantia-rose/25 bg-white p-6 shadow-2xl">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <p class="text-xs font-black uppercase tracking-wide text-atlantia-rose">Nuevo rol</p>
                        <h2 class="mt-1 text-2xl font-black text-atlantia-ink">Crear rol</h2>
                        <p class="mt-2 text-sm leading-6 text-atlantia-ink/65">Define un rol operativo y asignale permisos iniciales.</p>
                    </div>
                    <button type="button" class="rounded-md border border-atlantia-rose/30 px-3 py-2 text-sm font-black text-atlantia-wine hover:bg-atlantia-blush" data-close-role-modal>
                        Cerrar
                    </button>
                </div>

                <form method="POST" action="{{ route('admin.roles-permisos.store') }}" class="mt-5 space-y-4">
                    @csrf
                    <div>
                        <label class="text-sm font-bold text-atlantia-ink" for="new-role-name">Nombre tecnico</label>
                        <input id="new-role-name" name="name" type="text" placeholder="supervisor_bodega" class="mt-1 w-full rounded-md border border-atlantia-rose/35 bg-white px-3 py-2 text-sm outline-none transition focus:border-atlantia-wine focus:ring-2 focus:ring-atlantia-rose/25" required>
                    </div>
                    <div>
                        <p class="text-sm font-bold text-atlantia-ink">Permisos iniciales</p>
                        <div class="mt-2 max-h-72 overflow-y-auto rounded-xl border border-atlantia-rose/20 p-3">
                            @foreach ($permissionSections as $section)
                                <p class="px-2 py-2 text-xs font-black uppercase tracking-wide text-atlantia-rose">{{ $section['name'] }}</p>
                                @foreach ($section['permissions'] as $permission)
                                    <label class="flex items-center gap-3 rounded-md px-2 py-2 text-sm hover:bg-atlantia-cream">
                                        <input type="checkbox" name="permissions[]" value="{{ $permission['name'] }}" class="rounded border-atlantia-rose text-atlantia-wine focus:ring-atlantia-rose">
                                        <span class="font-semibold text-atlantia-ink">{{ $permission['label'] }}</span>
                                        <span class="font-mono text-xs text-atlantia-wine/65">{{ $permission['name'] }}</span>
                                    </label>
                                @endforeach
                            @endforeach
                        </div>
                    </div>
                    <div class="flex justify-end gap-3 border-t border-atlantia-rose/15 pt-4">
                        <button type="button" class="rounded-md border border-atlantia-rose/30 px-4 py-2 text-sm font-black text-atlantia-wine hover:bg-atlantia-blush" data-close-role-modal>Cancelar</button>
                        <x-ui.button type="submit">Crear rol</x-ui.button>
                    </div>
                </form>
            </section>
        </div>

        <div class="fixed inset-0 z-[110] hidden items-center justify-center bg-slate-950/60 px-4 py-6" data-permission-create-modal>
            <section class="w-full max-w-xl rounded-2xl border border-atlantia-rose/25 bg-white p-6 shadow-2xl">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <p class="text-xs font-black uppercase tracking-wide text-atlantia-rose">Nuevo permiso</p>
                        <h2 class="mt-1 text-2xl font-black text-atlantia-ink">Crear permiso</h2>
                        <p class="mt-2 text-sm leading-6 text-atlantia-ink/65">Agrega permisos tecnicos para nuevos modulos del sistema.</p>
                    </div>
                    <button type="button" class="rounded-md border border-atlantia-rose/30 px-3 py-2 text-sm font-black text-atlantia-wine hover:bg-atlantia-blush" data-close-permission-modal>
                        Cerrar
                    </button>
                </div>

                <form method="POST" action="{{ route('admin.roles-permisos.permisos.store') }}" class="mt-5 space-y-4">
                    @csrf
                    <div>
                        <label class="text-sm font-bold text-atlantia-ink" for="new-permission-name">Nombre tecnico</label>
                        <input id="new-permission-name" name="name" type="text" placeholder="bodega.transferencias.validar" class="mt-1 w-full rounded-md border border-atlantia-rose/35 bg-white px-3 py-2 text-sm outline-none transition focus:border-atlantia-wine focus:ring-2 focus:ring-atlantia-rose/25" required>
                        <p class="mt-2 text-xs text-atlantia-ink/55">Usa segmentos con punto para mantener orden por modulo.</p>
                    </div>
                    <div class="flex justify-end gap-3 border-t border-atlantia-rose/15 pt-4">
                        <button type="button" class="rounded-md border border-atlantia-rose/30 px-4 py-2 text-sm font-black text-atlantia-wine hover:bg-atlantia-blush" data-close-permission-modal>Cancelar</button>
                        <x-ui.button type="submit">Crear permiso</x-ui.button>
                    </div>
                </form>
            </section>
        </div>
    </section>
@endsection

@push('scripts')
    <script nonce="{{ request()->attributes->get('csp_nonce') }}">
        (() => {
            const tabs = [...document.querySelectorAll('[data-role-tab]')];
            const panels = [...document.querySelectorAll('[data-role-panel]')];
            const search = document.querySelector('[data-permission-search]');
            const roleSlider = document.querySelector('[data-role-slider]');
            const slideRoles = (direction) => {
                roleSlider?.scrollBy({ left: direction * 360, behavior: 'smooth' });
            };

            document.querySelector('[data-role-slider-prev]')?.addEventListener('click', () => slideRoles(-1));
            document.querySelector('[data-role-slider-next]')?.addEventListener('click', () => slideRoles(1));

            const activePanel = () => panels.find((panel) => !panel.classList.contains('hidden'));

            const updateSearch = () => {
                const term = (search?.value || '').trim().toLowerCase();
                const panel = activePanel();
                if (!panel) return;

                let visibleCards = 0;
                panel.querySelectorAll('[data-permission-card]').forEach((card) => {
                    const match = term === '' || card.dataset.permissionSearchText.includes(term);
                    card.classList.toggle('hidden', !match);
                    visibleCards += match ? 1 : 0;
                });

                panel.querySelectorAll('[data-permission-section]').forEach((section) => {
                    const hasVisible = [...section.querySelectorAll('[data-permission-card]')].some((card) => !card.classList.contains('hidden'));
                    section.classList.toggle('hidden', !hasVisible);
                });

                panel.querySelector('[data-empty-state]')?.classList.toggle('hidden', visibleCards > 0);
            };

            const activateRole = (role, shouldScroll = false) => {
                tabs.forEach((tab) => {
                    const active = tab.dataset.roleTab === role;
                    tab.classList.toggle('border-atlantia-wine', active);
                    tab.classList.toggle('bg-atlantia-blush/65', active);
                    tab.classList.toggle('shadow-sm', active);
                    tab.classList.toggle('bg-white', !active);
                    tab.setAttribute('aria-selected', active ? 'true' : 'false');
                });

                panels.forEach((panel) => {
                    panel.classList.toggle('hidden', panel.dataset.rolePanel !== role);
                });

                updateSearch();

                if (shouldScroll) {
                    document.querySelector(`[data-role-panel="${role}"] [data-role-summary]`)?.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start',
                    });
                }
            };

            tabs.forEach((tab) => {
                tab.addEventListener('click', () => activateRole(tab.dataset.roleTab, true));
            });

            search?.addEventListener('input', updateSearch);

            const openModal = (selector) => {
                const modal = document.querySelector(selector);
                modal?.classList.remove('hidden');
                modal?.classList.add('flex');
                modal?.querySelector('input')?.focus();
            };

            const closeModal = (selector) => {
                const modal = document.querySelector(selector);
                modal?.classList.add('hidden');
                modal?.classList.remove('flex');
            };

            document.querySelector('[data-open-role-modal]')?.addEventListener('click', () => openModal('[data-role-create-modal]'));
            document.querySelector('[data-open-permission-modal]')?.addEventListener('click', () => openModal('[data-permission-create-modal]'));
            document.querySelectorAll('[data-close-role-modal]').forEach((button) => {
                button.addEventListener('click', () => closeModal('[data-role-create-modal]'));
            });
            document.querySelectorAll('[data-close-permission-modal]').forEach((button) => {
                button.addEventListener('click', () => closeModal('[data-permission-create-modal]'));
            });
            document.querySelector('[data-role-create-modal]')?.addEventListener('click', (event) => {
                if (event.target === event.currentTarget) closeModal('[data-role-create-modal]');
            });
            document.querySelector('[data-permission-create-modal]')?.addEventListener('click', (event) => {
                if (event.target === event.currentTarget) closeModal('[data-permission-create-modal]');
            });

            document.querySelectorAll('[data-permission-checkbox]').forEach((checkbox) => {
                checkbox.addEventListener('change', () => {
                    const form = checkbox.closest('form');
                    const hint = form?.querySelector('[data-rule-hint]');
                    const changeStatus = form?.querySelector('[data-change-status]');
                    const name = checkbox.value;

                    checkbox.closest('[data-permission-card]')?.classList.toggle('bg-white', checkbox.checked);
                    if (changeStatus) {
                        changeStatus.textContent = 'Cambios pendientes';
                        changeStatus.classList.remove('bg-emerald-50', 'text-emerald-700', 'ring-emerald-200');
                        changeStatus.classList.add('bg-atlantia-blush', 'text-atlantia-wine', 'ring-atlantia-rose/20');
                    }

                    if (name === 'categorias.gestionar' && checkbox.checked && hint) {
                        hint.textContent = 'Recomendacion: categorias.gestionar funciona mejor junto con catalogo.ver.';
                    } else if (name === 'carrito.gestionar' && checkbox.checked && hint) {
                        const required = form?.querySelector('input[value="carrito.crear"][data-permission-checkbox]');
                        if (required) required.checked = true;
                        hint.textContent = 'carrito.gestionar requiere carrito.crear; se marco automaticamente.';
                    } else if (hint) {
                        hint.textContent = 'Cambios pendientes. Revisa y guarda cuando estes listo.';
                    }
                });
            });

            document.querySelectorAll('form[data-role-panel]').forEach((form) => {
                form.addEventListener('reset', () => {
                    setTimeout(() => {
                        form.querySelector('[data-rule-hint]').textContent = 'Los cambios se guardan solo al presionar el boton principal.';
                    }, 0);
                });
            });

            activateRole(@js($selectedRole?->name ?? $orderedRoles->first()?->name));
        })();
    </script>
@endpush
