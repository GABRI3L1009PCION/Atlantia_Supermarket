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
        $user?->hasAnyRole(['admin', 'super_admin']) => 'Administracion Atlantia',
        $user?->hasRole('vendedor') => 'Panel de vendedor',
        $user?->hasRole('repartidor') => 'Panel de repartidor',
        $user?->hasRole('empleado') => 'Panel operativo Atlantia',
        default => 'Atlantia Supermarket',
    };

    $adminQuickLinks = [
        ['label' => 'Dashboard', 'route' => route('admin.dashboard'), 'active' => request()->routeIs('admin.dashboard')],
        ['label' => 'Usuarios', 'route' => route('admin.usuarios.index'), 'active' => request()->routeIs('admin.usuarios.*')],
        ['label' => 'Vendedores', 'route' => route('admin.vendedores.index'), 'active' => request()->routeIs('admin.vendedores.*')],
        ['label' => 'Pedidos', 'route' => route('admin.pedidos.index'), 'active' => request()->routeIs('admin.pedidos.*')],
        ['label' => 'Comisiones', 'route' => route('admin.comisiones.index'), 'active' => request()->routeIs('admin.comisiones.*')],
        ['label' => 'DTE', 'route' => route('admin.dte.index'), 'active' => request()->routeIs('admin.dte.*')],
        ['label' => 'ML', 'route' => route('admin.ml.monitor'), 'active' => request()->routeIs('admin.ml.*')],
        ['label' => 'Reportes', 'route' => route('admin.reportes.index'), 'active' => request()->routeIs('admin.reportes.*')],
    ];
@endphp

<header class="border-b border-atlantia-rose/15 bg-white shadow-sm">
    <div class="mx-auto flex min-h-20 w-full max-w-[1500px] items-center justify-between gap-4 px-4 py-3 sm:px-6 lg:px-8">
        <div class="flex items-center gap-4">
            <a href="{{ $dashboardRoute }}" class="flex items-center" aria-label="Panel Atlantia">
                <img
                    src="{{ asset($logoPath) }}"
                    alt="Atlantia Supermarket"
                    class="h-12 w-auto"
                >
            </a>

            <div class="hidden md:block">
                <p class="text-xs font-semibold uppercase tracking-wide text-atlantia-rose">Control central</p>
                <p class="text-lg font-bold text-atlantia-ink">{{ $panelTitle }}</p>
            </div>
        </div>

        <div class="flex items-center gap-3">
            <div class="hidden rounded-lg bg-atlantia-cream px-4 py-2 text-right md:block">
                <p class="text-sm font-bold text-atlantia-ink">{{ $user?->name }}</p>
                <p class="text-xs text-atlantia-ink/60">{{ $user?->email }}</p>
            </div>

            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button
                    type="submit"
                    class="rounded-md bg-atlantia-wine px-4 py-2 text-sm font-bold text-white hover:bg-atlantia-wine-700"
                >
                    Cerrar sesion
                </button>
            </form>
        </div>
    </div>

    @if ($user?->hasAnyRole(['admin', 'super_admin']))
        <div class="border-t border-atlantia-rose/10 bg-[#fff8fb] xl:hidden">
            <div class="mx-auto flex w-full max-w-[1500px] gap-2 overflow-x-auto px-4 py-3 sm:px-6 lg:px-8">
                @foreach ($adminQuickLinks as $link)
                    <a
                        href="{{ $link['route'] }}"
                        class="{{ $link['active'] ? 'bg-atlantia-wine text-white' : 'bg-white text-atlantia-wine' }} shrink-0 rounded-md border border-atlantia-rose/20 px-3 py-2 text-sm font-semibold shadow-sm transition hover:border-atlantia-wine"
                    >
                        {{ $link['label'] }}
                    </a>
                @endforeach
            </div>
        </div>
    @endif
</header>
