<?php

namespace App\Services\Repartidores;

use App\Contracts\NotificacionContract;
use App\Enums\EstadoPedido;
use App\Events\RepartidorAsignado;
use App\Exceptions\TransaccionFallidaException;
use App\Jobs\ProcesarDespachoAutomatico;
use App\Models\DeliveryOffer;
use App\Models\DeliveryRoute;
use App\Models\ExternalDeliveryOrder;
use App\Models\Pedido;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Gestiona ofertas temporizadas de entrega.
 */
class DeliveryOfferService
{
    /**
     * Crea una instancia del servicio.
     */
    public function __construct(
        private readonly CourierProfileService $profileService,
        private readonly NotificacionContract $notificationService
    ) {}

    /**
     * Lista ofertas vigentes para el repartidor.
     *
     * @return Collection<int, DeliveryOffer>
     */
    public function activeFor(User $user): Collection
    {
        $this->expireStaleFor($user);

        return DeliveryOffer::query()
            ->with(['pedido.direccion', 'pedido.vendor', 'route', 'externalOrder'])
            ->where('repartidor_id', $user->id)
            ->active()
            ->latest()
            ->get();
    }

    /**
     * Crea oferta para pedido interno.
     *
     * @param  array<string, mixed>  $data
     */
    public function createForPedido(Pedido $pedido, User $repartidor, array $data = []): DeliveryOffer
    {
        $pedido->loadMissing(['deliveryRoute', 'direccion', 'vendor']);
        $route = $pedido->deliveryRoute;
        $estimatedGain = (float) ($data['estimated_gain'] ?? $route?->estimated_earning ?? max(15, ((float) $pedido->envio) * 0.75));

        $offer = DeliveryOffer::query()->updateOrCreate(
            [
                'pedido_id' => $pedido->id,
                'repartidor_id' => $repartidor->id,
                'status' => 'pending',
            ],
            [
                'uuid' => (string) Str::uuid(),
                'delivery_route_id' => $route?->id,
                'source_type' => $pedido->vendor_id === null ? 'internal' : 'entrepreneurs',
                'expires_at' => now()->addSeconds((int) ($data['ttl_seconds'] ?? 90)),
                'estimated_gain' => $estimatedGain,
                'pickup_distance_km' => $data['pickup_distance_km'] ?? null,
                'delivery_distance_km' => $route?->distancia_km,
                'total_distance_km' => $data['total_distance_km'] ?? $route?->distancia_km,
                'payment_method' => $pedido->metodoPagoValor(),
                'metadata' => [
                    'business_name' => $pedido->vendor?->business_name ?? 'Atlantia Supermarket',
                    'pickup_address' => $pedido->vendor?->direccion_comercial ?? $pedido->vendor?->municipio,
                    'delivery_zone' => $pedido->direccion?->municipio,
                ],
            ]
        );

        $this->maybeAutoAccept($offer, $repartidor);

        if ($offer->fresh()->status === 'pending') {
            $this->notifyOffer($offer->fresh(), $repartidor);
        }

        return $offer->refresh();
    }

    /**
     * Crea oferta para solicitud externa.
     *
     * @param  array<string, mixed>  $data
     */
    public function createForExternal(ExternalDeliveryOrder $order, User $repartidor, array $data = []): DeliveryOffer
    {
        $estimatedGain = (float) ($data['estimated_gain'] ?? $order->courier_earning);

        $offer = DeliveryOffer::query()->create([
            'uuid' => (string) Str::uuid(),
            'external_delivery_order_id' => $order->id,
            'repartidor_id' => $repartidor->id,
            'source_type' => 'external',
            'status' => 'pending',
            'expires_at' => now()->addSeconds((int) ($data['ttl_seconds'] ?? 90)),
            'estimated_gain' => $estimatedGain,
            'pickup_distance_km' => $data['pickup_distance_km'] ?? null,
            'delivery_distance_km' => $order->estimated_distance_km,
            'total_distance_km' => $data['total_distance_km'] ?? $order->estimated_distance_km,
            'payment_method' => $order->payment_method,
            'metadata' => [
                'business_name' => $order->store_name,
                'pickup_address' => $order->pickup_address,
                'delivery_zone' => $order->delivery_address,
            ],
        ]);

        $order->update([
            'status' => 'offered',
            'offered_at' => now(),
        ]);

        $this->maybeAutoAccept($offer, $repartidor);

        if ($offer->fresh()->status === 'pending') {
            $this->notifyOffer($offer->fresh(), $repartidor);
        }

        return $offer->refresh();
    }

    /**
     * Acepta una oferta vigente.
     */
    public function accept(DeliveryOffer $offer, User $user): DeliveryOffer
    {
        $this->expireIfStale($offer, $user);

        $acceptedOffer = DB::transaction(function () use ($offer, $user): DeliveryOffer {
            $offer = DeliveryOffer::query()->lockForUpdate()->findOrFail($offer->id);
            $this->assertOfferCanBeHandled($offer, $user);

            $offer->update([
                'status' => 'accepted',
                'accepted_at' => now(),
            ]);

            if ($offer->pedido_id !== null) {
                $this->acceptInternal($offer, $user);
            }

            if ($offer->external_delivery_order_id !== null) {
                $this->acceptExternal($offer, $user);
            }

            $this->cancelSiblingOffers($offer);
            $this->profileService->updateAvailability($user, ['availability_status' => 'busy']);
            $this->profileService->refreshPerformance($user);

            return $offer->refresh()->load(['pedido', 'externalOrder', 'route']);
        });

        if ($acceptedOffer->pedido !== null) {
            RepartidorAsignado::dispatch($acceptedOffer->pedido, $user);
        }

        return $acceptedOffer;
    }

    /**
     * Rechaza una oferta vigente.
     */
    public function reject(DeliveryOffer $offer, User $user, ?string $reason = null): DeliveryOffer
    {
        $this->expireIfStale($offer, $user);

        $rejectedOffer = DB::transaction(function () use ($offer, $user, $reason): DeliveryOffer {
            $offer = DeliveryOffer::query()->lockForUpdate()->findOrFail($offer->id);
            $this->assertOfferCanBeHandled($offer, $user);

            $offer->update([
                'status' => 'rejected',
                'rejected_at' => now(),
                'rejection_reason' => $reason,
            ]);

            $this->profileService->refreshPerformance($user);

            return $offer->refresh();
        });

        $this->queueRedispatch($rejectedOffer);

        return $rejectedOffer;
    }

    /**
     * Expira ofertas vencidas de un repartidor.
     */
    public function expireStaleFor(User $user): int
    {
        $offers = DeliveryOffer::query()
            ->where('repartidor_id', $user->id)
            ->where('status', 'pending')
            ->where('expires_at', '<=', now())
            ->get();

        $count = DeliveryOffer::query()
            ->whereKey($offers->modelKeys())
            ->where('status', 'pending')
            ->update(['status' => 'expired']);

        if ($count > 0) {
            $this->profileService->refreshPerformance($user);
            $offers->each(fn (DeliveryOffer $offer) => $this->queueRedispatch($offer));
        }

        return $count;
    }

    /**
     * Acepta oferta interna y asegura ruta.
     */
    private function acceptInternal(DeliveryOffer $offer, User $user): void
    {
        $pedido = Pedido::query()->lockForUpdate()->findOrFail($offer->pedido_id);
        $route = DeliveryRoute::query()->where('pedido_id', $pedido->id)->lockForUpdate()->first();

        if ($route === null) {
            throw new TransaccionFallidaException('El pedido aun no tiene una ruta asignada.');
        }

        if ($route->aceptada_at !== null && (int) $route->repartidor_id !== (int) $user->id) {
            throw new TransaccionFallidaException('La entrega ya fue aceptada por otro repartidor.');
        }

        if (DeliveryOffer::query()->where('pedido_id', $pedido->id)->where('status', 'accepted')->where('id', '!=', $offer->id)->exists()) {
            throw new TransaccionFallidaException('La entrega ya fue aceptada por otro repartidor.');
        }

        $route->update([
            'repartidor_id' => $user->id,
            'estado' => 'asignada',
            'asignada_at' => $route->asignada_at ?? now(),
            'aceptada_at' => $route->aceptada_at ?? now(),
            'estimated_earning' => $offer->estimated_gain,
            'confirmation_code' => $route->confirmation_code ?? (string) random_int(1000, 9999),
        ]);

        $offer->update(['delivery_route_id' => $route->id]);

        if ($pedido->estado === EstadoPedido::Confirmado) {
            $pedido->update(['estado' => EstadoPedido::EnPreparacion->value]);
        }

        $pedido->estados()->create([
            'estado' => $pedido->estadoValor(),
            'notas' => 'Oferta aceptada por el repartidor.',
            'usuario_id' => $user->id,
        ]);
    }

    /**
     * Acepta oferta externa.
     */
    private function acceptExternal(DeliveryOffer $offer, User $user): void
    {
        $order = ExternalDeliveryOrder::query()->lockForUpdate()->findOrFail($offer->external_delivery_order_id);

        if (! in_array($order->status, ['requested', 'offered'], true) && (int) $order->repartidor_id !== (int) $user->id) {
            throw new TransaccionFallidaException('La entrega externa ya fue aceptada por otro repartidor.');
        }

        if (DeliveryOffer::query()->where('external_delivery_order_id', $order->id)->where('status', 'accepted')->where('id', '!=', $offer->id)->exists()) {
            throw new TransaccionFallidaException('La entrega externa ya fue aceptada por otro repartidor.');
        }

        $order->update([
            'repartidor_id' => $user->id,
            'status' => 'accepted',
            'assigned_at' => $order->assigned_at ?? now(),
            'accepted_at' => now(),
            'courier_earning' => $offer->estimated_gain,
        ]);
    }

    /**
     * Cancela ofertas pendientes hermanas.
     */
    private function cancelSiblingOffers(DeliveryOffer $offer): void
    {
        DeliveryOffer::query()
            ->where('id', '!=', $offer->id)
            ->where('status', 'pending')
            ->when($offer->pedido_id !== null, fn ($query) => $query->where('pedido_id', $offer->pedido_id))
            ->when($offer->external_delivery_order_id !== null, fn ($query) => $query->where('external_delivery_order_id', $offer->external_delivery_order_id))
            ->update(['status' => 'cancelled']);
    }

    /**
     * Acepta automaticamente si el perfil lo permite.
     */
    private function maybeAutoAccept(DeliveryOffer $offer, User $user): void
    {
        $profile = $this->profileService->ensure($user);

        if (! $profile->auto_accept_enabled || $profile->availability_status !== 'available') {
            return;
        }

        $distance = (float) ($offer->total_distance_km ?? $offer->delivery_distance_km ?? 0);
        if ($distance > (float) $profile->auto_accept_max_distance_km) {
            return;
        }

        $this->accept($offer, $user);
    }

    /**
     * Valida que la oferta puede ser atendida por el repartidor.
     */
    private function assertOfferCanBeHandled(DeliveryOffer $offer, User $user): void
    {
        if ((int) $offer->repartidor_id !== (int) $user->id) {
            throw new TransaccionFallidaException('La oferta no pertenece a tu cuenta.');
        }

        if ($offer->status !== 'pending') {
            throw new TransaccionFallidaException('La oferta ya no esta disponible.');
        }

        if ($offer->expires_at->isPast()) {
            throw new TransaccionFallidaException('El tiempo para aceptar esta oferta vencio.');
        }
    }

    private function expireIfStale(DeliveryOffer $offer, User $user): void
    {
        $expiredOffer = DB::transaction(function () use ($offer, $user): ?DeliveryOffer {
            $offer = DeliveryOffer::query()->lockForUpdate()->findOrFail($offer->id);

            if ((int) $offer->repartidor_id !== (int) $user->id || $offer->status !== 'pending' || ! $offer->expires_at->isPast()) {
                return null;
            }

            $offer->update(['status' => 'expired']);

            return $offer->refresh();
        });

        if ($expiredOffer === null) {
            return;
        }

        $this->profileService->refreshPerformance($user);
        $this->queueRedispatch($expiredOffer);

        throw new TransaccionFallidaException('El tiempo para aceptar esta oferta vencio.');
    }

    private function queueRedispatch(DeliveryOffer $offer): void
    {
        ProcesarDespachoAutomatico::dispatch($offer->pedido_id, $offer->external_delivery_order_id);
    }

    private function notifyOffer(DeliveryOffer $offer, User $repartidor): void
    {
        $businessName = $offer->pedido?->vendor?->business_name
            ?? $offer->externalOrder?->store_name
            ?? 'Atlantia';

        $this->notificationService->enviar($repartidor, 'entrega.oferta', [
            'titulo' => 'Nueva oferta de entrega',
            'mensaje' => sprintf('Tienes una entrega disponible de %s.', $businessName),
            'offer_uuid' => $offer->uuid,
            'pedido_uuid' => $offer->pedido?->uuid,
            'external_order_uuid' => $offer->externalOrder?->uuid,
            'source_type' => $offer->source_type,
            'business_name' => $businessName,
            'estimated_gain' => (float) $offer->estimated_gain,
            'pickup_distance_km' => $offer->pickup_distance_km !== null ? (float) $offer->pickup_distance_km : null,
            'expires_at' => $offer->expires_at->toIso8601String(),
        ]);
    }
}
