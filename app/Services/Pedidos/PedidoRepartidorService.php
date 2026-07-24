<?php

namespace App\Services\Pedidos;

use App\Enums\EstadoPedido;
use App\Exceptions\TransaccionFallidaException;
use App\Models\DeliveryOffer;
use App\Models\DeliveryRoute;
use App\Models\Pedido;
use App\Models\User;
use App\Services\Repartidores\CourierProfileService;
use App\Services\Repartidores\CourierWalletService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Servicio de pedidos asignados al repartidor.
 */
class PedidoRepartidorService
{
    /**
     * Crea una instancia del servicio.
     */
    public function __construct(
        private readonly CourierWalletService $walletService,
        private readonly CourierProfileService $profileService,
        private readonly EstadoPedidoService $estadoPedidoService
    ) {}

    /**
     * Lista pedidos asignados.
     *
     * @return Collection<int, Pedido>
     */
    public function assigned(User $user): Collection
    {
        return Pedido::query()
            ->with(['direccion', 'vendor', 'deliveryRoute', 'deliveryOffers'])
            ->whereHas('deliveryRoute', fn ($query) => $query->where('repartidor_id', $user->id))
            ->latest()
            ->get();
    }

    /**
     * Detalle de pedido asignado.
     */
    public function detail(Pedido $pedido): Pedido
    {
        $pedido = $pedido->load(['direccion', 'items.producto', 'deliveryRoute.offers', 'cliente', 'vendor', 'deliveryOffers']);
        $route = $pedido->deliveryRoute;

        if (
            $route !== null
            && $pedido->estadoValor() === EstadoPedido::EnRuta->value
            && $route->picked_up_at !== null
            && $route->confirmation_code === null
        ) {
            $route->update(['confirmation_code' => $this->deliveryCode()]);
            $pedido->setRelation('deliveryRoute', $route->refresh()->load('offers'));
        }

        return $pedido;
    }

    /**
     * Actualiza estado de entrega usando transiciones seguras.
     *
     * @param  array<string, mixed>  $data
     */
    public function updateEstado(Pedido $pedido, array $data, User $user): Pedido
    {
        return match ($data['estado']) {
            EstadoPedido::EnRuta->value => $this->pickup($pedido, $user, $data),
            EstadoPedido::Entregado->value => $this->deliver($pedido, $data, $user),
            'incidencia' => $this->reportIncidentState($pedido, $data, $user),
            default => throw new TransaccionFallidaException('El estado solicitado no es valido para reparto.'),
        };
    }

    /**
     * Rechaza una entrega u oferta asignada.
     *
     * @param  array<string, mixed>  $data
     */
    public function reject(Pedido $pedido, array $data, User $user): Pedido
    {
        return DB::transaction(function () use ($pedido, $data, $user): Pedido {
            $route = $this->assignedRoute($pedido, $user);

            if ($route->aceptada_at !== null || $route->estado === 'iniciada') {
                throw new TransaccionFallidaException('No puedes rechazar una entrega que ya aceptaste o iniciaste.');
            }

            DeliveryOffer::query()
                ->where('pedido_id', $pedido->id)
                ->where('repartidor_id', $user->id)
                ->where('status', 'pending')
                ->update([
                    'status' => 'rejected',
                    'rejected_at' => now(),
                    'rejection_reason' => $data['reason'] ?? null,
                ]);

            $route->update(['estado' => 'cancelada']);

            $pedido->estados()->create([
                'estado' => $pedido->estadoValor(),
                'notas' => 'Entrega rechazada por el repartidor: '.($data['reason'] ?? 'Sin motivo especifico.'),
                'usuario_id' => $user->id,
            ]);

            $this->profileService->refreshPerformance($user);

            return $this->detail($pedido->fresh());
        });
    }

    /**
     * Acepta una entrega asignada al repartidor.
     */
    public function accept(Pedido $pedido, User $user): Pedido
    {
        return DB::transaction(function () use ($pedido, $user): Pedido {
            $route = $this->assignedRoute($pedido, $user);

            if (! in_array($route->estado, ['pendiente', 'asignada'], true)) {
                throw new TransaccionFallidaException('La entrega no puede aceptarse en su estado actual.');
            }

            $route->update($this->routeOperationalDefaults($pedido, [
                'estado' => 'asignada',
                'aceptada_at' => $route->aceptada_at ?? now(),
                'estimated_earning' => $route->estimated_earning ?: $this->defaultEarning($pedido, $route),
                'payment_method' => $pedido->metodoPagoValor(),
                'cash_to_collect' => $pedido->metodoPagoValor() === 'efectivo' ? (float) $pedido->total : 0,
                'change_required' => $pedido->changeRequiredAmount(),
                'confirmation_code' => $route->confirmation_code ?? $this->deliveryCode(),
            ]));

            DeliveryOffer::query()
                ->where('pedido_id', $pedido->id)
                ->where('repartidor_id', $user->id)
                ->where('status', 'pending')
                ->update([
                    'status' => 'accepted',
                    'accepted_at' => now(),
                    'delivery_route_id' => $route->id,
                ]);

            if ($pedido->estado === EstadoPedido::Confirmado) {
                $pedido = $this->estadoPedidoService->registrar(
                    $pedido,
                    EstadoPedido::EnPreparacion,
                    'Entrega aceptada por el repartidor. El comercio prepara el pedido.',
                    $user
                );
            } else {
                $pedido->estados()->create([
                    'estado' => $pedido->estadoValor(),
                    'notas' => 'Entrega aceptada por el repartidor.',
                    'usuario_id' => $user->id,
                ]);
            }

            $this->profileService->updateAvailability($user, ['availability_status' => 'busy']);
            $this->profileService->refreshPerformance($user);

            return $this->detail($pedido->fresh());
        });
    }

    /**
     * Marca llegada al comercio.
     */
    public function arrivedPickup(Pedido $pedido, User $user): Pedido
    {
        return DB::transaction(function () use ($pedido, $user): Pedido {
            $route = $this->assignedRoute($pedido, $user);
            $this->assertAccepted($route);

            if ($route->picked_up_at !== null) {
                throw new TransaccionFallidaException('El pedido ya fue recogido.');
            }

            $route->update(['arrived_pickup_at' => $route->arrived_pickup_at ?? now()]);
            $pedido->estados()->create([
                'estado' => $pedido->estadoValor(),
                'notas' => 'El repartidor llego al establecimiento.',
                'usuario_id' => $user->id,
            ]);

            return $this->detail($pedido->fresh());
        });
    }

    /**
     * Reporta que el pedido aun no esta listo.
     */
    public function pickupNotReady(Pedido $pedido, User $user, ?string $reason = null): Pedido
    {
        return DB::transaction(function () use ($pedido, $user, $reason): Pedido {
            $route = $this->assignedRoute($pedido, $user);
            $this->assertAccepted($route);

            if ($route->picked_up_at !== null) {
                throw new TransaccionFallidaException('El pedido ya fue recogido.');
            }

            $route->update([
                'pickup_not_ready_at' => now(),
                'pickup_issue_reason' => $reason,
            ]);

            $pedido->estados()->create([
                'estado' => 'incidencia',
                'notas' => 'Pedido no listo en comercio: '.($reason ?: 'Sin detalle.'),
                'usuario_id' => $user->id,
            ]);

            return $this->detail($pedido->fresh());
        });
    }

    /**
     * Marca el pedido como recogido e inicia la ruta hacia el cliente.
     *
     * @param  array<string, mixed>  $data
     */
    public function pickup(Pedido $pedido, User $user, array $data = []): Pedido
    {
        return DB::transaction(function () use ($pedido, $user, $data): Pedido {
            $route = $this->assignedRoute($pedido, $user);
            $this->assertAccepted($route);

            if ($pedido->estadoValor() !== EstadoPedido::ListoParaEntrega->value) {
                throw new TransaccionFallidaException('El comercio aun no marco este pedido como listo para entregar.');
            }

            if ($route->estado === 'iniciada' || $route->picked_up_at !== null) {
                throw new TransaccionFallidaException('El pedido ya fue recogido.');
            }

            $route->update([
                'estado' => 'iniciada',
                'aceptada_at' => $route->aceptada_at ?? now(),
                'arrived_pickup_at' => $route->arrived_pickup_at ?? now(),
                'picked_up_at' => now(),
                'iniciada_at' => $route->iniciada_at ?? now(),
                'confirmation_code' => $route->confirmation_code ?? $this->deliveryCode(),
                'ruta_real' => $this->appendGpsPoint($route->ruta_real, $data, 'pickup'),
            ]);

            $pedido = $this->estadoPedidoService->registrar(
                $pedido,
                EstadoPedido::EnRuta,
                'Pedido recogido por el repartidor y en camino al cliente.',
                $user
            );

            return $this->detail($pedido->fresh());
        });
    }

    /**
     * Marca llegada al cliente.
     */
    public function arrivedCustomer(Pedido $pedido, User $user): Pedido
    {
        return DB::transaction(function () use ($pedido, $user): Pedido {
            $route = $this->assignedRoute($pedido, $user);

            if ($route->estado !== 'iniciada' || $pedido->estadoValor() !== EstadoPedido::EnRuta->value) {
                throw new TransaccionFallidaException('Primero debes recoger el pedido e iniciar la ruta.');
            }

            $route->update([
                'arrived_customer_at' => $route->arrived_customer_at ?? now(),
                'confirmation_code' => $route->confirmation_code ?? $this->deliveryCode(),
            ]);
            $pedido->estados()->create([
                'estado' => $pedido->estadoValor(),
                'notas' => 'El repartidor llego al punto de entrega del cliente.',
                'usuario_id' => $user->id,
            ]);

            return $this->detail($pedido->fresh());
        });
    }

    /**
     * Verifica el codigo del cliente sin cerrar la entrega.
     */
    public function verifyDeliveryCode(Pedido $pedido, string $confirmationCode, User $user): Pedido
    {
        return DB::transaction(function () use ($pedido, $confirmationCode, $user): Pedido {
            $route = $this->assignedRoute($pedido, $user);

            if ($route->estado !== 'iniciada' || $pedido->estadoValor() !== EstadoPedido::EnRuta->value) {
                throw new TransaccionFallidaException('La verificacion solo puede hacerse cuando el pedido esta en ruta.');
            }

            if ($route->arrived_customer_at === null) {
                throw new TransaccionFallidaException('Primero marca que llegaste con el cliente.');
            }

            if ($route->confirmation_code === null) {
                $route->update(['confirmation_code' => $this->deliveryCode()]);
                $route->refresh();
            }

            if ((string) $confirmationCode !== (string) $route->confirmation_code) {
                throw new TransaccionFallidaException('El codigo de confirmacion no coincide.');
            }

            $route->update([
                'delivered_code_confirmed_at' => $route->delivered_code_confirmed_at ?? now(),
            ]);

            $pedido->estados()->create([
                'estado' => $pedido->estadoValor(),
                'notas' => 'Codigo de entrega verificado por el repartidor.',
                'usuario_id' => $user->id,
            ]);

            return $this->detail($pedido->fresh());
        });
    }

    /**
     * Completa la entrega con evidencia opcional.
     *
     * @param  array<string, mixed>  $data
     */
    public function deliver(Pedido $pedido, array $data, User $user): Pedido
    {
        return DB::transaction(function () use ($pedido, $data, $user): Pedido {
            $route = $this->assignedRoute($pedido, $user);

            if ($route->estado !== 'iniciada' || $pedido->estadoValor() !== EstadoPedido::EnRuta->value) {
                throw new TransaccionFallidaException('La entrega solo puede completarse cuando el pedido esta en ruta.');
            }

            if ($route->arrived_customer_at === null) {
                throw new TransaccionFallidaException('Primero marca que llegaste con el cliente.');
            }

            if ($route->confirmation_code === null) {
                $route->update(['confirmation_code' => $this->deliveryCode()]);
                $route->refresh();
            }

            if ($route->confirmation_code !== null && $route->delivered_code_confirmed_at === null && blank($data['confirmation_code'] ?? null)) {
                throw new TransaccionFallidaException('Pide al cliente el codigo de entrega para completar el pedido.');
            }

            if ($route->confirmation_code !== null && $route->delivered_code_confirmed_at === null && (string) $data['confirmation_code'] !== (string) $route->confirmation_code) {
                throw new TransaccionFallidaException('El codigo de confirmacion no coincide.');
            }

            $evidencePath = $route->foto_entrega_path;

            if (($data['foto_entrega'] ?? null) !== null) {
                $evidencePath = $data['foto_entrega']->store('entregas', config('filesystems.private_disk', 'local'));
            }

            $startedAt = $route->iniciada_at ?? $route->asignada_at ?? now();

            $route->update([
                'estado' => 'completada',
                'completada_at' => now(),
                'tiempo_real_min' => max(1, (int) $startedAt->diffInMinutes(now())),
                'foto_entrega_path' => $evidencePath,
                'delivered_code_confirmed_at' => $route->confirmation_code !== null ? now() : $route->delivered_code_confirmed_at,
            ]);

            $pedido = $this->estadoPedidoService->registrar(
                $pedido,
                EstadoPedido::Entregado,
                $data['notas'] ?? 'Pedido entregado al cliente.',
                $user
            );

            $this->walletService->recordInternalDelivery($user, $pedido, $route->refresh());
            $this->profileService->updateAvailability($user, ['availability_status' => 'available']);
            $this->profileService->refreshPerformance($user);

            return $this->detail($pedido->fresh());
        });
    }

    /**
     * Confirma que la app ya mostro el cierre de la entrega.
     */
    public function acknowledgeCompletion(Pedido $pedido, User $user): Pedido
    {
        $route = DeliveryRoute::query()
            ->where('pedido_id', $pedido->id)
            ->where('repartidor_id', $user->id)
            ->first();

        if ($route === null) {
            throw new TransaccionFallidaException('Este pedido no esta asignado a tu cuenta.');
        }

        if ($route->estado !== 'completada' || $pedido->estadoValor() !== EstadoPedido::Entregado->value) {
            throw new TransaccionFallidaException('La entrega aun no esta completada.');
        }

        $route->update([
            'completion_acknowledged_at' => $route->completion_acknowledged_at ?? now(),
        ]);

        return $this->detail($pedido->fresh());
    }

    /**
     * Registra estado de incidencia sin cerrar el pedido.
     *
     * @param  array<string, mixed>  $data
     */
    private function reportIncidentState(Pedido $pedido, array $data, User $user): Pedido
    {
        $this->assignedRoute($pedido, $user);
        $pedido->estados()->create([
            'estado' => 'incidencia',
            'notas' => $data['notas'] ?? 'Incidencia reportada por el repartidor.',
            'usuario_id' => $user->id,
        ]);

        return $this->detail($pedido->fresh());
    }

    /**
     * Obtiene ruta asignada y valida ownership.
     */
    private function assignedRoute(Pedido $pedido, User $user): DeliveryRoute
    {
        $pedido->loadMissing('deliveryRoute');
        $route = $pedido->deliveryRoute;

        if ($route === null || (int) $route->repartidor_id !== (int) $user->id) {
            throw new TransaccionFallidaException('Este pedido no esta asignado a tu cuenta.');
        }

        if (in_array($pedido->estadoValor(), [EstadoPedido::Cancelado->value, EstadoPedido::Entregado->value], true)) {
            throw new TransaccionFallidaException('El pedido ya esta cerrado.');
        }

        if (in_array($route->estado, ['cancelada', 'completada'], true)) {
            throw new TransaccionFallidaException('La ruta ya esta cerrada.');
        }

        return $route;
    }

    /**
     * Valida aceptacion previa.
     */
    private function assertAccepted(DeliveryRoute $route): void
    {
        if ($route->aceptada_at === null) {
            throw new TransaccionFallidaException('Primero debes aceptar la entrega.');
        }
    }

    /**
     * Defaults operativos derivados de pedido/vendor.
     *
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function routeOperationalDefaults(Pedido $pedido, array $overrides = []): array
    {
        $pedido->loadMissing(['vendor', 'direccion']);
        $vendor = $pedido->vendor;

        return array_merge([
            'source_type' => $vendor === null ? 'internal' : 'entrepreneurs',
            'pickup_name' => $vendor?->business_name ?? 'Atlantia Supermarket',
            'pickup_address' => $vendor?->direccion_comercial ?? $vendor?->municipio ?? 'Centro operativo Atlantia',
            'pickup_latitude' => $vendor?->latitude,
            'pickup_longitude' => $vendor?->longitude,
            'pickup_notes' => $vendor === null ? 'Recoger en despacho interno.' : 'Recoger pedido de emprendedor.',
            'payment_method' => $pedido->metodoPagoValor(),
        ], $overrides);
    }

    /**
     * Agrega punto GPS a ruta real si fue capturado.
     *
     * @param  array<string, mixed>|null  $current
     * @param  array<string, mixed>  $data
     * @return array<int, array<string, mixed>>|null
     */
    private function appendGpsPoint(?array $current, array $data, string $type): ?array
    {
        $latitude = $data['latitude_inicio'] ?? $data['latitude'] ?? null;
        $longitude = $data['longitude_inicio'] ?? $data['longitude'] ?? null;

        if (! is_numeric($latitude) || ! is_numeric($longitude)) {
            return $current;
        }

        $points = array_values($current ?? []);
        $points[] = [
            'type' => $type,
            'latitude' => (float) $latitude,
            'longitude' => (float) $longitude,
            'captured_at' => now()->toIso8601String(),
        ];

        return $points;
    }

    /**
     * Calcula ganancia base si la ruta no trae una configurada.
     */
    private function defaultEarning(Pedido $pedido, DeliveryRoute $route): float
    {
        $distanceBonus = ((float) $route->distancia_km) * 2.5;
        $deliveryFeeShare = ((float) $pedido->envio) * 0.75;

        return round(max(15, $deliveryFeeShare, 12 + $distanceBonus), 2);
    }

    /**
     * Genera un codigo corto que el cliente debe compartir al recibir.
     */
    private function deliveryCode(): string
    {
        return (string) random_int(1000, 9999);
    }
}
