<?php

namespace App\Policies;

use App\Models\AuditLog;
use App\Models\User;

/**
 * Politica para consulta de auditoria.
 */
class AuditLogPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'super_admin']);
    }

    public function view(User $user, AuditLog $auditLog): bool
    {
        return $user->hasAnyRole(['admin', 'super_admin']);
    }
}
