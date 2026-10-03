@extends('layouts.app')

@section('content')
    @php
        $pendingProfiles = $metrics['pending_bank_accounts'];
        $withdrawals = $metrics['withdrawals'];
        $cashSettlements = $metrics['cash_settlements'];
        $recentTransfers = $metrics['recent_transfers'];
        $money = static fn ($value): string => 'Q ' . number_format((float) $value, 2);
    @endphp

    <section class="mx-auto max-w-7xl space-y-6">
        <x-page-header title="Finanzas de repartidores" subtitle="Cuentas bancarias, retiros, transferencias y conciliacion de efectivo." />

        @if (session('success'))
            <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-bold text-emerald-800">{{ session('success') }}</div>
        @endif

        <div class="grid gap-4 xl:grid-cols-3">
            <article class="rounded-xl border border-atlantia-rose/20 bg-white p-4 shadow-sm">
                <h2 class="text-lg font-black text-atlantia-ink">Cuentas por verificar</h2>
                <div class="mt-4 space-y-3">
                    @forelse ($pendingProfiles as $profile)
                        <div class="rounded-lg border border-atlantia-rose/15 p-3">
                            <p class="font-black text-atlantia-ink">{{ $profile->user?->name }}</p>
                            <p class="text-sm text-atlantia-ink/60">{{ $profile->bank_name }} · {{ $profile->bank_account_type }} · ****{{ substr(preg_replace('/\D+/', '', $profile->bank_account_number ?? ''), -4) }}</p>
                            <form method="POST" action="{{ route('empleado.finanzas-repartidores.bank-accounts.update', $profile) }}" class="mt-3 flex gap-2">
                                @csrf
                                @method('PATCH')
                                <input type="hidden" name="action" value="verify">
                                <input name="notes" placeholder="Notas de verificacion" class="flex-1 rounded-lg border border-atlantia-rose/20 px-3 py-2 text-sm">
                                <button class="rounded-lg bg-emerald-600 px-4 py-2 text-sm font-black text-white">Verificar</button>
                            </form>
                        </div>
                    @empty
                        <p class="rounded-lg border border-dashed border-atlantia-rose/20 px-4 py-8 text-center text-sm font-bold text-atlantia-ink/55">No hay cuentas pendientes.</p>
                    @endforelse
                </div>
            </article>

            <article class="rounded-xl border border-atlantia-rose/20 bg-white p-4 shadow-sm xl:col-span-2">
                <h2 class="text-lg font-black text-atlantia-ink">Retiros pendientes</h2>
                <div class="mt-4 space-y-3">
                    @forelse ($withdrawals as $withdrawal)
                        <div class="rounded-lg border border-atlantia-rose/15 p-3">
                            <div class="flex flex-wrap items-center justify-between gap-3">
                                <div>
                                    <p class="font-black text-atlantia-ink">{{ $withdrawal->user?->name }} · {{ $money($withdrawal->requested_amount) }}</p>
                                    <p class="text-sm text-atlantia-ink/60">{{ $withdrawal->bank_name }} · {{ $withdrawal->bank_account_holder }} · estado {{ $withdrawal->status }}</p>
                                </div>
                                <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-black text-atlantia-ink">{{ optional($withdrawal->requested_at)->format('d/m/Y H:i') }}</span>
                            </div>
                            <div class="mt-3 grid gap-2 lg:grid-cols-3">
                                <form method="POST" action="{{ route('empleado.finanzas-repartidores.withdrawals.update', $withdrawal) }}" class="grid gap-2">
                                    @csrf
                                    @method('PATCH')
                                    <input type="hidden" name="action" value="approve">
                                    <input type="number" name="approved_amount" min="0.01" step="0.01" value="{{ $withdrawal->approved_amount ?? $withdrawal->requested_amount }}" class="rounded-lg border border-atlantia-rose/20 px-3 py-2 text-sm">
                                    <input name="admin_notes" placeholder="Notas internas" class="rounded-lg border border-atlantia-rose/20 px-3 py-2 text-sm">
                                    <button class="rounded-lg bg-emerald-600 px-4 py-2 text-sm font-black text-white">Aprobar</button>
                                </form>
                                <form method="POST" action="{{ route('empleado.finanzas-repartidores.withdrawals.update', $withdrawal) }}" class="grid gap-2">
                                    @csrf
                                    @method('PATCH')
                                    <input type="hidden" name="action" value="transfer">
                                    <input type="number" name="approved_amount" min="0.01" step="0.01" value="{{ $withdrawal->approved_amount ?? $withdrawal->requested_amount }}" class="rounded-lg border border-atlantia-rose/20 px-3 py-2 text-sm">
                                    <input name="transfer_reference" placeholder="Referencia bancaria" class="rounded-lg border border-atlantia-rose/20 px-3 py-2 text-sm">
                                    <button class="rounded-lg bg-atlantia-wine px-4 py-2 text-sm font-black text-white">Marcar transferido</button>
                                </form>
                                <form method="POST" action="{{ route('empleado.finanzas-repartidores.withdrawals.update', $withdrawal) }}" class="grid gap-2">
                                    @csrf
                                    @method('PATCH')
                                    <input type="hidden" name="action" value="reject">
                                    <textarea name="admin_notes" rows="2" placeholder="Motivo de rechazo" class="rounded-lg border border-atlantia-rose/20 px-3 py-2 text-sm"></textarea>
                                    <button class="rounded-lg border border-red-300 px-4 py-2 text-sm font-black text-red-700">Rechazar</button>
                                </form>
                            </div>
                        </div>
                    @empty
                        <p class="rounded-lg border border-dashed border-atlantia-rose/20 px-4 py-8 text-center text-sm font-bold text-atlantia-ink/55">Sin retiros pendientes.</p>
                    @endforelse
                </div>
            </article>
        </div>

        <div class="grid gap-4 xl:grid-cols-2">
            <article class="rounded-xl border border-atlantia-rose/20 bg-white p-4 shadow-sm">
                <h2 class="text-lg font-black text-atlantia-ink">Liquidaciones de efectivo</h2>
                <div class="mt-4 space-y-3">
                    @forelse ($cashSettlements as $settlement)
                        <div class="rounded-lg border border-atlantia-rose/15 p-3">
                            <p class="font-black text-atlantia-ink">{{ $settlement->user?->name }} · reportado {{ $money($settlement->reported_amount) }}</p>
                            <p class="text-sm text-atlantia-ink/60">Esperado {{ $money($settlement->expected_amount) }}</p>
                            <div class="mt-3 grid gap-2 md:grid-cols-2">
                                <form method="POST" action="{{ route('empleado.finanzas-repartidores.cash-settlements.update', $settlement) }}" class="grid gap-2">
                                    @csrf
                                    @method('PATCH')
                                    <input type="hidden" name="action" value="approve">
                                    <input type="number" name="approved_amount" min="0.01" step="0.01" value="{{ $settlement->reported_amount }}" class="rounded-lg border border-atlantia-rose/20 px-3 py-2 text-sm">
                                    <input name="settlement_reference" placeholder="Referencia de caja o banco" class="rounded-lg border border-atlantia-rose/20 px-3 py-2 text-sm">
                                    <button class="rounded-lg bg-emerald-600 px-4 py-2 text-sm font-black text-white">Conciliar</button>
                                </form>
                                <form method="POST" action="{{ route('empleado.finanzas-repartidores.cash-settlements.update', $settlement) }}" class="grid gap-2">
                                    @csrf
                                    @method('PATCH')
                                    <input type="hidden" name="action" value="reject">
                                    <textarea name="admin_notes" rows="2" placeholder="Motivo del rechazo" class="rounded-lg border border-atlantia-rose/20 px-3 py-2 text-sm"></textarea>
                                    <button class="rounded-lg border border-red-300 px-4 py-2 text-sm font-black text-red-700">Rechazar</button>
                                </form>
                            </div>
                        </div>
                    @empty
                        <p class="rounded-lg border border-dashed border-atlantia-rose/20 px-4 py-8 text-center text-sm font-bold text-atlantia-ink/55">Sin liquidaciones pendientes.</p>
                    @endforelse
                </div>
            </article>

            <article class="rounded-xl border border-atlantia-rose/20 bg-white p-4 shadow-sm">
                <h2 class="text-lg font-black text-atlantia-ink">Transferencias completadas</h2>
                <div class="mt-4 space-y-3">
                    @forelse ($recentTransfers as $transfer)
                        <div class="rounded-lg bg-slate-50 p-3">
                            <div class="flex items-center justify-between gap-3">
                                <p class="font-black text-atlantia-ink">{{ $transfer->user?->name }}</p>
                                <span class="text-sm font-black text-emerald-700">{{ $money($transfer->transferred_amount ?? $transfer->approved_amount) }}</span>
                            </div>
                            <p class="mt-1 text-sm text-atlantia-ink/60">Referencia {{ $transfer->transfer_reference ?? 'sin referencia' }}</p>
                        </div>
                    @empty
                        <p class="rounded-lg border border-dashed border-atlantia-rose/20 px-4 py-8 text-center text-sm font-bold text-atlantia-ink/55">Sin transferencias registradas todavia.</p>
                    @endforelse
                </div>
            </article>
        </div>
    </section>
@endsection
