<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
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

        return view('admin.usuarios.index', ['usuarios' => $this->usuarioService->paginate($request->all())]);
    }

    /**
     * Muestra detalle de usuario.
     */
    public function show(User $usuario): View
    {
        $this->authorize('view', $usuario);

        return view('admin.usuarios.show', ['usuario' => $this->usuarioService->detail($usuario)]);
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
}
