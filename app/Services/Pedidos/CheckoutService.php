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
use App\Jobs\AnalizarFraudeOrden;
use App\Jobs\EnviarCorreoFactura;
use App\Models\Carrito;
use App\Models\CarritoItem;
use App\Models\Cliente\Direccion;
use App\Models\Payment;
use App\Models\Pedido;
use App\Models\User;
use App\Models\Vendor;
use App\Services\Clientes\MembresiaService;
use App\Services\Clientes\SaldoService;
use App\Services\Fel\DteComprobantePdf;
use App\Services\Fel\DteGeneradorService;
use App\Services\Geolocalizacion\DeliveryCoverageService;
use App\Services\Inventario\StockService;
use App\Services\Promociones\CuponService;
use App\Services\Promociones\PromocionService;
use App\Support\CoberturaColonias;
use App\Support\HorarioTienda;
use App\Support\Iva;
use App\Support\Municipios;
use App\Support\PedidoMinimo;
use App\ValueObjects\Dinero;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
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
        private readonly PasarelaPagoContract $pasarelaPagoService,
        private readonly EstadoPedidoService $estadoPedidoService,
        private readonly CuponService $cuponService,
        private readonly DeliveryCoverageService $deliveryCoverageService,
        private readonly SaldoService $saldoService
    ) {}

    /**
     * Devuelve resumen seguro del checkout.
     *
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

                /* Atlantia compra al proveedor y entrega: sus productos no tienen tienda donde recogerse. */
                if ($pedidoDTO->esRecogerEnTienda() && CoberturaColonias::incluyeProductosAtlantia($items)) {
                    throw new TransaccionFallidaException('Los productos de Atlantia Store se entregan solo a domicilio. Elige Domicilio o Programado.');
                }

                $this->assertTiendasRecibenPedidos($items, $pedidoDTO);

                /* Pedido minimo por tienda: evita cobros muy pequenos que no cubren la tarifa de tarjeta. */
                $faltaMinimo = PedidoMinimo::pendientes($items)->first();
                if ($faltaMinimo !== null) {
                    throw new TransaccionFallidaException(PedidoMinimo::mensaje($faltaMinimo));
                }

                /* Recoger en tienda no requiere cobertura de entrega. */
                if (! $pedidoDTO->esRecogerEnTienda()) {
                    $this->assertColoniaCubiertaPorAtlantia($direccion, $items);
                    $this->assertDireccionDentroDeCobertura($direccion);
                }
                $this->assertValidCashChangeRequest($items, $pedidoDTO);
                $this->stockService->assertAvailableForItems($items);
                $this->stockService->reserveForItems($items);

                $totals = $this->calculateTotals($items, $direccion, $cliente, $pedidoDTO->cuponCodigo, $pedidoDTO->esRecogerEnTienda());
                $totals['propina'] = $pedidoDTO->esRecogerEnTienda() ? 0.0 : $pedidoDTO->propina;
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

                /* La dirección escrita en el pago pasa a la cuenta solo ahora que hay compra. */
                if ($direccion->borrador) {
                    $tienePrincipal = Direccion::query()->where('user_id', $cliente->id)->where('borrador', false)->where('es_principal', true)->exists();
                    $direccion->update(['borrador' => false, 'es_principal' => ! $tienePrincipal]);
                }

                if ($pedidoDTO->usarSaldo) {
                    $this->aplicarSaldo($cliente, $pedido);
                }

                try {
                    $payment = $this->pasarelaPagoService->registrarPagoCheckout($pedido, $pedidoDTO);
                } catch (PagoRechazadoException $exception) {
                    $this->stockService->releaseForPedido($pedido);
                    $this->saldoService->revertirPedido($pedido);
                    $this->cancelPedidoTree($pedido, $cliente, 'Pedido cancelado por rechazo de pago.');
                    $rejectedPayment = $exception;

                    return $pedido->refresh();
                }

                $this->splitMultivendedorService->crearSplitsDePago($payment, $pedido);
                $approvedPayment = $payment->refresh();

                if ($payment->estado !== EstadoPago::Rechazado) {
                    if (($totals['cupon'] ?? null) !== null) {
                        $this->cuponService->registrarUso($totals['cupon'], $cliente, $pedido);
                    }

                    /* Un pago en linea pendiente deja el pedido sin confirmar hasta recibir el cobro. */
                    if (! self::esperaPagoEnLinea($payment)) {
                        $this->estadoPedidoService->registrar(
                            $pedido,
                            EstadoPedido::Confirmado,
                            'Pedido confirmado por checkout.',
                            $cliente
                        );
                    }
                }

                $carrito->update(['estado' => 'convertido']);

                return $pedido->refresh();
            });

            if ($rejectedPayment instanceof PagoRechazadoException) {
                throw $rejectedPayment;
            }

            $this->emitirFacturaTarjetaPagada($pedido, $approvedPayment, $pedidoDTO->metodoPago);

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
     */
    private function activeCartFor(?User $user, ?string $sessionId = null): ?Carrito
    {
        return $user === null
            ? Carrito::query()->where('session_id', $sessionId)->where('estado', 'activo')->first()
            : Carrito::query()->where('user_id', $user->id)->where('estado', 'activo')->first();
    }

    /**
     * Obtiene carrito activo con bloqueo.
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
     */
    private function direccionFor(User $cliente, int $direccionId): Direccion
    {
        return Direccion::query()->where('user_id', $cliente->id)->whereKey($direccionId)->firstOrFail();
    }

    /**
     * Paga parte (o todo) el pedido con el saldo a favor y lo reparte entre las entregas
     * para que el repartidor cobre solo lo que falta.
     */
    private function aplicarSaldo(User $cliente, Pedido $pedido): void
    {
        $usado = $this->saldoService->aplicarAPedido($cliente, $pedido, (float) $pedido->total + (float) $pedido->propina);
        if ($usado <= 0) {
            return;
        }

        $pedido->update(['saldo_aplicado' => $usado]);
        $hijos = $pedido->pedidosHijos()->orderBy('id')->get();
        $base = max(0.01, (float) $hijos->sum(fn (Pedido $hijo): float => (float) $hijo->total + (float) $hijo->propina));
        $restante = $usado;

        foreach ($hijos as $posicion => $hijo) {
            $parte = $posicion === $hijos->count() - 1
                ? round($restante, 2)
                : round($usado * (((float) $hijo->total + (float) $hijo->propina) / $base), 2);
            $hijo->update(['saldo_aplicado' => $parte]);
            $restante -= $parte;
        }
    }

    /**
     * Fuera de horario solo se aceptan entregas programadas; una tienda en pausa no recibe pedidos.
     *
     * @param  Collection<int, CarritoItem>  $items
     */
    private function assertTiendasRecibenPedidos(Collection $items, PedidoDTO $pedidoDTO): void
    {
        $vendors = $items->map(fn (CarritoItem $item): ?Vendor => $item->producto?->vendor)->filter()->unique('id');

        foreach ($vendors as $vendor) {
            $estado = HorarioTienda::estado($vendor);

            if ($estado['pausado']) {
                throw new TransaccionFallidaException("{$vendor->business_name} no está recibiendo pedidos en este momento. {$estado['mensaje']}");
            }

            if (! $estado['abierto'] && $pedidoDTO->tipoEntrega !== 'programado') {
                throw new TransaccionFallidaException("{$vendor->business_name}: {$estado['mensaje']} Puedes elegir Programado para recibirlo en el horario que prefieras.");
            }
        }
    }

    /**
     * Rechaza colonias donde Atlantia no entrega, solo si el pedido trae productos de Atlantia.
     *
     * @param  Collection<int, mixed>  $items
     *
     * @throws DireccionFueraDeZonaException
     */
    private function assertColoniaCubiertaPorAtlantia(Direccion $direccion, Collection $items): void
    {
        if (! CoberturaColonias::incluyeProductosAtlantia($items)) {
            return;
        }

        if (($excluida = $this->deliveryCoverageService->coloniaExcluida($direccion)) !== null) {
            throw new DireccionFueraDeZonaException(
                "Por ahora Atlantia Store no entrega en {$excluida}. Puedes elegir Recoger en tienda o usar otra direccion."
            );
        }
    }

    /**
     * Verifica que la direccion pertenezca a una zona operativa activa.
     *
     * @throws DireccionFueraDeZonaException
     */
    private function assertDireccionDentroDeCobertura(Direccion $direccion): void
    {
        /* El GPS solo ubica la entrega; la direccion debe estar en un municipio y zona donde operamos. */
        if ($this->deliveryCoverageService->findActiveZoneFor($direccion) === null) {
            throw new DireccionFueraDeZonaException(
                'Por ahora solo entregamos en '.Municipios::texto().'. Revisa la direccion de entrega.'
            );
        }
    }

    /**
     * @param  Collection<int, CarritoItem>  $items
     *
     * @throws TransaccionFallidaException
     */
    private function assertValidCashChangeRequest($items, PedidoDTO $pedidoDTO): void
    {
        if ($pedidoDTO->metodoPago !== MetodoPago::Efectivo || ! $pedidoDTO->solicitaCambio) {
            return;
        }

        if ($pedidoDTO->cambioPara === null) {
            throw new TransaccionFallidaException('Debes indicar para que billete necesitas cambio.');
        }

        $subtotal = (float) $items->sum(fn ($item) => $item->precio_unitario_snapshot * $item->cantidad);
        $estimatedTotal = round($subtotal + (float) $pedidoDTO->envio->toDecimal(), 2);

        if ((float) $pedidoDTO->cambioPara <= $estimatedTotal) {
            throw new TransaccionFallidaException('El billete indicado para cambio debe ser mayor al total estimado.');
        }
    }

    /**
     * Para pagos con tarjeta aprobados, genera el DTE y envia el PDF al correo fiscal
     * en el mismo flujo, sin esperar a despacho ni a un worker de cola.
     */
    private function emitirFacturaTarjetaPagada(Pedido $pedido, ?Payment $payment, MetodoPago $metodoPago): void
    {
        if ($metodoPago !== MetodoPago::Tarjeta || $payment === null) {
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
     * Indica si el pago espera que el cliente complete un checkout en linea.
     */
    public static function esperaPagoEnLinea(?Payment $payment): bool
    {
        if ($payment === null) {
            return false;
        }

        $estado = $payment->estado instanceof EstadoPago ? $payment->estado : EstadoPago::tryFrom((string) $payment->estado);

        return ($payment->pasarela_payload['gateway'] ?? null) === 'recurrente'
            && in_array($estado, [EstadoPago::Pendiente, EstadoPago::Validando], true);
    }

    /**
     * Confirma un pedido cuyo pago en linea fue acreditado despues del checkout
     * y emite sus facturas. Debe llamarse una sola vez por pago.
     */
    public function confirmarPagoEnLinea(Payment $payment): void
    {
        $pedido = $payment->pedido()->first();

        if ($pedido === null) {
            return;
        }

        if ($pedido->estadoValor() === EstadoPedido::Pendiente->value) {
            $this->estadoPedidoService->registrar(
                $pedido,
                EstadoPedido::Confirmado,
                'Pedido confirmado por pago en linea acreditado.'
            );
        }

        $this->emitirFacturaTarjetaPagada($pedido->refresh(), $payment, MetodoPago::Tarjeta);
    }

    /**
     * Calcula totales confiando solo en precios actuales del servidor.
     *
     * @param  iterable<int, mixed>  $items
     * @return array<string, mixed>
     */
    private function calculateTotals(iterable $items, Direccion $direccion, User $cliente, ?string $cuponCodigo = null, bool $recogerEnTienda = false): array
    {
        $subtotal = Dinero::zero();
        $items = collect($items);

        foreach ($items as $item) {
            $precio = Dinero::from($item->producto->precio_oferta ?? $item->producto->precio_base);
            $subtotal = $subtotal->add($precio->multiply((int) $item->cantidad));
        }

        /* Recoger en tienda es gratis, y tambien el envio de los miembros desde el minimo. */
        $envioGratisMembresia = ! $recogerEnTienda && app(MembresiaService::class)->envioGratis($cliente, (float) $subtotal->toDecimal());
        $envio = $recogerEnTienda || $envioGratisMembresia
            ? Dinero::zero()
            : Dinero::from($this->deliveryCoverageService->deliveryCostForVendors(
                $direccion,
                $items->pluck('producto.vendor_id')->unique()->values()->all()
            ) ?? 0);
        /* Promociones automaticas (2x1, 3x2, relampago): se atribuyen al comercio de cada producto. */
        $promociones = app(PromocionService::class);
        $promo = $promociones->descuentoCarrito($items);
        $subtotalConPromo = max(0, (float) $subtotal->toDecimal() - $promo['total']);
        $subtotalPorVendor = self::subtotalesPorVendor($items, $promo['por_vendor'] ?? []);
        $respuestaCupon = $this->cuponService->resolver($cliente, $cuponCodigo, $subtotalConPromo, $subtotalPorVendor);
        $descuentoCupon = (float) ($respuestaCupon['descuento'] ?? 0);
        /* El descuento de primera compra no se combina con cupones. */
        $descuentoPrimeraCompra = $respuestaCupon['valido'] ? 0.0 : $promociones->descuentoPrimeraCompra($cliente, $subtotalConPromo);
        $descuento = Dinero::from(round($descuentoCupon + $descuentoPrimeraCompra, 2));
        $baseImponible = Dinero::from($subtotalConPromo)->subtract($descuento);
        /* Los precios de venta ya incluyen IVA: solo se desglosa, no se suma al total. */
        $impuestos = Dinero::from(Iva::incluido((float) $baseImponible->toDecimal()));
        $total = $baseImponible->add($envio);

        return [
            'subtotal' => (float) $subtotal->toDecimal(),
            'envio' => (float) $envio->toDecimal(),
            'impuestos' => (float) $impuestos->toDecimal(),
            'descuento' => (float) $descuento->toDecimal(),
            'descuento_promociones' => $promo['total'],
            'promociones_por_vendor' => $promo['por_vendor'],
            'descuento_primera_compra' => $descuentoPrimeraCompra,
            'descuento_cupon' => $descuentoCupon,
            'cupon_vendor_id' => $respuestaCupon['valido'] ? ($respuestaCupon['cupon']?->vendor_id) : null,
            'envio_gratis' => $envioGratisMembresia,
            'total' => (float) $total->toDecimal(),
            'cupon' => $respuestaCupon['valido'] ? $respuestaCupon['cupon'] : null,
        ];
    }

    /**
     * Subtotal de cada tienda en el carrito, ya con sus promociones automáticas.
     *
     * @param  iterable<int, mixed>  $items
     * @param  array<int, float>  $promosPorVendor
     * @return array<int, float>
     */
    public static function subtotalesPorVendor(iterable $items, array $promosPorVendor = []): array
    {
        $subtotales = [];
        foreach ($items as $item) {
            $vendorId = (int) ($item->producto?->vendor_id ?? 0);
            $precio = (float) ($item->producto?->precio_oferta ?? $item->producto?->precio_base ?? 0);
            $subtotales[$vendorId] = ($subtotales[$vendorId] ?? 0) + $precio * (int) $item->cantidad;
        }

        foreach ($subtotales as $vendorId => $valor) {
            $subtotales[$vendorId] = round(max(0, $valor - (float) ($promosPorVendor[$vendorId] ?? 0)), 2);
        }

        return $subtotales;
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
