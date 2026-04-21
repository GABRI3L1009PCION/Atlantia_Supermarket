<?php

namespace App\Http\Middleware;

use App\Models\CarritoItem;
use App\Models\Cliente\Direccion;
use App\Models\Dte\DteFactura;
use App\Models\Pedido;
use App\Models\Producto;
use App\Models\Resena;
use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Refuerza ownership sobre modelos enlazados por ruta.
 */
class VerificarOwnership
{
    /**
     * Verifica que el usuario tenga relacion directa con los recursos de ruta.
     *
     * @param Request $request
     * @param Closure(Request): Response $next
     * @return Response
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            abort(401, 'Debes iniciar sesion.');
        }

        if ($user->isAdministrator()) {
            return $next($request);
        }

        foreach ($request->route()?->parameters() ?? [] as $parameter) {
            if ($parameter instanceof Model && ! $this->owns($user, $parameter)) {
                abort(403, 'No tienes permiso para acceder a este recurso.');
            }
        }

        return $next($request);
    }

    /**
     * Evalua ownership segun el modelo recibido.
     *
     * @param mixed $user
     * @param Model $model
     * @return bool
     */
    private function owns(mixed $user, Model $model): bool
    {
        return match (true) {
            $model instanceof Producto => $this->ownsVendorId($user, $model->vendor_id),
            $model instanceof Pedido => $this->ownsPedido($user, $model),
            $model instanceof Direccion => $model->user_id === $user->id,
            $model instanceof Resena => $this->ownsResena($user, $model),
            $model instanceof DteFactura => $this->ownsDte($user, $model),
            $model instanceof CarritoItem => $this->ownsCarritoItem($user, $model),
            default => true,
        };
    }

    /**
     * Verifica ownership por vendor_id.
     *
     * @param mixed $user
     * @param int|null $vendorId
     * @return bool
     */
    private function ownsVendorId(mixed $user, ?int $vendorId): bool
    {
        return $vendorId !== null
            && $user->hasRole('vendedor')
            && $user->vendor()->whereKey($vendorId)->exists();
    }

    /**
     * Verifica ownership de pedidos por cliente, vendedor o repartidor.
     *
     * @param mixed $user
     * @param Pedido $pedido
     * @return bool
     */
    private function ownsPedido(mixed $user, Pedido $pedido): bool
    {
        if ($pedido->cliente_id === $user->id) {
            return true;
        }

        if ($this->ownsVendorId($user, $pedido->vendor_id)) {
            return true;
        }

        $pedido->loadMissing('deliveryRoute');

        return $pedido->deliveryRoute?->repartidor_id === $user->id;
    }

    /**
     * Verifica ownership de resenas.
     *
     * @param mixed $user
     * @param Resena $resena
     * @return bool
     */
    private function ownsResena(mixed $user, Resena $resena): bool
    {
        if ($resena->cliente_id === $user->id) {
            return true;
        }

        $resena->loadMissing('producto');

        return $this->ownsVendorId($user, $resena->producto?->vendor_id);
    }

    /**
     * Verifica ownership de DTE por cliente o vendedor.
     *
     * @param mixed $user
     * @param DteFactura $dte
     * @return bool
     */
    private function ownsDte(mixed $user, DteFactura $dte): bool
    {
        if ($this->ownsVendorId($user, $dte->vendor_id)) {
            return true;
        }

        $dte->loadMissing('pedido');

        return $dte->pedido?->cliente_id === $user->id;
    }

    /**
     * Verifica ownership de items de carrito.
     *
     * @param mixed $user
     * @param CarritoItem $item
     * @return bool
     */
    private function ownsCarritoItem(mixed $user, CarritoItem $item): bool
    {
        $item->loadMissing('carrito');

        return $item->carrito?->user_id === $user->id;
    }
}
