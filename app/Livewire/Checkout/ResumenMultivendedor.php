<?php

namespace App\Livewire\Checkout;

use App\Models\Carrito;
use App\Models\CarritoItem;
use App\Models\DeliveryZone;
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
        $impuestos = round($subtotal * 0.12, 2);

        return view('livewire.checkout.resumen-multivendedor', [
            'grupos' => $grupos,
            'subtotal' => $subtotal,
            'envio' => $envio,
            'impuestos' => $impuestos,
            'total' => round($subtotal + $envio + $impuestos, 2),
            'metodoPago' => $this->metodoPago,
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

        return (float) (DeliveryZone::query()
            ->active()
            ->municipio($direccion->municipio)
            ->orderBy('costo_base')
            ->value('costo_base') ?? 0);
    }
}
