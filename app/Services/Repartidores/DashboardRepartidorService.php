<?php

namespace App\Services\Repartidores;

use App\Models\DeliveryRoute;
use App\Models\DeliveryZone;
use App\Models\ExternalDeliveryOrder;
use App\Models\User;

/**
 * Servicio de metricas del repartidor.
 */
class DashboardRepartidorService
{
    /**
     * Crea una instancia del servicio.
     */
    public function __construct(
        private readonly CourierProfileService $profileService,
        private readonly CourierWalletService $walletService,
        private readonly DeliveryOfferService $offerService
    ) {}

    /**
     * Devuelve resumen de entregas.
     *
     * @return array<string, mixed>
     */
    public function metrics(User $user): array
    {
        $profile = $this->profileService->refreshPerformance($user);
        $walletSummary = $this->walletService->summary($user);
        $activeExternal = ExternalDeliveryOrder::query()
            ->where('repartidor_id', $user->id)
            ->whereIn('status', ['accepted', 'arrived_pickup', 'pickup_not_ready', 'picked_up', 'arrived_customer'])
            ->latest()
            ->get();
        $activeOffers = $this->offerService->activeFor($user);
        $recentCompletedInternal = DeliveryRoute::query()
            ->with(['pedido.cliente', 'pedido.direccion', 'pedido.items.producto', 'pedido.vendor'])
            ->where('repartidor_id', $user->id)
            ->where('estado', 'completada')
            ->whereNull('completion_acknowledged_at')
            ->latest('completada_at')
            ->first();
        $recentCompletedExternal = ExternalDeliveryOrder::query()
            ->where('repartidor_id', $user->id)
            ->where('status', 'delivered')
            ->whereNull('completion_acknowledged_at')
            ->latest('delivered_at')
            ->first();

        return [
            'overview' => [
                'rutas_activas' => DeliveryRoute::query()->where('repartidor_id', $user->id)->activas()->count(),
                'externas_activas' => $activeExternal->count(),
                'ofertas_pendientes' => $activeOffers->count(),
                'entregas_hoy' => DeliveryRoute::query()
                    ->where('repartidor_id', $user->id)
                    ->whereDate('completada_at', today())
                    ->count(),
                'externas_hoy' => ExternalDeliveryOrder::query()
                    ->where('repartidor_id', $user->id)
                    ->whereDate('delivered_at', today())
                    ->count(),
                'pendientes' => DeliveryRoute::query()
                    ->where('repartidor_id', $user->id)
                    ->whereIn('estado', ['pendiente', 'asignada'])
                    ->count(),
                'en_ruta' => DeliveryRoute::query()
                    ->where('repartidor_id', $user->id)
                    ->where('estado', 'iniciada')
                    ->count(),
            ],
            'ruta_actual' => DeliveryRoute::query()
                ->with(['pedido.cliente', 'pedido.direccion', 'pedido.items.producto'])
                ->where('repartidor_id', $user->id)
                ->whereNotNull('aceptada_at')
                ->whereIn('estado', ['asignada', 'iniciada', 'pausada'])
                ->orderByRaw("CASE estado WHEN 'iniciada' THEN 0 WHEN 'asignada' THEN 1 ELSE 2 END")
                ->oldest('asignada_at')
                ->first(),
            'proximas_entregas' => DeliveryRoute::query()
                ->with(['pedido.cliente', 'pedido.direccion'])
                ->where('repartidor_id', $user->id)
                ->whereIn('estado', ['asignada', 'pendiente'])
                ->latest('asignada_at')
                ->limit(4)
                ->get(),
            'rutas_recientes' => DeliveryRoute::query()
                ->with('pedido.cliente')
                ->where('repartidor_id', $user->id)
                ->latest()
                ->limit(6)
                ->get(),
            'external_active' => $activeExternal,
            'recent_completed_internal' => $recentCompletedInternal,
            'recent_completed_external' => $recentCompletedExternal,
            'offers' => $activeOffers,
            'profile' => $profile,
            'reward' => $this->profileService->rewardProgress($profile),
            'wallet' => $walletSummary,
            'demand_zones' => DeliveryZone::query()
                ->where('activa', true)
                ->orderByDesc('costo_base')
                ->limit(5)
                ->get(),
            'promotions' => $this->promotionsFor($profile),
            'quick_links' => [
                ['title' => 'Entregas', 'description' => 'Consulta pedidos asignados y estados.', 'route' => route('repartidor.pedidos.index')],
                ['title' => 'Rutas', 'description' => 'Revisa rutas, distancia y tiempos.', 'route' => route('repartidor.rutas.index')],
                ['title' => 'Ganancias', 'description' => 'Billetera, efectivo y transferencias.', 'route' => route('repartidor.ganancias.index')],
                ['title' => 'Soporte', 'description' => 'Ayuda local y emergencias.', 'route' => route('repartidor.soporte.index')],
            ],
        ];
    }

    /**
     * Promociones operativas disponibles.
     *
     * @return array<int, array<string, string>>
     */
    private function promotionsFor($profile): array
    {
        $level = (string) $profile->reward_level;

        return [
            [
                'title' => 'Bono hora pico',
                'description' => 'Q 10 extra por entrega completada entre 11:30 y 14:00.',
            ],
            [
                'title' => 'Racha segura',
                'description' => 'Completa 5 entregas sin cancelacion para sumar puntos de recompensa.',
            ],
            [
                'title' => 'Nivel '.$level,
                'description' => 'Tu nivel actual participa en incentivos y prioridad de ofertas.',
            ],
        ];
    }
}
