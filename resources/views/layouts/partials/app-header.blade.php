@php
    $logoPath = collect(['images/logo-atlantia-delivery.png', 'images/logo.png', 'images/atlantia-logo.svg'])
        ->first(fn (string $ruta): bool => file_exists(public_path($ruta))) ?? 'images/atlantia-logo.svg';
    $user = auth()->user();
    $internalRoles = ['empleado', 'bodeguero', 'soporte', 'contabilidad_finanzas', 'supervisor_logistica'];
    $dashboardRoute = match (true) {
        $user?->hasAnyRole(['admin', 'super_admin']) => route('admin.dashboard'),
        $user?->hasRole('vendedor') => route('vendedor.dashboard'),
        $user?->hasRole('repartidor') => route('repartidor.dashboard'),
        $user?->hasAnyRole($internalRoles) => route('empleado.dashboard'),
        default => route('home'),
    };

    $panelTitle = match (true) {
        $user?->hasRole('admin') => 'Administracion Atlantia Store',
        $user?->hasRole('vendedor') => 'Panel de vendedor',
        $user?->hasRole('repartidor') => 'Panel de repartidor',
        $user?->hasRole('bodeguero') => 'Panel de bodega',
        $user?->hasRole('soporte') => 'Panel de soporte',
        $user?->hasRole('contabilidad_finanzas') => 'Panel contable y financiero',
        $user?->hasRole('supervisor_logistica') => 'Panel de supervision logistica',
        $user?->hasRole('empleado') => 'Panel operativo Atlantia',
        default => 'Atlantia Store',
    };

    $quickLinks = match (true) {
        $user?->hasRole('admin') => [
            ['label' => 'Vista general', 'route' => route('admin.dashboard'), 'active' => request()->routeIs('admin.dashboard')],
            ['label' => 'Usuarios', 'route' => route('admin.usuarios.index'), 'active' => request()->routeIs('admin.usuarios.*')],
            ['label' => 'Roles', 'route' => route('admin.roles-permisos.index'), 'active' => request()->routeIs('admin.roles-permisos.*')],
            ['label' => 'Vendedores', 'route' => route('admin.vendedores.index'), 'active' => request()->routeIs('admin.vendedores.*')],
            ['label' => 'Productos', 'route' => route('admin.productos.index'), 'active' => request()->routeIs('admin.productos.*')],
            ['label' => 'Pedidos', 'route' => route('admin.pedidos.index'), 'active' => request()->routeIs('admin.pedidos.*')],
            ['label' => 'Entregas externas', 'route' => route('admin.entregas-externas.index'), 'active' => request()->routeIs('admin.entregas-externas.*')],
            ['label' => 'Cupones', 'route' => route('admin.cupones.index'), 'active' => request()->routeIs('admin.cupones.*')],
            ['label' => 'Promociones', 'route' => route('admin.promociones.index'), 'active' => request()->routeIs('admin.promociones.*')],
            ['label' => 'Planes y publicidad', 'route' => route('admin.servicios.index'), 'active' => request()->routeIs('admin.servicios.*')],
            ['label' => 'Datos de mercado', 'route' => route('admin.datos-mercado.index'), 'active' => request()->routeIs('admin.datos-mercado.*')],
            ['label' => 'Nominas', 'route' => route('admin.nominas.index'), 'active' => request()->routeIs('admin.nominas.*')],
            ['label' => 'DTE', 'route' => route('admin.dte.index'), 'active' => request()->routeIs('admin.dte.*')],
            ['label' => 'ML', 'route' => route('admin.ml.monitor'), 'active' => request()->routeIs('admin.ml.*')],
        ],
        $user?->hasRole('vendedor') => [
            ['label' => 'Vista general', 'route' => route('vendedor.dashboard'), 'active' => request()->routeIs('vendedor.dashboard')],
            ['label' => 'Productos', 'route' => route('vendedor.productos.index'), 'active' => request()->routeIs('vendedor.productos.*')],
            ['label' => 'Inventario', 'route' => route('vendedor.inventario.index'), 'active' => request()->routeIs('vendedor.inventario.*')],
            ['label' => 'Pedidos', 'route' => route('vendedor.pedidos.index'), 'active' => request()->routeIs('vendedor.pedidos.*')],
            ['label' => 'DTE', 'route' => route('vendedor.dte.index'), 'active' => request()->routeIs('vendedor.dte.*')],
            ['label' => 'ML', 'route' => route('vendedor.predicciones.index'), 'active' => request()->routeIs('vendedor.predicciones.*')],
        ],
        $user?->hasAnyRole($internalRoles) => [
            ['label' => 'Vista general', 'route' => route('empleado.dashboard'), 'active' => request()->routeIs('empleado.dashboard')],
            ['label' => 'Transferencias', 'route' => route('empleado.transferencias.index'), 'active' => request()->routeIs('empleado.transferencias.*')],
            ['label' => 'Finanzas repartidor', 'route' => route('empleado.finanzas-repartidores.index'), 'active' => request()->routeIs('empleado.finanzas-repartidores.*')],
            ['label' => 'Soporte repartidor', 'route' => route('empleado.soporte-repartidores.index'), 'active' => request()->routeIs('empleado.soporte-repartidores.*')],
            ['label' => 'Mensajes', 'route' => route('empleado.mensajes.index'), 'active' => request()->routeIs('empleado.mensajes.*')],
            ['label' => 'Resenas', 'route' => route('empleado.resenas.index'), 'active' => request()->routeIs('empleado.resenas.*')],
        ],
        $user?->hasRole('repartidor') => [
            ['label' => 'Vista general', 'route' => route('repartidor.dashboard'), 'active' => request()->routeIs('repartidor.dashboard')],
            ['label' => 'Entregas', 'route' => route('repartidor.pedidos.index'), 'active' => request()->routeIs('repartidor.pedidos.*')],
            ['label' => 'Tiendas online', 'route' => route('repartidor.externas.index'), 'active' => request()->routeIs('repartidor.externas.*')],
            ['label' => 'Rutas', 'route' => route('repartidor.rutas.index'), 'active' => request()->routeIs('repartidor.rutas.*')],
            ['label' => 'Ganancias', 'route' => route('repartidor.ganancias.index'), 'active' => request()->routeIs('repartidor.ganancias.*')],
            ['label' => 'Soporte', 'route' => route('repartidor.soporte.index'), 'active' => request()->routeIs('repartidor.soporte.*')],
        ],
        default => [],
    };

    $mobileSections = match (true) {
        $user?->hasRole('admin') => [
            'Gestion' => [
                ['label' => 'Vista general', 'route' => route('admin.dashboard'), 'active' => request()->routeIs('admin.dashboard')],
                ['label' => 'Usuarios', 'route' => route('admin.usuarios.index'), 'active' => request()->routeIs('admin.usuarios.*')],
                ['label' => 'Roles y permisos', 'route' => route('admin.roles-permisos.index'), 'active' => request()->routeIs('admin.roles-permisos.*')],
                ['label' => 'Vendedores', 'route' => route('admin.vendedores.index'), 'active' => request()->routeIs('admin.vendedores.*')],
                ['label' => 'Empleados', 'route' => route('admin.empleados.index'), 'active' => request()->routeIs('admin.empleados.*')],
                ['label' => 'Repartidores', 'route' => route('admin.repartidores.index'), 'active' => request()->routeIs('admin.repartidores.*')],
            ],
            'Catalogo y pedidos' => [
                ['label' => 'Productos', 'route' => route('admin.productos.index'), 'active' => request()->routeIs('admin.productos.*')],
                ['label' => 'Categorias', 'route' => route('admin.categorias.index'), 'active' => request()->routeIs('admin.categorias.*')],
                ['label' => 'Banners hero', 'route' => route('admin.hero-banners.index'), 'active' => request()->routeIs('admin.hero-banners.*')],
                ['label' => 'Pedidos', 'route' => route('admin.pedidos.index'), 'active' => request()->routeIs('admin.pedidos.*')],
                ['label' => 'Entregas externas', 'route' => route('admin.entregas-externas.index'), 'active' => request()->routeIs('admin.entregas-externas.*')],
                ['label' => 'Atlantia Store', 'route' => route('admin.tienda-atlantia.index'), 'active' => request()->routeIs('admin.tienda-atlantia.*')],
                ['label' => 'Mensajes', 'route' => route('admin.mensajes.index'), 'active' => request()->routeIs('admin.mensajes.*')],
                ['label' => 'Denuncias', 'route' => route('admin.denuncias.index'), 'active' => request()->routeIs('admin.denuncias.*')],
                ['label' => 'Zonas de entrega', 'route' => route('admin.zonas-entrega.index'), 'active' => request()->routeIs('admin.zonas-entrega.*')],
            ],
            'Finanzas y control' => [
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
            ],
            'Inteligencia' => [
                ['label' => 'Monitor ML', 'route' => route('admin.ml.monitor'), 'active' => request()->routeIs('admin.ml.monitor')],
                ['label' => 'Reentrenamiento ML', 'route' => route('admin.ml.reentrenamiento.index'), 'active' => request()->routeIs('admin.ml.reentrenamiento.*')],
            ],
        ],
        $user?->hasRole('vendedor') => [
            'Mi tienda' => [
                ['label' => 'Vista general', 'route' => route('vendedor.dashboard'), 'active' => request()->routeIs('vendedor.dashboard')],
                ['label' => 'Pedidos', 'route' => route('vendedor.pedidos.index'), 'active' => request()->routeIs('vendedor.pedidos.*')],
                ['label' => 'Productos', 'route' => route('vendedor.productos.index'), 'active' => request()->routeIs('vendedor.productos.*')],
                ['label' => 'Inventario', 'route' => route('vendedor.inventario.index'), 'active' => request()->routeIs('vendedor.inventario.*')],
                ['label' => 'Promociones', 'route' => route('vendedor.promociones.index'), 'active' => request()->routeIs('vendedor.promociones.*')],
                ['label' => 'Reseñas', 'route' => route('vendedor.resenas.index'), 'active' => request()->routeIs('vendedor.resenas.*')],
            ],
            'Entregas y horario' => [
                ['label' => 'Zonas de entrega', 'route' => route('vendedor.zonas-entrega.index'), 'active' => request()->routeIs('vendedor.zonas-entrega.*')],
                ['label' => 'Horario y pausa', 'route' => route('vendedor.operacion.edit'), 'active' => request()->routeIs('vendedor.operacion.*')],
            ],
            'Mi dinero' => [
                ['label' => 'Ventas y pagos', 'route' => route('vendedor.pagos.index'), 'active' => request()->routeIs('vendedor.pagos.*')],
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
            ],
        ],
        default => [],
    };

    /* Ubicación actual para la ruta de navegación (sección / página). */
    $seccionActual = null;
    $paginaActual = null;
    foreach ($mobileSections as $tituloSeccion => $enlaces) {
        foreach ($enlaces as $enlace) {
            if ($enlace['active']) {
                [$seccionActual, $paginaActual] = [$tituloSeccion, $enlace['label']];
            }
        }
    }
    $panelCorto = match (true) {
        $user?->hasRole('vendedor') => 'Mi panel',
        $user?->hasRole('repartidor') => 'Reparto',
        default => 'Panel',
    };

    /* Página pública de la tienda del vendedor. */
    $miTiendaUrl = $user?->hasRole('vendedor') && $user->vendor?->slug ? route('comercios.show', $user->vendor->slug) : null;

    [$etiquetaRol, $opcionesCuenta] = match (true) {
        $user?->hasRole('vendedor') => ['Vendedor', array_values(array_filter([
            $miTiendaUrl ? ['texto' => 'Ver mi tienda', 'url' => $miTiendaUrl, 'nueva' => true] : null,
            ['texto' => 'Horario y pausa', 'url' => route('vendedor.operacion.edit')],
            \App\Support\TiendaAtlantia::es($user->vendor) ? null : ['texto' => 'Mi plan y publicidad', 'url' => route('vendedor.plan.index')],
            ['texto' => 'Datos de facturación', 'url' => route('vendedor.perfil-fiscal.edit')],
            ['texto' => 'Seguridad de la cuenta', 'url' => route('cuenta.seguridad')],
        ]))],
        $user?->hasRole('repartidor') => ['Repartidor', [['texto' => 'Mi perfil', 'url' => route('repartidor.perfil.edit')], ['texto' => 'Mis ganancias', 'url' => route('repartidor.ganancias.index')], ['texto' => 'Seguridad de la cuenta', 'url' => route('cuenta.seguridad')]]],
        $user?->hasRole('admin') => ['Administrador', [['texto' => 'Ver la tienda', 'url' => route('home'), 'nueva' => true], ['texto' => 'Seguridad de la cuenta', 'url' => route('cuenta.seguridad')]]],
        default => [$panelTitle, [['texto' => 'Ver la tienda', 'url' => route('home'), 'nueva' => true]]],
    };
@endphp

<header class="{{ request()->routeIs('repartidor.dashboard', 'repartidor.pedidos.show', 'repartidor.ganancias.index', 'repartidor.historial.index') ? 'hidden md:sticky md:block' : 'sticky' }} top-0 z-50 border-b border-atlantia-rose/15 bg-white shadow-sm">
    <div class="flex h-14 items-center justify-between gap-2 px-4 sm:h-16 sm:gap-4 sm:px-6 lg:px-8">

        {{-- Izquierda: logo + titulo --}}
        <div class="flex min-w-0 items-center gap-2.5">
            @if ($mobileSections !== [])
                <details class="group relative xl:hidden">
                    <summary class="inline-flex h-10 w-10 cursor-pointer list-none items-center justify-center rounded-xl border border-atlantia-rose/25 bg-white text-atlantia-wine shadow-sm transition group-open:bg-atlantia-blush [&::-webkit-details-marker]:hidden" aria-label="Abrir menu administrativo">
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                            <path d="M4 7H20M4 12H20M4 17H20" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                        </svg>
                    </summary>

                    <div class="fixed inset-x-3 top-[4.25rem] z-[80] max-h-[calc(100vh-5.5rem)] overflow-y-auto rounded-2xl border border-atlantia-rose/20 bg-white p-4 text-atlantia-ink shadow-2xl">
                        <div class="flex items-center justify-between gap-3 border-b border-atlantia-rose/10 pb-3">
                            <div>
                                <p class="text-[11px] font-black uppercase text-atlantia-rose">Menu</p>
                                <p class="text-lg font-black text-atlantia-ink">{{ $panelTitle }}</p>
                            </div>
                            <span class="max-w-32 truncate rounded-full bg-atlantia-blush px-3 py-1 text-xs font-black text-atlantia-wine">{{ $user?->name }}</span>
                        </div>

                        <nav class="mt-4 space-y-4" aria-label="Menu administrativo movil">
                            @foreach ($mobileSections as $sectionTitle => $links)
                                <section>
                                    <p class="px-1 text-[11px] font-black uppercase text-atlantia-ink/45">{{ $sectionTitle }}</p>
                                    <div class="mt-2 grid gap-2 sm:grid-cols-2">
                                        @foreach ($links as $link)
                                            <a href="{{ $link['route'] }}" class="{{ $link['active'] ? 'border-atlantia-wine bg-atlantia-wine text-white' : 'border-atlantia-rose/20 bg-white text-atlantia-ink hover:bg-atlantia-blush hover:text-atlantia-wine' }} flex min-h-11 items-center justify-between rounded-xl border px-3 text-sm font-black transition">
                                                <span>{{ $link['label'] }}</span>
                                                <svg class="h-4 w-4 opacity-65" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                                    <path d="M9 5L16 12L9 19" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                                                </svg>
                                            </a>
                                        @endforeach
                                    </div>
                                </section>
                            @endforeach
                        </nav>
                    </div>
                </details>
            @endif

            <a href="{{ $dashboardRoute }}" class="shrink-0 xl:hidden" aria-label="Inicio">
                <img src="{{ asset($logoPath) }}" alt="Atlantia" class="h-8 w-auto sm:h-9">
            </a>
            <nav class="hidden min-w-0 items-center gap-2 text-sm sm:flex" aria-label="Ubicación">
                <a href="{{ $dashboardRoute }}" class="shrink-0 font-bold text-atlantia-ink/45 transition hover:text-atlantia-wine">{{ $panelCorto }}</a>
                @if ($seccionActual)
                    <span class="text-atlantia-ink/25" aria-hidden="true">/</span>
                    <span class="hidden shrink-0 font-bold text-atlantia-ink/45 md:inline">{{ $seccionActual }}</span>
                    <span class="hidden text-atlantia-ink/25 md:inline" aria-hidden="true">/</span>
                    <span class="truncate font-black text-atlantia-ink">{{ $paginaActual }}</span>
                @endif
            </nav>
        </div>

        {{-- Derecha: ver mi tienda, notificaciones y cuenta --}}
        <div class="flex shrink-0 items-center gap-2">
            @if ($miTiendaUrl)
                <a href="{{ $miTiendaUrl }}" target="_blank" rel="noopener" class="hidden items-center gap-1.5 rounded-lg px-3 py-1.5 text-sm font-bold text-atlantia-ink/65 transition hover:bg-atlantia-blush/50 hover:text-atlantia-wine sm:inline-flex" title="Así ven tu tienda los clientes">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 10h16l-1-5H5ZM6 10v10h12V10M9 20v-6h6v6"/></svg>
                    Ver mi tienda
                </a>
            @endif

            @include('layouts.partials.panel-cuenta', ['etiquetaRol' => $etiquetaRol, 'opcionesCuenta' => $opcionesCuenta])
        </div>
    </div>

    {{-- Barra de navegacion rapida (solo roles con quickLinks, solo < xl) --}}
    @if ($quickLinks !== [])
        <div class="border-t border-atlantia-rose/10 bg-[#fff8f8] xl:hidden">
            <div class="flex gap-2 overflow-x-auto px-4 py-2.5 sm:px-6 lg:px-8"
                 style="-webkit-overflow-scrolling: touch; scrollbar-width: none;">
                @foreach ($quickLinks as $link)
                    <a
                        href="{{ $link['route'] }}"
                        class="{{ $link['active']
                            ? 'bg-atlantia-wine text-white border-atlantia-wine shadow-sm'
                            : 'bg-white text-atlantia-ink border-atlantia-rose/25 hover:border-atlantia-wine hover:text-atlantia-wine' }}
                            shrink-0 whitespace-nowrap rounded-xl border px-4 py-2 text-sm font-bold transition"
                    >
                        {{ $link['label'] }}
                    </a>
                @endforeach
            </div>
        </div>
    @endif
</header>
