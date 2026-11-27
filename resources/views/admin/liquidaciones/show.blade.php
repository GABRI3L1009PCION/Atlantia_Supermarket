@extends(auth()->user()?->isSuperAdmin() && request()->routeIs('admin.*') ? 'layouts.super-admin' : 'layouts.app')

@section('content')
    @php
        $perfil = $liquidacion->vendor?->fiscalProfile;
    @endphp

    <section class="mx-auto max-w-6xl space-y-5 pb-10">
        <div class="flex flex-wrap items-end justify-between gap-3">
            <div>
                <a href="{{ route('admin.liquidaciones.index') }}" class="text-sm font-bold text-atlantia-wine">← Pagos a vendedores</a>
                <h1 class="mt-1 text-3xl font-black text-atlantia-ink">{{ $liquidacion->numero }} · {{ $liquidacion->vendor?->business_name }}</h1>
                <p class="mt-1 text-sm text-atlantia-ink/60">Solicitada el {{ $liquidacion->solicitada_at?->format('d/m/Y H:i') }} · {{ $liquidacion->ventas }} ventas</p>
            </div>
            <span class="rounded-full bg-atlantia-blush px-3 py-1.5 text-sm font-black text-atlantia-wine">{{ $liquidacion->etiquetaEstado() }}</span>
        </div>

        <div class="grid gap-4 lg:grid-cols-[1fr_340px]">
            <div>@include('partials.liquidacion-detalle')</div>

            <aside class="space-y-4">
                <div class="rounded-2xl border border-atlantia-rose/15 bg-white p-4">
                    <h2 class="text-sm font-black text-atlantia-ink">Cuenta del vendedor</h2>
                    <dl class="mt-2 space-y-1 text-sm">
                        <div><dt class="inline text-atlantia-ink/55">Banco:</dt> <dd class="inline font-semibold">{{ $perfil?->banco_nombre ?? '—' }}</dd></div>
                        <div><dt class="inline text-atlantia-ink/55">Titular:</dt> <dd class="inline font-semibold">{{ $perfil?->cuenta_bancaria_titular ?? '—' }}</dd></div>
                        <div><dt class="inline text-atlantia-ink/55">Tipo:</dt> <dd class="inline font-semibold">{{ $perfil?->cuenta_bancaria_tipo ?? '—' }}</dd></div>
                        <div><dt class="inline text-atlantia-ink/55">Cuenta:</dt> <dd class="inline font-mono font-semibold">{{ $perfil?->cuenta_bancaria ?? '—' }}</dd></div>
                    </dl>
                    <p class="mt-3 text-2xl font-black text-atlantia-wine">Transferir Q {{ number_format((float) $liquidacion->monto_neto, 2) }}</p>
                </div>

                @if ($liquidacion->estado === 'solicitada')
                    <form method="POST" action="{{ route('admin.liquidaciones.pagar', $liquidacion) }}" class="rounded-2xl border border-emerald-200 bg-emerald-50 p-4" data-protect-submit>
                        @csrf
                        <label for="referencia_pago" class="text-sm font-black text-emerald-900">Referencia de la transferencia</label>
                        <input id="referencia_pago" name="referencia_pago" required maxlength="120" value="{{ old('referencia_pago') }}" class="mt-1 w-full rounded-lg border border-emerald-300 px-3 py-2 text-sm" placeholder="Ej. 000123456789">
                        @error('referencia_pago') <p class="mt-1 text-xs font-bold text-red-600">{{ $message }}</p> @enderror
                        <button type="submit" class="mt-3 w-full rounded-lg bg-emerald-700 px-4 py-2.5 text-sm font-black text-white hover:bg-emerald-800">Marcar como pagada</button>
                    </form>

                    <form method="POST" action="{{ route('admin.liquidaciones.rechazar', $liquidacion) }}" class="rounded-2xl border border-red-200 bg-white p-4" data-protect-submit>
                        @csrf
                        <label for="notas" class="text-sm font-black text-red-700">Rechazar (las ventas vuelven a su saldo)</label>
                        <textarea id="notas" name="notas" required maxlength="500" rows="2" class="mt-1 w-full rounded-lg border border-red-200 px-3 py-2 text-sm" placeholder="Motivo, ej. cuenta bancaria inválida"></textarea>
                        <button type="submit" class="mt-2 w-full rounded-lg border border-red-300 px-4 py-2 text-sm font-black text-red-700 hover:bg-red-50">Rechazar solicitud</button>
                    </form>
                @elseif ($liquidacion->estado === 'pagada')
                    <p class="rounded-2xl bg-emerald-50 p-4 text-sm font-bold text-emerald-800">Pagada el {{ $liquidacion->pagada_at?->format('d/m/Y H:i') }} por {{ $liquidacion->pagadaPor?->name }} · Ref. {{ $liquidacion->referencia_pago }}</p>
                @else
                    <p class="rounded-2xl bg-red-50 p-4 text-sm font-bold text-red-700">Rechazada: {{ $liquidacion->notas }}</p>
                @endif
            </aside>
        </div>
    </section>
@endsection
