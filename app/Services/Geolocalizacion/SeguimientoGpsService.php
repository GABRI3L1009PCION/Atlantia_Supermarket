<?php

namespace App\Services\Geolocalizacion;

use App\Jobs\ProcesarDespachoAutomatico;
use App\Models\DeliveryRoute;
use App\Models\ExternalDeliveryOrder;
use App\Models\MarketCourierStatus;
use App\Models\Pedido;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Servicio de tracking GPS de repartidores.
 */
class SeguimientoGpsService
{
    /**
     * Registra una ubicacion GPS enviada por el repartidor.
     *
     * @param  array<string, mixed>  $data
     */
    public function storeLocation(User $repartidor, array $data): MarketCourierStatus
    {
        $status = DB::transaction(function () use ($repartidor, $data): MarketCourierStatus {
            $pedidoId = $this->resolvePedidoId($repartidor, $data);
            $externalOrderId = $this->resolveExternalOrderId($repartidor, $data);

            $status = MarketCourierStatus::query()->create([
                'repartidor_id' => $repartidor->id,
                'pedido_id' => $pedidoId,
                'external_delivery_order_id' => $externalOrderId,
                'latitude' => $data['latitude'],
                'longitude' => $data['longitude'],
                'timestamp_gps' => $data['timestamp_gps'] ?? now(),
                'estado' => $data['estado'] ?? 'en_ruta',
                'battery_level' => $data['battery_level'] ?? null,
                'accuracy_meters' => $data['accuracy_meters'] ?? null,
                'notas' => $data['notas'] ?? null,
            ]);

            $this->appendRutaRealInterna($status);
            $this->appendRutaRealExterna($status);

            return $status->refresh();
        });

        ProcesarDespachoAutomatico::dispatch();

        return $status;
    }

    /**
     * Obtiene ultima ubicacion del repartidor.
     */
    public function latestForCourier(User $repartidor): ?MarketCourierStatus
    {
        return MarketCourierStatus::query()
            ->forRepartidor($repartidor->id)
            ->latestGps()
            ->first();
    }

    /**
     * Obtiene historial GPS de un pedido.
     *
     * @return Collection<int, MarketCourierStatus>
     */
    public function historyForPedido(Pedido $pedido)
    {
        return MarketCourierStatus::query()
            ->forPedido($pedido->id)
            ->orderBy('timestamp_gps')
            ->get();
    }

    /**
     * Agrega punto GPS a la ruta real asociada.
     */
    private function appendRutaRealInterna(MarketCourierStatus $status): void
    {
        if ($status->pedido_id === null) {
            return;
        }

        $route = DeliveryRoute::query()->where('pedido_id', $status->pedido_id)->lockForUpdate()->first();

        if ($route === null) {
            return;
        }

        $rutaReal = $route->ruta_real ?? [];
        $rutaReal[] = [
            'latitude' => (float) $status->latitude,
            'longitude' => (float) $status->longitude,
            'timestamp_gps' => $status->timestamp_gps?->toIso8601String(),
            'estado' => $status->estado,
        ];

        $route->update(['ruta_real' => $rutaReal]);
    }

    /**
     * Agrega el punto a la ruta capturada de una entrega externa.
     */
    private function appendRutaRealExterna(MarketCourierStatus $status): void
    {
        if ($status->external_delivery_order_id === null) {
            return;
        }

        $order = ExternalDeliveryOrder::query()->lockForUpdate()->find($status->external_delivery_order_id);

        if ($order === null) {
            return;
        }

        $realPath = $order->real_path ?? [];
        $realPath[] = [
            'latitude' => (float) $status->latitude,
            'longitude' => (float) $status->longitude,
            'timestamp_gps' => $status->timestamp_gps?->toIso8601String(),
            'estado' => $status->estado,
        ];

        $order->update(['real_path' => $realPath]);
    }

    /**
     * Resuelve el UUID publico sin aceptar pedidos de otro repartidor.
     *
     * @param  array<string, mixed>  $data
     */
    private function resolvePedidoId(User $repartidor, array $data): ?int
    {
        if (isset($data['pedido_id'])) {
            return (int) $data['pedido_id'];
        }

        if (blank($data['pedido_uuid'] ?? null)) {
            return null;
        }

        $pedidoId = Pedido::query()
            ->where('uuid', $data['pedido_uuid'])
            ->whereHas('deliveryRoute', fn ($query) => $query->where('repartidor_id', $repartidor->id))
            ->value('id');

        if ($pedidoId === null) {
            throw ValidationException::withMessages([
                'pedido_uuid' => 'El pedido no esta asignado a tu ruta.',
            ]);
        }

        return (int) $pedidoId;
    }

    /**
     * Resuelve la entrega externa sin permitir seguimiento de otra cuenta.
     *
     * @param  array<string, mixed>  $data
     */
    private function resolveExternalOrderId(User $repartidor, array $data): ?int
    {
        if (blank($data['external_order_uuid'] ?? null)) {
            return null;
        }

        $orderId = ExternalDeliveryOrder::query()
            ->where('uuid', $data['external_order_uuid'])
            ->where('repartidor_id', $repartidor->id)
            ->whereNotIn('status', ['delivered', 'cancelled'])
            ->value('id');

        if ($orderId === null) {
            throw ValidationException::withMessages([
                'external_order_uuid' => 'La entrega externa no esta asignada a tu cuenta.',
            ]);
        }

        return (int) $orderId;
    }
}
