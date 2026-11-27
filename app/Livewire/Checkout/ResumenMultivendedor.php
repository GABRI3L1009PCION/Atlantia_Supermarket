<?php

namespace App\Livewire\Checkout;

use App\Models\Carrito;
use App\Models\CarritoItem;
use App\Models\Cliente\Direccion;
use App\Services\Clientes\MembresiaService;
use App\Services\Clientes\SaldoService;
use App\Services\Fidelizacion\PuntosService;
use App\Services\Geolocalizacion\DeliveryCoverageService;
use App\Services\Promociones\CuponService;
use App\Services\Promociones\PromocionService;
use App\Support\Iva;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Livewire\Attributes\On;
use Livewire\Component;

/**
 * Resumen de checkout agrupado por vendedor.
 */
class ResumenMultivendedor extends Component
{
    /**
     * Direccion seleccionada para estimar envio.
     */
    public ?int $direccionId = null;

    /**
     * Metodo de pago actual.
     */
    public string $metodoPago = 'efectivo';

    /**
     * Propina elegida para el repartidor.
     */
    public float $propina = 0;

    /**
     * Si el cliente usa su saldo a favor.
     */
    public bool $usarSaldo = false;

    /**
     * Aceptacion de terminos del checkout.
     */
    public bool $aceptaTerminos = false;

    /**
     * Codigo de cupon ingresado por el cliente.
     */
    public string $couponCode = '';

    /**
     * Respuesta actual de cupon.
     *
     * @var array<string, mixed>
     */
    public array $couponState = [
        'valido' => false,
        'mensaje' => null,
        'descuento' => 0.0,
        'cupon' => null,
    ];

    /**
     * Inicializa la direccion usada para estimar envio.
     */
    public function mount(): void
    {
        $this->direccionId = Direccion::query()
            ->where('user_id', auth()->id())
            ->active()
            ->orderByDesc('es_principal')
            ->orderBy('alias')
            ->value('id');
    }

    /**
     * Actualiza direccion seleccionada.
     */
    #[On('checkout.direccion-actualizada')]
    public function actualizarDireccion(int $direccionId): void
    {
        $this->direccionId = $direccionId;
    }

    /**
     * Tipo de entrega elegido (recoger en tienda no cobra envio).
     */
    public string $tipoEntrega = 'domicilio';

    /**
     * Datos de entrega del invitado para estimar el envio sin direccion guardada.
     *
     * @var array{municipio: ?string, latitude: ?float, longitude: ?float, direccion: ?string}
     */
    public array $entregaInvitado = ['municipio' => null, 'latitude' => null, 'longitude' => null, 'direccion' => null];

    /**
     * Recibe desde el formulario el tipo de entrega y la zona elegida para recalcular el envio.
     */
    #[On('checkout.entrega-actualizada')]
    public function actualizarEntrega(
        string $tipoEntrega = 'domicilio',
        ?string $municipio = null,
        mixed $latitude = null,
        mixed $longitude = null,
        ?string $direccion = null
    ): void {
        $this->tipoEntrega = in_array($tipoEntrega, ['domicilio', 'recoger', 'programado'], true) ? $tipoEntrega : 'domicilio';
        $this->entregaInvitado = [
            'municipio' => $municipio !== null && trim($municipio) !== '' ? mb_substr(trim($municipio), 0, 80) : null,
            'latitude' => is_numeric($latitude) && abs((float) $latitude) <= 90 ? (float) $latitude : null,
            'longitude' => is_numeric($longitude) && abs((float) $longitude) <= 180 ? (float) $longitude : null,
            'direccion' => $direccion !== null ? mb_substr(trim($direccion), 0, 500) : null,
        ];
    }

    /**
     * Actualiza metodo de pago seleccionado.
     */
    #[On('checkout.extras-actualizados')]
    public function actualizarExtras(float $propina = 0, bool $usarSaldo = false): void
    {
        $this->propina = round(max(0, min(500, $propina)), 2);
        $this->usarSaldo = $usarSaldo;
    }

    #[On('checkout.metodo-pago-actualizado')]
    public function actualizarMetodoPago(string $metodoPago): void
    {
        $this->metodoPago = $metodoPago;
    }

    /**
     * Punto de refresco cuando cambia el carrito.
     */
    #[On('carrito.actualizado')]
    public function recalcular(): void
    {
        unset($this->direccionId);
    }

    /**
     * Valida en tiempo real la aceptacion de terminos.
     */
    public function updatedAceptaTerminos(): void
    {
        $this->validateOnly('aceptaTerminos', [
            'aceptaTerminos' => ['accepted'],
        ], [
            'aceptaTerminos.accepted' => 'Debes aceptar los terminos y condiciones para continuar.',
        ]);
    }

    /**
     * Valida un cupon en tiempo real.
     */
    public function aplicarCupon(): void
    {
        $items = $this->items();
        $promo = app(PromocionService::class)->descuentoCarrito($items);
        $subtotal = max(0, $this->subtotal($items) - $promo['total']);
        $this->couponState = app(CuponService::class)->resolver(
            auth()->user(),
            $this->couponCode,
            $subtotal,
            \App\Services\Pedidos\CheckoutService::subtotalesPorVendor($items, $promo['por_vendor'] ?? []),
        );

        $this->dispatch(
            'toast',
            type: $this->couponState['valido'] ? 'success' : 'warning',
            message: $this->couponState['mensaje']
        );
    }

    /**
     * Elimina el cupon activo del resumen.
     */
    public function quitarCupon(): void
    {
        $this->couponCode = '';
        $this->couponState = [
            'valido' => false,
            'mensaje' => null,
            'descuento' => 0.0,
            'cupon' => null,
        ];
    }

    /**
     * Renderiza resumen multivendedor.
     */
    public function render(): View
    {
        $items = $this->items();
        $grupos = $this->gruposPorVendedor($items);
        $subtotal = $this->subtotal($items);
        $envioGratisMembresia = $this->tipoEntrega !== 'recoger' && app(MembresiaService::class)->envioGratis(auth()->user(), $subtotal);
        $grupos = $this->conEnvioPorTienda($grupos, $envioGratisMembresia);
        $envioCalculado = $envioGratisMembresia ? 0.0 : $this->envioEstimado();
        /* Sin direccion todavia no se conoce el envio: se muestra pendiente y no se suma al total. */
        $envioPendiente = $envioCalculado === null;
        $envio = $envioCalculado ?? 0.0;
        $promociones = app(PromocionService::class);
        $promo = $promociones->descuentoCarrito($items);
        $subtotalConPromo = max(0, $subtotal - $promo['total']);
        $descuento = (float) ($this->couponState['descuento'] ?? 0);
        $descuentoPrimeraCompra = ($this->couponState['valido'] ?? false) ? 0.0 : $promociones->descuentoPrimeraCompra(auth()->user(), $subtotalConPromo);
        $baseImponible = max(0, $subtotalConPromo - $descuento - $descuentoPrimeraCompra);
        /* IVA incluido en el precio: se muestra como desglose, no se suma al total. */
        $impuestos = Iva::incluido($baseImponible);
        $total = round($baseImponible + $envio, 2);
        $propina = $this->tipoEntrega === 'recoger' ? 0.0 : $this->propina;
        $saldoDisponible = auth()->check() ? app(SaldoService::class)->disponible(auth()->user()) : 0.0;
        $saldoUsado = $this->usarSaldo ? round(min($saldoDisponible, $total + $propina), 2) : 0.0;
        $puntos = auth()->check() ? app(PuntosService::class)->saldo(auth()->user()) : null;

        return view('livewire.checkout.resumen-multivendedor', [
            'grupos' => $grupos,
            'totalEntregas' => $this->tipoEntrega === 'recoger' ? 0 : $grupos->count(),
            'subtotal' => $subtotal,
            'envio' => $envio,
            'envioPendiente' => $envioPendiente,
            'descuento' => $descuento,
            'promociones' => $promo['detalle'],
            'envioGratisMembresia' => $envioGratisMembresia,
            'descuentoPrimeraCompra' => $descuentoPrimeraCompra,
            'impuestos' => $impuestos,
            'total' => $total,
            'propina' => $propina,
            'saldoUsado' => $saldoUsado,
            'totalAPagar' => round(max(0, $total + $propina - $saldoUsado), 2),
            'metodoPago' => $this->metodoPago,
            'couponState' => $this->couponState,
            'puntos' => $puntos,
            'puntosProximos' => (int) floor($total / 10),
        ]);
    }

    /**
     * Obtiene items actuales del carrito autenticado o visitante.
     *
     * @return Collection<int, CarritoItem>
     */
    private function items(): Collection
    {
        $carritoQuery = Carrito::query()->active();
        $carrito = auth()->check()
            ? $carritoQuery->where('user_id', auth()->id())->first()
            : $carritoQuery->where('session_id', session()->getId())->first();

        return $carrito?->items()
            ->with(['producto.vendor', 'producto.imagenPrincipal'])
            ->get() ?? collect();
    }

    /**
     * Agrupa items por vendedor para mostrar split operacional.
     *
     * @param  Collection<int, CarritoItem>  $items
     * @return Collection<int, array<string, mixed>>
     */
    private function gruposPorVendedor(Collection $items): Collection
    {
        return $items
            ->groupBy(fn (CarritoItem $item) => $item->producto?->vendor_id ?? 0)
            ->map(function (Collection $vendorItems): array {
                $vendor = $vendorItems->first()?->producto?->vendor;
                $subtotal = $this->subtotal($vendorItems);

                return [
                    'vendor' => $vendor,
                    'items' => $vendorItems,
                    'subtotal' => $subtotal,
                    'impuestos' => Iva::incluido($subtotal),
                ];
            })
            ->values();
    }

    /**
     * Calcula subtotal confiando en precios snapshot del carrito.
     *
     * @param  Collection<int, CarritoItem>  $items
     */
    private function subtotal(Collection $items): float
    {
        return (float) $items->sum(
            fn (CarritoItem $item) => $item->cantidad * (float) $item->precio_unitario_snapshot
        );
    }

    /**
     * Direccion con la que se estima el envio: la guardada del cliente o la que escribe el invitado.
     */
    private function direccionEntrega(): ?Direccion
    {
        $direccion = $this->direccionId
            ? auth()->user()?->direcciones()->active()->whereKey($this->direccionId)->first()
            : null;

        /* Invitado: se estima con la zona elegida (y el GPS si lo compartio), sin guardar nada. */
        if (! $direccion && ! auth()->check() && $this->entregaInvitado['municipio'] && mb_strlen((string) $this->entregaInvitado['direccion']) >= 5) {
            $direccion = new Direccion([
                'municipio' => $this->entregaInvitado['municipio'],
                'latitude' => $this->entregaInvitado['latitude'],
                'longitude' => $this->entregaInvitado['longitude'],
                'direccion_linea_1' => $this->entregaInvitado['direccion'],
            ]);
        }

        return $direccion;
    }

    /**
     * Envio de cada tienda (cada una es una entrega con su repartidor). Null si aun no hay direccion.
     *
     * @param  Collection<int, array<string, mixed>>  $grupos
     * @return Collection<int, array<string, mixed>>
     */
    private function conEnvioPorTienda(Collection $grupos, bool $envioGratis): Collection
    {
        $direccion = ($this->tipoEntrega === 'recoger' || $envioGratis) ? null : $this->direccionEntrega();
        $cobertura = app(DeliveryCoverageService::class);

        return $grupos->map(function (array $grupo) use ($direccion, $envioGratis, $cobertura): array {
            $grupo['envio'] = match (true) {
                $this->tipoEntrega === 'recoger', $envioGratis => 0.0,
                $direccion === null => null,
                default => $cobertura->deliveryCostForVendor($direccion, $grupo['vendor']?->id) ?? null,
            };

            return $grupo;
        });
    }

    /**
     * Estima envio con zona global activa del municipio de entrega.
     */
    private function envioEstimado(): ?float
    {
        if ($this->tipoEntrega === 'recoger') {
            return 0.0;
        }

        $direccion = $this->direccionEntrega();

        if (! $direccion) {
            return null;
        }

        $vendorIds = $this->items()
            ->pluck('producto.vendor_id')
            ->unique()
            ->values()
            ->all();

        return app(DeliveryCoverageService::class)->deliveryCostForVendors($direccion, $vendorIds) ?? 0.0;
    }
}
