@php
    $user = auth()->user();
    $logoPath = collect(['images/logo-atlantia-delivery.png', 'images/logo.png', 'images/atlantia-logo.svg'])
        ->first(fn (string $path): bool => file_exists(public_path($path))) ?? 'images/atlantia-logo.svg';
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
        'Auditoría', 'Antifraude' => 'M4 11h16v10H4ZM8 11V7a4 4 0 0 1 8 0v4',
        'Observabilidad' => 'M3 12h3l2-5 4 10 3-6h4',
        'Reportes' => 'M4 19V5M4 19h16M8 16v-5M12 16V8M16 16v-8',
        'Vendedores' => 'M4 10h16l-1-5H5ZM6 10v10h12V10M9 20v-6h6v6',
        'Empleados' => 'M15 3H9a2 2 0 0 0-2 2v14l5-3 5 3V5a2 2 0 0 0-2-2Z',
        'Repartidores', 'Entregas externas' => 'M3 6h11v10H3ZM14 10h4l3 3v3h-7ZM7 20a2 2 0 1 0 0-4 2 2 0 0 0 0 4ZM17 20a2 2 0 1 0 0-4 2 2 0 0 0 0 4Z',
        'Productos' => 'M21 8 12 3 3 8l9 5 9-5ZM3 8v8l9 5 9-5V8M12 13v8',
        'Categorías' => 'M3 3h7v7H3ZM14 3h7v7h-7ZM3 14h7v7H3ZM14 14h7v7h-7Z',
        'Pedidos' => 'M5 3v18l2-1 2 1 2-1 2 1 2-1 2 1 2-1V3ZM8 7h8M8 11h8M8 15h5',
        'Zonas de entrega' => 'M9 18l-6 3V6l6-3 6 3 6-3v15l-6 3-6-3ZM9 3v15M15 6v15',
        'Comisiones', 'Nóminas' => 'M12 6c0 1.7-2.2 3-5 3S2 7.7 2 6s2.2-3 5-3 5 1.3 5 3ZM22 13c0 1.7-2.2 3-5 3s-5-1.3-5-3 2.2-3 5-3 5 1.3 5 3ZM12 13v5c0 1.7 2.2 3 5 3s5-1.3 5-3v-5',
        'Cupones' => 'M3 9a3 3 0 0 0 0 6v3a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-3a3 3 0 0 0 0-6V6a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2ZM13 5v14',
        'DTE y FEL' => 'M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8ZM14 2v6h6M8 13h8M8 17h5',
        'Reseñas' => 'm12 3 2.7 5.5 6.1.9-4.4 4.3 1 6.1L12 17l-5.4 2.8 1-6.1-4.4-4.3 6.1-.9Z',
        'Devoluciones' => 'M3 12a9 9 0 0 1 15.5-6.2L21 8M21 3v5h-5M21 12a9 9 0 0 1-15.5 6.2L3 16M3 21v-5h5',
        'Monitor ML', 'Reentrenamiento ML' => 'M3 12h4l3-8 4 16 3-8h4',
        'Atlantia Store' => 'M4 10h16l-1-5H5ZM6 10v10h12V10M10 20v-5h4v5',
        'Denuncias' => 'M12 9v4M12 17h.01M10.3 3.9 1.8 18a2 2 0 0 0 1.7 3h17a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0Z',
        'Mensajes' => 'M21 15a4 4 0 0 1-4 4H8l-5 3V7a4 4 0 0 1 4-4h10a4 4 0 0 1 4 4Z',
        'Banners de portada' => 'M3 5h18v14H3ZM3 15l5-5 4 4 3-3 6 6',
        'Promociones' => 'M20.6 13.4 13.4 20.6a2 2 0 0 1-2.8 0L3 13V3h10l7.6 7.6a2 2 0 0 1 0 2.8ZM7.5 7.5h.01',
        'Planes y publicidad' => 'M3 11v2a1 1 0 0 0 1 1h2l5 4V6L6 10H4a1 1 0 0 0-1 1ZM15 9a3 3 0 0 1 0 6M18 6a7 7 0 0 1 0 12',
        'Precios de planes' => 'M12 2v20M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6',
        'Datos de mercado' => 'M4 19V5M4 19h16M8 15l3-4 3 2 5-6',
        default => 'M12 12h.01',
    };
    $sections = [
        'Plataforma' => [
            ['label' => 'Vista general', 'route' => route('admin.dashboard'), 'active' => request()->routeIs('admin.dashboard')],
            ['label' => 'Usuarios', 'route' => route('admin.usuarios.index'), 'active' => request()->routeIs('admin.usuarios.*')],
            ['label' => 'Roles y permisos', 'route' => route('admin.roles-permisos.index'), 'active' => request()->routeIs('admin.roles-permisos.*')],
            ['label' => 'Auditoría', 'route' => route('admin.auditoria.index'), 'active' => request()->routeIs('admin.auditoria.*')],
            ['label' => 'Observabilidad', 'route' => route('admin.observabilidad.index'), 'active' => request()->routeIs('admin.observabilidad.*')],
            ['label' => 'Reportes', 'route' => route('admin.reportes.index'), 'active' => request()->routeIs('admin.reportes.*')],
        ],
        'Gestión' => [
            ['label' => 'Vendedores', 'route' => route('admin.vendedores.index'), 'active' => request()->routeIs('admin.vendedores.*')],
            ['label' => 'Empleados', 'route' => route('admin.empleados.index'), 'active' => request()->routeIs('admin.empleados.*')],
            ['label' => 'Repartidores', 'route' => route('admin.repartidores.index'), 'active' => request()->routeIs('admin.repartidores.*')],
            ['label' => 'Denuncias', 'route' => route('admin.denuncias.index'), 'active' => request()->routeIs('admin.denuncias.*')],
        ],
        'Tienda y publicidad' => [
            ['label' => 'Atlantia Store', 'route' => route('admin.tienda-atlantia.index'), 'active' => request()->routeIs('admin.tienda-atlantia.*')],
            ['label' => 'Mensajes', 'route' => route('admin.mensajes.index'), 'active' => request()->routeIs('admin.mensajes.*')],
            ['label' => 'Banners de portada', 'route' => route('admin.hero-banners.index'), 'active' => request()->routeIs('admin.hero-banners.*')],
            ['label' => 'Promociones', 'route' => route('admin.promociones.index'), 'active' => request()->routeIs('admin.promociones.*')],
            ['label' => 'Planes y publicidad', 'route' => route('admin.servicios.index'), 'active' => request()->routeIs('admin.servicios.*')],
            ['label' => 'Precios de planes', 'route' => route('admin.precios-planes.index'), 'active' => request()->routeIs('admin.precios-planes.*')],
            ['label' => 'Datos de mercado', 'route' => route('admin.datos-mercado.index'), 'active' => request()->routeIs('admin.datos-mercado.*')],
        ],
        'Catálogo y pedidos' => [
            ['label' => 'Productos', 'route' => route('admin.productos.index'), 'active' => request()->routeIs('admin.productos.*')],
            ['label' => 'Categorías', 'route' => route('admin.categorias.index'), 'active' => request()->routeIs('admin.categorias.*')],
            ['label' => 'Pedidos', 'route' => route('admin.pedidos.index'), 'active' => request()->routeIs('admin.pedidos.*')],
            ['label' => 'Entregas externas', 'route' => route('admin.entregas-externas.index'), 'active' => request()->routeIs('admin.entregas-externas.*')],
            ['label' => 'Zonas de entrega', 'route' => route('admin.zonas-entrega.index'), 'active' => request()->routeIs('admin.zonas-entrega.*')],
        ],
        'Finanzas y control' => [
            ['label' => 'Utilidad', 'route' => route('admin.utilidad.index'), 'active' => request()->routeIs('admin.utilidad.*')],
            ['label' => 'Pagos a vendedores', 'route' => route('admin.liquidaciones.index'), 'active' => request()->routeIs('admin.liquidaciones.*')],
            ['label' => 'Comisiones', 'route' => route('admin.comisiones.index'), 'active' => request()->routeIs('admin.comisiones.*')],
            ['label' => 'Nóminas', 'route' => route('admin.nominas.index'), 'active' => request()->routeIs('admin.nominas.*')],
            ['label' => 'Cupones', 'route' => route('admin.cupones.index'), 'active' => request()->routeIs('admin.cupones.*')],
            ['label' => 'DTE y FEL', 'route' => route('admin.dte.index'), 'active' => request()->routeIs('admin.dte.*')],
            ['label' => 'Reseñas', 'route' => route('admin.resenas.index'), 'active' => request()->routeIs('admin.resenas.*')],
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

    @include('layouts.partials.favicon')
    <title>{{ $title ?? 'Administracion Atlantia Store' }}</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles(['nonce' => request()->attributes->get('csp_nonce')])
</head>
<body class="min-h-screen bg-[#fbf7f7] text-atlantia-ink antialiased">
    <div class="min-h-screen xl:grid xl:grid-cols-[18rem_1fr]">
        <aside class="hidden print:!hidden border-r border-white/10 bg-gradient-to-b from-[#4d0006] via-[#3a0004] to-[#260002] text-white shadow-[12px_0_38px_rgba(51,5,9,0.28)] xl:block">
            <div id="super-admin-sidebar" class="sticky top-0 flex h-screen flex-col overflow-y-auto">
                <div class="border-b border-white/10 px-5 py-5">
                    <a href="{{ route('admin.dashboard') }}" class="block rounded-2xl bg-transparent px-3 py-3 transition hover:bg-white/[0.05]">
                        <img src="{{ asset($logoPath) }}" alt="Atlantia Store" class="mx-auto h-16 w-auto max-w-[12.5rem] object-contain drop-shadow-[0_6px_14px_rgba(0,0,0,0.35)]">
                        <div class="mt-5 flex items-center gap-3">
                            <span class="grid h-12 w-12 shrink-0 place-items-center rounded-full bg-[#6e000a] text-sm font-black text-white shadow-[0_12px_28px_rgba(30,3,6,0.28)]">
                                {{ $adminInitials }}
                            </span>
                            <div class="min-w-0">
                                <p class="truncate text-sm font-black leading-tight text-white">{{ $adminName }}</p>
                                <p class="mt-0.5 truncate text-xs font-semibold text-white/64">{{ $adminEmail }}</p>
                                <span class="mt-1.5 inline-flex rounded-full bg-white px-2.5 py-1 text-[10px] font-black uppercase text-[#5e0008] ring-1 ring-white/20">
                                    ADMIN GENERAL
                                </span>
                            </div>
                        </div>
                    </a>
                </div>

                <nav class="flex-1 space-y-3 px-4 py-5 text-sm" aria-label="Navegacion super admin">
                    @foreach ($sections as $title => $links)
                        @php
                            $seccionActiva = collect($links)->contains('active', true);
                        @endphp
                        {{-- Cada sección se despliega; la de la página actual abre sola --}}
                        <details class="group/seccion" data-menu-seccion="{{ $title }}" @if ($seccionActiva) open data-menu-activa @endif>
                            <summary class="flex cursor-pointer list-none items-center gap-2 rounded-xl px-3 py-3 text-xs font-black uppercase tracking-wider text-white/55 group-open/seccion:bg-white/[0.05] group-open/seccion:text-white/85 transition hover:bg-white/[0.06] hover:text-white/80 [&::-webkit-details-marker]:hidden">
                                <span class="flex-1">{{ $title }}</span>
                                @if ($seccionActiva)
                                    <span class="h-1.5 w-1.5 rounded-full bg-white/70 group-open/seccion:hidden" aria-hidden="true"></span>
                                @endif
                                <span class="rounded-full bg-white/[0.08] px-1.5 text-[10px] font-black text-white/50">{{ count($links) }}</span>
                                <svg class="h-3.5 w-3.5 transition-transform group-open/seccion:rotate-180" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m6 9 6 6 6-6"/></svg>
                            </summary>
                            <div class="mt-2 space-y-1 pb-1">
                                @foreach ($links as $link)
                                    <a
                                        href="{{ $link['route'] }}"
                                        @if ($link['active']) aria-current="page" @endif
                                        class="{{ $link['active'] ? 'bg-[#6e000a] text-white shadow-[inset_0_0_0_1px_rgba(255,255,255,0.08)]' : 'text-white/72 hover:bg-white/10 hover:text-white' }} group flex items-center gap-3 rounded-xl px-3 py-2.5 font-bold transition"
                                    >
                                        <span class="{{ $link['active'] ? 'bg-white/12 text-white' : 'bg-white/[0.07] text-white/72 group-hover:bg-white/14 group-hover:text-white' }} grid h-8 w-8 shrink-0 place-items-center rounded-lg transition">
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
                        </details>
                    @endforeach
                </nav>
            </div>
        </aside>
        {{-- Mantiene la posicion del menu lateral entre paginas --}}
        <script @nonce>
            (() => {
                const menu = document.getElementById('super-admin-sidebar');
                if (!menu) return;

                /* Secciones desplegables: recuerda cuáles dejó abiertas el usuario. */
                const claveSecciones = 'atlantia.superAdminSidebarSecciones';
                const secciones = [...menu.querySelectorAll('[data-menu-seccion]')];
                try {
                    const abiertas = JSON.parse(localStorage.getItem(claveSecciones) || 'null');
                    if (Array.isArray(abiertas)) {
                        secciones.forEach((s) => {
                            if (abiertas.includes(s.dataset.menuSeccion) || s.hasAttribute('data-menu-activa')) s.open = true;
                        });
                    }
                } catch (e) {}
                const guardarSecciones = () => {
                    try {
                        localStorage.setItem(claveSecciones, JSON.stringify(secciones.filter((s) => s.open).map((s) => s.dataset.menuSeccion)));
                    } catch (e) {}
                };
                secciones.forEach((s) => s.addEventListener('toggle', guardarSecciones));

                const clave = 'atlantia.superAdminSidebarScroll';
                try {
                    const guardado = sessionStorage.getItem(clave);
                    if (guardado !== null) menu.scrollTop = Number(guardado) || 0;
                } catch (e) {}
                const activo = menu.querySelector('[aria-current="page"]');
                if (activo) {
                    const caja = menu.getBoundingClientRect();
                    const item = activo.getBoundingClientRect();
                    if (item.top < caja.top || item.bottom > caja.bottom) {
                        menu.scrollTop += item.top - caja.top - (caja.height / 2) + (item.height / 2);
                    }
                }
                const guardar = () => { try { sessionStorage.setItem(clave, String(menu.scrollTop)); } catch (e) {} };
                menu.addEventListener('click', (e) => { if (e.target.closest('a')) guardar(); });
                window.addEventListener('pagehide', guardar);
            })();
        </script>

        <div class="min-w-0">
            <header class="sticky top-0 z-30 print:hidden border-b border-atlantia-rose/15 bg-white/95 shadow-sm backdrop-blur">
                @php
                    /* Ubicación actual para la ruta de navegación (sección › página). */
                    $seccionActual = null;
                    $paginaActual = null;
                    foreach ($sections as $tituloSeccion => $enlaces) {
                        foreach ($enlaces as $enlace) {
                            if ($enlace['active']) {
                                [$seccionActual, $paginaActual] = [$tituloSeccion, $enlace['label']];
                            }
                        }
                    }
                    $primerNombre = \Illuminate\Support\Str::of($adminName)->explode(' ')->first();
                @endphp
                <div class="flex h-14 items-center justify-between gap-4 px-4 sm:px-6 lg:px-8">
                    {{-- Izquierda: dónde estás --}}
                    <div class="flex min-w-0 items-center gap-3">
                        <img src="{{ asset($logoPath) }}" alt="Atlantia Store" class="h-8 w-auto xl:hidden">
                        <nav class="flex min-w-0 items-center gap-2 text-sm" aria-label="Ubicación">
                            <a href="{{ route('admin.dashboard') }}" class="hidden shrink-0 font-bold text-atlantia-ink/45 transition hover:text-atlantia-wine sm:inline">Panel</a>
                            @if ($seccionActual)
                                <span class="hidden text-atlantia-ink/25 sm:inline" aria-hidden="true">/</span>
                                <span class="hidden shrink-0 font-bold text-atlantia-ink/45 md:inline">{{ $seccionActual }}</span>
                                <span class="hidden text-atlantia-ink/25 md:inline" aria-hidden="true">/</span>
                                <span class="truncate font-black text-atlantia-ink">{{ $paginaActual }}</span>
                            @endif
                        </nav>
                    </div>

                    {{-- Derecha: estado, tienda y cuenta --}}
                    <div class="flex shrink-0 items-center gap-2">
                        <a href="{{ route('admin.observabilidad.index') }}" class="hidden items-center gap-2 rounded-full border border-emerald-200 bg-emerald-50 px-3 py-1 text-xs font-bold text-emerald-700 transition hover:bg-emerald-100 lg:inline-flex" title="Ver salud del sistema">
                            <span class="relative flex h-2 w-2">
                                <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-emerald-400 opacity-60"></span>
                                <span class="relative inline-flex h-2 w-2 rounded-full bg-emerald-500"></span>
                            </span>
                            Sistemas operativos
                        </a>

                        <a href="{{ route('home') }}" target="_blank" rel="noopener" class="hidden items-center gap-1.5 rounded-lg px-3 py-1.5 text-sm font-bold text-atlantia-ink/65 transition hover:bg-atlantia-blush/50 hover:text-atlantia-wine sm:inline-flex" title="Abrir la tienda en otra pestaña">
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 10h16l-1-5H5ZM6 10v10h12V10M9 20v-6h6v6"/></svg>
                            Ver tienda
                        </a>

                        @include('layouts.partials.panel-cuenta', [
                            'etiquetaRol' => 'Dueño · acceso total',
                            'opcionesCuenta' => [
                                ['texto' => 'Ver la tienda', 'url' => route('home'), 'nueva' => true],
                                ['texto' => 'Salud del sistema', 'url' => route('admin.observabilidad.index')],
                                ['texto' => 'Historial de cambios', 'url' => route('admin.auditoria.index')],
                            ],
                        ])
                    </div>
                </div>

                <div class="flex gap-2 overflow-x-auto border-t border-atlantia-rose/15 px-4 py-3 sm:px-6 xl:hidden">
                    @foreach ($sections as $links)
                        @foreach ($links as $link)
                            <a
                                href="{{ $link['route'] }}"
                                @if ($link['active']) aria-current="page" @endif
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

    @include('layouts.partials.panel-avisos')
    @stack('scripts')
</body>
</html>
