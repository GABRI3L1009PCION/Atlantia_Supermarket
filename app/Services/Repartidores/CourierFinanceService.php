<?php

namespace App\Services\Repartidores;

use App\Models\CourierCashSettlement;
use App\Models\CourierProfile;
use App\Models\CourierWallet;
use App\Models\CourierWithdrawalRequest;
use App\Models\User;
use App\Services\Notificaciones\NotificationService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CourierFinanceService
{
    public function __construct(
        private readonly CourierProfileService $profileService,
        private readonly CourierWalletService $walletService,
        private readonly NotificationService $notificationService
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function summary(User $user): array
    {
        $profile = $this->profileService->ensure($user);
        $walletSummary = $this->walletService->summary($user);
        $reserved = $this->reservedWithdrawalAmount($user);

        return [
            ...$walletSummary,
            'bank_account' => $this->bankAccount($profile),
            'pending_withdrawal_amount' => $reserved,
            'available_to_withdraw' => max(0, (float) $walletSummary['wallet']->available_balance - $reserved),
            'withdrawals' => $this->recentWithdrawals($user),
            'cash_settlements' => $this->recentCashSettlements($user),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function dashboardForOperations(): array
    {
        return [
            'pending_bank_accounts' => CourierProfile::query()
                ->with('user')
                ->whereNotNull('bank_name')
                ->whereNull('bank_account_verified_at')
                ->latest('updated_at')
                ->limit(20)
                ->get(),
            'withdrawals' => CourierWithdrawalRequest::query()
                ->with(['user', 'wallet'])
                ->whereIn('status', ['pending', 'approved'])
                ->latest('requested_at')
                ->limit(30)
                ->get(),
            'cash_settlements' => CourierCashSettlement::query()
                ->with(['user', 'wallet'])
                ->where('status', 'pending')
                ->latest('requested_at')
                ->limit(30)
                ->get(),
            'recent_transfers' => CourierWithdrawalRequest::query()
                ->with('user')
                ->where('status', 'transferred')
                ->latest('transferred_at')
                ->limit(15)
                ->get(),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function updateBankAccount(User $user, array $data): CourierProfile
    {
        $profile = $this->profileService->ensure($user);
        $hasChanges = $profile->bank_name !== ($data['bank_name'] ?? null)
            || $profile->bank_account_type !== ($data['bank_account_type'] ?? null)
            || $profile->bank_account_number !== ($data['bank_account_number'] ?? null)
            || $profile->bank_account_holder !== ($data['bank_account_holder'] ?? null)
            || $profile->payout_method !== ($data['payout_method'] ?? 'transfer')
            || ($data['bank_document_path'] ?? null) !== null;

        $profile->update([
            'bank_name' => $data['bank_name'],
            'bank_account_type' => $data['bank_account_type'],
            'bank_account_number' => $data['bank_account_number'],
            'bank_account_holder' => $data['bank_account_holder'],
            'payout_method' => $data['payout_method'] ?? 'transfer',
            'bank_document_path' => $data['bank_document_path'] ?? $profile->bank_document_path,
            'bank_account_verified_at' => $hasChanges ? null : $profile->bank_account_verified_at,
            'bank_account_verified_by' => $hasChanges ? null : $profile->bank_account_verified_by,
            'bank_verification_notes' => $hasChanges ? null : $profile->bank_verification_notes,
        ]);

        return $profile->refresh();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function verifyBankAccount(CourierProfile $profile, array $data, User $reviewer): CourierProfile
    {
        $action = (string) ($data['action'] ?? 'verify');

        $profile->update([
            'bank_account_verified_at' => $action === 'verify' ? now() : null,
            'bank_account_verified_by' => $action === 'verify' ? $reviewer->id : null,
            'bank_verification_notes' => $data['notes'] ?? null,
        ]);

        $this->notificationService->enviar(
            $profile->user,
            'courier.bank_account.'.$action,
            [
                'title' => $action === 'verify' ? 'Cuenta bancaria verificada' : 'Cuenta bancaria observada',
                'message' => $action === 'verify'
                    ? 'Tu cuenta bancaria ya fue validada por Atlantia.'
                    : 'Actualiza tu cuenta bancaria para continuar con retiros.',
                'url' => route('repartidor.ganancias.index'),
            ]
        );

        return $profile->refresh();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function requestWithdrawal(User $user, array $data): CourierWithdrawalRequest
    {
        $profile = $this->profileService->ensure($user);
        $wallet = $this->walletService->ensure($user);
        $amount = round((float) $data['amount'], 2);
        $minimum = (float) config('atlantia.couriers.minimum_withdrawal', 25);

        if ($profile->bank_account_verified_at === null) {
            throw ValidationException::withMessages([
                'amount' => 'Primero debes tener una cuenta bancaria verificada.',
            ]);
        }

        if ($amount < $minimum) {
            throw ValidationException::withMessages([
                'amount' => 'El retiro minimo es de Q '.number_format($minimum, 2).'.',
            ]);
        }

        if ($amount > $this->availableToWithdraw($user, $wallet)) {
            throw ValidationException::withMessages([
                'amount' => 'No tienes saldo disponible suficiente para ese retiro.',
            ]);
        }

        if (CourierWithdrawalRequest::query()
            ->where('user_id', $user->id)
            ->whereIn('status', ['pending', 'approved'])
            ->exists()) {
            throw ValidationException::withMessages([
                'amount' => 'Ya tienes una solicitud de retiro en proceso.',
            ]);
        }

        $request = CourierWithdrawalRequest::query()->create([
            'uuid' => (string) Str::uuid(),
            'user_id' => $user->id,
            'courier_wallet_id' => $wallet->id,
            'status' => 'pending',
            'payout_method' => $profile->payout_method ?: 'transfer',
            'requested_amount' => $amount,
            'bank_name' => $profile->bank_name,
            'bank_account_type' => $profile->bank_account_type,
            'bank_account_number_last4' => $this->lastFour($profile->bank_account_number),
            'bank_account_holder' => $profile->bank_account_holder,
            'courier_notes' => $data['notes'] ?? null,
            'requested_at' => now(),
            'metadata' => [
                'available_balance_snapshot' => (float) $wallet->available_balance,
            ],
        ]);

        $this->notifyFinanceTeam(
            'courier.withdrawal.requested',
            'Nuevo retiro solicitado',
            $user->name.' solicito un retiro por Q '.number_format($amount, 2).'.'
        );

        return $request;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function requestCashSettlement(User $user, array $data): CourierCashSettlement
    {
        $wallet = $this->walletService->ensure($user);
        $reportedAmount = round((float) $data['reported_amount'], 2);
        $cashBalance = (float) $wallet->cash_balance;

        if ($cashBalance <= 0) {
            throw ValidationException::withMessages([
                'reported_amount' => 'No tienes efectivo pendiente por liquidar.',
            ]);
        }

        if ($reportedAmount <= 0 || $reportedAmount > $cashBalance) {
            throw ValidationException::withMessages([
                'reported_amount' => 'La liquidacion debe ser mayor a cero y no puede superar el efectivo pendiente.',
            ]);
        }

        $settlement = CourierCashSettlement::query()->create([
            'uuid' => (string) Str::uuid(),
            'user_id' => $user->id,
            'courier_wallet_id' => $wallet->id,
            'status' => 'pending',
            'expected_amount' => $cashBalance,
            'reported_amount' => $reportedAmount,
            'courier_notes' => $data['notes'] ?? null,
            'requested_at' => now(),
        ]);

        $this->notifyFinanceTeam(
            'courier.cash_settlement.requested',
            'Nueva liquidacion de efectivo',
            $user->name.' reporto una liquidacion por Q '.number_format($reportedAmount, 2).'.'
        );

        return $settlement;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function reviewWithdrawal(CourierWithdrawalRequest $request, array $data, User $reviewer): CourierWithdrawalRequest
    {
        $action = (string) $data['action'];

        if ($action === 'reject') {
            $request->update([
                'status' => 'rejected',
                'admin_notes' => $data['admin_notes'] ?? null,
                'reviewed_by_user_id' => $reviewer->id,
                'reviewed_at' => now(),
                'rejected_at' => now(),
            ]);

            $this->notifyCourierWithdrawal($request->refresh(), 'rechazada');

            return $request->refresh();
        }

        $approvedAmount = round((float) ($data['approved_amount'] ?? $request->requested_amount), 2);

        if ($approvedAmount <= 0 || $approvedAmount > (float) $request->requested_amount) {
            throw ValidationException::withMessages([
                'approved_amount' => 'El monto aprobado no es valido.',
            ]);
        }

        if ($action === 'approve') {
            $request->update([
                'status' => 'approved',
                'approved_amount' => $approvedAmount,
                'admin_notes' => $data['admin_notes'] ?? null,
                'reviewed_by_user_id' => $reviewer->id,
                'reviewed_at' => now(),
                'approved_at' => now(),
            ]);

            $this->notifyCourierWithdrawal($request->refresh(), 'aprobada');

            return $request->refresh();
        }

        if ($action !== 'transfer') {
            throw ValidationException::withMessages([
                'action' => 'La accion solicitada no es valida.',
            ]);
        }

        DB::transaction(function () use ($request, $reviewer, $data, $approvedAmount): void {
            if ($request->status === 'pending') {
                $request->update([
                    'status' => 'approved',
                    'approved_amount' => $approvedAmount,
                    'reviewed_by_user_id' => $reviewer->id,
                    'reviewed_at' => now(),
                    'approved_at' => now(),
                ]);
            }

            $existingMovement = $request->user->courierWalletMovements()
                ->where('type', 'withdrawal')
                ->where('metadata->withdrawal_request_uuid', $request->uuid)
                ->exists();

            if (! $existingMovement) {
                $this->walletService->recordMovement($request->user, [
                    'type' => 'withdrawal',
                    'amount' => -1 * $approvedAmount,
                    'description' => 'Transferencia de retiro Atlantia',
                    'metadata' => [
                        'withdrawal_request_uuid' => $request->uuid,
                        'transfer_reference' => $data['transfer_reference'] ?? null,
                    ],
                ]);
            }

            $request->update([
                'status' => 'transferred',
                'approved_amount' => $approvedAmount,
                'transferred_amount' => $approvedAmount,
                'admin_notes' => $data['admin_notes'] ?? $request->admin_notes,
                'transfer_reference' => $data['transfer_reference'] ?? $request->transfer_reference,
                'receipt_path' => $data['receipt_path'] ?? $request->receipt_path,
                'reviewed_by_user_id' => $request->reviewed_by_user_id ?? $reviewer->id,
                'transferred_by_user_id' => $reviewer->id,
                'reviewed_at' => $request->reviewed_at ?? now(),
                'approved_at' => $request->approved_at ?? now(),
                'transferred_at' => now(),
            ]);
        });

        $this->notifyCourierWithdrawal($request->refresh(), 'transferida');

        return $request->refresh();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function reviewCashSettlement(CourierCashSettlement $settlement, array $data, User $reviewer): CourierCashSettlement
    {
        $action = (string) $data['action'];

        if ($action === 'reject') {
            $settlement->update([
                'status' => 'rejected',
                'admin_notes' => $data['admin_notes'] ?? null,
                'approved_by_user_id' => $reviewer->id,
                'rejected_at' => now(),
            ]);

            $this->notifyCourierSettlement($settlement->refresh(), 'rechazada');

            return $settlement->refresh();
        }

        if ($action !== 'approve') {
            throw ValidationException::withMessages([
                'action' => 'La accion solicitada no es valida.',
            ]);
        }

        $approvedAmount = round((float) ($data['approved_amount'] ?? $settlement->reported_amount), 2);

        if ($approvedAmount <= 0 || $approvedAmount > (float) $settlement->expected_amount) {
            throw ValidationException::withMessages([
                'approved_amount' => 'El monto aprobado no es valido para la liquidacion.',
            ]);
        }

        DB::transaction(function () use ($settlement, $data, $reviewer, $approvedAmount): void {
            $existingMovement = $settlement->user->courierWalletMovements()
                ->where('type', 'cash_settlement')
                ->where('metadata->cash_settlement_uuid', $settlement->uuid)
                ->exists();

            if (! $existingMovement) {
                $this->walletService->recordMovement($settlement->user, [
                    'type' => 'cash_settlement',
                    'amount' => -1 * $approvedAmount,
                    'description' => 'Liquidacion de efectivo Atlantia',
                    'metadata' => [
                        'cash_settlement_uuid' => $settlement->uuid,
                        'settlement_reference' => $data['settlement_reference'] ?? null,
                    ],
                ]);
            }

            $settlement->update([
                'status' => 'approved',
                'approved_amount' => $approvedAmount,
                'admin_notes' => $data['admin_notes'] ?? null,
                'settlement_reference' => $data['settlement_reference'] ?? null,
                'receipt_path' => $data['receipt_path'] ?? $settlement->receipt_path,
                'received_by_user_id' => $reviewer->id,
                'approved_by_user_id' => $reviewer->id,
                'received_at' => now(),
                'approved_at' => now(),
            ]);

            $this->walletService->ensure($settlement->user)->update([
                'last_settlement_at' => now(),
            ]);
            $this->walletService->refresh($settlement->user);
        });

        $this->notifyCourierSettlement($settlement->refresh(), 'aprobada');

        return $settlement->refresh();
    }

    /**
     * @return Collection<int, CourierWithdrawalRequest>
     */
    public function recentWithdrawals(User $user): Collection
    {
        return CourierWithdrawalRequest::query()
            ->where('user_id', $user->id)
            ->latest('requested_at')
            ->limit(10)
            ->get();
    }

    /**
     * @return Collection<int, CourierCashSettlement>
     */
    public function recentCashSettlements(User $user): Collection
    {
        return CourierCashSettlement::query()
            ->where('user_id', $user->id)
            ->latest('requested_at')
            ->limit(10)
            ->get();
    }

    /**
     * @return array<string, mixed>
     */
    public function bankAccount(CourierProfile $profile): array
    {
        return [
            'bank_name' => $profile->bank_name,
            'bank_account_type' => $profile->bank_account_type,
            'bank_account_number_last4' => $this->lastFour($profile->bank_account_number),
            'bank_account_holder' => $profile->bank_account_holder,
            'payout_method' => $profile->payout_method ?: 'transfer',
            'document_path' => $profile->bank_document_path,
            'verified_at' => $profile->bank_account_verified_at?->toIso8601String(),
            'verification_notes' => $profile->bank_verification_notes,
        ];
    }

    private function reservedWithdrawalAmount(User $user): float
    {
        return (float) CourierWithdrawalRequest::query()
            ->where('user_id', $user->id)
            ->whereIn('status', ['pending', 'approved'])
            ->sum(DB::raw('COALESCE(approved_amount, requested_amount)'));
    }

    private function availableToWithdraw(User $user, CourierWallet $wallet): float
    {
        return max(0, (float) $wallet->available_balance - $this->reservedWithdrawalAmount($user));
    }

    private function lastFour(?string $value): ?string
    {
        if ($value === null || trim($value) === '') {
            return null;
        }

        $digits = preg_replace('/\D+/', '', $value) ?: $value;

        return substr($digits, -4);
    }

    private function notifyFinanceTeam(string $type, string $title, string $message): void
    {
        User::query()
            ->whereHas('roles', fn ($query) => $query
                ->whereIn('name', ['contabilidad_finanzas', 'admin', 'supervisor_logistica'])
                ->where('guard_name', 'web'))
            ->active()
            ->get()
            ->each(function (User $user) use ($type, $title, $message): void {
                $this->notificationService->enviar($user, $type, [
                    'title' => $title,
                    'message' => $message,
                    'url' => route('empleado.finanzas-repartidores.index'),
                ]);
            });
    }

    private function notifyCourierWithdrawal(CourierWithdrawalRequest $request, string $statusLabel): void
    {
        $this->notificationService->enviar($request->user, 'courier.withdrawal.'.$request->status, [
            'title' => 'Solicitud de retiro '.$statusLabel,
            'message' => 'Tu solicitud por Q '.number_format((float) ($request->approved_amount ?? $request->requested_amount), 2).' fue '.$statusLabel.'.',
            'url' => route('repartidor.ganancias.index'),
        ]);
    }

    private function notifyCourierSettlement(CourierCashSettlement $settlement, string $statusLabel): void
    {
        $this->notificationService->enviar($settlement->user, 'courier.cash_settlement.'.$settlement->status, [
            'title' => 'Liquidacion '.$statusLabel,
            'message' => 'Tu liquidacion de efectivo por Q '.number_format((float) ($settlement->approved_amount ?? $settlement->reported_amount), 2).' fue '.$statusLabel.'.',
            'url' => route('repartidor.ganancias.index'),
        ]);
    }
}
