@extends('layouts.app')

@section('content')
    @php
        $wallet = $summary['wallet'];
        $movements = $summary['movements'];
        $cashMovements = $summary['cash_movements'];
        $money = static fn ($value): string => 'Q ' . number_format((float) $value, 2);
        $todayOrders = (int) ($summary['today_order_count'] ?? 0);
        $weekOrders = (int) ($summary['current_week_order_count'] ?? 0);
        $bonusEarnings = (float) ($summary['bonus_earnings'] ?? 0);
        $tipEarnings = (float) ($summary['tip_earnings'] ?? 0);
        $transitBalance = (float) ($summary['transit_balance'] ?? $wallet->pending_balance);
        $bankAccount = $summary['bank_account'] ?? [];
        $withdrawals = $summary['withdrawals'] ?? collect();
        $cashSettlements = $summary['cash_settlements'] ?? collect();
        $availableToWithdraw = (float) ($summary['available_to_withdraw'] ?? $wallet->available_balance);
        $pendingWithdrawalAmount = (float) ($summary['pending_withdrawal_amount'] ?? 0);
        $movementLabel = static function (string $type): string {
            return match ($type) {
                'earning' => 'Pago por pedido',
                'tip' => 'Propina',
                'bonus' => 'Bono',
                'withdrawal' => 'Retiro',
                'cash_collected' => 'Efectivo cobrado',
                'cash_paid_pickup' => 'Pago en comercio',
                'cash_settlement' => 'Ajuste de efectivo',
                default => ucfirst(str_replace('_', ' ', $type)),
            };
        };
        $movementDate = static function ($date): string {
            if (! $date) {
                return 'Fecha pendiente';
            }

            if ($date->isToday()) {
                return 'Hoy, ' . $date->format('g:i a') . '.';
            }

            if ($date->isYesterday()) {
                return 'Ayer, ' . $date->format('g:i a') . '.';
            }

            return $date->format('d/m/Y, g:i a') . '.';
        };
    @endphp

    <section class="-mx-4 -my-6 min-h-screen bg-[#fbf7f9] pb-28 text-atlantia-ink md:hidden">
        <header class="bg-atlantia-wine px-5 pb-5 pt-5 text-white">
            <div class="grid min-h-12 grid-cols-[2.75rem_1fr_2.75rem] items-center gap-3">
                <span aria-hidden="true"></span>
                <h1 class="truncate text-center text-xl font-black">Ganancias y billetera</h1>
                <a href="{{ route('repartidor.soporte.index') }}" class="grid h-11 w-11 place-items-center rounded-full border-2 border-white/85 text-white active:scale-95" aria-label="Ayuda">
                    <span class="text-2xl font-black leading-none">?</span>
                </a>
            </div>
        </header>

        <div class="space-y-4 px-4 pt-4">
            @if (session('success'))
                <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-3 py-2 text-sm font-bold text-emerald-800">{{ session('success') }}</div>
            @endif

            <div class="grid grid-cols-2 gap-3">
                <article class="min-h-36 rounded-2xl bg-white p-5 shadow-[0_14px_34px_rgba(42,16,24,0.10)]">
                    <p class="text-base font-black text-atlantia-ink">Hoy</p>
                    <p class="mt-4 truncate text-4xl font-black tracking-normal text-atlantia-ink">{{ $money($summary['today_earnings']) }}</p>
                    <p class="mt-2 text-base font-bold text-atlantia-ink/65">{{ number_format($todayOrders) }} pedidos</p>
                </article>

                <article class="min-h-36 rounded-2xl bg-white p-5 shadow-[0_14px_34px_rgba(42,16,24,0.10)]">
                    <p class="text-base font-black text-atlantia-ink">Esta semana</p>
                    <p class="mt-4 truncate text-4xl font-black tracking-normal text-atlantia-ink">{{ $money($summary['current_week_earnings']) }}</p>
                    <p class="mt-2 text-base font-bold text-atlantia-ink/65">{{ number_format($weekOrders) }} pedidos</p>
                </article>
            </div>

            <article class="rounded-2xl bg-atlantia-wine p-5 text-white shadow-[0_18px_42px_rgba(139,8,50,0.22)]">
                <div class="flex items-center justify-between gap-4">
                    <div class="min-w-0">
                        <p class="text-base font-black text-white/90">Saldo disponible</p>
                        <p class="mt-3 truncate text-4xl font-black tracking-normal">{{ $money($wallet->available_balance) }}</p>
                    </div>
                    <a href="{{ route('repartidor.soporte.index') }}" class="inline-flex min-h-12 w-36 shrink-0 items-center justify-center gap-2 rounded-xl bg-white px-3 text-center text-sm font-black leading-tight text-atlantia-wine shadow-[0_10px_24px_rgba(42,16,24,0.18)]">
                        <span>Retirar / Transferir</span>
                        <svg class="h-5 w-5 shrink-0" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                            <path d="M4 10h16M6 10v9M10 10v9M14 10v9M18 10v9M3 20h18M12 4 4 8h16l-8-4Z" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </a>
                </div>
            </article>

            <div class="grid grid-cols-3 gap-3">
                <article class="rounded-2xl bg-white px-3 py-4 shadow-[0_12px_28px_rgba(42,16,24,0.09)]">
                    <div class="flex items-center gap-2">
                        <svg class="h-7 w-7 shrink-0 text-atlantia-wine" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                            <path d="M4 10h16v10H4V10ZM3 7h18v3H3V7Z" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round"/>
                            <path d="M12 7v13M8 7C6.7 5.2 7 3.5 8.5 3.2 10 2.9 11.2 4.7 12 7c.8-2.3 2-4.1 3.5-3.8C17 3.5 17.3 5.2 16 7" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                        <p class="text-xs font-black leading-tight">Bonos</p>
                    </div>
                    <p class="mt-2 truncate text-base font-black">{{ $money($bonusEarnings) }}</p>
                </article>

                <article class="rounded-2xl bg-white px-3 py-4 shadow-[0_12px_28px_rgba(42,16,24,0.09)]">
                    <div class="flex items-center gap-2">
                        <svg class="h-7 w-7 shrink-0 text-atlantia-wine" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                            <path d="M12 3v18M17 7.5c0-1.9-1.8-3-5-3s-5 1.1-5 3 1.8 3 5 3 5 1.1 5 3-1.8 3-5 3-5-1.1-5-3" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/>
                        </svg>
                        <p class="text-xs font-black leading-tight">Propinas</p>
                    </div>
                    <p class="mt-2 truncate text-base font-black">{{ $money($tipEarnings) }}</p>
                </article>

                <article class="rounded-2xl bg-white px-3 py-4 shadow-[0_12px_28px_rgba(42,16,24,0.09)]">
                    <div class="flex items-center gap-2">
                        <svg class="h-7 w-7 shrink-0 text-atlantia-wine" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                            <path d="M6 8.5h10.5a3.5 3.5 0 1 1 0 7H8" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/>
                            <path d="M9 5 5 8.5 9 12M15 19l4-3.5L15 12" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                        <p class="text-xs font-black leading-tight">En transito</p>
                    </div>
                    <p class="mt-2 truncate text-base font-black">{{ $money($transitBalance) }}</p>
                </article>
            </div>

            <article class="rounded-2xl bg-white p-4 shadow-[0_14px_34px_rgba(42,16,24,0.10)]">
                <div class="flex items-center justify-between gap-3">
                    <h2 class="text-lg font-black text-atlantia-wine">Historial de transacciones</h2>
                    <a href="{{ route('repartidor.ganancias.index') }}" class="shrink-0 text-sm font-black text-atlantia-wine">Ver todo</a>
                </div>

                <div class="mt-3 divide-y divide-atlantia-rose/10">
                    @forelse ($movements->take(4) as $movement)
                        @php
                            $amount = (float) $movement->amount;
                            $isPositive = $amount >= 0;
                            $title = $movement->description ?: $movementLabel($movement->type);
                        @endphp
                        <div class="flex items-center justify-between gap-3 py-3">
                            <div class="flex min-w-0 items-center gap-3">
                                <span class="grid h-11 w-11 shrink-0 place-items-center overflow-hidden rounded-full bg-atlantia-blush text-atlantia-wine">
                                    @if ($movement->type === 'withdrawal' || $movement->type === 'cash_settlement')
                                        <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                            <path d="M4 10h16M6 10v9M10 10v9M14 10v9M18 10v9M3 20h18M12 4 4 8h16l-8-4Z" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/>
                                        </svg>
                                    @elseif ($movement->type === 'bonus')
                                        <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                            <path d="M4 10h16v10H4V10ZM3 7h18v3H3V7Z" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round"/>
                                            <path d="M12 7v13M8 7C6.7 5.2 7 3.5 8.5 3.2 10 2.9 11.2 4.7 12 7c.8-2.3 2-4.1 3.5-3.8C17 3.5 17.3 5.2 16 7" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                        </svg>
                                    @else
                                        <span class="text-base font-black">{{ strtoupper(substr($movementLabel($movement->type), 0, 1)) }}</span>
                                    @endif
                                </span>
                                <div class="min-w-0">
                                    <p class="truncate text-base font-black text-atlantia-ink">{{ $title }}</p>
                                    <p class="mt-0.5 text-sm font-bold text-atlantia-ink/55">{{ $movementDate($movement->created_at) }}</p>
                                </div>
                            </div>
                            <p class="{{ $isPositive ? 'text-emerald-700' : 'text-red-600' }} shrink-0 text-base font-black">
                                {{ $isPositive ? '+' : '-' }} {{ $money(abs($amount)) }}
                            </p>
                        </div>
                    @empty
                        <p class="py-8 text-center text-sm font-bold text-atlantia-ink/55">Sin movimientos registrados.</p>
                    @endforelse
                </div>
            </article>
        </div>

        <nav class="fixed bottom-0 left-1/2 z-50 grid w-full max-w-md -translate-x-1/2 grid-cols-5 rounded-t-2xl border-t border-atlantia-rose/10 bg-white px-2 pb-2 pt-2 shadow-[0_-10px_34px_rgba(42,16,24,0.16)] md:hidden" aria-label="Navegacion del repartidor">
            <a href="{{ route('repartidor.dashboard') }}" class="flex min-w-0 flex-col items-center gap-1 rounded-xl px-1.5 py-2 text-[11px] font-black text-atlantia-ink/55">
                <svg class="h-6 w-6" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M4 10.5 12 4l8 6.5V20a1 1 0 0 1-1 1h-5v-6h-4v6H5a1 1 0 0 1-1-1v-9.5Z"/></svg>
                <span>Inicio</span>
            </a>
            <a href="{{ route('repartidor.pedidos.index') }}" class="flex min-w-0 flex-col items-center gap-1 rounded-xl px-1.5 py-2 text-[11px] font-black text-atlantia-ink/55">
                <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M7 4h10v3h2v13H5V7h2V4Z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/><path d="M9 11h6M9 15h6" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
                <span>Pedidos</span>
            </a>
            <a href="{{ route('repartidor.ganancias.index') }}" class="flex min-w-0 flex-col items-center gap-1 rounded-xl px-1.5 py-2 text-[11px] font-black text-atlantia-wine">
                <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M4 7.5h15a1 1 0 0 1 1 1v10a1.5 1.5 0 0 1-1.5 1.5H5.5A1.5 1.5 0 0 1 4 18.5v-11Z" stroke="currentColor" stroke-width="1.8"/><path d="M16 13h4v4h-4a2 2 0 0 1 0-4Z" stroke="currentColor" stroke-width="1.8"/></svg>
                <span>Ganancias</span>
            </a>
            <a href="{{ route('repartidor.soporte.index') }}" class="flex min-w-0 flex-col items-center gap-1 rounded-xl px-1.5 py-2 text-[11px] font-black text-atlantia-ink/55">
                <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M5 5h14v10H8l-3 3V5Z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/><path d="M9 10h6" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
                <span>Mensajes</span>
            </a>
            <a href="{{ route('repartidor.historial.index') }}" class="flex min-w-0 flex-col items-center gap-1 rounded-xl px-1.5 py-2 text-[11px] font-black text-atlantia-ink/55">
                <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M12 12a4 4 0 1 0 0-8 4 4 0 0 0 0 8ZM4 21a8 8 0 0 1 16 0" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
                <span>Perfil</span>
            </a>
        </nav>
    </section>

    <section class="mx-auto hidden max-w-6xl space-y-4 md:block">
        <header class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <p class="text-xs font-black uppercase tracking-[0.18em] text-atlantia-rose">Billetera</p>
                <h1 class="text-2xl font-black text-atlantia-ink">Ganancias y efectivo</h1>
            </div>
            <a href="{{ route('repartidor.dashboard') }}" class="rounded-md border border-atlantia-rose/30 px-4 py-2 text-sm font-black text-atlantia-wine">Dashboard</a>
        </header>

        <div class="grid gap-3 md:grid-cols-4">
            <div class="rounded-lg bg-atlantia-wine p-4 text-white">
                <p class="text-xs text-white/70">Disponible</p>
                <p class="mt-1 text-3xl font-black">{{ $money($wallet->available_balance) }}</p>
            </div>
            <div class="rounded-lg border border-atlantia-rose/15 bg-white p-4">
                <p class="text-xs text-atlantia-ink/55">Pendiente</p>
                <p class="mt-1 text-3xl font-black text-atlantia-ink">{{ $money($wallet->pending_balance) }}</p>
            </div>
            <div class="rounded-lg border border-atlantia-rose/15 bg-white p-4">
                <p class="text-xs text-atlantia-ink/55">Efectivo en mano</p>
                <p class="mt-1 text-3xl font-black text-atlantia-ink">{{ $money($wallet->cash_balance) }}</p>
            </div>
            <div class="rounded-lg border border-red-100 bg-red-50 p-4">
                <p class="text-xs text-red-700/70">Saldo negativo</p>
                <p class="mt-1 text-3xl font-black text-red-700">{{ $money($wallet->negative_balance) }}</p>
            </div>
        </div>

        <div class="grid gap-3 md:grid-cols-3">
            <div class="rounded-lg border border-atlantia-rose/15 bg-white p-4">
                <p class="text-xs text-atlantia-ink/55">Hoy</p>
                <p class="mt-1 text-2xl font-black text-emerald-700">{{ $money($summary['today_earnings']) }}</p>
                <p class="mt-1 text-xs font-bold text-atlantia-ink/50">{{ number_format($todayOrders) }} pedidos</p>
            </div>
            <div class="rounded-lg border border-atlantia-rose/15 bg-white p-4">
                <p class="text-xs text-atlantia-ink/55">Semana actual</p>
                <p class="mt-1 text-2xl font-black text-emerald-700">{{ $money($summary['current_week_earnings']) }}</p>
                <p class="mt-1 text-xs font-bold text-atlantia-ink/50">{{ number_format($weekOrders) }} pedidos</p>
            </div>
            <div class="rounded-lg border border-atlantia-rose/15 bg-white p-4">
                <p class="text-xs text-atlantia-ink/55">Semana anterior</p>
                <p class="mt-1 text-2xl font-black text-emerald-700">{{ $money($summary['previous_week_earnings']) }}</p>
            </div>
        </div>

        <div class="grid gap-4 lg:grid-cols-[1.2fr_0.8fr]">
            <article id="historial-billetera" class="rounded-lg border border-atlantia-rose/15 bg-white p-4 shadow-sm">
                <h2 class="text-lg font-black text-atlantia-ink">Historial de movimientos</h2>
                <div class="mt-3 divide-y divide-atlantia-rose/10">
                    @forelse ($movements as $movement)
                        <div class="flex items-center justify-between gap-4 py-3">
                            <div>
                                <p class="font-black text-atlantia-ink">{{ $movementLabel($movement->type) }}</p>
                                <p class="text-sm text-atlantia-ink/55">{{ $movement->description ?? 'Movimiento de billetera' }}</p>
                            </div>
                            <p class="{{ (float) $movement->amount >= 0 ? 'text-emerald-700' : 'text-red-700' }} shrink-0 font-black">
                                {{ $money($movement->amount) }}
                            </p>
                        </div>
                    @empty
                        <p class="py-8 text-center text-sm font-bold text-atlantia-ink/55">Sin movimientos registrados.</p>
                    @endforelse
                </div>
            </article>

            <article class="rounded-lg border border-atlantia-rose/15 bg-white p-4 shadow-sm">
                <h2 class="text-lg font-black text-atlantia-ink">Efectivo y recargas</h2>
                <div class="mt-3 space-y-2">
                    @forelse ($cashMovements as $movement)
                        <div class="rounded-md bg-slate-50 p-3">
                            <div class="flex items-center justify-between gap-3">
                                <p class="font-black text-atlantia-ink">{{ $movementLabel($movement->type) }}</p>
                                <p class="{{ (float) $movement->amount >= 0 ? 'text-atlantia-ink' : 'text-red-700' }} font-black">
                                    {{ $money($movement->amount) }}
                                </p>
                            </div>
                            <p class="mt-1 text-xs text-atlantia-ink/55">{{ $movement->created_at->format('d/m/Y H:i') }}</p>
                        </div>
                    @empty
                        <p class="rounded-lg border border-dashed border-atlantia-rose/25 p-6 text-center text-sm font-bold text-atlantia-ink/55">Sin movimientos de efectivo.</p>
                    @endforelse
                </div>
                <div class="mt-4 rounded-lg bg-atlantia-blush/60 p-3">
                    <p class="text-sm font-black text-atlantia-ink">Cuenta bancaria</p>
                    <p class="mt-1 text-sm text-atlantia-ink/60">{{ ($bankAccount['verified_at'] ?? null) ? 'Cuenta validada para retiros.' : 'Pendiente de validacion por finanzas.' }}</p>
                </div>
            </article>
        </div>

        <div class="grid gap-4 lg:grid-cols-2">
            <article class="rounded-lg border border-atlantia-rose/15 bg-white p-4 shadow-sm">
                <h2 class="text-lg font-black text-atlantia-ink">Cuenta bancaria</h2>
                <p class="mt-1 text-sm text-atlantia-ink/60">Disponible para retirar: {{ $money($availableToWithdraw) }}. Pendiente: {{ $money($pendingWithdrawalAmount) }}.</p>
                <form method="POST" action="{{ route('repartidor.ganancias.bank-account.update') }}" class="mt-4 grid gap-3">
                    @csrf
                    @method('PATCH')
                    <div class="grid gap-3 md:grid-cols-2">
                        <input name="bank_name" value="{{ old('bank_name', $bankAccount['bank_name'] ?? '') }}" placeholder="Banco" class="rounded-lg border border-atlantia-rose/20 px-3 py-2 text-sm">
                        <select name="bank_account_type" class="rounded-lg border border-atlantia-rose/20 px-3 py-2 text-sm">
                            @foreach (['monetaria' => 'Monetaria', 'ahorro' => 'Ahorro'] as $value => $label)
                                <option value="{{ $value }}" @selected(old('bank_account_type', $bankAccount['bank_account_type'] ?? '') === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="grid gap-3 md:grid-cols-2">
                        <input name="bank_account_number" placeholder="Numero de cuenta" class="rounded-lg border border-atlantia-rose/20 px-3 py-2 text-sm">
                        <input name="bank_account_holder" value="{{ old('bank_account_holder', $bankAccount['bank_account_holder'] ?? '') }}" placeholder="Titular de la cuenta" class="rounded-lg border border-atlantia-rose/20 px-3 py-2 text-sm">
                    </div>
                    <input type="hidden" name="payout_method" value="transfer">
                    <div class="rounded-lg bg-slate-50 px-3 py-2 text-sm font-bold text-atlantia-ink/70">
                        {{ ($bankAccount['verified_at'] ?? null) ? 'Cuenta verificada' : 'Pendiente de verificacion' }}
                        @if (! empty($bankAccount['bank_account_number_last4']))
                            · Terminacion {{ $bankAccount['bank_account_number_last4'] }}
                        @endif
                    </div>
                    <button class="rounded-lg border border-atlantia-wine px-4 py-2 text-sm font-black text-atlantia-wine">Guardar cuenta</button>
                </form>
            </article>

            <article class="rounded-lg border border-atlantia-rose/15 bg-white p-4 shadow-sm">
                <h2 class="text-lg font-black text-atlantia-ink">Retiros y liquidaciones</h2>
                <form method="POST" action="{{ route('repartidor.ganancias.withdrawals.store') }}" class="mt-4 grid gap-3">
                    @csrf
                    <div class="grid gap-3 md:grid-cols-[1fr_1.6fr_auto]">
                        <input type="number" name="amount" min="0.01" step="0.01" placeholder="Monto de retiro" class="rounded-lg border border-atlantia-rose/20 px-3 py-2 text-sm">
                        <input name="notes" placeholder="Notas para finanzas" class="rounded-lg border border-atlantia-rose/20 px-3 py-2 text-sm">
                        <button class="rounded-lg bg-atlantia-wine px-4 py-2 text-sm font-black text-white">Solicitar retiro</button>
                    </div>
                </form>
                <form method="POST" action="{{ route('repartidor.ganancias.cash-settlements.store') }}" class="mt-4 grid gap-3">
                    @csrf
                    <div class="grid gap-3 md:grid-cols-[1fr_1.6fr_auto]">
                        <input type="number" name="reported_amount" min="0.01" step="0.01" placeholder="Monto a liquidar" class="rounded-lg border border-atlantia-rose/20 px-3 py-2 text-sm">
                        <input name="notes" placeholder="Referencia o comentario" class="rounded-lg border border-atlantia-rose/20 px-3 py-2 text-sm">
                        <button class="rounded-lg bg-emerald-600 px-4 py-2 text-sm font-black text-white">Liquidar efectivo</button>
                    </div>
                </form>
                <div class="mt-4 grid gap-3 md:grid-cols-2">
                    <div class="rounded-lg bg-slate-50 p-3">
                        <p class="text-sm font-black text-atlantia-ink">Retiros recientes</p>
                        <div class="mt-2 space-y-2 text-sm">
                            @forelse ($withdrawals as $withdrawal)
                                <div class="flex items-center justify-between gap-2">
                                    <span>{{ $money($withdrawal->requested_amount) }}</span>
                                    <span class="font-black text-atlantia-ink/60">{{ $withdrawal->status }}</span>
                                </div>
                            @empty
                                <p class="text-atlantia-ink/55">Sin retiros.</p>
                            @endforelse
                        </div>
                    </div>
                    <div class="rounded-lg bg-slate-50 p-3">
                        <p class="text-sm font-black text-atlantia-ink">Liquidaciones recientes</p>
                        <div class="mt-2 space-y-2 text-sm">
                            @forelse ($cashSettlements as $settlement)
                                <div class="flex items-center justify-between gap-2">
                                    <span>{{ $money($settlement->reported_amount) }}</span>
                                    <span class="font-black text-atlantia-ink/60">{{ $settlement->status }}</span>
                                </div>
                            @empty
                                <p class="text-atlantia-ink/55">Sin liquidaciones.</p>
                            @endforelse
                        </div>
                    </div>
                </div>
            </article>
        </div>
    </section>
@endsection
