<?php

namespace App\Services\Auth;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

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
    /**
     * Un administrador entra al panel de vendedor de Atlantia Store (queda registrado).
     */
    public function entrarATiendaAtlantia(Request $request, User $admin, User $operador): string
    {
        $this->start($request, $admin, $operador);
        $request->session()->put('impersonation.tienda_atlantia', true);

        AuditLog::query()->create([
            'uuid' => (string) Str::uuid(),
            'user_id' => $admin->id,
            'event' => 'tienda_atlantia.entrada',
            'auditable_type' => User::class,
            'auditable_id' => $operador->id,
            'metadata' => ['admin' => $admin->name],
            'method' => 'SERVICE',
        ]);

        return 'vendedor.dashboard';
    }

    public function stop(Request $request): ?string
    {
        $adminId = $request->session()->get('impersonation.admin_id');
        $eraTienda = (bool) $request->session()->get('impersonation.tienda_atlantia', false);

        if (! is_numeric($adminId)) {
            return null;
        }

        $admin = User::query()->find((int) $adminId);

        /* Al panel de Atlantia Store entra cualquier administrador; al resto, solo el super administrador. */
        if ($admin === null || ! ($eraTienda ? $admin->isAdministrator() : $admin->isSuperAdmin())) {
            return null;
        }

        Auth::login($admin);
        $request->session()->forget('impersonation');
        $request->session()->regenerate();

        return $eraTienda ? 'admin.tienda-atlantia.index' : $this->routeNameFor($admin);
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
            $user->hasAnyRole(['empleado', 'bodeguero', 'soporte', 'contabilidad_finanzas', 'supervisor_logistica']) => 'empleado.dashboard',
            default => 'catalogo.index',
        };
    }
}
