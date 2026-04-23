<?php

namespace App\Policies;

use App\Enums\EstadoPago;
use App\Enums\MetodoPago;
use App\Models\Payment;
use App\Models\User;

/**
 * Politica de pagos y transferencias.
 */
class PaymentPolicy
{
    public function validateTransfers(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'empleado']);
    }

    public function validateTransfer(User $user, Payment $payment): bool
    {
        return $this->validateTransfers($user)
            && $payment->metodo === MetodoPago::Transferencia
            && $payment->estado === EstadoPago::Pendiente;
    }
}
