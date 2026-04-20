<?php

namespace App\Observers;

use App\Models\User;
use Illuminate\Support\Str;

/**
 * Observer de usuarios para identificadores publicos.
 */
class UserObserver
{
    /**
     * Asigna UUID seguro antes de crear usuario.
     *
     * @param User $user
     * @return void
     */
    public function creating(User $user): void
    {
        if (empty($user->uuid)) {
            $user->uuid = (string) Str::uuid();
        }
    }
}
