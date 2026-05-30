@php
    $user = auth()->user();
    $logoPath = file_exists(public_path('images/logo.png')) ? 'images/logo.png' : 'images/atlantia-logo.svg';
    $adminName = $user?->name ?? 'Administrador';
    $adminEmail = $user?->email ?? 'admin@atlantia.local';
    $nameParts = preg_split('/\s+/', trim($adminName)) ?: [];
    $adminInitials = collect($nameParts)
        ->filter()
        ->take(2)
        ->map(fn ($part) => mb_strtoupper(mb_substr($part, 0, 1)))
        ->join('') ?: 'A';
    $iconFor = fn (string $label) => match ($label) {
        'Vista general' => 'M4 13a8 8 0 1 1 16 0M12 13l4-4M7 17h10',
        'Usuarios' => 'M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2M9 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8M22 21v-2a4 4 0 0 0-3-3.9M16 3.1a4 4 0 0 1 0 7.8',
        'Roles y permisos' => 'M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10Z',
        'Auditoria', 'Antifraude' => 'M4 11h16v10H4ZM8 11V7a4 4 0 0 1 8 0v4',
        'Reportes' => 'M4 19V5M4 19h16M8 16v-5M12 16V8M16 16v-8',
        'Vendedores' => 'M4 10h16l-1-5H5ZM6 10v10h12V10M9 20v-6h6v6',
        'Empleados' => 'M15 3H9a2 2 0 0 0-2 2v14l5-3 5 3V5a2 2 0 0 0-2-2Z',
        'Repartidores' => 'M3 6h11v10H3ZM14 10h4l3 3v3h-7ZM7 20a2 2 0 1 0 0-4 2 2 0 0 0 0 4ZM17 20a2 2 0 1 0 0-4 2 2 0 0 0 0 4Z',
        'Productos' => 'M21 8 12 3 3 8l9 5 9-5ZM3 8v8l9 5 9-5V8M12 13v8',
        'Categorias' => 'M3 3h7v7H3ZM14 3h7v7h-7ZM3 14h7v7H3ZM14 14h7v7h-7Z',
        'Pedidos' => 'M5 3v18l2-1 2 1 2-1 2 1 2-1 2 1 2-1V3ZM8 7h8M8 11h8M8 15h5',
        'Zonas de entrega' => 'M9 18l-6 3V6l6-3 6 3 6-3v15l-6 3-6-3ZM9 3v15M15 6v15',
        'Comisiones', 'Nominas' => 'M12 6c0 1.7-2.2 3-5 3S2 7.7 2 6s2.2-3 5-3 5 1.3 5 3ZM22 13c0 1.7-2.2 3-5 3s-5-1.3-5-3 2.2-3 5-3 5 1.3 5 3ZM12 13v5c0 1.7 2.2 3 5 3s5-1.3 5-3v-5',
        'Cupones' => 'M3 9a3 3 0 0 0 0 6v3a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-3a3 3 0 0 0 0-6V6a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2ZM13 5v14',
        'DTE y FEL' => 'M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8ZM14 2v6h6M8 13h8M8 17h5',
        'Resenas' => 'm12 3 2.7 5.5 6.1.9-4.4 4.3 1 6.1L12 17l-5.4 2.8 1-6.1-4.4-4.3 6.1-.9Z',
        'Devoluciones' => 'M3 12a9 9 0 0 1 15.5-6.2L21 8M21 3v5h-5M21 12a9 9 0 0 1-15.5 6.2L3 16M3 21v-5h5',
        'Monitor ML', 'Reentrenamiento ML' => 'M3 12h4l3-8 4 16 3-8h4',
        default => 'M12 12h.01',
    };
    $sections = [
        'Plataforma' => [
            ['label' => 'Vista general', 'route' => route('admin.dashboard'), 'active' => request()->routeIs('admin.dashboard')],
            ['label' => 'Usuarios', 'route' => route('admin.usuarios.index'), 'active' => request()->routeIs('admin.usuarios.*')],
            ['label' => 'Roles y permisos', 'route' => route('admin.roles-permisos.index'), 'active' => request()->routeIs('admin.roles-permisos.*')],
            ['label' => 'Auditoria', 'route' => route('admin.auditoria.index'), 'active' => request()->routeIs('admin.auditoria.*')],
            ['label' => 'Reportes', 'route' => route('admin.reportes.index'), 'active' => request()->routeIs('admin.reportes.*')],
        ],
        'Gestion' => [
            ['label' => 'Vendedores', 'route' => route('admin.vendedores.index'), 'active' => request()->routeIs('admin.vendedores.*')],
            ['label' => 'Empleados', 'route' => route('admin.empleados.index'), 'active' => request()->routeIs('admin.empleados.*')],
            ['label' => 'Repartidores', 'route' => route('admin.repartidores.index'), 'active' => request()->routeIs('admin.repartidores.*')],
        ],
        'Catalogo y pedidos' => [
            ['label' => 'Productos', 'route' => route('admin.productos.index'), 'active' => request()->routeIs('admin.productos.*')],
            ['label' => 'Categorias', 'route' => route('admin.categorias.index'), 'active' => request()->routeIs('admin.categorias.*')],
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
        ],
        'Inteligencia' => [
            ['label' => 'Monitor ML', 'route' => route('admin.ml.monitor'), 'active' => request()->routeIs('admin.ml.monitor')],
            ['label' => 'Reentrenamiento ML', 'route' => route('admin.ml.reentrenamiento.index'), 'active' => request()->routeIs('admin.ml.reentrenamiento.*')],
        ],
    ];
@endphp

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $title ?? 'Administracion Atlantia' }}</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles(['nonce' => request()->attributes->get('csp_nonce')])
</head>
<body class="min-h-screen bg-[#fbf7f9] text-atlantia-ink antialiased">
    <div class="min-h-screen xl:grid xl:grid-cols-[18rem_1fr]">
        <aside class="hidden border-r border-white/10 bg-gradient-to-b from-[#74133a] via-[#5b0e2d] to-[#340716] text-white shadow-[12px_0_38px_rgba(52,7,22,0.28)] xl:block">
            <div class="sticky top-0 flex h-screen flex-col overflow-y-auto">
                <div class="border-b border-white/10 px-5 py-5">
                    <a href="{{ route('admin.dashboard') }}" class="block rounded-2xl bg-transparent px-3 py-3 transition hover:bg-white/[0.05]">
                        <img src="{{ asset($logoPath) }}" alt="Atlantia Supermarket" class="mx-auto h-12 w-auto max-w-[12.5rem] object-contain drop-shadow-[0_8px_18px_rgba(255,255,255,0.18)]">
                        <div class="mt-5 flex items-center gap-3">
                            <span class="grid h-12 w-12 shrink-0 place-items-center rounded-full bg-[#941a4c] text-sm font-black text-white shadow-[0_12px_28px_rgba(30,4,14,0.28)]">
                                {{ $adminInitials }}
                            </span>
                            <div class="min-w-0">
                                <p class="truncate text-sm font-black leading-tight text-white">{{ $adminName }}</p>
                                <p class="mt-0.5 truncate text-xs font-semibold text-white/64">{{ $adminEmail }}</p>
                                <span class="mt-1.5 inline-flex rounded-full bg-white px-2.5 py-1 text-[10px] font-black uppercase tracking-wide text-[#8b1745] ring-1 ring-white/20">
                                    ADMIN GENERAL
                                </span>
                            </div>
                        </div>
                    </a>
                </div>

                <nav class="flex-1 space-y-6 px-4 py-5 text-sm" aria-label="Navegacion super admin">
                    @foreach ($sections as $title => $links)
                        <div>
                            <p class="px-3 text-[11px] font-black uppercase tracking-[0.32em] text-white/35">{{ $title }}</p>
                            <div class="mt-2 space-y-1">
                                @foreach ($links as $link)
                                    <a
                                        href="{{ $link['route'] }}"
                                        class="{{ $link['active'] ? 'bg-white/14 text-white shadow-[inset_0_0_0_1px_rgba(255,255,255,0.12)]' : 'text-white/72 hover:bg-white/10 hover:text-white' }} group flex items-center gap-3 rounded-xl px-3 py-2.5 font-bold transition"
                                    >
                                        <span class="{{ $link['active'] ? 'bg-white/18 text-white' : 'bg-white/[0.07] text-white/72 group-hover:bg-white/14 group-hover:text-white' }} grid h-8 w-8 shrink-0 place-items-center rounded-lg transition">
                                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                                <path d="{{ $iconFor($link['label']) }}" />
                                            </svg>
                                        </span>
                                        <span class="min-w-0 flex-1 truncate">{{ $link['label'] }}</span>
                                        @if ($link['active'])
                                            <span class="h-2 w-2 rounded-full bg-white shadow-[0_0_12px_rgba(255,255,255,0.7)]"></span>
                                        @endif
                                    </a>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </nav>
            </div>
        </aside>

        <div class="min-w-0">
            <header class="sticky top-0 z-30 border-b border-atlantia-rose/15 bg-white/95 shadow-sm backdrop-blur">
                <div class="flex min-h-16 items-center justify-between gap-4 px-4 sm:px-6 lg:px-8">
                    <div class="flex min-w-0 items-center gap-3">
                        <img src="{{ asset($logoPath) }}" alt="Atlantia Supermarket" class="h-9 w-auto xl:hidden">
                        <div class="hidden min-w-0 sm:block">
                            <p class="truncate text-sm font-black text-atlantia-ink">
                                Atlantia Supermarket
                                <span class="ml-2 rounded bg-atlantia-blush px-2 py-1 text-xs text-atlantia-rose">ADMIN GENERAL</span>
                            </p>
                            <p class="truncate text-xs text-atlantia-ink/50">Gobierno de plataforma - produccion Guatemala</p>
                        </div>
                    </div>

                    <div class="flex items-center gap-3">
                        <span class="hidden items-center gap-2 text-xs font-bold text-emerald-600 sm:flex">
                            <span class="h-2 w-2 rounded-full bg-emerald-400"></span>
                            Sistemas operativos
                        </span>

                        <div class="hidden text-right md:block">
                            <p class="text-sm font-black text-atlantia-ink">{{ $user?->name }}</p>
                            <p class="text-xs text-atlantia-ink/50">acceso total</p>
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

                <div class="flex gap-2 overflow-x-auto border-t border-atlantia-rose/15 px-4 py-3 sm:px-6 xl:hidden">
                    @foreach ($sections as $links)
                        @foreach ($links as $link)
                            <a
                                href="{{ $link['route'] }}"
                                class="{{ $link['active'] ? 'bg-atlantia-wine text-white' : 'bg-atlantia-cream text-atlantia-ink/70' }} shrink-0 rounded-lg px-3 py-2 text-xs font-bold"
                            >
                                {{ $link['label'] }}
                            </a>
                        @endforeach
                    @endforeach
                </div>
            </header>

            <main id="super-admin-content" class="px-4 py-6 sm:px-6 lg:px-8" tabindex="-1">
                @include('layouts.partials.impersonation-banner')
                @include('layouts.partials.flash')

                {{ $slot ?? '' }}
                @yield('content')
            </main>
        </div>
    </div>

    @livewireScripts(['nonce' => request()->attributes->get('csp_nonce')])
    <x-toast />
    <div
        id="livewire-global-overlay"
        class="pointer-events-none fixed inset-0 z-[94] hidden items-center justify-center bg-slate-950/35 backdrop-blur-[1px]"
        role="status"
        aria-live="polite"
        aria-label="Cargando contenido"
    >
        <div class="rounded-xl bg-white px-5 py-4 text-sm font-bold text-atlantia-wine shadow-xl">
            Cargando...
        </div>
    </div>
    @include('layouts.partials.protect-submit')
    @stack('scripts')
</body>
</html>
