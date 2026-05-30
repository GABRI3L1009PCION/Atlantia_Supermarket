@php
    $logoPath = file_exists(public_path('images/logo.png')) ? 'images/logo.png' : 'images/atlantia-logo.svg';
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
        $user?->hasRole('admin') => 'Administracion Atlantia',
        $user?->hasRole('vendedor') => 'Panel de vendedor',
        $user?->hasRole('repartidor') => 'Panel de repartidor',
        $user?->hasRole('bodeguero') => 'Panel de bodega',
        $user?->hasRole('soporte') => 'Panel de soporte',
        $user?->hasRole('contabilidad_finanzas') => 'Panel contable y financiero',
        $user?->hasRole('supervisor_logistica') => 'Panel de supervision logistica',
        $user?->hasRole('empleado') => 'Panel operativo Atlantia',
        default => 'Atlantia Supermarket',
    };

    $quickLinks = match (true) {
        $user?->hasRole('admin') => [
            ['label' => 'Vista general', 'route' => route('admin.dashboard'), 'active' => request()->routeIs('admin.dashboard')],
            ['label' => 'Usuarios', 'route' => route('admin.usuarios.index'), 'active' => request()->routeIs('admin.usuarios.*')],
            ['label' => 'Roles', 'route' => route('admin.roles-permisos.index'), 'active' => request()->routeIs('admin.roles-permisos.*')],
            ['label' => 'Vendedores', 'route' => route('admin.vendedores.index'), 'active' => request()->routeIs('admin.vendedores.*')],
            ['label' => 'Productos', 'route' => route('admin.productos.index'), 'active' => request()->routeIs('admin.productos.*')],
            ['label' => 'Pedidos', 'route' => route('admin.pedidos.index'), 'active' => request()->routeIs('admin.pedidos.*')],
            ['label' => 'Cupones', 'route' => route('admin.cupones.index'), 'active' => request()->routeIs('admin.cupones.*')],
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
            ['label' => 'Mensajes', 'route' => route('empleado.mensajes.index'), 'active' => request()->routeIs('empleado.mensajes.*')],
            ['label' => 'Resenas', 'route' => route('empleado.resenas.index'), 'active' => request()->routeIs('empleado.resenas.*')],
        ],
        $user?->hasRole('repartidor') => [
            ['label' => 'Vista general', 'route' => route('repartidor.dashboard'), 'active' => request()->routeIs('repartidor.dashboard')],
            ['label' => 'Entregas', 'route' => route('repartidor.pedidos.index'), 'active' => request()->routeIs('repartidor.pedidos.*')],
            ['label' => 'Rutas', 'route' => route('repartidor.rutas.index'), 'active' => request()->routeIs('repartidor.rutas.*')],
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
                ['label' => 'Zonas de entrega', 'route' => route('admin.zonas-entrega.index'), 'active' => request()->routeIs('admin.zonas-entrega.*')],
            ],
            'Finanzas y control' => [
                ['label' => 'Comisiones', 'route' => route('admin.comisiones.index'), 'active' => request()->routeIs('admin.comisiones.*')],
                ['label' => 'Nominas', 'route' => route('admin.nominas.index'), 'active' => request()->routeIs('admin.nominas.*')],
                ['label' => 'Cupones', 'route' => route('admin.cupones.index'), 'active' => request()->routeIs('admin.cupones.*')],
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
            'Tienda' => [
                ['label' => 'Vista general', 'route' => route('vendedor.dashboard'), 'active' => request()->routeIs('vendedor.dashboard')],
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
        $user?->hasAnyRole($internalRoles) => [
            'Operacion' => [
                ['label' => 'Vista general', 'route' => route('empleado.dashboard'), 'active' => request()->routeIs('empleado.dashboard')],
                ['label' => 'Transferencias', 'route' => route('empleado.transferencias.index'), 'active' => request()->routeIs('empleado.transferencias.*')],
                ['label' => 'Mensajes', 'route' => route('empleado.mensajes.index'), 'active' => request()->routeIs('empleado.mensajes.*')],
                ['label' => 'Resenas', 'route' => route('empleado.resenas.index'), 'active' => request()->routeIs('empleado.resenas.*')],
            ],
        ],
        $user?->hasRole('repartidor') => [
            'Entregas' => [
                ['label' => 'Vista general', 'route' => route('repartidor.dashboard'), 'active' => request()->routeIs('repartidor.dashboard')],
                ['label' => 'Entregas', 'route' => route('repartidor.pedidos.index'), 'active' => request()->routeIs('repartidor.pedidos.*')],
                ['label' => 'Rutas', 'route' => route('repartidor.rutas.index'), 'active' => request()->routeIs('repartidor.rutas.*')],
            ],
        ],
        default => [],
    };
@endphp

<header class="sticky top-0 z-50 border-b border-atlantia-rose/15 bg-white shadow-sm">
    <div class="flex h-14 items-center justify-between gap-2 px-4 sm:h-16 sm:gap-4 sm:px-6 lg:px-8">

        {{-- Izquierda: logo + título --}}
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
                                <p class="text-[11px] font-black uppercase tracking-[0.18em] text-atlantia-rose">Menu</p>
                                <p class="text-lg font-black text-atlantia-ink">{{ $panelTitle }}</p>
                            </div>
                            <span class="max-w-32 truncate rounded-full bg-atlantia-blush px-3 py-1 text-xs font-black text-atlantia-wine">{{ $user?->name }}</span>
                        </div>

                        <nav class="mt-4 space-y-4" aria-label="Menu administrativo movil">
                            @foreach ($mobileSections as $sectionTitle => $links)
                                <section>
                                    <p class="px-1 text-[11px] font-black uppercase tracking-[0.18em] text-atlantia-ink/45">{{ $sectionTitle }}</p>
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
            <div class="min-w-0 hidden sm:block">
                <p class="text-[10px] font-black uppercase tracking-widest text-atlantia-rose/80">Atlantia</p>
                <p class="truncate text-sm font-black text-atlantia-ink leading-tight">{{ $panelTitle }}</p>
            </div>
        </div>

        {{-- Derecha: notificaciones + usuario + cerrar sesión --}}
        <div class="flex shrink-0 items-center gap-2">
            <livewire:cliente.campanilla-notificaciones />

            {{-- Nombre del usuario (solo md+) --}}
            <div class="hidden md:flex flex-col items-end leading-tight">
                <p class="text-sm font-black text-atlantia-ink">{{ $user?->name }}</p>
                <p class="text-xs text-atlantia-ink/50">{{ $user?->email }}</p>
            </div>

            {{-- Cerrar sesión --}}
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                {{-- Móvil: solo icono --}}
                <button type="submit"
                    class="flex items-center gap-1.5 rounded-lg bg-atlantia-wine px-3 py-2 text-xs font-black text-white transition active:scale-95 sm:px-4 sm:text-sm">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2h6a2 2 0 012 2v1"/>
                    </svg>
                    <span class="hidden sm:inline">Salir</span>
                </button>
            </form>
        </div>
    </div>

    {{-- Barra de navegación rápida (solo roles con quickLinks, solo < xl) --}}
    @if ($quickLinks !== [])
        <div class="border-t border-atlantia-rose/10 bg-[#fff8fb] xl:hidden">
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
