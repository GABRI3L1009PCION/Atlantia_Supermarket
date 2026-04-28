<?php

namespace App\Livewire\Checkout;

use App\Models\Carrito;
use App\Models\CarritoItem;
use App\Models\Cliente\Direccion;
use App\Services\Geolocalizacion\DeliveryCoverageService;
use App\Services\Fidelizacion\PuntosService;
use App\Services\Promociones\CuponService;
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
     *
     * @return void
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
     *
     * @param int $direccionId
     * @return void
     */
    #[On('checkout.direccion-actualizada')]
    public function actualizarDireccion(int $direccionId): void
    {
        $this->direccionId = $direccionId;
    }

    /**
     * Actualiza metodo de pago seleccionado.
     *
     * @param string $metodoPago
     * @return void
     */
    #[On('checkout.metodo-pago-actualizado')]
    public function actualizarMetodoPago(string $metodoPago): void
    {
        $this->metodoPago = $metodoPago;
    }

    /**
     * Punto de refresco cuando cambia el carrito.
     *
     * @return void
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
     *
     * @return void
     */
    public function aplicarCupon(): void
    {
        $subtotal = $this->subtotal($this->items());
        $this->couponState = app(CuponService::class)->resolver(auth()->user(), $this->couponCode, $subtotal);

        $this->dispatch(
            'toast',
            type: $this->couponState['valido'] ? 'success' : 'warning',
            message: $this->couponState['mensaje']
        );
    }

    /**
     * Elimina el cupon activo del resumen.
     *
     * @return void
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
     *
     * @return View
     */
    public function render(): View
    {
        $items = $this->items();
        $grupos = $this->gruposPorVendedor($items);
        $subtotal = $this->subtotal($items);
        $envio = $this->envioEstimado();
        $descuento = (float) ($this->couponState['descuento'] ?? 0);
        $baseImponible = max(0, $subtotal - $descuento);
        $impuestos = round($baseImponible * 0.12, 2);
        $puntos = auth()->check() ? app(PuntosService::class)->saldo(auth()->user()) : null;

        return view('livewire.checkout.resumen-multivendedor', [
            'grupos' => $grupos,
            'subtotal' => $subtotal,
            'envio' => $envio,
            'descuento' => $descuento,
            'impuestos' => $impuestos,
            'total' => round($baseImponible + $envio + $impuestos, 2),
            'metodoPago' => $this->metodoPago,
            'couponState' => $this->couponState,
            'puntos' => $puntos,
            'puntosProximos' => (int) floor(round($baseImponible + $envio + $impuestos, 2) / 10),
        ]);
    }

    /**
     * Obtiene items actuales del carrito autenticado.
     *
     * @return Collection<int, CarritoItem>
     */
    private function items(): Collection
    {
        $carrito = Carrito::query()
            ->where('user_id', auth()->id())
            ->active()
            ->first();

        return $carrito?->items()
            ->with(['producto.vendor', 'producto.imagenPrincipal'])
            ->get() ?? collect();
    }

    /**
     * Agrupa items por vendedor para mostrar split operacional.
     *
     * @param Collection<int, CarritoItem> $items
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
                    'impuestos' => round($subtotal * 0.12, 2),
                ];
            })
            ->values();
    }

    /**
     * Calcula subtotal confiando en precios snapshot del carrito.
     *
     * @param Collection<int, CarritoItem> $items
     * @return float
     */
    private function subtotal(Collection $items): float
    {
        return (float) $items->sum(
            fn (CarritoItem $item) => $item->cantidad * (float) $item->precio_unitario_snapshot
        );
    }

    /**
     * Estima envio con zona global activa del municipio de entrega.
     *
     * @return float
     */
    private function envioEstimado(): float
    {
        if (! $this->direccionId) {
            return 0.0;
        }

        $direccion = auth()->user()?->direcciones()
            ->active()
            ->whereKey($this->direccionId)
            ->first();

        if (! $direccion) {
            return 0.0;
        }

        return app(DeliveryCoverageService::class)->deliveryCostFor($direccion) ?? 0.0;
    }
}
