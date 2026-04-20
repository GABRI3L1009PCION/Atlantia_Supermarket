<?php

namespace App\Jobs;

use App\Models\Pedido;
use App\Models\User;
use App\Services\Geolocalizacion\RutaOptimaService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Calcula o actualiza ruta optima para un pedido asignado.
 */
class CalcularRutaOptima implements ShouldQueue
{
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public string $queue = 'geo';

    public int $tries = 3;

    /**
     * Crea el job.
     *
     * @param int $pedidoId
     * @param int $repartidorId
     * @param array<string, float> $origen
     */
    public function __construct(
        private readonly int $pedidoId,
        private readonly int $repartidorId,
        private readonly array $origen = []
    ) {
    }

    /**
     * Calcula ruta con Mapbox o fallback del servicio.
     *
     * @param RutaOptimaService $rutaOptimaService
     * @return void
     */
    public function handle(RutaOptimaService $rutaOptimaService): void
    {
        $pedido = Pedido::query()->with('direccion')->findOrFail($this->pedidoId);
        $repartidor = User::query()->findOrFail($this->repartidorId);

        $rutaOptimaService->asignar($pedido, $repartidor, $this->origen);
    }
}
