<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Usuario\StoreUsuarioRequest;
use App\Http\Requests\Admin\Usuario\UpdateUsuarioRequest;
use App\Models\User;
use App\Services\Auth\UsuarioService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Controlador administrativo de usuarios.
 */
class UsuarioController extends Controller
{
    /**
     * Crea una instancia del controlador.
     */
    public function __construct(private readonly UsuarioService $usuarioService)
    {
    }

    /**
     * Lista usuarios.
     */
    public function index(Request $request): View
    {
        $this->authorize('viewAny', User::class);

        return view('admin.usuarios.index', [
            'usuarios' => $this->usuarioService->paginate($request->all()),
            'roles' => \Spatie\Permission\Models\Role::query()->orderBy('name')->get(),
        ]);
    }

    /**
     * Crea un usuario desde administracion.
     */
    public function store(StoreUsuarioRequest $request): RedirectResponse
    {
        $this->authorize('create', User::class);
        $this->usuarioService->create($request->validated());

        return back()->with('success', 'Usuario creado correctamente.');
    }

    /**
     * Muestra detalle de usuario.
     */
    public function show(User $usuario): View
    {
        $this->authorize('view', $usuario);

        return view('admin.usuarios.show', [
            'usuario' => $this->usuarioService->detail($usuario),
            'roles' => \Spatie\Permission\Models\Role::query()->orderBy('name')->get(),
        ]);
    }

    /**
     * Actualiza un usuario.
     */
    public function update(UpdateUsuarioRequest $request, User $usuario): RedirectResponse
    {
        $this->authorize('update', $usuario);
        $this->usuarioService->update($usuario, $request->validated());

        return back()->with('success', 'Usuario actualizado correctamente.');
    }

    /**
     * Elimina un usuario.
     */
    public function destroy(User $usuario): RedirectResponse
    {
        $this->authorize('delete', $usuario);
        $this->usuarioService->delete($usuario);

        return redirect()->route('admin.usuarios.index')->with('success', 'Usuario eliminado correctamente.');
    }
}
