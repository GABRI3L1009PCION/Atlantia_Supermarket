<?php

namespace App\Services\Repartidores;

use App\Models\CourierProfile;
use App\Models\DeliveryOffer;
use App\Models\DeliveryRoute;
use App\Models\ExternalDeliveryOrder;
use App\Models\User;
use Illuminate\Support\Arr;

/**
 * Gestiona disponibilidad, alcance y desempeno del repartidor.
 */
class CourierProfileService
{
    /**
     * Devuelve o crea el perfil operativo base.
     */
    public function ensure(User $user): CourierProfile
    {
        return CourierProfile::query()->firstOrCreate(
            ['user_id' => $user->id],
            [
                'availability_status' => 'offline',
                'service_scope' => 'both',
                'reward_level' => 'Aprendiz',
                'reward_points' => 0,
                'rating' => 5,
                'acceptance_rate' => 100,
                'completion_rate' => 100,
                'auto_accept_enabled' => false,
                'auto_accept_max_distance_km' => 5,
                'safe_zones' => [],
                'insurance_active' => true,
            ]
        );
    }

    /**
     * Actualiza disponibilidad y alcance de servicio.
     *
     * @param  array<string, mixed>  $data
     */
    public function updateAvailability(User $user, array $data): CourierProfile
    {
        $profile = $this->ensure($user);
        $status = (string) $data['availability_status'];

        $payload = [
            'availability_status' => $status,
            'service_scope' => $data['service_scope'] ?? $profile->service_scope,
            'vehicle_type' => $data['vehicle_type'] ?? $profile->vehicle_type,
        ];

        if ($status === 'available') {
            $payload['last_online_at'] = now();
        }

        if ($status === 'offline') {
            $payload['last_offline_at'] = now();
        }

        $profile->update($payload);

        return $profile->refresh();
    }

    /**
     * Actualiza aceptacion automatica.
     *
     * @param  array<string, mixed>  $data
     */
    public function updateAutoAcceptance(User $user, array $data): CourierProfile
    {
        $profile = $this->ensure($user);
        $profile->update([
            'auto_accept_enabled' => (bool) ($data['auto_accept_enabled'] ?? false),
            'auto_accept_max_distance_km' => $data['auto_accept_max_distance_km'] ?? $profile->auto_accept_max_distance_km,
        ]);

        return $profile->refresh();
    }

    /**
     * Recalcula metricas y nivel de recompensa.
     */
    public function refreshPerformance(User $user): CourierProfile
    {
        $profile = $this->ensure($user);

        $completedInternal = DeliveryRoute::query()
            ->where('repartidor_id', $user->id)
            ->where('estado', 'completada')
            ->count();
        $completedExternal = ExternalDeliveryOrder::query()
            ->where('repartidor_id', $user->id)
            ->where('status', 'delivered')
            ->count();
        $cancelledInternal = DeliveryRoute::query()
            ->where('repartidor_id', $user->id)
            ->where('estado', 'cancelada')
            ->count();
        $cancelledExternal = ExternalDeliveryOrder::query()
            ->where('repartidor_id', $user->id)
            ->where('status', 'cancelled')
            ->count();

        $acceptedOffers = DeliveryOffer::query()
            ->where('repartidor_id', $user->id)
            ->where('status', 'accepted')
            ->count();
        $rejectedOffers = DeliveryOffer::query()
            ->where('repartidor_id', $user->id)
            ->whereIn('status', ['rejected', 'expired'])
            ->count();

        $finishedDeliveries = $completedInternal + $completedExternal + $cancelledInternal + $cancelledExternal;
        $completedDeliveries = $completedInternal + $completedExternal;
        $totalOffers = $acceptedOffers + $rejectedOffers;
        $points = ($completedDeliveries * 10) + max(0, $acceptedOffers - $rejectedOffers);

        $profile->update([
            'reward_points' => $points,
            'reward_level' => $this->levelForPoints($points),
            'acceptance_rate' => $totalOffers === 0 ? 100 : round(($acceptedOffers / $totalOffers) * 100, 2),
            'completion_rate' => $finishedDeliveries === 0 ? 100 : round(($completedDeliveries / $finishedDeliveries) * 100, 2),
        ]);

        return $profile->refresh();
    }

    /**
     * Construye tarjeta de programa de recompensas.
     *
     * @return array<string, mixed>
     */
    public function rewardProgress(CourierProfile $profile): array
    {
        $thresholds = [
            'Aprendiz' => 0,
            'Novato' => 50,
            'Amateur' => 150,
            'Master' => 350,
            'Experto' => 700,
            'Leyenda' => 1200,
            'Leyenda Pro' => 2000,
        ];

        $current = (string) $profile->reward_level;
        $currentThreshold = $thresholds[$current] ?? 0;
        $nextLevel = collect($thresholds)
            ->filter(fn (int $points): bool => $points > (int) $profile->reward_points)
            ->keys()
            ->first();
        $nextThreshold = $nextLevel !== null ? $thresholds[$nextLevel] : $currentThreshold;
        $span = max(1, $nextThreshold - $currentThreshold);
        $progress = $nextLevel === null
            ? 100
            : min(100, (int) round((((int) $profile->reward_points - $currentThreshold) / $span) * 100));

        return [
            'current' => $current,
            'points' => (int) $profile->reward_points,
            'next' => $nextLevel,
            'progress' => max(0, $progress),
            'levels' => Arr::map(CourierProfile::REWARD_LEVELS, fn (string $level): array => [
                'name' => $level,
                'points' => $thresholds[$level] ?? 0,
            ]),
        ];
    }

    /**
     * Devuelve el nivel segun puntos.
     */
    private function levelForPoints(int $points): string
    {
        return match (true) {
            $points >= 2000 => 'Leyenda Pro',
            $points >= 1200 => 'Leyenda',
            $points >= 700 => 'Experto',
            $points >= 350 => 'Master',
            $points >= 150 => 'Amateur',
            $points >= 50 => 'Novato',
            default => 'Aprendiz',
        };
    }
}
