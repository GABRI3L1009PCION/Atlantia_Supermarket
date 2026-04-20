<?php

namespace App\Services\Pagos;

use App\Exceptions\TransaccionFallidaException;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * Servicio de validacion manual de transferencias.
 */
class ValidadorTransferenciaService
{
    /**
     * Lista transferencias pendientes de validacion por el equipo interno.
     *
     * @param array<string, mixed> $filters
     * @return LengthAwarePaginator
     */
    public function pending(array $filters = []): LengthAwarePaginator
    {
        return Payment::query()
            ->with(['pedido', 'pedido.cliente'])
            ->where('metodo', 'transferencia')
            ->where('estado', 'pendiente')
            ->when($filters['fecha_desde'] ?? null, fn ($query, $fecha) => $query->whereDate('created_at', '>=', $fecha))
            ->when($filters['fecha_hasta'] ?? null, fn ($query, $fecha) => $query->whereDate('created_at', '<=', $fecha))
            ->latest()
            ->paginate(20);
    }

    /**
     * Valida o rechaza una transferencia bancaria.
     *
     * @param Payment $payment
     * @param array<string, mixed> $data
     * @param User $empleado
     * @return Payment
     *
     * @throws TransaccionFallidaException
     */
    public function validar(Payment $payment, array $data, User $empleado): Payment
    {
        try {
            return DB::transaction(function () use ($payment, $data, $empleado): Payment {
                if ($payment->metodo !== 'transferencia') {
                    throw new TransaccionFallidaException('El pago no corresponde a una transferencia.');
                }

                $estado = (bool) ($data['aprobada'] ?? false) ? 'aprobado' : 'rechazado';
                $payment->update([
                    'estado' => $estado,
                    'referencia_bancaria' => $data['referencia_bancaria'] ?? $payment->referencia_bancaria,
                    'validado_por' => $empleado->id,
                    'validado_at' => now(),
                    'pasarela_payload' => [
                        'tipo' => 'transferencia_manual',
                        'observaciones' => $data['observaciones'] ?? null,
                    ],
                ]);

                $payment->pedido()->update(['estado_pago' => $estado === 'aprobado' ? 'pagado' : 'rechazado']);

                return $payment->refresh();
            });
        } catch (TransaccionFallidaException $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            throw new TransaccionFallidaException('No fue posible validar la transferencia.', previous: $exception);
        }
    }
}
