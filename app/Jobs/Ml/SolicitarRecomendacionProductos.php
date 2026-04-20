<?php

namespace App\Jobs\Ml;

use App\Models\User;
use App\Services\Ml\RecomendacionService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Solicita recomendaciones personalizadas para un cliente.
 */
class SolicitarRecomendacionProductos implements ShouldQueue
{
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public string $queue = 'ml';

    /**
     * Crea el job.
     *
     * @param int $clienteId
     * @param int $limit
     */
    public function __construct(private readonly int $clienteId, private readonly int $limit = 12)
    {
    }

    /**
     * Genera recomendaciones para el cliente.
     *
     * @param RecomendacionService $recomendacionService
     * @return void
     */
    public function handle(RecomendacionService $recomendacionService): void
    {
        $cliente = User::query()->findOrFail($this->clienteId);

        $recomendacionService->generarParaCliente($cliente, $this->limit);
    }
}
