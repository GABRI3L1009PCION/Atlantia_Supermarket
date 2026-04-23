@php
    $logoPath = file_exists(public_path('images/logo.png')) ? 'images/logo.png' : 'images/atlantia-logo.svg';
    $user = auth()->user();
    $dashboardRoute = match (true) {
        $user?->hasAnyRole(['admin', 'super_admin']) => route('admin.dashboard'),
        $user?->hasRole('vendedor') => route('vendedor.dashboard'),
        $user?->hasRole('repartidor') => route('repartidor.dashboard'),
        $user?->hasRole('empleado') => route('empleado.dashboard'),
        default => route('home'),
    };

    $panelTitle = match (true) {
        $user?->hasRole('admin') => 'Administracion Atlantia',
        $user?->hasRole('vendedor') => 'Panel de vendedor',
        $user?->hasRole('repartidor') => 'Panel de repartidor',
        $user?->hasRole('empleado') => 'Panel operativo Atlantia',
        default => 'Atlantia Supermarket',
    };

    $quickLinks = match (true) {
        $user?->hasRole('admin') => [
            ['label' => 'Dashboard', 'route' => route('admin.dashboard'), 'active' => request()->routeIs('admin.dashboard')],
            ['label' => 'Usuarios', 'route' => route('admin.usuarios.index'), 'active' => request()->routeIs('admin.usuarios.*')],
            ['label' => 'Roles', 'route' => route('admin.roles-permisos.index'), 'active' => request()->routeIs('admin.roles-permisos.*')],
            ['label' => 'Vendedores', 'route' => route('admin.vendedores.index'), 'active' => request()->routeIs('admin.vendedores.*')],
            ['label' => 'Productos', 'route' => route('admin.productos.index'), 'active' => request()->routeIs('admin.productos.*')],
            ['label' => 'Pedidos', 'route' => route('admin.pedidos.index'), 'active' => request()->routeIs('admin.pedidos.*')],
            ['label' => 'Cupones', 'route' => route('admin.cupones.index'), 'active' => request()->routeIs('admin.cupones.*')],
            ['label' => 'DTE', 'route' => route('admin.dte.index'), 'active' => request()->routeIs('admin.dte.*')],
            ['label' => 'ML', 'route' => route('admin.ml.monitor'), 'active' => request()->routeIs('admin.ml.*')],
        ],
        $user?->hasRole('vendedor') => [
            ['label' => 'Dashboard', 'route' => route('vendedor.dashboard'), 'active' => request()->routeIs('vendedor.dashboard')],
            ['label' => 'Productos', 'route' => route('vendedor.productos.index'), 'active' => request()->routeIs('vendedor.productos.*')],
            ['label' => 'Inventario', 'route' => route('vendedor.inventario.index'), 'active' => request()->routeIs('vendedor.inventario.*')],
            ['label' => 'Pedidos', 'route' => route('vendedor.pedidos.index'), 'active' => request()->routeIs('vendedor.pedidos.*')],
            ['label' => 'DTE', 'route' => route('vendedor.dte.index'), 'active' => request()->routeIs('vendedor.dte.*')],
            ['label' => 'ML', 'route' => route('vendedor.predicciones.index'), 'active' => request()->routeIs('vendedor.predicciones.*')],
        ],
        $user?->hasRole('empleado') => [
            ['label' => 'Dashboard', 'route' => route('empleado.dashboard'), 'active' => request()->routeIs('empleado.dashboard')],
            ['label' => 'Transferencias', 'route' => route('empleado.transferencias.index'), 'active' => request()->routeIs('empleado.transferencias.*')],
            ['label' => 'Mensajes', 'route' => route('empleado.mensajes.index'), 'active' => request()->routeIs('empleado.mensajes.*')],
            ['label' => 'Resenas', 'route' => route('empleado.resenas.index'), 'active' => request()->routeIs('empleado.resenas.*')],
        ],
        $user?->hasRole('repartidor') => [
            ['label' => 'Dashboard', 'route' => route('repartidor.dashboard'), 'active' => request()->routeIs('repartidor.dashboard')],
            ['label' => 'Entregas', 'route' => route('repartidor.pedidos.index'), 'active' => request()->routeIs('repartidor.pedidos.*')],
            ['label' => 'Rutas', 'route' => route('repartidor.rutas.index'), 'active' => request()->routeIs('repartidor.rutas.*')],
        ],
        default => [],
    };
@endphp

<header class="sticky top-0 z-30 border-b border-atlantia-rose/15 bg-white/95 backdrop-blur">
    <div class="flex min-h-16 items-center justify-between gap-4 px-4 sm:px-6 lg:px-8">
        <div class="flex min-w-0 items-center gap-3">
            <a href="{{ $dashboardRoute }}" class="flex shrink-0 items-center xl:hidden" aria-label="Panel Atlantia">
                <img src="{{ asset($logoPath) }}" alt="Atlantia Supermarket" class="h-10 w-auto">
            </a>

            <div class="min-w-0">
                <p class="text-xs font-black uppercase tracking-wide text-atlantia-rose">Control central</p>
                <p class="truncate text-lg font-black text-atlantia-ink">{{ $panelTitle }}</p>
            </div>
        </div>

        <div class="flex items-center gap-3">
            <livewire:cliente.campanilla-notificaciones />
            <div class="hidden rounded-lg bg-atlantia-cream px-4 py-2 text-right md:block">
                <p class="text-sm font-black text-atlantia-ink">{{ $user?->name }}</p>
                <p class="text-xs text-atlantia-ink/60">{{ $user?->email }}</p>
            </div>

            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button
                    type="submit"
                    class="rounded-lg bg-atlantia-wine px-4 py-2 text-sm font-black text-white transition hover:bg-atlantia-wine-700"
                >
                    Cerrar sesion
                </button>
            </form>
        </div>
    </div>

    @if ($quickLinks !== [])
        <div class="border-t border-atlantia-rose/10 bg-[#fff8fb] xl:hidden">
            <div class="flex gap-2 overflow-x-auto px-4 py-3 sm:px-6 lg:px-8">
                @foreach ($quickLinks as $link)
                    <a
                        href="{{ $link['route'] }}"
                        class="{{ $link['active'] ? 'bg-atlantia-wine text-white' : 'bg-white text-atlantia-wine' }} shrink-0 rounded-lg border border-atlantia-rose/20 px-3 py-2 text-sm font-bold shadow-sm transition hover:border-atlantia-wine"
                    >
                        {{ $link['label'] }}
                    </a>
                @endforeach
            </div>
        </div>
    @endif
</header>
