<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\RolPermiso\SyncRolePermissionsRequest;
use App\Services\Auth\RolPermisoService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Spatie\Permission\Models\Role;

/**
 * Controlador administrativo de roles y permisos.
 */
class RolPermisoController extends Controller
{
    /**
     * Crea una instancia del controlador.
     */
    public function __construct(private readonly RolPermisoService $rolPermisoService)
    {
    }

    /**
     * Lista roles y permisos.
     */
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Role::class);

        return view('admin.roles.index', ['data' => $this->rolPermisoService->matrix($request->all())]);
    }

    /**
     * Sincroniza permisos de un rol.
     */
    public function sync(SyncRolePermissionsRequest $request, Role $role): RedirectResponse
    {
        $this->authorize('update', $role);
        $this->rolPermisoService->syncPermissions($role, $request->validated());

        return back()->with('success', 'Permisos sincronizados correctamente.');
    }
}
