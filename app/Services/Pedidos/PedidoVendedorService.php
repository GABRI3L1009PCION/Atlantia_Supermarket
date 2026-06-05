<?php

namespace App\Services\Pedidos;

use App\Enums\EstadoPedido;
use App\Models\Pedido;
use App\Models\User;
use App\Services\Notificaciones\NotificadorPedidoService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

/**
 * Servicio de pedidos recibidos por vendedor.
 */
class PedidoVendedorService
{
    /**
     * Pagina pedidos del vendedor autenticado.
     */
    public function paginate(User $user, array $filters = []): LengthAwarePaginator
    {
        return Pedido::query()
            ->with(['cliente.direcciones', 'direccion', 'items.producto', 'payments'])
            ->where('vendor_id', $user->vendor?->id)
            ->when($filters['q'] ?? null, function ($query, string $term): void {
                $query->where(function ($query) use ($term): void {
                    $query
                        ->where('numero_pedido', 'like', "%{$term}%")
                        ->orWhereHas('cliente', fn ($query) => $query->where('name', 'like', "%{$term}%")->orWhere('email', 'like', "%{$term}%"));
                });
            })
            ->when($filters['estado'] ?? null, fn ($query, string $estado) => $query->where('estado', $estado))
            ->when($filters['desde'] ?? null, fn ($query, string $date) => $query->whereDate('created_at', '>=', $date))
            ->when($filters['hasta'] ?? null, fn ($query, string $date) => $query->whereDate('created_at', '<=', $date))
            ->latest()
            ->paginate((int) ($filters['per_page'] ?? 8))
            ->withQueryString();
    }

    /**
     * Datos operativos para el tablero de pedidos del vendedor.
     *
     * @return array<string, mixed>
     */
    public function dashboard(User $user): array
    {
        $orders = Pedido::query()
            ->with(['cliente', 'payments'])
            ->where('vendor_id', $user->vendor?->id)
            ->latest()
            ->get();

        $total = max(1, $orders->count());
        $pendientes = $this->countByStatus($orders, EstadoPedido::Pendiente);
        $preparando = $this->countByStatus($orders, EstadoPedido::EnPreparacion);
        $listos = $this->countByStatus($orders, EstadoPedido::ListoParaEntrega);

        return [
            'metrics' => [
                'pedidos_hoy' => $orders->filter(fn (Pedido $pedido) => $pedido->created_at?->isToday())->count(),
                'pendientes' => $pendientes,
                'preparando' => $preparando,
                'listos' => $listos,
                'total' => $orders->count(),
            ],
            'percentages' => [
                'pendientes' => (int) round(($pendientes / $total) * 100),
                'preparando' => (int) round(($preparando / $total) * 100),
                'listos' => (int) round(($listos / $total) * 100),
            ],
            'summary' => [
                'tiempo_preparacion' => 28,
                'cumplimiento' => 92,
            ],
            'next_actions' => $this->nextActions($orders),
        ];
    }

    /**
     * @param Collection<int, Pedido> $orders
     * @return array<int, array<string, mixed>>
     */
    private function nextActions(Collection $orders): array
    {
        return [
            [
                'label' => $this->countByStatus($orders, EstadoPedido::Pendiente) . ' pedidos pendientes',
                'hint' => 'Requieren confirmacion',
                'tone' => 'orange',
            ],
            [
                'label' => $this->countByStatus($orders, EstadoPedido::EnPreparacion) . ' pedidos en preparacion',
                'hint' => 'Actualiza el estado',
                'tone' => 'blue',
            ],
            [
                'label' => $this->countByStatus($orders, EstadoPedido::ListoParaEntrega) . ' pedido listo para entrega',
                'hint' => 'Coordina la entrega',
                'tone' => 'green',
            ],
        ];
    }

    /**
     * @param Collection<int, Pedido> $orders
     */
    private function countByStatus(Collection $orders, EstadoPedido $estado): int
    {
        return $orders->filter(fn (Pedido $pedido) => $pedido->estadoValor() === $estado->value)->count();
    }

    /**
     * Detalle de pedido recibido.
     */
    public function detail(Pedido $pedido): Pedido
    {
        return $pedido->load(['cliente', 'direccion', 'items.producto', 'estados.usuario', 'payments']);
    }

    /**
     * Actualiza estado del pedido del vendedor.
     *
     * @param array<string, mixed> $data
     */
    public function updateEstado(Pedido $pedido, array $data, User $user): Pedido
    {
        $pedido->update(['estado' => $data['estado']]);
        $pedido->estados()->create([
            'estado' => $data['estado'],
            'notas' => $data['notas'] ?? null,
            'usuario_id' => $user->id,
        ]);

        if ($pedido->estado === EstadoPedido::ListoParaEntrega) {
            app(NotificadorPedidoService::class)->pedidoListoParaRecoger($pedido);
        }

        return $pedido->refresh();
    }
}
