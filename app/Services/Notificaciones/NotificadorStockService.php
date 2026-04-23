<?php

namespace App\Services\Notificaciones;

use App\Contracts\NotificacionContract;
use App\Models\Inventario;
use App\Models\SentEmail;
use Illuminate\Support\Str;

/**
 * Servicio de notificaciones por alertas de inventario.
 */
class NotificadorStockService
{
    /**
     * Crea una instancia del servicio.
     */
    public function __construct(private readonly NotificacionContract $notificationService)
    {
    }

    /**
     * Notifica al vendedor cuando un producto cae bajo el minimo.
     *
     * @param Inventario $inventario
     * @return void
     */
    public function stockBajo(Inventario $inventario): void
    {
        $inventario->loadMissing('producto.vendor.user');
        $user = $inventario->producto?->vendor?->user;

        if ($user === null) {
            return;
        }

        $data = [
            'titulo' => 'Stock bajo',
            'mensaje' => "El producto {$inventario->producto->nombre} esta bajo el minimo configurado.",
            'producto_uuid' => $inventario->producto->uuid,
            'stock_actual' => $inventario->stock_actual,
            'stock_minimo' => $inventario->stock_minimo,
        ];

        $this->notificationService->enviar($user, 'inventario.stock_bajo', $data);

        SentEmail::query()->create([
            'uuid' => (string) Str::uuid(),
            'user_id' => $user->id,
            'to' => $user->email,
            'subject' => 'Alerta de stock bajo',
            'template' => 'inventario.stock_bajo',
            'status' => 'queued',
            'metadata' => $data,
        ]);
    }

    /**
     * Notifica varios inventarios en alerta.
     *
     * @param iterable<int, Inventario> $inventarios
     * @return int
     */
    public function stockBajoMasivo(iterable $inventarios): int
    {
        $enviadas = 0;

        foreach ($inventarios as $inventario) {
            $this->stockBajo($inventario);
            $enviadas++;
        }

        return $enviadas;
    }
}
