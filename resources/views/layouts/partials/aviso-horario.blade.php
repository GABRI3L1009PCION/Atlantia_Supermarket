@php
    $estadoAtlantia = request()->routeIs('cliente.checkout.*', 'login', 'login.*', 'register', 'register.*', 'password.*', 'two-factor.*', 'verification.*', 'vendedor.acceso.*', 'acceso.*') ? null : \App\Support\HorarioTienda::estadoAtlantia();
@endphp

@if ($estadoAtlantia && ! $estadoAtlantia['abierto'])
    <div
        @class([
            'mx-auto mt-3 flex w-full max-w-7xl items-center gap-3 rounded-xl px-4 py-2.5 text-sm font-bold sm:px-5',
            'bg-red-50 text-red-700 ring-1 ring-red-100' => $estadoAtlantia['pausado'],
            'bg-amber-50 text-amber-800 ring-1 ring-amber-100' => ! $estadoAtlantia['pausado'],
        ])
        role="status"
        data-aviso-horario-tienda
    >
        <svg class="h-5 w-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2" stroke-linecap="round"/></svg>
        <p>
            {{ $estadoAtlantia['mensaje'] }}
            @unless ($estadoAtlantia['pausado'])
                <span class="font-semibold">Puedes hacer tu pedido ahora y programar la entrega.</span>
            @endunless
        </p>
    </div>
@endif
