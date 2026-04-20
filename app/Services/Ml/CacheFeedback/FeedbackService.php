<?php

namespace App\Services\Ml\CacheFeedback;

use Illuminate\Support\Facades\Cache;

/**
 * Servicio de cache para feedback explicito de recomendaciones.
 */
class FeedbackService
{
    /**
     * Registra feedback del cliente sobre un producto recomendado.
     *
     * @param int $clienteId
     * @param int $productoId
     * @param string $tipo
     * @return void
     */
    public function registrar(int $clienteId, int $productoId, string $tipo): void
    {
        Cache::put($this->key($clienteId, $productoId), [
            'cliente_id' => $clienteId,
            'producto_id' => $productoId,
            'tipo' => $tipo,
            'registrado_at' => now()->toIso8601String(),
        ], now()->addDays(30));
    }

    /**
     * Obtiene feedback cacheado.
     *
     * @param int $clienteId
     * @param int $productoId
     * @return array<string, mixed>|null
     */
    public function obtener(int $clienteId, int $productoId): ?array
    {
        return Cache::get($this->key($clienteId, $productoId));
    }

    /**
     * Llave de cache.
     *
     * @param int $clienteId
     * @param int $productoId
     * @return string
     */
    private function key(int $clienteId, int $productoId): string
    {
        return "ml:feedback:{$clienteId}:{$productoId}";
    }
}
