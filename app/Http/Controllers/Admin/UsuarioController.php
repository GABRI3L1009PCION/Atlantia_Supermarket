<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Usuario\StoreUsuarioRequest;
use App\Http\Requests\Admin\Usuario\UpdateUsuarioRequest;
use App\Models\User;
use App\Services\Auth\UsuarioService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
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
            'usuarios' => $this->usuarioService->paginate($request->all(), $request->user()),
            'roles' => \Spatie\Permission\Models\Role::query()->orderBy('name')->get(),
        ]);
    }

    /**
     * Crea un usuario desde administracion.
     */
    public function store(StoreUsuarioRequest $request): RedirectResponse|JsonResponse
    {
        $this->authorize('create', User::class);
        $usuario = $this->usuarioService->create($request->validated());

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Usuario creado correctamente.',
                'user' => [
                    'id' => $usuario->id,
                    'name' => $usuario->name,
                    'email' => $usuario->email,
                    'status' => $usuario->status,
                    'roles' => $usuario->roles->pluck('name')->values(),
                ],
            ], 201);
        }

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
    public function update(UpdateUsuarioRequest $request, User $usuario): RedirectResponse|JsonResponse
    {
        $this->authorize('update', $usuario);
        $usuario = $this->usuarioService->update($usuario, $request->validated());

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Usuario actualizado correctamente.',
                'user' => [
                    'id' => $usuario->id,
                    'name' => $usuario->name,
                    'email' => $usuario->email,
                    'phone' => $usuario->phone,
                    'status' => $usuario->status,
                    'roles' => $usuario->roles->pluck('name')->values(),
                    'updated_at' => optional($usuario->updated_at)->diffForHumans(),
                ],
            ]);
        }

        return back()->with('success', 'Usuario actualizado correctamente.');
    }

    /**
     * Aplica acciones masivas desde el listado.
     */
    public function batch(Request $request): RedirectResponse|JsonResponse
    {
        $this->authorize('viewAny', User::class);

        $data = $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer', 'exists:users,id'],
            'action' => ['required', Rule::in(['role', 'activate', 'deactivate', 'delete'])],
            'role' => ['required_if:action,role', 'nullable', 'string', 'exists:roles,name'],
        ]);

        abort_if(
            $data['action'] === 'role'
                && ! $request->user()?->isSuperAdmin()
                && in_array($data['role'], ['admin', 'super_admin'], true),
            403
        );

        $affected = $this->usuarioService->batch($data['ids'], $data['action'], $data, $request->user());
        $message = "{$affected} usuario(s) actualizados correctamente.";

        if ($request->expectsJson()) {
            return response()->json(['message' => $message, 'affected' => $affected]);
        }

        return back()->with('success', $message);
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
