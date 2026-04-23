<?php

namespace App\Services\Auth;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Gestiona la impersonacion segura de usuarios operativos.
 */
class ImpersonationService
{
    /**
     * Inicia una sesion impersonada.
     */
    public function start(Request $request, User $impersonador, User $objetivo): string
    {
        $request->session()->put([
            'impersonation.active' => true,
            'impersonation.admin_id' => $impersonador->id,
            'impersonation.admin_uuid' => $impersonador->uuid,
            'impersonation.admin_name' => $impersonador->name,
            'impersonation.target_id' => $objetivo->id,
            'impersonation.target_name' => $objetivo->name,
        ]);

        Auth::login($objetivo);
        $request->session()->regenerate();

        return $this->routeNameFor($objetivo);
    }

    /**
     * Finaliza la impersonacion y restaura al super admin.
     */
    public function stop(Request $request): ?string
    {
        $adminId = $request->session()->get('impersonation.admin_id');

        if (! is_numeric($adminId)) {
            return null;
        }

        $admin = User::query()->find((int) $adminId);

        if ($admin === null || ! $admin->isSuperAdmin()) {
            return null;
        }

        Auth::login($admin);
        $request->session()->forget('impersonation');
        $request->session()->regenerate();

        return $this->routeNameFor($admin);
    }

    /**
     * Indica si la sesion actual esta impersonando.
     */
    public function isActive(Request $request): bool
    {
        return (bool) $request->session()->get('impersonation.active', false);
    }

    /**
     * Resuelve la ruta principal segun el rol activo.
     */
    public function routeNameFor(User $user): string
    {
        return match (true) {
            $user->isAdministrator() => 'admin.dashboard',
            $user->hasRole('vendedor') => 'vendedor.dashboard',
            $user->hasRole('repartidor') => 'repartidor.dashboard',
            $user->hasRole('empleado') => 'empleado.dashboard',
            default => 'catalogo.index',
        };
    }
}
