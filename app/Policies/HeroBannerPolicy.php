<?php

namespace App\Policies;

use App\Models\HeroBanner;
use App\Models\User;

class HeroBannerPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'super_admin']);
    }

    public function view(User $user, HeroBanner $heroBanner): bool
    {
        return $user->hasAnyRole(['admin', 'super_admin']);
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'super_admin']);
    }

    public function update(User $user, HeroBanner $heroBanner): bool
    {
        return $user->hasAnyRole(['admin', 'super_admin']);
    }

    public function delete(User $user, HeroBanner $heroBanner): bool
    {
        return $user->hasAnyRole(['admin', 'super_admin']);
    }
}
