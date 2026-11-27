@php
    $user = auth()->user();
    $internalRoles = ['empleado', 'bodeguero', 'soporte', 'contabilidad_finanzas', 'supervisor_logistica'];
    $logoPath = collect(['images/logo-atlantia-delivery.png', 'images/logo.png', 'images/atlantia-logo.svg'])
        ->first(fn (string $ruta): bool => file_exists(public_path($ruta))) ?? 'images/atlantia-logo.svg';
    $adminName = $user?->name ?? 'Administrador';
    $adminEmail = $user?->email ?? 'admin@atlantia.local';
    $nameParts = preg_split('/\s+/', trim($adminName)) ?: [];
    $adminInitials = collect($nameParts)
        ->filter()
        ->take(2)
        ->map(fn ($part) => mb_strtoupper(mb_substr($part, 0, 1)))
        ->join('') ?: 'A';
    $panelLabel = match (true) {
        $user?->hasRole('admin') => 'ADMIN',
        $user?->hasRole('vendedor') => 'VENDEDOR',
        $user?->hasRole('bodeguero') => 'BODEGA',
        $user?->hasRole('soporte') => 'SOPORTE',
        $user?->hasRole('contabilidad_finanzas') => 'FINANZAS',
        $user?->hasRole('supervisor_logistica') => 'LOGISTICA',
        $user?->hasRole('empleado') => 'EMPLEADO',
        $user?->hasRole('repartidor') => 'REPARTO',
        default => 'CUENTA',
    };
    $dashboardRoute = match (true) {
        $user?->hasRole('admin') => route('admin.dashboard'),
        $user?->hasRole('vendedor') => route('vendedor.dashboard'),
        $user?->hasAnyRole($internalRoles) => route('empleado.dashboard'),
        $user?->hasRole('repartidor') => route('repartidor.dashboard'),
        default => route('home'),
    };
    $iconFor = fn (string $label) => match ($label) {
        'Vista general' => 'dashboard',
        'Usuarios' => 'users',
        'Roles y permisos' => 'shield',
        'Vendedores' => 'store',
        'Empleados' => 'badge',
        'Repartidores', 'Entregas', 'Rutas', 'Tiendas online', 'Entregas externas' => 'truck',
        'Ganancias', 'Finanzas repartidor' => 'coins',
        'Historial' => 'chart',
        'Soporte', 'Soporte repartidor' => 'message',
        'Productos', 'Inventario' => 'package',
        'Categorias' => 'grid',
        'Banners hero' => 'image',
        'Pedidos', 'Mis pedidos' => 'receipt',
        'Zonas de entrega', 'Direcciones' => 'map',
        'Comisiones', 'Nominas' => 'coins',
        'Cupones' => 'ticket',
        'DTE y FEL', 'DTE', 'Perfil fiscal' => 'file',
        'Resenas', 'Wishlist' => 'star',
        'Devoluciones' => 'rotate',
        'Antifraude', 'Auditoria' => 'lock',
        'Reportes' => 'chart',
        'Monitor ML', 'Prediccion demanda' => 'activity',
        'Reentrenamiento ML', 'Reabasto ML' => 'cpu',
        'Transferencias' => 'swap',
        'Mensajes', 'Ayuda y soporte' => 'message',
        'Atlantia Store' => 'store',
        'Precios de planes' => 'coins',
        'Denuncias' => 'shield',
        'Seguridad de la cuenta' => 'lock',
        'Mi perfil' => 'users',
        'Horario y pausa' => 'clock',
        'Promociones' => 'ticket',
        'Mi plan y publicidad' => 'megaphone',
        'Ventas y pagos' => 'coins',
        'Reseñas' => 'star',
        'Facturas (DTE)', 'Datos de facturación' => 'file',
        'Predicción de demanda' => 'activity',
        'Reabasto sugerido' => 'cpu',
        'Saldo e invitaciones' => 'coins',
        default => 'dot',
    };
    $icons = [
        'activity' => '<path d="M3 12h4l3-8 4 16 3-8h4"/>',
        'clock' => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>',
        'megaphone' => '<path d="M3 11v2a1 1 0 0 0 1 1h2l5 4V6L6 10H4a1 1 0 0 0-1 1Z"/><path d="M15 8a5 5 0 0 1 0 8"/><path d="M18 5a9 9 0 0 1 0 14"/>',
        'badge' => '<path d="M15 3H9a2 2 0 0 0-2 2v14l5-3 5 3V5a2 2 0 0 0-2-2Z"/>',
        'chart' => '<path d="M4 19V5"/><path d="M4 19h16"/><path d="M8 16v-5"/><path d="M12 16V8"/><path d="M16 16v-8"/>',
        'coins' => '<path d="M12 6c0 1.7-2.2 3-5 3S2 7.7 2 6s2.2-3 5-3 5 1.3 5 3Z"/><path d="M2 6v6c0 1.7 2.2 3 5 3 .8 0 1.6-.1 2.2-.3"/><path d="M22 13c0 1.7-2.2 3-5 3s-5-1.3-5-3 2.2-3 5-3 5 1.3 5 3Z"/><path d="M12 13v5c0 1.7 2.2 3 5 3s5-1.3 5-3v-5"/>',
        'cpu' => '<rect x="7" y="7" width="10" height="10" rx="2"/><path d="M9 1v3"/><path d="M15 1v3"/><path d="M9 20v3"/><path d="M15 20v3"/><path d="M20 9h3"/><path d="M20 15h3"/><path d="M1 9h3"/><path d="M1 15h3"/>',
        'dashboard' => '<path d="M4 13a8 8 0 1 1 16 0"/><path d="M12 13l4-4"/><path d="M7 17h10"/>',
        'dot' => '<circle cx="12" cy="12" r="3"/>',
        'file' => '<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8Z"/><path d="M14 2v6h6"/><path d="M8 13h8"/><path d="M8 17h5"/>',
        'grid' => '<rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/>',
        'image' => '<rect x="3" y="5" width="18" height="14" rx="2"/><circle cx="8.5" cy="10.5" r="1.5"/><path d="M21 16l-5-5L5 19"/>',
        'lock' => '<rect x="4" y="11" width="16" height="10" rx="2"/><path d="M8 11V7a4 4 0 0 1 8 0v4"/>',
        'map' => '<path d="M9 18l-6 3V6l6-3 6 3 6-3v15l-6 3-6-3Z"/><path d="M9 3v15"/><path d="M15 6v15"/>',
        'message' => '<path d="M21 15a4 4 0 0 1-4 4H8l-5 3V7a4 4 0 0 1 4-4h10a4 4 0 0 1 4 4Z"/>',
        'package' => '<path d="M21 8 12 3 3 8l9 5 9-5Z"/><path d="M3 8v8l9 5 9-5V8"/><path d="M12 13v8"/>',
        'receipt' => '<path d="M5 3v18l2-1 2 1 2-1 2 1 2-1 2 1 2-1V3Z"/><path d="M8 7h8"/><path d="M8 11h8"/><path d="M8 15h5"/>',
        'rotate' => '<path d="M3 12a9 9 0 0 1 15.5-6.2L21 8"/><path d="M21 3v5h-5"/><path d="M21 12a9 9 0 0 1-15.5 6.2L3 16"/><path d="M3 21v-5h5"/>',
        'shield' => '<path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10Z"/>',
        'star' => '<path d="m12 3 2.7 5.5 6.1.9-4.4 4.3 1 6.1L12 17l-5.4 2.8 1-6.1-4.4-4.3 6.1-.9Z"/>',
        'store' => '<path d="M4 10h16l-1-5H5Z"/><path d="M6 10v10h12V10"/><path d="M9 20v-6h6v6"/><path d="M4 10a3 3 0 0 0 6 0"/><path d="M10 10a3 3 0 0 0 6 0"/><path d="M16 10a3 3 0 0 0 6 0"/>',
        'swap' => '<path d="M16 3h5v5"/><path d="M21 3 3 21"/><path d="M8 3H3v5"/><path d="M3 3l18 18"/>',
        'ticket' => '<path d="M3 9a3 3 0 0 0 0 6v3a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-3a3 3 0 0 0 0-6V6a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2Z"/><path d="M13 5v14"/>',
        'truck' => '<path d="M3 6h11v10H3Z"/><path d="M14 10h4l3 3v3h-7Z"/><circle cx="7" cy="18" r="2"/><circle cx="17" cy="18" r="2"/>',
        'users' => '<path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.9"/><path d="M16 3.1a4 4 0 0 1 0 7.8"/>',
    ];

    $sections = match (true) {
        $user?->hasRole('admin') => [
            'Gestion' => [
                ['label' => 'Vista general', 'route' => route('admin.dashboard'), 'active' => request()->routeIs('admin.dashboard')],
                ['label' => 'Atlantia Store', 'route' => route('admin.tienda-atlantia.index'), 'active' => request()->routeIs('admin.tienda-atlantia.*')],
                ['label' => 'Usuarios', 'route' => route('admin.usuarios.index'), 'active' => request()->routeIs('admin.usuarios.*')],
                ['label' => 'Roles y permisos', 'route' => route('admin.roles-permisos.index'), 'active' => request()->routeIs('admin.roles-permisos.*')],
                ['label' => 'Vendedores', 'route' => route('admin.vendedores.index'), 'active' => request()->routeIs('admin.vendedores.*')],
                ['label' => 'Empleados', 'route' => route('admin.empleados.index'), 'active' => request()->routeIs('admin.empleados.*')],
                ['label' => 'Repartidores', 'route' => route('admin.repartidores.index'), 'active' => request()->routeIs('admin.repartidores.*')],
                ['label' => 'Mensajes', 'route' => route('admin.mensajes.index'), 'active' => request()->routeIs('admin.mensajes.*')],
                ['label' => 'Denuncias', 'route' => route('admin.denuncias.index'), 'active' => request()->routeIs('admin.denuncias.*')],
            ],
            'Catalogo' => [
                ['label' => 'Productos', 'route' => route('admin.productos.index'), 'active' => request()->routeIs('admin.productos.*')],
                ['label' => 'Categorias', 'route' => route('admin.categorias.index'), 'active' => request()->routeIs('admin.categorias.*')],
                ['label' => 'Banners hero', 'route' => route('admin.hero-banners.index'), 'active' => request()->routeIs('admin.hero-banners.*')],
                ['label' => 'Pedidos', 'route' => route('admin.pedidos.index'), 'active' => request()->routeIs('admin.pedidos.*')],
                ['label' => 'Entregas externas', 'route' => route('admin.entregas-externas.index'), 'active' => request()->routeIs('admin.entregas-externas.*')],
                ['label' => 'Zonas de entrega', 'route' => route('admin.zonas-entrega.index'), 'active' => request()->routeIs('admin.zonas-entrega.*')],
            ],
            'Finanzas y ML' => [
                ['label' => 'Utilidad', 'route' => route('admin.utilidad.index'), 'active' => request()->routeIs('admin.utilidad.*')],
                ['label' => 'Pagos a vendedores', 'route' => route('admin.liquidaciones.index'), 'active' => request()->routeIs('admin.liquidaciones.*')],
                ['label' => 'Comisiones', 'route' => route('admin.comisiones.index'), 'active' => request()->routeIs('admin.comisiones.*')],
                ['label' => 'Nominas', 'route' => route('admin.nominas.index'), 'active' => request()->routeIs('admin.nominas.*')],
                ['label' => 'Cupones', 'route' => route('admin.cupones.index'), 'active' => request()->routeIs('admin.cupones.*')],
                ['label' => 'Promociones', 'route' => route('admin.promociones.index'), 'active' => request()->routeIs('admin.promociones.*')],
                ['label' => 'Planes y publicidad', 'route' => route('admin.servicios.index'), 'active' => request()->routeIs('admin.servicios.*')],
                ['label' => 'Precios de planes', 'route' => route('admin.precios-planes.index'), 'active' => request()->routeIs('admin.precios-planes.*')],
                ['label' => 'Datos de mercado', 'route' => route('admin.datos-mercado.index'), 'active' => request()->routeIs('admin.datos-mercado.*')],
                ['label' => 'DTE y FEL', 'route' => route('admin.dte.index'), 'active' => request()->routeIs('admin.dte.*')],
                ['label' => 'Resenas', 'route' => route('admin.resenas.index'), 'active' => request()->routeIs('admin.resenas.*')],
                ['label' => 'Devoluciones', 'route' => route('admin.devoluciones.index'), 'active' => request()->routeIs('admin.devoluciones.*')],
                ['label' => 'Antifraude', 'route' => route('admin.antifraude.index'), 'active' => request()->routeIs('admin.antifraude.*')],
                ['label' => 'Auditoria', 'route' => route('admin.auditoria.index'), 'active' => request()->routeIs('admin.auditoria.*')],
                ['label' => 'Reportes', 'route' => route('admin.reportes.index'), 'active' => request()->routeIs('admin.reportes.*')],
                ['label' => 'Monitor ML', 'route' => route('admin.ml.monitor'), 'active' => request()->routeIs('admin.ml.monitor')],
                ['label' => 'Reentrenamiento ML', 'route' => route('admin.ml.reentrenamiento.index'), 'active' => request()->routeIs('admin.ml.reentrenamiento.*')],
            ],
        ],
        $user?->hasRole('vendedor') => [
            'Mi tienda' => [
                ['label' => 'Vista general', 'route' => route('vendedor.dashboard'), 'active' => request()->routeIs('vendedor.dashboard')],
                ['label' => 'Pedidos', 'route' => route('vendedor.pedidos.index'), 'active' => request()->routeIs('vendedor.pedidos.*')],
                ['label' => 'Devoluciones', 'route' => route('vendedor.devoluciones.index'), 'active' => request()->routeIs('vendedor.devoluciones.*')],
                ['label' => 'Productos', 'route' => route('vendedor.productos.index'), 'active' => request()->routeIs('vendedor.productos.*')],
                ['label' => 'Inventario', 'route' => route('vendedor.inventario.index'), 'active' => request()->routeIs('vendedor.inventario.*')],
                ['label' => 'Promociones', 'route' => route('vendedor.promociones.index'), 'active' => request()->routeIs('vendedor.promociones.*')],
                ['label' => 'Cupones', 'route' => route('vendedor.cupones.index'), 'active' => request()->routeIs('vendedor.cupones.*')],
                ['label' => 'Reseñas', 'route' => route('vendedor.resenas.index'), 'active' => request()->routeIs('vendedor.resenas.*')],
                ['label' => 'Perfil de la tienda', 'route' => route('vendedor.apariencia.edit'), 'active' => request()->routeIs('vendedor.apariencia.*')],
            ],
            'Entregas y horario' => [
                ['label' => 'Zonas de entrega', 'route' => route('vendedor.zonas-entrega.index'), 'active' => request()->routeIs('vendedor.zonas-entrega.*')],
                ['label' => 'Horario y pausa', 'route' => route('vendedor.operacion.edit'), 'active' => request()->routeIs('vendedor.operacion.*')],
            ],
            'Mi dinero' => [
                ['label' => 'Ventas y pagos', 'route' => route('vendedor.pagos.index'), 'active' => request()->routeIs('vendedor.pagos.*')],
                /* Atlantia Store no paga comisión ni plan. */
                ...(\App\Support\TiendaAtlantia::es($user->vendor) ? [] : [
                    ['label' => 'Comisiones', 'route' => route('vendedor.comisiones.index'), 'active' => request()->routeIs('vendedor.comisiones.*')],
                    ['label' => 'Mi plan y publicidad', 'route' => route('vendedor.plan.index'), 'active' => request()->routeIs('vendedor.plan.*')],
                ]),
                ['label' => 'Reportes', 'route' => route('vendedor.reportes.index'), 'active' => request()->routeIs('vendedor.reportes.*')],
            ],
            'Facturación' => [
                ['label' => 'Datos de facturación', 'route' => route('vendedor.perfil-fiscal.edit'), 'active' => request()->routeIs('vendedor.perfil-fiscal.*')],
                ['label' => 'Facturas (DTE)', 'route' => route('vendedor.dte.index'), 'active' => request()->routeIs('vendedor.dte.*')],
            ],
            'Inteligencia' => [
                ['label' => 'Predicción de demanda', 'route' => route('vendedor.predicciones.index'), 'active' => request()->routeIs('vendedor.predicciones.*')],
                ['label' => 'Reabasto sugerido', 'route' => route('vendedor.reabasto.index'), 'active' => request()->routeIs('vendedor.reabasto.*')],
            ],
            'Cuenta' => [
                ['label' => 'Ayuda y soporte', 'route' => route('vendedor.soporte.index'), 'active' => request()->routeIs('vendedor.soporte.*')],
                ['label' => 'Seguridad de la cuenta', 'route' => route('cuenta.seguridad'), 'active' => request()->routeIs('cuenta.seguridad*')],
            ],
        ],
        $user?->hasAnyRole($internalRoles) => [
            'Operacion' => [
                ['label' => 'Vista general', 'route' => route('empleado.dashboard'), 'active' => request()->routeIs('empleado.dashboard')],
                ['label' => 'Transferencias', 'route' => route('empleado.transferencias.index'), 'active' => request()->routeIs('empleado.transferencias.*')],
                ['label' => 'Finanzas repartidor', 'route' => route('empleado.finanzas-repartidores.index'), 'active' => request()->routeIs('empleado.finanzas-repartidores.*')],
                ['label' => 'Soporte repartidor', 'route' => route('empleado.soporte-repartidores.index'), 'active' => request()->routeIs('empleado.soporte-repartidores.*')],
                ['label' => 'Mensajes', 'route' => route('empleado.mensajes.index'), 'active' => request()->routeIs('empleado.mensajes.*')],
                ['label' => 'Resenas', 'route' => route('empleado.resenas.index'), 'active' => request()->routeIs('empleado.resenas.*')],
            ],
        ],
        $user?->hasRole('repartidor') => [
            'Entregas' => [
                ['label' => 'Vista general', 'route' => route('repartidor.dashboard'), 'active' => request()->routeIs('repartidor.dashboard')],
                ['label' => 'Entregas', 'route' => route('repartidor.pedidos.index'), 'active' => request()->routeIs('repartidor.pedidos.*')],
                ['label' => 'Tiendas online', 'route' => route('repartidor.externas.index'), 'active' => request()->routeIs('repartidor.externas.*')],
                ['label' => 'Rutas', 'route' => route('repartidor.rutas.index'), 'active' => request()->routeIs('repartidor.rutas.*')],
                ['label' => 'Ganancias', 'route' => route('repartidor.ganancias.index'), 'active' => request()->routeIs('repartidor.ganancias.*')],
                ['label' => 'Historial', 'route' => route('repartidor.historial.index'), 'active' => request()->routeIs('repartidor.historial.*')],
                ['label' => 'Soporte', 'route' => route('repartidor.soporte.index'), 'active' => request()->routeIs('repartidor.soporte.*')],
                ['label' => 'Mi perfil', 'route' => route('repartidor.perfil.edit'), 'active' => request()->routeIs('repartidor.perfil.*')],
            ],
        ],
        default => [
            'Cuenta' => [
                ['label' => 'Mis pedidos', 'route' => route('cliente.pedidos.index'), 'active' => request()->routeIs('cliente.pedidos.*')],
                ['label' => 'Direcciones', 'route' => route('cliente.direcciones.index'), 'active' => request()->routeIs('cliente.direcciones.*')],
                ['label' => 'Saldo e invitaciones', 'route' => route('cliente.saldo.index'), 'active' => request()->routeIs('cliente.saldo.*')],
                ['label' => 'Wishlist', 'route' => route('cliente.wishlist.index'), 'active' => request()->routeIs('cliente.wishlist.*')],
            ],
        ],
    };
@endphp

<aside class="hidden border-r border-white/10 bg-gradient-to-b from-[#4d0006] via-[#3a0004] to-[#260002] text-white shadow-[12px_0_38px_rgba(51,5,9,0.28)] print:!hidden xl:block" aria-label="Navegacion lateral">
    <div id="panel-sidebar" class="sticky top-0 flex h-screen flex-col overflow-y-auto">
        <div class="border-b border-white/10 px-5 py-5">
            <a href="{{ $dashboardRoute }}" class="block rounded-2xl px-3 py-2 transition hover:bg-white/[0.05]">
                <img src="{{ asset($logoPath) }}" alt="Atlantia Store" class="mx-auto h-16 w-auto max-w-[12.5rem] object-contain drop-shadow-[0_0_18px_rgba(255,70,90,0.3)]">
                <div class="mt-4 flex items-center gap-3">
                    <span class="grid h-11 w-11 shrink-0 place-items-center rounded-full bg-[#6e000a] text-sm font-black text-white shadow-[0_12px_28px_rgba(30,3,6,0.28)]">
                        {{ $adminInitials }}
                    </span>
                    <div class="min-w-0">
                        <p class="truncate text-sm font-black leading-tight text-white">{{ $user?->vendor?->business_name ?? $adminName }}</p>
                        <p class="mt-0.5 truncate text-xs font-semibold text-white/60">{{ $adminEmail }}</p>
                        <span class="mt-1.5 inline-flex rounded-full bg-white px-2.5 py-0.5 text-[10px] font-black uppercase tracking-wide text-[#5e0008]">
                            {{ $panelLabel }}
                        </span>
                    </div>
                </div>
            </a>
        </div>

        <nav class="flex-1 space-y-3 px-4 py-5 text-sm" aria-label="Menu principal">
            @foreach ($sections as $title => $links)
                @php
                    $seccionActiva = collect($links)->contains('active', true);
                @endphp
                {{-- Cada sección se despliega; la de la página actual abre sola --}}
                <details class="group/seccion" data-menu-seccion="{{ $title }}" @if ($seccionActiva || count($sections) === 1) open @endif @if ($seccionActiva) data-menu-activa @endif>
                    <summary class="flex cursor-pointer list-none items-center gap-2 rounded-xl px-3 py-3 text-xs font-black uppercase tracking-wider text-white/55 transition hover:bg-white/[0.06] hover:text-white/80 group-open/seccion:bg-white/[0.05] group-open/seccion:text-white/85 [&::-webkit-details-marker]:hidden">
                        <span class="flex-1">{{ $title }}</span>
                        @if ($seccionActiva)
                            <span class="h-1.5 w-1.5 rounded-full bg-white/70 group-open/seccion:hidden" aria-hidden="true"></span>
                        @endif
                        <span class="rounded-full bg-white/[0.08] px-1.5 text-[10px] font-black text-white/50">{{ count($links) }}</span>
                        <svg class="h-3.5 w-3.5 transition-transform group-open/seccion:rotate-180" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m6 9 6 6 6-6"/></svg>
                    </summary>
                    <div class="mt-2 space-y-1 pb-1">
                        @foreach ($links as $link)
                            @php
                                $icon = $iconFor($link['label']);
                            @endphp
                            <a
                                href="{{ $link['route'] }}"
                                @if ($link['active']) aria-current="page" @endif
                                class="{{ $link['active'] ? 'bg-[#6e000a] text-white shadow-[inset_0_0_0_1px_rgba(255,255,255,0.08)]' : 'text-white/72 hover:bg-white/10 hover:text-white' }} group flex items-center gap-3 rounded-xl px-3 py-2.5 font-bold transition"
                            >
                                <span class="{{ $link['active'] ? 'bg-white/12 text-white' : 'bg-white/[0.07] text-white/72 group-hover:bg-white/14 group-hover:text-white' }} grid h-8 w-8 shrink-0 place-items-center rounded-lg transition">
                                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                        {!! $icons[$icon] ?? $icons['dot'] !!}
                                    </svg>
                                </span>
                                <span class="min-w-0 flex-1 truncate">{{ $link['label'] }}</span>
                                @if ($link['active'])
                                    <span class="h-2 w-2 rounded-full bg-white shadow-[0_0_12px_rgba(255,255,255,0.7)]"></span>
                                @endif
                            </a>
                        @endforeach
                    </div>
                </details>
            @endforeach
        </nav>
    </div>
</aside>

{{-- Recuerda las secciones abiertas y la posición del menú entre páginas --}}
<script @nonce>
    (() => {
        const menu = document.getElementById('panel-sidebar');
        if (!menu) return;
        const claveSecciones = 'atlantia.panelSidebarSecciones';
        const claveScroll = 'atlantia.panelSidebarScroll';
        const secciones = [...menu.querySelectorAll('[data-menu-seccion]')];
        try {
            const abiertas = JSON.parse(localStorage.getItem(claveSecciones) || 'null');
            if (Array.isArray(abiertas)) {
                secciones.forEach((s) => { if (abiertas.includes(s.dataset.menuSeccion) || s.hasAttribute('data-menu-activa')) s.open = true; });
            }
        } catch (e) {}
        secciones.forEach((s) => s.addEventListener('toggle', () => {
            try { localStorage.setItem(claveSecciones, JSON.stringify(secciones.filter((x) => x.open).map((x) => x.dataset.menuSeccion))); } catch (e) {}
        }));
        try {
            const guardado = sessionStorage.getItem(claveScroll);
            if (guardado !== null) menu.scrollTop = Number(guardado) || 0;
        } catch (e) {}
        const activo = menu.querySelector('[aria-current="page"]');
        if (activo) {
            const caja = menu.getBoundingClientRect();
            const item = activo.getBoundingClientRect();
            if (item.top < caja.top || item.bottom > caja.bottom) menu.scrollTop += item.top - caja.top - (caja.height / 2) + (item.height / 2);
        }
        const guardar = () => { try { sessionStorage.setItem(claveScroll, String(menu.scrollTop)); } catch (e) {} };
        menu.addEventListener('click', (e) => { if (e.target.closest('a')) guardar(); });
        window.addEventListener('pagehide', guardar);
    })();
</script>
