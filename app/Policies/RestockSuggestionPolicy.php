<?php

namespace App\Policies;

use App\Models\Ml\RestockSuggestion;
use App\Models\User;

/**
 * Politica de sugerencias de reabasto.
 */
class RestockSuggestionPolicy
{
    public function viewOwnRestockSuggestions(User $user): bool
    {
        return $user->hasRole('vendedor') && $user->vendor !== null;
    }

    public function accept(User $user, RestockSuggestion $suggestion): bool
    {
        return (int) $suggestion->vendor_id === (int) $user->vendor?->id && ! $suggestion->aceptada;
    }
}

