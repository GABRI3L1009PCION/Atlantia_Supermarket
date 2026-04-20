@extends('layouts.app')

@section('content')
<section class="mx-auto max-w-7xl px-4 py-8">
    <x-page-header title="Panel administrador" subtitle="Metricas consolidadas de Atlantia Supermarket." />
    <x-data-panel title="Indicadores principales" :items="$metrics" />

    <div class="mt-6 grid gap-4 md:grid-cols-3">
        <a
            href="{{ route('admin.roles-permisos.index') }}"
            class="rounded-lg border border-atlantia-rose/30 bg-white p-5 shadow-sm transition hover:border-atlantia-wine hover:shadow-md"
        >
            <p class="text-sm font-bold uppercase text-atlantia-wine">Seguridad</p>
            <h2 class="mt-2 text-xl font-bold text-atlantia-ink">Roles y permisos</h2>
            <p class="mt-2 text-sm text-atlantia-ink/70">
                Crea roles operativos, permisos escalables y controla accesos del sistema.
            </p>
        </a>

        <a
            href="{{ route('admin.usuarios.index') }}"
            class="rounded-lg border border-atlantia-rose/30 bg-white p-5 shadow-sm transition hover:border-atlantia-wine hover:shadow-md"
        >
            <p class="text-sm font-bold uppercase text-atlantia-wine">Usuarios</p>
            <h2 class="mt-2 text-xl font-bold text-atlantia-ink">Cuentas del sistema</h2>
            <p class="mt-2 text-sm text-atlantia-ink/70">
                Supervisa clientes, empleados, vendedores y cuentas administrativas.
            </p>
        </a>

        <a
            href="{{ route('admin.vendedores.index') }}"
            class="rounded-lg border border-atlantia-rose/30 bg-white p-5 shadow-sm transition hover:border-atlantia-wine hover:shadow-md"
        >
            <p class="text-sm font-bold uppercase text-atlantia-wine">Marketplace</p>
            <h2 class="mt-2 text-xl font-bold text-atlantia-ink">Vendedores</h2>
            <p class="mt-2 text-sm text-atlantia-ink/70">
                Aprueba, suspende y revisa vendedores locales de Atlantia.
            </p>
        </a>
    </div>
</section>
@endsection
