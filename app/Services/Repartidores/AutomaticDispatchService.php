<?php

namespace App\Services\Repartidores;

use App\Enums\EstadoPedido;
use App\Models\CourierProfile;
use App\Models\DeliveryOffer;
use App\Models\DeliveryRoute;
use App\Models\ExternalDeliveryOrder;
use App\Models\MarketCourierStatus;
use App\Models\Pedido;
use App\Models\User;
use App\Services\Geolocalizacion\EtaCalculadorService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AutomaticDispatchService
{
    /** @var array<int, string> */
    private const INTERNAL_ELIGIBLE_STATES = [
        EstadoPedido::Confirmado->value,
        EstadoPedido::EnPreparacion->value,
        EstadoPedido::ListoParaEntrega->value,
    ];

    public function __construct(
        private readonly DeliveryOfferService $offerService,
        private readonly EtaCalculadorService $etaCalculadorService
    ) {}

    /**
     * @return array{expired: int, internal_offers: int, external_offers: int}
     */
    public function run(): array
    {
        if (! config('dispatch.enabled')) {
            return ['expired' => 0, 'internal_offers' => 0, 'external_offers' => 0];
        }

        $expired = $this->expireStaleOffers();
        $batchSize = max(1, (int) config('dispatch.batch_size', 100));
        $internalOffers = 0;
        $externalOffers = 0;

        Pedido::query()
            ->whereIn('estado', self::INTERNAL_ELIGIBLE_STATES)
            ->whereDoesntHave('deliveryOffers', fn ($query) => $query->where('status', 'accepted'))
            ->oldest()
            ->limit($batchSize)
            ->get()
            ->each(function (Pedido $pedido) use (&$internalOffers): void {
                $internalOffers += $this->dispatchPedido($pedido) !== null ? 1 : 0;
            });

        ExternalDeliveryOrder::query()
            ->whereIn('status', ['requested', 'offered'])
            ->oldest('requested_at')
            ->limit($batchSize)
            ->get()
            ->each(function (ExternalDeliveryOrder $order) use (&$externalOffers): void {
                $externalOffers += $this->dispatchExternal($order) !== null ? 1 : 0;
            });

        return [
            'expired' => $expired,
            'internal_offers' => $internalOffers,
            'external_offers' => $externalOffers,
        ];
    }

    public function dispatchPedido(Pedido $pedido): ?DeliveryOffer
    {
        if (! config('dispatch.enabled')) {
            return null;
        }

        return DB::transaction(function () use ($pedido): ?DeliveryOffer {
            $pedido = Pedido::query()
                ->with(['vendor', 'direccion', 'deliveryRoute'])
                ->lockForUpdate()
                ->findOrFail($pedido->id);

            if (! in_array($pedido->estadoValor(), self::INTERNAL_ELIGIBLE_STATES, true)) {
                return null;
            }

            $this->expirePendingForPedido($pedido);

            if ($this->hasAcceptedOrActiveOfferForPedido($pedido)) {
                return null;
            }

            $this->resetUnacceptedRoute($pedido);

            [$pickupLatitude, $pickupLongitude] = $this->pickupForPedido($pedido);
            $candidate = $this->bestCandidate(
                $pedido->vendor_id === null ? 'internal' : 'entrepreneurs',
                $pickupLatitude,
                $pickupLongitude,
                $this->recentlyOfferedCourierIds('pedido_id', $pedido->id)
            );

            if ($candidate === null) {
                return null;
            }

            $deliveryDistance = $this->deliveryDistanceForPedido($pedido, $pickupLatitude, $pickupLongitude);
            $route = $this->ensureUnassignedRoute($pedido, $pickupLatitude, $pickupLongitude, $deliveryDistance);
            $offer = $this->offerService->createForPedido($pedido->setRelation('deliveryRoute', $route), $candidate['user'], [
                'ttl_seconds' => max(30, (int) config('dispatch.offer_ttl_seconds', 90)),
                'pickup_distance_km' => $candidate['distance_km'],
                'total_distance_km' => round($candidate['distance_km'] + $deliveryDistance, 2),
            ]);

            $this->storeDispatchMetadata($offer, $candidate);

            return $offer->refresh();
        }, 3);
    }

    public function dispatchExternal(ExternalDeliveryOrder $order): ?DeliveryOffer
    {
        if (! config('dispatch.enabled')) {
            return null;
        }

        return DB::transaction(function () use ($order): ?DeliveryOffer {
            $order = ExternalDeliveryOrder::query()->lockForUpdate()->findOrFail($order->id);

            if (! in_array($order->status, ['requested', 'offered'], true)) {
                return null;
            }

            $this->expirePendingForExternal($order);

            if ($this->hasAcceptedOrActiveOfferForExternal($order)) {
                return null;
            }

            if ($order->status === 'offered' || $order->repartidor_id !== null) {
                $order->update(['status' => 'requested', 'repartidor_id' => null]);
            }

            if ($order->pickup_latitude === null || $order->pickup_longitude === null) {
                return null;
            }

            $candidate = $this->bestCandidate(
                'external',
                (float) $order->pickup_latitude,
                (float) $order->pickup_longitude,
                $this->recentlyOfferedCourierIds('external_delivery_order_id', $order->id)
            );

            if ($candidate === null) {
                $order->update(['status' => 'requested', 'repartidor_id' => null]);

                return null;
            }

            $deliveryDistance = (float) ($order->estimated_distance_km ?? 0);
            $offer = $this->offerService->createForExternal($order, $candidate['user'], [
                'ttl_seconds' => max(30, (int) config('dispatch.offer_ttl_seconds', 90)),
                'pickup_distance_km' => $candidate['distance_km'],
                'total_distance_km' => round($candidate['distance_km'] + $deliveryDistance, 2),
            ]);

            $this->storeDispatchMetadata($offer, $candidate);

            return $offer->refresh();
        }, 3);
    }

    public function expireStaleOffers(): int
    {
        $offers = DeliveryOffer::query()
            ->where('status', 'pending')
            ->where('expires_at', '<=', now())
            ->get();

        if ($offers->isEmpty()) {
            return 0;
        }

        DeliveryOffer::query()->whereKey($offers->modelKeys())->where('status', 'pending')->update(['status' => 'expired']);

        $offers->pluck('pedido_id')->filter()->unique()->each(function (int $pedidoId): void {
            DeliveryRoute::query()
                ->where('pedido_id', $pedidoId)
                ->whereNull('aceptada_at')
                ->update(['repartidor_id' => null, 'estado' => 'pendiente']);
        });

        $offers->pluck('external_delivery_order_id')->filter()->unique()->each(function (int $orderId): void {
            ExternalDeliveryOrder::query()
                ->whereKey($orderId)
                ->where('status', 'offered')
                ->update(['status' => 'requested', 'repartidor_id' => null]);
        });

        return $offers->count();
    }

    /**
     * @param  array<int, int>  $excludedCourierIds
     * @return array{user: User, distance_km: float, active_load: int, score: float, gps_at: string}|null
     */
    private function bestCandidate(string $sourceType, float $pickupLatitude, float $pickupLongitude, array $excludedCourierIds): ?array
    {
        $scopes = match ($sourceType) {
            'external' => ['external', 'both'],
            'internal' => ['internal', 'both'],
            default => ['entrepreneurs', 'both'],
        };

        $profiles = CourierProfile::query()
            ->available()
            ->whereIn('service_scope', $scopes)
            ->whereNotIn('user_id', $excludedCourierIds)
            ->whereHas('user', function ($query): void {
                $query->where('status', 'active')
                    ->whereHas('roles', fn ($roleQuery) => $roleQuery->where('name', 'repartidor'));
            })
            ->with('user')
            ->get();

        if ($profiles->isEmpty()) {
            return null;
        }

        $courierIds = $profiles->pluck('user_id');
        $locations = MarketCourierStatus::query()
            ->whereIn('repartidor_id', $courierIds)
            ->where('timestamp_gps', '>=', now()->subSeconds(max(30, (int) config('dispatch.gps_max_age_seconds', 180))))
            ->where('estado', '!=', 'fuera_servicio')
            ->where(function ($query): void {
                $query->whereNull('accuracy_meters')
                    ->orWhere('accuracy_meters', '<=', (float) config('dispatch.gps_max_accuracy_meters', 100));
            })
            ->latestGps()
            ->get()
            ->unique('repartidor_id')
            ->keyBy('repartidor_id');

        $pendingCourierIds = DeliveryOffer::query()
            ->active()
            ->whereIn('repartidor_id', $courierIds)
            ->pluck('repartidor_id')
            ->all();
        $loads = $this->activeLoads($courierIds);
        $maxLoad = max(1, (int) config('dispatch.max_active_deliveries', 1));
        $maxDistance = max(0.1, (float) config('dispatch.max_pickup_distance_km', 20));

        return $profiles
            ->reject(fn (CourierProfile $profile): bool => in_array($profile->user_id, $pendingCourierIds, true))
            ->map(function (CourierProfile $profile) use ($locations, $loads, $maxLoad, $maxDistance, $pickupLatitude, $pickupLongitude): ?array {
                $location = $locations->get($profile->user_id);
                $activeLoad = (int) ($loads[$profile->user_id] ?? 0);

                if ($location === null || $activeLoad >= $maxLoad) {
                    return null;
                }

                $distance = $this->etaCalculadorService->distanciaKm(
                    (float) $location->latitude,
                    (float) $location->longitude,
                    $pickupLatitude,
                    $pickupLongitude
                );

                if ($distance > $maxDistance) {
                    return null;
                }

                $score = ($distance * (float) config('dispatch.distance_weight', 10))
                    + ($activeLoad * (float) config('dispatch.load_weight', 25))
                    - ((float) $profile->rating * (float) config('dispatch.rating_weight', 0.5))
                    - ((float) $profile->completion_rate * (float) config('dispatch.completion_weight', 0.02));

                return [
                    'user' => $profile->user,
                    'distance_km' => $distance,
                    'active_load' => $activeLoad,
                    'score' => round($score, 4),
                    'gps_at' => $location->timestamp_gps->toIso8601String(),
                ];
            })
            ->filter()
            ->sort(function (array $left, array $right): int {
                $scoreComparison = $left['score'] <=> $right['score'];

                return $scoreComparison !== 0
                    ? $scoreComparison
                    : $left['user']->id <=> $right['user']->id;
            })
            ->first();
    }

    /** @param Collection<int, int> $courierIds */
    private function activeLoads(Collection $courierIds): array
    {
        $internal = DeliveryRoute::query()
            ->selectRaw('repartidor_id, COUNT(*) as aggregate')
            ->whereIn('repartidor_id', $courierIds)
            ->whereNotNull('aceptada_at')
            ->whereIn('estado', ['asignada', 'iniciada', 'pausada'])
            ->groupBy('repartidor_id')
            ->pluck('aggregate', 'repartidor_id');
        $external = ExternalDeliveryOrder::query()
            ->selectRaw('repartidor_id, COUNT(*) as aggregate')
            ->whereIn('repartidor_id', $courierIds)
            ->whereIn('status', ['accepted', 'assigned', 'arrived_pickup', 'pickup_not_ready', 'picked_up', 'arrived_customer'])
            ->groupBy('repartidor_id')
            ->pluck('aggregate', 'repartidor_id');

        return $courierIds->mapWithKeys(fn (int $courierId): array => [
            $courierId => (int) ($internal[$courierId] ?? 0) + (int) ($external[$courierId] ?? 0),
        ])->all();
    }

    /** @return array<int, int> */
    private function recentlyOfferedCourierIds(string $foreignKey, int $subjectId): array
    {
        return DeliveryOffer::query()
            ->where($foreignKey, $subjectId)
            ->where('created_at', '>=', now()->subMinutes(max(1, (int) config('dispatch.retry_courier_after_minutes', 15))))
            ->pluck('repartidor_id')
            ->map(fn ($id): int => (int) $id)
            ->all();
    }

    private function hasAcceptedOrActiveOfferForPedido(Pedido $pedido): bool
    {
        return DeliveryOffer::query()->where('pedido_id', $pedido->id)->where('status', 'accepted')->exists()
            || DeliveryOffer::query()->where('pedido_id', $pedido->id)->active()->exists()
            || ($pedido->deliveryRoute?->aceptada_at !== null);
    }

    private function hasAcceptedOrActiveOfferForExternal(ExternalDeliveryOrder $order): bool
    {
        return DeliveryOffer::query()->where('external_delivery_order_id', $order->id)->where('status', 'accepted')->exists()
            || DeliveryOffer::query()->where('external_delivery_order_id', $order->id)->active()->exists();
    }

    private function expirePendingForPedido(Pedido $pedido): void
    {
        DeliveryOffer::query()
            ->where('pedido_id', $pedido->id)
            ->where('status', 'pending')
            ->where('expires_at', '<=', now())
            ->update(['status' => 'expired']);
    }

    private function resetUnacceptedRoute(Pedido $pedido): void
    {
        DeliveryRoute::query()
            ->where('pedido_id', $pedido->id)
            ->whereNull('aceptada_at')
            ->update([
                'repartidor_id' => null,
                'estado' => 'pendiente',
                'asignada_at' => null,
            ]);
    }

    private function expirePendingForExternal(ExternalDeliveryOrder $order): void
    {
        $expired = DeliveryOffer::query()
            ->where('external_delivery_order_id', $order->id)
            ->where('status', 'pending')
            ->where('expires_at', '<=', now())
            ->update(['status' => 'expired']);

        if ($expired > 0 && $order->status === 'offered') {
            $order->update(['status' => 'requested', 'repartidor_id' => null]);
        }
    }

    /** @return array{0: float, 1: float} */
    private function pickupForPedido(Pedido $pedido): array
    {
        return [
            (float) ($pedido->vendor?->latitude ?? config('services.google_maps.default_lat', 15.7309)),
            (float) ($pedido->vendor?->longitude ?? config('services.google_maps.default_lng', -88.5944)),
        ];
    }

    private function deliveryDistanceForPedido(Pedido $pedido, float $pickupLatitude, float $pickupLongitude): float
    {
        if ($pedido->direccion?->latitude === null || $pedido->direccion?->longitude === null) {
            return (float) ($pedido->deliveryRoute?->distancia_km ?? 0);
        }

        return $this->etaCalculadorService->distanciaKm(
            $pickupLatitude,
            $pickupLongitude,
            (float) $pedido->direccion->latitude,
            (float) $pedido->direccion->longitude
        );
    }

    private function ensureUnassignedRoute(Pedido $pedido, float $pickupLatitude, float $pickupLongitude, float $deliveryDistance): DeliveryRoute
    {
        return DeliveryRoute::query()->updateOrCreate(
            ['pedido_id' => $pedido->id],
            [
                'uuid' => $pedido->deliveryRoute?->uuid ?? (string) Str::uuid(),
                'repartidor_id' => null,
                'source_type' => $pedido->vendor_id === null ? 'internal' : 'entrepreneurs',
                'pickup_name' => $pedido->vendor?->business_name ?? 'Atlantia Supermarket',
                'pickup_address' => $pedido->vendor?->direccion_comercial ?? $pedido->vendor?->municipio ?? 'Centro operativo Atlantia',
                'pickup_latitude' => $pickupLatitude,
                'pickup_longitude' => $pickupLongitude,
                'pickup_notes' => $pedido->vendor_id === null
                    ? 'Recoger en despacho interno. Oferta generada automaticamente.'
                    : 'Recoger pedido de emprendedor. Oferta generada automaticamente.',
                'distancia_km' => $deliveryDistance,
                'tiempo_estimado_min' => $this->etaCalculadorService->etaMinutos($deliveryDistance),
                'estado' => 'pendiente',
                'asignada_at' => null,
                'aceptada_at' => null,
                'estimated_earning' => round(max(15, ((float) $pedido->envio) * 0.75, 12 + ($deliveryDistance * 2.5)), 2),
                'cash_to_collect' => $pedido->metodoPagoValor() === 'efectivo' ? (float) $pedido->total : 0,
                'change_required' => $pedido->changeRequiredAmount(),
                'payment_method' => $pedido->metodoPagoValor(),
                'proof_type' => 'photo',
            ]
        );
    }

    /** @param array{user: User, distance_km: float, active_load: int, score: float, gps_at: string} $candidate */
    private function storeDispatchMetadata(DeliveryOffer $offer, array $candidate): void
    {
        $offer->update([
            'metadata' => array_merge($offer->metadata ?? [], [
                'dispatch' => [
                    'mode' => 'automatic',
                    'score' => $candidate['score'],
                    'active_load' => $candidate['active_load'],
                    'gps_at' => $candidate['gps_at'],
                ],
            ]),
        ]);
    }
}
