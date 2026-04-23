<?php

namespace App\Services\Pedidos;

use App\Enums\EstadoPago;
use App\Enums\EstadoPedido;
use App\Exceptions\StockInsuficienteException;
use App\Exceptions\PagoRechazadoException;
use App\Exceptions\TransaccionFallidaException;
use App\Jobs\AnalizarFraudeOrden;
use App\Models\Carrito;
use App\Models\Cliente\Direccion;
use App\Models\Pedido;
use App\Models\User;
use App\Services\Inventario\StockService;
use App\Services\Pagos\PasarelaPagoService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * Servicio de finalizacion de compra.
 */
class CheckoutService
{
    /**
     * Crea una instancia del servicio.
     */
    public function __construct(
        private readonly StockService $stockService,
        private readonly SplitMultivendedorService $splitMultivendedorService,
        private readonly PasarelaPagoService $pasarelaPagoService,
        private readonly EstadoPedidoService $estadoPedidoService
    ) {
    }

    /**
     * Devuelve resumen seguro del checkout.
     *
     * @param Request $request
     * @return array<string, mixed>
     */
    public function summary(Request $request): array
    {
        $carrito = $this->activeCartFor($request->user());

        return [
            'carrito' => $carrito,
            'items' => $carrito?->items()->with(['producto.vendor', 'producto.inventario'])->get() ?? collect(),
        ];
    }

    /**
     * Ejecuta checkout con validacion server-side de precios y stock.
     *
     * @param User $cliente
     * @param array<string, mixed> $data
     * @return Pedido
     *
     * @throws StockInsuficienteException
     * @throws TransaccionFallidaException
     */
    public function checkout(User $cliente, array $data): Pedido
    {
        $rejectedPayment = null;

        try {
            $pedido = DB::transaction(function () use ($cliente, $data, &$rejectedPayment): Pedido {
                $carrito = $this->lockedCartFor($cliente);
                $direccion = $this->direccionFor($cliente, (int) $data['direccion_id']);
                $items = $carrito->items()->with(['producto.vendor', 'producto.inventario'])->lockForUpdate()->get();

                if ($items->isEmpty()) {
                    throw new TransaccionFallidaException('El carrito no tiene productos para finalizar la compra.');
                }

                $this->stockService->assertAvailableForItems($items);
                $this->stockService->reserveForItems($items);

                $totals = $this->calculateTotals($items, (float) ($data['envio'] ?? 0));
                $pedido = $this->splitMultivendedorService->crearPedidoDesdeCarrito($cliente, $direccion, $items, $totals, $data);

                try {
                    $payment = $this->pasarelaPagoService->registrarPagoCheckout($pedido, $data);
                } catch (PagoRechazadoException $exception) {
                    $this->stockService->releaseForPedido($pedido);
                    $this->estadoPedidoService->registrar(
                        $pedido,
                        EstadoPedido::Cancelado,
                        'Pedido cancelado por rechazo de pago.',
                        $cliente
                    );
                    $rejectedPayment = $exception;

                    return $pedido->refresh();
                }

                $this->splitMultivendedorService->crearSplitsDePago($payment, $pedido);

                if ($payment->estado !== EstadoPago::Rechazado) {
                    $this->estadoPedidoService->registrar(
                        $pedido,
                        EstadoPedido::Confirmado,
                        'Pedido confirmado por checkout.',
                        $cliente
                    );
                }

                $carrito->update(['estado' => 'convertido']);

                return $pedido->refresh();
            });

            if ($rejectedPayment instanceof PagoRechazadoException) {
                throw $rejectedPayment;
            }

            AnalizarFraudeOrden::dispatch($pedido->id);

            return $pedido;
        } catch (StockInsuficienteException|TransaccionFallidaException $exception) {
            throw $exception;
        } catch (PagoRechazadoException $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            throw new TransaccionFallidaException('No fue posible completar el checkout.', previous: $exception);
        }
    }

    /**
     * Obtiene carrito activo para resumen.
     *
     * @param User|null $user
     * @return Carrito|null
     */
    private function activeCartFor(?User $user): ?Carrito
    {
        return $user === null
            ? null
            : Carrito::query()->where('user_id', $user->id)->where('estado', 'activo')->first();
    }

    /**
     * Obtiene carrito activo con bloqueo.
     *
     * @param User $user
     * @return Carrito
     */
    private function lockedCartFor(User $user): Carrito
    {
        $carrito = Carrito::query()
            ->where('user_id', $user->id)
            ->where('estado', 'activo')
            ->lockForUpdate()
            ->first();

        if ($carrito === null) {
            throw new TransaccionFallidaException('No existe un carrito activo para finalizar la compra.');
        }

        return $carrito;
    }

    /**
     * Obtiene direccion del cliente.
     *
     * @param User $cliente
     * @param int $direccionId
     * @return Direccion
     */
    private function direccionFor(User $cliente, int $direccionId): Direccion
    {
        return Direccion::query()->where('user_id', $cliente->id)->whereKey($direccionId)->firstOrFail();
    }

    /**
     * Calcula totales confiando solo en precios actuales del servidor.
     *
     * @param iterable<int, mixed> $items
     * @param float $envio
     * @return array<string, float>
     */
    private function calculateTotals(iterable $items, float $envio): array
    {
        $subtotal = 0.0;

        foreach ($items as $item) {
            $precio = (float) ($item->producto->precio_oferta ?? $item->producto->precio_base);
            $subtotal += $precio * (int) $item->cantidad;
        }

        $impuestos = round($subtotal * 0.12, 2);

        return [
            'subtotal' => round($subtotal, 2),
            'envio' => round($envio, 2),
            'impuestos' => $impuestos,
            'descuento' => 0.0,
            'total' => round($subtotal + $envio + $impuestos, 2),
        ];
    }
}
