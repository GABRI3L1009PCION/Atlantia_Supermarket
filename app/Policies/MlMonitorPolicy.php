<?php

namespace App\Policies;

use App\Models\Ml\FraudAlert;
use App\Models\Ml\RestockSuggestion;
use App\Models\Ml\SalesPrediction;
use App\Models\User;

/**
 * Politica de autorizacion para monitoreo y operaciones ML.
 */
class MlMonitorPolicy
{
    /**
     * Permite acceso global a administradores.
     *
     * @param User $user
     * @param string $ability
     * @return bool|null
     */
    public function before(User $user, string $ability): ?bool
    {
        if ($user->hasRole('admin')) {
            return true;
        }

        return null;
    }

    /**
     * Determina si el usuario puede listar recursos ML.
     *
     * @param User $user
     * @return bool
     */
    public function viewAny(User $user): bool
    {
        return $user->hasRole('empleado')
            || $user->can('view ml monitor');
    }

    /**
     * Determina si el usuario puede ver el monitor ML general.
     *
     * @param User $user
     * @return bool
     */
    public function monitorMl(User $user): bool
    {
        return $user->hasRole('empleado')
            || $user->can('view ml monitor');
    }

    /**
     * Determina si el usuario puede iniciar reentrenamiento ML.
     *
     * @param User $user
     * @return bool
     */
    public function trainMl(User $user): bool
    {
        return $user->can('train ml models');
    }

    /**
     * Determina si el usuario puede ver predicciones propias de vendedor.
     *
     * @param User $user
     * @return bool
     */
    public function viewOwnPredictions(User $user): bool
    {
        return $user->hasRole('vendedor')
            && $user->vendor()
                ->where('is_approved', true)
                ->where('status', 'approved')
                ->exists();
    }

    /**
     * Determina si el usuario puede ver una prediccion especifica.
     *
     * @param User $user
     * @param SalesPrediction $prediction
     * @return bool
     */
    public function viewPrediction(User $user, SalesPrediction $prediction): bool
    {
        return $this->ownsVendorResource($user, $prediction->vendor_id)
            || $user->hasRole('empleado')
            || $user->can('view ml monitor');
    }

    /**
     * Determina si el usuario puede ver sugerencias de reabastecimiento propias.
     *
     * @param User $user
     * @return bool
     */
    public function viewOwnRestockSuggestions(User $user): bool
    {
        return $this->viewOwnPredictions($user);
    }

    /**
     * Determina si el usuario puede aceptar una sugerencia de reabastecimiento.
     *
     * @param User $user
     * @param RestockSuggestion $suggestion
     * @return bool
     */
    public function acceptRestockSuggestion(User $user, RestockSuggestion $suggestion): bool
    {
        return $this->ownsVendorResource($user, $suggestion->vendor_id)
            && ! $suggestion->aceptada;
    }

    /**
     * Determina si el usuario puede listar alertas antifraude.
     *
     * @param User $user
     * @return bool
     */
    public function viewFraudAlerts(User $user): bool
    {
        return $user->hasRole('empleado')
            || $user->can('review fraud alerts');
    }

    /**
     * Determina si el usuario puede ver una alerta antifraude.
     *
     * @param User $user
     * @param FraudAlert $fraudAlert
     * @return bool
     */
    public function viewFraudAlert(User $user, FraudAlert $fraudAlert): bool
    {
        return $user->hasRole('empleado')
            || $user->can('review fraud alerts');
    }

    /**
     * Determina si el usuario puede resolver una alerta antifraude.
     *
     * @param User $user
     * @param FraudAlert $fraudAlert
     * @return bool
     */
    public function resolveFraudAlert(User $user, FraudAlert $fraudAlert): bool
    {
        return ($user->hasRole('empleado') || $user->can('resolve fraud alerts'))
            && ! $fraudAlert->resuelta;
    }

    /**
     * Determina si el usuario puede revisar resenas marcadas por ML.
     *
     * @param User $user
     * @return bool
     */
    public function reviewMlResenas(User $user): bool
    {
        return $user->hasRole('empleado')
            || $user->can('review ml flags');
    }

    /**
     * Determina si el usuario puede consultar logs de prediccion ML.
     *
     * @param User $user
     * @return bool
     */
    public function viewPredictionLogs(User $user): bool
    {
        return $user->can('view ml logs');
    }

    /**
     * Verifica ownership de recursos ML por vendor_id.
     *
     * @param User $user
     * @param int|null $vendorId
     * @return bool
     */
    private function ownsVendorResource(User $user, ?int $vendorId): bool
    {
        if (! $vendorId || ! $user->hasRole('vendedor')) {
            return false;
        }

        return $user->vendor()
            ->whereKey($vendorId)
            ->where('is_approved', true)
            ->where('status', 'approved')
            ->exists();
    }
}
