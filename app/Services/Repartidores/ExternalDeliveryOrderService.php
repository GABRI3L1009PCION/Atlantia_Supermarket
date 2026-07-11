<?php

namespace App\Services\Repartidores;

use App\Exceptions\TransaccionFallidaException;
use App\Jobs\ProcesarDespachoAutomatico;
use App\Models\ExternalDeliveryOrder;
use App\Models\User;
use App\Services\Geolocalizacion\EtaCalculadorService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Gestiona entregas solicitadas por tiendas externas en linea.
 */
class ExternalDeliveryOrderService
{
    /**
     * Crea una instancia del servicio.
     */
    public function __construct(
        private readonly EtaCalculadorService $etaCalculadorService,
        private readonly CourierWalletService $walletService,
        private readonly CourierProfileService $profileService,
        private readonly DeliveryOfferService $offerService
    ) {}

    /**
     * Pagina solicitudes externas para administracion.
     *
     * @param  array<string, mixed>  $filters
     */
    public function paginate(array $filters = []): LengthAwarePaginator
    {
        return ExternalDeliveryOrder::query()
            ->with('repartidor')
            ->when($filters['q'] ?? null, function ($query, string $search): void {
                $query->where(function ($builder) use ($search): void {
                    $builder->where('store_name', 'like', '%'.$search.'%')
                        ->orWhere('customer_name', 'like', '%'.$search.'%')
                        ->orWhere('external_reference', 'like', '%'.$search.'%')
                        ->orWhere('uuid', 'like', '%'.$search.'%');
                });
            })
            ->when($filters['status'] ?? null, fn ($query, string $status) => $query->where('status', $status))
            ->latest()
            ->paginate(25)
            ->withQueryString();
    }

    /**
     * Crea una solicitud externa.
     *
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): ExternalDeliveryOrder
    {
        $distanceKm = $this->estimateDistance($data);
        $deliveryFee = (float) ($data['delivery_fee'] ?? $this->defaultDeliveryFee($distanceKm));
        $courierEarning = (float) ($data['courier_earning'] ?? max(18, round($deliveryFee * 0.7, 2)));

        $order = ExternalDeliveryOrder::query()->create([
            'uuid' => (string) Str::uuid(),
            'source_channel' => $data['source_channel'] ?? 'manual',
            'external_reference' => $data['external_reference'] ?? 'EXT-'.now()->format('YmdHis'),
            'store_name' => $data['store_name'],
            'store_contact_name' => $data['store_contact_name'] ?? null,
            'store_phone' => $data['store_phone'] ?? null,
            'store_email' => $data['store_email'] ?? null,
            'pickup_address' => $data['pickup_address'],
            'pickup_latitude' => $data['pickup_latitude'] ?? null,
            'pickup_longitude' => $data['pickup_longitude'] ?? null,
            'pickup_notes' => $data['pickup_notes'] ?? null,
            'customer_name' => $data['customer_name'],
            'customer_phone' => $data['customer_phone'] ?? null,
            'delivery_address' => $data['delivery_address'],
            'delivery_latitude' => $data['delivery_latitude'] ?? null,
            'delivery_longitude' => $data['delivery_longitude'] ?? null,
            'delivery_notes' => $data['delivery_notes'] ?? null,
            'payment_method' => $data['payment_method'] ?? 'digital',
            'amount_to_collect' => $data['amount_to_collect'] ?? 0,
            'amount_to_pay_store' => $data['amount_to_pay_store'] ?? 0,
            'change_required' => $data['change_required'] ?? 0,
            'delivery_fee' => $deliveryFee,
            'courier_earning' => $courierEarning,
            'tip_amount' => $data['tip_amount'] ?? 0,
            'estimated_distance_km' => $distanceKm,
            'estimated_time_min' => $this->etaCalculadorService->etaMinutos($distanceKm, 2),
            'status' => 'requested',
            'requested_at' => now(),
            'confirmation_code' => (string) random_int(1000, 9999),
            'metadata' => $data['metadata'] ?? null,
        ]);

        ProcesarDespachoAutomatico::dispatch(null, $order->id);

        return $order;
    }

    /**
     * Asigna u oferta una solicitud externa a un repartidor.
     *
     * @param  array<string, mixed>  $data
     */
    public function assign(ExternalDeliveryOrder $order, User $repartidor, array $data = []): ExternalDeliveryOrder
    {
        return DB::transaction(function () use ($order, $repartidor, $data): ExternalDeliveryOrder {
            if (in_array($order->status, ['delivered', 'cancelled'], true)) {
                throw new TransaccionFallidaException('La solicitud externa ya no puede asignarse.');
            }

            $order->update([
                'repartidor_id' => $repartidor->id,
                'status' => ($data['direct_accept'] ?? false) ? 'accepted' : 'offered',
                'assigned_at' => now(),
                'accepted_at' => ($data['direct_accept'] ?? false) ? now() : $order->accepted_at,
            ]);

            $this->offerService->createForExternal($order->refresh(), $repartidor, [
                'estimated_gain' => $data['estimated_gain'] ?? $order->courier_earning,
                'ttl_seconds' => $data['ttl_seconds'] ?? 90,
            ]);

            return $order->refresh()->load('repartidor');
        });
    }

    /**
     * Lista solicitudes externas asignadas al repartidor.
     *
     * @return Collection<int, ExternalDeliveryOrder>
     */
    public function assignedTo(User $user): Collection
    {
        return ExternalDeliveryOrder::query()
            ->where('repartidor_id', $user->id)
            ->active()
            ->latest()
            ->get();
    }

    /**
     * Carga detalle.
     */
    public function detail(ExternalDeliveryOrder $order): ExternalDeliveryOrder
    {
        return $order->load(['repartidor', 'offers.repartidor']);
    }

    /**
     * Marca llegada al comercio.
     */
    public function arrivedPickup(ExternalDeliveryOrder $order, User $user): ExternalDeliveryOrder
    {
        return $this->transition($order, $user, ['accepted', 'assigned'], [
            'status' => 'arrived_pickup',
            'arrived_pickup_at' => now(),
        ]);
    }

    /**
     * Reporta que el pedido externo no esta listo.
     */
    public function pickupNotReady(ExternalDeliveryOrder $order, User $user, ?string $reason = null): ExternalDeliveryOrder
    {
        return $this->transition($order, $user, ['accepted', 'arrived_pickup'], [
            'status' => 'pickup_not_ready',
            'pickup_not_ready_at' => now(),
            'pickup_issue_reason' => $reason,
        ]);
    }

    /**
     * Marca pedido externo recogido.
     */
    public function pickedUp(ExternalDeliveryOrder $order, User $user): ExternalDeliveryOrder
    {
        return $this->transition($order, $user, ['arrived_pickup', 'pickup_not_ready'], [
            'status' => 'picked_up',
            'picked_up_at' => now(),
        ]);
    }

    /**
     * Marca llegada al cliente.
     */
    public function arrivedCustomer(ExternalDeliveryOrder $order, User $user): ExternalDeliveryOrder
    {
        return $this->transition($order, $user, ['picked_up'], [
            'status' => 'arrived_customer',
            'arrived_customer_at' => now(),
        ]);
    }

    /**
     * Reporta falta de efectivo.
     */
    public function reportCashIssue(ExternalDeliveryOrder $order, User $user, ?string $notes = null): ExternalDeliveryOrder
    {
        $this->assertAssigned($order, $user);
        $order->update([
            'cash_issue_reported_at' => now(),
            'cash_issue_notes' => $notes,
        ]);

        return $order->refresh();
    }

    /**
     * Entrega solicitud externa con evidencia.
     *
     * @param  array<string, mixed>  $data
     */
    public function deliver(ExternalDeliveryOrder $order, User $user, array $data = []): ExternalDeliveryOrder
    {
        return DB::transaction(function () use ($order, $user, $data): ExternalDeliveryOrder {
            $this->assertAssigned($order, $user);

            if ($order->status !== 'arrived_customer') {
                throw new TransaccionFallidaException('Primero debes marcar que llegaste con el cliente.');
            }

            if ($order->confirmation_code === null) {
                $order->confirmation_code = (string) random_int(1000, 9999);
                $order->save();
            }

            if ($order->confirmation_code !== null && blank($data['confirmation_code'] ?? null)) {
                throw new TransaccionFallidaException('Pide al cliente el codigo de entrega para completar el pedido.');
            }

            if ($order->confirmation_code !== null) {
                if ((string) $data['confirmation_code'] !== (string) $order->confirmation_code) {
                    throw new TransaccionFallidaException('El codigo de confirmacion no coincide.');
                }

                $order->confirmation_code_verified_at = now();
            }

            $proofPath = $order->proof_photo_path;
            if (($data['proof_photo'] ?? null) !== null) {
                $proofPath = $data['proof_photo']->store('entregas-externas', config('filesystems.private_disk', 'local'));
            }

            $order->fill([
                'status' => 'delivered',
                'delivered_at' => now(),
                'proof_photo_path' => $proofPath,
            ])->save();

            $this->walletService->recordExternalDelivery($user, $order->refresh());
            $this->profileService->updateAvailability($user, ['availability_status' => 'available']);
            $this->profileService->refreshPerformance($user);

            return $order->refresh();
        });
    }

    /**
     * Cancela solicitud externa.
     */
    public function cancel(ExternalDeliveryOrder $order, ?string $reason = null): ExternalDeliveryOrder
    {
        if ($order->status === 'delivered') {
            throw new TransaccionFallidaException('Una entrega finalizada no puede cancelarse.');
        }

        $order->update([
            'status' => 'cancelled',
            'cancelled_at' => now(),
            'metadata' => array_merge($order->metadata ?? [], ['cancellation_reason' => $reason]),
        ]);

        return $order->refresh();
    }

    /**
     * Aplica transicion validada.
     *
     * @param  array<int, string>  $allowedStatuses
     * @param  array<string, mixed>  $payload
     */
    private function transition(ExternalDeliveryOrder $order, User $user, array $allowedStatuses, array $payload): ExternalDeliveryOrder
    {
        $this->assertAssigned($order, $user);

        if (! in_array($order->status, $allowedStatuses, true)) {
            throw new TransaccionFallidaException('La entrega externa no puede avanzar desde su estado actual.');
        }

        $order->update($payload);

        return $order->refresh();
    }

    /**
     * Valida ownership operativo.
     */
    private function assertAssigned(ExternalDeliveryOrder $order, User $user): void
    {
        if ((int) $order->repartidor_id !== (int) $user->id) {
            throw new TransaccionFallidaException('Esta entrega externa no esta asignada a tu cuenta.');
        }

        if (in_array($order->status, ['delivered', 'cancelled'], true)) {
            throw new TransaccionFallidaException('La entrega externa ya esta cerrada.');
        }
    }

    /**
     * Estima distancia entre tienda y cliente si hay coordenadas.
     *
     * @param  array<string, mixed>  $data
     */
    private function estimateDistance(array $data): float
    {
        if (
            is_numeric($data['pickup_latitude'] ?? null)
            && is_numeric($data['pickup_longitude'] ?? null)
            && is_numeric($data['delivery_latitude'] ?? null)
            && is_numeric($data['delivery_longitude'] ?? null)
        ) {
            return $this->etaCalculadorService->distanciaKm(
                (float) $data['pickup_latitude'],
                (float) $data['pickup_longitude'],
                (float) $data['delivery_latitude'],
                (float) $data['delivery_longitude']
            );
        }

        return 4.0;
    }

    /**
     * Tarifa base para tiendas externas.
     */
    private function defaultDeliveryFee(float $distanceKm): float
    {
        return round(max(25, 15 + ($distanceKm * 4.5)), 2);
    }
}
