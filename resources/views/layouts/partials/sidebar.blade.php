@php
    $user = auth()->user();
@endphp

<aside class="hidden w-64 shrink-0 lg:block" aria-label="Navegacion lateral">
    <div class="sticky top-6 rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
        <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Panel</p>

        <nav class="mt-4 space-y-1 text-sm">
            @if ($user?->hasRole('admin'))
                <x-ui.nav-link :href="route('admin.dashboard')" :active="request()->routeIs('admin.dashboard')">
                    Inicio
                </x-ui.nav-link>
                <x-ui.nav-link
                    :href="route('admin.vendedores.index')"
                    :active="request()->routeIs('admin.vendedores.*')"
                >
                    Vendedores
                </x-ui.nav-link>
                <x-ui.nav-link :href="route('admin.pedidos.index')" :active="request()->routeIs('admin.pedidos.*')">
                    Pedidos
                </x-ui.nav-link>
                <x-ui.nav-link
                    :href="route('admin.roles-permisos.index')"
                    :active="request()->routeIs('admin.roles-permisos.*')"
                >
                    Roles y permisos
                </x-ui.nav-link>
            @elseif ($user?->hasRole('vendedor'))
                <x-ui.nav-link :href="route('vendedor.dashboard')" :active="request()->routeIs('vendedor.dashboard')">
                    Inicio
                </x-ui.nav-link>
                <x-ui.nav-link
                    :href="route('vendedor.productos.index')"
                    :active="request()->routeIs('vendedor.productos.*')"
                >
                    Productos
                </x-ui.nav-link>
                <x-ui.nav-link
                    :href="route('vendedor.pedidos.index')"
                    :active="request()->routeIs('vendedor.pedidos.*')"
                >
                    Pedidos
                </x-ui.nav-link>
            @elseif ($user?->hasRole('repartidor'))
                <x-ui.nav-link
                    :href="route('repartidor.dashboard')"
                    :active="request()->routeIs('repartidor.dashboard')"
                >
                    Inicio
                </x-ui.nav-link>
                <x-ui.nav-link
                    :href="route('repartidor.pedidos.index')"
                    :active="request()->routeIs('repartidor.pedidos.*')"
                >
                    Entregas
                </x-ui.nav-link>
            @elseif ($user?->hasRole('empleado'))
                <x-ui.nav-link :href="route('empleado.dashboard')" :active="request()->routeIs('empleado.dashboard')">
                    Inicio
                </x-ui.nav-link>
                <x-ui.nav-link
                    :href="route('empleado.transferencias.index')"
                    :active="request()->routeIs('empleado.transferencias.*')"
                >
                    Transferencias
                </x-ui.nav-link>
            @else
                <x-ui.nav-link :href="route('cliente.pedidos.index')" :active="request()->routeIs('cliente.pedidos.*')">
                    Mis pedidos
                </x-ui.nav-link>
                <x-ui.nav-link
                    :href="route('cliente.direcciones.index')"
                    :active="request()->routeIs('cliente.direcciones.*')"
                >
                    Direcciones
                </x-ui.nav-link>
            @endif
        </nav>
    </div>
</aside>
