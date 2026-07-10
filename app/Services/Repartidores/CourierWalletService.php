<?php

namespace App\Services\Repartidores;

use App\Models\CourierWallet;
use App\Models\CourierWalletMovement;
use App\Models\DeliveryRoute;
use App\Models\ExternalDeliveryOrder;
use App\Models\Pedido;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Gestiona saldos, ganancias, efectivo y movimientos del repartidor.
 */
class CourierWalletService
{
    /**
     * Devuelve o crea billetera del repartidor.
     */
    public function ensure(User $user): CourierWallet
    {
        return CourierWallet::query()->firstOrCreate(
            ['user_id' => $user->id],
            [
                'available_balance' => 0,
                'pending_balance' => 0,
                'cash_balance' => 0,
                'negative_balance' => 0,
            ]
        );
    }

    /**
     * Resumen financiero para panel.
     *
     * @return array<string, mixed>
     */
    public function summary(User $user): array
    {
        $wallet = $this->ensure($user);
        $weekStart = now()->startOfWeek();
        $previousWeekStart = now()->subWeek()->startOfWeek();
        $previousWeekEnd = now()->subWeek()->endOfWeek();

        $earningsQuery = CourierWalletMovement::query()
            ->where('user_id', $user->id)
            ->whereIn('type', ['earning', 'tip', 'bonus'])
            ->where('status', 'available');
        $orderEarningsQuery = CourierWalletMovement::query()
            ->where('user_id', $user->id)
            ->where('type', 'earning')
            ->where('status', 'available');
        $pendingEarningsQuery = CourierWalletMovement::query()
            ->where('user_id', $user->id)
            ->whereIn('type', ['earning', 'tip', 'bonus'])
            ->where('status', 'pending');

        return [
            'wallet' => $wallet,
            'today_earnings' => (clone $earningsQuery)->whereDate('created_at', today())->sum('amount'),
            'today_order_count' => (clone $orderEarningsQuery)->whereDate('created_at', today())->count(),
            'current_week_earnings' => (clone $earningsQuery)->where('created_at', '>=', $weekStart)->sum('amount'),
            'current_week_order_count' => (clone $orderEarningsQuery)->where('created_at', '>=', $weekStart)->count(),
            'bonus_earnings' => (clone $earningsQuery)->where('type', 'bonus')->where('created_at', '>=', $weekStart)->sum('amount'),
            'tip_earnings' => (clone $earningsQuery)->where('type', 'tip')->where('created_at', '>=', $weekStart)->sum('amount'),
            'transit_balance' => max((float) $wallet->pending_balance, (float) (clone $pendingEarningsQuery)->sum('amount')),
            'previous_week_earnings' => (clone $earningsQuery)
                ->whereBetween('created_at', [$previousWeekStart, $previousWeekEnd])
                ->sum('amount'),
            'movements' => CourierWalletMovement::query()
                ->where('user_id', $user->id)
                ->latest()
                ->limit(15)
                ->get(),
            'cash_movements' => CourierWalletMovement::query()
                ->where('user_id', $user->id)
                ->whereIn('type', ['cash_collected', 'cash_paid_pickup', 'cash_settlement'])
                ->latest()
                ->limit(8)
                ->get(),
        ];
    }

    /**
     * Registra ganancias de una ruta interna entregada de forma idempotente.
     */
    public function recordInternalDelivery(User $user, Pedido $pedido, DeliveryRoute $route): void
    {
        DB::transaction(function () use ($user, $pedido, $route): void {
            $earning = (float) ($route->estimated_earning ?: $this->defaultInternalEarning($pedido, $route));
            $tip = (float) $route->tip_amount;
            $bonus = (float) $route->bonus_amount;
            $cashCollected = (float) $route->cash_to_collect;
            $cashPaidPickup = (float) $route->cash_to_pay_pickup;

            $this->recordMovementOnce($user, [
                'pedido_id' => $pedido->id,
                'delivery_route_id' => $route->id,
                'type' => 'earning',
                'amount' => $earning,
                'description' => 'Ganancia por entrega interna '.$pedido->numero_pedido,
            ]);

            if ($tip > 0) {
                $this->recordMovementOnce($user, [
                    'pedido_id' => $pedido->id,
                    'delivery_route_id' => $route->id,
                    'type' => 'tip',
                    'amount' => $tip,
                    'description' => 'Propina de '.$pedido->numero_pedido,
                ]);
            }

            if ($bonus > 0) {
                $this->recordMovementOnce($user, [
                    'pedido_id' => $pedido->id,
                    'delivery_route_id' => $route->id,
                    'type' => 'bonus',
                    'amount' => $bonus,
                    'description' => 'Bono de entrega '.$pedido->numero_pedido,
                ]);
            }

            if ($cashCollected > 0) {
                $this->recordMovementOnce($user, [
                    'pedido_id' => $pedido->id,
                    'delivery_route_id' => $route->id,
                    'type' => 'cash_collected',
                    'amount' => $cashCollected,
                    'description' => 'Efectivo cobrado al cliente',
                ]);
            }

            if ($cashPaidPickup > 0) {
                $this->recordMovementOnce($user, [
                    'pedido_id' => $pedido->id,
                    'delivery_route_id' => $route->id,
                    'type' => 'cash_paid_pickup',
                    'amount' => -1 * $cashPaidPickup,
                    'description' => 'Efectivo pagado en comercio',
                ]);
            }

            $this->syncWalletBalances($user);
        });
    }

    /**
     * Registra ganancias de una solicitud externa entregada de forma idempotente.
     */
    public function recordExternalDelivery(User $user, ExternalDeliveryOrder $order): void
    {
        DB::transaction(function () use ($user, $order): void {
            $this->recordMovementOnce($user, [
                'type' => 'earning',
                'amount' => (float) $order->courier_earning,
                'description' => 'Ganancia por entrega externa '.$order->external_reference,
                'metadata' => ['external_delivery_order_id' => $order->id],
            ]);

            if ((float) $order->tip_amount > 0) {
                $this->recordMovementOnce($user, [
                    'type' => 'tip',
                    'amount' => (float) $order->tip_amount,
                    'description' => 'Propina de entrega externa',
                    'metadata' => ['external_delivery_order_id' => $order->id],
                ]);
            }

            if ((float) $order->amount_to_collect > 0) {
                $this->recordMovementOnce($user, [
                    'type' => 'cash_collected',
                    'amount' => (float) $order->amount_to_collect,
                    'description' => 'Efectivo cobrado en entrega externa',
                    'metadata' => ['external_delivery_order_id' => $order->id],
                ]);
            }

            if ((float) $order->amount_to_pay_store > 0) {
                $this->recordMovementOnce($user, [
                    'type' => 'cash_paid_pickup',
                    'amount' => -1 * (float) $order->amount_to_pay_store,
                    'description' => 'Efectivo pagado en tienda externa',
                    'metadata' => ['external_delivery_order_id' => $order->id],
                ]);
            }

            $this->syncWalletBalances($user);
        });
    }

    /**
     * Registra un movimiento manual.
     *
     * @param  array<string, mixed>  $data
     */
    public function recordMovement(User $user, array $data): CourierWalletMovement
    {
        $movement = CourierWalletMovement::query()->create([
            'uuid' => (string) Str::uuid(),
            'user_id' => $user->id,
            'pedido_id' => $data['pedido_id'] ?? null,
            'delivery_route_id' => $data['delivery_route_id'] ?? null,
            'type' => $data['type'],
            'amount' => $data['amount'],
            'status' => $data['status'] ?? 'available',
            'description' => $data['description'] ?? null,
            'metadata' => $data['metadata'] ?? null,
        ]);

        $this->syncWalletBalances($user);

        return $movement;
    }

    /**
     * Registra un movimiento si no existe para el mismo origen/tipo.
     *
     * @param  array<string, mixed>  $data
     */
    private function recordMovementOnce(User $user, array $data): ?CourierWalletMovement
    {
        $query = CourierWalletMovement::query()
            ->where('user_id', $user->id)
            ->where('type', $data['type']);

        if (($data['pedido_id'] ?? null) !== null) {
            $query->where('pedido_id', $data['pedido_id']);
        }

        if (($data['delivery_route_id'] ?? null) !== null) {
            $query->where('delivery_route_id', $data['delivery_route_id']);
        }

        if (($data['metadata']['external_delivery_order_id'] ?? null) !== null) {
            $query->where('metadata->external_delivery_order_id', $data['metadata']['external_delivery_order_id']);
        }

        if ($query->exists()) {
            return null;
        }

        return $this->recordMovement($user, $data);
    }

    /**
     * Recalcula saldos desde movimientos disponibles.
     */
    private function syncWalletBalances(User $user): CourierWallet
    {
        $wallet = $this->ensure($user);
        $available = (float) CourierWalletMovement::query()
            ->where('user_id', $user->id)
            ->whereIn('type', ['earning', 'tip', 'bonus', 'adjustment', 'topup', 'withdrawal'])
            ->where('status', 'available')
            ->sum('amount');
        $pending = (float) CourierWalletMovement::query()
            ->where('user_id', $user->id)
            ->whereIn('type', ['earning', 'tip', 'bonus'])
            ->where('status', 'pending')
            ->sum('amount');
        $cash = (float) CourierWalletMovement::query()
            ->where('user_id', $user->id)
            ->whereIn('type', ['cash_collected', 'cash_paid_pickup', 'cash_settlement'])
            ->where('status', 'available')
            ->sum('amount');

        $wallet->update([
            'available_balance' => $available,
            'pending_balance' => $pending,
            'cash_balance' => $cash,
            'negative_balance' => abs(min(0, $cash)),
        ]);

        return $wallet->refresh();
    }

    /**
     * Calcula ganancia base de un pedido interno.
     */
    private function defaultInternalEarning(Pedido $pedido, DeliveryRoute $route): float
    {
        $distanceBonus = ((float) $route->distancia_km) * 2.5;
        $deliveryFeeShare = ((float) $pedido->envio) * 0.75;

        return round(max(15, $deliveryFeeShare, 12 + $distanceBonus), 2);
    }
}
