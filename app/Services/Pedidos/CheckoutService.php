<?php

namespace App\Services\Pedidos;

use App\Contracts\PasarelaPagoContract;
use App\DTOs\PedidoDTO;
use App\Enums\EstadoPago;
use App\Enums\EstadoPedido;
use App\Enums\MetodoPago;
use App\Exceptions\DireccionFueraDeZonaException;
use App\Exceptions\PagoRechazadoException;
use App\Exceptions\StockInsuficienteException;
use App\Exceptions\TransaccionFallidaException;
use App\Jobs\EnviarCorreoFactura;
use App\Jobs\AnalizarFraudeOrden;
use App\Models\Carrito;
use App\Models\Cliente\Direccion;
use App\Models\Payment;
use App\Models\Pedido;
use App\Models\User;
use App\Services\Fel\DteComprobantePdf;
use App\Services\Fel\DteGeneradorService;
use App\Services\Geolocalizacion\DeliveryCoverageService;
use App\Services\Inventario\StockService;
use App\Services\Promociones\CuponService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;
use App\ValueObjects\Dinero;

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
        private readonly PasarelaPagoContract $pasarelaPagoService,
        private readonly EstadoPedidoService $estadoPedidoService,
        private readonly CuponService $cuponService,
        private readonly DeliveryCoverageService $deliveryCoverageService
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
        $carrito = $this->activeCartFor($request->user(), $request->session()->getId());

        return [
            'carrito' => $carrito,
            'items' => $carrito?->items()->with(['producto.vendor', 'producto.inventario'])->get() ?? collect(),
        ];
    }

    /**
     * Ejecuta checkout con validacion server-side de precios y stock.
     *
     * @param User $cliente
     * @param PedidoDTO $pedidoDTO
     * @return Pedido
     *
     * @throws StockInsuficienteException
     * @throws TransaccionFallidaException
     */
    public function checkout(User $cliente, PedidoDTO $pedidoDTO): Pedido
    {
        $rejectedPayment = null;
        $approvedPayment = null;

        try {
            $pedido = DB::transaction(function () use ($cliente, $pedidoDTO, &$rejectedPayment, &$approvedPayment): Pedido {
                $carrito = $this->lockedCartFor($cliente);
                $direccion = $this->direccionFor($cliente, $pedidoDTO->direccionId);
                $items = $carrito->items()->with(['producto.vendor', 'producto.inventario'])->lockForUpdate()->get();

                if ($items->isEmpty()) {
                    throw new TransaccionFallidaException('El carrito no tiene productos para finalizar la compra.');
                }

                $this->assertDireccionDentroDeCobertura($direccion);
                $this->stockService->assertAvailableForItems($items);
                $this->stockService->reserveForItems($items);

                $totals = $this->calculateTotals($items, $direccion, $cliente, $pedidoDTO->cuponCodigo);
                $pedido = $this->splitMultivendedorService->crearPedidoDesdeCarrito(
                    $cliente,
                    $direccion,
                    $items,
                    $totals,
                    [
                        ...$pedidoDTO->toPaymentPayload(),
                        'direccion_id' => $pedidoDTO->direccionId,
                        'coupon_code' => $pedidoDTO->cuponCodigo,
                    ]
                );

                if (($totals['cupon'] ?? null) !== null) {
                    $this->cuponService->registrarUso($totals['cupon'], $cliente, $pedido);
                }

                try {
                    $payment = $this->pasarelaPagoService->registrarPagoCheckout($pedido, $pedidoDTO);
                } catch (PagoRechazadoException $exception) {
                    $this->stockService->releaseForPedido($pedido);
                    $this->cancelPedidoTree($pedido, $cliente, 'Pedido cancelado por rechazo de pago.');
                    $rejectedPayment = $exception;

                    return $pedido->refresh();
                }

                $this->splitMultivendedorService->crearSplitsDePago($payment, $pedido);
                $approvedPayment = $payment->refresh();

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

            $this->emitirFacturaTarjetaPagada($pedido, $approvedPayment, $pedidoDTO);

            AnalizarFraudeOrden::dispatch($pedido->id);

            return $pedido;
        } catch (StockInsuficienteException|DireccionFueraDeZonaException|TransaccionFallidaException $exception) {
            Log::warning('Checkout business rule failed', [
                'user_id' => $cliente->id,
                'direccion_id' => $pedidoDTO->direccionId,
                'metodo_pago' => $pedidoDTO->metodoPago->value,
                'coupon_code' => $pedidoDTO->cuponCodigo,
                'error' => $exception->getMessage(),
                'exception' => $exception::class,
            ]);

            throw $exception;
        } catch (PagoRechazadoException $exception) {
            Log::warning('Checkout payment rejected', [
                'user_id' => $cliente->id,
                'direccion_id' => $pedidoDTO->direccionId,
                'metodo_pago' => $pedidoDTO->metodoPago->value,
                'coupon_code' => $pedidoDTO->cuponCodigo,
                'error' => $exception->getMessage(),
                'exception' => $exception::class,
            ]);

            throw $exception;
        } catch (Throwable $exception) {
            Log::error('Checkout failed unexpectedly', [
                'user_id' => $cliente->id,
                'direccion_id' => $pedidoDTO->direccionId,
                'metodo_pago' => $pedidoDTO->metodoPago->value,
                'coupon_code' => $pedidoDTO->cuponCodigo,
                'error' => $exception->getMessage(),
                'trace' => $exception->getTraceAsString(),
                'exception' => $exception::class,
            ]);

            throw new TransaccionFallidaException('No fue posible completar el checkout.', previous: $exception);
        }
    }

    /**
     * Obtiene carrito activo para resumen.
     *
     * @param User|null $user
     * @return Carrito|null
     */
    private function activeCartFor(?User $user, ?string $sessionId = null): ?Carrito
    {
        return $user === null
            ? Carrito::query()->where('session_id', $sessionId)->where('estado', 'activo')->first()
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
     * Verifica que la direccion pertenezca a una zona operativa activa.
     *
     * @throws DireccionFueraDeZonaException
     */
    private function assertDireccionDentroDeCobertura(Direccion $direccion): void
    {
        if ($this->deliveryCoverageService->hasRealtimeLocation($direccion)) {
            return;
        }

        if ($this->deliveryCoverageService->findActiveZoneFor($direccion) === null) {
            throw new DireccionFueraDeZonaException(
                'Captura tu ubicacion exacta para validar la entrega del pedido.'
            );
        }
    }

    /**
     * Para pagos con tarjeta aprobados, genera el DTE y envia el PDF al correo fiscal
     * en el mismo flujo, sin esperar a despacho ni a un worker de cola.
     */
    private function emitirFacturaTarjetaPagada(Pedido $pedido, ?Payment $payment, PedidoDTO $pedidoDTO): void
    {
        if ($pedidoDTO->metodoPago !== MetodoPago::Tarjeta || $payment === null) {
            return;
        }

        $estado = $payment->estado instanceof EstadoPago
            ? $payment->estado
            : EstadoPago::tryFrom((string) $payment->estado);

        if (! in_array($estado, [EstadoPago::Aprobado, EstadoPago::Pagado], true)) {
            return;
        }

        $pedidosAFacturar = $pedido->pedidosHijos()
            ->with(['vendor.fiscalProfile', 'cliente', 'direccion', 'items.producto'])
            ->get();

        if ($pedidosAFacturar->isEmpty() && $pedido->vendor_id !== null) {
            $pedidosAFacturar = collect([$pedido->load(['vendor.fiscalProfile', 'cliente', 'direccion', 'items.producto'])]);
        }

        foreach ($pedidosAFacturar as $pedidoHijo) {
            try {
                if ($pedidoHijo->estado_pago !== EstadoPago::Pagado) {
                    $pedidoHijo->update(['estado_pago' => EstadoPago::Pagado->value]);
                }

                $dte = app(DteGeneradorService::class)->emitirParaPedido($pedidoHijo->refresh());
                $pdf = app(DteComprobantePdf::class);
                $pdf->store($dte);
                (new EnviarCorreoFactura($dte->id))->handle($pdf);
            } catch (Throwable $exception) {
                Log::warning('No fue posible enviar factura automatica tras pago con tarjeta.', [
                    'pedido_id' => $pedidoHijo->id,
                    'payment_id' => $payment->id,
                    'error' => $exception->getMessage(),
                ]);
            }
        }
    }

    /**
     * Calcula totales confiando solo en precios actuales del servidor.
     *
     * @param iterable<int, mixed> $items
     * @return array<string, mixed>
     */
    private function calculateTotals(iterable $items, Direccion $direccion, User $cliente, ?string $cuponCodigo = null): array
    {
        $subtotal = Dinero::zero();
        $items = collect($items);

        foreach ($items as $item) {
            $precio = Dinero::from($item->producto->precio_oferta ?? $item->producto->precio_base);
            $subtotal = $subtotal->add($precio->multiply((int) $item->cantidad));
        }

        $envio = Dinero::from($this->deliveryCoverageService->deliveryCostForVendors(
            $direccion,
            $items->pluck('producto.vendor_id')->unique()->values()->all()
        ) ?? 0);
        $respuestaCupon = $this->cuponService->resolver($cliente, $cuponCodigo, (float) $subtotal->toDecimal());
        $descuento = Dinero::from((float) ($respuestaCupon['descuento'] ?? 0));
        $baseImponible = $subtotal->subtract($descuento);
        $impuestos = $baseImponible->percentage(12);
        $total = $baseImponible->add($envio)->add($impuestos);

        return [
            'subtotal' => (float) $subtotal->toDecimal(),
            'envio' => (float) $envio->toDecimal(),
            'impuestos' => (float) $impuestos->toDecimal(),
            'descuento' => (float) $descuento->toDecimal(),
            'total' => (float) $total->toDecimal(),
            'cupon' => $respuestaCupon['valido'] ? $respuestaCupon['cupon'] : null,
        ];
    }

    /**
     * Cancela el pedido padre y todos sus pedidos hijos relacionados.
     */
    private function cancelPedidoTree(Pedido $pedido, User $cliente, string $nota): void
    {
        $pedido->loadMissing('pedidosHijos');

        $this->estadoPedidoService->registrar($pedido, EstadoPedido::Cancelado, $nota, $cliente);

        foreach ($pedido->pedidosHijos as $pedidoHijo) {
            $this->estadoPedidoService->registrar($pedidoHijo, EstadoPedido::Cancelado, $nota, $cliente);
        }
    }
}
