@php
    $user = auth()->user();
    $panelLabel = match (true) {
        $user?->hasRole('admin') => 'ADMIN',
        $user?->hasRole('vendedor') => 'VENDEDOR',
        $user?->hasRole('empleado') => 'EMPLEADO',
        $user?->hasRole('repartidor') => 'REPARTO',
        default => 'CUENTA',
    };
    $dashboardRoute = match (true) {
        $user?->hasRole('admin') => route('admin.dashboard'),
        $user?->hasRole('vendedor') => route('vendedor.dashboard'),
        $user?->hasRole('empleado') => route('empleado.dashboard'),
        $user?->hasRole('repartidor') => route('repartidor.dashboard'),
        default => route('home'),
    };

    $sections = match (true) {
        $user?->hasRole('admin') => [
            'Gestion' => [
                ['label' => 'Dashboard', 'route' => route('admin.dashboard'), 'active' => request()->routeIs('admin.dashboard')],
                ['label' => 'Usuarios', 'route' => route('admin.usuarios.index'), 'active' => request()->routeIs('admin.usuarios.*')],
                ['label' => 'Roles y permisos', 'route' => route('admin.roles-permisos.index'), 'active' => request()->routeIs('admin.roles-permisos.*')],
                ['label' => 'Vendedores', 'route' => route('admin.vendedores.index'), 'active' => request()->routeIs('admin.vendedores.*')],
                ['label' => 'Empleados', 'route' => route('admin.empleados.index'), 'active' => request()->routeIs('admin.empleados.*')],
                ['label' => 'Repartidores', 'route' => route('admin.repartidores.index'), 'active' => request()->routeIs('admin.repartidores.*')],
            ],
            'Catalogo' => [
                ['label' => 'Productos', 'route' => route('admin.productos.index'), 'active' => request()->routeIs('admin.productos.*')],
                ['label' => 'Categorias', 'route' => route('admin.categorias.index'), 'active' => request()->routeIs('admin.categorias.*')],
                ['label' => 'Pedidos', 'route' => route('admin.pedidos.index'), 'active' => request()->routeIs('admin.pedidos.*')],
                ['label' => 'Zonas de entrega', 'route' => route('admin.zonas-entrega.index'), 'active' => request()->routeIs('admin.zonas-entrega.*')],
            ],
            'Finanzas y ML' => [
                ['label' => 'Comisiones', 'route' => route('admin.comisiones.index'), 'active' => request()->routeIs('admin.comisiones.*')],
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
            'Tienda' => [
                ['label' => 'Dashboard', 'route' => route('vendedor.dashboard'), 'active' => request()->routeIs('vendedor.dashboard')],
                ['label' => 'Productos', 'route' => route('vendedor.productos.index'), 'active' => request()->routeIs('vendedor.productos.*')],
                ['label' => 'Inventario', 'route' => route('vendedor.inventario.index'), 'active' => request()->routeIs('vendedor.inventario.*')],
                ['label' => 'Pedidos', 'route' => route('vendedor.pedidos.index'), 'active' => request()->routeIs('vendedor.pedidos.*')],
                ['label' => 'Zonas de entrega', 'route' => route('vendedor.zonas-entrega.index'), 'active' => request()->routeIs('vendedor.zonas-entrega.*')],
                ['label' => 'Resenas', 'route' => route('vendedor.resenas.index'), 'active' => request()->routeIs('vendedor.resenas.*')],
            ],
            'Fiscal y ML' => [
                ['label' => 'Perfil fiscal', 'route' => route('vendedor.perfil-fiscal.edit'), 'active' => request()->routeIs('vendedor.perfil-fiscal.*')],
                ['label' => 'DTE', 'route' => route('vendedor.dte.index'), 'active' => request()->routeIs('vendedor.dte.*')],
                ['label' => 'Comisiones', 'route' => route('vendedor.comisiones.index'), 'active' => request()->routeIs('vendedor.comisiones.*')],
                ['label' => 'Reportes', 'route' => route('vendedor.reportes.index'), 'active' => request()->routeIs('vendedor.reportes.*')],
                ['label' => 'Prediccion demanda', 'route' => route('vendedor.predicciones.index'), 'active' => request()->routeIs('vendedor.predicciones.*')],
                ['label' => 'Reabasto ML', 'route' => route('vendedor.reabasto.index'), 'active' => request()->routeIs('vendedor.reabasto.*')],
            ],
        ],
        $user?->hasRole('empleado') => [
            'Operacion' => [
                ['label' => 'Dashboard', 'route' => route('empleado.dashboard'), 'active' => request()->routeIs('empleado.dashboard')],
                ['label' => 'Transferencias', 'route' => route('empleado.transferencias.index'), 'active' => request()->routeIs('empleado.transferencias.*')],
                ['label' => 'Mensajes', 'route' => route('empleado.mensajes.index'), 'active' => request()->routeIs('empleado.mensajes.*')],
                ['label' => 'Resenas', 'route' => route('empleado.resenas.index'), 'active' => request()->routeIs('empleado.resenas.*')],
            ],
        ],
        $user?->hasRole('repartidor') => [
            'Entregas' => [
                ['label' => 'Dashboard', 'route' => route('repartidor.dashboard'), 'active' => request()->routeIs('repartidor.dashboard')],
                ['label' => 'Entregas', 'route' => route('repartidor.pedidos.index'), 'active' => request()->routeIs('repartidor.pedidos.*')],
                ['label' => 'Rutas', 'route' => route('repartidor.rutas.index'), 'active' => request()->routeIs('repartidor.rutas.*')],
            ],
        ],
        default => [
            'Cuenta' => [
                ['label' => 'Mis pedidos', 'route' => route('cliente.pedidos.index'), 'active' => request()->routeIs('cliente.pedidos.*')],
                ['label' => 'Direcciones', 'route' => route('cliente.direcciones.index'), 'active' => request()->routeIs('cliente.direcciones.*')],
            ],
        ],
    };
@endphp

<aside class="hidden border-r border-atlantia-rose/15 bg-white xl:block" aria-label="Navegacion lateral">
    <div class="sticky top-0 flex h-screen flex-col overflow-y-auto">
        <div class="border-b border-atlantia-rose/15 px-6 py-5">
            <a href="{{ $dashboardRoute }}" class="flex items-center gap-3">
                <span class="grid h-10 w-10 place-items-center rounded-lg bg-atlantia-wine text-lg font-black text-white">
                    A
                </span>
                <div class="min-w-0">
                    <p class="truncate text-base font-black text-atlantia-ink">Atlantia</p>
                    <p class="text-xs font-black uppercase tracking-wide text-atlantia-rose">{{ $panelLabel }}</p>
                </div>
            </a>
        </div>

        <nav class="flex-1 space-y-6 px-4 py-5 text-sm" aria-label="Menu principal">
            @foreach ($sections as $title => $links)
                <div>
                    <p class="px-2 text-xs font-black uppercase tracking-wider text-atlantia-ink/38">{{ $title }}</p>
                    <div class="mt-2 space-y-1">
                        @foreach ($links as $link)
                            <a
                                href="{{ $link['route'] }}"
                                class="{{ $link['active'] ? 'bg-atlantia-wine text-white' : 'text-atlantia-ink/72 hover:bg-atlantia-blush hover:text-atlantia-wine' }} block rounded-lg px-3 py-2.5 font-bold transition"
                            >
                                {{ $link['label'] }}
                            </a>
                        @endforeach
                    </div>
                </div>
            @endforeach
        </nav>
    </div>
</aside>
