<?php

namespace App\Services\Pedidos;

use App\Enums\EstadoPago;
use App\Enums\EstadoPedido;
use App\Events\DevolucionAprobada;
use App\Exceptions\TransaccionFallidaException;
use App\Models\Devolucion;
use App\Models\Pedido;
use App\Models\User;
use App\Services\Inventario\StockService;
use App\Services\Pagos\PasarelaPagoService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

/**
 * Servicio de devoluciones y reembolsos.
 */
class DevolucionService
{
    /**
     * Crea una instancia del servicio.
     */
    public function __construct(
        private readonly StockService $stockService,
        private readonly PasarelaPagoService $pasarelaPagoService
    ) {
    }

    /**
     * Lista devoluciones pendientes para administracion.
     */
    public function pendientes(): LengthAwarePaginator
    {
        return Devolucion::query()
            ->with(['pedido.cliente', 'pedido.items.producto', 'user'])
            ->pendientes()
            ->latest()
            ->paginate(20);
    }

    /**
     * Crea una solicitud de devolucion del cliente.
     *
     * @param array<string, mixed> $data
     */
    public function solicitar(Pedido $pedido, User $cliente, array $data): Devolucion
    {
        return DB::transaction(function () use ($pedido, $cliente, $data): Devolucion {
            $pedido = Pedido::query()
                ->with('devoluciones')
                ->whereKey($pedido->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($pedido->devoluciones()->whereIn('estado', ['solicitada', 'aprobada'])->exists()) {
                throw new TransaccionFallidaException('Este pedido ya tiene una devolucion en revision.');
            }

            $fotoPath = null;

            if (($data['foto_evidencia'] ?? null) !== null) {
                $fotoPath = $data['foto_evidencia']->store('devoluciones', 'public');
            }

            return Devolucion::query()->create([
                'pedido_id' => $pedido->id,
                'user_id' => $cliente->id,
                'motivo' => $data['motivo'],
                'descripcion' => $data['descripcion'],
                'foto_evidencia' => $fotoPath,
                'monto_reembolso' => 0,
            ]);
        });
    }

    /**
     * Resuelve una devolucion desde administracion.
     *
     * @param array<string, mixed> $data
     */
    public function resolver(Devolucion $devolucion, User $admin, array $data): Devolucion
    {
        return DB::transaction(function () use ($devolucion, $admin, $data): Devolucion {
            $devolucion = Devolucion::query()
                ->with(['pedido.items.producto.inventario', 'pedido.payments'])
                ->whereKey($devolucion->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($devolucion->estado !== 'solicitada') {
                throw new TransaccionFallidaException('La devolucion ya fue resuelta anteriormente.');
            }

            if ($data['decision'] === 'rechazada') {
                $devolucion->update([
                    'estado' => 'rechazada',
                    'notas_admin' => $data['notas_admin'] ?? null,
                    'resuelta_por' => $admin->id,
                    'resuelta_at' => now(),
                ]);

                return $devolucion->refresh();
            }

            $monto = round((float) $data['monto_reembolso'], 2);
            $pedido = $devolucion->pedido;
            $payment = $pedido->payments()->latest()->first();

            if ($payment !== null && in_array($payment->estado, [EstadoPago::Aprobado, EstadoPago::Pagado], true)) {
                $this->pasarelaPagoService->reembolsar($payment, $monto);
            }

            $this->stockService->restoreForPedido($pedido);
            $pedido->update([
                'estado' => EstadoPedido::Cancelado->value,
                'estado_pago' => EstadoPago::Reembolsado->value,
                'cancelado_at' => now(),
            ]);
            $pedido->estados()->create([
                'estado' => EstadoPedido::Cancelado->value,
                'notas' => 'Devolucion aprobada por administracion.',
                'usuario_id' => $admin->id,
            ]);

            $devolucion->update([
                'estado' => 'aprobada',
                'monto_reembolso' => $monto,
                'notas_admin' => $data['notas_admin'] ?? null,
                'resuelta_por' => $admin->id,
                'resuelta_at' => now(),
            ]);

            DevolucionAprobada::dispatch($devolucion->fresh(['pedido.cliente', 'user']));

            return $devolucion->refresh();
        });
    }
}
