<?php

namespace App\Livewire\Checkout;

use App\Models\Cliente\Direccion;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

/**
 * Selector de direccion de entrega para checkout.
 */
class SelectorDireccion extends Component
{
    /**
     * Direccion seleccionada para entrega.
     */
    public ?int $direccionId = null;

    /**
     * Inicializa direccion principal del cliente.
     *
     * @return void
     */
    public function mount(): void
    {
        $this->direccionId = $this->direcciones()->firstWhere('es_principal', true)?->id
            ?? $this->direcciones()->first()?->id;

        if ($this->direccionId) {
            $this->dispatch('checkout.direccion-actualizada', direccionId: $this->direccionId);
        }
    }

    /**
     * Selecciona una direccion activa del cliente.
     *
     * @param int $direccionId
     * @return void
     */
    public function seleccionarDireccion(int $direccionId): void
    {
        $direccion = $this->direccionDelCliente($direccionId);
        $this->direccionId = $direccion->id;

        $this->dispatch('checkout.direccion-actualizada', direccionId: $direccion->id);
    }

    /**
     * Marca una direccion como principal.
     *
     * @param int $direccionId
     * @return void
     */
    public function marcarPrincipal(int $direccionId): void
    {
        $direccion = $this->direccionDelCliente($direccionId);

        DB::transaction(function () use ($direccion): void {
            Direccion::query()
                ->where('user_id', auth()->id())
                ->where('es_principal', true)
                ->update(['es_principal' => false]);

            $direccion->update(['es_principal' => true]);
        });

        $this->seleccionarDireccion($direccion->id);
    }

    /**
     * Renderiza direcciones disponibles.
     *
     * @return View
     */
    public function render(): View
    {
        return view('livewire.checkout.selector-direccion', [
            'direcciones' => $this->direcciones(),
        ]);
    }

    /**
     * Obtiene direcciones activas del cliente autenticado.
     *
     * @return Collection<int, Direccion>
     */
    private function direcciones(): Collection
    {
        return Direccion::query()
            ->where('user_id', auth()->id())
            ->active()
            ->orderByDesc('es_principal')
            ->orderBy('alias')
            ->get();
    }

    /**
     * Obtiene una direccion con ownership estricto.
     *
     * @param int $direccionId
     * @return Direccion
     */
    private function direccionDelCliente(int $direccionId): Direccion
    {
        return Direccion::query()
            ->where('user_id', auth()->id())
            ->active()
            ->whereKey($direccionId)
            ->firstOrFail();
    }
}
