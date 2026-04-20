@php
    $user = auth()->user();
    $logoPath = file_exists(public_path('images/logo.png')) ? 'images/logo.png' : 'images/atlantia-logo.svg';
@endphp

<aside class="hidden w-72 shrink-0 xl:block" aria-label="Navegacion lateral">
    <div class="sticky top-6 overflow-hidden rounded-xl border border-atlantia-rose/20 bg-gradient-to-b from-[#eef7f1] via-[#f7fbf8] to-white shadow-sm">
        <div class="border-b border-atlantia-rose/10 px-6 py-6 text-center">
            <img
                src="{{ asset($logoPath) }}"
                alt="Atlantia Supermarket"
                class="mx-auto h-16 w-auto"
            >
            <h2 class="mt-4 text-2xl font-bold text-atlantia-wine">Supermercado Atlantia</h2>
            <p class="mt-1 text-sm text-atlantia-ink/55">Panel administrativo</p>

            <div class="mt-5 rounded-lg bg-white/80 px-4 py-3 text-left shadow-sm">
                <p class="text-xs font-semibold uppercase text-atlantia-rose">Sesion activa</p>
                <p class="mt-1 text-sm font-bold text-atlantia-ink">{{ $user?->name }}</p>
                <p class="text-sm text-atlantia-ink/60">{{ $user?->email }}</p>
            </div>
        </div>

        <div class="px-4 py-4">
            <p class="px-2 text-xs font-semibold uppercase tracking-wide text-atlantia-ink/45">Gestion principal</p>

            <nav class="mt-3 space-y-1.5 text-sm">
                @if ($user?->hasAnyRole(['admin', 'super_admin']))
                    <x-ui.nav-link :href="route('admin.dashboard')" :active="request()->routeIs('admin.dashboard')">
                        Dashboard
                    </x-ui.nav-link>
                    <x-ui.nav-link :href="route('admin.usuarios.index')" :active="request()->routeIs('admin.usuarios.*')">
                        Usuarios
                    </x-ui.nav-link>
                    <x-ui.nav-link :href="route('admin.productos.index')" :active="request()->routeIs('admin.productos.*')">
                        Productos
                    </x-ui.nav-link>
                    <x-ui.nav-link :href="route('admin.categorias.index')" :active="request()->routeIs('admin.categorias.*')">
                        Categorias
                    </x-ui.nav-link>
                    <x-ui.nav-link :href="route('admin.pedidos.index')" :active="request()->routeIs('admin.pedidos.*')">
                        Pedidos
                    </x-ui.nav-link>
                    <x-ui.nav-link :href="route('admin.repartidores.index')" :active="request()->routeIs('admin.repartidores.*')">
                        Repartidores
                    </x-ui.nav-link>
                    <x-ui.nav-link :href="route('admin.vendedores.index')" :active="request()->routeIs('admin.vendedores.*')">
                        Vendedores
                    </x-ui.nav-link>
                    <x-ui.nav-link
                        :href="route('admin.roles-permisos.index')"
                        :active="request()->routeIs('admin.roles-permisos.*')"
                    >
                        Roles y permisos
                    </x-ui.nav-link>
                @elseif ($user?->hasRole('vendedor'))
                    <x-ui.nav-link :href="route('vendedor.dashboard')" :active="request()->routeIs('vendedor.dashboard')">
                        Dashboard
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
                        Dashboard
                    </x-ui.nav-link>
                    <x-ui.nav-link
                        :href="route('repartidor.pedidos.index')"
                        :active="request()->routeIs('repartidor.pedidos.*')"
                    >
                        Entregas
                    </x-ui.nav-link>
                @elseif ($user?->hasRole('empleado'))
                    <x-ui.nav-link :href="route('empleado.dashboard')" :active="request()->routeIs('empleado.dashboard')">
                        Dashboard
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
    </div>
</aside>
