<?php

namespace App\Services\Pedidos;

use App\Models\Pedido;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * Servicio de pedidos del cliente.
 */
class PedidoClienteService
{
    /** @var array<int, string> */
    private const ACTIVE_STATES = [
        'pendiente',
        'confirmado',
        'en_revision',
        'preparando',
        'listo_para_entrega',
        'en_ruta',
    ];

    /** @var array<int, string> */
    private const HISTORY_STATES = ['entregado', 'cancelado', 'rechazado'];

    /**
     * Pagina pedidos propios del cliente.
     */
    public function paginate(User $user): LengthAwarePaginator
    {
        return $this->baseQuery($user)
            ->latest()
            ->paginate(20);
    }

    /**
     * Pedidos que todavia requieren seguimiento operativo.
     *
     * @return Collection<int, Pedido>
     */
    public function active(User $user): Collection
    {
        return $this->baseQuery($user)
            ->whereIn('estado', self::ACTIVE_STATES)
            ->latest('updated_at')
            ->get();
    }

    /**
     * Historial finalizado con filtros reales y paginacion.
     */
    public function history(
        User $user,
        string $search = '',
        string $status = 'all',
        int $perPage = 8
    ): LengthAwarePaginator {
        $search = trim($search);

        return $this->baseQuery($user)
            ->whereIn('estado', self::HISTORY_STATES)
            ->when(
                in_array($status, self::HISTORY_STATES, true),
                fn (Builder $query): Builder => $query->where('estado', $status)
            )
            ->when($search !== '', function (Builder $query) use ($search): void {
                $query->where(function (Builder $searchQuery) use ($search): void {
                    $searchQuery
                        ->where('numero_pedido', 'like', "%{$search}%")
                        ->orWhereHas('vendor', fn (Builder $vendorQuery): Builder => $vendorQuery
                            ->where('business_name', 'like', "%{$search}%"))
                        ->orWhereHas('pedidosHijos.vendor', fn (Builder $vendorQuery): Builder => $vendorQuery
                            ->where('business_name', 'like', "%{$search}%"));
                });
            })
            ->latest('updated_at')
            ->paginate($perPage);
    }

    /**
     * Resumen de estados para el panel del cliente.
     *
     * @return array<string, int|float>
     */
    public function summary(User $user): array
    {
        $baseQuery = Pedido::query()
            ->where('cliente_id', $user->id)
            ->padres();

        $completedCount = (clone $baseQuery)->where('estado', 'entregado')->count();
        $spent = (float) (clone $baseQuery)->where('estado', 'entregado')->sum('total');

        return [
            'total' => (clone $baseQuery)->count(),
            'active' => (clone $baseQuery)->whereIn('estado', self::ACTIVE_STATES)->count(),
            'active_amount' => (float) (clone $baseQuery)->whereIn('estado', self::ACTIVE_STATES)->sum('total'),
            'closed' => (clone $baseQuery)->whereIn('estado', self::HISTORY_STATES)->count(),
            'completed' => $completedCount,
            'cancelled' => (clone $baseQuery)->whereIn('estado', ['cancelado', 'rechazado'])->count(),
            'spent' => $spent,
            'average' => $completedCount > 0 ? round($spent / $completedCount, 2) : 0.0,
        ];
    }

    /**
     * Detalle de pedido propio.
     */
    public function detail(Pedido $pedido): Pedido
    {
        return $pedido->load([
            'direccion',
            'cliente',
            'items.producto.vendor',
            'pedidosHijos.vendor',
            'pedidosHijos.items.producto',
            'pedidosHijos.dteFacturas',
            'payments',
            'estados.usuario',
            'historialEstados.usuario',
            'deliveryRoute',
        ]);
    }

    /**
     * Consulta comun con toda la informacion visible para el cliente.
     *
     * @return Builder<Pedido>
     */
    private function baseQuery(User $user): Builder
    {
        return Pedido::query()
            ->with([
                'vendor',
                'direccion',
                'payments',
                'items.producto.imagenPrincipal',
                'dteFacturas',
                'deliveryRoute.repartidor',
                'pedidosHijos.vendor',
                'pedidosHijos.items.producto.imagenPrincipal',
                'pedidosHijos.dteFacturas',
            ])
            ->where('cliente_id', $user->id)
            ->padres();
    }
}
