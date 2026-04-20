<?php

namespace App\Services\Geolocalizacion;

use App\Exceptions\GeolocalizacionException;
use App\Exceptions\TransaccionFallidaException;
use App\Models\DeliveryRoute;
use App\Models\Pedido;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Throwable;

/**
 * Servicio de rutas optimas y gestion de rutas de entrega.
 */
class RutaOptimaService
{
    /**
     * Crea una instancia del servicio.
     */
    public function __construct(
        private readonly EtaCalculadorService $etaCalculadorService,
        private readonly TspOptimizadorService $tspOptimizadorService
    ) {
    }

    /**
     * Lista rutas asignadas a un repartidor.
     *
     * @param User $repartidor
     * @return LengthAwarePaginator
     */
    public function assignedTo(User $repartidor): LengthAwarePaginator
    {
        return DeliveryRoute::query()
            ->with(['pedido.direccion'])
            ->where('repartidor_id', $repartidor->id)
            ->latest()
            ->paginate(25);
    }

    /**
     * Carga detalle completo de una ruta.
     *
     * @param DeliveryRoute $route
     * @return DeliveryRoute
     */
    public function detail(DeliveryRoute $route): DeliveryRoute
    {
        return $route->load(['pedido.direccion', 'pedido.items.producto', 'repartidor']);
    }

    /**
     * Genera vista previa de ruta desde coordenadas.
     *
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    public function preview(array $data): array
    {
        $origen = [
            'latitude' => (float) $data['origen_latitude'],
            'longitude' => (float) $data['origen_longitude'],
        ];
        $destino = [
            'latitude' => (float) $data['destino_latitude'],
            'longitude' => (float) $data['destino_longitude'],
        ];

        return $this->calcularEntrePuntos($origen, [$destino]);
    }

    /**
     * Obtiene ruta registrada para un pedido.
     *
     * @param Pedido $pedido
     * @return DeliveryRoute|null
     */
    public function forPedido(Pedido $pedido): ?DeliveryRoute
    {
        return DeliveryRoute::query()
            ->with(['pedido.direccion', 'repartidor'])
            ->where('pedido_id', $pedido->id)
            ->first();
    }

    /**
     * Asigna y planifica ruta para un pedido.
     *
     * @param Pedido $pedido
     * @param User $repartidor
     * @param array<string, mixed> $origen
     * @return DeliveryRoute
     */
    public function asignar(Pedido $pedido, User $repartidor, array $origen): DeliveryRoute
    {
        return DB::transaction(function () use ($pedido, $repartidor, $origen): DeliveryRoute {
            $pedido->loadMissing('direccion');
            $destino = [
                'latitude' => (float) $pedido->direccion->latitude,
                'longitude' => (float) $pedido->direccion->longitude,
            ];
            $ruta = $this->calcularEntrePuntos($origen, [$destino]);

            return DeliveryRoute::query()->updateOrCreate(
                ['pedido_id' => $pedido->id],
                [
                    'uuid' => (string) Str::uuid(),
                    'repartidor_id' => $repartidor->id,
                    'ruta_planificada' => $ruta,
                    'distancia_km' => $ruta['distancia_km'],
                    'tiempo_estimado_min' => $ruta['tiempo_estimado_min'],
                    'estado' => 'asignada',
                    'asignada_at' => now(),
                ]
            );
        });
    }

    /**
     * Completa ruta con evidencia de entrega.
     *
     * @param DeliveryRoute $route
     * @param array<string, mixed> $data
     * @param User $repartidor
     * @return DeliveryRoute
     *
     * @throws TransaccionFallidaException
     */
    public function complete(DeliveryRoute $route, array $data, User $repartidor): DeliveryRoute
    {
        try {
            return DB::transaction(function () use ($route, $data): DeliveryRoute {
                if (in_array($route->estado, ['completada', 'cancelada'], true)) {
                    throw new TransaccionFallidaException('La ruta no puede completarse en su estado actual.');
                }

                $inicio = $route->iniciada_at ?? $route->asignada_at ?? $route->created_at;
                $route->update([
                    'estado' => 'completada',
                    'completada_at' => now(),
                    'tiempo_real_min' => max(1, (int) $inicio->diffInMinutes(now())),
                    'firma_path' => $data['firma_path'] ?? $route->firma_path,
                    'foto_entrega_path' => $data['foto_entrega_path'] ?? $route->foto_entrega_path,
                ]);

                $route->pedido()->update(['estado' => 'entregado']);

                return $route->refresh();
            });
        } catch (TransaccionFallidaException $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            throw new TransaccionFallidaException('No fue posible completar la ruta.', previous: $exception);
        }
    }

    /**
     * Calcula ruta optimizada con Mapbox o fallback local.
     *
     * @param array<string, float> $origen
     * @param array<int, array<string, mixed>> $paradas
     * @return array<string, mixed>
     */
    public function calcularEntrePuntos(array $origen, array $paradas): array
    {
        $ordenadas = $this->tspOptimizadorService->ordenarParadas($origen, $paradas);
        $token = config('services.mapbox.token') ?: env('MAPBOX_TOKEN');

        if (empty($token) || app()->environment(['local', 'testing'])) {
            return $this->fallbackRuta($origen, $ordenadas);
        }

        $coordinates = collect([$origen, ...$ordenadas])
            ->map(fn ($punto) => $punto['longitude'] . ',' . $punto['latitude'])
            ->implode(';');

        $response = Http::timeout(15)->get("https://api.mapbox.com/directions/v5/mapbox/driving/{$coordinates}", [
            'access_token' => $token,
            'geometries' => 'geojson',
            'overview' => 'full',
            'language' => 'es',
        ]);

        if (! $response->successful()) {
            throw new GeolocalizacionException('No fue posible calcular la ruta con Mapbox.');
        }

        $route = $response->json('routes.0');
        $distanciaKm = round(((float) ($route['distance'] ?? 0)) / 1000, 2);

        return [
            'provider' => 'mapbox',
            'paradas' => $ordenadas,
            'distancia_km' => $distanciaKm,
            'tiempo_estimado_min' => (int) ceil(((float) ($route['duration'] ?? 0)) / 60),
            'geometry' => $route['geometry'] ?? null,
        ];
    }

    /**
     * Fallback local basado en distancia Haversine.
     *
     * @param array<string, float> $origen
     * @param array<int, array<string, mixed>> $paradas
     * @return array<string, mixed>
     */
    private function fallbackRuta(array $origen, array $paradas): array
    {
        $distancia = 0.0;
        $actual = $origen;
        $geometry = [[$origen['longitude'], $origen['latitude']]];

        foreach ($paradas as $parada) {
            $distancia += $this->etaCalculadorService->distanciaKm(
                $actual['latitude'],
                $actual['longitude'],
                $parada['latitude'],
                $parada['longitude']
            );
            $geometry[] = [$parada['longitude'], $parada['latitude']];
            $actual = $parada;
        }

        return [
            'provider' => 'local_haversine',
            'paradas' => $paradas,
            'distancia_km' => round($distancia, 2),
            'tiempo_estimado_min' => $this->etaCalculadorService->etaMinutos($distancia, count($paradas)),
            'geometry' => ['type' => 'LineString', 'coordinates' => $geometry],
        ];
    }
}
