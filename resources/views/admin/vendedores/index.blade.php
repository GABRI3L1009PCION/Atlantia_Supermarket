@extends(auth()->user()?->isSuperAdmin() && request()->routeIs('admin.*') ? 'layouts.super-admin' : 'layouts.app')

@section('content')
    <section class="mx-auto max-w-full py-2">
        <div class="rounded-2xl border border-atlantia-rose/20 bg-white p-6 shadow-sm">
            <x-page-header title="Vendedores" subtitle="Aprueba, suspende, reactiva y depura vendedores del marketplace." />

            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead>
                        <tr class="border-b border-atlantia-rose/20 text-left text-atlantia-ink/55">
                            <th class="pb-3">Comercio</th>
                            <th class="pb-3">Usuario</th>
                            <th class="pb-3">Municipio</th>
                            <th class="pb-3">Estado</th>
                            <th class="pb-3 text-right">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-atlantia-rose/15">
                        @forelse ($vendors as $vendor)
                            <tr>
                                <td class="py-3">
                                    <p class="font-semibold text-atlantia-ink">{{ $vendor->business_name }}</p>
                                    <p class="text-xs text-atlantia-ink/50">{{ $vendor->slug }}</p>
                                </td>
                                <td class="py-3 text-atlantia-ink/70">{{ $vendor->user?->email }}</td>
                                <td class="py-3 text-atlantia-ink/70">{{ $vendor->municipio }}</td>
                                <td class="py-3">
                                    <span class="rounded-md bg-atlantia-blush px-3 py-1 text-xs font-bold text-atlantia-wine">
                                        {{ $vendor->status }}
                                    </span>
                                </td>
                                <td class="py-3 text-right">
                                    <a href="{{ route('admin.vendedores.show', $vendor) }}" class="font-semibold text-atlantia-wine hover:underline">Gestionar</a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="py-6 text-center text-atlantia-ink/60">No hay vendedores registrados.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-4">{{ $vendors->links() }}</div>
        </div>
    </section>
@endsection
