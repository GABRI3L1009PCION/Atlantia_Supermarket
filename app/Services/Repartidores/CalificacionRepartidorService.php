<?php

namespace App\Services\Repartidores;

use App\Enums\EstadoPedido;
use App\Models\CalificacionRepartidor;
use App\Models\CourierProfile;
use App\Models\DeliveryRoute;
use App\Models\Pedido;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * El cliente califica al repartidor; el promedio alimenta el reparto automático de pedidos.
 */
class CalificacionRepartidorService
{
    /**
     * Repartidor que entregó el pedido (la ruta puede estar en el pedido principal o en el de una tienda).
     */
    public function repartidorDe(Pedido $pedido): ?User
    {
        $ids = $pedido->pedidosHijos()->pluck('id')->push($pedido->id);
        $repartidorId = DeliveryRoute::query()->whereIn('pedido_id', $ids)->whereNotNull('repartidor_id')->latest('id')->value('repartidor_id');

        return $repartidorId ? User::query()->find($repartidorId) : null;
    }

    public function calificacionDe(Pedido $pedido): ?CalificacionRepartidor
    {
        return CalificacionRepartidor::query()->where('pedido_id', $pedido->id)->first();
    }

    public function puedeCalificar(Pedido $pedido, User $cliente): bool
    {
        return (int) $pedido->cliente_id === (int) $cliente->id
            && $pedido->pedido_padre_id === null
            && $pedido->estado === EstadoPedido::Entregado
            && $this->calificacionDe($pedido) === null
            && $this->repartidorDe($pedido) !== null;
    }

    public function calificar(Pedido $pedido, User $cliente, int $estrellas, ?string $comentario): CalificacionRepartidor
    {
        if (! $this->puedeCalificar($pedido, $cliente)) {
            throw ValidationException::withMessages(['estrellas' => 'Este pedido ya fue calificado o todavía no se entrega.']);
        }

        $repartidor = $this->repartidorDe($pedido);

        return DB::transaction(function () use ($pedido, $cliente, $estrellas, $comentario, $repartidor): CalificacionRepartidor {
            $calificacion = CalificacionRepartidor::query()->create([
                'pedido_id' => $pedido->id,
                'repartidor_id' => $repartidor->id,
                'cliente_id' => $cliente->id,
                'estrellas' => max(1, min(5, $estrellas)),
                'comentario' => filled($comentario) ? trim((string) $comentario) : null,
            ]);

            $this->recalcular($repartidor);

            return $calificacion;
        });
    }

    /**
     * Promedio de sus últimas 100 calificaciones (así un mal día antiguo no lo castiga para siempre).
     */
    public function recalcular(User $repartidor): void
    {
        $promedio = CalificacionRepartidor::query()->where('repartidor_id', $repartidor->id)
            ->latest('id')->limit(100)->pluck('estrellas')->avg();

        if ($promedio !== null) {
            CourierProfile::query()->where('user_id', $repartidor->id)->update(['rating' => round((float) $promedio, 2)]);
        }
    }
}
