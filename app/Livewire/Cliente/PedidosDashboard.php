<?php

namespace App\Livewire\Cliente;

use App\Models\Pedido;
use App\Services\Pedidos\PedidoClienteService;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Panel vivo de pedidos propios del cliente.
 */
class PedidosDashboard extends Component
{
    use WithPagination;

    public string $tab = 'active';

    public string $search = '';

    public string $historyStatus = 'all';

    /**
     * Selecciona una vista permitida del panel.
     */
    public function selectTab(string $tab): void
    {
        if (! in_array($tab, ['active', 'empty', 'history'], true)) {
            return;
        }

        $this->tab = $tab;
        $this->resetPage();
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedHistoryStatus(): void
    {
        $this->resetPage();
    }

    /**
     * Livewire vuelve a ejecutar render en cada intervalo de polling.
     */
    public function refreshOrders(): void {}

    public function render(PedidoClienteService $orders): View
    {
        $user = auth()->user();
        abort_unless($user !== null, 401);
        Gate::authorize('viewOwnOrders', Pedido::class);

        $summary = $orders->summary($user);

        if ((int) $summary['total'] === 0) {
            $this->tab = 'empty';
        } elseif ($this->tab === 'empty') {
            $this->tab = (int) $summary['active'] > 0 ? 'active' : 'history';
        } elseif ($this->tab === 'active' && (int) $summary['active'] === 0) {
            $this->tab = 'history';
        }

        return view('livewire.cliente.pedidos-dashboard', [
            'summary' => $summary,
            'activeOrders' => $this->tab === 'active' ? $orders->active($user) : collect(),
            'historyOrders' => $this->tab === 'history'
                ? $orders->history($user, $this->search, $this->historyStatus)
                : null,
        ]);
    }
}
