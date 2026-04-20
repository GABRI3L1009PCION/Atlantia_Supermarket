<?php

namespace App\Policies;

use App\Models\Ml\FraudAlert;
use App\Models\User;

/**
 * Politica de alertas antifraude.
 */
class FraudAlertPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'empleado']);
    }

    public function resolve(User $user, FraudAlert $alert): bool
    {
        return $user->hasAnyRole(['admin', 'empleado']) && ! $alert->resuelta;
    }
}

