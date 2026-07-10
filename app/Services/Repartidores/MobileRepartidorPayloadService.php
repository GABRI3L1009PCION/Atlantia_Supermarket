<?php

namespace App\Services\Repartidores;

use App\Models\CourierProfile;
use App\Models\CourierWallet;
use App\Models\CourierWalletMovement;
use App\Models\DeliveryOffer;
use App\Models\DeliveryRoute;
use App\Models\DeliveryZone;
use App\Models\ExternalDeliveryOrder;
use App\Models\Pedido;
use App\Models\User;

/**
 * Convierte datos operativos del repartidor en payloads estables para la app movil.
 */
class MobileRepartidorPayloadService
{
    public function __construct(
        private readonly CourierProfileService $profileService,
        private readonly CourierWalletService $walletService
    ) {}

    /**
     * Perfil autenticado del repartidor.
     *
     * @return array<string, mixed>
     */
    public function me(User $user): array
    {
        $profile = $this->profileService->ensure($user);
        $wallet = $this->walletService->ensure($user);

        return [
            'id' => $user->uuid,
            'name' => $user->name,
            'email' => $user->email,
            'phone' => $user->phone,
            'profile' => $this->profile($profile),
            'reward' => $this->profileService->rewardProgress($profile),
            'wallet' => $this->wallet($wallet),
        ];
    }

    /**
     * Dashboard operativo.
     *
     * @param  array<string, mixed>  $metrics
     * @return array<string, mixed>
     */
    public function dashboard(array $metrics): array
    {
        return [
            'overview' => $metrics['overview'] ?? [],
            'profile' => $this->profile($metrics['profile']),
            'reward' => $metrics['reward'] ?? [],
            'wallet' => $this->walletSummary($metrics['wallet'] ?? []),
            'current_order' => isset($metrics['ruta_actual']) && $metrics['ruta_actual']?->pedido
                ? $this->internalOrder($metrics['ruta_actual']->pedido)
                : null,
            'upcoming_orders' => collect($metrics['proximas_entregas'] ?? [])->map(
                fn (DeliveryRoute $route): array => $this->routeSummary($route)
            )->values(),
            'external_active' => collect($metrics['external_active'] ?? [])->map(
                fn (ExternalDeliveryOrder $order): array => $this->externalOrder($order)
            )->values(),
            'offers' => collect($metrics['offers'] ?? [])->map(
                fn (DeliveryOffer $offer): array => $this->offer($offer)
            )->values(),
            'demand_zones' => collect($metrics['demand_zones'] ?? [])->map(
                fn (DeliveryZone $zone): array => $this->demandZone($zone)
            )->values(),
            'promotions' => $metrics['promotions'] ?? [],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function profile(CourierProfile $profile): array
    {
        return [
            'availability_status' => $profile->availability_status,
            'service_scope' => $profile->service_scope,
            'vehicle_type' => $profile->vehicle_type,
            'reward_level' => $profile->reward_level,
            'reward_points' => (int) $profile->reward_points,
            'rating' => (float) $profile->rating,
            'acceptance_rate' => (float) $profile->acceptance_rate,
            'completion_rate' => (float) $profile->completion_rate,
            'auto_accept_enabled' => (bool) $profile->auto_accept_enabled,
            'auto_accept_max_distance_km' => (float) $profile->auto_accept_max_distance_km,
            'safe_zones' => $profile->safe_zones ?? [],
            'insurance_active' => (bool) $profile->insurance_active,
            'last_online_at' => $profile->last_online_at?->toIso8601String(),
            'last_offline_at' => $profile->last_offline_at?->toIso8601String(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function wallet(CourierWallet $wallet): array
    {
        return [
            'available_balance' => (float) $wallet->available_balance,
            'pending_balance' => (float) $wallet->pending_balance,
            'cash_balance' => (float) $wallet->cash_balance,
            'negative_balance' => (float) $wallet->negative_balance,
            'last_settlement_at' => $wallet->last_settlement_at?->toIso8601String(),
        ];
    }

    /**
     * @param  array<string, mixed>  $summary
     * @return array<string, mixed>
     */
    public function walletSummary(array $summary): array
    {
        return [
            'wallet' => isset($summary['wallet']) ? $this->wallet($summary['wallet']) : null,
            'today_earnings' => (float) ($summary['today_earnings'] ?? 0),
            'today_order_count' => (int) ($summary['today_order_count'] ?? 0),
            'current_week_earnings' => (float) ($summary['current_week_earnings'] ?? 0),
            'current_week_order_count' => (int) ($summary['current_week_order_count'] ?? 0),
            'bonus_earnings' => (float) ($summary['bonus_earnings'] ?? 0),
            'tip_earnings' => (float) ($summary['tip_earnings'] ?? 0),
            'transit_balance' => (float) ($summary['transit_balance'] ?? 0),
            'previous_week_earnings' => (float) ($summary['previous_week_earnings'] ?? 0),
            'movements' => collect($summary['movements'] ?? [])->map(
                fn (CourierWalletMovement $movement): array => $this->walletMovement($movement)
            )->values(),
            'cash_movements' => collect($summary['cash_movements'] ?? [])->map(
                fn (CourierWalletMovement $movement): array => $this->walletMovement($movement)
            )->values(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function walletMovement(CourierWalletMovement $movement): array
    {
        return [
            'id' => $movement->uuid,
            'type' => $movement->type,
            'amount' => (float) $movement->amount,
            'status' => $movement->status,
            'description' => $movement->description,
            'created_at' => $movement->created_at?->toIso8601String(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function offer(DeliveryOffer $offer): array
    {
        $offer->loadMissing(['pedido.direccion', 'pedido.vendor', 'route', 'externalOrder']);

        return [
            'id' => $offer->uuid,
            'source_type' => $offer->source_type,
            'status' => $offer->status,
            'expires_at' => $offer->expires_at?->toIso8601String(),
            'estimated_gain' => (float) $offer->estimated_gain,
            'pickup_distance_km' => $offer->pickup_distance_km === null ? null : (float) $offer->pickup_distance_km,
            'delivery_distance_km' => $offer->delivery_distance_km === null ? null : (float) $offer->delivery_distance_km,
            'total_distance_km' => $offer->total_distance_km === null ? null : (float) $offer->total_distance_km,
            'payment_method' => $offer->payment_method,
            'business_name' => $offer->metadata['business_name'] ?? $offer->pedido?->vendor?->business_name ?? $offer->externalOrder?->store_name,
            'pickup_address' => $offer->metadata['pickup_address'] ?? $offer->route?->pickup_address ?? $offer->externalOrder?->pickup_address,
            'delivery_zone' => $offer->metadata['delivery_zone'] ?? $offer->pedido?->direccion?->municipio ?? $offer->externalOrder?->delivery_address,
            'internal_order' => $offer->pedido ? $this->internalOrder($offer->pedido) : null,
            'external_order' => $offer->externalOrder ? $this->externalOrder($offer->externalOrder) : null,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function internalOrder(Pedido $pedido): array
    {
        $pedido->loadMissing(['direccion', 'items.producto', 'deliveryRoute', 'cliente', 'vendor']);
        $route = $pedido->deliveryRoute;

        return [
            'id' => $pedido->uuid,
            'number' => $pedido->numero_pedido,
            'source_type' => $route?->source_type ?? ($pedido->vendor_id === null ? 'internal' : 'entrepreneurs'),
            'status' => $pedido->estadoValor(),
            'payment_method' => $pedido->metodoPagoValor(),
            'payment_status' => $pedido->estadoPagoValor(),
            'subtotal' => (float) $pedido->subtotal,
            'delivery_fee' => (float) $pedido->envio,
            'discount' => (float) $pedido->descuento,
            'total' => (float) $pedido->total,
            'vendor' => [
                'name' => $pedido->vendor?->business_name ?? $route?->pickup_name ?? 'Atlantia Supermarket',
                'address' => $route?->pickup_address ?? $pedido->vendor?->direccion_comercial,
                'latitude' => $this->nullableFloat($route?->pickup_latitude ?? $pedido->vendor?->latitude),
                'longitude' => $this->nullableFloat($route?->pickup_longitude ?? $pedido->vendor?->longitude),
                'notes' => $route?->pickup_notes,
            ],
            'customer' => [
                'name' => $pedido->direccion?->nombre_contacto ?? $pedido->cliente?->name,
                'phone' => $pedido->direccion?->telefono_contacto ?? $pedido->cliente?->phone,
                'address' => $this->addressLine($pedido),
                'municipio' => $pedido->direccion?->municipio,
                'reference' => $pedido->direccion?->referencia,
                'latitude' => $this->nullableFloat($pedido->direccion?->latitude),
                'longitude' => $this->nullableFloat($pedido->direccion?->longitude),
            ],
            'cash' => [
                'to_collect' => (float) ($route?->cash_to_collect ?? ($pedido->metodoPagoValor() === 'efectivo' ? $pedido->total : 0)),
                'to_pay_pickup' => (float) ($route?->cash_to_pay_pickup ?? 0),
                'change_required' => (float) ($route?->change_required ?? 0),
            ],
            'earning' => [
                'estimated' => (float) ($route?->estimated_earning ?? 0),
                'tip' => (float) ($route?->tip_amount ?? 0),
                'bonus' => (float) ($route?->bonus_amount ?? 0),
            ],
            'route' => $route ? $this->route($route) : null,
            'items' => $pedido->items->map(fn ($item): array => [
                'name' => $item->producto_nombre_snapshot,
                'sku' => $item->producto_sku_snapshot,
                'quantity' => (int) $item->cantidad,
                'unit_price' => (float) $item->precio_unitario_snapshot,
                'subtotal' => (float) $item->subtotal,
            ])->values(),
            'notes' => $pedido->notas,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function externalOrder(ExternalDeliveryOrder $order): array
    {
        return [
            'id' => $order->uuid,
            'external_reference' => $order->external_reference,
            'source_channel' => $order->source_channel,
            'status' => $order->status,
            'store' => [
                'name' => $order->store_name,
                'contact_name' => $order->store_contact_name,
                'phone' => $order->store_phone,
                'address' => $order->pickup_address,
                'latitude' => $this->nullableFloat($order->pickup_latitude),
                'longitude' => $this->nullableFloat($order->pickup_longitude),
                'notes' => $order->pickup_notes,
            ],
            'customer' => [
                'name' => $order->customer_name,
                'phone' => $order->customer_phone,
                'address' => $order->delivery_address,
                'latitude' => $this->nullableFloat($order->delivery_latitude),
                'longitude' => $this->nullableFloat($order->delivery_longitude),
                'notes' => $order->delivery_notes,
            ],
            'payment_method' => $order->payment_method,
            'cash' => [
                'to_collect' => (float) $order->amount_to_collect,
                'to_pay_pickup' => (float) $order->amount_to_pay_store,
                'change_required' => (float) $order->change_required,
            ],
            'delivery_fee' => (float) $order->delivery_fee,
            'courier_earning' => (float) $order->courier_earning,
            'tip_amount' => (float) $order->tip_amount,
            'estimated_distance_km' => (float) $order->estimated_distance_km,
            'estimated_time_min' => (int) $order->estimated_time_min,
            'confirmation_code_required' => $order->confirmation_code !== null,
            'timeline' => [
                'requested_at' => $order->requested_at?->toIso8601String(),
                'accepted_at' => $order->accepted_at?->toIso8601String(),
                'arrived_pickup_at' => $order->arrived_pickup_at?->toIso8601String(),
                'picked_up_at' => $order->picked_up_at?->toIso8601String(),
                'arrived_customer_at' => $order->arrived_customer_at?->toIso8601String(),
                'delivered_at' => $order->delivered_at?->toIso8601String(),
            ],
            'cash_issue' => [
                'reported_at' => $order->cash_issue_reported_at?->toIso8601String(),
                'notes' => $order->cash_issue_notes,
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function route(DeliveryRoute $route): array
    {
        return [
            'id' => $route->uuid,
            'status' => $route->estado,
            'distance_km' => (float) $route->distancia_km,
            'estimated_time_min' => (int) $route->tiempo_estimado_min,
            'real_time_min' => $route->tiempo_real_min === null ? null : (int) $route->tiempo_real_min,
            'planned_path' => $route->ruta_planificada ?? [],
            'real_path' => $route->ruta_real ?? [],
            'confirmation_code_required' => $route->confirmation_code !== null,
            'proof_type' => $route->proof_type,
            'timeline' => [
                'assigned_at' => $route->asignada_at?->toIso8601String(),
                'accepted_at' => $route->aceptada_at?->toIso8601String(),
                'arrived_pickup_at' => $route->arrived_pickup_at?->toIso8601String(),
                'picked_up_at' => $route->picked_up_at?->toIso8601String(),
                'started_at' => $route->iniciada_at?->toIso8601String(),
                'arrived_customer_at' => $route->arrived_customer_at?->toIso8601String(),
                'completed_at' => $route->completada_at?->toIso8601String(),
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function routeSummary(DeliveryRoute $route): array
    {
        $route->loadMissing(['pedido.cliente', 'pedido.direccion', 'pedido.vendor']);

        return [
            'route' => $this->route($route),
            'order' => $route->pedido ? $this->internalOrder($route->pedido) : null,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function demandZone(DeliveryZone $zone): array
    {
        return [
            'id' => $zone->uuid,
            'name' => $zone->nombre,
            'municipio' => $zone->municipio,
            'description' => $zone->descripcion,
            'base_cost' => (float) $zone->costo_base,
            'latitude' => $this->nullableFloat($zone->latitude_centro),
            'longitude' => $this->nullableFloat($zone->longitude_centro),
        ];
    }

    private function addressLine(Pedido $pedido): ?string
    {
        if ($pedido->direccion === null) {
            return null;
        }

        return trim(collect([
            $pedido->direccion->direccion_linea_1,
            $pedido->direccion->direccion_linea_2,
            $pedido->direccion->zona_o_barrio,
            $pedido->direccion->municipio,
        ])->filter()->join(', '));
    }

    private function nullableFloat(mixed $value): ?float
    {
        return $value === null ? null : (float) $value;
    }
}
