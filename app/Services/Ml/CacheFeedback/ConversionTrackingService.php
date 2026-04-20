<?php

namespace App\Services\Ml\CacheFeedback;

use Illuminate\Support\Facades\Cache;

/**
 * Servicio de tracking de conversiones para recomendaciones.
 */
class ConversionTrackingService
{
    /**
     * Registra una conversion atribuida a recomendacion.
     *
     * @param int $clienteId
     * @param int $productoId
     * @param string $evento
     * @return void
     */
    public function track(int $clienteId, int $productoId, string $evento): void
    {
        $key = $this->key($clienteId);
        $eventos = Cache::get($key, []);
        $eventos[] = [
            'producto_id' => $productoId,
            'evento' => $evento,
            'registrado_at' => now()->toIso8601String(),
        ];

        Cache::put($key, array_slice($eventos, -100), now()->addDays(14));
    }

    /**
     * Devuelve eventos recientes del cliente.
     *
     * @param int $clienteId
     * @return array<int, array<string, mixed>>
     */
    public function recientes(int $clienteId): array
    {
        return Cache::get($this->key($clienteId), []);
    }

    /**
     * Llave de cache.
     *
     * @param int $clienteId
     * @return string
     */
    private function key(int $clienteId): string
    {
        return "ml:conversiones:{$clienteId}";
    }
}
