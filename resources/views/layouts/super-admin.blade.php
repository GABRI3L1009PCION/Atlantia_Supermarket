@php
    $user = auth()->user();
    $logoPath = file_exists(public_path('images/logo.png')) ? 'images/logo.png' : 'images/atlantia-logo.svg';
    $sections = [
        'Plataforma' => [
            ['label' => 'Vista general', 'route' => route('admin.dashboard'), 'active' => request()->routeIs('admin.dashboard')],
            ['label' => 'Administradores', 'route' => route('admin.usuarios.index'), 'active' => request()->routeIs('admin.usuarios.*')],
            ['label' => 'Roles y permisos', 'route' => route('admin.roles-permisos.index'), 'active' => request()->routeIs('admin.roles-permisos.*')],
            ['label' => 'Funciones activas', 'route' => route('admin.reportes.index'), 'active' => request()->routeIs('admin.reportes.*')],
        ],
        'Infraestructura' => [
            ['label' => 'Servicios', 'route' => route('admin.dashboard'), 'active' => false],
            ['label' => 'Base de datos', 'route' => route('admin.auditoria.index'), 'active' => request()->routeIs('admin.auditoria.*')],
            ['label' => 'Tareas y colas', 'route' => route('admin.reportes.index'), 'active' => false],
            ['label' => 'Registros y trazas', 'route' => route('admin.auditoria.index'), 'active' => request()->routeIs('admin.auditoria.*')],
        ],
        'Despliegues' => [
            ['label' => 'Versiones', 'route' => route('admin.dashboard'), 'active' => false],
            ['label' => 'Actualizaciones', 'route' => route('admin.dashboard'), 'active' => false],
            ['label' => 'Migraciones de datos', 'route' => route('admin.dashboard'), 'active' => false],
            ['label' => 'Reversion', 'route' => route('admin.dashboard'), 'active' => false],
        ],
        'Seguridad' => [
            ['label' => 'Auditoria', 'route' => route('admin.auditoria.index'), 'active' => request()->routeIs('admin.auditoria.*')],
            ['label' => 'Accesos y contrasenas', 'route' => route('admin.usuarios.index'), 'active' => request()->routeIs('admin.usuarios.*')],
            ['label' => 'Cifrado y llaves', 'route' => route('admin.roles-permisos.index'), 'active' => false],
            ['label' => 'Respaldos', 'route' => route('admin.dashboard'), 'active' => false],
        ],
        'Inteligencia' => [
            ['label' => 'Modelos en produccion', 'route' => route('admin.ml.monitor'), 'active' => request()->routeIs('admin.ml.monitor')],
            ['label' => 'Procesos automaticos', 'route' => route('admin.ml.reentrenamiento.index'), 'active' => request()->routeIs('admin.ml.reentrenamiento.*')],
        ],
    ];
@endphp

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $title ?? 'Atlantia Super Admin' }}</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="min-h-screen bg-[#101014] text-[#f7f0f4] antialiased">
    <div class="min-h-screen xl:grid xl:grid-cols-[18rem_1fr]">
        <aside class="hidden border-r border-white/10 bg-[#15151b] xl:block">
            <div class="sticky top-0 flex h-screen flex-col overflow-y-auto">
                <div class="border-b border-white/10 px-6 py-5">
                    <a href="{{ route('admin.dashboard') }}" class="flex items-center gap-3">
                        <span class="grid h-9 w-9 place-items-center rounded-lg bg-[#9a285a] font-black text-white">A</span>
                        <div>
                            <p class="font-black leading-tight text-white">Atlantia</p>
                            <p class="text-xs font-black uppercase tracking-wide text-[#f4a7c5]">Super Admin</p>
                        </div>
                    </a>
                </div>

                <nav class="flex-1 space-y-7 px-4 py-5 text-sm" aria-label="Navegacion super admin">
                    @foreach ($sections as $title => $links)
                        <div>
                            <p class="px-2 text-xs font-black uppercase tracking-wider text-white/35">{{ $title }}</p>
                            <div class="mt-2 space-y-1">
                                @foreach ($links as $link)
                                    <a
                                        href="{{ $link['route'] }}"
                                        class="{{ $link['active'] ? 'bg-[#9a285a] text-white' : 'text-white/72 hover:bg-white/7 hover:text-white' }} block rounded-lg px-3 py-2 font-bold transition"
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

        <div class="min-w-0">
            <header class="sticky top-0 z-30 border-b border-white/10 bg-[#101014]/95 backdrop-blur">
                <div class="flex min-h-16 items-center justify-between gap-4 px-4 sm:px-6 lg:px-8">
                    <div class="flex min-w-0 items-center gap-3">
                        <img src="{{ asset($logoPath) }}" alt="Atlantia Supermarket" class="h-9 w-auto xl:hidden">
                        <div class="hidden min-w-0 sm:block">
                            <p class="truncate text-sm font-black text-white">
                                Atlantia Supermarket
                                <span class="ml-2 rounded bg-[#9a285a]/25 px-2 py-1 text-xs text-[#f4a7c5]">SUPER ADMIN</span>
                            </p>
                            <p class="truncate text-xs text-white/45">Gobierno de plataforma · produccion Guatemala</p>
                        </div>
                    </div>

                    <div class="flex items-center gap-3">
                        <span class="hidden items-center gap-2 text-xs font-bold text-emerald-400 sm:flex">
                            <span class="h-2 w-2 rounded-full bg-emerald-400"></span>
                            Sistemas operativos
                        </span>

                        <div class="hidden text-right md:block">
                            <p class="text-sm font-black text-white">{{ $user?->name }}</p>
                            <p class="text-xs text-white/50">acceso total</p>
                        </div>

                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button
                                type="submit"
                                class="rounded-lg bg-[#9a285a] px-4 py-2 text-sm font-black text-white transition hover:bg-[#b63a70]"
                            >
                                Cerrar sesion
                            </button>
                        </form>
                    </div>
                </div>

                <div class="flex gap-2 overflow-x-auto border-t border-white/10 px-4 py-3 sm:px-6 xl:hidden">
                    @foreach ($sections as $links)
                        @foreach ($links as $link)
                            <a
                                href="{{ $link['route'] }}"
                                class="{{ $link['active'] ? 'bg-[#9a285a] text-white' : 'bg-white/5 text-white/70' }} shrink-0 rounded-lg px-3 py-2 text-xs font-bold"
                            >
                                {{ $link['label'] }}
                            </a>
                        @endforeach
                    @endforeach
                </div>
            </header>

            <main id="contenido-principal" class="px-4 py-6 sm:px-6 lg:px-8" tabindex="-1">
                @include('layouts.partials.flash')

                {{ $slot ?? '' }}
                @yield('content')
            </main>
        </div>
    </div>

    @livewireScripts
    @stack('scripts')
</body>
</html>
